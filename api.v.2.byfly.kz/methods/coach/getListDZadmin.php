<?php
$dz = array();
$listDZdb = $db->query("SELECT * FROM home_work");
while ($listDZ = $listDZdb->fetch_assoc()) {
    $dz[] = $listDZ;
}

echo json_encode(
    array(
        "type" => true,
        "data" => $dz,
    ),
    JSON_UNESCAPED_UNICODE,
);
?>