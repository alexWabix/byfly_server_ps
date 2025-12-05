<?php
$searchUserDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['userId'] . "'");
if ($searchUserDB->num_rows > 0) {
    $searchUser = $searchUserDB->fetch_assoc();
    if ($db->query("UPDATE users SET password='" . md5($_POST['newPassword']) . "' WHERE id='" . $_POST['userId'] . "'")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => getUserInfoFromID($searchUser['id']),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Не удалось изменить пароль!',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Пользователь не найден в системе!',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>