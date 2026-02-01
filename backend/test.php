<?php
echo "PHP работает<br>";
echo "Версия PHP: " . phpversion() . "<br>";

// Проверка модулей
$modules = ['pdo_mysql', 'mbstring', 'openssl', 'json'];
foreach ($modules as $module) {
    echo $module . ': ' . (extension_loaded($module) ? '✓' : '✗') . '<br>';
}

// Проверка пути к vendor
$vendorPath = __DIR__ . '/vendor/autoload.php';
echo "Vendor путь: $vendorPath<br>";
echo "Vendor существует: " . (file_exists($vendorPath) ? '✓' : '✗') . '<br>';

if (file_exists($vendorPath)) {
    require $vendorPath;
    echo "Vendor загружен<br>";
}