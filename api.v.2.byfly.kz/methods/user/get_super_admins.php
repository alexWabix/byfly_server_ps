<?php

$sql = "SELECT id, name, famale, phone FROM users WHERE is_super_user = 1 OR is_buh = 1";
$result = $db->query($sql);

$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}

echo json_encode([
    "type" => true,
    "data" => $admins
]);
?>