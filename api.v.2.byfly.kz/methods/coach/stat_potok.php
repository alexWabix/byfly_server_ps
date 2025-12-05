<?php
$searchPotokDB = $db->query("SELECT * FROM grouped_coach WHERE id='" . $_POST['group_id'] . "'");
if ($searchPotokDB->num_rows > 0) {
    $resp = array();
    $resp['total_calls'] = $db->query("SELECT SUM(count_call) as ct FROM grouped_user_ads WHERE group_id='" . $_POST['group_id'] . "'")->fetch_assoc()['ct'] ?? 0;
    $resp['total_whatsapp'] = $db->query("SELECT SUM(count_whats) as ct FROM grouped_user_ads WHERE group_id='" . $_POST['group_id'] . "'")->fetch_assoc()['ct'] ?? 0;
    $resp['total_participants'] = $db->query("SELECT COUNT(*) as ct FROM users WHERE grouped='" . $_POST['group_id'] . "'")->fetch_assoc()['ct'] ?? 0;
    $resp['sources'] = array();
    $stat = $db->query("SELECT * FROM group_coach_stat WHERE group_id='" . $_POST['group_id'] . "'")->fetch_assoc();
    $resp['sources']['instagram'] = array(
        "visitors" => $stat['instagram'] ?? 0,
        "calls" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='call' AND istochnik='instagram'")->fetch_assoc()['ct'],
        "whatsapp" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='whats' AND istochnik='instagram'")->fetch_assoc()['ct'],
    );

    $resp['sources']['tiktok'] = array(
        "visitors" => $stat['tiktok'] ?? 0,
        "calls" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='call' AND istochnik='tiktok'")->fetch_assoc()['ct'],
        "whatsapp" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='whats' AND istochnik='tiktok'")->fetch_assoc()['ct'],
    );

    $resp['sources']['youtube'] = array(
        "visitors" => $stat['google_youtube'] ?? 0,
        "calls" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='call' AND istochnik='google_youtube'")->fetch_assoc()['ct'],
        "whatsapp" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='whats' AND istochnik='google_youtube'")->fetch_assoc()['ct'],
    );

    $resp['sources']['kms'] = array(
        "visitors" => $stat['google_kms'] ?? 0,
        "calls" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='call' AND istochnik='google_kms'")->fetch_assoc()['ct'],
        "whatsapp" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='whats' AND istochnik='google_kms'")->fetch_assoc()['ct'],
    );


    $resp['sources']['google_search'] = array(
        "visitors" => $stat['google_search'] ?? 0,
        "calls" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='call' AND istochnik='google_search'")->fetch_assoc()['ct'],
        "whatsapp" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='whats' AND istochnik='google_search'")->fetch_assoc()['ct'],
    );

    $resp['sources']['others'] = array(
        "visitors" => $stat['other'] ?? 0,
        "calls" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='call' AND istochnik='other'")->fetch_assoc()['ct'],
        "whatsapp" => $db->query("SELECT COUNT(*) as ct FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "' AND type='whats' AND istochnik='other'")->fetch_assoc()['ct'],
    );


    $resp['details'] = array();
    $listDetailsDB = $db->query("SELECT * FROM group_coach_stat_detail WHERE group_id='" . $_POST['group_id'] . "'");
    while ($listDetails = $listDetailsDB->fetch_assoc()) {
        $userInfo = $db->query("SELECT name,famale,surname,id FROM users WHERE phone='" . $listDetails['phone'] . "'")->fetch_assoc();
        $listDetails['userInfo'] = $userInfo;
        $resp['details'][] = $listDetails;
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => $resp
        ),
        JSON_UNESCAPED_UNICODE,
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Поток не существует!'
        ),
        JSON_UNESCAPED_UNICODE,
    );
}
?>