<?php

namespace App\Enums;

enum Category: string
{
    case Abrafaxe = 'Abrafaxe';
    case Digedags = 'Digedags';

    public function label(): string
    {
        return match ($this) {
            self::Abrafaxe => 'Abrafaxe',
            self::Digedags => 'Digedags',
        };
    }
}
