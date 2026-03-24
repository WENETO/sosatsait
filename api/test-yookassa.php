<?php
// api/test-yookassa.php - проверка ключей ЮKassa

$shopId = '1311267';
$secretKey = 'test_zJ9VBIyGHfwV6DJfNm4N4M8zfxPsIj7YjmQJiItWYkk';

// Тестовый запрос на получение информации о магазине
$ch = curl_init('https://api.yookassa.ru/v3/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP код: " . $httpCode . "\n";
echo "Ответ: " . $response . "\n";
if ($error) echo "CURL ошибка: " . $error . "\n";
?>