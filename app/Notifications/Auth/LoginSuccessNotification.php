<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginSuccessNotification extends Notification
{

    public function __construct(
        public string $ip,
        public string $device,
        public string $timestamp
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo inicio de sesión detectado')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Se ha detectado un nuevo inicio de sesión en tu cuenta.')
            ->line('**Detalles del acceso:**')
            ->line('Dirección IP: ' . $this->ip)
            ->line('Dispositivo: ' . $this->device)
            ->line('Fecha y hora: ' . $this->timestamp)
            ->line('Si no fuiste tú, cambia tu contraseña inmediatamente y contacta al administrador.')
            ->salutation('Saludos, Sistema CMF');
    }
}
