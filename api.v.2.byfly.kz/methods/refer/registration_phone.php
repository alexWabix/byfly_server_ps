<?php
function digitToWord($digit)
{
    $digits = [
        '0' => getTextTranslate(78),
        '1' => getTextTranslate(79),
        '2' => getTextTranslate(80),
        '3' => getTextTranslate(81),
        '4' => getTextTranslate(82),
        '5' => getTextTranslate(83),
        '6' => getTextTranslate(84),
        '7' => getTextTranslate(85),
        '8' => getTextTranslate(86),
        '9' => getTextTranslate(87),
    ];
    return $digits[$digit];
}


if (empty($_POST['phoneNumber']) == false && empty($_POST['agentId']) == false) {
    $search_phone_db = $db->query("SELECT * FROM referal WHERE user_phone='" . $_POST['phoneNumber'] . "'");
    $search_phone_users = $db->query("SELECT * FROM users WHERE phone='" . $_POST['phoneNumber'] . "'");

    if ($search_phone_db->num_rows == 0 && $search_phone_users->num_rows == 0) {
        $randomNumber = rand(100000, 999999);
        $numberWithHyphens = substr($randomNumber, 0, 3) . '-' . substr($randomNumber, 3);
        $numberAsWords = '';

        $numberArray = str_split($randomNumber);
        foreach ($numberArray as $digit) {
            $numberAsWords .= digitToWord($digit) . ' ... ';
        }


        $text = getTextTranslate(75) . " $numberWithHyphens.\n\n" . getTextTranslate(76) . "\n\nwww.byfly.kz";
        $textAudio = getTextTranslate(76) . " " . getTextTranslate(75) . ' ' . $numberAsWords;
        $textAudio = $textAudio . " ... " . getTextTranslate(77) . " ... " . $numberAsWords;
        $textAudio = $textAudio . " ... " . getTextTranslate(77) . " ... " . $numberAsWords;
        $sended = checkNumberFromWhatsapp($_POST['phoneNumber']);

        if ($sended['type']) {
            $sendMessage = sendWhatsapp($_POST['phoneNumber'], $text);
            if ($sendMessage['type']) {
                $resp = array(
                    "type" => true,
                    "data" => array(
                        "code" => $randomNumber,
                        "tipical" => 'whatsapp',
                    ),
                );
                echo json_encode($resp, JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                $sendCalledOffer = sendCall($_POST['phoneNumber'], $textAudio);
                if ($sendCalledOffer['type']) {
                    $resp = array(
                        "type" => true,
                        "data" => array(
                            "code" => $randomNumber,
                            "tipical" => 'audio',
                        ),
                    );
                    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    $resp = array(
                        "type" => false,
                        "msg" => getTextTranslate(71),
                    );
                    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        } else {
            $sendCalledOffer = sendCall($_POST['phoneNumber'], $textAudio);
            if ($sendCalledOffer['type']) {
                $resp = array(
                    "type" => true,
                    "data" => array(
                        "code" => $randomNumber,
                        "tipical" => 'audio',
                    ),
                );
                echo json_encode($resp, JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                $resp = array(
                    "type" => false,
                    "msg" => getTextTranslate(72),
                );
                echo json_encode($resp, JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    } else {
        $resp = array(
            "type" => false,
            "test" => $_POST['phoneNumber'],
            "msg" => getTextTranslate(73),
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    $resp = array(
        "type" => false,
        "msg" => getTextTranslate(74),
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
?>