<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('captain_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // 1. Добавляем team_id в users (таблица users уже создана в миграции 2024_01_02)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('status')->constrained('teams')->nullOnDelete();
        });

        // 2. УБРАТЬ: Добавление team_id в registrations - ЭТО СДЕЛАЕМ В СЛЕДУЮЩЕЙ МИГРАЦИИ
        // Schema::table('registrations', function (Blueprint $table) {
        //     $table->foreignId('team_id')->nullable()->after('user_id')->constrained('teams')->nullOnDelete();
        // });
    }

    public function down(): void
    {
        // Обратный порядок удаления
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::dropIfExists('teams');
    }
};