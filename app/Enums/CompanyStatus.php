<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case ACTIVO = 'activo';
    case SUSPENDIDO = 'suspendido';
    case INACTIVO = 'inactivo';
    case PRUEBA = 'prueba';

    public function label(): string
    {
        return match($this) {
            self::ACTIVO => 'Activo',
            self::SUSPENDIDO => 'Suspendido',
            self::INACTIVO => 'Inactivo',
            self::PRUEBA => 'Periodo de Prueba',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVO => 'green',
            self::SUSPENDIDO => 'red',
            self::INACTIVO => 'gray',
            self::PRUEBA => 'blue',
        };
    }
}
