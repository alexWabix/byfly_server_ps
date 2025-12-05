<?php
$input = $_POST;
$login = isset($input['login']) ? trim($input['login']) : '';
$newPassword = isset($input['new_password']) ? trim($input['new_password']) : '';

if (empty($login) || empty($newPassword)) {
    $resp = array(
        "type" => false,
        "msg" => "Не все поля заполнены"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($newPassword) < 6) {
    $resp = array(
        "type" => false,
        "msg" => "Пароль должен содержать минимум 6 символов"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Определяем тип логина (телефон или email)
    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

    if ($isEmail) {
        // Поиск по email
        $managerStmt = $db->prepare("
            SELECT id, fio, phone_whatsapp, phone_call FROM managers 
            WHERE email = ? AND date_off_works IS NULL 
            LIMIT 1
        ");
        if (!$managerStmt) {
            throw new Exception("Ошибка подготовки запроса поиска менеджера: " . $db->error);
        }

        $managerStmt->bind_param("s", $login);
    } else {
        // Удаляем все символы кроме цифр для телефона
        $phone = preg_replace('/[^0-9]/', '', $login);

        // Поиск по телефону
        $managerStmt = $db->prepare("
            SELECT id, fio, phone_whatsapp, phone_call FROM managers 
            WHERE (phone_call = ? OR phone_whatsapp = ?) AND date_off_works IS NULL 
            LIMIT 1
        ");
        if (!$managerStmt) {
            throw new Exception("Ошибка подготовки запроса поиска менеджера: " . $db->error);
        }

        $managerStmt->bind_param("ss", $phone, $phone);
    }

    if (!$managerStmt->execute()) {
        throw new Exception("Ошибка выполнения запроса поиска менеджера: " . $managerStmt->error);
    }

    $managerResult = $managerStmt->get_result();

    if ($managerResult->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Менеджер не найден или уволен"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $manager = $managerResult->fetch_assoc();
    $managerStmt->close();

    // Обновляем пароль менеджера
    $hashedPassword = md5($newPassword);

    if ($isEmail) {
        $updateStmt = $db->prepare("
            UPDATE managers SET password = ? 
            WHERE email = ? AND date_off_works IS NULL
        ");
        if (!$updateStmt) {
            throw new Exception("Ошибка подготовки запроса обновления пароля: " . $db->error);
        }

        $updateStmt->bind_param("ss", $hashedPassword, $login);
    } else {
        $updateStmt = $db->prepare("
            UPDATE managers SET password = ? 
            WHERE (phone_call = ? OR phone_whatsapp = ?) AND date_off_works IS NULL
        ");
        if (!$updateStmt) {
            throw new Exception("Ошибка подготовки запроса обновления пароля: " . $db->error);
        }

        $updateStmt->bind_param("sss", $hashedPassword, $phone, $phone);
    }

    if (!$updateStmt->execute()) {
        throw new Exception("Ошибка обновления пароля: " . $updateStmt->error);
    }
    $updateStmt->close();

    // Определяем номер для отправки уведомления
    $notificationPhone = '';
    if (!empty($manager['phone_whatsapp'])) {
        $notificationPhone = preg_replace('/[^0-9]/', '', $manager['phone_whatsapp']);
    } elseif (!empty($manager['phone_call'])) {
        $notificationPhone = preg_replace('/[^0-9]/', '', $manager['phone_call']);
    }

    // Отправляем уведомление об успешной смене пароля
    if (!empty($notificationPhone)) {
        $message = "✅ *Пароль CRM системы изменен*\n\n";
        $message .= "Здравствуйте, {$manager['fio']}!\n\n";
        $message .= "Ваш пароль в CRM системе ByFly Travel был успешно изменен.\n\n";
        $message .= "🔒 Теперь вы можете войти в систему с новым паролем.\n\n";
        $message .= "Если вы не меняли пароль, немедленно свяжитесь с администратором.\n\n";
        $message .= "🔒 CRM система ByFly Travel\n";
        $message .= "С уважением,\nКоманда ByFly Travel 🛫";

        if (function_exists('sendWhatsapp')) {
            sendWhatsapp($notificationPhone, $message);
        }
    }

    $resp = array(
        "type" => true,
        "msg" => "Пароль успешно изменен",
        "data" => array(
            "manager_id" => $manager['id'],
            "manager_name" => $manager['fio']
        )
    );

} catch (Exception $e) {
    error_log("Manager password reset error: " . $e->getMessage());

    $resp = array(
        "type" => false,
        "msg" => "Произошла ошибка при смене пароля: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>