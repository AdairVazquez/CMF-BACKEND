<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SmartCacheService;
use App\Services\SmartQueueService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/system/health
     * Health check público del sistema
     */
    public function health(SmartCacheService $cache, SmartQueueService $queue): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache($cache),
            'queue' => $this->checkQueue($queue),
            'storage' => $this->checkStorage(),
        ];

        $status = $this->determineOverallStatus($services);
        $statusCode = $this->getStatusCode($status);

        $response = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'version' => config('cmf.version', '1.0.0'),
            'services' => $this->formatServices($services),
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Verificar conexión a base de datos
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'online',
                'driver' => config('database.default'),
                'response_ms' => $responseMs,
                'message' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'driver' => config('database.default'),
                'response_ms' => null,
                'message' => 'Base de datos no disponible',
            ];
        }
    }

    /**
     * Verificar sistema de cache
     */
    private function checkCache(SmartCacheService $cache): array
    {
        try {
            $start = microtime(true);
            $stats = $cache->getStats();
            $responseMs = round((microtime(true) - $start) * 1000, 2);

            $status = $stats['redis_available'] ? 'online' : 'degraded';
            $message = $stats['fallback_active'] ? 'Redis no disponible, usando cache local' : null;

            return [
                'status' => $status,
                'driver' => $stats['active_driver'],
                'fallback_active' => $stats['fallback_active'],
                'response_ms' => $responseMs,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'driver' => 'file',
                'fallback_active' => true,
                'response_ms' => null,
                'message' => 'Cache funcionando con fallback',
            ];
        }
    }

    /**
     * Verificar sistema de colas
     */
    private function checkQueue(SmartQueueService $queue): array
    {
        try {
            $stats = $queue->getStats();
            
            $status = $stats['redis_available'] ? 'online' : 'degraded';
            $message = $stats['fallback_active'] ? 'Colas usando base de datos' : null;

            return [
                'status' => $status,
                'driver' => $stats['active_driver'],
                'pending_jobs' => $stats['pending_jobs'],
                'failed_jobs' => $stats['failed_jobs'],
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'driver' => 'database',
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'message' => 'Colas funcionando con fallback',
            ];
        }
    }

    /**
     * Verificar almacenamiento
     */
    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $freeSpaceMB = round(disk_free_space($storagePath) / 1024 / 1024, 2);
            
            $writable = is_writable($storagePath);
            $criticalSpace = config('cmf.health.critical_free_space_mb', 100);
            $warningSpace = config('cmf.health.warning_free_space_mb', 500);

            $status = 'online';
            $message = null;

            if (!$writable) {
                $status = 'offline';
                $message = 'Directorio storage no escribible';
            } elseif ($freeSpaceMB < $criticalSpace) {
                $status = 'critical';
                $message = 'Espacio en disco crítico';
            } elseif ($freeSpaceMB < $warningSpace) {
                $status = 'warning';
                $message = 'Espacio en disco bajo';
            }

            return [
                'status' => $status,
                'writable' => $writable,
                'free_space_mb' => $freeSpaceMB,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'writable' => false,
                'free_space_mb' => 0,
                'message' => 'No se pudo verificar almacenamiento',
            ];
        }
    }

    /**
     * Determinar estado general del sistema
     */
    private function determineOverallStatus(array $services): string
    {
        // Critical: Base de datos o storage offline
        if ($services['database']['status'] === 'offline' || 
            $services['storage']['status'] === 'offline' ||
            $services['storage']['status'] === 'critical') {
            return 'critical';
        }

        // Degraded: Cache o Queue con fallback activo
        if ($services['cache']['status'] === 'degraded' || 
            $services['queue']['status'] === 'degraded' ||
            $services['storage']['status'] === 'warning') {
            return 'degraded';
        }

        // Healthy: Todo online
        return 'healthy';
    }

    /**
     * Obtener código HTTP según estado
     */
    private function getStatusCode(string $status): int
    {
        return match($status) {
            'critical' => 503, // Service Unavailable
            'degraded' => 200, // OK pero con advertencia
            'healthy' => 200,  // OK
            default => 200,
        };
    }

    /**
     * Formatear servicios para respuesta
     */
    private function formatServices(array $services): array
    {
        $exposeMetrics = config('cmf.health.expose_metrics', false);

        if (!$exposeMetrics && app()->environment('production')) {
            // En producción sin expose_metrics, solo mostrar status
            return array_map(function ($service) {
                return [
                    'status' => $service['status'],
                    'message' => $service['message'] ?? null,
                ];
            }, $services);
        }

        // En desarrollo o con expose_metrics, mostrar todo
        return $services;
    }
}
