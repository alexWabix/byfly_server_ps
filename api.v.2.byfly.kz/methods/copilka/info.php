<?php
$resp = array(
    "countCeils" => 0,
    "summCeils" => 0,
    "dostupCeils" => 0,
    "openDostup" => true,
);

$settings = $db->query("SELECT * FROM app_settings WHERE id='1'")->fetch_assoc();


$countCeils = $db->query("SELECT COUNT(*) as ct FROM copilka_ceils")->fetch_assoc()['ct'];
$resp['countCeils'] = $countCeils != null ? $countCeils : 0;


$summCeils = $db->query("SELECT SUM(summ_money) as ct FROM copilka_ceils")->fetch_assoc()['ct'];
$resp['summCeils'] = $summCeils != null ? $summCeils : 0;


$resp['dostupCeils'] = $settings['max_copilka_ceils'] - $countCeils;
if ($resp['dostupCeils'] == 0 || $resp['dostupCeils'] < 0) {
    $resp['openDostup'] = false;
}



$resp['countCeils'] = $resp['countCeils'] . '/' . $settings['max_copilka_ceils'];

echo json_encode(
    array(
        "type" => true,
        "data" => $resp,
    ),
    JSON_UNESCAPED_UNICODE
);
?>