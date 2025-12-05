<?php

function formatPrice($price)
{
    return number_format($price, 0, '.', ' ') . " ₸";
}

function getPaymentOrderDetails($db, $orderId)
{
    // Получаем информацию о заказе
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

    // Получаем все платежи по заказу
    $paymentsResult = $db->query("SELECT * FROM order_pays WHERE order_id='$orderId' ORDER BY date_create DESC");
    $payments = [];
    if ($paymentsResult) {
        while ($payment = $paymentsResult->fetch_assoc()) {
            $payments[] = $payment;
        }
    }

    // Получаем дополнительные платежи
    $dopPaymentsResult = $db->query("SELECT SUM(summ) as total FROM order_dop_pays WHERE order_id='$orderId'");
    $dopPayments = $dopPaymentsResult ? $dopPaymentsResult->fetch_assoc()['total'] : 0;

    return [
        'order' => $order,
        'user' => $user,
        'manager' => $manager,
        'tour_info' => $tourInfo,
        'payments' => $payments,
        'dop_payments' => $dopPayments ?? 0
    ];
}

function getStatusText($statusCode)
{
    $statuses = [
        0 => 'Новая (в обработке)',
        1 => 'Подтверждена, ожидает предоплату',
        2 => 'Подтверждена, ожидает полную оплату',
        3 => 'Полностью оплачена, ожидает вылета',
        4 => 'Турист на отдыхе',
        5 => 'Заявка отменена'
    ];

    return $statuses[$statusCode] ?? 'Неизвестный статус';
}

function getPaymentTypeText($paymentType)
{
    $types = [
        'order_in' => 'Поступление средств',
        'balance' => 'Оплата балансом',
        'bonus' => 'Оплата бонусами',
        'kaspi' => 'Оплата через Kaspi',
        'nalichnie' => 'Оплата наличными',
        'bank_transfer' => 'Банковский перевод'
    ];

    return $types[$paymentType] ?? 'Неизвестный тип оплаты';
}

