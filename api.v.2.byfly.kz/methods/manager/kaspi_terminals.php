<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_POST['method'] ?? $_GET['method'] ?? '';

try {
    switch ($method) {
        case 'check_payment_status':
            checkPaymentStatus();
            break;

        case 'cancel_payment':
            cancelPayment();
            break;

        case 'get_transaction_details':
            getTransactionDetails();
            break;

        case 'get_terminals_status':
            getTerminalsStatus();
            break;

        case 'actualize_payment':
            actualizePayment();
            break;

        case 'complete_payment':
            completePayment();
            break;

        default:
            throw new Exception('Неизвестный метод: ' . $method);
    }
} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function checkPaymentStatus()
{
    global $db;

    $transactionId = intval($_POST['transaction_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);

    if ($transactionId <= 0) {
        throw new Exception('ID транзакции не указан');
    }

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Получаем информацию о транзакции
    $transactionQuery = "
        SELECT 
            kt.*,
            kterm.port
        FROM kaspi_transactions kt
        LEFT JOIN kaspi_terminals kterm ON kt.terminal_id = kterm.id
        WHERE kt.id = ?
    ";

    $stmt = $db->prepare($transactionQuery);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Транзакция не найдена');
    }

    $transaction = $result->fetch_assoc();
    $terminalPort = $transaction['port'];
    $processId = $transaction['terminal_operation_id'];

    if (empty($processId)) {
        throw new Exception('ID процесса на терминале не найден');
    }

    // Проверяем статус на терминале
    $apiUrl = "http://109.175.215.40:{$terminalPort}/v2/status?processId={$processId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        throw new Exception('Ошибка соединения с терминалом');
    }

    $terminalResponse = json_decode($response, true);

    if (!$terminalResponse || $terminalResponse['statusCode'] !== 0) {
        throw new Exception('Терминал вернул ошибку при проверке статуса');
    }

    $status = $terminalResponse['data']['status'];
    $newDbStatus = '';
    $transactionNumber = '';

    switch ($status) {
        case 'wait':
            $newDbStatus = 'processing';
            break;
        case 'success':
            $newDbStatus = 'completed';
            $transactionNumber = $terminalResponse['data']['transactionId'] ?? '';
            break;
        case 'fail':
            $newDbStatus = 'failed';
            break;
        case 'unknown':
            $newDbStatus = 'processing'; // Требуется актуализация
            break;
        default:
            $newDbStatus = 'processing';
    }

    // Обновляем статус транзакции
    $updateQuery = "
        UPDATE kaspi_transactions 
        SET 
            status = ?,
            terminal_response = ?,
            transaction_number = ?,
            last_status_check = NOW(),
            attempts_count = attempts_count + 1
        WHERE id = ?
    ";

    $stmt = $db->prepare($updateQuery);
    $terminalResponseJson = json_encode($terminalResponse, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param('sssi', $newDbStatus, $terminalResponseJson, $transactionNumber, $transactionId);
    $stmt->execute();

    // Если платеж завершен успешно
    if ($status === 'success') {
        try {
            completeSuccessfulPayment($transactionId, $managerId);
        } catch (Exception $e) {
            // Логируем ошибку, но не прерываем выполнение
            logKaspiOperation(
                $transactionId,
                null,
                'complete_payment_error',
                'error',
                'Ошибка завершения платежа: ' . $e->getMessage()
            );
        }
    }

    // Если платеж неудачен или отменен
    if ($status === 'fail') {
        // Освобождаем терминал
        $updateTerminalQuery = "
            UPDATE kaspi_terminals 
            SET status = 'free' 
            WHERE id = ?
        ";
        $stmt = $db->prepare($updateTerminalQuery);
        $stmt->bind_param('i', $transaction['terminal_id']);
        $stmt->execute();
    }

    // Логируем проверку статуса
    logKaspiOperation(
        $transactionId,
        $transaction['terminal_id'],
        'status_check',
        'success',
        "Статус: {$status}"
    );

    $response = [
        'type' => true,
        'data' => [
            'status' => $status,
            'db_status' => $newDbStatus,
            'transaction_number' => $transactionNumber,
            'terminal_response' => $terminalResponse
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function cancelPayment()
{
    global $db;

    $transactionId = intval($_POST['transaction_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);

    if ($transactionId <= 0) {
        throw new Exception('ID транзакции не указан');
    }

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Получаем информацию о транзакции
    $transactionQuery = "
        SELECT 
            kt.*,
            kterm.port
        FROM kaspi_transactions kt
        LEFT JOIN kaspi_terminals kterm ON kt.terminal_id = kterm.id
        WHERE kt.id = ? AND kt.status IN ('pending', 'processing')
    ";

    $stmt = $db->prepare($transactionQuery);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Транзакция не найдена или уже завершена');
    }

    $transaction = $result->fetch_assoc();
    $terminalPort = $transaction['port'];
    $processId = $transaction['terminal_operation_id'];

    if (empty($processId)) {
        throw new Exception('ID процесса на терминале не найден');
    }

    // Отменяем платеж на терминале
    $apiUrl = "http://109.175.215.40:{$terminalPort}/v2/cancel?processId={$processId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Ошибка соединения с терминалом при отмене');
    }

    // Обновляем статус транзакции
    $updateQuery = "
        UPDATE kaspi_transactions 
        SET 
            status = 'cancelled',
            terminal_response = ?,
            last_status_check = NOW()
        WHERE id = ?
    ";

    $stmt = $db->prepare($updateQuery);
    $stmt->bind_param('si', $response, $transactionId);
    $stmt->execute();

    // Освобождаем терминал
    $updateTerminalQuery = "
        UPDATE kaspi_terminals 
        SET status = 'free' 
        WHERE id = ?
    ";
    $stmt = $db->prepare($updateTerminalQuery);
    $stmt->bind_param('i', $transaction['terminal_id']);
    $stmt->execute();

    // Логируем отмену
    logKaspiOperation(
        $transactionId,
        $transaction['terminal_id'],
        'payment_cancel',
        'success',
        "Платеж отменен менеджером ID: {$managerId}"
    );

    $response = [
        'type' => true,
        'msg' => 'Платеж успешно отменен'
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getTransactionDetails()
{
    global $db;

    $transactionId = intval($_POST['transaction_id'] ?? 0);

    if ($transactionId <= 0) {
        throw new Exception('ID транзакции не указан');
    }

    $transactionQuery = "
        SELECT 
            kt.*,
            kterm.terminal_name,
            kterm.port,
            ot.id as order_id,
            u.name as user_name,
            u.famale as user_famale
        FROM kaspi_transactions kt
        LEFT JOIN kaspi_terminals kterm ON kt.terminal_id = kterm.id
        LEFT JOIN order_tours ot ON kt.order_id = ot.id
        LEFT JOIN users u ON kt.user_id = u.id
        WHERE kt.id = ?
    ";

    $stmt = $db->prepare($transactionQuery);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Транзакция не найдена');
    }

    $transaction = $result->fetch_assoc();

    $transactionDetails = [
        'id' => intval($transaction['id']),
        'amount' => intval($transaction['amount']),
        'payment_type' => $transaction['payment_type'],
        'percentage_fee' => floatval($transaction['percentage_fee']),
        'clean_amount' => intval($transaction['clean_amount']),
        'total_amount_with_fee' => intval($transaction['total_amount_with_fee']),
        'status' => $transaction['status'],
        'date_initiated' => $transaction['date_initiated'],
        'date_completed' => $transaction['date_completed'],
        'client_phone' => $transaction['client_phone'],
        'client_name' => $transaction['client_name'],
        'terminal_operation_id' => $transaction['terminal_operation_id'],
        'transaction_number' => $transaction['transaction_number'],
        'error_message' => $transaction['error_message'],
        'attempts_count' => intval($transaction['attempts_count']),
        'last_status_check' => $transaction['last_status_check'],
        'terminal_name' => $transaction['terminal_name'],
        'terminal_port' => $transaction['port'],
        'order_id' => intval($transaction['order_id']),
        'user_name' => $transaction['user_famale'] . ' ' . $transaction['user_name']
    ];

    $response = [
        'type' => true,
        'data' => $transactionDetails
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getTerminalsStatus()
{
    global $db;

    $terminalsQuery = "
        SELECT 
            id,
            terminal_name,
            port,
            status,
            operations_count,
            last_operation_date,
            last_health_check,
            error_count,
            last_error_message,
            is_active
        FROM kaspi_terminals
        ORDER BY priority DESC, operations_count ASC
    ";

    $result = $db->query($terminalsQuery);
    $terminals = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $terminals[] = [
                'id' => intval($row['id']),
                'terminal_name' => $row['terminal_name'],
                'port' => intval($row['port']),
                'status' => $row['status'],
                'operations_count' => intval($row['operations_count']),
                'last_operation_date' => $row['last_operation_date'],
                'last_health_check' => $row['last_health_check'],
                'error_count' => intval($row['error_count']),
                'last_error_message' => $row['last_error_message'],
                'is_active' => intval($row['is_active'])
            ];
        }
    }

    $response = [
        'type' => true,
        'data' => $terminals
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function actualizePayment()
{
    global $db;

    $transactionId = intval($_POST['transaction_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);

    if ($transactionId <= 0) {
        throw new Exception('ID транзакции не указан');
    }

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Получаем информацию о транзакции
    $transactionQuery = "
        SELECT 
            kt.*,
            kterm.port
        FROM kaspi_transactions kt
        LEFT JOIN kaspi_terminals kterm ON kt.terminal_id = kterm.id
        WHERE kt.id = ?
    ";

    $stmt = $db->prepare($transactionQuery);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Транзакция не найдена');
    }

    $transaction = $result->fetch_assoc();
    $terminalPort = $transaction['port'];
    $processId = $transaction['terminal_operation_id'];

    if (empty($processId)) {
        throw new Exception('ID процесса на терминале не найден');
    }

    // Актуализируем статус на терминале
    $apiUrl = "http://109.175.215.40:{$terminalPort}/v2/actualize?processId={$processId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        throw new Exception('Ошибка соединения с терминалом при актуализации');
    }

    $terminalResponse = json_decode($response, true);

    if (!$terminalResponse || $terminalResponse['statusCode'] !== 0) {
        throw new Exception('Терминал вернул ошибку при актуализации');
    }

    $status = $terminalResponse['data']['status'];
    $newDbStatus = '';
    $transactionNumber = '';

    switch ($status) {
        case 'success':
            $newDbStatus = 'completed';
            $transactionNumber = $terminalResponse['data']['transactionId'] ?? '';
            break;
        case 'fail':
            $newDbStatus = 'failed';
            break;
        default:
            $newDbStatus = 'processing';
    }

    // Обновляем статус транзакции
    $updateQuery = "
        UPDATE kaspi_transactions 
        SET 
            status = ?,
            terminal_response = ?,
            transaction_number = ?,
            last_status_check = NOW()
        WHERE id = ?
    ";

    $stmt = $db->prepare($updateQuery);
    $terminalResponseJson = json_encode($terminalResponse, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param('sssi', $newDbStatus, $terminalResponseJson, $transactionNumber, $transactionId);
    $stmt->execute();

    // Если платеж завершен успешно
    if ($status === 'success') {
        try {
            completeSuccessfulPayment($transactionId, $managerId);
        } catch (Exception $e) {
            logKaspiOperation(
                $transactionId,
                null,
                'complete_payment_error',
                'error',
                'Ошибка завершения платежа: ' . $e->getMessage()
            );
        }
    }

    // Логируем актуализацию
    logKaspiOperation(
        $transactionId,
        $transaction['terminal_id'],
        'actualize',
        'success',
        "Актуализация выполнена, статус: {$status}"
    );

    $response = [
        'type' => true,
        'msg' => 'Актуализация выполнена',
        'data' => [
            'status' => $status,
            'db_status' => $newDbStatus,
            'transaction_number' => $transactionNumber
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function completePayment()
{
    global $db;

    $transactionId = intval($_POST['transaction_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);

    if ($transactionId <= 0) {
        throw new Exception('ID транзакции не указан');
    }

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    completeSuccessfulPayment($transactionId, $managerId);

    $response = [
        'type' => true,
        'msg' => 'Платеж успешно завершен'
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function completeSuccessfulPayment($transactionId, $managerId)
{
    global $db;

    // Получаем информацию о транзакции
    $transactionQuery = "
        SELECT 
            kt.*,
            ot.includesPrice,
            ot.price as order_price
        FROM kaspi_transactions kt
        LEFT JOIN order_tours ot ON kt.order_id = ot.id
        WHERE kt.id = ?
    ";

    $stmt = $db->prepare($transactionQuery);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Транзакция не найдена');
    }

    $transaction = $result->fetch_assoc();
    $orderId = intval($transaction['order_id']);
    $userId = intval($transaction['user_id']);
    $cleanAmount = intval($transaction['clean_amount']);

    // Начинаем транзакцию БД
    $db->begin_transaction();

    try {
        // Добавляем платеж в order_pays
        $insertPaymentQuery = "
            INSERT INTO order_pays (order_id, summ, user_id, date_create, type, tranzaction_id) 
            VALUES (?, ?, ?, NOW(), 'kaspi', ?)
        ";

        $stmt = $db->prepare($insertPaymentQuery);
        $stmt->bind_param('iiis', $orderId, $cleanAmount, $userId, $transactionId);
        $stmt->execute();

        // Обновляем сумму оплаты в заявке
        $newIncludesPrice = intval($transaction['includesPrice']) + $cleanAmount;
        $updateOrderQuery = "UPDATE order_tours SET includesPrice = ? WHERE id = ?";
        $stmt = $db->prepare($updateOrderQuery);
        $stmt->bind_param('ii', $newIncludesPrice, $orderId);
        $stmt->execute();

        // Обновляем статус транзакции
        $updateTransactionQuery = "
            UPDATE kaspi_transactions 
            SET 
                status = 'completed',
                date_completed = NOW(),
                notification_sent = 1
            WHERE id = ?
        ";
        $stmt = $db->prepare($updateTransactionQuery);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();

        // Освобождаем терминал
        $updateTerminalQuery = "
            UPDATE kaspi_terminals 
            SET 
                status = 'free',
                operations_count = operations_count + 1
            WHERE id = ?
        ";
        $stmt = $db->prepare($updateTerminalQuery);
        $stmt->bind_param('i', $transaction['terminal_id']);
        $stmt->execute();

        // Проверяем, нужно ли обновить статус заявки
        $orderPrice = intval($transaction['order_price']);
        if ($newIncludesPrice >= $orderPrice) {
            // Заявка полностью оплачена
            $updateOrderStatusQuery = "UPDATE order_tours SET status_code = 3 WHERE id = ?";
            $stmt = $db->prepare($updateOrderStatusQuery);
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
        }

        $db->commit();

        // Отправляем уведомление клиенту
        if (!empty($transaction['client_phone'])) {
            $message = "✅ Платеж успешно проведен!\n\n";
            $message .= "💰 Сумма: " . number_format($cleanAmount, 0, '', ' ') . " ₸\n";
            $message .= "📋 Заявка: #{$orderId}\n\n";
            $message .= "Спасибо за оплату! 🙏";

            sendWhatsapp($transaction['client_phone'], $message);
        }

        // Логируем успешное завершение
        logKaspiOperation(
            $transactionId,
            $transaction['terminal_id'],
            'payment_completed',
            'success',
            "Платеж завершен, сумма: {$cleanAmount} тенге"
        );

    } catch (Exception $e) {
        $db->rollback();
        throw new Exception('Ошибка завершения платежа: ' . $e->getMessage());
    }
}

function logKaspiOperation($transactionId, $terminalId, $action, $status, $message)
{
    global $db;

    $stmt = $db->prepare("
        INSERT INTO kaspi_operation_logs (
            transaction_id, 
            terminal_id, 
            action, 
            status, 
            message, 
            date_created
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param('iisss', $transactionId, $terminalId, $action, $status, $message);
    $stmt->execute();
}

// Функция для автоматической проверки зависших транзакций
function checkTimeoutTransactions()
{
    global $db;

    // Находим транзакции, которые висят более 2.5 минут
    $timeoutQuery = "
        SELECT 
            kt.id,
            kt.terminal_id,
            kt.terminal_operation_id,
            kterm.port
        FROM kaspi_transactions kt
        LEFT JOIN kaspi_terminals kterm ON kt.terminal_id = kterm.id
        WHERE 
            kt.status IN ('pending', 'processing')
            AND kt.date_initiated < DATE_SUB(NOW(), INTERVAL 150 SECOND)
    ";

    $result = $db->query($timeoutQuery);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactionId = intval($row['id']);
            $terminalId = intval($row['terminal_id']);
            $processId = $row['terminal_operation_id'];
            $terminalPort = $row['port'];

            try {
                // Пытаемся отменить операцию на терминале
                if (!empty($processId) && !empty($terminalPort)) {
                    $apiUrl = "http://109.175.215.40:{$terminalPort}/v2/cancel?processId={$processId}";

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

                    curl_exec($ch);
                    curl_close($ch);
                }

                // Обновляем статус транзакции на timeout
                $updateQuery = "
                    UPDATE kaspi_transactions 
                    SET 
                        status = 'timeout',
                        error_message = 'Превышено время ожидания оплаты'
                    WHERE id = ?
                ";
                $stmt = $db->prepare($updateQuery);
                $stmt->bind_param('i', $transactionId);
                $stmt->execute();

                // Освобождаем терминал
                $updateTerminalQuery = "UPDATE kaspi_terminals SET status = 'free' WHERE id = ?";
                $stmt = $db->prepare($updateTerminalQuery);
                $stmt->bind_param('i', $terminalId);
                $stmt->execute();

                // Логируем таймаут
                logKaspiOperation(
                    $transactionId,
                    $terminalId,
                    'timeout',
                    'timeout',
                    'Транзакция отменена по таймауту'
                );

            } catch (Exception $e) {
                // Логируем ошибку обработки таймаута
                logKaspiOperation(
                    $transactionId,
                    $terminalId,
                    'timeout_error',
                    'error',
                    'Ошибка обработки таймаута: ' . $e->getMessage()
                );
            }
        }
    }
}

// Запускаем проверку таймаутов при каждом обращении к API
checkTimeoutTransactions();
?>