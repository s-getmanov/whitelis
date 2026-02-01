<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Event;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем общие категории
        $categories = [
            // Возрастные категории для мужчин
            ['name' => 'Мужчины 18-25 лет', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 18, 'max_age' => 25, 'sort_order' => 1],
            ['name' => 'Мужчины 26-35 лет', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 26, 'max_age' => 35, 'sort_order' => 2],
            ['name' => 'Мужчины 36-45 лет', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 36, 'max_age' => 45, 'sort_order' => 3],
            ['name' => 'Мужчины 46+ лет', 'type' => 'age_gender', 'gender' => 'male', 'min_age' => 46, 'max_age' => null, 'sort_order' => 4],
            
            // Возрастные категории для женщин
            ['name' => 'Женщины 18-25 лет', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 18, 'max_age' => 25, 'sort_order' => 5],
            ['name' => 'Женщины 26-35 лет', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 26, 'max_age' => 35, 'sort_order' => 6],
            ['name' => 'Женщины 36-45 лет', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 36, 'max_age' => 45, 'sort_order' => 7],
            ['name' => 'Женщины 46+ лет', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 46, 'max_age' => null, 'sort_order' => 8],
            
            // Детские и юношеские категории
            ['name' => 'Юниоры (14-17 лет)', 'type' => 'age_gender', 'gender' => 'mixed', 'min_age' => 14, 'max_age' => 17, 'sort_order' => 9],
            ['name' => 'Юниорки (14-17 лет)', 'type' => 'age_gender', 'gender' => 'female', 'min_age' => 14, 'max_age' => 17, 'sort_order' => 10],
            
            // Категории по уровню подготовки
            ['name' => 'Начинающие', 'type' => 'skill_level', 'skill_level' => 'beginner', 'sort_order' => 11],
            ['name' => 'Любители', 'type' => 'skill_level', 'skill_level' => 'intermediate', 'sort_order' => 12],
            ['name' => 'Продвинутые', 'type' => 'skill_level', 'skill_level' => 'advanced', 'sort_order' => 13],
            ['name' => 'Профессионалы', 'type' => 'skill_level', 'skill_level' => 'pro', 'sort_order' => 14],
            
            // Общие категории
            ['name' => 'Общий зачет', 'type' => 'general', 'sort_order' => 15],
            ['name' => 'Ветераны (50+ лет)', 'type' => 'age_gender', 'min_age' => 50, 'max_age' => null, 'sort_order' => 16],
        ];
        
        foreach ($categories as $categoryData) {
            Category::create(array_merge($categoryData, ['is_active' => true]));
        }
        
        $categoriesCount = count($categories);
        $this->command->info("Создано {$categoriesCount} категорий");
        
        // Привязываем категории к мероприятиям (если они есть)
        $events = Event::all();
        
        if ($events->isEmpty()) {
            $this->command->info('Нет мероприятий для привязки категорий');
            return;
        }
        
        $allCategories = Category::all();
        $attachmentsCount = 0;
        
        foreach ($events as $event) {
            // Выбираем случайные категории для каждого мероприятия (от 4 до 8)
            $selectedCategories = $allCategories->random(rand(4, 8));
            
            foreach ($selectedCategories as $index => $category) {
                $event->categories()->attach($category->id, ['sort_order' => $index + 1]);
                $attachmentsCount++;
            }
        }
        
        $this->command->info("Привязано {$attachmentsCount} категорий к {$events->count()} мероприятиям");
    }
}