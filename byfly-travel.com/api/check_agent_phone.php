<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json');

// Получаем данные из запроса
$phone = $db->real_escape_string($_POST['phone'] ?? '');
$country = $db->real_escape_string($_POST['country'] ?? '');

// Проверяем, что телефон и страна переданы
if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Телефон не указан']);
    exit;
}

// Нормализуем номер телефона (удаляем все нецифровые символы)
$cleanPhone = preg_replace('/\D/', '', $phone);

// Ищем пользователя в базе
$query = "SELECT id FROM users WHERE phone LIKE '%$cleanPhone' AND user_status IN ('agent', 'coach', 'alpha') LIMIT 1";
$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user_id' => $user['id']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Агент с таким телефоном не найден']);
}

$db->close();
