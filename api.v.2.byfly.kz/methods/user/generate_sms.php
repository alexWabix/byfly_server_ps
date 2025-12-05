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

if (empty($_POST['phone']) == false) {
    $randomNumber = rand(100000, 999999);
    $numberWithHyphens = substr($randomNumber, 0, 3) . '-' . substr($randomNumber, 3);
    $numberAsWords = '';

    $numberArray = str_split($randomNumber);
    foreach ($numberArray as $digit) {
        $numberAsWords .= digitToWord($digit) . ' ... ';
    }


    $textWhatsapp = $numberWithHyphens . " - Код подтверждения регистрации в мобильном приложении ByFly.\r\n\r\n Пожалуйста никому не сообщайте данный код во избежания утраты доступа к учетной записи!";
    $textAudio = '...' . $numberAsWords . " - Код подтверждения регистрации в мобильном приложении ByFly. .... Пожалуйста никому не сообщайте данный код во избежания утраты доступа к учетной записи! ... Повтор: " . $numberAsWords;

    $isWhatsapp = checkNumberFromWhatsapp($_POST['phone']);
    if ($isWhatsapp['type']) {
        $sendMessage = sendWhatsapp($_POST['phone'], $textWhatsapp);
        if (!$sendMessage['type']) {
            $sendMessage = sendCall($_POST['phone'], $textAudio);
        }
    } else {
        $sendMessage = sendCall($_POST['phone'], $textAudio);
    }

    if ($sendMessage['type']) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $randomNumber,
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Не удалось отправить код поддтверждения!',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty phone number...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>