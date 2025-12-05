<?php
if (empty($_POST['user_id']) == false and empty($_POST['event_id']) == false) {
    if ($db->query("INSERT INTO event_user (`id`, `user_id`, `event_id`) VALUES (NULL, '" . $_POST['user_id'] . "', '" . $_POST['event_id'] . "')")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $db->insert_id,
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $db->error
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Отсуствуют данные... (ID пользователя или ID мероприятия)'
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>