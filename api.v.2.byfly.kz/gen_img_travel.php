<?php
$template = $_GET['template'] ?? 'template_1';
$citys = $_GET['citys'] ?? '60,59';
$countryTo = $_GET['countryTo'] ?? '2';
$userId = $_GET['user_id'] ?? null;
$orientation = $_GET['orientation'] ?? 'portrait';

$width = '1080';
$height = '1920';
if ($orientation == 'landscape') {
    $width = '1920';
    $height = '1920';
}

$url = "https://api.v.2.byfly.kz/image_templates/" . $template . "-" . $orientation . ".php?country_to=" . $countryTo . "&citys=" . $citys;

if ($userId != null) {
    $url = $url . "&user_id=" . $userId;
}
$encodedUrl = urlencode($url);
$apiUrl = "http://localhost:4534/convert?url=" . $encodedUrl . "&width=" . $width . "&height=" . $height;

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0\r\n"
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($apiUrl, false, $context);

if ($result === false) {
    die("Ошибка при получении данных от сервера.");
}

echo $result;
?>