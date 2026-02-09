<?php

namespace App\Enums;

enum ShoppingItemCategory: string
{
    case FruitsLegumes = 'fruits_legumes';
    case Frais = 'frais';
    case Epicerie = 'epicerie';
    case Boissons = 'boissons';
    case Hygiene = 'hygiene';
    case Maison = 'maison';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::FruitsLegumes => 'Fruits & Légumes',
            self::Frais => 'Frais',
            self::Epicerie => 'Épicerie',
            self::Boissons => 'Boissons',
            self::Hygiene => 'Hygiène',
            self::Maison => 'Maison',
            self::Autre => 'Autre',
        };
    }
}
