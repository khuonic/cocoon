<?php

namespace App\Enums;

enum ShoppingItemCategory: string
{
    case FruitsLegumes = 'fruits_legumes';
    case Frais = 'frais';
    case EpicerieSalee = 'epicerie_salee';
    case EpicerieSucree = 'epicerie_sucree';
    case Boissons = 'boissons';
    case Hygiene = 'hygiene';
    case Maison = 'maison';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::FruitsLegumes => 'Fruits & Légumes',
            self::Frais => 'Frais',
            self::EpicerieSalee => 'Épicerie salée',
            self::EpicerieSucree => 'Épicerie sucrée',
            self::Boissons => 'Boissons',
            self::Hygiene => 'Hygiène',
            self::Maison => 'Maison',
            self::Autre => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FruitsLegumes => '🥬',
            self::Frais => '🧀',
            self::EpicerieSalee => '🥫',
            self::EpicerieSucree => '🍪',
            self::Boissons => '🥤',
            self::Hygiene => '🧴',
            self::Maison => '🏠',
            self::Autre => '📦',
        };
    }
}
