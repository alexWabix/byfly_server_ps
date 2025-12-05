<?php
function checkVideoGenerate($id)
{
    global $genVideoApi;
    $videoId = $id;
    $apiUrl = "https://ext.videogen.io/v1/get-file?apiFileId=" . urlencode($videoId);
    $apiKey = $genVideoApi;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

echo json_encode(
    array(
        "type" => false,
        "msg" => "Нет ID загпущенной генерации"
    ),
    JSON_UNESCAPED_UNICODE,
);
?>