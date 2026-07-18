<?php

namespace App\Entity;

enum CategoryLocationOptionsEnum: int
{
    case DISABLED = 0;
    case REQUIRED = 1;
    case OPTIONAL = 2;

    public function machineName(): string
    {
        return match ($this) {
            self::DISABLED => 'disabled',
            self::REQUIRED => 'required',
            self::OPTIONAL => 'optional',
        };
    }
}
