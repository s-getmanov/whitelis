<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distance extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'length',
        'unit',
        'description',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'length' => 'float',
        'sort_order' => 'integer',
        'is_active' => 'boolean'
    ];

    // Отношения
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'discipline', 'name');
    }

    // Хелперы
    public function getFullNameAttribute(): string
    {
        if ($this->length) {
            return "{$this->name} ({$this->length} {$this->unit})";
        }
        return $this->name;
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->is_active && $this->event->status !== 'completed';
    }
}