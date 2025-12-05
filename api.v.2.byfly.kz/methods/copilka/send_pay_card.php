Тут тоже надо подправить и явно указать что оплата производится платежной картой через JUSAN BANK проверь все и верни
полный код

<?php
if (empty($_POST['ceil_id']) == false && empty($_POST['user_id']) == false && empty($_POST['summ']) == false) {
    $ceilsInfo = $db->query("SELECT * FROM copilka_ceils WHERE id='" . $_POST['ceil_id'] . "'")->fetch_assoc();
    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();

    $month = getNextPaymentMonth($ceilsInfo);

    $ceilsInfo["month_" . $month . "_money"] = $ceilsInfo["month_" . $month . "_money"] + $_POST['summ'];
    $ceilsInfo["month_" . $month . "_bonus"] = $ceilsInfo["month_" . $month . "_bonus"] + $_POST['summ'];

    $ceilsInfo["summ_bonus"] = $ceilsInfo["summ_bonus"] + $_POST['summ'];
    $ceilsInfo["summ_money"] = $ceilsInfo["summ_money"] + $_POST['summ'];

    $db->query("UPDATE copilka_ceils SET summ_bonus='" . $ceilsInfo["summ_bonus"] . "', summ_money='" . $ceilsInfo["summ_money"] . "', month_" . $month . "_money='" . $ceilsInfo["month_" . $month . "_money"] . "', month_" . $month . "_bonus='" . $ceilsInfo["month_" . $month . "_bonus"] . "' WHERE id='" . $ceilsInfo['id'] . "'");
    $db->query("INSERT INTO user_tranzactions (`id`, `date_create`, `summ`, `type_operations`, `user_id`, `pay_info`) VALUES (NULL, CURRENT_TIMESTAMP, '" . $_POST['summ'] . "', '0', '" . $userInfo['id'] . "', 'Пополнение накопительной ячейки (Месячный платеж).');");



    $formattedSum = number_format($_POST['summ'], 0, '.', ' ');

    $message = "Здравствуйте! 👋\n\n";
    $message .= "Поздравляем вас с пополнением накопительной ячейки на сумму: {$formattedSum} KZT 💰.\n\n";
    $message .= "Ваша сумма успешно зачислена и теперь доступна для дальнейшего использования. Мы ценим ваш вклад в систему! 🙏\n\n";
    $message .= "Для проверки и получения подробной информации о балансе, пожалуйста, перейдите в ваш профиль на сайте: www.byfly.kz 🌐.\n\n";
    sendWhatsapp($userInfo['phone'], $message);

    $ceilsInfo = $db->query("SELECT * FROM copilka_ceils WHERE id='" . $_POST['ceil_id'] . "'")->fetch_assoc();
    echo json_encode(
        array(
            "type" => true,
            "data" => $ceilsInfo,
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Пустые данные!',
        ),
        JSON_UNESCAPED_UNICODE
    );
}

function getNextPaymentMonth($ceilInfo)
{
    for ($i = 1; $i <= 12; $i++) {
        $monthColumnMoney = 'month_' . $i . '_money';

        if ($ceilInfo[$monthColumnMoney] < 50000) {
            return $i;
        }
    }
    return 1;
}
?>