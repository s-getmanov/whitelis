<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'type',
        'gender',
        'min_age',
        'max_age',
        'skill_level',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean'
    ];

    // Отношения
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'category_event')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'category', 'name');
    }

    // Хелперы
    public function getAgeRangeAttribute(): ?string
    {
        if ($this->min_age && $this->max_age) {
            return "{$this->min_age}-{$this->max_age} лет";
        } elseif ($this->min_age) {
            return "от {$this->min_age} лет";
        } elseif ($this->max_age) {
            return "до {$this->max_age} лет";
        }
        return null;
    }

    public function getDescriptionAttribute(): string
    {
        $parts = [];
        
        if ($this->gender) {
            $parts[] = $this->gender === 'male' ? 'Мужчины' : 
                      ($this->gender === 'female' ? 'Женщины' : 'Смешанная');
        }
        
        if ($ageRange = $this->age_range) {
            $parts[] = $ageRange;
        }
        
        if ($this->skill_level) {
            $levels = [
                'beginner' => 'Начинающие',
                'intermediate' => 'Любители',
                'advanced' => 'Продвинутые',
                'pro' => 'Профессионалы'
            ];
            $parts[] = $levels[$this->skill_level] ?? $this->skill_level;
        }
        
        return implode(', ', $parts) ?: $this->name;
    }
}