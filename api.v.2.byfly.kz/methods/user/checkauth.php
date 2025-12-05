<?php
if (empty($_POST['login']) == false && empty($_POST['password']) == false) {
    $searchUserDB = $db->query("SELECT * FROM users WHERE phone='" . $db->real_escape_string($_POST['login']) . "'");

    if ($searchUserDB->num_rows > 0) {
        $searchUser = $searchUserDB->fetch_assoc();

        if ($searchUser['password'] == $_POST['password']) {
            setcookie('user_id', $searchUser['id'], time() + (86400 * 365), "/");
            echo json_encode(
                array(
                    "type" => true,
                    "data" => array(
                        "user_info" => getUserInfoFromID($searchUser['id']),
                    ),
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Password error...',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Not user registered...',
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