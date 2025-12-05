<?php

function formatPrice($price)
{
    return number_format($price, 0, '.', ' ') . " ₸";
}

function getOrderDetailsForCancellation($db, $orderId)
{
    // Получаем основную информацию о заказе
    $orderResult = $db->query("SELECT * FROM order_tours WHERE id='$orderId'");
    if (!$orderResult || $orderResult->num_rows == 0) {
        return null;
    }

    $order = $orderResult->fetch_assoc();

    // Получаем информацию о пользователе
    $userResult = $db->query("SELECT * FROM users WHERE id='" . $order['user_id'] . "'");
    $user = $userResult ? $userResult->fetch_assoc() : null;

    // Получаем информацию о менеджере
    $manager = null;
    if ($order['manager_id'] > 0) {
        $managerResult = $db->query("SELECT * FROM managers WHERE id='" . $order['manager_id'] . "'");
        $manager = $managerResult ? $managerResult->fetch_assoc() : null;
    }

    // Парсим информацию о туре
    $tourInfo = json_decode($order['tours_info'], true);

    return [
        'order' => $order,
        'user' => $user,
        'manager' => $manager,
        'tour_info' => $tourInfo
    ];
}

function sendCancellationNotification($managerInfo, $orderDetails, $cancelReason = null)
{
    $order = $orderDetails['order'];
    $user = $orderDetails['user'];
    $tourInfo = $orderDetails['tour_info'];

    // Формируем сообщение об отмене
    $message = "❌ *ЗАЯВКА ОТМЕНЕНА* ❌\n\n";

    // Информация о заявке
    $message .= "📋 *ИНФОРМАЦИЯ ОБ ОТМЕНЕННОЙ ЗАЯВКЕ:*\n";
    $message .= "🆔 ID заявки: *" . $order['id'] . "*\n";
    $message .= "📅 Дата создания: " . date('d.m.Y H:i', strtotime($order['date_create'])) . "\n";
    $message .= "🗓️ Дата отмены: " . date('d.m.Y H:i') . "\n";
    $message .= "🏷️ Тип заявки: *" . ($order['type'] === 'spec' ? 'СПЕЦ ПРЕДЛОЖЕНИЕ' : ($order['type'] === 'test' ? 'ТЕСТОВАЯ ЗАЯВКА' : 'ОБЫЧНЫЙ ТУР')) . "*\n";
    $message .= "💰 Стоимость: *" . formatPrice($order['price']) . "*\n";

    // Статус на момент отмены
    $statusText = [
        0 => 'Новая (в обработке)',
        1 => 'Подтверждена, ожидала предоплату',
        2 => 'Подтверждена, ожидала полную оплату',
        3 => 'Полностью оплачена, ожидала вылета',
        4 => 'Турист был на отдыхе',
        5 => 'Уже была отменена'
    ];
    $message .= "📊 Статус на момент отмены: *" . ($statusText[$order['status_code']] ?? 'Неизвестный') . "*\n\n";

    // Информация о клиенте
    if ($user) {
        $message .= "👤 *ИНФОРМАЦИЯ О КЛИЕНТЕ:*\n";
        $message .= "👨‍💼 ФИО: " . $user['famale'] . " " . $user['name'] . " " . $user['surname'] . "\n";
        $message .= "📱 Телефон: *" . $user['phone'] . "*\n\n";
    }

    // Краткая информация о туре
    if ($tourInfo && is_array($tourInfo)) {
        $message .= "🏖️ *ИНФОРМАЦИЯ О ТУРЕ:*\n";

        if (isset($tourInfo['hotelname'])) {
            $message .= "🏨 Отель: " . $tourInfo['hotelname'] . "\n";
        }

        if (isset($tourInfo['countryname']) && isset($tourInfo['regionname'])) {
            $message .= "🌍 Направление: " . $tourInfo['countryname'] . ", " . $tourInfo['regionname'] . "\n";
        }

        if (isset($tourInfo['flydate'])) {
            $message .= "✈️ Дата вылета: " . date('d.m.Y', strtotime($tourInfo['flydate'])) . "\n";
        }

        if (isset($tourInfo['nights'])) {
            $message .= "🌙 Ночей: " . $tourInfo['nights'] . "\n";
        }

        $message .= "\n";
    }

    // Причина отмены (если указана)
    if (!empty($cancelReason)) {
        $message .= "📝 *ПРИЧИНА ОТМЕНЫ:*\n";
        $message .= $cancelReason . "\n\n";
    }

    // Информация об оплатах (если были)
    if ($order['includesPrice'] > 0) {
        $message .= "💳 *ИНФОРМАЦИЯ ОБ ОПЛАТАХ:*\n";
        $message .= "💰 Было оплачено: " . formatPrice($order['includesPrice']) . "\n";

        if ($order['bonusPay'] > 0) {
            $message .= "🎁 Из них бонусами: " . formatPrice($order['bonusPay']) . "\n";
        }

        $message .= "⚠️ *Требуется обработка возврата средств!*\n\n";
    }

    // Дополнительная информация
    $message .= "ℹ️ *ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ:*\n";
    $message .= "• Заявка полностью удалена из системы\n";
    $message .= "• Все связанные данные очищены\n";

    if ($order['includesPrice'] > 0) {
        $message .= "• Необходимо оформить возврат средств клиенту\n";
    }

    $message .= "\n";

    // Действия менеджера
    if ($order['includesPrice'] > 0) {
        $message .= "📋 *НЕОБХОДИМЫЕ ДЕЙСТВИЯ:*\n";
        $message .= "1️⃣ Связаться с клиентом для уточнения реквизитов возврата\n";
        $message .= "2️⃣ Оформить документы на возврат средств\n";
        $message .= "3️⃣ Уведомить бухгалтерию о необходимости возврата\n\n";
    }

    // Контакты для связи
    $message .= "📞 *КОНТАКТЫ ДЛЯ СВЯЗИ:*\n";
    $message .= "🔗 Система управления: https://manager.byfly.kz/2.0/\n";

    $message .= "🙏 *Спасибо за вашу работу!*\n";
    $message .= "_Система уведомлений ByFly Travel_";

    // Отправляем сообщение
    sendWhatsapp($managerInfo['phone_whatsapp'], $message);
}

