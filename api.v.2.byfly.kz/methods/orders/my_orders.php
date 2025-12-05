<?php
if (empty($_POST['user_id']) == false) {
    $listOne = array();
    $listOrdersDB = $db->query("SELECT * FROM order_tours WHERE user_id='" . $_POST['user_id'] . "'");
    while ($listOrders = $listOrdersDB->fetch_assoc()) {
        $listOrders['tours_info'] = json_decode($listOrders['tours_info'], true);
        array_push($listOne, $listOrders);
    }


    $listTwo = array();
    $lisorderFromPassangerDB = $db->query("SELECT * FROM order_passangers WHERE user_id='" . $_POST['user_id'] . "'");
    while ($lisorderFromPassanger = $lisorderFromPassangerDB->fetch_assoc()) {
        $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $lisorderFromPassanger['order_id'] . "'")->fetch_assoc();
        $orderInfo['tours_info'] = json_decode($orderInfo['tours_info'], true);
        if ($orderInfo['tours_info'] != null) {
            array_push($listTwo, $orderInfo);
        }
    }


    echo json_encode(
        array(
            "type" => true,
            "data" => array(
                "listOne" => $listOne,
                "listTwo" => $listTwo
            ),
        ),
        JSON_UNESCAPED_UNICODE
    );
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