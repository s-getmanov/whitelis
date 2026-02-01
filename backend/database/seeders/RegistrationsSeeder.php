<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        $registrations = [
            [
                'user_id' => 2,
                'event_id' => 1,
                'team_id' => 1, // Ссылка на команду вместо строки
                'discipline' => 'Лыжные гонки',
                'category' => 'Мужчины',
                'bib_number' => '001-001',
                'status' => 'approved',
                'notes' => 'Участник подтвердил оплату',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 3,
                'event_id' => 1,
                'team_id' => 2,
                'discipline' => 'Лыжные гонки',
                'category' => 'Женщины',
                'bib_number' => '001-002',
                'status' => 'pending',
                'notes' => 'Ожидает оплаты',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 4,
                'event_id' => 1,
                'team_id' => 3,
                'discipline' => 'Бег',
                'category' => 'Мужчины 30-40',
                'bib_number' => '001-003',
                'status' => 'approved',
                'notes' => 'Оплачено онлайн',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 5,
                'event_id' => 2,
                'team_id' => null, // Индивидуальный участник
                'discipline' => 'Лыжные гонки',
                'category' => 'Женщины 40+',
                'bib_number' => '002-001',
                'status' => 'approved',
                'notes' => 'Ветеран соревнований',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('registrations')->insert($registrations);
    }
}