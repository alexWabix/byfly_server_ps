<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


// Инициализируем запись если её нет
$checkControl = $db->query("SELECT COUNT(*) as count FROM mailing_cron_control");
$controlExists = $checkControl->fetch_assoc()['count'];

if ($controlExists == 0) {
    $db->query("INSERT INTO mailing_cron_control (is_running) VALUES (0)");
}

// Получаем текущие настройки
$controlQuery = $db->query("SELECT * FROM mailing_cron_control ORDER BY id DESC LIMIT 1");
$control = $controlQuery->fetch_assoc();

// Проверяем, запущен ли уже процесс
if ($control['is_running'] == 1) {
    // Проверяем, не завис ли процесс (если последний запуск был более 5 минут назад)
    $lastStart = strtotime($control['last_start_time']);
    $currentTime = time();

    if (($currentTime - $lastStart) > 300) { // 5 минут
        // Процесс завис, сбрасываем статус
        $db->query("UPDATE mailing_cron_control SET is_running = 0 WHERE id = {$control['id']}");
        error_log("Mailing cron process was stuck, resetting status");
    } else {
        // Процесс уже запущен, выходим
        exit("Mailing process is already running");
    }
}

// Отмечаем начало работы
$db->query("UPDATE mailing_cron_control SET 
    is_running = 1, 
    last_start_time = NOW() 
    WHERE id = {$control['id']}");

// Функция для логирования
function logMessage($message)
{
    error_log("[Mailing Cron] " . date('Y-m-d H:i:s') . " - " . $message);
}

// Функция для отправки WhatsApp сообщения
function sendWhatsAppMessage($phone, $message)
{
    return sendWhatsapp($phone, $message);
}

// Функция для обновления статистики
function updateStats($control, $messagesSent, $db)
{
    $newTotal = $control['messages_sent_total'] + $messagesSent;
    $newToday = $control['messages_sent_today'] + $messagesSent;

    // Вычисляем среднюю скорость
    $startTime = strtotime($control['last_start_time']);
    $currentTime = time();
    $minutesPassed = max(1, ($currentTime - $startTime) / 60);
    $averageSpeed = $messagesSent / $minutesPassed;

    $db->query("UPDATE mailing_cron_control SET 
        messages_sent_total = $newTotal,
        messages_sent_today = $newToday,
        average_speed_per_minute = $averageSpeed
        WHERE id = {$control['id']}");
}

// Основная логика отправки
try {
    logMessage("Starting mailing process");

    $messagesSentThisRun = 0;
    $maxMessagesPerMinute = $control['max_messages_per_minute'];
    $testMode = $control['test_mode'];
    $testPhone = $control['test_phone'];

    // Получаем активные рассылки с неотправленными сообщениями
    $mailingsQuery = $db->query("
        SELECT DISTINCT m.id, m.title, m.status, 
               COUNT(mm.id) as pending_messages
        FROM mailings m
        INNER JOIN mailing_messages mm ON m.id = mm.mailing_id
        WHERE m.status IN ('active', 'pending') 
        AND mm.status IN ('pending', 'failed')
        GROUP BY m.id, m.title, m.status
        ORDER BY m.id ASC
    ");

    if ($mailingsQuery->num_rows == 0) {
        logMessage("No active mailings with pending messages found");
        $db->query("UPDATE mailing_cron_control SET 
            is_running = 0, 
            last_end_time = NOW(),
            current_mailing_id = NULL,
            last_message_id = NULL
            WHERE id = {$control['id']}");
        exit("No mailings to process");
    }

    // Определяем с какой рассылки начинать
    $startFromMailingId = $control['current_mailing_id'];
    $startFromMessageId = $control['last_message_id'] ?? 0;

    $mailings = [];
    while ($mailing = $mailingsQuery->fetch_assoc()) {
        $mailings[] = $mailing;
    }

    // Находим индекс текущей рассылки
    $startIndex = 0;
    if ($startFromMailingId) {
        foreach ($mailings as $index => $mailing) {
            if ($mailing['id'] == $startFromMailingId) {
                $startIndex = $index;
                break;
            }
        }
    }

    // Обрабатываем рассылки начиная с текущей
    $processedMailings = 0;
    $totalMailings = count($mailings);

    for ($i = 0; $i < $totalMailings && $messagesSentThisRun < $maxMessagesPerMinute; $i++) {
        $currentIndex = ($startIndex + $i) % $totalMailings;
        $mailing = $mailings[$currentIndex];

        logMessage("Processing mailing ID: {$mailing['id']}, Title: {$mailing['title']}");

        // Проверяем статус рассылки перед обработкой
        $statusCheck = $db->query("SELECT status FROM mailings WHERE id = {$mailing['id']}");
        $currentStatus = $statusCheck->fetch_assoc();

        if (!$currentStatus || !in_array($currentStatus['status'], ['active', 'pending'])) {
            logMessage("Mailing {$mailing['id']} is no longer active, skipping");
            continue;
        }

        // Получаем неотправленные сообщения для текущей рассылки
        $messagesQuery = $db->query(
            "
            SELECT mm.*, u.phone as user_phone
            FROM mailing_messages mm
            LEFT JOIN users u ON mm.user_id = u.id
            WHERE mm.mailing_id = {$mailing['id']} 
            AND mm.status IN ('pending', 'failed')
            AND mm.id > $startFromMessageId
            ORDER BY mm.id ASC
            LIMIT " . ($maxMessagesPerMinute - $messagesSentThisRun)
        );

        if ($messagesQuery->num_rows == 0) {
            logMessage("No pending messages for mailing {$mailing['id']}");

            // Если это была текущая рассылка, переходим к следующей
            if ($mailing['id'] == $startFromMailingId) {
                $startFromMessageId = 0;
            }
            continue;
        }

        // Отправляем сообщения
        while ($message = $messagesQuery->fetch_assoc()) {
            if ($messagesSentThisRun >= $maxMessagesPerMinute) {
                break;
            }

            // Проверяем дубликаты (один номер - одно сообщение в рассылке)
            $duplicateCheck = $db->query("
                SELECT COUNT(*) as count 
                FROM mailing_messages 
                WHERE mailing_id = {$mailing['id']} 
                AND phone = '{$message['phone']}' 
                AND status = 'sent'
                AND id != {$message['id']}
            ");

            $duplicateCount = $duplicateCheck->fetch_assoc()['count'];
            if ($duplicateCount > 0) {
                logMessage("Duplicate message found for phone {$message['phone']} in mailing {$mailing['id']}, marking as failed");
                $db->query("UPDATE mailing_messages SET 
                    status = 'failed',
                    error_message = 'Duplicate message for this phone number',
                    last_attempt_at = NOW()
                    WHERE id = {$message['id']}");
                continue;
            }

            // Определяем номер для отправки
            $phoneToSend = $testMode ? $testPhone : $message['phone'];

            // Обновляем статус на "отправляется"
            $db->query("UPDATE mailing_messages SET 
                status = 'pending',
                attempts = attempts + 1,
                last_attempt_at = NOW()
                WHERE id = {$message['id']}");

            // Отправляем сообщение
            $sendResult = sendWhatsAppMessage($phoneToSend, $message['message_text']);

            if ($sendResult) {
                // Успешная отправка
                $db->query("UPDATE mailing_messages SET 
                    status = 'sent',
                    sent_at = NOW()
                    WHERE id = {$message['id']}");

                logMessage("Message sent successfully to {$phoneToSend} (original: {$message['phone']})");
                $messagesSentThisRun++;

                // Обновляем статистику рассылки
                $db->query("UPDATE mailings SET 
                    sent_count = sent_count + 1,
                    updated_at = NOW()
                    WHERE id = {$mailing['id']}");

            } else {
                // Ошибка отправки
                $errorMessage = "Failed to send WhatsApp message";
                $db->query("UPDATE mailing_messages SET 
                    status = 'failed',
                    error_message = '$errorMessage'
                    WHERE id = {$message['id']}");

                logMessage("Failed to send message to {$phoneToSend}: $errorMessage");

                // Обновляем статистику рассылки
                $db->query("UPDATE mailings SET 
                    failed_count = failed_count + 1,
                    updated_at = NOW()
                    WHERE id = {$mailing['id']}");
            }

            // Сохраняем прогресс
            $db->query("UPDATE mailing_cron_control SET 
                current_mailing_id = {$mailing['id']},
                last_message_id = {$message['id']}
                WHERE id = {$control['id']}");

            // Небольшая пауза между отправками (1 секунда)
            sleep(1);
        }

        // Проверяем, закончились ли сообщения в текущей рассылке
        $remainingCheck = $db->query("
            SELECT COUNT(*) as count 
            FROM mailing_messages 
            WHERE mailing_id = {$mailing['id']} 
            AND status IN ('pending', 'failed')
        ");

        $remainingMessages = $remainingCheck->fetch_assoc()['count'];

        if ($remainingMessages == 0) {
            // Рассылка завершена
            $db->query("UPDATE mailings SET 
                status = 'completed',
                end_time = NOW(),
                updated_at = NOW()
                WHERE id = {$mailing['id']}");

            logMessage("Mailing {$mailing['id']} completed");

            // Сбрасываем текущую рассылку
            if ($mailing['id'] == $startFromMailingId) {
                $startFromMessageId = 0;
                $db->query("UPDATE mailing_cron_control SET 
                    current_mailing_id = NULL,
                    last_message_id = NULL
                    WHERE id = {$control['id']}");
            }
        }

        $processedMailings++;
    }

    // Обновляем общую статистику
    if ($messagesSentThisRun > 0) {
        updateStats($control, $messagesSentThisRun, $db);
        logMessage("Sent $messagesSentThisRun messages in this run");
    }

    // Сбрасываем счетчик сообщений за день в полночь
    $today = date('Y-m-d');
    $lastUpdate = date('Y-m-d', strtotime($control['updated_at']));

    if ($today != $lastUpdate) {
        $db->query("UPDATE mailing_cron_control SET 
            messages_sent_today = 0
            WHERE id = {$control['id']}");
        logMessage("Daily message counter reset");
    }

} catch (Exception $e) {
    logMessage("Error in mailing process: " . $e->getMessage());

    // В случае ошибки сбрасываем статус запуска
    $db->query("UPDATE mailing_cron_control SET 
        is_running = 0,
        last_end_time = NOW()
        WHERE id = {$control['id']}");

    exit("Error: " . $e->getMessage());
}

// Завершаем работу
$db->query("UPDATE mailing_cron_control SET 
    is_running = 0,
    last_end_time = NOW()
    WHERE id = {$control['id']}");

logMessage("Mailing process completed. Messages sent: $messagesSentThisRun");

// Закрываем соединение с БД
$db->close();

echo json_encode([
    'type' => true,
    'message' => 'Mailing process completed',
    'messages_sent' => $messagesSentThisRun,
    'data' => [
        'messages_sent_this_run' => $messagesSentThisRun,
        'total_messages_sent' => $control['messages_sent_total'] + $messagesSentThisRun,
        'test_mode' => $testMode
    ]
]);
?>