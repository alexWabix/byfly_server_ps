<?php
$settings = $db->query("SELECT * FROM app_settings WHERE id='1'")->fetch_assoc();
$settings['user_info'] = $db->query("SELECT id, avatar, name, famale, surname, phone FROM users WHERE id='" . $settings['last_user_update'] . "'")->fetch_assoc();
$buh = $db->query("SELECT id, name, famale, phone, surname, avatar FROM users WHERE is_buh ='1'");
$settings['buh_info'] = array();
while ($buhd = $buh->fetch_assoc()) {
    $settings['buh_info'][] = $buhd;
}
echo json_encode(
    array(
        "type" => true,
        "data" => $settings,
    ),
    JSON_UNESCAPED_UNICODE
);
?>