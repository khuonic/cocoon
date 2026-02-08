<?php

namespace App\Models;

use App\Enums\MealType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlan extends Model
{
    /** @use HasFactory<\Database\Factories\MealPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'meal_type',
        'description',
        'cooked_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal_type' => MealType::class,
        ];
    }

    public function cook(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cooked_by');
    }
}
