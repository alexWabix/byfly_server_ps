<?php
$offices = array();
$listOfficcesDB = $db->query("SELECT * FROM franchaise");
while ($listOfficces = $listOfficcesDB->fetch_assoc()) {
    array_push($offices, $listOfficces);
}

echo json_encode(
    array(
        "type" => true,
        "data" => $offices,
    ),
    JSON_UNESCAPED_UNICODE
);
?>