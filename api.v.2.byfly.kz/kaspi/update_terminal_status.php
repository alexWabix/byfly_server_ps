<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $resp = array(
        "type" => false,
        "msg" => "Метод запроса должен быть POST"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем данные из POST запроса
$input = json_decode(file_get_contents('php://input'), true);

// Проверяем обязательные параметры
if (!isset($input['terminal_id']) || !isset($input['status'])) {
    $resp = array(
        "type" => false,
        "msg" => "Не указаны обязательные параметры: terminal_id, status"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$terminal_id = intval($input['terminal_id']);
$status = $input['status'];

// Проверяем корректность статуса
$allowed_statuses = ['free', 'busy', 'offline', 'error', 'maintenance'];
if (!in_array($status, $allowed_statuses)) {
    $resp = array(
        "type" => false,
        "msg" => "Некорректный статус. Допустимые значения: " . implode(', ', $allowed_statuses)
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем существование терминала
$check_query = "SELECT id, terminal_name FROM kaspi_terminals WHERE id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("i", $terminal_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $resp = array(
        "type" => false,
        "msg" => "Терминал с ID {$terminal_id} не найден"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$terminal_info = $check_result->fetch_assoc();

try {
    // Подготавливаем данные для обновления
    $last_operation_id = isset($input['last_operation_id']) ? intval($input['last_operation_id']) : null;
    $last_operation_date = isset($input['last_operation_date']) ? $input['last_operation_date'] : null;
    $last_check_date = isset($input['last_check_date']) ? $input['last_check_date'] : date('Y-m-d H:i:s');
    $error_message = isset($input['error_message']) ? $input['error_message'] : null;

    // Если статус меняется на 'error', увеличиваем счетчик ошибок
    if ($status === 'error') {
        $update_query = "UPDATE kaspi_terminals SET 
                        status = ?, 
                        last_operation_id = ?, 
                        last_operation_date = ?, 
                        last_check_date = ?, 
                        last_error_message = ?,
                        error_count = error_count + 1,
                        updated_at = NOW()
                        WHERE id = ?";

        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param(
            "sisssi",
            $status,
            $last_operation_id,
            $last_operation_date,
            $last_check_date,
            $error_message,
            $terminal_id
        );
    } else {
        // Для остальных статусов обычное обновление
        $update_query = "UPDATE kaspi_terminals SET 
                        status = ?, 
                        last_operation_id = ?, 
                        last_operation_date = ?, 
                        last_check_date = ?, 
                        updated_at = NOW()
                        WHERE id = ?";

        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param(
            "sissi",
            $status,
            $last_operation_id,
            $last_operation_date,
            $last_check_date,
            $terminal_id
        );

        // Если статус успешный (free, busy), сбрасываем счетчик ошибок
        if (in_array($status, ['free', 'busy'])) {
            $reset_errors_query = "UPDATE kaspi_terminals SET error_count = 0, last_error_message = NULL WHERE id = ?";
            $reset_stmt = $db->prepare($reset_errors_query);
            $reset_stmt->bind_param("i", $terminal_id);
            $reset_stmt->execute();
        }
    }

    // Выполняем основное обновление
    if (!$update_stmt->execute()) {
        throw new Exception("Ошибка выполнения запроса: " . $update_stmt->error);
    }

    // Проверяем, была ли обновлена запись
    if ($update_stmt->affected_rows === 0) {
        // Возможно, данные не изменились, это не ошибка
        $resp = array(
            "type" => true,
            "msg" => "Статус терминала не изменился (данные актуальны)",
            "data" => array(
                "terminal_id" => $terminal_id,
                "terminal_name" => $terminal_info['terminal_name'],
                "status" => $status,
                "updated" => false
            )
        );
    } else {
        // Получаем обновленную информацию о терминале
        $get_updated_query = "SELECT 
                                id,
                                terminal_name,
                                status,
                                last_operation_id,
                                last_operation_date,
                                last_check_date,
                                error_count,
                                last_error_message,
                                operations_count_today,
                                operations_count_total,
                                updated_at
                              FROM kaspi_terminals 
                              WHERE id = ?";

        $get_stmt = $db->prepare($get_updated_query);
        $get_stmt->bind_param("i", $terminal_id);
        $get_stmt->execute();
        $updated_result = $get_stmt->get_result();
        $updated_data = $updated_result->fetch_assoc();

        $resp = array(
            "type" => true,
            "msg" => "Статус терминала успешно обновлен",
            "data" => array(
                "terminal_id" => $terminal_id,
                "terminal_name" => $updated_data['terminal_name'],
                "old_status" => $terminal_info['status'] ?? 'unknown',
                "new_status" => $updated_data['status'],
                "last_operation_id" => $updated_data['last_operation_id'],
                "last_operation_date" => $updated_data['last_operation_date'],
                "last_check_date" => $updated_data['last_check_date'],
                "error_count" => $updated_data['error_count'],
                "last_error_message" => $updated_data['last_error_message'],
                "operations_count_today" => $updated_data['operations_count_today'],
                "operations_count_total" => $updated_data['operations_count_total'],
                "updated_at" => $updated_data['updated_at'],
                "updated" => true
            )
        );

        // Логируем изменение статуса
        $log_message = "Статус терминала {$updated_data['terminal_name']} (ID: {$terminal_id}) изменен на: {$status}";
        if ($last_operation_id) {
            $log_message .= ", операция: {$last_operation_id}";
        }
        if ($error_message) {
            $log_message .= ", ошибка: {$error_message}";
        }

        error_log("[KASPI TERMINAL] " . $log_message);
    }

    // Если терминал переходит в статус offline или error, проверяем автоотключение
    if (in_array($status, ['offline', 'error'])) {
        $check_auto_disable_query = "SELECT error_count, auto_disable_errors FROM kaspi_terminals WHERE id = ?";
        $check_auto_stmt = $db->prepare($check_auto_disable_query);
        $check_auto_stmt->bind_param("i", $terminal_id);
        $check_auto_stmt->execute();
        $auto_result = $check_auto_stmt->get_result();
        $auto_data = $auto_result->fetch_assoc();

        if ($auto_data && $auto_data['error_count'] >= $auto_data['auto_disable_errors']) {
            // Автоматически отключаем терминал
            $disable_query = "UPDATE kaspi_terminals SET 
                             is_enabled = 0, 
                             maintenance_mode = 1,
                             maintenance_reason = 'Автоматическое отключение из-за превышения лимита ошибок'
                             WHERE id = ?";
            $disable_stmt = $db->prepare($disable_query);
            $disable_stmt->bind_param("i", $terminal_id);
            $disable_stmt->execute();

            $resp['data']['auto_disabled'] = true;
            $resp['data']['disable_reason'] = 'Превышен лимит ошибок';

            error_log("[KASPI TERMINAL] Терминал {$terminal_info['terminal_name']} (ID: {$terminal_id}) автоматически отключен из-за превышения лимита ошибок");

            // Отправляем уведомление администратору
            adminNotification("Терминал {$terminal_info['terminal_name']} автоматически отключен из-за превышения лимита ошибок ({$auto_data['error_count']} ошибок подряд)");
        }
    }

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => "Ошибка при обновлении статуса терминала: " . $e->getMessage()
    );

    error_log("[KASPI TERMINAL ERROR] Ошибка обновления статуса терминала {$terminal_id}: " . $e->getMessage());
}

// Закрываем подготовленные запросы
if (isset($check_stmt))
    $check_stmt->close();
if (isset($update_stmt))
    $update_stmt->close();
if (isset($get_stmt))
    $get_stmt->close();
if (isset($reset_stmt))
    $reset_stmt->close();
if (isset($check_auto_stmt))
    $check_auto_stmt->close();
if (isset($disable_stmt))
    $disable_stmt->close();

// Возвращаем результат
echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>