<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'regulations', // Добавлено
        'date',
        'location',
        'organizer', // Добавлено
        'contact_person', // Добавлено
        'contact_phone', // Добавлено
        'contact_email', // Добавлено
        'discipline',
        'status',
        'max_participants',
        'registration_fee', // Добавлено
        'registration_deadline', // Добавлено
        'additional_info', // Добавлено
        'participants_count',
        'registrations_count'
    ];

    protected $casts = [
        'date' => 'date',
        'registration_deadline' => 'datetime',
        'max_participants' => 'integer',
        'participants_count' => 'integer',
        'registrations_count' => 'integer',
        'registration_fee' => 'decimal:2',
        'additional_info' => 'array' // Добавлено
    ];
    
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'registrations')
            ->wherePivot('status', 'approved')
            ->withPivot(['discipline', 'category', 'team'])
            ->withTimestamps();
    }

    public function pendingRegistrations()
    {
        return $this->hasMany(Registration::class)->where('status', 'pending');
    }

    public function getRegistrationsCountAttribute()
    {
        return $this->registrations()->count();
    }

    public function getParticipantsCountAttribute()
    {
        return $this->participants()->count();
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_event')
            ->withPivot('sort_order')
            ->orderBy('sort_order');
    }

    
    public function distances()
    {
        return $this->hasMany(Distance::class)->orderBy('sort_order');
    }

    

}

