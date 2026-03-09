<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test';

    protected $description = 'Envía un email de prueba a joshuapaz24@gmail.com';

    public function handle(): int
    {
        $to = 'joshuapaz24@gmail.com';
        $subject = config('app.name') . ' - Email de prueba';

        $this->info('Enviando email de prueba a ' . $to . '...');

        try {
            Mail::raw(
                "Este es un email de prueba enviado desde " . config('app.name') . ".\n\n"
                . "Fecha: " . now()->format('Y-m-d H:i:s') . "\n"
                . "Entorno: " . config('app.env') . "\n\n"
                . "Si recibiste este mensaje, la configuración de correo está funcionando correctamente.",
                function ($message) use ($to, $subject) {
                    $message->to($to)
                        ->subject($subject);
                }
            );

            $this->info('✓ Email enviado correctamente.');
            Log::channel('mail')->info('Email de prueba enviado correctamente', [
                'to' => $to,
                'subject' => $subject,
                'sent_at' => now()->toIso8601String(),
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Error al enviar el email: ' . $e->getMessage());
            Log::channel('mail')->error('Error al enviar email de prueba', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
