<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/js_bot_wa/api/get_info.php');


$resp = file_get_contents('https://tourvisor.ru/xml/list.php?type=hotel&hotcountry=93&authlogin=' . $tourvisor_login . '&authpass=' . $tourvisor_password);
$resp = json_decode($resp, true);



$db->query("TRUNCATE mekka_list_hotels");
foreach ($resp['lists']['hotels']['hotel'] as $hotel) {
    $db->query("INSERT INTO mekka_list_hotels (`id`, `name_hotel`, `id_visor`, `date_create`) VALUES (NULL, '" . $hotel['name'] . "', '" . $hotel['id'] . "', CURRENT_TIMESTAMP);");
}
?>