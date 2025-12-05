<?php
if ($_POST['going'] == '1' && $_POST['going'] == 1) {
    if ($db->query("INSERT INTO present_event_users (`id`, `event_id`, `user_id`, `date_create`) VALUES (NULL, '" . $_POST['event_id'] . "', '" . $_POST['user_id'] . "', CURRENT_TIMESTAMP);")) {
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();
        echo json_encode(
            [
                "type" => true,
                "data" => $userInfo,
            ],
            JSON_UNESCAPED_UNICODE,
        );
    } else {
        echo json_encode(
            [
                "type" => false,
                "msg" => $db->error,
            ],
            JSON_UNESCAPED_UNICODE,
        );
    }
} else {
    if ($db->query("DELETE FROM present_event_users WHERE event_id='" . $_POST['event_id'] . "' AND user_id='" . $_POST['user_id'] . "'")) {
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();
        echo json_encode(
            [
                "type" => true,
                "data" => $userInfo,
            ],
            JSON_UNESCAPED_UNICODE,
        );
    } else {
        echo json_encode(
            [
                "type" => false,
                "msg" => $db->error,
            ],
            JSON_UNESCAPED_UNICODE,
        );
    }
}
?>