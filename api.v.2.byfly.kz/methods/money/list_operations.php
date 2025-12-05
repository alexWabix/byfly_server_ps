<?php
$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "type" => false,
        "msg" => "Не указан ID пользователя"
    ]);
    exit;
}

$sql = "SELECT 
    ut.id as tranzaction_id,
    ut.date_create as date,
    ut.summ,
    ut.type_operations as oute,
    ut.pay_info as desc,
    ut.operation as type,
    ut.tour_id as order_id
FROM user_tranzactions ut 
WHERE ut.user_id = ? 
ORDER BY ut.date_create DESC 
LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode([
    "type" => true,
    "data" => [
        "tranzaction" => $transactions
    ]
]);
?>