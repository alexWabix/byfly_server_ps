<?php

$input = $_POST;
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$code = isset($input['code']) ? trim($input['code']) : '';
$newPassword = isset($input['new_password']) ? trim($input['new_password']) : '';

// Удаляем все символы кроме цифр из телефона
$phone = preg_replace('/[^0-9]/', '', $phone);

// Удаляем все символы кроме цифр из кода
$code = preg_replace('/[^0-9]/', '', $code);

if (empty($phone) || empty($code) || empty($newPassword)) {
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
    // Проверяем код
    $stmt = $db->prepare("
        SELECT id FROM password_reset_codes 
        WHERE phone = ? AND code = ? AND expires_at > NOW() AND used = 0 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $phone, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Неверный код или код истек"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Проверяем существует ли пользователь
    $userStmt = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $userStmt->bind_param("s", $phone);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Пользователь не найден"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Обновляем пароль пользователя
    $hashedPassword = md5($newPassword);
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE phone = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $phone);
    $updateStmt->execute();

    // Отмечаем код как использованный
    $markUsedStmt = $db->prepare("UPDATE password_reset_codes SET used = 1 WHERE phone = ? AND code = ?");
    $markUsedStmt->bind_param("ss", $phone, $code);
    $markUsedStmt->execute();

    // Удаляем все коды для этого номера
    $deleteCodesStmt = $db->prepare("DELETE FROM password_reset_codes WHERE phone = ?");
    $deleteCodesStmt->bind_param("s", $phone);
    $deleteCodesStmt->execute();

    // Отправляем уведомление об успешной смене пароля
    $message = "✅ *Пароль успешно изменен*\n\n";
    $message .= "Ваш пароль в системе ByFly Travel был успешно изменен.\n\n";
    $message .= "🔒 Теперь вы можете войти в систему с новым паролем.\n\n";
    $message .= "Если вы не меняли пароль, немедленно свяжитесь с поддержкой.\n\n";
    $message .= "С уважением,\nКоманда ByFly Travel 🛫";

    sendWhatsapp($phone, $message);

    $resp = array(
        "type" => true,
        "msg" => "Пароль успешно изменен"
    );

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => "Произошла ошибка при смене пароля: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>