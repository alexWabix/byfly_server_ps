<?php
$dir = '/var/www/www-root/data/www/byfly.kz/';
include ($dir . 'config/db.php');


$proxy = '213.139.221.193:8000:xjWBJN:MUDe7p
213.139.221.194:8000:xjWBJN:MUDe7p
213.139.221.6:8000:xjWBJN:MUDe7p
194.32.251.162:8000:xjWBJN:MUDe7p
194.32.251.105:8000:xjWBJN:MUDe7p
194.32.251.24:8000:xjWBJN:MUDe7p
194.32.251.100:8000:xjWBJN:MUDe7p
194.32.251.220:8000:xjWBJN:MUDe7p
194.32.251.164:8000:xjWBJN:MUDe7p
194.32.251.4:8000:xjWBJN:MUDe7p
194.32.251.121:8000:xjWBJN:MUDe7p
194.32.251.205:8000:xjWBJN:MUDe7p
81.200.159.234:8000:xjWBJN:MUDe7p
46.19.71.21:8000:xjWBJN:MUDe7p
81.200.159.46:8000:xjWBJN:MUDe7p
81.200.159.128:8000:xjWBJN:MUDe7p
213.139.222.249:8000:xjWBJN:MUDe7p
213.139.222.53:8000:xjWBJN:MUDe7p
213.139.223.188:8000:xjWBJN:MUDe7p
213.139.223.142:8000:xjWBJN:MUDe7p';

$toursBase->query("TRUNCATE proxy_list");
$toursBase->query("TRUNCATE proxy");
$proxy = explode("\n", $proxy);
foreach ($proxy as $pr) {
    $pr = explode(':', $pr);
    $url = $pr[0] . ':' . $pr[1];
    $auth = $pr[2] . ':' . $pr[3];


    $toursBase->query("INSERT INTO proxy_list (`id`, `url`, `auth`, `summ_get`, `status`, `last_send`, `countERR`, `sendedNotify`, `lasterr`, `onOff`, `lastconnectiontime`) VALUES (NULL, '" . trim($url) . "', '" . trim($auth) . "', '0', '1', '', '0', '0', '', '1', '');");


    $listOperatorsDB = $toursBase->query("SELECT * FROM sites");
    while ($listOperators = $listOperatorsDB->fetch_assoc()) {
        $toursBase->query("INSERT INTO proxy (`id`, `url`, `auth`, `summ_get`, `status`, `last_send`, `countERR`, `sendedNotify`, `lasterr`, `onOff`, `operator_id`) VALUES (NULL, '" . trim($url) . "', '" . trim($auth) . "', '0', '1', '', '0', '0', '', '0', '" . $listOperators['id'] . "');");
    }
}
?>