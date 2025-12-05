<?php
$input = $_POST;
$phone = isset($input['phone']) ? trim($input['phone']) : '';

// Удаляем все символы кроме цифр
$phone = preg_replace('/[^0-9]/', '', $phone);

if (empty($phone)) {
    $resp = array(
        "type" => false,
        "msg" => "Номер телефона не указан"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем минимальную длину номера (должно быть минимум 10 цифр)
if (strlen($phone) < 10) {
    $resp = array(
        "type" => false,
        "msg" => "Неверный формат номера телефона"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Проверяем существует ли пользователь с таким номером
    $stmt = $db->prepare("SELECT id, name, famale FROM users WHERE phone = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Пользователь с таким номером телефона не найден"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user = $result->fetch_assoc();

    // Генерируем 6-значный код
    $code = sprintf("%06d", mt_rand(1, 999999));

    // Сохраняем код в базе данных (создаем таблицу если нужно)
    $createTable = "
        CREATE TABLE IF NOT EXISTS password_reset_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL,
            code VARCHAR(6) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            INDEX idx_phone_code (phone, code),
            INDEX idx_expires (expires_at)
        )
    ";
    $db->query($createTable);

    // Удаляем старые коды для этого номера
    $deleteOld = $db->prepare("DELETE FROM password_reset_codes WHERE phone = ?");
    $deleteOld->bind_param("s", $phone);
    $deleteOld->execute();

    // Добавляем новый код (действителен 10 минут)
    $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 минут
    $insertCode = $db->prepare("INSERT INTO password_reset_codes (phone, code, expires_at) VALUES (?, ?, ?)");
    $insertCode->bind_param("sss", $phone, $code, $expiresAt);
    $insertCode->execute();

    // Форматируем номер для отображения в сообщении
    $formattedPhone = $phone;
    if (strlen($phone) == 11 && (substr($phone, 0, 1) == '7')) {
        // Казахстан/Россия: +7 (777) 123 45 67
        $formattedPhone = '+7 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 2) . ' ' . substr($phone, 9, 2);
    } elseif (strlen($phone) == 12) {
        // Другие страны СНГ
        $countryCode = substr($phone, 0, 3);
        $number = substr($phone, 3);
        $formattedPhone = '+' . $countryCode . ' (' . substr($number, 0, 2) . ') ' . substr($number, 2, 3) . ' ' . substr($number, 5, 2) . ' ' . substr($number, 7, 2);
    }

    // Отправляем код в WhatsApp
    $message = "🔐 *Восстановление пароля ByFly Travel*\n\n";
    $message .= "Здравствуйте, {$user['name']} {$user['famale']}!\n\n";
    $message .= "Ваш код для восстановления пароля: *{$code}*\n\n";
    $message .= "⏰ Код действителен в течение 10 минут\n\n";
    $message .= "Если вы не запрашивали восстановление пароля, проигнорируйте это сообщение.\n\n";
    $message .= "С уважением,\nКоманда ByFly Travel 🛫";

    sendWhatsapp($phone, $message);

    $resp = array(
        "type" => true,
        "msg" => "Код подтверждения отправлен в WhatsApp на номер {$formattedPhone}"
    );

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => "Произошла ошибка при отправке кода: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>