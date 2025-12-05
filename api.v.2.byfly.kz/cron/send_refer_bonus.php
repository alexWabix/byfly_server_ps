<?php
header('Content-Type: application/json');

include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$tourId = $_GET['tourid'] ?? '';

$query = array(
    "authlogin" => $tourvisor_login,
    "authpass" => $tourvisor_password,
    "format" => 'json',
    "tourid" => $tourId,
);

$url = 'http://tourvisor.ru/xml/actdetail.php?' . http_build_query($query);

$data = file_get_contents($url);

if ($data === false) {
    echo json_encode(["error" => "Не удалось получить данные"]);
    exit;
}

echo $data;
?>