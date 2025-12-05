<?php

$couch = $_POST['bal'] >= 75 ? 1 : 0;
if ($db->query("UPDATE users SET start_test = '" . $_POST['bal'] . "', for_couch=" . $couch . " WHERE id='" . $_POST['user_id'] . "'")) {
    echo json_encode(
        array(
            "type" => true,
            "msg" => 'Atestation Seted',
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Not set atestation bal... ' . $db->error,
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>