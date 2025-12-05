<?php
// api/check_phone.php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';
$country = $data['country'] ?? 'KZ';

// Приводим телефон к стандартному формату
$phone = preg_replace('/[^0-9]/', '', $phone);

// Проверяем пользователя в базе
$query = $db->prepare("SELECT id, CONCAT(famale, ' ', name, ' ', surname) as full_name FROM users WHERE phone = ?");
$query->bind_param('s', $phone);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'exists' => true,
        'name' => $user['full_name']
    ]);
} else {
    echo json_encode([
        'exists' => false
    ]);
}
?>

<!-- PHP файл для проверки промокода (api/check_promo.php) -->