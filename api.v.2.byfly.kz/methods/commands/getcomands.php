<?php
if (empty($_POST['id']) == false) {
    echo json_encode(
        array(
            "type" => true,
            "data" => getUserComandsInfo($_POST['id'], $_POST['date_from'], $_POST['date_to']),
        ),
        JSON_UNESCAPED_UNICODE,
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Dont send user id..."
        ),
        JSON_UNESCAPED_UNICODE,
    );
}
?>