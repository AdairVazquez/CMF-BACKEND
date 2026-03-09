<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case BAJA = 'baja';
    case SUSPENDIDO = 'suspendido';

    public function label(): string
    {
        return match($this) {
            self::ACTIVO => 'Activo',
            self::INACTIVO => 'Inactivo',
            self::BAJA => 'Baja',
            self::SUSPENDIDO => 'Suspendido',
        };
    }
}
