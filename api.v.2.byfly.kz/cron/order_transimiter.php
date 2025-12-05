<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function isWorkingHours()
{
    $dayOfWeek = date('N');
    $currentTime = strtotime(date('H:i'));

    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
        return $currentTime >= strtotime('10:00') && $currentTime <= strtotime('20:00');
    } elseif ($dayOfWeek == 6) {
        return $currentTime >= strtotime('10:00') && $currentTime <= strtotime('15:00');
    }
    return false;
}

function getManagerWithLowestCurrentLoad($db, $isQActive = null, $requiresSpec = false)
{
    $condition = "WHERE work_for_tours = '1' AND date_off_works IS NULL";
    if ($isQActive !== null) {
        $condition .= $isQActive ? " AND isActive='1'" : "";
    }
    if ($requiresSpec) {
        $condition .= " AND show_spec='1'";
    }

    $searchManagerDB = $db->query("SELECT * FROM managers $condition");
    if (!$searchManagerDB || $searchManagerDB->num_rows == 0) {
        return null;
    }

    $selectedId = null;
    $franchaiseId = null;
    $minCurrentLoad = null;

    while ($manager = $searchManagerDB->fetch_assoc()) {
        // Считаем текущую нагрузку: активные заявки (статусы 0, 1, 2, 3, 4)
        $currentLoadResult = $db->query("SELECT COUNT(*) as currentLoad 
                                         FROM order_tours 
                                         WHERE manager_id='" . $manager['id'] . "' 
                                         AND status_code IN (0, 1, 2, 3, 4)
                                         AND isCancle = 0");

        $currentLoad = $currentLoadResult ? $currentLoadResult->fetch_assoc()['currentLoad'] : 0;
        $currentLoad = $currentLoad ?? 0;

        // Добавляем небольшой вес для менеджеров, которые сегодня не работали
        $todayWorkResult = $db->query("SELECT COUNT(*) as todayWork 
                                       FROM order_tours 
                                       WHERE manager_id='" . $manager['id'] . "' 
                                       AND DATE(date_create) = CURDATE()");

        $todayWork = $todayWorkResult ? $todayWorkResult->fetch_assoc()['todayWork'] : 0;

        // Если менеджер сегодня не работал, уменьшаем его "нагрузку" для приоритета
        $adjustedLoad = $currentLoad - ($todayWork == 0 ? 0.5 : 0);

        if ($minCurrentLoad === null || $adjustedLoad < $minCurrentLoad) {
            $minCurrentLoad = $adjustedLoad;
            $selectedId = $manager['id'];
            $franchaiseId = $manager['franchaise'];
        }
    }

    return [
        'manager_id' => $selectedId,
        'franchaise_id' => $franchaiseId,
        'current_load' => $minCurrentLoad
    ];
}

function getOrderDetails($db, $orderId)
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

    // Парсим информацию о туре
    $tourInfo = json_decode($order['tours_info'], true);

    // Парсим информацию о пассажирах
    $passengersInfo = json_decode($order['listPassangers'], true);

    return [
        'order' => $order,
        'user' => $user,
        'tour_info' => $tourInfo,
        'passengers' => $passengersInfo
    ];
}

function formatPassengersList($passengers)
{
    if (!$passengers || !is_array($passengers)) {
        return "Информация о пассажирах не указана";
    }

    $passengersList = "";
    foreach ($passengers as $index => $passenger) {
        $passengersList .= "👤 " . ($index + 1) . ". ";
        $passengersList .= $passenger['passanger_famale'] . " " . $passenger['passanger_name'];

        if (isset($passenger['date_berthday'])) {
            $birthDate = date('d.m.Y', strtotime($passenger['date_berthday']));
            $passengersList .= " (д.р. $birthDate)";
        }

        if (isset($passenger['passangers_phone']) && !empty($passenger['passangers_phone'])) {
            $passengersList .= " 📞 " . $passenger['passangers_phone'];
        }

        $passengersList .= "\n";
    }

    return $passengersList;
}

function formatTourInfo($tourInfo)
{
    if (!$tourInfo || !is_array($tourInfo)) {
        return "Информация о туре недоступна";
    }

    $info = "";

    // Основная информация
    if (isset($tourInfo['hotelname'])) {
        $info .= "🏨 *Отель:* " . $tourInfo['hotelname'] . "\n";
    }

    if (isset($tourInfo['hotelstars'])) {
        $stars = str_repeat("⭐", (int) $tourInfo['hotelstars']);
        $info .= "⭐ *Звездность:* " . $stars . " (" . $tourInfo['hotelstars'] . "*)\n";
    }

    if (isset($tourInfo['countryname'])) {
        $info .= "🌍 *Страна:* " . $tourInfo['countryname'] . "\n";
    }

    if (isset($tourInfo['regionname'])) {
        $info .= "🏖️ *Курорт:* " . $tourInfo['regionname'] . "\n";
    }

    if (isset($tourInfo['flydate'])) {
        $info .= "✈️ *Дата вылета:* " . date('d.m.Y', strtotime($tourInfo['flydate'])) . "\n";
    }

    if (isset($tourInfo['nights'])) {
        $info .= "🌙 *Ночей:* " . $tourInfo['nights'] . "\n";
    }

    if (isset($tourInfo['mealrussian'])) {
        $info .= "🍽️ *Питание:* " . $tourInfo['mealrussian'] . "\n";
    }

    if (isset($tourInfo['room'])) {
        $info .= "🛏️ *Номер:* " . $tourInfo['room'] . "\n";
    }

    if (isset($tourInfo['operatorname'])) {
        $info .= "🏢 *Туроператор:* " . $tourInfo['operatorname'] . "\n";
    }

    return $info;
}

function formatPrice($price)
{
    return number_format($price, 0, '.', ' ') . " ₸";
}

try {
    $getMonitor = $db->query("SELECT * FROM monitor WHERE id='1'");
    if (!$getMonitor) {
        throw new Exception("Не удалось получить данные из таблицы monitor");
    }
    $monitorData = $getMonitor->fetch_assoc();

    if ($monitorData['order_getting'] == 0) {
        $db->query("UPDATE monitor SET order_getting='1' WHERE id='1'");

        // Получаем все заказы без менеджера
        $getEmptyOrdersDB = $db->query("SELECT * FROM order_tours WHERE manager_id='0' ORDER BY date_create ASC");
        if (!$getEmptyOrdersDB) {
            throw new Exception("Не удалось получить данные из таблицы order_tours");
        }

        while ($order = $getEmptyOrdersDB->fetch_assoc()) {
            $requiresSpec = $order['type'] === 'spec';
            $workingHours = isWorkingHours();

            // Получаем менеджера с наименьшей текущей нагрузкой
            if ($requiresSpec) {
                $managerData = getManagerWithLowestCurrentLoad($db, true, true);
                if (!$managerData) {
                    $managerData = getManagerWithLowestCurrentLoad($db, false, true);
                }
            } else {
                if ($workingHours) {
                    $managerData = getManagerWithLowestCurrentLoad($db, true, false);
                    if (!$managerData) {
                        $managerData = getManagerWithLowestCurrentLoad($db, false, false);
                    }
                } else {
                    $managerData = getManagerWithLowestCurrentLoad($db, false, false);
                }
            }

            if ($managerData) {
                // Получаем детальную информацию о заказе
                $orderDetails = getOrderDetails($db, $order['id']);

                $updateResult = $db->query("UPDATE order_tours 
                                            SET manager_id='" . $managerData['manager_id'] . "', 
                                                franchaice_id='" . $managerData['franchaise_id'] . "' 
                                            WHERE id='" . $order['id'] . "'");

                if ($updateResult && $orderDetails) {
                    $managerInfo = $db->query("SELECT * FROM managers WHERE id='" . $managerData['manager_id'] . "'")->fetch_assoc();

                    // Формируем подробное сообщение
                    $message = "🎉 *НОВАЯ ЗАЯВКА НА ОБРАБОТКУ!* 🎉\n\n";

                    // Информация о заявке
                    $message .= "📋 *ИНФОРМАЦИЯ О ЗАЯВКЕ:*\n";
                    $message .= "🆔 ID заявки: *" . $order['id'] . "*\n";
                    $message .= "📅 Дата создания: " . date('d.m.Y H:i', strtotime($order['date_create'])) . "\n";
                    $message .= "🏷️ Тип заявки: *" . ($order['type'] === 'spec' ? 'СПЕЦ ПРЕДЛОЖЕНИЕ' : 'ОБЫЧНЫЙ ТУР') . "*\n";
                    $message .= "💰 Стоимость: *" . formatPrice($order['price']) . "*\n";

                    // Статус
                    $statusText = [
                        0 => 'Новая (требует обработки)',
                        1 => 'Подтверждена, ожидает предоплату',
                        2 => 'Подтверждена, ожидает полную оплату',
                        3 => 'Полностью оплачена, ожидает вылета',
                        4 => 'Турист на отдыхе',
                        5 => 'Заявка отменена'
                    ];
                    $message .= "📊 Статус: *" . ($statusText[$order['status_code']] ?? 'Неизвестный') . "*\n\n";

                    // Информация о клиенте
                    if ($orderDetails['user']) {
                        $user = $orderDetails['user'];
                        $message .= "👤 *ИНФОРМАЦИЯ О КЛИЕНТЕ:*\n";
                        $message .= "👨‍💼 ФИО: " . $user['famale'] . " " . $user['name'] . " " . $user['surname'] . "\n";
                        $message .= "📱 Телефон: *" . $user['phone'] . "*\n";

                        if (!empty($user['email'])) {
                            $message .= "📧 Email: " . $user['email'] . "\n";
                        }

                        $userStatus = [
                            'user' => 'Пользователь',
                            'agent' => 'Агент',
                            'coach' => 'Коуч',
                            'alpha' => 'Альфа',
                            'ambasador' => 'Амбассадор'
                        ];
                        $message .= "🏆 Статус: " . ($userStatus[$user['user_status']] ?? 'Неизвестный') . "\n\n";
                    }

                    // Информация о туре
                    $message .= "🏖️ *ИНФОРМАЦИЯ О ТУРЕ:*\n";
                    $message .= formatTourInfo($orderDetails['tour_info']);
                    $message .= "\n";

                    // Информация о пассажирах
                    $message .= "✈️ *СПИСОК ПАССАЖИРОВ:*\n";
                    $message .= formatPassengersList($orderDetails['passengers']);
                    $message .= "\n";

                    // Дополнительные пожелания
                    if (!empty($order['dop_pojelaniya'])) {
                        $message .= "💭 *Дополнительные пожелания:*\n";
                        $message .= $order['dop_pojelaniya'] . "\n\n";
                    }

                    // Информация об оплате
                    if ($order['bonusPay'] > 0) {
                        $message .= "🎁 Оплачено бонусами: " . formatPrice($order['bonusPay']) . "\n";
                    }

                    if ($order['predoplata'] > 0) {
                        $message .= "💳 Требуется предоплата: " . formatPrice($order['predoplata']) . "\n";
                    }

                    if (!empty($order['dateOffPay'])) {
                        $message .= "⏰ Срок оплаты до: " . date('d.m.Y H:i', strtotime($order['dateOffPay'])) . "\n";
                    }

                    $message .= "\n";

                    // Текущая нагрузка менеджера
                    $message .= "📊 *ВАША ТЕКУЩАЯ НАГРУЗКА:*\n";
                    $currentActiveOrders = $db->query("SELECT COUNT(*) as count FROM order_tours WHERE manager_id='" . $managerData['manager_id'] . "' AND status_code IN (0,1,2,3,4) AND isCancle=0")->fetch_assoc()['count'];
                    $message .= "📋 Активных заявок: *" . ($currentActiveOrders + 1) . "*\n\n";

                    // Ссылка на систему
                    $message .= "🔗 *ПЕРЕЙТИ К ОБРАБОТКЕ:*\n";
                    $message .= "👉 https://manager.byfly.kz/\n\n";

                    $message .= "⚡ *Заявка требует срочной обработки!*\n";
                    $message .= "🙏 Спасибо за вашу работу!\n\n";
                    $message .= "_Система автоматического распределения заявок ByFly Travel_";

                    // Отправляем сообщение менеджеру
                    sendWhatsapp($managerInfo['phone_whatsapp'], $message);

                    // Логируем распределение
                    error_log("Заявка ID:" . $order['id'] . " назначена менеджеру ID:" . $managerData['manager_id'] . " (текущая нагрузка: " . $currentActiveOrders . ")");
                }

                if (!$updateResult) {
                    throw new Exception("Не удалось обновить данные order_tours для id=" . $order['id']);
                }
            } else {
                // Если не нашли подходящего менеджера, отправляем уведомление администратору
                $adminMessage = "⚠️ *ВНИМАНИЕ! ЗАЯВКА БЕЗ МЕНЕДЖЕРА!*\n\n";
                $adminMessage .= "Заявка ID: " . $order['id'] . "\n";
                $adminMessage .= "Тип: " . ($order['type'] === 'spec' ? 'Спец предложение' : 'Обычный тур') . "\n";
                $adminMessage .= "Дата создания: " . date('d.m.Y H:i', strtotime($order['date_create'])) . "\n\n";
                $adminMessage .= "Не удалось найти свободного менеджера для обработки заявки!\n";
                $adminMessage .= "Требуется ручное назначение.";

                adminNotification($adminMessage);
                error_log("КРИТИЧНО: Не найден менеджер для заявки ID:" . $order['id']);
            }
        }

        $db->query("UPDATE monitor SET order_getting='0' WHERE id='1'");
    }

} catch (\Throwable $th) {
    error_log("Ошибка в системе распределения заявок: " . $th->getMessage());
    $db->query("UPDATE monitor SET order_getting='0' WHERE id='1'");

    // Отправляем уведомление об ошибке администратору
    $errorMessage = "🚨 *ОШИБКА СИСТЕМЫ РАСПРЕДЕЛЕНИЯ ЗАЯВОК!*\n\n";
    $errorMessage .= "Время: " . date('d.m.Y H:i:s') . "\n";
    $errorMessage .= "Ошибка: " . $th->getMessage() . "\n\n";
    $errorMessage .= "Требуется немедленная проверка системы!";

    adminNotification($errorMessage);
}

$db->close();
if (isset($db2))
    $db2->close();
if (isset($db_docs))
    $db_docs->close();

?>