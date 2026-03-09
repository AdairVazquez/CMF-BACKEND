<?php

namespace App\Enums;

enum CardStatus: string
{
    case ACTIVA = 'activa';
    case INACTIVA = 'inactiva';
    case BLOQUEADA = 'bloqueada';
    case PERDIDA = 'perdida';

    public function label(): string
    {
        return match($this) {
            self::ACTIVA => 'Activa',
            self::INACTIVA => 'Inactiva',
            self::BLOQUEADA => 'Bloqueada',
            self::PERDIDA => 'Perdida',
        };
    }
}
