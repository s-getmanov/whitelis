<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = [
        'registration_id',
        'finish_time',
        'result_time',
        'judge_id',
        'status',
        'notes',
        'synced'
    ];

    protected $casts = [
        'finish_time' => 'datetime',
        'synced' => 'boolean'
    ];

    // Отношения
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function event()
    {
        return $this->hasOneThrough(
            Event::class,
            Registration::class,
            'id', // Foreign key on registrations table
            'id', // Foreign key on events table
            'registration_id', // Local key on results table
            'event_id' // Local key on registrations table
        );
    }

    // Хелперы
    public function getStatusText(): string
    {
        return [
            'pending' => 'Ожидает подтверждения',
            'confirmed' => 'Подтвержден',
            'disqualified' => 'Дисквалифицирован'
        ][$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return [
            'pending' => 'warning',
            'confirmed' => 'success',
            'disqualified' => 'danger'
        ][$this->status] ?? 'secondary';
    }

    // Преобразование result_time в секунды
    public function getTimeInSeconds(): float
    {
        if (empty($this->result_time)) {
            return 0;
        }

        $parts = explode(':', $this->result_time);
        if (count($parts) === 2) {
            // Формат MM:SS.ss
            $minutes = (int)$parts[0];
            $seconds = (float)$parts[1];
            return ($minutes * 60) + $seconds;
        } else {
            // Просто секунды
            return (float)$this->result_time;
        }
    }

    // Форматирование времени для отображения
    public function getFormattedTime(): string
    {
        if (empty($this->result_time)) {
            return '–';
        }

        // Если время в формате MM:SS.ss, возвращаем как есть
        if (strpos($this->result_time, ':') !== false) {
            return $this->result_time;
        }

        // Если время в секундах, форматируем
        $seconds = (float)$this->result_time;
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%02d:%05.2f', $minutes, $remainingSeconds);
    }
}