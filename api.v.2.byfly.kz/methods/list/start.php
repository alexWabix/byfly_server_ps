<?php

$filename = $dir . 'methods/list/start.json';

if (file_exists($filename)) {
    $jsonData = json_decode(file_get_contents($filename), true);
    $jsonData['curencys'] = array();

    $valutesDB = $db->query("SELECT * FROM exchange_rates ORDER BY priority_stay DESC");
    while ($valutes = $valutesDB->fetch_assoc()) {
        array_push($jsonData['curencys'], $valutes);
    }

    echo json_encode(array(
        "type" => true,
        "data" => $jsonData,
    ), JSON_UNESCAPED_UNICODE);
}

?>