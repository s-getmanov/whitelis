<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Distance;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ProtocolSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создаем тестовые дистанции и категории
        $this->createDistancesAndCategories();
        
        // 2. Создаем тестовое мероприятие для протоколов
        $event = Event::create([
            'title' => 'Первый весенний пробег "Белый Лис"',
            'description' => 'Традиционный весенний забег по живописным тропам лесопарка. Дистанции для всех уровней подготовки.',
            'date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'location' => 'Лесопарк "Сосновый бор", старт у центрального входа',
            'discipline' => 'Бег',
            'status' => 'upcoming',
            'max_participants' => 200,
            'participants_count' => 0,
            'registrations_count' => 0,
            'regulations' => 'Официальное положение о соревнованиях...',
            'organizer' => 'Спортивный клуб "Белый Лис"',
            'contact_person' => 'Иванов Иван Иванович',
            'contact_phone' => '+7 (999) 123-45-67',
            'contact_email' => 'organizer@beliy-lis.ru',
            'registration_fee' => 500.00,
            'registration_deadline' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
        ]);
        
        echo "Создано мероприятие: {$event->title}\n";
        
        // 3. Создаем тестовых пользователей с датами рождения
        $users = $this->createTestUsers();
        
        // 4. Создаем регистрации на мероприятие
        $registrations = $this->createRegistrations($event, $users);
        
        // 5. Назначаем стартовые номера (случайно)
        $this->assignBibNumbers($registrations);
        
        // 6. Создаем результаты для части участников (имитация завершенного мероприятия)
        $this->createResults($registrations);
        
        // 7. Обновляем счетчики мероприятия
        // $event->update([
        //     'participants_count' => $registrations->where('status', 'approved')->count(),
        //     'registrations_count' => $registrations->count()
        // ]);
        
        echo "\n✅ Сидинг протоколов завершен!\n";
        echo "Мероприятие ID: {$event->id}\n";
        echo "Участников: {$event->participants_count}\n";
        echo "Регистраций: {$event->registrations_count}\n";
        echo "Результатов: " . Result::count() . "\n";
    }
    
    private function createDistancesAndCategories(): void
    {
        // Создаем дистанции если их нет
        if (Distance::count() === 0) {
            $distances = [
                ['name' => '5км', 'length' => 5, 'unit' => 'km', 'description' => 'Короткая дистанция для новичков'],
                ['name' => '10км', 'length' => 10, 'unit' => 'km', 'description' => 'Стандартная дистанция'],
                ['name' => '21км', 'length' => 21.1, 'unit' => 'km', 'description' => 'Полумарафон'],
            ];
            
            foreach ($distances as $distance) {
                Distance::create($distance);
            }
            echo "Созданы дистанции\n";
        }
        
        // Создаем категории если их нет
        if (Category::count() === 0) {
            $categories = [
                ['name' => 'Мужчины 18-25', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 18, 'max_age' => 25],
                ['name' => 'Мужчины 26-40', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 26, 'max_age' => 40],
                ['name' => 'Мужчины 40+', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 40, 'max_age' => null],
                ['name' => 'Женщины 18-25', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 18, 'max_age' => 25],
                ['name' => 'Женщины 26-40', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 26, 'max_age' => 40],
                ['name' => 'Женщины 40+', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 40, 'max_age' => null],
                ['name' => 'Юниоры', 'type' => 'age_gender', 'gender' => 'mixed', 'min_age' => 14, 'max_age' => 17],
                ['name' => 'Общий зачет', 'type' => 'general', 'gender' => 'mixed'],
            ];
            
            foreach ($categories as $category) {
                Category::create($category);
            }
            echo "Созданы категории\n";
        }
    }
    
    private function createTestUsers(): array
    {
        $users = [];
        
        // Создаем 20 тестовых пользователей с разными датами рождения
        $firstNames = ['Александр', 'Дмитрий', 'Михаил', 'Андрей', 'Сергей', 'Алексей', 'Иван', 'Евгений', 'Владимир', 'Николай'];
        $lastNames = ['Иванов', 'Петров', 'Сидоров', 'Смирнов', 'Кузнецов', 'Попов', 'Васильев', 'Соколов', 'Михайлов', 'Новиков'];
        $femaleFirstNames = ['Анна', 'Елена', 'Ольга', 'Мария', 'Наталья', 'Ирина', 'Светлана', 'Татьяна', 'Екатерина', 'Юлия'];
        
        // Мужчины
        for ($i = 0; $i < 12; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $birthYear = rand(1970, 2005);
            $birthMonth = rand(1, 12);
            $birthDay = rand(1, 28);
            
            $users[] = User::create([
                'name' => "{$firstName} {$lastName}",
                'email' => strtolower("user{$i}@test.com"),
                'password' => Hash::make('password123'),
                'phone' => '+7 (999) ' . rand(100, 999) . '-' . rand(10, 99) . '-' . rand(10, 99),
                'birth_date' => Carbon::create($birthYear, $birthMonth, $birthDay)->format('Y-m-d'),
                'role' => 'participant',
                'status' => 'active'
            ]);
        }
        
        // Женщины
        for ($i = 12; $i < 20; $i++) {
            $firstName = $femaleFirstNames[array_rand($femaleFirstNames)];
            $lastName = $lastNames[array_rand($lastNames)] . 'а';
            $birthYear = rand(1975, 2005);
            $birthMonth = rand(1, 12);
            $birthDay = rand(1, 28);
            
            $users[] = User::create([
                'name' => "{$firstName} {$lastName}",
                'email' => strtolower("user{$i}@test.com"),
                'password' => Hash::make('password123'),
                'phone' => '+7 (999) ' . rand(100, 999) . '-' . rand(10, 99) . '-' . rand(10, 99),
                'birth_date' => Carbon::create($birthYear, $birthMonth, $birthDay)->format('Y-m-d'),
                'role' => 'participant',
                'status' => 'active'
            ]);
        }
        
        echo "Создано 20 тестовых пользователей с датами рождения\n";
        return $users;
    }
    
    private function createRegistrations(Event $event, array $users): array
    {
        $registrations = [];
        $distances = ['5км', '10км', '21км'];
        $categories = ['Мужчины 18-25', 'Мужчины 26-40', 'Мужчины 40+', 'Женщины 18-25', 'Женщины 26-40', 'Женщины 40+', 'Общий зачет'];
        
        foreach ($users as $index => $user) {
            // Выбираем случайную дистанцию и категорию
            $distance = $distances[array_rand($distances)];
            $category = $categories[array_rand($categories)];
            
            // Определяем статус регистрации (80% approved, 20% pending)
            $status = rand(1, 100) <= 80 ? 'approved' : 'pending';
            
            $registrations[] = Registration::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'discipline' => $distance,
                'category' => $category,
                'status' => $status,
                'notes' => $index % 3 === 0 ? 'Оплата подтверждена' : null
            ]);
        }
        
        echo "Создано " . count($registrations) . " регистраций\n";
        return $registrations;
    }
    
    private function assignBibNumbers(array $registrations): void
    {
        $bibNumber = 1;
        
        foreach ($registrations as $registration) {
            // Назначаем номер только подтвержденным заявкам
            if ($registration->status === 'approved') {
                $registration->update(['bib_number' => $bibNumber]);
                $bibNumber++;
            }
        }
        
        echo "Назначены стартовые номера (с 1 по " . ($bibNumber - 1) . ")\n";
    }
    
    private function createResults(array $registrations): void
    {
        $resultsCreated = 0;
        
        foreach ($registrations as $registration) {
            // Создаем результаты только для approved заявок с номером (70% вероятность)
            if ($registration->status === 'approved' && $registration->bib_number && rand(1, 100) <= 70) {
                
                // Генерируем случайное время в зависимости от дистанции
                $distance = $registration->discipline;
                $baseTime = match($distance) {
                    '5км' => rand(1200, 1800), // 20-30 минут в секундах
                    '10км' => rand(2400, 3600), // 40-60 минут
                    '21км' => rand(4500, 7200), // 75-120 минут
                    default => rand(1800, 2700)
                };
                
                // Добавляем случайное отклонение
                $resultTime = $baseTime + rand(-60, 60);
                
                // Конвертируем секунды в формат MM:SS.ss
                $minutes = floor($resultTime / 60);
                $seconds = $resultTime % 60;
                $formattedTime = sprintf('%02d:%05.2f', $minutes, $seconds);
                
                Result::create([
                    'registration_id' => $registration->id,
                    'result_time' => $formattedTime,
                    'finish_time' => Carbon::now()->subMinutes(rand(10, 120)),
                    'status' => 'confirmed',
                    'synced' => true
                ]);
                
                $resultsCreated++;
            }
        }
        
        echo "Создано {$resultsCreated} результатов\n";
    }
}