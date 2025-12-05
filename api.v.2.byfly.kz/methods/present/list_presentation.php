<?php
$listPresent = array();

$getPresentDB = $db->query("SELECT * FROM present_event WHERE checked='1' AND showToClient='1' AND date_off > '" . date('Y-m-d H:i:s') . "' ORDER BY date_start ASC");
while ($getPresent = $getPresentDB->fetch_assoc()) {
    $getPresent['present_user'] = $db->query("SELECT * FROM users WHERE id='" . $getPresent['user_id'] . "'")->fetch_assoc();
    $getPresent['link'] = 'https://byfly.kz/?type=present&id=' . $getPresent['id'];

    $getPresent['users'] = array();
    $getUsersPresentDB = $db->query("SELECT * FROM present_event_users WHERE event_id='" . $getPresent['id'] . "'");
    while ($getUsersPresent = $getUsersPresentDB->fetch_assoc()) {
        $userInfo = $db->query("SELECT id, avatar, name, famale, user_status FROM users WHERE id='" . $getUsersPresent['user_id'] . "'")->fetch_assoc();
        array_push($getPresent['users'], $userInfo);
    }

    array_push($listPresent, $getPresent);
}


echo json_encode(
    [
        "type" => true,
        "data" => $listPresent,
    ],
    JSON_UNESCAPED_UNICODE,
);
?>