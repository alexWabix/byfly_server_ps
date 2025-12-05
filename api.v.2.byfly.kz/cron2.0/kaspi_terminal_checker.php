<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Устанавливаем временную зону
date_default_timezone_set('Asia/Almaty');

// Функция для отправки HTTP запросов к терминалам
function sendTerminalRequest($port, $endpoint, $timeout = 10)
{
    $url = "http://109.175.215.40:$port$endpoint";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("CURL Error: $error");
    }

    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: $httpCode");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Decode Error: " . json_last_error_msg());
    }

    return $data;
}

// Функция для освобождения терминала
function freeTerminal($terminalId)
{
    global $db;

    $sql = "UPDATE kaspi_terminals 
            SET status = 'free', last_health_check = NOW() 
            WHERE id = $terminalId";

    return mysqli_query($db, $sql);
}

// Функция для отмены платежа и обновления статуса в БД
function cancelPayment($transactionId, $terminalId, $reason = 'Платеж отменен пользователем')
{
    global $db;

    $escapedReason = mysqli_real_escape_string($db, $reason);

    $sql = "UPDATE kaspi_transactions 
            SET status = 'cancelled', 
                error_message = '$escapedReason',
                date_completed = NOW(),
                last_status_check = NOW()
            WHERE id = $transactionId";

    $result = mysqli_query($db, $sql);

    if ($result) {
        // Отправляем уведомление об отмене
        $notificationQuery = "SELECT notification_sent FROM kaspi_transactions WHERE id = $transactionId";
        $notificationResult = mysqli_query($db, $notificationQuery);
        $notificationData = mysqli_fetch_assoc($notificationResult);

        if (!$notificationData['notification_sent']) {
            sendPaymentNotification($transactionId, 'cancelled');
        }
    }

    return $result;
}

// Функция для отмены платежа на терминале и обновления статуса в БД
function cancelTerminalPayment($terminalPort, $processId, $transactionId, $terminalId)
{
    try {
        // Отправляем запрос на отмену на терминал
        $response = sendTerminalRequest($terminalPort, "/v2/cancel?processId=$processId", 15);

        // Отменяем платеж в БД
        return cancelPayment($transactionId, $terminalId, 'Платеж отменен через терминал');

    } catch (Exception $e) {
        // Даже если не удалось отменить на терминале, обновляем статус в БД
        return cancelPayment($transactionId, $terminalId, "Ошибка отмены на терминале: " . $e->getMessage());
    }
}

