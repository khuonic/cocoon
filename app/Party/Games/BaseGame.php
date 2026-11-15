<?php

namespace App\Party\Games;

use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;

/**
 * Comportement par défaut des jeux : sans variante, sans props de page supplémentaires.
 */
abstract class BaseGame implements PartyGame
{
    public function variantAxes(): array
    {
        return [];
    }

    public function defaultVariant(): ?string
    {
        $axes = $this->variantAxes();

        if ($axes === []) {
            return null;
        }

        return collect($axes)
            ->map(fn (array $axis) => (string) array_key_first($axis['options']))
            ->implode('-');
    }

    public function isValidVariant(?string $variant): bool
    {
        $axes = $this->variantAxes();

        if ($axes === []) {
            return $variant === null;
        }

        if ($variant === null) {
            return false;
        }

        $values = explode('-', $variant);

        if (count($values) !== count($axes)) {
            return false;
        }

        foreach ($axes as $index => $axis) {
            if (! array_key_exists($values[$index], $axis['options'])) {
                return false;
            }
        }

        return true;
    }

    public function variantLabel(?string $variant): ?string
    {
        if (! $this->isValidVariant($variant) || $variant === null) {
            return null;
        }

        $values = explode('-', $variant);

        return collect($this->variantAxes())
            ->map(fn (array $axis, int $index) => $axis['options'][$values[$index]])
            ->implode(' · ');
    }

    public function pageProps(Player $player): array
    {
        return [];
    }
}
