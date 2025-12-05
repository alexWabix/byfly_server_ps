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

    if (!isset($input['terminal_id'])) {
        throw new Exception('Не указан ID терминала');
    }

    $terminal_id = (int) $input['terminal_id'];
    $updates = [];
    $params = [];
    $types = '';

    // Разрешенные поля для обновления
    $allowed_fields = [
        'camera_id' => 's',
        'camera_name' => 's',
        'is_enabled' => 'i',
        'priority' => 'i',
        'max_amount' => 'i',
        'min_amount' => 'i',
        'location' => 's',
        'maintenance_mode' => 'i',
        'maintenance_reason' => 's'
    ];

    foreach ($allowed_fields as $field => $type) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
            $types .= $type;
        }
    }

    if (empty($updates)) {
        throw new Exception('Нет данных для обновления');
    }

    // Добавляем updated_at
    $updates[] = "updated_at = NOW()";

    $query = "UPDATE kaspi_terminals SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $terminal_id;
    $types .= 'i';

    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $response = [
            'type' => true,
            'msg' => 'Настройки терминала обновлены'
        ];
    } else {
        throw new Exception('Ошибка обновления терминала');
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => 'Ошибка обновления терминала: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>