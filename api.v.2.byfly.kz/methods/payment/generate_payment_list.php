<?php
if (empty($_POST['reqs']) == false && empty($_POST['bank']) == false) {
    $_POST['reqs_to'] = json_decode($_POST['reqs'], true);
    $_POST['bank_to'] = json_decode($_POST['bank'], true);
    $_POST['order_info_to'] = json_decode($_POST['order_info'], true);


    $db->query("INSERT INTO order_banked_bill (`id`, `order_id`, `user_id`, `company_info`, `bank_info`, `order_info`, `isPredoplata`, `date_create`, `date_pay`) 
    VALUES (NULL, '" . $_POST['order_info_to']['id'] . "', '" . $_POST['order_info_to']['user_id'] . "', '" . $_POST['reqs'] . "', '" . $_POST['bank'] . "', '" . $_POST['order_info'] . "', '" . $_POST['predoplata'] . "', CURRENT_TIMESTAMP, NULL);");

    $schetNumber = $db->insert_id;

    $orderInfo = '';
    if ($_POST['predoplata'] == '1' || $_POST['predoplata'] == 1) {
        $orderInfo = 'Предоплата за: ';
    } else {
        $orderInfo = 'Оплата за: ';
    }

    $orderInfo .= 'Заказ №' . $_POST['order_info_to']['id'] . ', от ' . $_POST['order_info_to']['date_create'];

    $resp = http_build_query(
        array(
            "number" => $schetNumber,
            "my_iik" => $_POST['bank_to']['iik'],
            "my_bank_name" => $_POST['bank_to']['name'],
            "my_bank_bik" => $_POST['bank_to']['bik'],
            "my_adres" => $_POST['bank_to']['ur_adress'],
            "date_schet" => Date('Y-m-d H:i:s'),
            "company_bin" => $_POST['reqs_to']['bin_iin'],
            "company_too" => $_POST['reqs_to']['company_name'],
            "company_adress" => $_POST['reqs_to']['company_adress'],
            "order_info" => $orderInfo,
            "order_price" => $_POST['price'],
        ),
    );


    $document_html = file_get_contents('https://api.v.2.byfly.kz/methods/payment/order_chet.php?' . $resp);
    if ($document_html) {
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "html" => $document_html,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error generate schet...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }





} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty data for payments...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>