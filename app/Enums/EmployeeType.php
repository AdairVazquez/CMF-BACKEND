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

    public function color(): string
    {
        return match($this) {
            self::BASE => 'blue',
            self::CONFIANZA => 'purple',
        };
    }
}