function sendPaymentNotificationToManager($managerInfo, $orderDetails, $newPayment, $newStatus, $oldStatus)
{
    $order = $orderDetails['order'];
    $user = $orderDetails['user'];
    $tourInfo = $orderDetails['tour_info'];
    $payments = $orderDetails['payments'];
    $dopPayments = $orderDetails['dop_payments'];

    // Рассчитываем суммы
    $totalOrderPrice = $order['price'] + $dopPayments;
    $totalPaid = $order['includesPrice'];
    $remainingAmount = $totalOrderPrice - $totalPaid;

    // Определяем тип уведомления
    $isFullPayment = ($newStatus == 3);
    $isFirstPayment = (count($payments) == 1);

    // Формируем сообщение
    if ($isFullPayment) {
        $message = "💰 *ЗАЯВКА ПОЛНОСТЬЮ ОПЛАЧЕНА!* 💰\n\n";
    } elseif ($isFirstPayment) {
        $message = "💳 *ПОСТУПИЛА ПЕРВАЯ ОПЛАТА!* 💳\n\n";
    } else {
        $message = "💵 *ПОСТУПИЛА ДОПЛАТА!* 💵\n\n";
    }

    // Информация о заявке
    $message .= "📋 *ИНФОРМАЦИЯ О ЗАЯВКЕ:*\n";
    $message .= "🆔 ID заявки: *" . $order['id'] . "*\n";
    $message .= "📅 Дата создания: " . date('d.m.Y H:i', strtotime($order['date_create'])) . "\n";
    $message .= "🏷️ Тип заявки: *" . ($order['type'] === 'spec' ? 'СПЕЦ ПРЕДЛОЖЕНИЕ' : ($order['type'] === 'test' ? 'ТЕСТОВАЯ ЗАЯВКА' : 'ОБЫЧНЫЙ ТУР')) . "*\n\n";

    // Информация о клиенте
    if ($user) {
        $message .= "👤 *ИНФОРМАЦИЯ О КЛИЕНТЕ:*\n";
        $message .= "👨‍💼 ФИО: " . $user['famale'] . " " . $user['name'] . " " . $user['surname'] . "\n";
        $message .= "📱 Телефон: *" . $user['phone'] . "*\n";

        $userStatus = [
            'user' => 'Пользователь',
            'agent' => 'Агент',
            'coach' => 'Коуч',
            'alpha' => 'Альфа',
            'ambasador' => 'Амбассадор'
        ];
        $message .= "🏆 Статус: " . ($userStatus[$user['user_status']] ?? 'Неизвестный') . "\n\n";
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
            $flyDate = date('d.m.Y', strtotime($tourInfo['flydate']));
            $daysToFly = ceil((strtotime($tourInfo['flydate']) - time()) / (24 * 60 * 60));
            $message .= "✈️ Дата вылета: " . $flyDate;

            if ($daysToFly > 0) {
                $message .= " (через " . $daysToFly . " дн.)";
            } elseif ($daysToFly == 0) {
                $message .= " (СЕГОДНЯ!)";
            } else {
                $message .= " (ПРОСРОЧЕН!)";
            }
            $message .= "\n";
        }

        if (isset($tourInfo['nights'])) {
            $message .= "🌙 Ночей: " . $tourInfo['nights'] . "\n";
        }

        $message .= "\n";
    }

    // Информация о текущем платеже
    $message .= "💰 *ИНФОРМАЦИЯ О ПЛАТЕЖЕ:*\n";
    $message .= "💵 Сумма платежа: *" . formatPrice($newPayment['summ']) . "*\n";
    $message .= "🏷️ Тип оплаты: " . getPaymentTypeText($newPayment['type']) . "\n";
    $message .= "🕐 Время поступления: " . date('d.m.Y H:i:s') . "\n";

    if (!empty($newPayment['tranzaction_id'])) {
        $message .= "🔗 ID транзакции: " . $newPayment['tranzaction_id'] . "\n";
    }

    $message .= "\n";

    // Общая информация об оплатах
    $message .= "📊 *СТАТУС ОПЛАТЫ:*\n";
    $message .= "💰 Стоимость тура: " . formatPrice($order['price']) . "\n";

    if ($dopPayments > 0) {
        $message .= "➕ Доп. платежи: " . formatPrice($dopPayments) . "\n";
        $message .= "💯 Итого к оплате: " . formatPrice($totalOrderPrice) . "\n";
    }

    $message .= "✅ Всего оплачено: *" . formatPrice($totalPaid) . "*\n";

    if ($remainingAmount > 0) {
        $message .= "⏳ Осталось доплатить: *" . formatPrice($remainingAmount) . "*\n";
    } else {
        $message .= "🎉 *ОПЛАЧЕНО ПОЛНОСТЬЮ!*\n";
    }

    // Изменение статуса
    if ($oldStatus != $newStatus) {
        $message .= "\n📈 *ИЗМЕНЕНИЕ СТАТУСА:*\n";
        $message .= "📤 Было: " . getStatusText($oldStatus) . "\n";
        $message .= "📥 Стало: *" . getStatusText($newStatus) . "*\n";
    }

    $message .= "\n";

    // Необходимые действия
    if ($isFullPayment) {
        $message .= "🎯 *НЕОБХОДИМЫЕ ДЕЙСТВИЯ:*\n";
        $message .= "1️⃣ Подтвердить бронирование у туроператора\n";
        $message .= "2️⃣ Получить ваучеры и документы\n";
        $message .= "3️⃣ Отправить документы клиенту\n";
        $message .= "4️⃣ Напомнить о сборах за 2-3 дня до вылета\n\n";

        // Срочность для близких дат
        if (isset($tourInfo['flydate'])) {
            $daysToFly = ceil((strtotime($tourInfo['flydate']) - time()) / (24 * 60 * 60));
            if ($daysToFly <= 7) {
                $message .= "🔥 *СРОЧНО! ВЫЛЕТ ЧЕРЕЗ " . $daysToFly . " ДНЕЙ!*\n";
                $message .= "⚡ Требуется немедленная обработка!\n\n";
            }
        }
    } elseif ($remainingAmount > 0) {
        $message .= "📋 *НЕОБХОДИМЫЕ ДЕЙСТВИЯ:*\n";
        $message .= "1️⃣ Связаться с клиентом\n";
        $message .= "2️⃣ Напомнить о доплате " . formatPrice($remainingAmount) . "\n";
        $message .= "3️⃣ Отправить реквизиты для доплаты\n\n";
    }

    // История всех платежей (если их больше одного)
    if (count($payments) > 1) {
        $message .= "📜 *ИСТОРИЯ ПЛАТЕЖЕЙ:*\n";
        foreach (array_reverse($payments) as $index => $payment) {
            $message .= ($index + 1) . ". " . formatPrice($payment['summ']) . " - " . date('d.m.Y H:i', strtotime($payment['date_create'])) . "\n";
        }
        $message .= "\n";
    }

    // Ссылка на систему
    $message .= "🔗 *ПЕРЕЙТИ К ОБРАБОТКЕ:*\n";
    $message .= "👉 https://manager.byfly.kz/\n\n";

    $message .= "🙏 Спасибо за вашу работу!\n";
    $message .= "_Система уведомлений ByFly Travel_";

    // Отправляем сообщение
    sendWhatsapp($managerInfo['phone_whatsapp'], $message);
}

