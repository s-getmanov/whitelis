<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Разрешаем все для разработки
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
];