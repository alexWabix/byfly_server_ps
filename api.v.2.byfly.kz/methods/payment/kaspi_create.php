<?php
try {
    $groupId = 0;
    if ($_POST['group_id'] != 'none') {
        $groupId = $_POST['group_id'];
    }
    $searchOrderDB = $db->query("SELECT * FROM order_kaspi_pays WHERE order_id='" . $_POST['order_id'] . "' AND summ='" . $_POST['summ'] . "' AND user_id='" . $_POST['user_id'] . "' AND type='" . $_POST['type'] . "' AND group_id='" . $groupId . "'");
    if ($searchOrderDB->num_rows > 0) {
        $searchOrder = $searchOrderDB->fetch_assoc();
        echo json_encode(
            array(
                "type" => true,
                "data" => $searchOrder['id'],
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        if (
            $db->query("INSERT INTO order_kaspi_pays (
            `id`, 
            `order_id`, 
            `summ`,
            `user_id`, 
            `date_create`, 
            `date_sended_pay`,
            `tranzaction_number`,
            `type`,
            `group_id`
        ) VALUES (
            NULL, 
            '" . $_POST['order_id'] . "', 
            '" . $_POST['summ'] . "', 
            '" . $_POST['user_id'] . "', 
            CURRENT_TIMESTAMP, 
            NULL,
            '',
            '" . $_POST['type'] . "',
            '" . $groupId . "'
        );")
        ) {
            echo json_encode(
                array(
                    "type" => true,
                    "data" => $db->insert_id,
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