function sendPaymentNotificationToClient($userInfo, $orderDetails, $newPayment, $newStatus)
{
    $order = $orderDetails['order'];
    $tourInfo = $orderDetails['tour_info'];
    $dopPayments = $orderDetails['dop_payments'];

    $totalOrderPrice = $order['price'] + $dopPayments;
    $totalPaid = $order['includesPrice'];
    $remainingAmount = $totalOrderPrice - $totalPaid;

    $message = "✅ *ОПЛАТА ПРИНЯТА!* ✅\n\n";

    $message .= "🎉 Ваша оплата успешно обработана!\n\n";

    $message .= "📋 *ДЕТАЛИ ПЛАТЕЖА:*\n";
    $message .= "🆔 Заявка №" . $order['id'] . "\n";
    $message .= "💰 Сумма: *" . formatPrice($newPayment['summ']) . "*\n";
    $message .= "🕐 Время: " . date('d.m.Y H:i:s') . "\n\n";

    if ($tourInfo && isset($tourInfo['hotelname'])) {
        $message .= "🏨 Отель: " . $tourInfo['hotelname'] . "\n";
        if (isset($tourInfo['flydate'])) {
            $message .= "✈️ Вылет: " . date('d.m.Y', strtotime($tourInfo['flydate'])) . "\n";
        }
        $message .= "\n";
    }

    $message .= "💯 *СТАТУС ОПЛАТЫ:*\n";
    $message .= "✅ Оплачено: " . formatPrice($totalPaid) . "\n";

    if ($remainingAmount > 0) {
        $message .= "⏳ Осталось: " . formatPrice($remainingAmount) . "\n";
        $message .= "\n📞 Наш менеджер свяжется с вами для завершения оплаты.\n";
    } else {
        $message .= "🎉 *ОПЛАЧЕНО ПОЛНОСТЬЮ!*\n";
        $message .= "\n📞 Наш менеджер свяжется с вами с документами для поездки.\n";
    }

    $message .= "\n🙏 Спасибо за выбор ByFly Travel!";

    sendWhatsapp($userInfo['phone'], $message);
}

