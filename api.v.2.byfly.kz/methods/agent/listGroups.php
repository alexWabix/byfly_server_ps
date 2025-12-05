<?php
$listGrouped = array();
$listCitys = array();
$listGroupDB = $db->query("SELECT * FROM grouped_coach WHERE date_start_coaching > '" . date("Y-m-d H:i:s") . "'");
while ($listGroup = $listGroupDB->fetch_assoc()) {
    $listGroup['teacher'] = $db->query("SELECT id,name_famale,avatar,date_berthday,date_registration,franchaise,phone,biografi_text_ru,biografi_text_en,biografi_text_kk,desc_video FROM coach WHERE id='" . $listGroup['coach_id'] . "'")->fetch_assoc();
    $listGroup['occupiedSeats'] = $db->query("SELECT COUNT(*) as ct FROM users WHERE grouped='" . $listGroup['id'] . "'")->fetch_assoc()['ct'];

    array_push($listGrouped, $listGroup);



    if (!in_array($listGroup['coaching_city'], $listCitys)) {  // Исправлено здесь
        array_push($listCitys, $listGroup['coaching_city']);
    }
}

echo json_encode(
    array(
        "type" => true,
        "data" => array(
            "citys" => $listCitys,
            "groups" => $listGrouped,
        ),
    ),
    JSON_UNESCAPED_UNICODE
);
?>