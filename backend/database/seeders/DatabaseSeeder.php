<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            
            UsersSeeder::class,         
            TeamsSeeder::class,          
            EventsSeeder::class,         
            PublicationsSeeder::class,   
            DistancesSeeder::class,     
            CategoriesSeeder::class,     
            RegistrationsSeeder::class,  
            ProtocolSeeder::class,          
        ]);
        
        $this->command->info('Все сидеры успешно выполнены!');
    }
}