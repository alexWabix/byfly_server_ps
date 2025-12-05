<?php
if (empty($_POST['user_id']) == false && empty($_POST['promocode']) == false) {
    $searchPromocode = $db->query("SELECT * FROM users WHERE promo_code='" . $_POST['promocode'] . "'");
    if ($searchPromocode->num_rows > 0) {
        echo json_encode(
            array(
                "type" => false,
                "data" => 'This promocode added from user...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => true,
                "data" => 'Success check promocode.',
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