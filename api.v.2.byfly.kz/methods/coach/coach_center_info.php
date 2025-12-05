<?php
// Инициализация массива для ответа
$arrResp = array(
    "count_teachers" => 0,
    "count_teachers_this" => 0,
    "count_teachers_exam_dont" => 0,
    "count_teachers_sr_bal" => 0,
    "count_group_teachers" => 0,
    "count_group_teachers_after" => 0,
    "coach_center_settings" => null,
    "coucher_summ_money" => 0
);

$currentDateTime = date('Y-m-d H:i:s');
$result = $db->query("SELECT COUNT(*) as ct FROM coach WHERE astestation_bal >= 98");
$arrResp['count_teachers'] = ($result) ? (int) $result->fetch_assoc()['ct'] : 0;

$result = $db->query("SELECT COUNT(*) as ct FROM users WHERE date_couch_start < '$currentDateTime' AND date_validate_agent > '$currentDateTime'");
$arrResp['count_teachers_this'] = ($result) ? (int) $result->fetch_assoc()['ct'] : 0;

$result = $db->query("SELECT COUNT(*) as ct FROM users WHERE astestation_bal < 92");
$arrResp['count_teachers_exam_dont'] = ($result) ? (int) $result->fetch_assoc()['ct'] : 0;

$result = $db->query("SELECT AVG(`astestation_bal`) as average FROM users WHERE `astestation_bal` IS NOT NULL AND astestation_bal != '0'");
$arrResp['count_teachers_sr_bal'] = ($result) ? round((float) $result->fetch_assoc()['average'], 2) : 0;

$result = $db->query("SELECT COUNT(*) as ct FROM grouped_coach WHERE date_start_coaching < '$currentDateTime' AND date_end_coaching < '$currentDateTime'");
$arrResp['count_group_teachers'] = ($result) ? (int) $result->fetch_assoc()['ct'] : 0;

$result = $db->query("SELECT COUNT(*) as ct FROM grouped_coach WHERE date_start_coaching > '$currentDateTime' AND date_end_coaching < '$currentDateTime'");
$arrResp['count_group_teachers_after'] = ($result) ? (int) $result->fetch_assoc()['ct'] : 0;

$result = $db->query("SELECT * FROM coach_center_settings WHERE id='1'");
$arrResp['coach_center_settings'] = ($result) ? $result->fetch_assoc() : null;

$result = $db->query("SELECT SUM(balance) as ct FROM coach");
$arrResp['coucher_summ_money'] = ($result) ? (float) $result->fetch_assoc()['ct'] : 0;

// Возвращаем результат в формате JSON
echo json_encode(
    array(
        "type" => true,
        "data" => $arrResp,
    ),
    JSON_UNESCAPED_UNICODE
);
?>