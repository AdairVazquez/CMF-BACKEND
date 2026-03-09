<?php

namespace App\Notifications\Auth;

use App\Services\QueueHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AccountLockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $attempts,
        public int $lockMinutes = 15
    ) {
        $queueHealth = app(QueueHealthService::class);
        if (!$queueHealth->shouldUseQueue()) {
            $this->connection = 'sync';
            Log::channel('queue')->warning('Queue worker caído, usando envío síncrono para account locked');
        }
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cuenta bloqueada por intentos fallidos')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu cuenta ha sido bloqueada temporalmente.')
            ->line('Se detectaron ' . $this->attempts . ' intentos fallidos de inicio de sesión.')
            ->line('Tu cuenta estará bloqueada por ' . $this->lockMinutes . ' minutos.')
            ->line('Si no fuiste tú, cambia tu contraseña inmediatamente.')
            ->salutation('Saludos, Sistema CMF');
    }
}
