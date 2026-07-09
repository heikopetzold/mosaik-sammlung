<?php

namespace App\Enums;

enum Month: int
{
    case Januar = 1;
    case Februar = 2;
    case Maerz = 3;
    case April = 4;
    case Mai = 5;
    case Juni = 6;
    case Juli = 7;
    case August = 8;
    case September = 9;
    case Oktober = 10;
    case November = 11;
    case Dezember = 12;

    public function label(): string
    {
        return match ($this) {
            self::Maerz => 'März',
            default => $this->name,
        };
    }
}
