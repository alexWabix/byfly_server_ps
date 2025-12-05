<?php
$opArr = array();
$listOperatorsDB = $db->query("SELECT * FROM operators ORDER BY LENGTH(login) DESC");
while ($listOperators = $listOperatorsDB->fetch_assoc()) {
    array_push($opArr, $listOperators);
}


echo json_encode(
    array(
        "type" => true,
        "data" => $opArr
    ),
    JSON_UNESCAPED_UNICODE,
);
?>