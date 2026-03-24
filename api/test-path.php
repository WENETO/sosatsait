<?php
// api/test-path.php - проверка путей

echo "Текущий файл: " . __FILE__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
echo "Папка uploads: " . dirname(__DIR__) . '/uploads' . "\n";

$uploadsDir = dirname(__DIR__) . '/uploads';
echo "Папка uploads существует: " . (file_exists($uploadsDir) ? 'Да' : 'Нет') . "\n";
echo "Абсолютный путь: " . realpath($uploadsDir) . "\n";
?>