try {
    // Проверяем обязательные параметры
    $requiredFields = ['tranzactionId', 'order_id', 'price', 'user_id'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Обязательное поле '$field' не заполнено");
        }
    }

    $tranzactionId = $_POST['tranzactionId'];
    $orderId = intval($_POST['order_id']);
    $paymentAmount = floatval($_POST['price']);
    $userId = intval($_POST['user_id']);
    $paymentType = isset($_POST['payment_type']) ? $_POST['payment_type'] : 'order_in';

    // Проверяем, не дублируется ли транзакция
    $searchTranzaction = $db->query("SELECT * FROM order_pays WHERE tranzaction_id='" . $db->real_escape_string($tranzactionId) . "'");

    if ($searchTranzaction->num_rows > 0) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Транзакция уже была обработана ранее',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // Получаем информацию о заказе ДО изменений
    $orderDetails = getPaymentOrderDetails($db, $orderId);
    if (!$orderDetails) {
        throw new Exception("Заявка с ID $orderId не найдена");
    }

    $oldStatus = $orderDetails['order']['status_code'];
    $oldIncludesPrice = $orderDetails['order']['includesPrice'];

    // Начинаем транзакцию
    $db->autocommit(false);

    try {
        // Добавляем запись о платеже
        $insertPaymentSql = "INSERT INTO order_pays (`id`, `order_id`, `summ`, `user_id`, `date_create`, `type`, `tranzaction_id`) 
                            VALUES (NULL, '$orderId', '$paymentAmount', '$userId', CURRENT_TIMESTAMP, '" . $db->real_escape_string($paymentType) . "', '" . $db->real_escape_string($tranzactionId) . "')";

        if (!$db->query($insertPaymentSql)) {
            throw new Exception("Ошибка добавления платежа: " . $db->error);
        }

        $paymentId = $db->insert_id;

        // Получаем обновленную информацию о заказе
        $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='$orderId'")->fetch_assoc();
        $newIncludesPrice = $orderInfo['includesPrice'] + $paymentAmount;

        // Рассчитываем итоговую стоимость с доп. платежами
        $dopPaymentsResult = $db->query("SELECT SUM(summ) as total FROM order_dop_pays WHERE order_id='$orderId'");
        $dopPayments = 0;
        if ($dopPaymentsResult) {
            $dopPayments = $dopPaymentsResult->fetch_assoc()['total'] ?? 0;
        }

        $totalPrice = $orderInfo['price'] + $dopPayments;

        // Определяем новый статус
        $newStatus = $oldStatus;
        if ($newIncludesPrice >= $totalPrice) {
            $newStatus = 3; // Полностью оплачена
        } elseif ($newIncludesPrice >= $orderInfo['predoplata']) {
            $newStatus = 2; // Ожидает полную оплату
        } else {
            $newStatus = 1; // Ожидает предоплату
        }

        // Обновляем заказ
        $updateOrderSql = "UPDATE order_tours SET 
                          includesPrice='$newIncludesPrice', 
                          status_code='$newStatus' 
                          WHERE id='$orderId'";

        if (!$db->query($updateOrderSql)) {
            throw new Exception("Ошибка обновления заказа: " . $db->error);
        }

        // Подтверждаем транзакцию
        $db->commit();

        // Логируем успешный платеж
        $logMessage = "Платеж принят | Заявка ID:$orderId | Сумма:" . formatPrice($paymentAmount) .
            " | Тип:$paymentType | Транзакция:$tranzactionId | Статус:$oldStatus->$newStatus" .
            " | Всего оплачено:" . formatPrice($newIncludesPrice) . "/" . formatPrice($totalPrice);
        error_log($logMessage);

        // Получаем обновленную информацию для уведомлений
        $updatedOrderDetails = getPaymentOrderDetails($db, $orderId);

        // Создаем объект нового платежа для уведомлений
        $newPayment = [
            'id' => $paymentId,
            'summ' => $paymentAmount,
            'type' => $paymentType,
            'tranzaction_id' => $tranzactionId,
            'date_create' => date('Y-m-d H:i:s')
        ];

        // Отправляем уведомление менеджеру
        if ($updatedOrderDetails['manager'] && !empty($updatedOrderDetails['manager']['phone_whatsapp'])) {
            try {
                sendPaymentNotificationToManager(
                    $updatedOrderDetails['manager'],
                    $updatedOrderDetails,
                    $newPayment,
                    $newStatus,
                    $oldStatus
                );

                error_log("Отправлено уведомление о платеже менеджеру " . $updatedOrderDetails['manager']['fio'] . " (ID:" . $updatedOrderDetails['manager']['id'] . ")");
            } catch (\Throwable $notificationError) {
                error_log("Ошибка отправки уведомления менеджеру: " . $notificationError->getMessage());
            }
        }

        // Отправляем уведомление клиенту
        if ($updatedOrderDetails['user'] && !empty($updatedOrderDetails['user']['phone'])) {
            try {
                sendPaymentNotificationToClient(
                    $updatedOrderDetails['user'],
                    $updatedOrderDetails,
                    $newPayment,
                    $newStatus
                );

                error_log("Отправлено уведомление о платеже клиенту " . $updatedOrderDetails['user']['phone']);
            } catch (\Throwable $notificationError) {
                error_log("Ошибка отправки уведомления клиенту: " . $notificationError->getMessage());
            }
        }

        // Уведомляем администратора о полной оплате
        if ($newStatus == 3 && $oldStatus != 3) {
            $adminMessage = "💰 *ЗАЯВКА ПОЛНОСТЬЮ ОПЛАЧЕНА!*\n\n";
            $adminMessage .= "🆔 Заявка №$orderId\n";
            $adminMessage .= "👤 Клиент: " . ($updatedOrderDetails['user'] ? $updatedOrderDetails['user']['famale'] . " " . $updatedOrderDetails['user']['name'] : 'неизвестен') . "\n";
            $adminMessage .= "💰 Сумма: " . formatPrice($totalPrice) . "\n";
            $adminMessage .= "👨‍💼 Менеджер: " . ($updatedOrderDetails['manager'] ? $updatedOrderDetails['manager']['fio'] : 'не назначен') . "\n";

            if ($updatedOrderDetails['tour_info'] && isset($updatedOrderDetails['tour_info']['flydate'])) {
                $daysToFly = ceil((strtotime($updatedOrderDetails['tour_info']['flydate']) - time()) / (24 * 60 * 60));
                $adminMessage .= "✈️ Вылет через: $daysToFly дн.\n";

                if ($daysToFly <= 3) {
                    $adminMessage .= "\n🔥 *СРОЧНО! БЛИЗКИЙ ВЫЛЕТ!*";
                }
            }

            adminNotification($adminMessage);
        }

        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "payment_id" => $paymentId,
                    "new_status" => $newStatus,
                    "old_status" => $oldStatus,
                    "total_paid" => $newIncludesPrice,
                    "total_price" => $totalPrice,
                    "remaining_amount" => max(0, $totalPrice - $newIncludesPrice),
                    "is_fully_paid" => ($newIncludesPrice >= $totalPrice),
                    "manager_notified" => $updatedOrderDetails['manager'] ? true : false,
                    "client_notified" => $updatedOrderDetails['user'] ? true : false
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );

    } catch (\Throwable $dbError) {
        // Откатываем транзакцию при ошибке
        $db->rollback();
        throw $dbError;
    } finally {
        // Восстанавливаем автокоммит
        $db->autocommit(true);
    }

} catch (\Throwable $th) {
    // Восстанавливаем автокоммит в случае ошибки
    if (isset($db)) {
        $db->autocommit(true);
    }

    error_log("Ошибка обработки платежа: " . $th->getMessage());

    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>
