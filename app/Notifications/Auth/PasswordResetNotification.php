<?php

namespace App\Notifications\Auth;

use App\Services\QueueHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $expiresInMinutes = 15
    ) {
        $queueHealth = app(QueueHealthService::class);
        if (!$queueHealth->shouldUseQueue()) {
            $this->connection = 'sync';
            Log::channel('queue')->warning('Queue worker caído, usando envío síncrono para reset password');
        }
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de recuperación de contraseña')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Has solicitado restablecer tu contraseña.')
            ->line('Tu código de verificación es:')
            ->line('**' . $this->code . '**')
            ->line('Este código expira en ' . $this->expiresInMinutes . ' minutos.')
            ->line('Si no solicitaste este cambio, ignora este mensaje.')
            ->salutation('Saludos, Sistema CMF');
    }
}
