<?php

namespace App\Enums;

enum EmployeeType: string
{
    case BASE = 'base';
    case CONFIANZA = 'confianza';

    public function label(): string
    {
        return match($this) {
            self::BASE => 'Base',
            self::CONFIANZA => 'Confianza',
        };
    }
}
