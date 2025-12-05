<?php
$url = 'http://localhost:3453/create-webrtc-stream';

$response = file_get_contents($url);

if ($response === FALSE) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "2Не удалось получить данные! " . $response,
        )
    );
} else {
    $decodeResp = json_decode($response, true);
    if ($decodeResp['success']) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $decodeResp,
            )
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "1Не удалось получить данные! " . $response,
            )
        );
    }
}
?>