<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealIdea extends Model
{
    /** @use HasFactory<\Database\Factories\MealIdeaFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'url',
        'tags',
        'created_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
