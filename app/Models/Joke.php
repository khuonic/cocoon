<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $content
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Joke whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Joke extends Model
{
    protected $fillable = [
        'content',
    ];
}
