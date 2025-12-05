<?php
$user_id = $_POST['user_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$ip_id = $_POST['ip_id'] ?? null;

if (!$user_id || !$amount || !$ip_id) {
    echo json_encode([
        "type" => false,
        "msg" => "Не указаны обязательные параметры"
    ]);
    exit;
}

// Проверяем баланс пользователя
$user_info = getUserParams($user_id);
if ($user_info['balance'] < $amount) {
    echo json_encode([
        "type" => false,
        "msg" => "Недостаточно средств на балансе"
    ]);
    exit;
}

// Проверяем минимальную сумму
if ($amount < 50000) {
    echo json_encode([
        "type" => false,
        "msg" => "Минимальная сумма для вывода 50 000 тенге"
    ]);
    exit;
}

// Проверяем, что реквизиты принадлежат пользователю и верифицированы
$ip_sql = "SELECT * FROM user_ip WHERE id = ? AND user_id = ? AND is_active = 1 AND verification_status = 'verified'";
$ip_stmt = $db->prepare($ip_sql);
$ip_stmt->bind_param("ii", $ip_id, $user_id);
$ip_stmt->execute();
$ip_result = $ip_stmt->get_result();

if ($ip_result->num_rows == 0) {
    echo json_encode([
        "type" => false,
        "msg" => "Реквизиты не найдены или не верифицированы"
    ]);
    exit;
}

// Создаем заявку на вывод
$sql = "INSERT INTO order_oute_money (
    date_create, 
    date_money_oute_prognoz, 
    user_id, 
    summ, 
    ip_id
) VALUES (
    NOW(), 
    DATE_ADD(NOW(), INTERVAL 5 DAY), 
    ?, 
    ?, 
    ?
)";

$stmt = $db->prepare($sql);
$stmt->bind_param("iii", $user_id, $amount, $ip_id);

if ($stmt->execute()) {
    echo json_encode([
        "type" => true,
        "msg" => "Заявка на вывод средств успешно создана"
    ]);
} else {
    echo json_encode([
        "type" => false,
        "msg" => "Ошибка при создании заявки: " . $db->error
    ]);
}
?>