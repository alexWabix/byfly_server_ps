<?php
function convertDateToTimestamp($date)
{
    $timestamp = strtotime($date);
    return date('Y-m-d', $timestamp);
}

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

function getManagerWithLowestNewOrders($db, $isQActive = null, $requiresSpec = false)
{
    $condition = "WHERE work_for_tours = '1' AND date_off_works IS NULL";
    if ($isQActive !== null) {
        $condition .= $isQActive ? " AND isActive='1'" : "";
    }
    if ($requiresSpec) {
        $condition .= " AND show_spec='1'";
    }

    $searchManagerDB = $db->query("SELECT * FROM managers $condition ORDER BY id ASC");
    if (!$searchManagerDB || $searchManagerDB->num_rows == 0) {
        return null;
    }

    $selectedId = null;
    $franchaiseId = null;
    $minNewOrders = null;
    $managerInfo = null;

    while ($manager = $searchManagerDB->fetch_assoc()) {
        // Считаем только заявки в статусе "Новая" (status_code = 0)
        $newOrdersResult = $db->query("SELECT COUNT(*) as newOrders 
                                       FROM order_tours 
                                       WHERE manager_id='" . $manager['id'] . "' 
                                       AND status_code = 0
                                       AND isCancle = 0");

        $newOrders = $newOrdersResult ? $newOrdersResult->fetch_assoc()['newOrders'] : 0;
        $newOrders = $newOrders ?? 0;

        // Добавляем небольшой приоритет для активных менеджеров
        $adjustedLoad = $newOrders;
        if ($manager['isActive'] == '1') {
            $adjustedLoad -= 0.1; // Небольшое преимущество для активных менеджеров
        }

        if ($minNewOrders === null || $adjustedLoad < $minNewOrders) {
            $minNewOrders = $adjustedLoad;
            $selectedId = $manager['id'];
            $franchaiseId = $manager['franchaise'];
            $managerInfo = $manager;
        }
    }

    return [
        'manager_id' => $selectedId,
        'franchaise_id' => $franchaiseId,
        'manager_info' => $managerInfo,
        'new_orders_count' => $minNewOrders
    ];
}

function getOrderDetails($db, $orderId)
{
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

function sendDetailedOrderNotification($managerInfo, $orderDetails)
{
    $order = $orderDetails['order'];
    $user = $orderDetails['user'];
    $tourInfo = $orderDetails['tour_info'];
    $passengers = $orderDetails['passengers'];

    // Формируем подробное сообщение
    $message = "🎉 *НОВАЯ ЗАЯВКА НАЗНАЧЕНА ВАМ!* 🎉\n\n";

    // Информация о заявке
    $message .= "📋 *ИНФОРМАЦИЯ О ЗАЯВКЕ:*\n";
    $message .= "🆔 ID заявки: *" . $order['id'] . "*\n";
    $message .= "📅 Дата создания: " . date('d.m.Y H:i', strtotime($order['date_create'])) . "\n";
    $message .= "🏷️ Тип заявки: *" . ($order['type'] === 'spec' ? 'СПЕЦ ПРЕДЛОЖЕНИЕ' : ($order['type'] === 'test' ? 'ТЕСТОВАЯ ЗАЯВКА' : 'ОБЫЧНЫЙ ТУР')) . "*\n";
    $message .= "💰 Стоимость: *" . formatPrice($order['price']) . "*\n";

    if ($order['nakrutka'] > 0) {
        $message .= "📈 Накрутка: " . $order['nakrutka'] . "%\n";
    }

    $message .= "📊 Статус: *НОВАЯ - ТРЕБУЕТ ОБРАБОТКИ*\n\n";

    // Информация о клиенте
    if ($user) {
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
    $message .= formatTourInfo($tourInfo);
    $message .= "\n";

    // Информация о пассажирах
    $message .= "✈️ *СПИСОК ПАССАЖИРОВ:*\n";
    $message .= formatPassengersList($passengers);
    $message .= "\n";

    // Дополнительные пожелания
    if (!empty($order['dop_pojelaniya'])) {
        $message .= "💭 *Дополнительные пожелания:*\n";
        $message .= $order['dop_pojelaniya'] . "\n\n";
    }

    // Информация об оплате
    if ($order['predoplata'] > 0) {
        $message .= "💳 Требуется предоплата: " . formatPrice($order['predoplata']) . "\n";
    }

    if (!empty($order['dateOffPay'])) {
        $message .= "⏰ Срок оплаты до: " . date('d.m.Y H:i', strtotime($order['dateOffPay'])) . "\n";
    }

    $message .= "\n";

    // Приоритет обработки
    if ($order['type'] === 'spec') {
        $message .= "🔥 *ВЫСОКИЙ ПРИОРИТЕТ - СПЕЦ ПРЕДЛОЖЕНИЕ!*\n";
    } elseif ($order['count_day_to_fly'] <= 7) {
        $message .= "⚡ *СРОЧНО - ВЫЛЕТ ЧЕРЕЗ " . $order['count_day_to_fly'] . " ДН.*\n";
    }

    // Ссылка на систему
    $message .= "🔗 *ПЕРЕЙТИ К ОБРАБОТКЕ:*\n";
    $message .= "👉 https://manager.byfly.kz/\n\n";

    $message .= "⏰ *Заявка требует обработки в течение 1 часа!*\n";
    $message .= "🙏 Спасибо за вашу работу!\n\n";
    $message .= "_Автоматическое назначение заявки ByFly Travel_";

    // Отправляем сообщение
    sendWhatsapp($managerInfo['phone_whatsapp'], $message);
}

try {
    $orderType = 'tour';
    if (isset($_POST['isTest']) && ($_POST['isTest'] == 1 || $_POST['isTest'] == '1')) {
        $orderType = 'test';
    } elseif (isset($_POST['isSpec']) && ($_POST['isSpec'] == 1 || $_POST['isSpec'] == '1')) {
        $orderType = 'spec';
    }

    // Автоматический подбор менеджера
    $assignedManager = 0;
    $assignedFranchise = 0;
    $managerToNotify = null;

    // Если менеджер указан вручную
    $managerFromPost = isset($_POST['manager']) ? $_POST['manager'] : null;
    if (!empty($managerFromPost) && $managerFromPost != 0 && $managerFromPost != '0') {
        $assignedManager = intval($managerFromPost);
        $managerResult = $db->query("SELECT * FROM managers WHERE id='" . $assignedManager . "'");
        if ($managerResult && $managerResult->num_rows > 0) {
            $managerInfo = $managerResult->fetch_assoc();
            $assignedFranchise = $managerInfo['franchaise'];
            $managerToNotify = $managerInfo;
        }
    } else {
        // Автоматический подбор менеджера
        $requiresSpec = ($orderType === 'spec');
        $workingHours = isWorkingHours();

        if ($requiresSpec) {
            // Для спец предложений сначала ищем активных менеджеров со спец правами
            $managerData = getManagerWithLowestNewOrders($db, true, true);
            if (!$managerData) {
                // Если нет активных, ищем среди всех со спец правами
                $managerData = getManagerWithLowestNewOrders($db, false, true);
            }
        } else {
            if ($workingHours) {
                // В рабочее время приоритет активным менеджерам
                $managerData = getManagerWithLowestNewOrders($db, true, false);
                if (!$managerData) {
                    // Если нет активных, ищем среди всех
                    $managerData = getManagerWithLowestNewOrders($db, false, false);
                }
            } else {
                // В нерабочее время ищем среди всех менеджеров
                $managerData = getManagerWithLowestNewOrders($db, false, false);
            }
        }

        if ($managerData) {
            $assignedManager = $managerData['manager_id'];
            $assignedFranchise = $managerData['franchaise_id'];
            $managerToNotify = $managerData['manager_info'];
        } else {
            // Если не нашли менеджера, отправляем уведомление администратору
            $adminMessage = "⚠️ *КРИТИЧНО! НЕТ ДОСТУПНЫХ МЕНЕДЖЕРОВ!*\n\n";
            $adminMessage .= "Тип заявки: " . ($orderType === 'spec' ? 'Спец предложение' : 'Обычный тур') . "\n";
            $adminMessage .= "Время создания: " . date('d.m.Y H:i:s') . "\n\n";
            $adminMessage .= "Заявка будет создана без назначенного менеджера!\n";
            $adminMessage .= "Требуется срочное ручное назначение.";

            adminNotification($adminMessage);
        }
    }

    // Проверяем обязательные поля
    $requiredFields = ['price', 'tour_info', 'user_id'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Обязательное поле '$field' не заполнено");
        }
    }

    $price = floatval($_POST['price']);
    $nakrutka_user = isset($_POST['nakrutka']) ? floatval($_POST['nakrutka']) : 0;

    if ($nakrutka_user > 100) {
        if ($nakrutka_user > $price) {
            $nakrutka_percent = (($nakrutka_user - $price) / $price) * 100;
            if ($nakrutka_percent > 0) {
                $_POST['nakrutka'] = $nakrutka_percent;
            }
        } else {
            $_POST['nakrutka'] = isset($_POST['nakrutka_percentage']) ? $_POST['nakrutka_percentage'] : 0;
        }
    }

    $tour_info = $db->real_escape_string($_POST['tour_info']);
    $visor_hotel_info = isset($_POST['visor_hotel_info']) ? $db->real_escape_string($_POST['visor_hotel_info']) : '';
    $byly_hotel_info = isset($_POST['byly_hotel_info']) ? $db->real_escape_string($_POST['byly_hotel_info']) : '';
    $list_passangers = isset($_POST['list_passangers']) ? $db->real_escape_string($_POST['list_passangers']) : '';
    $dop_pojelaniya = isset($_POST['dop_pojelaniya']) ? $db->real_escape_string($_POST['dop_pojelaniya']) : '';

    if ($orderType == 'spec') {
        try {
            $tourId = isset($_POST['tourId']) ? $_POST['tourId'] : '';
            if (!empty($tourId)) {
                $searchSpecDB = $db->query("SELECT * FROM spec_tours WHERE tour_id='" . $tourId . "'");
                if ($searchSpecDB && $searchSpecDB->num_rows > 0) {
                    $searchSpec = $searchSpecDB->fetch_assoc();
                    $searchSpec['sales_place'] = $searchSpec['sales_place'] + 2;

                    $db->query("UPDATE spec_tours SET sales_place='" . $searchSpec['sales_place'] . "' WHERE id='" . $searchSpec['id'] . "'");
                }
            }
        } catch (\Throwable $th) {
        }
    }

    if (isset($_POST['nakrutka']) && $_POST['nakrutka'] > 0) {
        $price = $price + ceil((($price / 100) * $_POST['nakrutka']));
    }

    $realPrice = isset($_POST['real_price']) ? floatval($_POST['real_price']) : $price;

    if ($realPrice > 0 && $realPrice != $price) {
        $priceDifference = $price - $realPrice;
        $nakrutkaPercentage = ($priceDifference / $realPrice) * 100;
        $_POST['nakrutka'] = round($nakrutkaPercentage, 2);
    }

    $sallerId = isset($_POST['saler_id']) ? $_POST['saler_id'] : 0;
    $tourId = isset($_POST['tourId']) ? $_POST['tourId'] : '';
    $subUser = isset($_POST['sub_user']) ? $_POST['sub_user'] : 0;
    $countDayToFly = isset($_POST['count_day_to_fly']) ? intval($_POST['count_day_to_fly']) : 0;
    $predoplata = isset($_POST['predoplata']) ? floatval($_POST['predoplata']) : 0;
    $isAgent = isset($_POST['isAgent']) ? intval($_POST['isAgent']) : 0;
    $payments = isset($_POST['payments']) ? intval($_POST['payments']) : 0;
    $nakrutka = isset($_POST['nakrutka']) ? floatval($_POST['nakrutka']) : 0;

    // Получаем дату вылета из tour_info
    $flyDate = null;
    $tourInfoDecoded = json_decode($_POST['tour_info'], true);
    if ($tourInfoDecoded && isset($tourInfoDecoded['flydate'])) {
        $flyDate = convertDateToTimestamp($tourInfoDecoded['flydate']);
    }

    $sql = "INSERT INTO order_tours (
        `id`, 
        `date_create`, 
        `status_code`,
        `tours_info`, 
        `visor_hotel_info`, 
        `byly_hotel_info`, 
        `count_day_to_fly`, 
        `price`, 
        `predoplata`, 
        `isAgent`, 
        `payments`, 
        `listPassangers`, 
        `dop_pojelaniya`, 
        `nakrutka`, 
        `cancle_description`, 
        `isCancle`, 
        `isSuccess`, 
        `franchaice_id`, 
        `manager_id`, 
        `user_id`,
        `bonusPay`,
        `dateOffPay`,
        `flyDate`,
        `includesPrice`,
        `kaspi_pay_to_number`,
        `percentage_predoplata`,
        `type`,
        `real_price`,
        `sub_user`,
        `send_money_agent`,
        `summ_send_money`,
        `tourId`,
        `saler_id`,
        `summ_pay_to_operator`,
        `summ_need_pay`,
        `comission`,
        `order_id_in_operator_systems`,
        `date_deadline_pay_in_operarator`
    ) VALUES (
        NULL, 
        CURRENT_TIMESTAMP, 
        '0',
        '$tour_info', 
        '$visor_hotel_info', 
        '$byly_hotel_info', 
        '$countDayToFly', 
        '$price', 
        '$predoplata', 
        '$isAgent', 
        '$payments', 
        '$list_passangers', 
        '$dop_pojelaniya', 
        '$nakrutka', 
        '', 
        '0', 
        '0', 
        '$assignedFranchise', 
        '$assignedManager', 
        '" . intval($_POST['user_id']) . "',
        '0',
        NULL,
        " . ($flyDate ? "'$flyDate'" : "NULL") . ",
        '0',
        '',
        NULL,
        '$orderType',
        '$realPrice',
        '$subUser',
        '0',
        '0',
        '$tourId',
        '$sallerId',
        '0',
        '0',
        '0',
        NULL,
        NULL
    )";

    if ($db->query($sql)) {
        $order_id = $db->insert_id;

        // Обработка пассажиров
        if (!empty($list_passangers)) {
            $passangers = json_decode($_POST['list_passangers'], true);
            if ($passangers && is_array($passangers)) {
                foreach ($passangers as $vl) {
                    if (isset($vl['passangers_phone'])) {
                        $phone = preg_replace("/[^0-9]/", "", $vl['passangers_phone']);
                        $passangers_info = $db->real_escape_string(json_encode($vl, JSON_UNESCAPED_UNICODE));

                        $searchUserDB = $db->query("SELECT * FROM users WHERE phone = '$phone'");
                        if ($searchUserDB && $searchUserDB->num_rows > 0) {
                            $searchUser = $searchUserDB->fetch_assoc();
                            $db->query("INSERT INTO order_passangers (`id`, `user_id`, `order_id`, `passangers_info`) VALUES (NULL, '" . $searchUser['id'] . "', '$order_id', '$passangers_info')");
                        }
                    }
                }
            }
        }

        // Отправляем уведомление назначенному менеджеру
        if ($managerToNotify && $assignedManager > 0) {
            try {
                // Получаем детальную информацию о заказе
                $orderDetails = getOrderDetails($db, $order_id);

                if ($orderDetails) {
                    // Отправляем подробное уведомление
                    sendDetailedOrderNotification($managerToNotify, $orderDetails);
                } else {
                    // Отправляем простое уведомление если не удалось получить детали
                    $simpleMessage = "🎉 *Новая заявка назначена!* 🎉\n\n";
                    $simpleMessage .= "🆔 ID заявки: *" . $order_id . "*\n";
                    $simpleMessage .= "🏷️ Тип: " . ($orderType === 'spec' ? 'Спец предложение' : 'Обычный тур') . "\n\n";
                    $simpleMessage .= "🔗 Перейти к обработке:\n";
                    $simpleMessage .= "👉 https://manager.byfly.kz/2.0/";

                    sendWhatsapp($managerToNotify['phone_whatsapp'], $simpleMessage);
                }
            } catch (\Throwable $th) {
            }
        }

        echo json_encode(
            array(
                "type" => true,
                "data" => $order_id,
                "assigned_manager" => $assignedManager,
                "manager_name" => $managerToNotify ? $managerToNotify['fio'] : null
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $db->error,
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} catch (\Throwable $th) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>