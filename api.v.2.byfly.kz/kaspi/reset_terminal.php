<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Устанавливаем заголовки для JSON ответа
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обрабатываем preflight запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $resp = array(
        "type" => false,
        "msg" => "Метод запроса должен быть POST"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true);

// Проверяем обязательные параметры
if (!isset($input['terminal_id']) || empty($input['terminal_id'])) {
    $resp = array(
        "type" => false,
        "msg" => "Не указан ID терминала"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$terminal_id = intval($input['terminal_id']);

try {
    // Получаем информацию о терминале
    $stmt = $db->prepare("SELECT * FROM kaspi_terminals WHERE id = ?");
    $stmt->bind_param("i", $terminal_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Терминал не найден"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $terminal = $result->fetch_assoc();

    // Логируем начало сброса
    error_log("[KASPI RESET] Начало сброса терминала ID: {$terminal_id}, IP: {$terminal['ip_address']}");

    // 1. Отменяем все активные операции на терминале
    $cancelled_operations = 0;

    // Получаем активные операции терминала
    $stmt = $db->prepare("
        SELECT id, process_id 
        FROM kaspi_payment_queue 
        WHERE terminal_id = ? 
        AND status IN ('waiting_payment') 
        AND process_id IS NOT NULL
    ");
    $stmt->bind_param("i", $terminal_id);
    $stmt->execute();
    $active_operations = $stmt->get_result();

    // Отменяем каждую активную операцию
    while ($operation = $active_operations->fetch_assoc()) {
        try {
            // Пытаемся отменить платеж на терминале
            $cancel_result = cancelTerminalPayment($terminal, $operation['process_id']);

            // Обновляем статус операции в базе
            $update_stmt = $db->prepare("
                UPDATE kaspi_payment_queue 
                SET status = 'cancelled',
                    error_message = 'Операция отменена при сбросе терминала',
                    date_completed = NOW()
                WHERE id = ?
            ");
            $update_stmt->bind_param("i", $operation['id']);
            $update_stmt->execute();

            $cancelled_operations++;
            error_log("[KASPI RESET] Отменена операция ID: {$operation['id']}, Process ID: {$operation['process_id']}");

        } catch (Exception $e) {
            error_log("[KASPI RESET] Ошибка отмены операции {$operation['id']}: " . $e->getMessage());
        }
    }

    // 2. Сбрасываем статус терминала
    $stmt = $db->prepare("
        UPDATE kaspi_terminals 
        SET status = 'free',
            last_operation_id = NULL,
            last_operation_date = NOW(),
            error_count = 0,
            last_error_message = NULL,
            maintenance_mode = 0,
            maintenance_reason = NULL,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $terminal_id);

    if (!$stmt->execute()) {
        throw new Exception("Ошибка обновления статуса терминала: " . $db->error);
    }

    // 3. Пытаемся проверить доступность терминала
    $terminal_status = checkTerminalConnection($terminal);

    // 4. Обновляем статус на основе проверки доступности
    $final_status = $terminal_status['available'] ? 'free' : 'offline';
    $stmt = $db->prepare("
        UPDATE kaspi_terminals 
        SET status = ?,
            last_check_date = NOW(),
            response_time_avg = ?
        WHERE id = ?
    ");
    $response_time = $terminal_status['response_time'] ?? 0;
    $stmt->bind_param("sii", $final_status, $response_time, $terminal_id);
    $stmt->execute();

    // 5. Записываем в лог системы
    $log_message = "Терминал {$terminal['terminal_name']} (ID: {$terminal_id}) сброшен. Отменено операций: {$cancelled_operations}. Статус: {$final_status}";

    $stmt = $db->prepare("
        INSERT INTO error_logs (text, date_create) 
        VALUES (?, NOW())
    ");
    $stmt->bind_param("s", $log_message);
    $stmt->execute();

    // Формируем ответ
    $resp = array(
        "type" => true,
        "msg" => "Терминал успешно сброшен",
        "data" => array(
            "terminal_id" => $terminal_id,
            "terminal_name" => $terminal['terminal_name'],
            "cancelled_operations" => $cancelled_operations,
            "final_status" => $final_status,
            "terminal_available" => $terminal_status['available'],
            "response_time" => $terminal_status['response_time'] ?? 0
        )
    );

    error_log("[KASPI RESET] Терминал {$terminal_id} успешно сброшен. Статус: {$final_status}");

} catch (Exception $e) {
    error_log("[KASPI RESET] Ошибка сброса терминала {$terminal_id}: " . $e->getMessage());

    $resp = array(
        "type" => false,
        "msg" => "Ошибка сброса терминала: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);

// Функция для отмены платежа на терминале
function cancelTerminalPayment($terminal, $process_id)
{
    $terminal_url = "https://{$terminal['ip_address']}:{$terminal['port']}";
    $cancel_url = "{$terminal_url}/cancel?processId={$process_id}";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    try {
        $response = file_get_contents($cancel_url, false, $context);

        if ($response === false) {
            throw new Exception("Не удалось подключиться к терминалу");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Некорректный ответ от терминала");
        }

        return array(
            'success' => isset($data['statusCode']) && $data['statusCode'] === 0,
            'data' => $data
        );

    } catch (Exception $e) {
        error_log("[KASPI CANCEL] Ошибка отмены платежа {$process_id}: " . $e->getMessage());
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

// Функция для проверки подключения к терминалу
function checkTerminalConnection($terminal)
{
    $start_time = microtime(true);
    $terminal_url = "https://{$terminal['ip_address']}:{$terminal['port']}";
    $device_info_url = "{$terminal_url}/v2/deviceinfo";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    try {
        $response = file_get_contents($device_info_url, false, $context);
        $response_time = round((microtime(true) - $start_time) * 1000); // в миллисекундах

        if ($response === false) {
            return array(
                'available' => false,
                'error' => 'Не удалось подключиться к терминалу',
                'response_time' => 0
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'available' => false,
                'error' => 'Некорректный ответ от терминала',
                'response_time' => $response_time
            );
        }

        $available = isset($data['statusCode']) && $data['statusCode'] === 0;

        return array(
            'available' => $available,
            'response_time' => $response_time,
            'device_info' => $available ? $data['data'] : null
        );

    } catch (Exception $e) {
        return array(
            'available' => false,
            'error' => $e->getMessage(),
            'response_time' => 0
        );
    }
}
?>