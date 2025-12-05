<?php
try {
    if (empty($_POST['card_id']) == false) {
        if ($db->query("DELETE FROM user_cards WHERE id='" . $_POST['card_id'] . "'")) {
            echo json_encode(
                array(
                    "type" => true,
                    "data" => array(),
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
                "msg" => 'Error empty key data...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} catch (\Throwable $th) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>