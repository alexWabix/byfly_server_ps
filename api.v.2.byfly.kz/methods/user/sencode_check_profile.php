<?php

function digitToWord($digit)
{
    $digits = [
        '0' => 'ноль',
        '1' => 'один',
        '2' => 'два',
        '3' => 'три',
        '4' => 'четыре',
        '5' => 'пять',
        '6' => 'шесть',
        '7' => 'семь',
        '8' => 'восемь',
        '9' => 'девять',
    ];
    return $digits[$digit];
}


if (empty($_POST['user_id']) == false && empty($_POST['phone']) == false) {
    $randomNumber = rand(100000, 999999);
    $numberWithHyphens = substr($randomNumber, 0, 3) . '-' . substr($randomNumber, 3);
    $numberAsWords = '';

    $numberArray = str_split($randomNumber);
    foreach ($numberArray as $digit) {
        $numberAsWords .= digitToWord($digit) . ' ... ';
    }
    $textWhats = "Код подтверждения: " . $numberWithHyphens . ".\r\n\r\nЭто ваш код для подтверждения смены пароля. Пожалуйста не сообщайте никому данный код и используйте его сразу для смены вашего пароля...";
    $textAudio = 'Код подтверждения смены пароля в приложении ByFly Travel: ' . $numberAsWords . '.... Это ваш код для подтверждения смены пароля. Пожалуйста не сообщайте никому данный код и используйте его сразу для смены вашего пароля... Повтор кода: ' . $numberAsWords;

    $sendCode = sendCode($textWhats, $textAudio, $_POST['phone']);

    echo json_encode(
        array(
            "type" => true,
            "data" => array(
                "code" => $randomNumber,
                "resp" => $sendCode,
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