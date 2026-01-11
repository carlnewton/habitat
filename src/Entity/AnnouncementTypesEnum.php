<?php

namespace App\Entity;

enum AnnouncementTypesEnum: int
{
    case PRIMARY = 0;
    case SECONDARY = 1;
    case WARNING = 2;
    case INFO = 3;
    case LIGHT = 4;

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY => 'Primary',
            self::SECONDARY => 'Secondary',
            self::WARNING => 'Warning',
            self::INFO => 'Info',
            self::LIGHT => 'Light',
        };
    }

    public function machineName(): string
    {
        return match ($this) {
            self::PRIMARY => 'primary',
            self::SECONDARY => 'secondary',
            self::WARNING => 'warning',
            self::INFO => 'info',
            self::LIGHT => 'light',
        };
    }
}
