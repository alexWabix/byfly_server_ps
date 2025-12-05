<?php

$passenger_id = isset($_POST['passenger_id']) ? intval($_POST['passenger_id']) : 0;
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$current_user_id = isset($_POST['current_user_id']) ? intval($_POST['current_user_id']) : 0;
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$citizenship = isset($_POST['citizenship']) ? trim($_POST['citizenship']) : '';
$iin = isset($_POST['iin']) ? trim($_POST['iin']) : '';
$passport_number = isset($_POST['passport_number']) ? trim($_POST['passport_number']) : '';
$birth_date = isset($_POST['birth_date']) ? $_POST['birth_date'] : null;
$passport_expiry = isset($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null;
$is_child = isset($_POST['is_child']) ? intval($_POST['is_child']) : 0;
$passport_image = isset($_POST['passport_image']) ? $_POST['passport_image'] : null;

if ($passenger_id <= 0 || $order_id <= 0 || $current_user_id <= 0) {
    $resp = array(
        "type" => false,
        "msg" => "Некорректные параметры запроса",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем старые данные пассажира для сравнения
$get_old_passenger = $db->prepare("SELECT * FROM passangers WHERE id = ?");
$get_old_passenger->bind_param("i", $passenger_id);
$get_old_passenger->execute();
$old_passenger_result = $get_old_passenger->get_result();

if ($old_passenger_result->num_rows == 0) {
    $resp = array(
        "type" => false,
        "msg" => "Пассажир не найден",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$old_passenger_data = $old_passenger_result->fetch_assoc();

// Проверяем права на редактирование и получаем данные заказа
$check_order = $db->prepare("SELECT ot.*, u.name as user_name, u.phone as user_phone, 
    s.name as saler_name, s.phone as saler_phone, m.phone_whatsapp as manager_phone 
    FROM order_tours ot 
    LEFT JOIN users u ON ot.user_id = u.id 
    LEFT JOIN users s ON ot.saler_id = s.id 
    LEFT JOIN managers m ON ot.manager_id = m.id 
    WHERE ot.id = ?");
$check_order->bind_param("i", $order_id);
$check_order->execute();
$order_result = $check_order->get_result();

if ($order_result->num_rows == 0) {
    $resp = array(
        "type" => false,
        "msg" => "Заказ не найден",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$order_data = $order_result->fetch_assoc();

// Проверяем, можно ли редактировать
if (!empty($order_data['order_id_in_operator_systems'])) {
    $resp = array(
        "type" => false,
        "msg" => "Редактирование недоступно - заявка уже передана туроператору",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем права пользователя
if (
    $current_user_id != $order_data['user_id'] &&
    ($order_data['saler_id'] == 0 || $current_user_id != $order_data['saler_id'])
) {
    $resp = array(
        "type" => false,
        "msg" => "У вас нет прав на редактирование данного пассажира",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Валидация данных
if (empty($last_name) || empty($first_name)) {
    $resp = array(
        "type" => false,
        "msg" => "Фамилия и имя обязательны для заполнения",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[A-Za-z\s]+$/', $last_name) || !preg_match('/^[A-Za-z\s]+$/', $first_name)) {
    $resp = array(
        "type" => false,
        "msg" => "Фамилия и имя должны содержать только латинские буквы",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($birth_date)) {
    $resp = array(
        "type" => false,
        "msg" => "Дата рождения обязательна для заполнения",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Валидация ИИН для Казахстана
if ($citizenship == 'Казахстан' && !empty($iin)) {
    if (!preg_match('/^\d{12}$/', $iin)) {
        $resp = array(
            "type" => false,
            "msg" => "ИИН должен содержать 12 цифр",
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Валидация ИНН для России
if ($citizenship == 'Россия' && !empty($iin)) {
    if (!preg_match('/^\d{12}$/', $iin)) {
        $resp = array(
            "type" => false,
            "msg" => "ИНН должен содержать 12 цифр",
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Получаем информацию о пользователе, который вносит изменения
$get_editor_info = $db->prepare("SELECT name, famale, phone FROM users WHERE id = ?");
$get_editor_info->bind_param("i", $current_user_id);
$get_editor_info->execute();
$editor_result = $get_editor_info->get_result();
$editor_data = $editor_result->fetch_assoc();

// Формируем список изменений
$changes = array();

if ($old_passenger_data['passanger_famale'] != $last_name) {
    $changes[] = "Фамилия: {$old_passenger_data['passanger_famale']} → $last_name";
}

if ($old_passenger_data['passanger_name'] != $first_name) {
    $changes[] = "Имя: {$old_passenger_data['passanger_name']} → $first_name";
}

if ($old_passenger_data['passangers_phone'] != $phone) {
    $changes[] = "Телефон: {$old_passenger_data['passangers_phone']} → $phone";
}

if ($old_passenger_data['grazhdanstvo'] != $citizenship) {
    $changes[] = "Гражданство: {$old_passenger_data['grazhdanstvo']} → $citizenship";
}

if ($old_passenger_data['iin'] != $iin) {
    $changes[] = "ИИН/ИНН: {$old_passenger_data['iin']} → $iin";
}

if ($old_passenger_data['number_pasport'] != $passport_number) {
    $changes[] = "Номер паспорта: {$old_passenger_data['number_pasport']} → $passport_number";
}

if ($old_passenger_data['date_berthday'] != $birth_date) {
    $changes[] = "Дата рождения: {$old_passenger_data['date_berthday']} → $birth_date";
}

if ($old_passenger_data['pasport_srok'] != $passport_expiry) {
    $changes[] = "Срок действия паспорта: {$old_passenger_data['pasport_srok']} → $passport_expiry";
}

if ($old_passenger_data['isChildren'] != $is_child) {
    $old_type = $old_passenger_data['isChildren'] == 1 ? 'Ребенок' : 'Взрослый';
    $new_type = $is_child == 1 ? 'Ребенок' : 'Взрослый';
    $changes[] = "Тип пассажира: $old_type → $new_type";
}

if ($old_passenger_data['pasport_link'] != $passport_image) {
    $changes[] = "Фото паспорта: обновлено";
}

// Обновляем данные пассажира
$update_passenger = $db->prepare("UPDATE passangers SET 
    passanger_famale = ?, 
    passanger_name = ?, 
    passangers_phone = ?, 
    grazhdanstvo = ?, 
    iin = ?, 
    number_pasport = ?, 
    date_berthday = ?, 
    pasport_srok = ?, 
    isChildren = ?, 
    pasport_link = ? 
    WHERE id = ?");

$update_passenger->bind_param(
    "sssssssissi",
    $last_name,
    $first_name,
    $phone,
    $citizenship,
    $iin,
    $passport_number,
    $birth_date,
    $passport_expiry,
    $is_child,
    $passport_image,
    $passenger_id
);

if ($update_passenger->execute()) {
    // Обновляем информацию в заказе тура
    $get_order_passengers = $db->prepare("SELECT listPassangers FROM order_tours WHERE id = ?");
    $get_order_passengers->bind_param("i", $order_id);
    $get_order_passengers->execute();
    $order_passengers_result = $get_order_passengers->get_result();

    if ($order_passengers_result->num_rows > 0) {
        $order_passengers_data = $order_passengers_result->fetch_assoc();
        $passengers_list = json_decode($order_passengers_data['listPassangers'], true);

        if ($passengers_list) {
            // Обновляем данные пассажира в списке
            for ($i = 0; $i < count($passengers_list); $i++) {
                if (isset($passengers_list[$i]['id']) && $passengers_list[$i]['id'] == $passenger_id) {
                    $passengers_list[$i]['passanger_famale'] = $last_name;
                    $passengers_list[$i]['passanger_name'] = $first_name;
                    $passengers_list[$i]['passangers_phone'] = $phone;
                    $passengers_list[$i]['grazhdanstvo'] = $citizenship;
                    $passengers_list[$i]['iin'] = $iin;
                    $passengers_list[$i]['number_pasport'] = $passport_number;
                    $passengers_list[$i]['date_berthday'] = $birth_date;
                    $passengers_list[$i]['pasport_srok'] = $passport_expiry;
                    $passengers_list[$i]['isChildren'] = $is_child;
                    $passengers_list[$i]['pasport_link'] = $passport_image;
                    break;
                }
            }

            // Сохраняем обновленный список
            $updated_passengers_json = json_encode($passengers_list, JSON_UNESCAPED_UNICODE);
            $update_order_passengers = $db->prepare("UPDATE order_tours SET listPassangers = ? WHERE id = ?");
            $update_order_passengers->bind_param("si", $updated_passengers_json, $order_id);
            $update_order_passengers->execute();
        }
    }

    // Отправляем уведомления только если есть изменения
    if (!empty($changes)) {
        $editor_name = trim($editor_data['name'] . ' ' . $editor_data['famale']);
        $old_passenger_name = trim($old_passenger_data['passanger_name'] . ' ' . $old_passenger_data['passanger_famale']);
        $new_passenger_name = trim($first_name . ' ' . $last_name);

        $changes_text = implode("\n", $changes);

        // Проверяем статус заказа для добавления ваучера
        $voucher_text = "";
        if ($order_data['status_code'] > 2) {
            $voucher_link = "https://byfly-travel.com/vaucher.php?orderId=$order_id";
            $voucher_text = "🎫 Ваучер: $voucher_link\n\n";
        }

        // Базовое сообщение
        $base_message = "🔄 ИЗМЕНЕНИЕ ДАННЫХ ПАССАЖИРА\n\n";
        $base_message .= "📋 Заказ №$order_id\n";
        $base_message .= "👤 Пассажир: $old_passenger_name → $new_passenger_name\n";
        $base_message .= "✏️ Изменения внес: $editor_name\n\n";
        $base_message .= "📝 Список изменений:\n$changes_text\n\n";

        if (!empty($passport_image)) {
            $base_message .= "📷 Фото паспорта: $passport_image\n\n";
        }

        $base_message .= $voucher_text;
        $base_message .= "ByFly Travel 🌍✈️";

        // Отправляем уведомление менеджеру
        if (!empty($order_data['manager_phone'])) {
            $manager_message = "👨‍💼 УВЕДОМЛЕНИЕ ДЛЯ МЕНЕДЖЕРА\n\n" . $base_message;
            sendWhatsapp($order_data['manager_phone'], $manager_message);
        }

        // Отправляем уведомление клиенту (тому кто оформил заказ)
        if (!empty($order_data['user_phone'])) {
            $client_message = "👤 УВЕДОМЛЕНИЕ КЛИЕНТУ\n\n" . $base_message;
            sendWhatsapp($order_data['user_phone'], $client_message);
        }

        // Отправляем уведомление продавцу (если есть и он не тот же кто вносил изменения)
        if (!empty($order_data['saler_phone']) && $order_data['saler_id'] != $current_user_id) {
            $saler_message = "💼 УВЕДОМЛЕНИЕ ПРОДАВЦУ\n\n" . $base_message;
            sendWhatsapp($order_data['saler_phone'], $saler_message);
        }

        // Отправляем уведомление старому пассажиру (если номер изменился)
        if (
            !empty($old_passenger_data['passangers_phone']) &&
            $old_passenger_data['passangers_phone'] != $phone
        ) {
            $old_passenger_message = "📱 УВЕДОМЛЕНИЕ ПАССАЖИРУ\n\n";
            $old_passenger_message .= "Ваши данные в заказе №$order_id были изменены.\n\n";
            $old_passenger_message .= "Старые данные: $old_passenger_name\n";
            $old_passenger_message .= "Новые данные: $new_passenger_name\n\n";
            $old_passenger_message .= "Изменения внес: $editor_name\n\n";
            $old_passenger_message .= $voucher_text;
            $old_passenger_message .= "ByFly Travel 🌍✈️";

            sendWhatsapp($old_passenger_data['passangers_phone'], $old_passenger_message);
        }

        // Отправляем уведомление новому пассажиру (если номер изменился)
        if (!empty($phone) && $old_passenger_data['passangers_phone'] != $phone) {
            $new_passenger_message = "📱 УВЕДОМЛЕНИЕ ПАССАЖИРУ\n\n";
            $new_passenger_message .= "Вы добавлены как пассажир в заказ №$order_id\n\n";
            $new_passenger_message .= "Ваши данные: $new_passenger_name\n";
            $new_passenger_message .= "Изменения внес: $editor_name\n\n";
            $new_passenger_message .= $voucher_text;
            $new_passenger_message .= "ByFly Travel 🌍✈️";

            sendWhatsapp($phone, $new_passenger_message);
        }

        // Отправляем уведомление всем остальным пассажирам в заказе
        if ($passengers_list) {
            foreach ($passengers_list as $passenger) {
                if ($passenger['id'] != $passenger_id && !empty($passenger['passangers_phone'])) {
                    $other_passenger_message = "👥 УВЕДОМЛЕНИЕ ПОПУТЧИКУ\n\n";
                    $other_passenger_message .= "В вашем заказе №$order_id изменены данные пассажира.\n\n";
                    $other_passenger_message .= "Было: $old_passenger_name\n";
                    $other_passenger_message .= "Стало: $new_passenger_name\n\n";
                    $other_passenger_message .= "Изменения внес: $editor_name\n\n";
                    $other_passenger_message .= $voucher_text;
                    $other_passenger_message .= "ByFly Travel 🌍✈️";

                    sendWhatsapp($passenger['passangers_phone'], $other_passenger_message);
                }
            }
        }
    }

    // Логируем изменение
    $log_message = "Обновлены данные пассажира ID: $passenger_id в заказе ID: $order_id пользователем ID: $current_user_id. Изменения: " . implode(", ", $changes);
    $log_stmt = $db->prepare("INSERT INTO error_logs (text, date_create) VALUES (?, NOW())");
    $log_stmt->bind_param("s", $log_message);
    $log_stmt->execute();

    $resp = array(
        "type" => true,
        "msg" => "Данные пассажира успешно обновлены",
    );
} else {
    $resp = array(
        "type" => false,
        "msg" => "Ошибка при обновлении данных пассажира",
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>