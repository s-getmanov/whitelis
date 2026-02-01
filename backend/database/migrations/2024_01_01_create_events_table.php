<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date'); // Изменено с dateTime на date
            $table->string('location');
            $table->string('discipline');
            $table->string('status')->default('draft');
            $table->integer('max_participants')->nullable();
            $table->integer('participants_count')->default(0);
            $table->integer('registrations_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};