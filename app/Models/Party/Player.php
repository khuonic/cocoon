<?php

namespace App\Models\Party;

use App\Party\Contracts\PartyGame;
use Carbon\CarbonImmutable;
use Database\Factories\Party\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invité du mini-site de la fête, identifié par un cookie contenant son uuid.
 *
 * @property int $id
 * @property string $uuid
 * @property string $pseudo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $table = 'party_players';

    protected $fillable = [
        'uuid',
        'pseudo',
    ];

    /**
     * @return HasMany<GameRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(GameRun::class, 'party_player_id');
    }

    public function hasFinished(string $gameSlug): bool
    {
        return $this->runs()->finished()->where('game_slug', $gameSlug)->exists();
    }

    /**
     * Un jeu peut exiger d'en avoir terminé un autre au moins une fois.
     */
    public function canPlay(PartyGame $game): bool
    {
        $requiredSlug = $game->requiredGameSlug();

        return $requiredSlug === null || $this->hasFinished($requiredSlug);
    }

    public static function pseudoIsTaken(string $pseudo, ?int $ignoreId = null): bool
    {
        return static::query()
            ->whereRaw('LOWER(pseudo) = ?', [mb_strtolower(trim($pseudo))])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
