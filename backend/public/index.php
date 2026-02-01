<?php

use Illuminate\Http\Request;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));

// Проверяем наличие автозагрузчика
$autoload = __DIR__.'/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Composer dependencies not installed. Run "composer install"');
}

require $autoload;

// Загружаем приложение
$app = require_once __DIR__.'/../bootstrap/app.php';

// Обрабатываем запрос
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);