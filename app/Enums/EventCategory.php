<?php

namespace App\Enums;

enum EventCategory: string
{
    case Conges = 'Conges';
    case Pro = 'Pro';
    case Loisir = 'Loisir';
    case Rdv = 'Rdv';

    public function label(): string
    {
        return match ($this) {
            self::Conges => 'Congés',
            self::Pro => 'Pro',
            self::Loisir => 'Loisirs',
            self::Rdv => 'RDV',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Conges => '#10B981',
            self::Pro => '#3B82F6',
            self::Loisir => '#8B5CF6',
            self::Rdv => '#F59E0B',
        };
    }
}
