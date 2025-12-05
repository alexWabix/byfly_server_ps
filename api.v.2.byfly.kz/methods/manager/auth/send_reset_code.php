<?php
$input = $_POST;
$login = isset($input['login']) ? trim($input['login']) : '';

if (empty($login)) {
    $resp = array(
        "type" => false,
        "msg" => "Логин не указан"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Определяем тип логина (телефон или email)
    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

    if ($isEmail) {
        // Поиск по email
        $stmt = $db->prepare("
            SELECT id, fio, phone_call, phone_whatsapp, email 
            FROM managers 
            WHERE email = ? AND date_off_works IS NULL 
            LIMIT 1
        ");
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . $db->error);
        }

        $stmt->bind_param("s", $login);
    } else {
        // Удаляем все символы кроме цифр для телефона
        $phone = preg_replace('/[^0-9]/', '', $login);

        if (strlen($phone) < 10) {
            $resp = array(
                "type" => false,
                "msg" => "Неверный формат номера телефона или email"
            );
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Поиск по телефону
        $stmt = $db->prepare("
            SELECT id, fio, phone_call, phone_whatsapp, email 
            FROM managers 
            WHERE (phone_call = ? OR phone_whatsapp = ?) AND date_off_works IS NULL 
            LIMIT 1
        ");
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . $db->error);
        }

        $stmt->bind_param("ss", $phone, $phone);
    }

    if (!$stmt->execute()) {
        throw new Exception("Ошибка выполнения запроса: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Менеджер с таким логином не найден или уволен"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $manager = $result->fetch_assoc();
    $stmt->close();

    // Генерируем 6-значный код
    $code = sprintf("%06d", mt_rand(100000, 999999));

    // Определяем номер для отправки WhatsApp (приоритет phone_whatsapp)
    $whatsappPhone = '';
    if (!empty($manager['phone_whatsapp'])) {
        $whatsappPhone = preg_replace('/[^0-9]/', '', $manager['phone_whatsapp']);
    } elseif (!empty($manager['phone_call'])) {
        $whatsappPhone = preg_replace('/[^0-9]/', '', $manager['phone_call']);
    } else {
        $resp = array(
            "type" => false,
            "msg" => "У менеджера не указан номер телефона для отправки кода"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Форматируем номер для отображения в сообщении
    $formattedPhone = $whatsappPhone;
    if (strlen($whatsappPhone) == 11 && (substr($whatsappPhone, 0, 1) == '7')) {
        // Казахстан/Россия: +7 (777) 123 45 67
        $formattedPhone = '+7 (' . substr($whatsappPhone, 1, 3) . ') ' . substr($whatsappPhone, 4, 3) . ' ' . substr($whatsappPhone, 7, 2) . ' ' . substr($whatsappPhone, 9, 2);
    } elseif (strlen($whatsappPhone) == 12) {
        // Другие страны СНГ
        $countryCode = substr($whatsappPhone, 0, 3);
        $number = substr($whatsappPhone, 3);
        $formattedPhone = '+' . $countryCode . ' (' . substr($number, 0, 2) . ') ' . substr($number, 2, 3) . ' ' . substr($number, 5, 2) . ' ' . substr($number, 7, 2);
    }

    // Отправляем код в WhatsApp
    $message = "🔐 *Восстановление пароля CRM ByFly Travel*\n\n";
    $message .= "Здравствуйте, {$manager['fio']}!\n\n";
    $message .= "Ваш код для восстановления пароля: *{$code}*\n\n";
    $message .= "⏰ Код действителен в течение 10 минут\n\n";
    $message .= "Если вы не запрашивали восстановление пароля, проигнорируйте это сообщение.\n\n";
    $message .= "🔒 CRM система ByFly Travel\n";
    $message .= "С уважением,\nКоманда ByFly Travel 🛫";

    // Отправляем WhatsApp сообщение
    if (function_exists('sendWhatsapp')) {
        sendWhatsapp($whatsappPhone, $message);
    }

    $resp = array(
        "type" => true,
        "msg" => "Код подтверждения отправлен в WhatsApp на номер {$formattedPhone}",
        "data" => array(
            "manager_id" => $manager['id'],
            "manager_name" => $manager['fio'],
            "phone_for_code" => $whatsappPhone,
            "code" => $code // Возвращаем код приложению
        )
    );

} catch (Exception $e) {
    error_log("Manager password reset error: " . $e->getMessage());

    $resp = array(
        "type" => false,
        "msg" => "Произошла ошибка при отправке кода. Попробуйте позже."
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>