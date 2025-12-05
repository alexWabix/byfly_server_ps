<?php
if (empty($_POST['user_id']) == false && empty($_POST['password']) == false) {
    if ($db->query("UPDATE users SET password='" . $_POST['password'] . "' WHERE id='" . $_POST['user_id'] . "'")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => 'Password updated...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $db->error,
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty data...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>