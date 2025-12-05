<?php
include('../config.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function checkTerminalConnection($ip, $port, $timeout = 5)
{
    $url = "https://{$ip}:{$port}/v2/deviceinfo";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $start_time = microtime(true);
    $response = file_get_contents($url, false, $context);
    $response_time = (microtime(true) - $start_time) * 1000;

    if ($response === false) {
        return [
            'online' => false,
            'response_time' => null,
            'error' => 'Не удалось подключиться',
            'device_info' => null
        ];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['statusCode'])) {
        return [
            'online' => false,
            'response_time' => $response_time,
            'error' => 'Некорректный ответ',
            'device_info' => null
        ];
    }

    if ($data['statusCode'] !== 0) {
        return [
            'online' => false,
            'response_time' => $response_time,
            'error' => $data['errorText'] ?? 'Ошибка терминала',
            'device_info' => null
        ];
    }

    return [
        'online' => true,
        'response_time' => $response_time,
        'error' => null,
        'device_info' => $data['data'] ?? null
    ];
}

try {
    // Получаем все терминалы
    $query = "SELECT * FROM kaspi_terminals ORDER BY id ASC";
    $result = $db->query($query);

    $terminals_status = [];
    $total_checked = 0;
    $online_count = 0;

    while ($terminal = $result->fetch_assoc()) {
        $total_checked++;

        // Проверяем подключение
        $check_result = checkTerminalConnection($terminal['ip_address'], $terminal['port']);

        $new_status = $check_result['online'] ? 'free' : 'offline';
        $error_message = $check_result['error'];

        // Если терминал был занят и сейчас недоступен, оставляем статус busy
        if ($terminal['status'] === 'busy' && !$check_result['online']) {
            $new_status = 'error';
        }

        // Обновляем информацию о терминале в базе
        $update_fields = [
            'last_check_date = NOW()'
        ];
        $params = [];
        $types = '';

        // Обновляем статус только если он изменился
        if ($terminal['status'] !== $new_status) {
            $update_fields[] = 'status = ?';
            $params[] = $new_status;
            $types .= 's';
        }

        // Обновляем время отклика
        if ($check_result['response_time'] !== null) {
            $update_fields[] = 'response_time_avg = CASE 
                                WHEN response_time_avg = 0 THEN ?
                                ELSE (response_time_avg + ?) / 2
                                END';
            $params[] = $check_result['response_time'];
            $params[] = $check_result['response_time'];
            $types .= 'ii';
        }

        // Обновляем информацию об ошибке
        if ($error_message) {
            $update_fields[] = 'last_error_message = ?';
            $update_fields[] = 'error_count = error_count + 1';
            $params[] = $error_message;
            $types .= 's';
        } else {
            $update_fields[] = 'last_error_message = NULL';
            $update_fields[] = 'error_count = 0';
        }

        // Обновляем информацию о терминале
        if ($check_result['device_info']) {
            $update_fields[] = 'terminal_info = ?';
            $params[] = json_encode($check_result['device_info'], JSON_UNESCAPED_UNICODE);
            $types .= 's';
        }

        $params[] = $terminal['id'];
        $types .= 'i';

        $update_query = "UPDATE kaspi_terminals SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($update_query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        if ($check_result['online']) {
            $online_count++;
        }

        $terminals_status[] = [
            'id' => (int) $terminal['id'],
            'terminal_name' => $terminal['terminal_name'],
            'ip_address' => $terminal['ip_address'],
            'old_status' => $terminal['status'],
            'new_status' => $new_status,
            'online' => $check_result['online'],
            'response_time' => $check_result['response_time'],
            'error' => $check_result['error'],
            'device_info' => $check_result['device_info'],
            'is_enabled' => (int) $terminal['is_enabled'],
            'maintenance_mode' => (int) $terminal['maintenance_mode']
        ];
    }

    $response = [
        'type' => true,
        'data' => [
            'terminals' => $terminals_status,
            'summary' => [
                'total_checked' => $total_checked,
                'online' => $online_count,
                'offline' => $total_checked - $online_count,
                'check_time' => date('Y-m-d H:i:s')
            ]
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => 'Ошибка проверки терминалов: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>