<?php
if (empty($_POST['phone']) == false && empty($_POST['mess']) == false) {
    $checkWhatsApp = checkNumberFromWhatsapp($_POST['phone']);
    $sended = false;
    $errMsg = '';
    if ($checkWhatsApp['type']) {
        $sendFunc = sendWhatsapp($_POST['phone'], $_POST['mess']);
        if ($sendFunc['type']) {
            $sended = true;
        } else {
            $sended = false;
            $errMsg = $sendFunc['msg'];
        }
    } else {
        $sended = false;
        $errMsg = $checkWhatsApp['msg'];
    }

    if ($sended) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $sendFunc,
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $errMsg,
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty messages data...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>