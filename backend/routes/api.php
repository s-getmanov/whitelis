<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RegistrationController;

use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\ProtocolController;

// Тестовый маршрут
Route::get('/test', function () {
    return response()->json(['message' => 'API работает!']);
});

// Маршруты модуля мероприятий
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events', [EventController::class, 'store']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);

// Маршруты модуля управления пользователями (пока без аутентификации, для разработки)
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);


//[TODO] Маршруты модуля управления пользователями , пока заблочены, потом поставим sanctum и отладим. 
// Route::middleware(['auth:sanctum', 'admin'])->group(function () {
//     Route::get('/users', [UserController::class, 'index']);
//     Route::get('/users/{id}', [UserController::class, 'show']);
//     Route::post('/users', [UserController::class, 'store']);
//     Route::put('/users/{id}', [UserController::class, 'update']);
//     Route::delete('/users/{id}', [UserController::class, 'destroy']);
// });

// Маршруты регистраций
Route::get('/registrations', [RegistrationController::class, 'index']);
Route::get('/registrations/{id}', [RegistrationController::class, 'show']);
Route::post('/registrations', [RegistrationController::class, 'store']);
Route::put('/registrations/{id}', [RegistrationController::class, 'update']);
Route::delete('/registrations/{id}', [RegistrationController::class, 'destroy']);
Route::post('/registrations/bulk-update', [RegistrationController::class, 'bulkUpdate']);

Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{id}', [TeamController::class, 'show']);
Route::post('/teams', [TeamController::class, 'store']);
Route::put('/teams/{id}', [TeamController::class, 'update']);
Route::delete('/teams/{id}', [TeamController::class, 'destroy']);
Route::get('/teams/{id}/members', [TeamController::class, 'members']);

// Маршруты результатов
Route::get('/results', [ResultController::class, 'index']);
Route::post('/results', [ResultController::class, 'store']);
Route::post('/results/bulk-sync', [ResultController::class, 'bulkSync']);
Route::get('/results/unsynced', [ResultController::class, 'getUnsynced']);
Route::put('/results/{id}', [ResultController::class, 'update']);
Route::delete('/results/{id}', [ResultController::class, 'destroy']);
Route::post('/results/event/{eventId}/confirm-all', [ResultController::class, 'confirmAll']);

// Протоколы
Route::prefix('protocols')->group(function () {
    Route::get('/start/{eventId}', [ProtocolController::class, 'startProtocol']);
    Route::get('/final/{eventId}', [ProtocolController::class, 'finalProtocol']);
    Route::post('/{eventId}/assign-numbers', [ProtocolController::class, 'assignNumbers']);
    Route::post('/{eventId}/clear-numbers', [ProtocolController::class, 'clearNumbers']);
    Route::get('/{eventId}/export/pdf', [ProtocolController::class, 'exportPDF']);
});

// Публикации
// Попробуем сокращенный метод, не надо писать все отдельно. 
//[TODO] если норм - переделать остальные. 
Route::apiResource('publications', \App\Http\Controllers\Api\PublicationController::class);
Route::get('publications/slug/{slug}', [\App\Http\Controllers\Api\PublicationController::class, 'show']);

//[TODO] Дальше пока без контроллеров, сделать потом.
// Дистанции
// Route::apiResource('distances', \App\Http\Controllers\Api\DistanceController::class);
// Route::get('events/{event}/distances', [\App\Http\Controllers\Api\DistanceController::class, 'byEvent']);

// Категории
// Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);
// Route::get('events/{event}/categories', [\App\Http\Controllers\Api\CategoryController::class, 'byEvent']);
