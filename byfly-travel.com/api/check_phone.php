<?php
header('Content-Type: application/json');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? '';
$country = $input['country'] ?? 'KZ';

// Нормализуем номер телефона
$phone = preg_replace('/[^0-9]/', '', $phone);

$response = [
    'exists' => false,
    'name' => ''
];

if (!empty($phone)) {
    $query = $db->prepare("SELECT name FROM users WHERE phone = ?");
    $query->bind_param('s', $phone);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response = [
            'exists' => true,
            'name' => $user['name']
        ];
    }
}

echo json_encode($response);
?>