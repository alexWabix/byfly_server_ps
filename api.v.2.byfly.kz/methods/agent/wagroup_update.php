<?php

$data = $_POST;
if (
    empty($data['method']) ||
    $data['method'] !== 'agent/wagroup_update' ||
    empty($data['title_group']) ||
    empty($data['group_link']) ||
    empty($data['defoult_nakrutka']) ||
    empty($data['new_city']) ||
    empty($data['group_id'])
) {
    echo json_encode([
        'type' => false,
        'msg' => 'Некорректные или отсутствующие параметры.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$groupId = $data['group_id'];
$titleGroup = $db->real_escape_string($data['title_group']);
$groupLink = $db->real_escape_string($data['group_link']);
$nakrutka = (int) $data['defoult_nakrutka'];
$newCity = (int) $data['new_city'];

$query = "
    UPDATE user_whatsapp_groups 
    SET 
        title_group = '$titleGroup', 
        group_link = '$groupLink', 
        defoult_nakrutka = $nakrutka, 
        city_id = $newCity 
    WHERE id = $groupId
";

if ($db->query($query)) {
    $wa = $db->query("SELECT * FROM user_whatsapp_groups WHERE id='" . $data['group_id'] . "'")->fetch_assoc();
    $wa['city'] = $db->query("SELECT * FROM departure_citys WHERE id_visor='" . $wa['city_id'] . "'")->fetch_assoc();

    echo json_encode([
        'type' => true,
        'data' => $wa,
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'type' => false,
        'msg' => 'Ошибка при обновлении данных: ' . $db->error,
    ], JSON_UNESCAPED_UNICODE);
}
?>