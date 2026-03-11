<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\Auth\TwoFactorCodeNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Test2FAEmailCommand extends Command
{
    protected $signature = 'test:2fa-email {email}';

    protected $description = 'Envía un código 2FA de prueba al email especificado';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado.");
            return Command::FAILURE;
        }

        $code = '123456';

        $this->info("Enviando código 2FA de prueba a {$user->email}...");
        $this->line("Código: {$code}");

        try {
            $user->notify(new TwoFactorCodeNotification($code, 10, 'login'));
            
            $this->info('✓ Notificación enviada correctamente.');
            Log::channel('mail')->info('Test 2FA email sent', [
                'to' => $user->email,
                'code' => $code,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Error al enviar: ' . $e->getMessage());
            Log::channel('mail')->error('Test 2FA email failed', [
                'to' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
