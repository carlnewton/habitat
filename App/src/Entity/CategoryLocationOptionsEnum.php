<?php

namespace App\Entity;

enum CategoryLocationOptionsEnum: int
{
    case DISABLED = 0;
    case REQUIRED = 1;
    case OPTIONAL = 2;

    public function label(): string
    {
        return match ($this) {
            self::DISABLED => 'Disabled',
            self::REQUIRED => 'Required',
            self::OPTIONAL => 'Optional',
        };
    }
}
