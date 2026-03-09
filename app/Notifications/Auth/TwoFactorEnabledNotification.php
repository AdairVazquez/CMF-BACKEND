<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorEnabledNotification extends Notification
{

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Autenticación de dos factores activada')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('La autenticación de dos factores (2FA) ha sido activada en tu cuenta.')
            ->line('A partir de ahora, necesitarás ingresar un código de verificación al iniciar sesión.')
            ->line('Guarda tus códigos de recuperación en un lugar seguro.')
            ->line('Si no activaste esta función, contacta al administrador inmediatamente.')
            ->salutation('Saludos, Sistema CMF');
    }
}
