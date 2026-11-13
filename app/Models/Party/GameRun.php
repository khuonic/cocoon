<?php

namespace App\Models\Party;

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
 * @property array<string, array{choice: string, correct: bool}> $answers
 * @property int $score
 * @property int $max_score
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
        'answers',
        'score',
        'max_score',
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
            'score' => 'integer',
            'max_score' => 'integer',
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

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }
}
