<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/api.php';



// 2. ВСЕ остальные запросы → отдаем SPA
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '^(?!api|assets|favicon\.ico|storage).*$');

// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\SpaController;

// Route::get('/{any}', [SpaController::class, 'index'])->where('any', '.*');

// use Illuminate\Support\Facades\Route;

// // Все маршруты ведут к Vue.js SPA
// Route::get('/{any}', function () {
//     // Простейший HTML для тестирования
//     return '
//     <!DOCTYPE html>
//     <html>
//     <head>
//         <title>Белый Лис</title>
//         <meta charset="UTF-8">
//         <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     </head>
//     <body>
//         <h1>Бэкенд работает!</h1>
//         <p>API доступно по адресу: <a href="/api/events">/api/events</a></p>
//         <p>Фронтенд будет подключен позже.</p>
//     </body>
//     </html>
//     ';
// })->where('any', '.*');