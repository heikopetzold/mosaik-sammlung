<?php

namespace App\Enums;

enum Series: string
{
    case Abrafaxe = 'abrafaxe';
    case Digedags = 'digedags';

    public function label(): string
    {
        return match ($this) {
            self::Abrafaxe => 'Abrafaxe',
            self::Digedags => 'Digedags',
        };
    }
}
