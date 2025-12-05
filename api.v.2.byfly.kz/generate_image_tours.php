<?php
$template = $_GET['template'] ?? 'template_1';
$citys = $_GET['citys'] ?? '60,59';
$countryTo = $_GET['countryTo'] ?? '2';
$userId = $_GET['user_id'] ?? null;
$orientation = $_GET['orientation'] ?? 'portrait';

$url = "https://api.v.2.byfly.kz/image_templates/" . $template . "-" . $orientation . ".php?country_to=" . $countryTo . "&citys=" . $citys;

if ($userId != null) {
    $url = $url . "&user_id=" . $userId;
}



echo file_get_contents($url);
?>