<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Администратор
        User::create([
            'name' => 'Администратор',
            'email' => 'admin@beliy-lis.ru',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Участник
        User::create([
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'password' => Hash::make('password'),
            'phone' => '+7 (999) 123-45-67',
            'role' => 'participant',
            'status' => 'active'
        ]);

        // Судья
        User::create([
            'name' => 'Сергей Судья',
            'email' => 'judge@example.com',
            'password' => Hash::make('password'),
            'phone' => '+7 (999) 987-65-43',
            'role' => 'judge',
            'status' => 'active'
        ]);

        // Еще несколько участников
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => 'Участник ' . $i,
                'email' => 'participant' . $i . '@example.com',
                'password' => Hash::make('password'),
                'phone' => '+7 (999) 111-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-00',
                'role' => 'participant',
                'status' => 'active'
            ]);
        }
    }
}