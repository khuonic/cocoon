<?php

namespace App\Models\Party;

use App\Party\Ranking;
use Carbon\CarbonImmutable;
use Database\Factories\Party\GameRunFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une partie d'un mini-jeu. Le score et la durée sont génériques pour tous les jeux.
 *
 * @property int $id
 * @property string $uuid
 * @property int $party_player_id
 * @property string $game_slug
 * @property string|null $variant
 * @property array<string, array{choice: string, correct: bool}> $answers
 * @property array<string, mixed>|null $data
 * @property int $score
 * @property int $max_score
 * @property int|null $moves
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $finished_at
 * @property int|null $duration_ms
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Player $player
 */
class GameRun extends Model
{
    /** @use HasFactory<GameRunFactory> */
    use HasFactory;

    protected $table = 'party_game_runs';

    protected $fillable = [
        'uuid',
        'party_player_id',
        'game_slug',
        'variant',
        'answers',
        'data',
        'score',
        'max_score',
        'moves',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'data' => 'array',
            'score' => 'integer',
            'max_score' => 'integer',
            'moves' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'party_player_id');
    }

    /**
     * @param  Builder<GameRun>  $query
     */
    public function scopeFinished(Builder $query): void
    {
        $query->whereNotNull('finished_at');
    }

    /**
     * Trie les parties de la meilleure à la moins bonne selon le classement du jeu.
     *
     * @param  Builder<GameRun>  $query
     */
    public function scopeRankedBy(Builder $query, Ranking $ranking): void
    {
        match ($ranking) {
            Ranking::Points => $query->orderByDesc('score')->orderBy('duration_ms'),
            Ranking::Speed => $query->orderBy('duration_ms')->orderBy('moves'),
        };

        $query->orderBy('finished_at')->orderBy('id');
    }

    /**
     * Cette partie est-elle strictement meilleure que l'autre ?
     */
    public function beats(GameRun $other, Ranking $ranking): bool
    {
        return match ($ranking) {
            Ranking::Points => $this->score > $other->score
                || ($this->score === $other->score && $this->duration_ms < $other->duration_ms),
            Ranking::Speed => $this->duration_ms < $other->duration_ms
                || ($this->duration_ms === $other->duration_ms && $this->moves < $other->moves),
        };
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }
}
