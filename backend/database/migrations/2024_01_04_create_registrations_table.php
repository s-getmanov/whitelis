<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->after('user_id')->constrained('teams')->nullOnDelete();
            
            $table->string('discipline')->nullable();
            $table->string('category')->nullable();
            $table->string('bib_number')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Один пользователь может быть зарегистрирован на мероприятие только один раз
            $table->unique(['user_id', 'event_id']);
            
            // Индексы для быстрого поиска
            $table->index('status');
            $table->index('bib_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};