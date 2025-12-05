<?php

$manager_id = $_POST['manager_id'] ?? '';
$phone = $_POST['phone'] ?? '';
$manager_name = $_POST['manager_name'] ?? '';
$login_time = $_POST['login_time'] ?? '';

if (empty($manager_id) || empty($phone)) {
    echo json_encode([
        "type" => false,
        "msg" => "Не указаны обязательные параметры"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Форматируем время
$login_datetime = new DateTime($login_time);
$formatted_time = $login_datetime->format('d.m.Y в H:i');

// Определяем устройство/браузер (упрощенно)
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Неизвестное устройство';
$device = 'Компьютер';
if (strpos($user_agent, 'Mobile') !== false) {
    $device = 'Мобильное устройство';
} elseif (strpos($user_agent, 'Tablet') !== false) {
    $device = 'Планшет';
}

// Получаем IP адрес
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Неизвестно';

// Формируем сообщение
$message = "🔐 *ByFly Travel CRM*\n\n";
$message .= "✅ *Успешный вход в систему*\n\n";
$message .= "👤 Менеджер: *{$manager_name}*\n";
$message .= "📅 Время: *{$formatted_time}*\n";
$message .= "💻 Устройство: *{$device}*\n";
$message .= "🌐 IP адрес: *{$ip_address}*\n\n";
$message .= "🔒 Если это были не вы, немедленно смените пароль и обратитесь к администратору!\n\n";
$message .= "_Это автоматическое уведомление системы безопасности_";

// Отправляем уведомление
sendWhatsapp($phone, $message);

// Логируем вход в систему
$stmt = $db->prepare("INSERT INTO manager_login_logs (manager_id, login_time, ip_address, user_agent, device_type) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$manager_id, $login_time, $ip_address, $user_agent, $device]);

echo json_encode([
    "type" => true,
    "msg" => "Уведомление отправлено"
], JSON_UNESCAPED_UNICODE);
?>