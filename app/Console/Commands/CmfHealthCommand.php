<?php

namespace App\Console\Commands;

use App\Services\SmartCacheService;
use App\Services\SmartQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CmfHealthCommand extends Command
{
    protected $signature = 'cmf:health';
    protected $description = 'Diagnóstico completo del sistema CMF';

    public function handle(SmartCacheService $cache, SmartQueueService $queue): int
    {
        $this->printHeader();

        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache($cache),
            'queue' => $this->checkQueue($queue),
            'storage' => $this->checkStorage(),
            'logs' => $this->checkLogs(),
        ];

        $this->printChecks($checks);
        
        $overallStatus = $this->determineStatus($checks);
        $this->printFooter($overallStatus, $checks);

        return $overallStatus === 'critical' ? self::FAILURE : self::SUCCESS;
    }

    private function printHeader(): void
    {
        $this->newLine();
        $this->line(str_repeat('=', 65));
        $this->info('CMF - Diagnóstico del Sistema');
        $this->line('Fecha: ' . now()->format('d/m/Y H:i:s'));
        $this->line('Entorno: ' . app()->environment());
        $this->line('Versión: ' . config('cmf.version', '1.0.0'));
        $this->line(str_repeat('=', 65));
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseMs = round((microtime(true) - $start) * 1000);

            return [
                'status' => 'online',
                'label' => 'EN LÍNEA',
                'detail' => "({$responseMs}ms)",
                'color' => 'green',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'label' => 'FALLO',
                'detail' => '(no conecta)',
                'color' => 'red',
            ];
        }
    }

    private function checkCache(SmartCacheService $cache): array
    {
        try {
            $start = microtime(true);
            $stats = $cache->getStats();
            $responseMs = round((microtime(true) - $start) * 1000);

            if ($stats['redis_available']) {
                return [
                    'status' => 'online',
                    'label' => 'EN LÍNEA',
                    'detail' => "({$responseMs}ms)",
                    'color' => 'green',
                    'extra' => null,
                ];
            }

            return [
                'status' => 'degraded',
                'label' => 'FALLO',
                'detail' => '(timeout)',
                'color' => 'red',
                'extra' => [
                    'Cache Fallback' => [
                        'label' => 'ACTIVO',
                        'detail' => '(usando archivos)',
                        'color' => 'yellow',
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label' => 'ERROR',
                'detail' => '',
                'color' => 'red',
            ];
        }
    }

    private function checkQueue(SmartQueueService $queue): array
    {
        try {
            $stats = $queue->getStats();

            if ($stats['redis_available']) {
                $pending = $stats['pending_jobs'];
                $detail = $pending > 0 ? "({$pending} pendientes)" : '';
                
                return [
                    'status' => 'online',
                    'label' => 'EN LÍNEA',
                    'detail' => $detail,
                    'color' => 'green',
                ];
            }

            return [
                'status' => 'degraded',
                'label' => 'DEGRADADO',
                'detail' => '(usando base de datos)',
                'color' => 'yellow',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label' => 'ERROR',
                'detail' => '',
                'color' => 'red',
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $freeSpaceMB = round(disk_free_space($storagePath) / 1024 / 1024);
            $writable = is_writable($storagePath);

            if (!$writable) {
                return [
                    'status' => 'offline',
                    'label' => 'NO ESCRIBIBLE',
                    'detail' => '',
                    'color' => 'red',
                ];
            }

            $criticalSpace = config('cmf.health.critical_free_space_mb', 100);
            
            if ($freeSpaceMB < $criticalSpace) {
                return [
                    'status' => 'critical',
                    'label' => 'CRÍTICO',
                    'detail' => "({$freeSpaceMB} MB libres)",
                    'color' => 'red',
                ];
            }

            return [
                'status' => 'online',
                'label' => 'ESCRIBIBLE',
                'detail' => "({$freeSpaceMB} MB libres)",
                'color' => 'green',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label' => 'ERROR',
                'detail' => '',
                'color' => 'red',
            ];
        }
    }

    private function checkLogs(): array
    {
        try {
            $logPath = storage_path('logs');
            $writable = is_writable($logPath);

            return [
                'status' => $writable ? 'online' : 'offline',
                'label' => $writable ? 'ESCRIBIBLE' : 'NO ESCRIBIBLE',
                'detail' => '',
                'color' => $writable ? 'green' : 'red',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'label' => 'ERROR',
                'detail' => '',
                'color' => 'red',
            ];
        }
    }

    private function printChecks(array $checks): void
    {
        $labels = [
            'database' => 'Base de datos MySQL',
            'cache' => 'Cache Redis',
            'queue' => 'Colas',
            'storage' => 'Almacenamiento',
            'logs' => 'Logs',
        ];

        foreach ($checks as $key => $check) {
            $label = str_pad($labels[$key], 30);
            $status = str_pad($check['label'], 15);
            
            $this->line(
                "{$label} : " . 
                "<fg={$check['color']}>{$status}</> " .
                "<fg=gray>{$check['detail']}</>"
            );

            // Mostrar información extra (como fallback)
            if (isset($check['extra'])) {
                foreach ($check['extra'] as $extraLabel => $extraCheck) {
                    $extraLabelPadded = str_pad("  {$extraLabel}", 30);
                    $extraStatus = str_pad($extraCheck['label'], 15);
                    
                    $this->line(
                        "{$extraLabelPadded} : " . 
                        "<fg={$extraCheck['color']}>{$extraStatus}</> " .
                        "<fg=gray>{$extraCheck['detail']}</>"
                    );
                }
            }
        }
    }

    private function determineStatus(array $checks): string
    {
        // Crítico si database o storage están offline
        if ($checks['database']['status'] === 'offline' || 
            $checks['storage']['status'] === 'offline' ||
            $checks['storage']['status'] === 'critical') {
            return 'critical';
        }

        // Degradado si cache o queue están degradados
        if ($checks['cache']['status'] === 'degraded' || 
            $checks['queue']['status'] === 'degraded') {
            return 'degraded';
        }

        // Saludable si todo está online
        return 'healthy';
    }

    private function printFooter(string $status, array $checks): void
    {
        $this->line(str_repeat('=', 65));
        
        $messages = [
            'healthy' => [
                'label' => 'SALUDABLE',
                'color' => 'green',
            ],
            'degraded' => [
                'label' => 'DEGRADADO   (sistema funcional)',
                'color' => 'yellow',
            ],
            'critical' => [
                'label' => 'CRÍTICO     (sistema no funcional)',
                'color' => 'red',
            ],
        ];

        $statusInfo = $messages[$status];
        $statusLabel = str_pad('Estado general', 30);
        
        $this->line(
            "{$statusLabel} : <fg={$statusInfo['color']}>{$statusInfo['label']}</>"
        );
        
        $this->line(str_repeat('=', 65));

        // Recomendaciones
        if ($status === 'degraded') {
            $this->newLine();
            
            if ($checks['cache']['status'] === 'degraded') {
                $this->warn('Recomendación: Verificar conexión con Redis');
                $this->line('  Comando: docker-compose ps');
                $this->line('  Comando: docker-compose logs redis');
            }
        }

        if ($status === 'critical') {
            $this->newLine();
            
            if ($checks['database']['status'] === 'offline') {
                $this->error('Acción requerida: Verificar base de datos MySQL');
                $this->line('  1. Verificar que MySQL esté corriendo');
                $this->line('  2. Verificar credenciales en .env');
                $this->line('  3. Verificar permisos de conexión');
            }
            
            if ($checks['storage']['status'] === 'offline' || 
                $checks['storage']['status'] === 'critical') {
                $this->error('Acción requerida: Verificar almacenamiento');
                $this->line('  1. Liberar espacio en disco');
                $this->line('  2. Verificar permisos de storage/');
            }
        }

        $this->newLine();
    }
}
