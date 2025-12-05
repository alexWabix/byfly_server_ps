<?php
if (!empty($_POST['user_id']) && !empty($_POST['ceil_id']) && !empty($_POST['sertId'])) {
    $user_id = $_POST['user_id'];
    $ceil_id = $_POST['ceil_id'];
    $sertId = $_POST['sertId'];

    $getInfoSert = $db->query("SELECT * FROM vauchers WHERE id='$sertId'")->fetch_assoc();

    if ($getInfoSert && $getInfoSert['date_activated'] === null) {
        $summ = $getInfoSert['summ'];
        $db->query("UPDATE vauchers SET date_activated='" . date('Y-m-d H:i:s') . "', user_activated='$user_id', activated='1' WHERE id='$sertId'");
        $db->query("INSERT INTO user_tranzactions (`id`, `date_create`, `summ`, `type_operations`, `user_id`, `pay_info`) VALUES (NULL, CURRENT_TIMESTAMP, '$summ', '0', '$user_id', 'Пополнение накопительной ячейки (Сертификат)!');");

        $ceil = $db->query("SELECT * FROM copilka_ceils WHERE id='$ceil_id' AND user_id='$user_id'")->fetch_assoc();

        if ($ceil) {
            for ($i = 1; $i <= 12; $i++) {
                if ($ceil["month_{$i}_money"] == 0) {
                    $ceil['summ_bonus'] = $ceil['summ_bonus'] + $summ;
                    $ceil['summ_money'] = $ceil['summ_money'] + $summ;
                    $db->query("UPDATE copilka_ceils SET summ_bonus='" . $ceil['summ_bonus'] . "', summ_money='" . $ceil['summ_money'] . "', month_{$i}_money='$summ', month_{$i}_bonus='$summ' WHERE id='$ceil_id'");
                    break;
                }
            }

            $ceilsInfo = $db->query("SELECT * FROM copilka_ceils WHERE id='" . $_POST['ceil_id'] . "'")->fetch_assoc();

            echo json_encode(
                array(
                    "type" => true,
                    "data" => $ceilsInfo,
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => "Ячейка не найдена или не принадлежит пользователю!",
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Сертификат недействителен или уже активирован!",
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Необходимо указать user_id, ceil_id и sertId!",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>