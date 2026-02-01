<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name'); // Например: "5км", "10км", "Полумарафон"
            $table->float('length')->nullable(); // Длина в км
            $table->string('unit')->default('km'); // км, м, мили
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0); // Порядок отображения
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Индексы
            $table->index(['event_id', 'sort_order']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distances');
    }
};