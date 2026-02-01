<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        $teams = [
            [
                'name' => 'ЦСКА',
                'slug' => 'cska',
                'description' => 'Центральный спортивный клуб армии',
                'captain_id' => 2,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Динамо',
                'slug' => 'dynamo',
                'description' => 'Спортивное общество Динамо',
                'captain_id' => 3,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Спартак',
                'slug' => 'spartak',
                'description' => 'Народная команда',
                'captain_id' => 4,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('teams')->insert($teams);
    }
}