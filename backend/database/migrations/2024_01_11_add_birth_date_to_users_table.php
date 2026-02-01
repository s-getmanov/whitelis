<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Добавляем дату рождения (для расчета категорий и протоколов)
            $table->date('birth_date')->nullable()->after('phone');
            
            // Индекс для быстрого поиска по дате рождения
            $table->index('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['birth_date']);
            $table->dropColumn('birth_date');
        });
    }
};