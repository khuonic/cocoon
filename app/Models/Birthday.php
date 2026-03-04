<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Birthday extends Model
{
    /** @use HasFactory<\Database\Factories\BirthdayFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'name',
        'date',
        'reminder_days_before',
        'added_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reminder_days_before' => 'integer',
        ];
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => (int) $this->date->age);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
