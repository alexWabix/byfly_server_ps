<?php

if (!isset($_GET['video_id'])) {
    die("Ошибка: video_id не указан.");
}

$videoId = $_GET['video_id'];
$apiUrl = "https://ext.videogen.io/v1/get-file?apiFileId=" . urlencode($videoId);
$apiKey = "bdf68a7b497d535a50f9e5aa3b7cd56e23b7ad80";

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

echo $response;
