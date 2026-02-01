<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        // Очищаем таблицу
        Event::truncate();
        
        // Создаем тестовые мероприятия как в EventList.vue демо-данных
        $events = [
            [
                'title' => 'Зимний марафон "Ледяная миля"',
                'description' => 'Лыжные гонки по живописной лесной трассе',
                'date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'location' => 'Лесопарк "Сосновый бор"',
                'discipline' => 'Лыжные гонки',
                'status' => 'upcoming',
                'participants_count' => 120,
                'registrations_count' => 85,
                'max_participants' => 200,
            ],
            [
                'title' => 'Спортивное ориентирование "Лисья тропа"',
                'description' => 'Для всех возрастных категорий. Индивидуальные и командные зачеты.',
                'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                'location' => 'Городской парк',
                'discipline' => 'Ориентирование',
                'status' => 'upcoming',
                'participants_count' => 80,
                'registrations_count' => 45,
                'max_participants' => 150,
            ],
            [
                'title' => 'Осенний кросс "Золотая листва"',
                'description' => 'Ежегодный кросс по пересеченной местности',
                'date' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'location' => 'Лесной массив',
                'discipline' => 'Бег по пересеченной местности',
                'status' => 'completed',
                'participants_count' => 156,
                'registrations_count' => 156,
                'max_participants' => 100,
            ],
            [
                'title' => 'Тренировка по бегу',
                'description' => 'Бесплатные занятия с профессиональным тренером',
                'date' => Carbon::now()->addDay()->format('Y-m-d'),
                'location' => 'Стадион "Юность"',
                'discipline' => 'Бег',
                'status' => 'active',
                'participants_count' => 25,
                'registrations_count' => 18,
                'max_participants' => 50,
            ],
        ];
        
        foreach ($events as $event) {
            Event::create($event);
        }
        
        $this->command->info('Создано ' . count($events) . ' тестовых мероприятий');
    }
}