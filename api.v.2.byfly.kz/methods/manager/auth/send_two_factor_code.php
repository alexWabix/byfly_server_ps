<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$manager_id = $_POST['manager_id'] ?? '';
$phone = $_POST['phone'] ?? '';

if (empty($manager_id) || empty($phone)) {
    echo json_encode([
        "type" => false,
        "msg" => "Не указаны обязательные параметры"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Генерируем 6-значный код
    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

    // Удаляем старые коды для этого менеджера
    $deleteStmt = $db->prepare("DELETE FROM manager_two_factor_codes WHERE manager_id = ?");
    $deleteStmt->execute([$manager_id]);

    // Сохраняем новый код
    $insertStmt = $db->prepare("INSERT INTO manager_two_factor_codes (manager_id, code, phone, created_at, expires_at) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))");

    if ($insertStmt->execute([$manager_id, $code, $phone])) {
        // Отправляем код в WhatsApp
        $message = "🔐 *ByFly Travel CRM*\n\n";
        $message .= "Код для входа в систему: *{$code}*\n\n";
        $message .= "⏰ Код действует 10 минут\n";
        $message .= "🔒 Никому не сообщайте этот код!\n\n";
        $message .= "_Если это были не вы, немедленно свяжитесь с администратором_";

        sendWhatsapp($phone, $message);

        echo json_encode([
            "type" => true,
            "msg" => "Код подтверждения отправлен в WhatsApp",
            "data" => [
                "code" => $code // В продакшене уберите эту строку!
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "type" => false,
            "msg" => "Ошибка при сохранении кода в базе данных"
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode([
        "type" => false,
        "msg" => "Ошибка при генерации кода: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>