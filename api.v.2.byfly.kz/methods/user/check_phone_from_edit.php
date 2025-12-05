<?php
if (empty($_POST['user_id']) == false && empty($_POST['phone']) == false) {
    $searchPhoneDB = $db->query("SELECT * FROM users WHERE phone='" . preg_replace('/\D/', '', $_POST['phone']) . "'");
    if ($searchPhoneDB->num_rows > 0) {
        echo json_encode(
            array(
                "type" => false,
                "data" => 'This number added from user...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => true,
                "data" => 'Success check number phone.',
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