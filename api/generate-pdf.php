<?php
// api/generate-pdf.php - генерация PDF договора

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$contractId = $_GET['contract_id'] ?? null;

if (!$contractId) {
    echo json_encode(['success' => false, 'error' => 'ID договора не указан']);
    exit;
}

$supabaseUrl = 'https://dtuipgbsupodwvxlwmse.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImR0dWlwZ2JzdXBvZHd2eGx3bXNlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQzMzUxNzIsImV4cCI6MjA4OTkxMTE3Mn0.ABaA7-D4jU_64bFGQQZc3J4-0TUH5Lz8PjhqobK8ZvI';

// Получаем договор
$ch = curl_init("$supabaseUrl/rest/v1/contracts?id=eq.$contractId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);
$response = curl_exec($ch);
curl_close($ch);

$contracts = json_decode($response, true);
$contract = $contracts[0] ?? null;

if (!$contract) {
    echo json_encode(['success' => false, 'error' => 'Договор не найден']);
    exit;
}

// Получаем профиль
$ch = curl_init("$supabaseUrl/rest/v1/profiles?id=eq." . $contract['user_id']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);
$response = curl_exec($ch);
curl_close($ch);

$profiles = json_decode($response, true);
$profile = $profiles[0] ?? [];

// Получаем email
$ch = curl_init("$supabaseUrl/rest/v1/auth/users?id=eq." . $contract['user_id']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);
$response = curl_exec($ch);
curl_close($ch);

$users = json_decode($response, true);
$user = $users[0] ?? [];
$profile['email'] = $user['email'] ?? 'Не указан';

$uploadsDir = dirname(__DIR__) . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$safeNumber = preg_replace('/[^0-9]/', '', $contract['contract_number']);
$timestamp = date('Ymd_His');
$filename = 'contract_' . $safeNumber . '_' . $timestamp . '.html';
$filepath = $uploadsDir . '/' . $filename;
$pdfUrl = '/inzhener/uploads/' . $filename;

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Договор ' . htmlspecialchars($contract['contract_number']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        h1 { color: #00c2ff; text-align: center; }
        .contract-number { text-align: right; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #ccc; margin-bottom: 10px; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
        .signatures { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature { width: 200px; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Инженерные системы Томск</h1>
    <p style="text-align: center;">Договор оказания услуг по монтажу инженерных систем</p>
    
    <div class="contract-number">
        <strong>Договор № ' . htmlspecialchars($contract['contract_number']) . '</strong><br>
        от ' . date('d.m.Y', strtotime($contract['contract_date'])) . '
    </div>
    
    <div class="section">
        <div class="section-title">1. СТОРОНЫ ДОГОВОРА</div>
        <p><strong>Исполнитель:</strong> Индивидуальный предприниматель [Ваши данные]<br>
        <strong>Заказчик:</strong> ' . htmlspecialchars($profile['full_name'] ?? 'Не указано') . '<br>
        <strong>Телефон:</strong> ' . htmlspecialchars($profile['phone'] ?? 'Не указан') . '<br>
        <strong>Email:</strong> ' . htmlspecialchars($profile['email'] ?? 'Не указан') . '</p>
    </div>
    
    <div class="section">
        <div class="section-title">2. ПРЕДМЕТ ДОГОВОРА</div>
        <p>Исполнитель обязуется выполнить работы по монтажу инженерных систем, а Заказчик обязуется принять и оплатить выполненные работы.</p>
        <p><strong>Объект:</strong> ' . htmlspecialchars($contract['object_address'] ?? 'Не указан') . '<br>
        <strong>Состав работ:</strong> ' . htmlspecialchars($contract['services'] ?? 'Монтаж инженерных систем') . '</p>
    </div>
    
    <div class="section">
        <div class="section-title">3. СТОИМОСТЬ РАБОТ</div>
        <p>Стоимость работ составляет <strong>' . number_format($contract['total_amount'], 0, '.', ' ') . ' рублей</strong>.</p>
        <p>Оплата произведена полностью.</p>
    </div>
    
    <div class="total">
        ИТОГО К ОПЛАТЕ: ' . number_format($contract['total_amount'], 0, '.', ' ') . ' рублей
    </div>
    
    <div class="signatures">
        <div class="signature">
            <div class="signature-line">Исполнитель</div>
            <div>___________________</div>
        </div>
        <div class="signature">
            <div class="signature-line">Заказчик</div>
            <div>___________________</div>
            <div>' . htmlspecialchars($profile['full_name'] ?? '___________') . '</div>
        </div>
    </div>
    
    <div class="footer">
        <p>Настоящий договор считается заключенным с момента его оплаты.</p>
        <p>© 2025 Инженерные системы Томск</p>
    </div>
</body>
</html>';

file_put_contents($filepath, $html);

$ch = curl_init("$supabaseUrl/rest/v1/contracts?id=eq.$contractId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pdf_url' => $pdfUrl]));
curl_exec($ch);
curl_close($ch);

echo json_encode(['success' => true, 'pdf_url' => $pdfUrl]);
?>