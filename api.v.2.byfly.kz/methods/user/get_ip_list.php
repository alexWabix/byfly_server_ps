<?php
$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "type" => false,
        "msg" => "Не указан ID пользователя"
    ]);
    exit;
}

$sql = "SELECT ui.*, 
               CASE 
                 WHEN ui.company_name IS NOT NULL AND ui.company_name != '' 
                 THEN CONCAT(ui.company_form, ' \"', ui.company_name, '\"')
                 ELSE CONCAT(ui.company_form, ' ', ui.owner_full_name)
               END as display_name
        FROM user_ip ui 
        WHERE ui.user_id = ? 
        AND ui.is_active = 1 
        ORDER BY ui.date_create DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$ip_list = [];
while ($row = $result->fetch_assoc()) {
    $ip_list[] = $row;
}

echo json_encode([
    "type" => true,
    "data" => $ip_list
]);
?>