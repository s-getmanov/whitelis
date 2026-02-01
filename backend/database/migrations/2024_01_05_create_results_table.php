<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->dateTime('finish_time'); // Абсолютное время финиша
            $table->string('result_time', 20); // Формат: "45:23.15" (минуты:секунды.сотые)
            $table->foreignId('judge_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, confirmed, disqualified
            $table->text('notes')->nullable();
            $table->boolean('synced')->default(true); // Для офлайн-режима
            $table->timestamps();
            
            // Один результат на одну регистрацию
            $table->unique('registration_id');
            
            // Индексы для быстрого поиска
            $table->index('finish_time');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};