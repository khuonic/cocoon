<?php

namespace App\Party;

/**
 * Façon de classer les parties d'un jeu.
 */
enum Ranking: string
{
    /** Plus de points d'abord, puis le plus rapide. */
    case Points = 'points';

    /** Le plus rapide d'abord, puis le moins de coups. */
    case Speed = 'speed';
}