// Функция для проверки статуса платежа на терминале
function checkTerminalPaymentStatus($terminalPort, $processId, $transactionId, $terminalId)
{
    try {
        $response = sendTerminalRequest($terminalPort, "/v2/status?processId=$processId", 10);

        if (isset($response['statusCode']) && $response['statusCode'] == 0 && isset($response['data'])) {
            $status = $response['data']['status'] ?? '';
            $subStatus = $response['data']['subStatus'] ?? '';

            // Проверяем на отмену пользователем
            if ($status === 'wait' && $subStatus === 'WaitForQrConfirmation') {
                return [
                    'success' => true,
                    'status' => 'cancelled_by_user',
                    'data' => $response['data']
                ];
            }

            return [
                'success' => true,
                'status' => $status,
                'subStatus' => $subStatus,
                'data' => $response['data']
            ];
        } else {
            throw new Exception('Некорректный ответ от терминала: ' . json_encode($response));
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Функция для актуализации статуса платежа
function actualizeTerminalPaymentStatus($terminalPort, $processId, $transactionId, $terminalId)
{
    try {
        $response = sendTerminalRequest($terminalPort, "/v2/actualize?processId=$processId", 20);

        if (isset($response['statusCode']) && $response['statusCode'] == 0 && isset($response['data'])) {
            $status = $response['data']['status'] ?? '';
            $subStatus = $response['data']['subStatus'] ?? '';

            // Проверяем на отмену пользователем
            if ($status === 'wait' && $subStatus === 'WaitForQrConfirmation') {
                return [
                    'success' => true,
                    'status' => 'cancelled_by_user',
                    'data' => $response['data']
                ];
            }

            return [
                'success' => true,
                'status' => $status,
                'subStatus' => $subStatus,
                'data' => $response['data']
            ];
        } else {
            throw new Exception('Некорректный ответ при актуализации: ' . json_encode($response));
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Функция для обработки успешной оплаты тура
function processSuccessfulTourPayment($transaction)
{
    global $db;

    $orderId = $transaction['order_id'];
    $amount = $transaction['clean_amount'];
    $transactionNumber = $transaction['transaction_number'];

    try {
        // Проверяем, не была ли уже обработана эта транзакция
        $checkQuery = "SELECT status FROM kaspi_transactions WHERE id = {$transaction['id']}";
        $checkResult = $db->query($checkQuery);
        $currentTransaction = $checkResult->fetch_assoc();

        if ($currentTransaction['status'] !== 'completed') {
            return false;
        }

        // Получаем текущую информацию о заказе
        $orderQuery = "SELECT includesPrice FROM order_tours WHERE id = $orderId";
        $orderResult = $db->query($orderQuery);

        if ($orderResult->num_rows == 0) {
            return false;
        }

        $currentOrder = $orderResult->fetch_assoc();

        // Обновляем заказ
        $newIncludesPrice = $currentOrder['includesPrice'] + $amount;
        if ($newIncludesPrice >= $currentOrder['price']) {
            $updateOrderQuery = "UPDATE order_tours SET includesPrice = '$newIncludesPrice', status_code='3' WHERE id = $orderId";
        } else {
            $updateOrderQuery = "UPDATE order_tours SET includesPrice = '$newIncludesPrice' WHERE id = $orderId";
        }

        $db->query($updateOrderQuery);

        // Создаем запись об оплате
        $insertPayQuery = "INSERT INTO order_pays 
                          (order_id, summ, user_id, date_create, type, tranzaction_id)
                          VALUES ($orderId, $amount, 0, NOW(), 'kaspi', '$transactionNumber')";
        $db->query($insertPayQuery);

        // Отправляем уведомления
        sendTourPaymentNotifications($orderId, $amount, $transactionNumber);

        return true;

    } catch (Exception $e) {
        return false;
    }
}

// Функция отправки уведомлений об оплате тура
function sendTourPaymentNotifications($orderId, $amount, $transactionNumber)
{
    global $db;

    // Получаем информацию о заказе
    $query = "SELECT ot.*, u.phone as user_phone, u.name as user_name, u.famale as user_famale,
                     m.phone_whatsapp as manager_phone, m.fio as manager_name,
                     seller.phone as seller_phone, seller.name as seller_name, seller.famale as seller_famale
              FROM order_tours ot
              LEFT JOIN users u ON ot.user_id = u.id
              LEFT JOIN managers m ON ot.manager_id = m.id
              LEFT JOIN users seller ON ot.saler_id = seller.id
              WHERE ot.id = $orderId";

    $result = $db->query($query);
    if ($result->num_rows == 0) {
        return;
    }

    $order = $result->fetch_assoc();
    $tourInfo = json_decode($order['tours_info'], true);
    $orderNumber = str_pad($orderId, 8, '0', STR_PAD_LEFT);

    // Уведомление клиенту
    if ($order['user_phone']) {
        $clientMessage = "✅ Платеж успешно получен!\n\n";
        $clientMessage .= "🎫 Заказ №$orderNumber\n";
        $clientMessage .= "🏖️ {$tourInfo['countryname']}, {$tourInfo['hotelname']}\n";
        $clientMessage .= "💰 Оплачено: " . number_format($amount, 0, ',', ' ') . " ₸\n";
        $clientMessage .= "🧾 № транзакции: $transactionNumber\n\n";
        $clientMessage .= "📋 Получить ваучер: https://byfly-travel.com/vaucher.php?orderId=$orderId\n\n";
        $clientMessage .= "Спасибо за выбор ByFly Travel! 🌟";

        sendWhatsapp($order['user_phone'], $clientMessage);
    }

    // Уведомление продавцу (если есть и отличается от клиента)
    if ($order['saler_id'] > 0 && $order['saler_id'] != $order['user_id'] && $order['seller_phone']) {
        $sellerMessage = "💰 Получена оплата по вашей продаже!\n\n";
        $sellerMessage .= "🎫 Заказ №$orderNumber\n";
        $sellerMessage .= "👤 Клиент: {$order['user_name']} {$order['user_famale']}\n";
        $sellerMessage .= "🏖️ Тур: {$tourInfo['countryname']}, {$tourInfo['hotelname']}\n";
        $sellerMessage .= "💰 Сумма: " . number_format($amount, 0, ',', ' ') . " ₸\n";
        $sellerMessage .= "🧾 № транзакции: $transactionNumber\n\n";
        $sellerMessage .= "Отличная работа! 👏";

        sendWhatsapp($order['seller_phone'], $sellerMessage);
    }

    // Уведомление менеджеру
    if ($order['manager_phone']) {
        $managerMessage = "💳 Поступила оплата по заказу\n\n";
        $managerMessage .= "🎫 Заказ №$orderNumber\n";
        $managerMessage .= "👤 Клиент: {$order['user_name']} {$order['user_famale']}\n";
        $managerMessage .= "📞 Телефон: {$order['user_phone']}\n";
        $managerMessage .= "🏖️ Тур: {$tourInfo['countryname']}, {$tourInfo['hotelname']}\n";
        $managerMessage .= "💰 Сумма: " . number_format($amount, 0, ',', ' ') . " ₸\n";
        $managerMessage .= "🧾 № транзакции: $transactionNumber\n\n";
        $managerMessage .= "Требуется обработка заказа 📋";

        sendWhatsapp($order['manager_phone'], $managerMessage);
    }
}

// Функция для завершения успешного платежа
function completePayment($transactionId, $terminalId, $paymentData)
{
    global $db;

    $transactionNumber = $paymentData['transactionId'] ?? $paymentData['processId'] ?? '';
    $terminalResponseBase64 = base64_encode(json_encode($paymentData));

    $escapedTransactionNumber = mysqli_real_escape_string($db, $transactionNumber);

    $sql = "UPDATE kaspi_transactions 
            SET status = 'completed', 
                date_completed = NOW(), 
                transaction_number = '$escapedTransactionNumber',
                terminal_response = '$terminalResponseBase64',
                last_status_check = NOW()
            WHERE id = $transactionId";

    if (mysqli_query($db, $sql)) {
        // Получаем данные транзакции для проверки на оплату тура
        $transactionQuery = "SELECT * FROM kaspi_transactions WHERE id = $transactionId";
        $transactionResult = $db->query($transactionQuery);
        $transaction = $transactionResult->fetch_assoc();

        // Если это оплата тура - обрабатываем
        if ($transaction['order_type'] === 'tour' && $transaction['order_id'] > 0) {
            processSuccessfulTourPayment($transaction);
        }

        // Отправляем уведомление пользователю об успешной оплате
        sendPaymentNotification($transactionId, 'completed');

        return true;
    }

    return false;
}

// Функция для отправки уведомлений пользователям
function sendPaymentNotification($transactionId, $status)
{
    global $db;

    // Получаем данные транзакции и пользователя
    $sql = "SELECT t.*, u.phone as user_phone, u.name, u.famale 
            FROM kaspi_transactions t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.id = $transactionId";

    $result = mysqli_query($db, $sql);
    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }

    $transaction = mysqli_fetch_assoc($result);

    // Если нет телефона пользователя, используем client_phone
    $phone = $transaction['user_phone'] ?: $transaction['client_phone'];

    if (empty($phone)) {
        return false;
    }

    // Форматируем сумму
    $amount = number_format($transaction['total_amount_with_fee'], 0, '.', ' ');
    $userName = trim(($transaction['name'] ?? '') . ' ' . ($transaction['famale'] ?? ''));

    // Формируем сообщение в зависимости от статуса
    switch ($status) {
        case 'completed':
            $message = "✅ *Платеж успешно завершен!*\n\n";
            $message .= "💰 Сумма: *{$amount} ₸*\n";
            $message .= "💳 Способ: " . getPaymentTypeTitle($transaction['payment_type']) . "\n";
            if (!empty($transaction['transaction_number'])) {
                $message .= "🔢 № транзакции: `{$transaction['transaction_number']}`\n";
            }
            $message .= "📅 Дата: " . date('d.m.Y H:i', strtotime($transaction['date_completed'])) . "\n\n";
            $message .= "Спасибо за использование наших услуг! 🙏";
            break;

        case 'timeout':
            $message = "⏰ *Время оплаты истекло*\n\n";
            $message .= "К сожалению, время на оплату (2 минуты 30 секунд) истекло.\n\n";
            $message .= "💰 Сумма: *{$amount} ₸*\n";
            $message .= "💳 Способ: " . getPaymentTypeTitle($transaction['payment_type']) . "\n\n";
            $message .= "Вы можете повторить попытку оплаты в любое время.\n\n";
            break;

        case 'failed':
            $message = "❌ *Ошибка платежа*\n\n";
            $message .= "Произошла ошибка при обработке платежа.\n\n";
            $message .= "💰 Сумма: *{$amount} ₸*\n";
            $message .= "💳 Способ: " . getPaymentTypeTitle($transaction['payment_type']) . "\n";
            if (!empty($transaction['error_message'])) {
                $message .= "📝 Причина: {$transaction['error_message']}\n";
            }
            break;

        case 'cancelled':
            $message = "🚫 *Платеж отменен*\n\n";
            $message .= "Платеж был отменен.\n\n";
            $message .= "💰 Сумма: *{$amount} ₸*\n";
            $message .= "💳 Способ: " . getPaymentTypeTitle($transaction['payment_type']) . "\n\n";
            $message .= "Вы можете повторить попытку оплаты в любое время.";
            break;

        default:
            return false;
    }

    // Добавляем персональное обращение если есть имя
    if (!empty($userName)) {
        $message = "Здравствуйте, $userName!\n\n" . $message;
    }

    try {
        // Отправляем WhatsApp сообщение
        sendWhatsapp($phone, $message);

        // Обновляем флаг отправки уведомления
        $sql = "UPDATE kaspi_transactions SET notification_sent = 1 WHERE id = $transactionId";
        mysqli_query($db, $sql);

        return true;

    } catch (Exception $e) {
        return false;
    }
}

// Функция для получения названия типа оплаты
function getPaymentTypeTitle($type)
{
    switch ($type) {
        case 'cash':
            return 'Kaspi Gold';
        case 'kaspi_red':
            return 'Kaspi Red';
        case 'credit':
            return 'Kaspi Кредит';
        case 'installment':
            return 'Рассрочка';
        default:
            return ucfirst($type);
    }
}

// Функция для проверки здоровья терминалов
function checkTerminalHealth()
{
    global $db;

    // Получаем все активные терминалы
    $sql = "SELECT id, port, terminal_name, status, last_health_check, error_count 
            FROM kaspi_terminals 
            WHERE is_active = 1";

    $result = mysqli_query($db, $sql);
    if (!$result) {
        return;
    }

    while ($terminal = mysqli_fetch_assoc($result)) {
        $terminalId = $terminal['id'];
        $port = $terminal['port'];

        try {
            // Простая проверка доступности терминала
            sendTerminalRequest($port, '/v2/status?processId=health_check', 5);

            // Терминал отвечает - обновляем статус
            $sql = "UPDATE kaspi_terminals 
                    SET last_health_check = NOW(), 
                        error_count = 0 
                    WHERE id = $terminalId";
            mysqli_query($db, $sql);

        } catch (Exception $e) {
            // Терминал не отвечает - увеличиваем счетчик ошибок
            $errorMessage = mysqli_real_escape_string($db, $e->getMessage());

            $sql = "UPDATE kaspi_terminals 
                    SET error_count = error_count + 1,
                        last_error_message = '$errorMessage',
                        last_health_check = NOW()
                    WHERE id = $terminalId";
            mysqli_query($db, $sql);

            // Если много ошибок подряд - переводим в offline
            if ($terminal['error_count'] >= 3) {
                $sql = "UPDATE kaspi_terminals SET status = 'offline' WHERE id = $terminalId";
                mysqli_query($db, $sql);
            }
        }
    }
}

// Основная функция мониторинга
function monitorTransactions()
{
    global $db;

    // 1. Проверяем транзакции с истекшим временем (более 2 минут 30 секунд)
    $sql = "SELECT t.id, t.terminal_id, t.terminal_operation_id, t.amount, t.total_amount_with_fee, 
                   t.payment_type, t.client_phone, t.user_id, t.date_initiated, t.notification_sent,
                   term.port, term.terminal_name
            FROM kaspi_transactions t
            LEFT JOIN kaspi_terminals term ON t.terminal_id = term.id
            WHERE t.status IN ('pending', 'processing') 
            AND t.date_initiated < DATE_SUB(NOW(), INTERVAL 150 SECOND)
            ORDER BY t.date_initiated ASC";

    $result = mysqli_query($db, $sql);
    if (!$result) {
        return;
    }

    while ($transaction = mysqli_fetch_assoc($result)) {
        $transactionId = $transaction['id'];
        $terminalId = $transaction['terminal_id'];
        $processId = $transaction['terminal_operation_id'];
        $terminalPort = $transaction['port'];

        // Отменяем платеж на терминале если есть processId
        if (!empty($processId) && !empty($terminalPort)) {
            cancelTerminalPayment($terminalPort, $processId, $transactionId, $terminalId);
        } else {
            // Если нет processId, просто обновляем статус на timeout
            $sql = "UPDATE kaspi_transactions 
                    SET status = 'timeout', 
                        error_message = 'Время ожидания истекло (2 минуты 30 секунд)',
                        date_completed = NOW(),
                        last_status_check = NOW()
                    WHERE id = $transactionId";

            if (mysqli_query($db, $sql)) {
                // Отправляем уведомление если еще не отправляли
                if (!$transaction['notification_sent']) {
                    sendPaymentNotification($transactionId, 'timeout');
                }
            }
        }

        // Освобождаем терминал
        if (!empty($terminalId)) {
            freeTerminal($terminalId);
        }
    }

    // 2. Проверяем активные транзакции (обновляем статус)
    $sql = "SELECT t.id, t.terminal_id, t.terminal_operation_id, t.amount, t.total_amount_with_fee,
                   t.payment_type, t.client_phone, t.user_id, t.attempts_count, t.notification_sent,
                   term.port, term.terminal_name
            FROM kaspi_transactions t
            LEFT JOIN kaspi_terminals term ON t.terminal_id = term.id
            WHERE t.status IN ('pending', 'processing') 
            AND t.date_initiated >= DATE_SUB(NOW(), INTERVAL 150 SECOND)
            AND t.terminal_operation_id IS NOT NULL
            AND t.terminal_operation_id != ''
            ORDER BY t.date_initiated ASC";

    $result = mysqli_query($db, $sql);
    if (!$result) {
        return;
    }

    while ($transaction = mysqli_fetch_assoc($result)) {
        $transactionId = $transaction['id'];
        $terminalId = $transaction['terminal_id'];
        $processId = $transaction['terminal_operation_id'];
        $terminalPort = $transaction['port'];
        $attempts = $transaction['attempts_count'];

        if (empty($terminalPort) || empty($processId)) {
            continue;
        }

        // Проверяем статус на терминале
        $statusResult = checkTerminalPaymentStatus($terminalPort, $processId, $transactionId, $terminalId);

        if ($statusResult['success']) {
            $status = $statusResult['status'];

            // Обновляем счетчик попыток
            $sql = "UPDATE kaspi_transactions 
                    SET attempts_count = attempts_count + 1, 
                        last_status_check = NOW() 
                    WHERE id = $transactionId";
            mysqli_query($db, $sql);

            if ($status === 'success') {
                // Платеж успешен
                completePayment($transactionId, $terminalId, $statusResult['data']);
                freeTerminal($terminalId);

            } elseif ($status === 'fail') {
                // Платеж отклонен
                $sql = "UPDATE kaspi_transactions 
                        SET status = 'failed', 
                            error_message = 'Платеж отклонен',
                            date_completed = NOW(),
                            last_status_check = NOW()
                        WHERE id = $transactionId";

                if (mysqli_query($db, $sql)) {
                    if (!$transaction['notification_sent']) {
                        sendPaymentNotification($transactionId, 'failed');
                    }
                }

                freeTerminal($terminalId);

            } elseif ($status === 'cancelled_by_user') {
                // Пользователь отменил платеж (WaitForQrConfirmation)
                cancelPayment($transactionId, $terminalId, 'Платеж отменен пользователем');
                freeTerminal($terminalId);

            } elseif ($status === 'unknown') {
                // Неопределенный статус - пытаемся актуализировать
                $actualizeResult = actualizeTerminalPaymentStatus($terminalPort, $processId, $transactionId, $terminalId);

                if ($actualizeResult['success']) {
                    $actualizedStatus = $actualizeResult['status'];

                    if ($actualizedStatus === 'success') {
                        completePayment($transactionId, $terminalId, $actualizeResult['data']);
                        freeTerminal($terminalId);

                    } elseif ($actualizedStatus === 'fail') {
                        $sql = "UPDATE kaspi_transactions 
                                SET status = 'failed', 
                                    error_message = 'Платеж отклонен после актуализации',
                                    date_completed = NOW(),
                                    last_status_check = NOW()
                                WHERE id = $transactionId";

                        if (mysqli_query($db, $sql)) {
                            if (!$transaction['notification_sent']) {
                                sendPaymentNotification($transactionId, 'failed');
                            }
                        }

                        freeTerminal($terminalId);

                    } elseif ($actualizedStatus === 'cancelled_by_user') {
                        // Пользователь отменил платеж при актуализации
                        cancelPayment($transactionId, $terminalId, 'Платеж отменен пользователем при актуализации');
                        freeTerminal($terminalId);
                    }
                }
            }

        } else {
            // Ошибка связи с терминалом
            // Если много неудачных попыток - помечаем как ошибку
            if ($attempts >= 10) {
                $errorMessage = mysqli_real_escape_string($db, "Множественные ошибки связи с терминалом: " . $statusResult['error']);

                $sql = "UPDATE kaspi_transactions 
                        SET status = 'failed', 
                            error_message = '$errorMessage',
                            date_completed = NOW(),
                            last_status_check = NOW()
                        WHERE id = $transactionId";

                if (mysqli_query($db, $sql)) {
                    if (!$transaction['notification_sent']) {
                        sendPaymentNotification($transactionId, 'failed');
                    }
                }

                freeTerminal($terminalId);
            }
        }
    }

    // 3. Освобождаем зависшие терминалы
    $sql = "SELECT id, terminal_name, port, last_operation_date 
            FROM kaspi_terminals 
            WHERE status = 'busy' 
            AND last_operation_date < DATE_SUB(NOW(), INTERVAL 300 SECOND)";

    $result = mysqli_query($db, $sql);
    if ($result) {
        while ($terminal = mysqli_fetch_assoc($result)) {
            freeTerminal($terminal['id']);
        }
    }

    // 4. Проверяем здоровье терминалов (каждые 5 минут)
    $currentMinute = (int) date('i');
    if ($currentMinute % 5 === 0) {
        checkTerminalHealth();
    }
}

// Проверяем подключение к базе данных
if ($db->connect_error) {
    exit(1);
}

// Запускаем мониторинг
try {
    monitorTransactions();
} catch (Exception $e) {
    exit(1);
}

// Закрываем подключение к базе данных
$db->close();

?>