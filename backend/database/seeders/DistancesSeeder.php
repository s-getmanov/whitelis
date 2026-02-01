<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Distance;
use App\Models\Event;

class DistancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::all();
        
        if ($events->isEmpty()) {
            $this->command->info('Нет мероприятий для создания дистанций. Сначала создайте мероприятия.');
            return;
        }
        
        $distancesCreated = 0;
        
        foreach ($events as $event) {
            // Создаем дистанции в зависимости от дисциплины мероприятия
            $distances = $this->getDistancesForDiscipline($event->discipline);
            
            foreach ($distances as $index => $distanceData) {
                Distance::create([
                    'event_id' => $event->id,
                    'name' => $distanceData['name'],
                    'length' => $distanceData['length'],
                    'unit' => 'km',
                    'description' => $distanceData['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
                $distancesCreated++;
            }
        }
        
        $this->command->info("Создано {$distancesCreated} дистанций для {$events->count()} мероприятий");
    }
    
    private function getDistancesForDiscipline(string $discipline): array
    {
        $distancesByDiscipline = [
            'Лыжные гонки' => [
                ['name' => '5км', 'length' => 5, 'description' => 'Короткая дистанция для начинающих'],
                ['name' => '10км', 'length' => 10, 'description' => 'Стандартная дистанция'],
                ['name' => '21км', 'length' => 21, 'description' => 'Полумарафон'],
            ],
            'Бег' => [
                ['name' => '5км', 'length' => 5, 'description' => 'Дистанция для начинающих'],
                ['name' => '10км', 'length' => 10, 'description' => 'Популярная дистанция'],
                ['name' => '21км', 'length' => 21, 'description' => 'Полумарафон'],
                ['name' => '42км', 'length' => 42, 'description' => 'Марафон для опытных бегунов'],
            ],
            'Ориентирование' => [
                ['name' => 'Короткая', 'length' => 3, 'description' => 'Техническая дистанция'],
                ['name' => 'Длинная', 'length' => 8, 'description' => 'Дистанция на выносливость'],
            ],
            'Велоспорт' => [
                ['name' => '30км', 'length' => 30, 'description' => 'Спринтерская дистанция'],
                ['name' => '60км', 'length' => 60, 'description' => 'Средняя дистанция'],
                ['name' => '100км', 'length' => 100, 'description' => 'Дистанция на выносливость'],
            ],
            'Триатлон' => [
                ['name' => 'Спринт', 'length' => 25.75, 'description' => '0.75 км плавание, 20 км вело, 5 км бег'],
                ['name' => 'Олимпийская', 'length' => 51.5, 'description' => '1.5 км плавание, 40 км вело, 10 км бег'],
                ['name' => 'Полужелезная', 'length' => 113, 'description' => '1.9 км плавание, 90 км вело, 21.1 км бег'],
            ],
        ];
        
        return $distancesByDiscipline[$discipline] ?? [
            ['name' => 'Стандартная', 'length' => 10, 'description' => 'Основная дистанция']
        ];
    }
}