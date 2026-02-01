<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Например: "Мужчины 18-25", "Женщины 40+"
            $table->string('type')->default('age_gender'); // age_gender, skill_level, weight_class
            $table->string('gender')->nullable(); // male, female, mixed
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->string('skill_level')->nullable(); // beginner, intermediate, advanced, pro
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Индексы
            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};