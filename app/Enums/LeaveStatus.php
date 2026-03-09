<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case PENDIENTE = 'pendiente';
    case APROBADO_JEFE = 'aprobado_jefe';
    case APROBADO_RH = 'aprobado_rh';
    case RECHAZADO = 'rechazado';

    public function label(): string
    {
        return match($this) {
            self::PENDIENTE => 'Pendiente',
            self::APROBADO_JEFE => 'Aprobado por Jefe',
            self::APROBADO_RH => 'Aprobado por RH',
            self::RECHAZADO => 'Rechazado',
        };
    }
}
