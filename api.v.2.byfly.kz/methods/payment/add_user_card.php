<?php
try {
    if (empty($_POST['cryptogram']) == false && empty($_POST['card_number']) == false && empty($_POST['card_date']) == false && empty($_POST['card_cvv']) == false && empty($_POST['user_id']) == false) {
        if (
            $db->query("INSERT INTO user_cards (
                `id`, 
                `card_number`, 
                `card_date`, 
                `card_cvv`, 
                `card_cryptogram`, 
                `date_create`, 
                `status`, 
                `amount_verified`, 
                `verifed_return`, 
                `user_id`, 
                `tranzaction_id_verified`) 
                VALUES (
                    NULL, 
                    '" . $_POST['card_number'] . "', 
                    '" . $_POST['card_date'] . "', 
                    '" . $_POST['card_cvv'] . "', 
                    '" . $_POST['cryptogram'] . "', 
                    CURRENT_TIMESTAMP, '1', 
                    '" . $_POST['amount_verified'] . "', 
                    '0', '" . $_POST['user_id'] . "', 
                    '" . $_POST['tranzaction_id'] . "');
            ")
        ) {
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
                "msg" => 'Not load card data...',
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