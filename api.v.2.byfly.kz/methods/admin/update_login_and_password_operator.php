<?php
if ($db->query("UPDATE operators SET login='" . $_POST['new_login'] . "', password='" . $_POST['new_password'] . "', real_link='" . $_POST['real_link'] . "' WHERE id='" . $_POST['id'] . "'")) {
    echo json_encode(
        array(
            "type" => true,
            "data" => "Данные обновлены!"
        ),
        JSON_UNESCAPED_UNICODE,
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $db->error
        ),
        JSON_UNESCAPED_UNICODE,
    );
}


?>