<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueRestartCommand extends Command
{
    protected $signature = 'queue:auto-restart';
    protected $description = 'Detectar y reiniciar queue worker si está caído';

    public function handle(): int
    {
        $this->info('Verificando estado del queue worker...');

        // Verificar jobs atascados
        $stuckJobs = DB::table('jobs')
            ->where('created_at', '<', now()->subMinutes(10))
            ->count();

        if ($stuckJobs > 0) {
            $this->warn("Detectados {$stuckJobs} jobs atascados. Reiniciando worker...");
            
            // Enviar señal de restart a workers existentes
            $this->call('queue:restart');
            
            $this->info('✓ Señal de restart enviada.');
            $this->info('Ejecuta manualmente: .\\start-queue-worker.ps1');
            
            return Command::FAILURE;
        }

        $this->info('✓ Queue worker funcionando correctamente.');
        return Command::SUCCESS;
    }
}
