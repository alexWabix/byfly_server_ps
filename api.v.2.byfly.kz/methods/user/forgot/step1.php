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

try {
    $getUserInfoDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['userId'] . "'");
    if ($getUserInfoDB->num_rows > 0) {
        $getUserInfo = $getUserInfoDB->fetch_assoc();
        $checkWhatsApp = checkNumberFromWhatsapp($getUserInfo['phone']);

        $randomNumber = rand(100000, 999999);
        $numberWithHyphens = substr($randomNumber, 0, 3) . '-' . substr($randomNumber, 3);
        $numberAsWords = '';

        $numberArray = str_split($randomNumber);
        foreach ($numberArray as $digit) {
            $numberAsWords .= digitToWord($digit) . ' ... ';
        }

        $textWhats = "Код подтверждения: " . $numberWithHyphens . ".\r\n\r\nЭто ваш код для подтверждения смены пароля. Пожалуйста не сообщайте никому данный код и используйте его сразу для смены вашего пароля...";
        $textAudio = 'Код подтверждения смены пароля в приложении ByFly Travel: ' . $numberAsWords . '.... Это ваш код для подтверждения смены пароля. Пожалуйста не сообщайте никому данный код и используйте его сразу для смены вашего пароля... Повтор кода: ' . $numberAsWords;


        if ($checkWhatsApp['type']) {
            $sendFunc = sendWhatsapp($getUserInfo['phone'], $textWhats);
            if (!$sendFunc['type']) {
                $sendFunc = sendCall($phone, $textAudio);
            }
        } else {
            $sendFunc = sendCall($phone, $textAudio);
        }

        if ($sendFunc['type']) {
            echo json_encode(
                array(
                    "type" => true,
                    "data" => array(
                        "code" => $randomNumber,
                        "type" => $sendFunc['tipical'],
                    ),
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Dont sended code request...',
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Dont user from id...',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
} catch (\Throwable $th) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>