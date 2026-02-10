<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'paid_by');
    }

    public function assignedTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'assigned_to');
    }

    public function createdTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'created_by');
    }

    public function mealIdeas(): HasMany
    {
        return $this->hasMany(MealIdea::class, 'created_by');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'created_by');
    }

    public function shoppingItems(): HasMany
    {
        return $this->hasMany(ShoppingItem::class, 'added_by');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'added_by');
    }
}
