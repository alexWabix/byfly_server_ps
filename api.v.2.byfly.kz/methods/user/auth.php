<?php
if (empty($_POST['login']) == false and empty($_POST['password']) == false) {
    $searchUserDB = $db->query("SELECT * FROM users WHERE phone='" . $_POST['login'] . "'");
    if ($searchUserDB->num_rows > 0) {
        $searchUser = $searchUserDB->fetch_assoc();
        if ($searchUser['password'] == md5($_POST['password'])) {
            if ($searchUser['parent_user'] == null) {
                if (empty($_POST['myAgentCash']) == false) {
                    $searchAgent = $db->query("SELECT * FROM users WHERE id='" . $_POST['myAgentCash'] . "'");
                    if ($searchAgent->num_rows > 0) {
                        $searchAgent = $searchAgent->fetch_assoc();
                        if ($searchAgent['blocked_to_time'] == null) {
                            $db->query("UPDATE users SET parent_user='" . $_POST['myAgentCash'] . "' WHERE id='" . $searchUser['id'] . "'");
                        }
                    }

                }
            }
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