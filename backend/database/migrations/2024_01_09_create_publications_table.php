<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable(); // Краткое описание для превью
            $table->foreignId('author_id')->constrained('users')->nullOnDelete();
            $table->string('type')->default('news'); // news, announcement, article, page
            $table->string('status')->default('draft'); // draft, published, archived
            $table->boolean('is_pinned')->default(false); // Закрепленная публикация
            $table->integer('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Индексы
            $table->index(['type', 'status', 'published_at']);
            $table->index('is_pinned');
            $table->index('author_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};