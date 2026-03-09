<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class SmartCacheService
{
    private bool $redisAvailable;
    private string $activeDriver;
    private int $lastRedisCheck;
    private const REDIS_CHECK_INTERVAL = 300; // 5 minutos

    public function __construct()
    {
        $this->lastRedisCheck = time();
        $this->redisAvailable = $this->checkRedisAvailability();
        $this->activeDriver = $this->determineDriver();
    }

    /**
     * Obtener valor del cache con fallback automático
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->recheckRedisIfNeeded();

        // Intentar primary (redis)
        if ($this->redisAvailable && $this->shouldUseRedis()) {
            try {
                return Cache::store('redis')->get($key, $default);
            } catch (\Exception $e) {
                $this->handleRedisFailure('get', $key, $e);
            }
        }

        // Fallback automático
        return Cache::store($this->getFallbackDriver())->get($key, $default);
    }

    /**
     * Guardar en cache con sincronización dual
     */
    public function put(string $key, mixed $value, int|\DateTimeInterface|\DateInterval|null $ttl = null): bool
    {
        $this->recheckRedisIfNeeded();

        $success = false;

        // Intentar guardar en primary (redis)
        if ($this->redisAvailable && $this->shouldUseRedis()) {
            try {
                Cache::store('redis')->put($key, $value, $ttl);
                $success = true;
            } catch (\Exception $e) {
                $this->handleRedisFailure('put', $key, $e);
            }
        }

        // Siempre guardar en fallback como backup
        try {
            Cache::store($this->getFallbackDriver())->put($key, $value, $ttl);
            $success = true;
        } catch (\Exception $e) {
            Log::error('Error guardando en cache fallback', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }

        return $success;
    }

    /**
     * Remember con fallback automático
     */
    public function remember(string $key, int|\DateTimeInterface|\DateInterval $ttl, \Closure $callback): mixed
    {
        $this->recheckRedisIfNeeded();

        // Intentar usar primary (redis)
        if ($this->redisAvailable && $this->shouldUseRedis()) {
            try {
                $value = Cache::store('redis')->remember($key, $ttl, $callback);
                
                // Sincronizar con fallback
                Cache::store($this->getFallbackDriver())->put($key, $value, $ttl);
                
                return $value;
            } catch (\Exception $e) {
                $this->handleRedisFailure('remember', $key, $e);
            }
        }

        // Fallback automático
        return Cache::store($this->getFallbackDriver())->remember($key, $ttl, $callback);
    }

    /**
     * Olvidar clave con tags
     */
    public function forget(string $key): bool
    {
        $success = true;

        try {
            if ($this->redisAvailable) {
                Cache::store('redis')->forget($key);
            }
        } catch (\Exception $e) {
            $this->handleRedisFailure('forget', $key, $e);
            $success = false;
        }

        try {
            Cache::store('file')->forget($key);
        } catch (\Exception $e) {
            Log::warning('No se pudo eliminar del cache local', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            $success = false;
        }

        return $success;
    }

    /**
     * Limpiar cache por tag o todo
     */
    public function flush(?string $tag = null): bool
    {
        if ($tag) {
            return $this->flushByTag($tag);
        }

        $success = true;

        try {
            if ($this->redisAvailable) {
                Cache::store('redis')->flush();
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo limpiar Redis', [
                'error' => $e->getMessage(),
            ]);
            $success = false;
        }

        try {
            Cache::store('file')->flush();
        } catch (\Exception $e) {
            Log::warning('No se pudo limpiar el cache local', [
                'error' => $e->getMessage(),
            ]);
            $success = false;
        }

        return $success;
    }

    /**
     * Limpiar cache por tag (company, employee, device, permissions)
     */
    private function flushByTag(string $tag): bool
    {
        // En Redis con tags nativos
        if ($this->redisAvailable) {
            try {
                Cache::store('redis')->tags([$tag])->flush();
                
                if (config('cmf.logging.cache_fallback')) {
                    Log::info('Cache limpiado por tag', ['tag' => $tag]);
                }
                
                return true;
            } catch (\Exception $e) {
                $this->handleRedisFailure('flush-tag', $tag, $e);
            }
        }

        // Fallback: buscar claves con patrón
        $this->flushByPattern($tag);
        
        return true;
    }

    /**
     * Limpiar por patrón (cuando no hay Redis con tags)
     */
    private function flushByPattern(string $pattern): void
    {
        // Aquí se implementaría limpieza manual por patrón en archivos
        // Por ahora, limpiamos todo el cache local relacionado
        try {
            $prefix = config('cache.prefix');
            $keys = [
                "{$prefix}{$pattern}:*",
            ];
            
            foreach ($keys as $key) {
                Cache::store('file')->forget($key);
            }
        } catch (\Exception $e) {
            Log::warning('Error limpiando cache por patrón', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verificar disponibilidad de Redis con timeout
     */
    public function isRedisAvailable(): bool
    {
        return $this->redisAvailable;
    }

    /**
     * Verificar si debemos usar Redis
     */
    private function shouldUseRedis(): bool
    {
        if (!config('cmf.redis.auto_detect', true)) {
            return false;
        }

        $primaryDriver = config('cmf.fallbacks.cache.primary', 'redis');
        return $primaryDriver === 'redis';
    }

    /**
     * Obtener driver de fallback
     */
    private function getFallbackDriver(): string
    {
        return config('cmf.fallbacks.cache.fallback', 'file');
    }

    /**
     * Chequear Redis con timeout de 100ms
     */
    private function checkRedisAvailability(): bool
    {
        if (!config('cmf.redis.auto_detect', true)) {
            return false;
        }

        $primaryDriver = config('cmf.fallbacks.cache.primary', 'redis');
        if ($primaryDriver !== 'redis') {
            return false;
        }

        try {
            $start = microtime(true);
            Redis::connection()->ping();
            $duration = (microtime(true) - $start) * 1000; // ms

            if ($duration > config('cmf.redis.timeout', 100)) {
                Log::warning('Redis responde lento', [
                    'response_time_ms' => $duration,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if (config('cmf.logging.cache_fallback')) {
                Log::info('Redis no disponible, usando fallback automático', [
                    'error' => $e->getMessage(),
                    'fallback_driver' => $this->getFallbackDriver(),
                ]);
            }
            return false;
        }
    }

    /**
     * Re-chequear Redis cada 5 minutos
     */
    private function recheckRedisIfNeeded(): void
    {
        $now = time();
        $interval = config('cmf.redis.reconnect_interval', 300);

        if (($now - $this->lastRedisCheck) >= $interval) {
            $wasAvailable = $this->redisAvailable;
            $this->redisAvailable = $this->checkRedisAvailability();
            $this->lastRedisCheck = $now;

            // Loggear cambios de estado
            if ($wasAvailable !== $this->redisAvailable) {
                $status = $this->redisAvailable ? 'recuperado' : 'caído';
                Log::info("Redis {$status}", [
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }

            $this->activeDriver = $this->determineDriver();
        }
    }

    /**
     * Determinar driver activo
     */
    private function determineDriver(): string
    {
        if ($this->redisAvailable && $this->shouldUseRedis()) {
            return 'redis';
        }

        return $this->getFallbackDriver();
    }

    /**
     * Obtener driver activo
     */
    public function getActiveDriver(): string
    {
        return $this->activeDriver;
    }

    /**
     * Obtener estadísticas del cache
     */
    public function getStats(): array
    {
        $primaryDriver = config('cmf.fallbacks.cache.primary', 'redis');
        $fallbackDriver = config('cmf.fallbacks.cache.fallback', 'file');
        
        return [
            'redis_available' => $this->redisAvailable,
            'active_driver' => $this->activeDriver,
            'primary_driver' => $primaryDriver,
            'fallback_driver' => $fallbackDriver,
            'fallback_active' => !$this->redisAvailable && $primaryDriver === 'redis',
            'stores' => [
                'redis' => $this->redisAvailable ? 'online' : 'offline',
                $fallbackDriver => 'online',
            ],
            'last_check' => date('Y-m-d H:i:s', $this->lastRedisCheck),
        ];
    }

    /**
     * Precarga de datos frecuentes (cache warming)
     */
    public function warmUp(): void
    {
        if (!config('cmf.cache_warming.enabled', true)) {
            return;
        }

        $config = config('cmf.cache_warming.items', []);

        try {
            // Permisos y roles
            if ($config['permissions'] ?? true) {
                $this->remember('permissions:all', 3600, function () {
                    return DB::table('permissions')->get();
                });
            }

            // Módulos activos por empresa
            if ($config['company_modules'] ?? true) {
                $companies = DB::table('companies')->where('deleted_at', null)->pluck('id');
                
                foreach ($companies as $companyId) {
                    $this->remember("company:{$companyId}:modules", 3600, function () use ($companyId) {
                        return DB::table('company_modules')
                            ->where('company_id', $companyId)
                            ->where('is_active', true)
                            ->pluck('module_name')
                            ->toArray();
                    });
                }
            }

            // Dispositivos activos
            if ($config['active_devices'] ?? true) {
                $this->remember('devices:active', 600, function () {
                    return DB::table('devices')
                        ->where('status', 'activo')
                        ->where('deleted_at', null)
                        ->get();
                });
            }

            Log::info('Cache warming completado');
        } catch (\Exception $e) {
            Log::error('Error en cache warming', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manejar fallos de Redis
     */
    private function handleRedisFailure(string $operation, string $key, \Exception $e): void
    {
        $this->redisAvailable = false;
        $this->activeDriver = $this->determineDriver();

        if (config('cmf.logging.cache_fallback')) {
            Log::warning('Redis falló, usando cache local', [
                'operation' => $operation,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
