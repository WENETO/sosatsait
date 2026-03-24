<?php
// api/test-write.php - проверка записи в папку uploads

$uploadsDir = dirname(__DIR__) . '/uploads';
$testFile = $uploadsDir . '/test.txt';

// Проверяем, существует ли папка
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
    echo "Папка uploads создана: $uploadsDir\n";
}

// Проверяем права
echo "Папка uploads существует: " . (file_exists($uploadsDir) ? 'Да' : 'Нет') . "\n";
echo "Папка доступна для записи: " . (is_writable($uploadsDir) ? 'Да' : 'Нет') . "\n";

// Пытаемся записать файл
$result = file_put_contents($testFile, 'Тестовая запись: ' . date('Y-m-d H:i:s'));

if ($result !== false) {
    echo "✅ Файл успешно создан: $testFile\n";
    echo "Содержимое файла: " . file_get_contents($testFile);
} else {
    echo "❌ Не удалось создать файл. Проверьте права на папку: $uploadsDir";
}
?>