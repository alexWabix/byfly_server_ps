<?php
if (empty($_POST['user_phone']) == false && empty($_POST['user_id']) == false && empty($_POST['user_name']) == false && empty($_POST['user_famale']) == false && empty($_POST['user_surname']) == false) {
    if ($db->query("UPDATE users SET adress='" . $_POST['user_adress'] . "', email='" . $_POST['user_email'] . "', name='" . $_POST['user_name'] . "', famale='" . $_POST['user_famale'] . "', surname = '" . $_POST['user_surname'] . "', phone='" . $_POST['user_phone'] . "' WHERE id='" . $_POST['user_id'] . "'")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => 'Data updated...',
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