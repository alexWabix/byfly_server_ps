<?php
if (empty($_POST['user_id']) == false && empty($_POST['summ_zp']) == false) {
    if ($_POST['summ_zp'] <= 20000) {
        if ($db->query("UPDATE users SET price_of_agent='" . $_POST['summ_zp'] . "' WHERE id='" . $_POST['user_id'] . "'")) {
            echo json_encode([
                "type" => true,
                "data" => array(),
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "type" => false,
                "msg" => $db->error,
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            "type" => false,
            "msg" => "Стоимость за одного привлеченного не может быть более 20 000 тенге!"
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        "type" => false,
        "msg" => "Не указаны данные!"
    ], JSON_UNESCAPED_UNICODE);
}
?>