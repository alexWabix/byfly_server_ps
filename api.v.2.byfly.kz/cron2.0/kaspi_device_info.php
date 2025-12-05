<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Номер для уведомлений
$alertPhone = '77780021666';

// Функция быстрой проверки терминала
function checkTerminal($port, $timeout = 3)
{
    $url = "http://109.175.215.40:$port/v2/status?processId=healthcheck";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ByFly-Terminal-Monitor/1.0');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($response !== false && $httpCode == 200);
}

// Функция проверки камеры через снимок
function checkCameraAndUpdatePhoto($cameraId, $terminalId, $timeout = 8)
{
    global $db, $domain;

    $url = "http://109.175.215.40:3000/capture/$cameraId";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ByFly-Camera-Monitor/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: image/jpeg, image/png, application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    // Проверяем получили ли изображение
    if ($response !== false && $httpCode == 200 && strpos($contentType, 'image/') !== false) {
        // Получаем старое фото для удаления
        $oldPhotoSql = "SELECT last_photo_url FROM kaspi_terminals WHERE id = ?";
        $stmt = $db->prepare($oldPhotoSql);
        $stmt->bind_param('i', $terminalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $oldPhoto = $result->fetch_assoc();

        // Удаляем старое фото с сервера
        if ($oldPhoto && !empty($oldPhoto['last_photo_url'])) {
            $oldFilePath = str_replace($domain, '/var/www/www-root/data/www/api.v.2.byfly.kz/', $oldPhoto['last_photo_url']);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        // Создаем папку для фото терминалов если не существует
        $photoDir = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/terminal_photos/';
        if (!is_dir($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        // Генерируем имя файла
        $fileName = 'terminal_' . $terminalId . '_camera_' . $cameraId . '_' . date('Y-m-d_H-i-s') . '.jpg';
        $filePath = $photoDir . $fileName;
        $fileUrl = $domain . 'images/terminal_photos/' . $fileName;

        // Сохраняем новое фото
        if (file_put_contents($filePath, $response)) {
            // Обновляем ссылку в БД
            $updateSql = "UPDATE kaspi_terminals 
                         SET last_photo_url = ?,
                             last_health_check = NOW()
                         WHERE id = ?";

            $stmt = $db->prepare($updateSql);
            $stmt->bind_param('si', $fileUrl, $terminalId);
            $stmt->execute();

            return true;
        }
    }

    return false;
}

// Функция проверки активных транзакций
function hasActiveTransactions($terminalId)
{
    global $db;

    $sql = "SELECT COUNT(*) as active_count 
            FROM kaspi_transactions 
            WHERE terminal_id = ? 
            AND status IN ('pending', 'processing') 
            AND date_initiated > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $terminalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return ($row['active_count'] > 0);
}

try {
    // Получаем все терминалы
    $sql = "SELECT id, port, camera_id, terminal_name, is_active
            FROM kaspi_terminals 
            ORDER BY port";

    $result = $db->query($sql);

    if (!$result) {
        sendWhatsapp($alertPhone, "🚨 ОШИБКА: Не удалось получить список терминалов Kaspi\n\nВремя: " . date('H:i d.m.Y'));
        exit;
    }

    $problemTerminals = [];
    $checkedCount = 0;
    $skippedBusy = 0;
    $totalTerminals = 0;

    while ($terminal = $result->fetch_assoc()) {
        $totalTerminals++;
        $terminalId = $terminal['id'];
        $port = $terminal['port'];
        $cameraId = $terminal['camera_id'];
        $terminalName = $terminal['terminal_name'] ?: "Терминал $port";
        $isActive = $terminal['is_active'];

        // Пропускаем занятые терминалы
        if (hasActiveTransactions($terminalId)) {
            $skippedBusy++;
            continue;
        }

        $checkedCount++;
        $hasProblems = false;
        $problems = [];

        // 1. Проверяем доступность терминала
        if (!checkTerminal($port, 3)) {
            $hasProblems = true;
            $problems[] = "терминал недоступен";
        }

        // 2. Проверяем камеру (только если терминал доступен)
        if (!$hasProblems) {
            if (!checkCameraAndUpdatePhoto($cameraId, $terminalId, 8)) {
                $hasProblems = true;
                $problems[] = "камера $cameraId не отвечает";
            }
        }

        // Обновляем статус в БД
        if ($hasProblems) {
            $problemTerminals[] = [
                'id' => $terminalId,
                'name' => $terminalName,
                'port' => $port,
                'camera' => $cameraId,
                'problems' => $problems,
                'is_active' => $isActive,
                'type' => $isActive ? "активный" : "отключенный"
            ];

            $errorMsg = implode(', ', $problems);
            $updateSql = "UPDATE kaspi_terminals 
                         SET status = 'offline', 
                             last_error_message = ?, 
                             error_count = error_count + 1,
                             last_health_check = NOW()
                         WHERE id = ?";

            $stmt = $db->prepare($updateSql);
            $stmt->bind_param('si', $errorMsg, $terminalId);
            $stmt->execute();
        } else {
            // Все в порядке - сбрасываем ошибки
            $newStatus = $isActive ? 'free' : 'offline';
            $updateSql = "UPDATE kaspi_terminals 
                         SET status = ?,
                             error_count = 0,
                             last_error_message = NULL,
                             last_health_check = NOW()
                         WHERE id = ?";

            $stmt = $db->prepare($updateSql);
            $stmt->bind_param('si', $newStatus, $terminalId);
            $stmt->execute();
        }

        // Пауза между проверками
        usleep(500000); // 0.5 секунды
    }

    // Отправляем уведомления только если есть проблемы
    if (!empty($problemTerminals)) {
        $activeProblems = 0;
        $inactiveProblems = 0;

        $message = "🚨 ПРОБЛЕМЫ С ТЕРМИНАЛАМИ KASPI\n\n";
        $message .= "Время проверки: " . date('H:i d.m.Y') . "\n";
        $message .= "Всего терминалов: $totalTerminals\n";
        $message .= "Проверено: $checkedCount\n";
        $message .= "Пропущено (заняты): $skippedBusy\n\n";

        foreach ($problemTerminals as $terminal) {
            if ($terminal['is_active']) {
                $activeProblems++;
            } else {
                $inactiveProblems++;
            }

            $statusIcon = $terminal['is_active'] ? "❌" : "⚠️";
            $message .= "$statusIcon {$terminal['name']} ({$terminal['type']})\n";
            $message .= "Порт: {$terminal['port']}, Камера: {$terminal['camera']}\n";
            $message .= "Проблемы: " . implode(', ', $terminal['problems']) . "\n\n";
        }

        if ($activeProblems > 0) {
            $message .= "🔴 Активных терминалов с проблемами: $activeProblems\n";
        }
        if ($inactiveProblems > 0) {
            $message .= "🟡 Отключенных терминалов с проблемами: $inactiveProblems\n";
        }

        $message .= "\nТребуется проверка оборудования!";

        // Отправляем уведомление
        sendWhatsapp($alertPhone, $message);

        // Создаем уведомление для админа
        adminNotification("Обнаружены проблемы с " . count($problemTerminals) . " терминалами Kaspi (активных: $activeProblems, отключенных: $inactiveProblems)");
    }

    // Проверяем критическую ситуацию
    $activeTerminalsChecked = 0;
    $activeTerminalsWithProblems = 0;

    foreach ($problemTerminals as $terminal) {
        if ($terminal['is_active']) {
            $activeTerminalsWithProblems++;
        }
    }

    // Подсчитываем активные терминалы которые были проверены
    $countActiveSql = "SELECT COUNT(*) as active_checked 
                       FROM kaspi_terminals 
                       WHERE is_active = 1 
                       AND id NOT IN (
                           SELECT DISTINCT terminal_id 
                           FROM kaspi_transactions 
                           WHERE status IN ('pending', 'processing') 
                           AND date_initiated > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                           AND terminal_id IS NOT NULL
                       )";

    $activeResult = $db->query($countActiveSql);
    if ($activeRow = $activeResult->fetch_assoc()) {
        $activeTerminalsChecked = $activeRow['active_checked'];
    }

    if ($activeTerminalsChecked > 0 && $activeTerminalsWithProblems == $activeTerminalsChecked) {
        $criticalMessage = "🔴 КРИТИЧЕСКАЯ СИТУАЦИЯ!\n\n";
        $criticalMessage .= "ВСЕ активные терминалы Kaspi недоступны!\n";
        $criticalMessage .= "Проверено активных: $activeTerminalsChecked\n";
        $criticalMessage .= "С проблемами: $activeTerminalsWithProblems\n";
        $criticalMessage .= "Пропущено (заняты): $skippedBusy\n";
        $criticalMessage .= "Время: " . date('H:i d.m.Y') . "\n\n";
        $criticalMessage .= "СРОЧНО требуется вмешательство технического специалиста!";

        sendWhatsapp($alertPhone, $criticalMessage);
    }

} catch (Exception $e) {
    $errorMessage = "💥 ОШИБКА МОНИТОРИНГА ТЕРМИНАЛОВ\n\n";
    $errorMessage .= "Время: " . date('H:i d.m.Y') . "\n";
    $errorMessage .= "Ошибка: " . $e->getMessage() . "\n\n";
    $errorMessage .= "Система мониторинга терминалов не работает!";

    sendWhatsapp($alertPhone, $errorMessage);
}
?>