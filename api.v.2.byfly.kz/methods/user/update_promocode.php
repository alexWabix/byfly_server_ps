<?php
if (empty($_POST['user_id']) == false && empty($_POST['promo_code']) == false) {
    if ($db->query("UPDATE users SET promo_code='" . $_POST['promo_code'] . "' WHERE id='" . $_POST['user_id'] . "'")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => 'Promocode updated...',
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