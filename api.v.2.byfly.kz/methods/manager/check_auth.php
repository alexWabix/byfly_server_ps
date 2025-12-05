<?php
if (empty($_POST['login']) == false && empty($_POST['password']) == false) {
    $_POST['login'] = preg_replace('/\D/', '', $_POST['login']);
    $searchManager = $db->query("SELECT * FROM managers WHERE phone_call = '" . $_POST['login'] . "'");
    if ($searchManager->num_rows > 0) {
        $searchManager = $searchManager->fetch_assoc();

        if ($searchManager['password'] == md5($_POST['password'])) {
            echo json_encode(
                array(
                    "type" => true,
                    "data" => array(
                        "user_info" => $searchManager,
                    )
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Указанный пароль не совпадает...',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Преподаватель с таким телефоном не существует...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Не указан логин или пароль...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>