<?php
if (empty($_POST['id']) == false) {
    // Создаем объект DateTime для текущей даты и времени
    $date = new DateTime();
    // Добавляем 90 дней
    $date->modify('+90 days');
    // Форматируем дату в нужный формат
    $date_money_send = $date->format('Y-m-d H:i:s');

    // Выполняем обновление в базе данных
    if (
        $db->query("UPDATE copilka_ceils SET `month_1_bonus`='0', `month_2_bonus`='0', `month_3_bonus`='0', `month_4_bonus`='0', `month_5_bonus`='0', `month_6_bonus`='0', `month_7_bonus`='0', `month_8_bonus`='0', `month_9_bonus`='0', `month_10_bonus`='0', `month_11_bonus`='0', `month_12_bonus`='0', `summ_bonus`='0', date_dosrok_close='" . date('Y-m-d H:i:s') . "', date_money_send='" . $date_money_send . "' WHERE id='" . $_POST['id'] . "'")
    ) {
        echo json_encode(
            array(
                "type" => true,
                "msg" => $db->error,
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
            "msg" => 'Отсутствуют данные!',
        ),
        JSON_UNESCAPED_UNICODE
    );
}

?>