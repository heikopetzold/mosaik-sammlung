<?php

namespace App\Enums;

enum PublicationType: string
{
    case Heft = 'heft';
    case Buch = 'buch';

    public function label(): string
    {
        return match ($this) {
            self::Heft => 'Heft',
            self::Buch => 'Buch',
        };
    }
}
