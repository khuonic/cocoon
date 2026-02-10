<?php

namespace App\Enums;

enum MealTag: string
{
    case Rapide = 'rapide';
    case Vege = 'vege';
    case Comfort = 'comfort';
    case Leger = 'leger';
    case Gourmand = 'gourmand';

    public function label(): string
    {
        return match ($this) {
            self::Rapide => 'Rapide',
            self::Vege => 'Végé',
            self::Comfort => 'Comfort',
            self::Leger => 'Léger',
            self::Gourmand => 'Gourmand',
        };
    }
}
