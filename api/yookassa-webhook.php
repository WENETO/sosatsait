<?php
// api/yookassa-webhook.php - обработчик уведомлений от ЮKassa

// Получаем данные от ЮKassa
$data = json_decode(file_get_contents('php://input'), true);

// Логируем для отладки
file_put_contents('yookassa_log.txt', date('Y-m-d H:i:s') . ' - ' . json_encode($data) . "\n", FILE_APPEND);

// Проверяем, что это уведомление об успешном платеже
if (isset($data['object']['status']) && $data['object']['status'] === 'succeeded') {
    $paymentId = $data['object']['id'];
    $amount = $data['object']['amount']['value'];
    $orderNumber = $data['object']['metadata']['order_number'] ?? null;
    $contractId = $data['object']['metadata']['contract_id'] ?? null;
    
    if ($orderNumber) {
        // Конфигурация Supabase
        $supabaseUrl = 'https://dtuipgbsupodwvxlwmse.supabase.co';
        $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImR0dWlwZ2JzdXBvZHd2eGx3bXNlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQzMzUxNzIsImV4cCI6MjA4OTkxMTE3Mn0.ABaA7-D4jU_64bFGQQZc3J4-0TUH5Lz8PjhqobK8ZvI';
        
        // Обновляем статус заказа в Supabase
        $ch = curl_init("$supabaseUrl/rest/v1/orders?order_number=eq.$orderNumber");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'status' => 'paid',
            'yookassa_payment_id' => $paymentId,
            'paid_at' => date('Y-m-d H:i:s')
        ]));
        $result = curl_exec($ch);
        curl_close($ch);
        
        // Обновляем статус договора
        if ($contractId) {
            $ch2 = curl_init("$supabaseUrl/rest/v1/contracts?id=eq.$contractId");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $supabaseKey,
                'Authorization: Bearer ' . $supabaseKey
            ]);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
                'status' => 'signed',
                'signed_at' => date('Y-m-d H:i:s')
            ]));
            curl_exec($ch2);
            curl_close($ch2);
        }
        
        // Создаём запись в платежах
        $ch3 = curl_init("$supabaseUrl/rest/v1/payments");
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_POST, true);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Prefer: return=representation'
        ]);
        curl_setopt($ch3, CURLOPT_POSTFIELDS, json_encode([
            'contract_id' => $contractId,
            'amount' => $amount,
            'payment_method' => 'card',
            'status' => 'paid',
            'yookassa_payment_id' => $paymentId,
            'payment_date' => date('Y-m-d')
        ]));
        curl_exec($ch3);
        curl_close($ch3);
    }
}

// Всегда возвращаем 200 OK
http_response_code(200);
echo 'OK';