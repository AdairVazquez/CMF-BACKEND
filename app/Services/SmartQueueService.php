<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redis;

class SmartQueueService
{
    private bool $redisAvailable;
    private string $activeDriver;

    public function __construct()
    {
        $this->redisAvailable = $this->checkRedisAvailability();
        $this->activeDriver = $this->determineDriver();
        $this->ensureJobsTableExists();
    }

    /**
     * Despachar trabajo con fallback automático
     */
    public function dispatch($job): void
    {
        $primaryDriver = $this->getPrimaryDriver();
        $fallbackDriver = $this->getFallbackDriver();

        try {
            if ($this->redisAvailable && $primaryDriver === 'redis') {
                Queue::connection('redis')->push($job);
                return;
            }
        } catch (\Exception $e) {
            Log::error('Error despachando trabajo en Redis', [
                'job' => get_class($job),
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback automático
        try {
            Queue::connection($fallbackDriver)->push($job);
        } catch (\Exception $fallbackError) {
            Log::critical('Fallback de cola también falló', [
                'job' => get_class($job),
                'error' => $fallbackError->getMessage(),
            ]);
            throw $fallbackError;
        }
    }

    /**
     * Obtener driver activo
     */
    public function getActiveDriver(): string
    {
        return $this->activeDriver;
    }

    /**
     * Obtener driver primario de configuración
     */
    private function getPrimaryDriver(): string
    {
        return config('cmf.fallbacks.queue.primary', 'redis');
    }

    /**
     * Obtener driver de fallback de configuración
     */
    private function getFallbackDriver(): string
    {
        return config('cmf.fallbacks.queue.fallback', 'database');
    }

    /**
     * Obtener cantidad de trabajos pendientes
     */
    public function getPendingCount(): int
    {
        try {
            if ($this->activeDriver === 'database') {
                return DB::table('jobs')->count();
            }

            // Para Redis, intentar obtener el tamaño de la cola
            if ($this->redisAvailable) {
                try {
                    return Redis::connection()->llen('queues:default');
                } catch (\Exception $e) {
                    return 0;
                }
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning('Error obteniendo trabajos pendientes', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtener cantidad de trabajos fallidos
     */
    public function getFailedCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            Log::warning('Error obteniendo trabajos fallidos', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Verificar disponibilidad de Redis
     */
    private function checkRedisAvailability(): bool
    {
        if (!config('cmf.redis.auto_detect', true)) {
            return false;
        }

        $primaryDriver = config('cmf.fallbacks.queue.primary', 'redis');
        if ($primaryDriver !== 'redis') {
            return false;
        }

        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            if (config('cmf.logging.cache_fallback')) {
                Log::info('Redis no disponible para colas, usando fallback automático', [
                    'fallback_driver' => $this->getFallbackDriver(),
                ]);
            }
            return false;
        }
    }

    /**
     * Determinar driver activo
     */
    private function determineDriver(): string
    {
        if ($this->redisAvailable && $this->getPrimaryDriver() === 'redis') {
            return 'redis';
        }

        return $this->getFallbackDriver();
    }

    /**
     * Asegurar que la tabla jobs existe
     */
    private function ensureJobsTableExists(): void
    {
        if ($this->activeDriver !== 'database') {
            return;
        }

        try {
            if (!Schema::hasTable('jobs')) {
                Log::warning('Tabla jobs no existe. Ejecuta: php artisan queue:table && php artisan migrate');
            }

            if (!Schema::hasTable('failed_jobs')) {
                Log::warning('Tabla failed_jobs no existe. Ejecuta: php artisan queue:failed-table && php artisan migrate');
            }
        } catch (\Exception $e) {
            Log::error('Error verificando tablas de colas', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener estadísticas de colas
     */
    public function getStats(): array
    {
        $primaryDriver = $this->getPrimaryDriver();
        $fallbackDriver = $this->getFallbackDriver();
        
        return [
            'redis_available' => $this->redisAvailable,
            'active_driver' => $this->activeDriver,
            'primary_driver' => $primaryDriver,
            'fallback_driver' => $fallbackDriver,
            'pending_jobs' => $this->getPendingCount(),
            'failed_jobs' => $this->getFailedCount(),
            'fallback_active' => !$this->redisAvailable && $primaryDriver === 'redis',
        ];
    }

    /**
     * Limpiar trabajos fallidos antiguos
     */
    public function cleanOldFailedJobs(int $days = 7): int
    {
        try {
            $count = DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays($days))
                ->delete();

            if ($count > 0 && config('cmf.logging.queue_failures')) {
                Log::info("Limpiados {$count} trabajos fallidos antiguos");
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('Error limpiando trabajos fallidos', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
