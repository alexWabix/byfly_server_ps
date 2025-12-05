<?php
if (empty($_POST['media_id']) == false && empty($_POST['user_id']) == false) {
    $media = $db->query("SELECT * FROM order_media WHERE id='" . $_POST['media_id'] . "'")->fetch_assoc();
    if ($media['user_id'] == $_POST['user_id']) {
        $db->query("DELETE FROM order_media WHERE id='" . $_POST['media_id'] . "'");
        echo json_encode(
            array(
                "type" => true,
                "data" => '',
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error permission for user...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty data for media...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>