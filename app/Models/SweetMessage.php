<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SweetMessage extends Model
{
    /** @use HasFactory<\Database\Factories\SweetMessageFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'user_id',
        'content',
        'uuid',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
