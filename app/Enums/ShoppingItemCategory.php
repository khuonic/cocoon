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
}
