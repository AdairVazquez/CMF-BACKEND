<?php

namespace App\Enums;

enum AttendanceType: string
{
    case ENTRADA = 'entrada';
    case SALIDA = 'salida';

    public function label(): string
    {
        return match($this) {
            self::ENTRADA => 'Entrada',
            self::SALIDA => 'Salida',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ENTRADA => 'blue',
            self::SALIDA => 'orange',
        };
    }
}
