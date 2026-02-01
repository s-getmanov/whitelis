<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'team_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    

    // Отношения
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'registrations')
            ->withPivot(['discipline', 'category', 'team', 'status'])
            ->withTimestamps();
    }

    // Хелперы
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isParticipant()
    {
        return $this->role === 'participant';
    }

    
    public function getStatusText()
    {
        return [
            'active' => 'Активен',
            'blocked' => 'Заблокирован',
            'pending' => 'Ожидает подтверждения'
        ][$this->status] ?? $this->status;
    }

   


    //Добавить касты
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'team_id' => 'integer'
    ];

    // Добавить отношения
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function managedTeam()
    {
        return $this->hasOne(Team::class, 'captain_id');
    }

    // Добавить хелперы
    public function isTeamManager()
    {
        return $this->role === 'team_manager';
    }

    public function isTeamCaptain()
    {
        return $this->managedTeam()->exists();
    }

    public function isJudge()
{
    return $this->role === 'judge';
}

    // Обновить getRoleText()
    public function getRoleText()
    {
        return [
            'admin' => 'Администратор',
            'participant' => 'Участник',
            'team_manager' => 'Представитель команды',
            'judge' => 'Судья',
            'volunteer' => 'Волонтер'
        ][$this->role] ?? $this->role;
    }

    





}