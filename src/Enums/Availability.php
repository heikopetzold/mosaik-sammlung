<?php

namespace App\Enums;

enum Availability: string
{
    case Vorhanden = 'vorhanden';
    case Fehlt = 'fehlt';

    public function label(): string
    {
        return match ($this) {
            self::Vorhanden => 'Vorhanden',
            self::Fehlt => 'Fehlt',
        };
    }
}
