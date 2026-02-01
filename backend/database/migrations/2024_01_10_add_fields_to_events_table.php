<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Добавляем поля для регламента и деталей
            $table->text('regulations')->nullable()->after('description'); // Официальное положение
            $table->string('organizer')->nullable()->after('location'); // Организатор
            $table->string('contact_person')->nullable()->after('organizer'); // Контактное лицо
            $table->string('contact_phone')->nullable()->after('contact_person');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->decimal('registration_fee', 8, 2)->nullable()->after('max_participants'); // Взнос
            $table->timestamp('registration_deadline')->nullable()->after('registration_fee'); // Дедлайн регистрации
            $table->json('additional_info')->nullable()->after('registration_deadline'); // Дополнительная информация в JSON
            
            // Обновляем возможные статусы
            // draft, upcoming, active, completed (уже есть)
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'regulations',
                'organizer',
                'contact_person',
                'contact_phone',
                'contact_email',
                'registration_fee',
                'registration_deadline',
                'additional_info'
            ]);
        });
    }
};