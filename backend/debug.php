<?php
echo "Текущая папка: " . __DIR__ . "<br>";

$paths = [
    'vendor' => __DIR__.'/../vendor/autoload.php',
    'bootstrap' => __DIR__.'/../bootstrap/app.php',
    'bootstrap2' => __DIR__.'/bootstrap/app.php',
    'app' => __DIR__.'/../app/',
    'app2' => __DIR__.'/app/',
];

foreach ($paths as $name => $path) {
    echo "$name: $path → " . (file_exists($path) ? '✅' : '❌') . "<br>";
}