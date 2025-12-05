<?php
function getVoicess()
{
    $apiKey = "D6UYDING5R5b3iXVYYLlL-NXemXHyBtaO2B6Ou6Cdl8";
    $getVideoUrl = "https://viralapi.vadoo.tv/api/get_voices";

    $ch = curl_init("$getVideoUrl");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $apiKey"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $videoData = json_decode($response, true);

    return $videoData;
}

$listVocess = getVoicess();
if (empty($listVocess) == false) {
    foreach ($listVocess as $voicess) {
        echo $voicess . '<br>';
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Error get listed voicess..."
        ),
        JSON_UNESCAPED_UNICODE
    );
}

?>