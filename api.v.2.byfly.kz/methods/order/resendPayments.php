<?php
try {
    if (empty($_POST['orderId']) == false) {
        $orderInfoDB = $db->query("SELECT * FROM order_tours WHERE id='" . $_POST['orderId'] . "'")->fetch_assoc();
        if ($orderInfoDB['payments'] == 1 || $orderInfoDB['payments'] == 2) {
            $orderInfoDB['price'] = json_decode($orderInfoDB['tours_info'], true)['price'];
        }



        if ($_POST['newPayment'] == 1 || $_POST['newPayment'] == 2) {
            if ($_POST['newPayment'] == 1) {
                $orderInfoDB['price'] += ceil((($orderInfoDB['price'] - $orderInfoDB['includesPrice']) / 100) * 7);
            } else {
                $orderInfoDB['price'] += ceil((($orderInfoDB['price'] - $orderInfoDB['includesPrice']) / 100) * 15);
            }

            $listDopPaysDB = $db->query("SELECT * FROM order_dop_pays WHERE order_id='" . $_POST['orderId'] . "'");
            while ($listDopPays = $listDopPaysDB->fetch_assoc()) {
                if ($_POST['newPayment'] == 1) {
                    $db->query("UPDATE order_dop_pays SET percentage = '7' WHERE id='" . $listDopPays['id'] . "'");
                } else if ($_POST['newPayment'] == 2) {
                    $db->query("UPDATE order_dop_pays SET percentage = '15' WHERE id='" . $listDopPays['id'] . "'");
                } else {
                    $db->query("UPDATE order_dop_pays SET percentage = '0' WHERE id='" . $listDopPays['id'] . "'");
                }
            }
        } else {
            $listDopPaysDB = $db->query("SELECT * FROM order_dop_pays WHERE order_id='" . $_POST['orderId'] . "'");
            while ($listDopPays = $listDopPaysDB->fetch_assoc()) {
                $db->query("UPDATE order_dop_pays SET percentage = '0' WHERE id='" . $listDopPays['id'] . "'");
            }
        }


        if ($db->query("UPDATE order_tours SET payments='" . $_POST['newPayment'] . "', price='" . $orderInfoDB['price'] . "', bonusPay='" . $_POST['isBonusPays'] . "' WHERE id='" . $_POST['orderId'] . "'")) {
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
                    "msg" => 'Error data base update information.',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }


    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Empty data for update order.',
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