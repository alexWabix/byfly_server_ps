<?php
$listAgents = array();
$listDB = $db->query("SELECT * FROM  users WHERE date_couch_start IS NOT NULL");
while ($list = $listDB->fetch_assoc()) {
    $list['potok_name'] = 'Поток не указан!';
    if ($list['grouped'] != 0) {
        $potokInfoDB = $db->query("SELECT * FROM grouped_coach WHERE id='" . $list['grouped'] . "'");
        if ($potokInfoDB->num_rows > 0) {
            $potokInfo = $potokInfoDB->fetch_assoc();
            $list['potok_name'] = $potokInfo['name_grouped_ru'];
        }
    }


    $listAgents[] = $list;
}


echo json_encode(
    array(
        "type" => true,
        "data" => $listAgents,
    ),
    JSON_UNESCAPED_UNICODE
);
?>