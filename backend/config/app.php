<?php

return [
    'name' => env('APP_NAME', 'Белый Лис'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'Europe/Moscow',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
];