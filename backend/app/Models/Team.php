<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'captain_id',
        'status'
    ];

    // Отношения
    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    // Хелперы
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }

    public function getStatusText(): string
    {
        return [
            'active' => 'Активна',
            'inactive' => 'Неактивна'
        ][$this->status] ?? $this->status;
    }
}