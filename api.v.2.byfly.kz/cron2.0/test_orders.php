<?php
// Файл: /var/www/www-root/data/www/api.v.2.byfly.kz/cron/test_tours_status_updater.php

include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Функция получения информации о пользователе
function getUserInfo($userId)
{
    global $db;

    $stmt = $db->prepare("SELECT name, famale, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Функция получения информации о туре
function getTourInfo($tourId)
{
    global $db;

    $stmt = $db->prepare("
        SELECT 
            ot.id, ot.tourId, ot.tours_info, ot.user_id, ot.status_code,
            ot.price, ot.flyDate, ot.predoplata, ot.includesPrice
        FROM order_tours ot 
        WHERE ot.id = ?
    ");
    $stmt->bind_param("i", $tourId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tour = $result->fetch_assoc();
        if ($tour['tours_info']) {
            $tour['tours_info'] = json_decode($tour['tours_info'], true);
        }
        return $tour;
    }

    return null;
}

// Функция отправки уведомления пользователю
function sendStatusNotification($userId, $tourId, $newStatus, $tourInfo)
{
    $userInfo = getUserInfo($userId);

    if (!$userInfo || !$userInfo['phone']) {
        return;
    }

    $userName = trim($userInfo['name'] . ' ' . $userInfo['famale']);
    $hotelName = $tourInfo['tours_info']['hotelname'] ?? "Тур #" . $tourInfo['tourId'];
    $flyDate = $tourInfo['flyDate'] ? date('d.m.Y', strtotime($tourInfo['flyDate'])) : 'не указана';
    $price = number_format($tourInfo['price'], 0, '.', ' ');

    $statusMessages = [
        1 => [
            'title' => '💳 Требуется предоплата',
            'description' => "Для подтверждения бронирования необходимо внести предоплату в размере " . number_format($tourInfo['predoplata'], 0, '.', ' ') . " ₸",
            'action' => 'Внесите предоплату в личном кабинете для подтверждения брони.'
        ],
        2 => [
            'title' => '💰 Требуется полная оплата',
            'description' => "Предоплата получена! Теперь необходимо доплатить оставшуюся сумму: " . number_format($tourInfo['price'] - $tourInfo['predoplata'], 0, '.', ' ') . " ₸",
            'action' => 'Доплатите оставшуюся сумму для окончательного подтверждения тура.'
        ],
        3 => [
            'title' => '✅ Тур полностью оплачен',
            'description' => "Поздравляем! Ваш тур полностью оплачен. Ожидайте вылета $flyDate",
            'action' => 'Готовьтесь к путешествию! Документы будут направлены дополнительно.'
        ],
        4 => [
            'title' => '🏖️ Приятного отдыха!',
            'description' => "Вы уже должны быть на отдыхе! Наслаждайтесь путешествием",
            'action' => 'Не забудьте поделиться фото и видео из поездки для получения бонусов!'
        ],
        5 => [
            'title' => '❌ Тур отменен',
            'description' => "К сожалению, ваш тур был отменен",
            'action' => 'Обратитесь к менеджеру для уточнения деталей возврата средств.'
        ]
    ];

    $statusInfo = $statusMessages[$newStatus] ?? [
        'title' => '📋 Статус тура изменен',
        'description' => 'Статус вашего тура был обновлен',
        'action' => 'Проверьте актуальную информацию в приложении.'
    ];

    $message = "🔔 *ТЕСТОВОЕ УВЕДОМЛЕНИЕ*\n\n";
    $message .= "Здравствуйте, $userName!\n\n";
    $message .= "*{$statusInfo['title']}*\n\n";
    $message .= "📍 *Направление:* $hotelName\n";
    $message .= "🗓️ *Дата вылета:* $flyDate\n";
    $message .= "💵 *Стоимость:* $price ₸\n";
    $message .= "🆔 *Номер заявки:* {$tourInfo['tourId']}\n\n";
    $message .= "📝 *Детали:*\n{$statusInfo['description']}\n\n";
    $message .= "⚡ *Что делать:*\n{$statusInfo['action']}\n\n";
    $message .= "---\n";
    $message .= "⚠️ *Это тестовое сообщение* для демонстрации работы системы уведомлений ByFly Travel\n\n";
    $message .= "📱 Подробности в приложении ByFly Travel";

    sendWhatsapp($userInfo['phone'], $message);
}

// Функция отправки уведомления об удалении
function sendDeletionNotification($userId, $tourInfo)
{
    $userInfo = getUserInfo($userId);

    if (!$userInfo || !$userInfo['phone']) {
        return;
    }

    $userName = trim($userInfo['name'] . ' ' . $userInfo['famale']);
    $hotelName = $tourInfo['tours_info']['hotelname'] ?? "Тур #" . $tourInfo['tourId'];
    $price = number_format($tourInfo['price'], 0, '.', ' ');

    $message = "🔔 *ТЕСТОВОЕ УВЕДОМЛЕНИЕ*\n\n";
    $message .= "Здравствуйте, $userName!\n\n";
    $message .= "*🗑️ Тестовая заявка удалена*\n\n";
    $message .= "📍 *Направление:* $hotelName\n";
    $message .= "💵 *Стоимость:* $price ₸\n";
    $message .= "🆔 *Номер заявки:* {$tourInfo['tourId']}\n\n";
    $message .= "📝 *Детали:*\nВаша тестовая заявка прошла полный цикл обработки и была автоматически удалена из системы.\n\n";
    $message .= "✨ *Что это значит:*\nТестирование системы уведомлений завершено успешно! Теперь вы знаете, как работают уведомления в ByFly Travel.\n\n";
    $message .= "---\n";
    $message .= "⚠️ *Это было тестовое сообщение* для демонстрации работы системы уведомлений ByFly Travel\n\n";
    $message .= "🎯 Теперь вы можете оформлять реальные туры!\n";
    $message .= "📱 Переходите в приложение ByFly Travel";

    sendWhatsapp($userInfo['phone'], $message);
}

// Основная логика крона
try {
    // Получаем все тестовые заявки
    $query = "
        SELECT 
            ot.id, ot.tourId, ot.user_id, ot.status_code, ot.tours_info,
            ot.price, ot.flyDate, ot.predoplata, ot.includesPrice,
            ot.date_create
        FROM order_tours ot 
        WHERE ot.type = 'test' 
        ORDER BY ot.date_create ASC
    ";

    $result = $db->query($query);

    if ($result) {
        while ($tour = $result->fetch_assoc()) {
            try {
                $db->query("UPDATE order_tours SET dateOffPay = '" . date('Y-m-d H:i:s', strtotime('+1 day')) . "' WHERE id='" . $tour['id'] . "'");
                $tourId = $tour['id'];
                $currentStatus = (int) $tour['status_code'];
                $userId = (int) $tour['user_id'];

                // Декодируем информацию о туре
                if ($tour['tours_info']) {
                    $tour['tours_info'] = json_decode($tour['tours_info'], true);
                }

                // Определяем следующий статус или удаляем
                if ($currentStatus >= 5) {
                    // Статус 5 (отменен) - удаляем заявку

                    // Отправляем уведомление об удалении
                    if ($userId > 0) {
                        sendDeletionNotification($userId, $tour);
                    }

                    // Удаляем заявку
                    $deleteStmt = $db->prepare("DELETE FROM order_tours WHERE id = ?");
                    $deleteStmt->bind_param("i", $tourId);
                    $deleteStmt->execute();

                } else {
                    // Повышаем статус на 1
                    $newStatus = $currentStatus + 1;

                    // Обновляем статус в базе данных
                    $updateStmt = $db->prepare("
                        UPDATE order_tours 
                        SET status_code = ?, 
                            includesPrice = CASE 
                                WHEN ? = 2 THEN predoplata 
                                WHEN ? = 3 THEN price 
                                ELSE includesPrice 
                            END
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param("iiii", $newStatus, $newStatus, $newStatus, $tourId);

                    if ($updateStmt->execute()) {
                        // Отправляем уведомление пользователю
                        if ($userId > 0) {
                            // Получаем обновленную информацию о туре
                            $updatedTour = getTourInfo($tourId);
                            if ($updatedTour) {
                                sendStatusNotification($userId, $tourId, $newStatus, $updatedTour);
                            }
                        }
                    }
                }

            } catch (Exception $e) {
                // Игнорируем ошибки отдельных туров
                continue;
            }
        }
    }

} catch (Exception $e) {
    // Игнорируем критические ошибки
}

// Закрываем соединение с базой данных
if (isset($db)) {
    $db->close();
}

?>