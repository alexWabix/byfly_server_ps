<?php
$code = isset($_POST['code']) ? trim($_POST['code']) : null;

if (!$code) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Код сертификата не указан.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$searchVaucherDB = $db->query("SELECT * FROM vauchers WHERE number='" . $db->real_escape_string($code) . "' AND type='" . $_POST['type'] . "'");

if ($searchVaucherDB->num_rows > 0) {
    $searchVaucher = $searchVaucherDB->fetch_assoc();
    if (strtotime($searchVaucher['date_off']) < time()) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Срок действия сертификата уже истек.",
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        if ($searchVaucher['activated'] == 0) {
            if ($searchVaucher['summ'] < $_POST['price']) {
                echo json_encode(
                    array(
                        "type" => false,
                        "msg" => "Сумма сертификата не достаточна для оплаты данного обучения. Лимит по сертификату составляет " . $searchVaucher['summ'] . " тенге.",
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            } else {
                echo json_encode(
                    array(
                        "type" => true,
                        "data" => $searchVaucher,
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            }

        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => "Данный сертификат уже активирован!",
                ),
                JSON_UNESCAPED_UNICODE
            );
        }

    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Сертификат не найден или неверный код.",
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>