try {
    if (empty($_POST['orderId']) == false) {
        $orderId = intval($_POST['orderId']);

        // Получаем информацию о заявке перед удалением
        $orderDetails = getOrderDetailsForCancellation($db, $orderId);

        if (!$orderDetails) {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Заявка с ID ' . $orderId . ' не найдена',
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        $order = $orderDetails['order'];
        $manager = $orderDetails['manager'];

        // Логируем отмену заявки
        $logMessage = "Отмена заявки ID:" . $orderId .
            " | Клиент: " . ($orderDetails['user'] ? $orderDetails['user']['phone'] : 'неизвестен') .
            " | Менеджер: " . ($manager ? $manager['fio'] . " (ID:" . $manager['id'] . ")" : 'не назначен') .
            " | Статус: " . $order['status_code'] .
            " | Сумма: " . $order['price'] . " тенге" .
            " | Оплачено: " . $order['includesPrice'] . " тенге";

        error_log($logMessage);

        // Начинаем транзакцию для безопасного удаления
        $db->autocommit(false);

        try {
            // Удаляем связанные данные
            $sql1 = "DELETE FROM order_tours WHERE id='" . $orderId . "'";
            $sql2 = "DELETE FROM order_passangers WHERE order_id='" . $orderId . "'";
            $sql3 = "DELETE FROM order_dop_pays WHERE order_id='" . $orderId . "'";
            $sql4 = "DELETE FROM order_pays WHERE order_id='" . $orderId . "'";
            $sql5 = "DELETE FROM order_docs WHERE order_id='" . $orderId . "'";
            $sql6 = "DELETE FROM order_media WHERE order_id='" . $orderId . "'";
            $sql7 = "DELETE FROM order_vozvrat WHERE order_id='" . $orderId . "'";
            $sql8 = "DELETE FROM order_tour_operators WHERE order_id='" . $orderId . "'";

            // Выполняем все запросы
            $success = true;
            $success &= $db->query($sql1);
            $success &= $db->query($sql2);
            $success &= $db->query($sql3);
            $success &= $db->query($sql4);
            $success &= $db->query($sql5);
            $success &= $db->query($sql6);
            $success &= $db->query($sql7);
            $success &= $db->query($sql8);

            if ($success) {
                // Подтверждаем транзакцию
                $db->commit();

                // Отправляем уведомление менеджеру (если он назначен)
                if ($manager && !empty($manager['phone_whatsapp'])) {
                    try {
                        $cancelReason = isset($_POST['cancel_reason']) ? $_POST['cancel_reason'] : null;
                        sendCancellationNotification($manager, $orderDetails, $cancelReason);

                        error_log("Отправлено уведомление об отмене заявки ID:" . $orderId . " менеджеру " . $manager['fio']);
                    } catch (\Throwable $notificationError) {
                        error_log("Ошибка отправки уведомления об отмене: " . $notificationError->getMessage());
                    }
                }

                // Если была оплата, уведомляем администратора о необходимости возврата
                if ($order['includesPrice'] > 0) {
                    $adminMessage = "💰 *ТРЕБУЕТСЯ ВОЗВРАТ СРЕДСТВ!*\n\n";
                    $adminMessage .= "🆔 Отменена заявка ID: " . $orderId . "\n";
                    $adminMessage .= "👤 Клиент: " . ($orderDetails['user'] ? $orderDetails['user']['famale'] . " " . $orderDetails['user']['name'] : 'неизвестен') . "\n";
                    $adminMessage .= "📱 Телефон: " . ($orderDetails['user'] ? $orderDetails['user']['phone'] : 'неизвестен') . "\n";
                    $adminMessage .= "💰 Сумма к возврату: " . formatPrice($order['includesPrice']) . "\n";

                    if ($order['bonusPay'] > 0) {
                        $adminMessage .= "🎁 Из них бонусами: " . formatPrice($order['bonusPay']) . "\n";
                        $adminMessage .= "💵 К возврату деньгами: " . formatPrice($order['includesPrice'] - $order['bonusPay']) . "\n";
                    }

                    $adminMessage .= "\n⚠️ Требуется оформление возврата!";

                    adminNotification($adminMessage);
                }

                // Если это спец предложение, возвращаем места
                if ($order['type'] === 'spec' && !empty($order['tourId'])) {
                    try {
                        $db->query("UPDATE spec_tours SET sales_place = sales_place - 2 WHERE tour_id='" . $order['tourId'] . "' AND sales_place >= 2");
                        error_log("Возвращены места для спец тура ID:" . $order['tourId']);
                    } catch (\Throwable $specError) {
                        error_log("Ошибка возврата мест для спец тура: " . $specError->getMessage());
                    }
                }

                echo json_encode(
                    array(
                        "type" => true,
                        "data" => array(
                            "deleted_order_id" => $orderId,
                            "had_payments" => $order['includesPrice'] > 0,
                            "refund_amount" => $order['includesPrice'],
                            "manager_notified" => $manager ? true : false
                        ),
                    ),
                    JSON_UNESCAPED_UNICODE
                );

            } else {
                // Откатываем транзакцию
                $db->rollback();

                echo json_encode(
                    array(
                        "type" => false,
                        "msg" => 'Ошибка при удалении данных заявки: ' . $db->error,
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            }

        } catch (\Throwable $dbError) {
            // Откатываем транзакцию при ошибке
            $db->rollback();
            throw $dbError;
        } finally {
            // Восстанавливаем автокоммит
            $db->autocommit(true);
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Не указан ID заявки для удаления',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} catch (\Throwable $th) {
    // Восстанавливаем автокоммит в случае ошибки
    if (isset($db)) {
        $db->autocommit(true);
    }

    error_log("Критическая ошибка при отмене заявки: " . $th->getMessage());

    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Произошла ошибка при отмене заявки: ' . $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>