<?php
// api/create-payment.php - создание платежа в ЮKassa

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? null;
$description = $input['description'] ?? 'Оплата заказа';
$returnUrl = $input['return_url'] ?? null;
$contractId = $input['contract_id'] ?? null;

if (!$amount) {
    echo json_encode(['success' => false, 'error' => 'Сумма не указана']);
    exit;
}

$shopId = '1311267';
$secretKey = 'test_zJ9VBIyGHfwV6DJfNm4N4M8zfxPsIj7YjmQJiItWYkk';

$amountValue = number_format((float)$amount, 2, '.', '');
if ($amountValue <= 0) {
    echo json_encode(['success' => false, 'error' => 'Некорректная сумма: ' . $amountValue]);
    exit;
}

$paymentData = [
    'amount' => [
        'value' => $amountValue,
        'currency' => 'RUB'
    ],
    'capture' => true,
    'confirmation' => [
        'type' => 'redirect',
        'return_url' => $returnUrl ?: 'http://localhost/inzhener/payment-success.html'
    ],
    'description' => $description,
    'metadata' => [
        'contract_id' => $contractId
    ]
];

$auth = base64_encode($shopId . ':' . $secretKey);

$ch = curl_init('https://api.yookassa.ru/v3/payments');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Idempotence-Key: ' . uniqid(),
    'Authorization: Basic ' . $auth
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['success' => false, 'error' => 'CURL ошибка: ' . $error]);
    exit;
}

if ($httpCode !== 200 && $httpCode !== 201) {
    echo json_encode(['success' => false, 'error' => 'HTTP ошибка: ' . $httpCode, 'response' => $response]);
    exit;
}

$result = json_decode($response, true);

if (isset($result['confirmation']['confirmation_url'])) {
    echo json_encode([
        'success' => true,
        'confirmation_url' => $result['confirmation']['confirmation_url'],
        'payment_id' => $result['id']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Не удалось создать платеж', 'response' => $result]);
}
?>