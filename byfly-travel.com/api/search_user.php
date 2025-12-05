<?php
header('Content-Type: application/json');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? ''; // 'phone' или 'promo'
$value = $input['value'] ?? '';
$country = $input['country'] ?? 'KZ';

// Проверяем тип запроса
if (!in_array($type, ['phone', 'promo']) || empty($value)) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры запроса']);
    exit;
}

// Подготовка данных для ответа
$response = [
    'success' => false,
    'message' => 'Пользователь не найден',
    'user' => null
];

// Поиск по телефону
if ($type === 'phone') {
    // Нормализуем номер телефона (убираем все нецифровые символы)
    $phone = preg_replace('/[^0-9]/', '', $value);

    // Проверяем длину номера в зависимости от страны
    $valid = false;
    switch ($country) {
        case 'KZ':
        case 'RU':
            $valid = (strlen($phone) === 11 && $phone[0] === '7');
            break;
        case 'UZ':
            $valid = (strlen($phone) === 12 && substr($phone, 0, 3) === '998');
            break;
        // Добавьте другие страны по аналогии
        default:
            $valid = (strlen($phone) >= 10);
    }

    if (!$valid) {
        $response['message'] = 'Неверный формат телефона для выбранной страны';
        echo json_encode($response);
        exit;
    }

    // Ищем пользователя в базе
    $query = $db->prepare("SELECT id, name, famale, surname, phone, avatar, promo_code FROM users WHERE phone = ?");
    $query->bind_param('s', $phone);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response = [
            'success' => true,
            'message' => 'Пользователь найден',
            'user' => [
                'id' => $user['id'],
                'name' => $user['famale'] . ' ' . $user['name'] . ' ' . $user['surname'],
                'phone' => $user['phone'],
                'avatar' => $user['avatar'],
                'promo_code' => $user['promo_code']
            ]
        ];
    }
}
// Поиск по промокоду
elseif ($type === 'promo') {
    $promo_code = trim($value);

    // Ищем пользователя по промокоду
    $query = $db->prepare("SELECT id, name, phone, avatar, promo_code FROM users WHERE promo_code = ?");
    $query->bind_param('s', $promo_code);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response = [
            'success' => true,
            'message' => 'Пользователь найден',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'avatar' => $user['avatar'],
                'promo_code' => $user['promo_code']
            ]
        ];
    }
}

echo json_encode($response);
?>