<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publication extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'author_id',
        'type',
        'status',
        'is_pinned',
        'views_count',
        'published_at'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'views_count' => 'integer',
        'published_at' => 'datetime'
    ];

    // Отношения
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Scope'ы
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeNews($query)
    {
        return $query->where('type', 'news');
    }

    public function scopeAnnouncements($query)
    {
        return $query->where('type', 'announcement');
    }

    // Хелперы
    public function getStatusTextAttribute(): string
    {
        return [
            'draft' => 'Черновик',
            'published' => 'Опубликовано',
            'archived' => 'В архиве'
        ][$this->status] ?? $this->status;
    }

    public function getTypeTextAttribute(): string
    {
        return [
            'news' => 'Новость',
            'announcement' => 'Анонс',
            'article' => 'Статья',
            'page' => 'Страница'
        ][$this->type] ?? $this->type;
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' 
            && $this->published_at 
            && $this->published_at <= now();
    }
}