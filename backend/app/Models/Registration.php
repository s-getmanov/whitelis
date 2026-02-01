<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'discipline',
        'category',
        'team_id',
        'bib_number',
        'status',
        'notes'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function result()
    {
        return $this->hasOne(Result::class);
    }

    // public function result()
    // {
    //     return $this->hasOne(Result::class);
    // }

    // Хелперы
    public function getStatusText()
    {
        return [
            'pending' => 'Ожидает подтверждения',
            'approved' => 'Подтверждена',
            'rejected' => 'Отклонена',
            'cancelled' => 'Отменена',
            'completed' => 'Участвовал'
        ][$this->status] ?? $this->status;
    }

    public function getStatusColor()
    {
        return [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            'completed' => 'info'
        ][$this->status] ?? 'secondary';
    }

    public function hasResult()
    {
        return $this->result()->exists();
    }
}