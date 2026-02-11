<?php

namespace App\Enums;

enum NoteColor: string
{
    case Default = 'default';
    case Yellow = 'yellow';
    case Green = 'green';
    case Blue = 'blue';
    case Pink = 'pink';
    case Purple = 'purple';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Par défaut',
            self::Yellow => 'Jaune',
            self::Green => 'Vert',
            self::Blue => 'Bleu',
            self::Pink => 'Rose',
            self::Purple => 'Violet',
        };
    }

    public function bgClass(): string
    {
        return match ($this) {
            self::Default => 'bg-card',
            self::Yellow => 'bg-yellow-100',
            self::Green => 'bg-green-100',
            self::Blue => 'bg-blue-100',
            self::Pink => 'bg-pink-100',
            self::Purple => 'bg-purple-100',
        };
    }
}
