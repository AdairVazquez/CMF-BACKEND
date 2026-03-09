<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case MANTENIMIENTO = 'mantenimiento';

    public function label(): string
    {
        return match($this) {
            self::ACTIVO => 'Activo',
            self::INACTIVO => 'Inactivo',
            self::MANTENIMIENTO => 'En Mantenimiento',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVO => 'green',
            self::INACTIVO => 'gray',
            self::MANTENIMIENTO => 'yellow',
        };
    }
}
