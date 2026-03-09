<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueHealthService
{
    private const HEALTH_CHECK_KEY = 'queue_health_check';
    private const HEALTH_CHECK_TTL = 300; // 5 minutos

    /**
     * Verificar si el queue worker está funcionando
     */
    public function isQueueWorkerRunning(): bool
    {
        try {
            // Verificar si hay jobs pendientes muy antiguos (más de 5 minutos)
            $oldJobs = DB::table('jobs')
                ->where('created_at', '<', now()->subMinutes(5))
                ->count();

            // Si hay más de 10 jobs atascados, la cola está muerta
            if ($oldJobs > 10) {
                Log::channel('queue')->warning('Queue worker parece estar detenido', [
                    'old_jobs_count' => $oldJobs,
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('queue')->error('Error verificando health de cola', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtener fallback: ¿usar cola o envío síncrono?
     */
    public function shouldUseQueue(): bool
    {
        // Verificar si QUEUE_CONNECTION es sync
        if (config('queue.default') === 'sync') {
            return false;
        }

        // Verificar cache de health check
        $cachedHealth = Cache::get(self::HEALTH_CHECK_KEY);
        if ($cachedHealth !== null) {
            return $cachedHealth;
        }

        // Hacer health check
        $isHealthy = $this->isQueueWorkerRunning();
        Cache::put(self::HEALTH_CHECK_KEY, $isHealthy, self::HEALTH_CHECK_TTL);

        return $isHealthy;
    }

    /**
     * Forzar health check inmediato
     */
    public function forceHealthCheck(): bool
    {
        Cache::forget(self::HEALTH_CHECK_KEY);
        return $this->shouldUseQueue();
    }

    /**
     * Obtener estadísticas de la cola
     */
    public function getStats(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $oldestJob = DB::table('jobs')
                ->orderBy('created_at', 'asc')
                ->first();

            return [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'oldest_job_age_minutes' => $oldestJob 
                    ? now()->diffInMinutes($oldestJob->created_at)
                    : 0,
                'is_healthy' => $this->isQueueWorkerRunning(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'is_healthy' => false,
            ];
        }
    }
}
