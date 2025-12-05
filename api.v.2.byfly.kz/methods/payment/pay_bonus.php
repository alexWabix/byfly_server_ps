<?php
try {
    if (empty($_POST['user_id']) == false && empty($_POST['order_id']) == false) {
        $searchUserDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'");
        if ($searchUserDB->num_rows > 0) {
            $searchUser = $searchUserDB->fetch_assoc();
            $orderSearchDB = $db->query("SELECT * FROM order_tours WHERE id='" . $_POST['order_id'] . "'");
            if ($orderSearchDB->num_rows > 0) {
                $orderSearch = $orderSearchDB->fetch_assoc();
                $orderSumDopPrice = $db->query("SELECT SUM(summ) as ct FROM order_dop_pays WHERE order_id='" . $_POST['order_id'] . "'")->fetch_assoc()['ct'];

                $obshPrice = $orderSearch['price'];

                if ($orderSumDopPrice != null) {
                    $obshPrice = $obshPrice + $orderSumDopPrice;
                }
                $obshPriceForBonus = $obshPrice - $orderSearch['includesPrice'];

                $includePrice = 0;

                if ($searchUser['bonus'] > 0) {
                    if ($obshPriceForBonus > $searchUser['bonus']) {
                        $orderSearch['includesPrice'] = $orderSearch['includesPrice'] + $searchUser['bonus'];
                        $searchUser['bonus'] = 0;
                        $includePrice = $searchUser['bonus'];
                    } else {
                        $operation = $searchUser['bonus'] - $obshPriceForBonus;
                        $searchUser['bonus'] = $operation;
                        $orderSearch['includesPrice'] = $orderSearch['includesPrice'] + $obshPriceForBonus;
                        $includePrice = $obshPriceForBonus;
                    }
                }

                $db->query("UPDATE users SET bonus='" . $searchUser['bonus'] . "' WHERE id='" . $searchUser['id'] . "'");
                $db->query("UPDATE order_tours SET includesPrice='" . $orderSearch['includesPrice'] . "' WHERE id='" . $orderSearch['id'] . "'");
                $db->query("INSERT INTO order_pays (`id`, `order_id`, `summ`, `user_id`, `date_create`, `type`, `tranzaction_id`) VALUES (NULL, '" . $orderSearch['id'] . "', '" . $includePrice . "', '" . $searchUser['id'] . "', CURRENT_TIMESTAMP, 'bonus', '');");


                if ($orderSearch['includesPrice'] >= $obshPrice) {
                    $db->query("UPDATE order_tours SET status_code='3' WHERE id='" . $orderSearch['id'] . "'");
                } else {
                    if ($orderSearch['includesPrice'] >= $orderSearch['predoplata']) {
                        $db->query("UPDATE order_tours SET status_code='2' WHERE id='" . $orderSearch['id'] . "'");
                    }
                }

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
                        "msg" => 'Order not found...',
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            }


        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'User not found...',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Empty user data...',
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