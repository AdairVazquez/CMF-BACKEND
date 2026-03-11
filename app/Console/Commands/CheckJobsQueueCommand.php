<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckJobsQueueCommand extends Command
{
    protected $signature = 'queue:check-jobs';

    protected $description = 'Verificar jobs en cola';

    public function handle(): int
    {
        $count = DB::table('jobs')->count();
        $this->info("Jobs encolados: {$count}");

        if ($count > 0) {
            $this->line("\nPrimeros 5 jobs:");
            DB::table('jobs')->limit(5)->get(['id', 'queue', 'created_at'])->each(function($job) {
                $this->line("  - Job #{$job->id} | Queue: {$job->queue} | Creado: {$job->created_at}");
            });
            
            $this->warn("\n¡Tienes jobs encolados! Ejecuta: php artisan queue:work");
        } else {
            $this->info("✓ No hay jobs encolados.");
        }

        return Command::SUCCESS;
    }
}
