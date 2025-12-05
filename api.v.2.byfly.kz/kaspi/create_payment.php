<?php
include('../config.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Валидация обязательных полей
    $required_fields = ['payment_type', 'payer_phone', 'payer_name', 'amount_total'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Поле {$field} обязательно для заполнения");
        }
    }

    // Валидация суммы
    $amount = (int) $input['amount_total'];
    if ($amount < 100 || $amount > 1000000) {
        throw new Exception("Сумма должна быть от 100 до 1,000,000 тенге");
    }

    // Валидация телефона
    $phone = preg_replace('/[^0-9]/', '', $input['payer_phone']);
    if (strlen($phone) !== 11 || !preg_match('/^7\d{10}$/', $phone)) {
        throw new Exception("Некорректный формат телефона");
    }

    // Подготовка данных
    $payment_data = [
        'payment_type' => $input['payment_type'],
        'payment_comment' => $input['payment_comment'] ?? '',
        'initiator_user_id' => $input['initiator_user_id'] ?? 0,
        'payer_phone' => $phone,
        'payer_name' => trim($input['payer_name']),
        'amount_total' => $amount,
        'amount_received' => 0,
        'status' => 'queue',
        'payment_method' => $input['payment_method'] ?? 'kaspi_gold',
        'priority' => $input['priority'] ?? 100,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+3 minutes')),
        'related_order_id' => $input['related_order_id'] ?? null,
        'installment_term' => $input['installment_term'] ?? null,
        'max_retries' => 3,
        'created_by_user_id' => $input['created_by_user_id'] ?? null
    ];

    // Дополнительные данные
    if (isset($input['additional_data'])) {
        $payment_data['additional_data'] = json_encode($input['additional_data'], JSON_UNESCAPED_UNICODE);
    }

    if (isset($input['installment_info'])) {
        $payment_data['installment_info'] = json_encode($input['installment_info'], JSON_UNESCAPED_UNICODE);
    }

    // Вставка в базу данных
    $fields = array_keys($payment_data);
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    $values = array_values($payment_data);

    $query = "INSERT INTO kaspi_payment_queue (" . implode(',', $fields) . ") VALUES ($placeholders)";
    $stmt = $db->prepare($query);

    // Определяем типы параметров
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        $payment_id = $db->insert_id;

        $response = [
            'type' => true,
            'msg' => 'Платеж добавлен в очередь',
            'data' => [
                'payment_id' => $payment_id,
                'status' => 'queue',
                'estimated_processing_time' => '1-3 минуты'
            ]
        ];
    } else {
        throw new Exception('Ошибка добавления платежа в очередь');
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => 'Ошибка создания платежа: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>