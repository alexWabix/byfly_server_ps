<?php
// api/
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$promo_code = $data['promo_code'] ?? '';

// Проверяем промокод в базе
$query = $db->prepare("SELECT id, name, famale, surname, phone, avatar, promo_code FROM users WHERE promo_code = ?");
$query->bind_param('s', $promo_code);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $full_name = trim($user['famale'] . ' ' . $user['name'] . ' ' . $user['surname']);

    echo json_encode([
        'exists' => true,
        'name' => $full_name,
        'phone' => $user['phone'],
        'promo_code' => $user['promo_code'],
        'avatar' => $user['avatar'] ?: null
    ]);
} else {
    echo json_encode([
        'exists' => false
    ]);
}
?>

<!-- PHP файл для проверки телефона пригласителя (api/check_inviter.php) -->
<?php
// api/check_inviter.php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';

// Приводим телефон к стандартному формату
$phone = preg_replace('/[^0-9]/', '', $phone);

// Проверяем пользователя в базе
$query = $db->prepare("SELECT id, name, famale, surname, phone, avatar, promo_code FROM users WHERE phone = ?");
$query->bind_param('s', $phone);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $full_name = trim($user['famale'] . ' ' . $user['name'] . ' ' . $user['surname']);

    echo json_encode([
        'exists' => true,
        'name' => $full_name,
        'phone' => $user['phone'],
        'promo_code' => $user['promo_code'],
        'avatar' => $user['avatar'] ?: null
    ]);
} else {
    echo json_encode([
        'exists' => false
    ]);
}
?>