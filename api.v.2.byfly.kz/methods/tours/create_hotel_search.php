<?php
$_POST['data'] = json_decode($_POST['data'], true);


if (empty($_POST['data']['hotels']) == false) {
    $query = array(
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "departure" => 99,
        "country" => $_POST['data']['to']['country_id'],
        "datefrom" => $_POST['data']['startDate'],
        "dateto" => $_POST['data']['endDate'],
        "nightsfrom" => $_POST['data']['nigtsFrom'],
        "nightsto" => $_POST['data']['nigthTo'],
        "adults" => $_POST['data']['adult'],
        "hotels" => $_POST['data']['hotels'],
        "child" => count($_POST['data']['children']),
        "meal" => $_POST['data']['dict'],
        "mealbetter " => $_POST['data']['dict_beetter'],
        "regions" => implode(',', $_POST['data']['to']['citys']),
        "currency" => 3,
        "hideregular" => 0,
    );
} else {
    $query = array(
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "departure" => 99,
        "country" => $_POST['data']['to']['country_id'],
        "datefrom" => $_POST['data']['startDate'],
        "dateto" => $_POST['data']['endDate'],
        "nightsfrom" => $_POST['data']['nigtsFrom'],
        "nightsto" => $_POST['data']['nigthTo'],
        "adults" => $_POST['data']['adult'],
        "child" => count($_POST['data']['children']),
        "stars" => $_POST['data']['hotel'],
        "starsbetter" => $_POST['data']['hotel_beetter'],
        "meal" => $_POST['data']['dict'],
        "mealbetter " => $_POST['data']['dict_beetter'],
        "regions" => implode(',', $_POST['data']['to']['citys']),
        "currency" => 3,
        "hideregular" => 0,
    );
}


$count = 0;
foreach ($_POST['data']['children'] as $age) {
    $count = $count + 1;
    $query['childage' . $count] = $age;
}
$url = 'https://tourvisor.ru/xml/search.php?' . http_build_query($query);
$data = file_get_contents($url);

$resp = json_decode($data, true);

if ($resp) {
    echo json_encode(
        array(
            "type" => true,
            "data" => $resp,
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error send data respoce...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}

exit;
?>