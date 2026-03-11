<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:health';

    /**
     * The console command description.
     */
    protected $description = 'Verificar el estado de Redis y el sistema de cache con fallback';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $this->info('=== Estado del Sistema de Cache ===');
        $this->newLine();

        // Verificar Redis
        $this->info('1. Verificando Redis...');
        $redisAvailable = $cacheService->isRedisAvailable();
        
        if ($redisAvailable) {
            $this->components->twoColumnDetail('Redis', '<fg=green>✓ Conectado</>');
            
            try {
                $ping = Redis::connection()->ping();
                $this->components->twoColumnDetail('  Ping', $ping);
            } catch (\Exception $e) {
                $this->components->twoColumnDetail('  Error', '<fg=red>' . $e->getMessage() . '</>');
            }
        } else {
            $this->components->twoColumnDetail('Redis', '<fg=red>✗ No disponible</>');
            $this->warn('  Redis no está disponible. El sistema usará cache local como fallback.');
        }

        $this->newLine();

        // Verificar cache local (file)
        $this->info('2. Verificando cache local...');
        try {
            Cache::store('file')->put('health_check', 'ok', 60);
            $value = Cache::store('file')->get('health_check');
            
            if ($value === 'ok') {
                $this->components->twoColumnDetail('Cache local (file)', '<fg=green>✓ Funcionando</>');
            } else {
                $this->components->twoColumnDetail('Cache local (file)', '<fg=yellow>⚠ Respuesta inesperada</>');
            }
            
            Cache::store('file')->forget('health_check');
        } catch (\Exception $e) {
            $this->components->twoColumnDetail('Cache local (file)', '<fg=red>✗ Error</>');
            $this->error('  ' . $e->getMessage());
        }

        $this->newLine();

        // Probar CacheService con fallback
        $this->info('3. Probando CacheService con fallback...');
        try {
            $cacheService->put('test_key', 'test_value', 60);
            $value = $cacheService->get('test_key');
            
            if ($value === 'test_value') {
                $this->components->twoColumnDetail('CacheService', '<fg=green>✓ Funcionando correctamente</>');
            } else {
                $this->components->twoColumnDetail('CacheService', '<fg=yellow>⚠ Respuesta inesperada</>');
            }
            
            $cacheService->forget('test_key');
        } catch (\Exception $e) {
            $this->components->twoColumnDetail('CacheService', '<fg=red>✗ Error</>');
            $this->error('  ' . $e->getMessage());
        }

        $this->newLine();

        // Mostrar estadísticas
        $this->info('4. Estadísticas del sistema:');
        $stats = $cacheService->getStats();
        
        $this->components->twoColumnDetail('  Store primario', $stats['primary_store']);
        foreach ($stats['stores'] as $store => $status) {
            $color = $status === 'online' ? 'green' : 'red';
            $this->components->twoColumnDetail("  Store: $store", "<fg=$color>$status</>");
        }

        $this->newLine();

        if ($redisAvailable) {
            $this->components->info('Sistema de cache funcionando correctamente con Redis');
            return self::SUCCESS;
        } else {
            $this->components->warn('Redis no disponible. Funcionando con cache local de emergencia');
            return self::SUCCESS;
        }
    }
}
