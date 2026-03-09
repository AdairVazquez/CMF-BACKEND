<?php

namespace App\Console\Commands;

use App\Services\QueueHealthService;
use Illuminate\Console\Command;

class QueueHealthCheckCommand extends Command
{
    protected $signature = 'queue:health';
    protected $description = 'Verificar estado del queue worker';

    public function handle(QueueHealthService $queueHealth): int
    {
        $this->info('=== QUEUE HEALTH CHECK ===');
        $this->newLine();

        $stats = $queueHealth->getStats();

        if (isset($stats['error'])) {
            $this->error("Error: {$stats['error']}");
            return Command::FAILURE;
        }

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Jobs pendientes', $stats['pending_jobs']],
                ['Jobs fallidos', $stats['failed_jobs']],
                ['Job más antiguo (minutos)', $stats['oldest_job_age_minutes']],
                ['Estado', $stats['is_healthy'] ? '✓ Saludable' : '✗ Caído'],
            ]
        );

        if (!$stats['is_healthy']) {
            $this->newLine();
            $this->warn('⚠ Queue worker parece estar detenido o atascado.');
            $this->info('Soluciones:');
            $this->line('  1. Reinicia el worker: php artisan queue:restart');
            $this->line('  2. Ejecuta: .\\start-queue-worker.ps1');
            $this->line('  3. Procesa manualmente: php artisan queue:work --once');
            
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Queue worker funcionando correctamente');
        return Command::SUCCESS;
    }
}
