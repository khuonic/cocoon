<?php

namespace App\Enums;

enum BookmarkCategory: string
{
    case Resto = 'resto';
    case Voyage = 'voyage';
    case Shopping = 'shopping';
    case Loisirs = 'loisirs';
    case Maison = 'maison';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Resto => 'Resto',
            self::Voyage => 'Voyage',
            self::Shopping => 'Shopping',
            self::Loisirs => 'Loisirs',
            self::Maison => 'Maison',
            self::Autre => 'Autre',
        };
    }
}
