<?php

namespace App\Notifications\Auth;

use App\Services\QueueHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $expiresInMinutes = 10,
        public string $context = 'login'
    ) {
        // Si la cola está caída, forzar envío síncrono
        $queueHealth = app(QueueHealthService::class);
        if (!$queueHealth->shouldUseQueue()) {
            $this->connection = 'sync';
            Log::channel('queue')->warning('Queue worker caído, usando envío síncrono para 2FA');
        }
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = "Código 2FA: {$this->code} - " . config('app.name');
        
        $body = "¡Hola {$notifiable->name}!\n\n";
        
        if ($this->context === 'login') {
            $body .= "Estás intentando iniciar sesión. Tu código de verificación es:\n\n";
            $body .= "===== {$this->code} =====\n\n";
            $body .= "Este código expira en {$this->expiresInMinutes} minutos.\n";
            $body .= "Si no fuiste tú, ignora este mensaje y cambia tu contraseña.\n\n";
        } else {
            $body .= "Para activar 2FA, tu código de verificación es:\n\n";
            $body .= "===== {$this->code} =====\n\n";
            $body .= "Válido por {$this->expiresInMinutes} minutos.\n\n";
        }
        
        $body .= "Saludos, " . config('app.name');

        return (new MailMessage)
            ->subject($subject)
            ->line($body);
    }
}
