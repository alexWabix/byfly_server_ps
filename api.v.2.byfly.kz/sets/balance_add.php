<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$statuses = [
    '0' => 'Новая в обработке',
    '1' => 'Подтверждена - Требуется предоплата',
    '2' => 'Подтверждена - Требуется полная оплата',
    '3' => 'Полностью оплачена ожидает вылета',
    '4' => 'Турист на отдыхе',
    '5' => 'Отменена'
];


$toursDB = $db->query("SELECT * FROM `order_tours` WHERE status_code != '5' AND type != 'test'");
while ($tours = $toursDB->fetch_assoc()) {
    $dopPays = '';
    $searchDopPaysDB = $db->query("SELECT * FROM order_dop_pays WHERE order_id='" . $tours['id'] . "'");
    while ($searchDopPays = $searchDopPaysDB->fetch_assoc()) {
        $dopPays .= ' ---- ' . $searchDopPays['desc_pay'] . ' - ' . $searchDopPays['summ'] . '<br>';
    }

    $salerSummNakrutka = ceil(($tours['includesPrice'] / 100) * $tours['nakrutka']);

    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $tours['user_id'] . "'")->fetch_assoc();
    $parentUserInfo = $db->query("SELECT * FROM users WHERE id='" . $userInfo['parent_user'] . "'")->fetch_assoc(); // Пользователь который пригласил

    $userLine1 = $parentUserInfo; // Первая линия
    $userLine2 = $db->query("SELECT * FROM users WHERE id='" . $userLine1['parent_user'] . "'")->fetch_assoc(); // Вторая линия
    $userLine3 = $db->query("SELECT * FROM users WHERE id='" . $userLine2['parent_user'] . "'")->fetch_assoc(); // Третья линия
    $userLine4 = $db->query("SELECT * FROM users WHERE id='" . $userLine3['parent_user'] . "'")->fetch_assoc(); // Четвертая линия
    $userLine5 = $db->query("SELECT * FROM users WHERE id='" . $userLine4['parent_user'] . "'")->fetch_assoc(); // Пятая линия


    echo 'Дата создания: ' . $tours['date_create'] . ', Дата вылета: ' . $tours['flyDate'] . ', Статус: ' . $statuses[$tours['status_code']] . ', Накрутка: ' . $tours['nakrutka'] . '%, Стоимость: ' . $tours['price'] . ', Внесено: ' . $tours['includesPrice'] . ', Реальная стоимость: ' . $tours['real_price'] . ', Минус для компании по туру: ' . ($tours['real_price'] - $tours['includesPrice']) . ', Сумма по накрутке: ' . $salerSummNakrutka . '<br>' . $dopPays . '<br><br>';

    if ($userInfo['saler_id'] == 0) {
        $nakrutka = getUserParams($userInfo['id']);
    } else {
        $nakrutka = getUserParams($userInfo['saler_id']);
    }
    $line1 = getUserParams($userLine1['id']);
    $line2 = getUserParams($userLine2['id']);
    $line3 = getUserParams($userLine3['id']);
    $line4 = getUserParams($userLine4['id']);
    $line5 = getUserParams($userLine5['id']);

    echo ' ----------- Накрутка ' . $tours['nakrutka'] . '% получает ' . $nakrutka['name_user'] . '<br>';
    if ($line1['work_lines']['line1']) {
        echo ' ----------- 1 линия получает ' . $line1['percentage']['line1'] . '% получает ' . $line1['name_user'] . '<br>';
    }


    if ($line2['work_lines']['line2']) {
        echo ' ----------- 2 линия получает ' . $line2['percentage']['line2'] . '% получает ' . $line2['name_user'] . '<br>';
    }

    if ($line3['work_lines']['line3']) {
        echo ' ----------- 3 линия получает ' . $line2['percentage']['line3'] . '% получает ' . $line3['name_user'] . '<br>';
    }


    if ($line4['work_lines']['line4']) {
        echo ' ----------- 4 линия получает ' . $line2['percentage']['line4'] . '% получает ' . $line4['name_user'] . '<br>';
    }


    if ($line5['work_lines']['line5']) {
        echo ' ----------- 5 линия получает ' . $line2['percentage']['line5'] . '% получает ' . $line5['name_user'] . '<br>';
    }


    echo '<br><br>';
}





?>