<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Obtener valor del cache con fallback automático
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            // Intentar obtener de Redis
            return Cache::store('redis')->get($key, $default);
        } catch (\Exception $e) {
            Log::warning('Redis no disponible, usando cache local', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Fallback a cache de archivos
            return Cache::store('file')->get($key, $default);
        }
    }

    /**
     * Guardar en cache con fallback automático
     */
    public function put(string $key, mixed $value, int|\DateTimeInterface|\DateInterval|null $ttl = null): bool
    {
        try {
            // Intentar guardar en Redis
            Cache::store('redis')->put($key, $value, $ttl);
            
            // También guardar en cache local como backup
            Cache::store('file')->put($key, $value, $ttl);
            
            return true;
        } catch (\Exception $e) {
            Log::warning('Redis no disponible, solo guardando en cache local', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Fallback: solo guardar en cache de archivos
            return Cache::store('file')->put($key, $value, $ttl);
        }
    }

    /**
     * Recordar valor en cache (get o compute)
     */
    public function remember(string $key, int|\DateTimeInterface|\DateInterval $ttl, \Closure $callback): mixed
    {
        try {
            // Intentar usar Redis
            $value = Cache::store('redis')->remember($key, $ttl, $callback);
            
            // Sincronizar con cache local
            Cache::store('file')->put($key, $value, $ttl);
            
            return $value;
        } catch (\Exception $e) {
            Log::warning('Redis no disponible, usando cache local para remember', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Fallback a cache de archivos
            return Cache::store('file')->remember($key, $ttl, $callback);
        }
    }

    /**
     * Olvidar (eliminar) una clave del cache
     */
    public function forget(string $key): bool
    {
        $success = true;

        try {
            Cache::store('redis')->forget($key);
        } catch (\Exception $e) {
            Log::warning('No se pudo eliminar de Redis', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
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
     * Limpiar todo el cache
     */
    public function flush(): bool
    {
        $success = true;

        try {
            Cache::store('redis')->flush();
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
     * Verificar si Redis está disponible
     */
    public function isRedisAvailable(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener estadísticas del cache
     */
    public function getStats(): array
    {
        return [
            'redis_available' => $this->isRedisAvailable(),
            'primary_store' => config('cache.default'),
            'stores' => [
                'redis' => $this->isRedisAvailable() ? 'online' : 'offline',
                'file' => 'online',
            ],
        ];
    }
}
