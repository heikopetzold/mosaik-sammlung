<?php

namespace App\Enums;

enum Condition: string
{
    case SehrGut = 'sehr_gut';
    case Fehlerhaft = 'fehlerhaft';
    case Genaeht = 'genaeht';

    public function label(): string
    {
        return match ($this) {
            self::SehrGut => 'Sehr gut',
            self::Fehlerhaft => 'Fehlerhaft',
            self::Genaeht => 'Genäht',
        };
    }
}
