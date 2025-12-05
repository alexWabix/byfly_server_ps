<?php
if (empty($_POST['user_id']) == false) {
    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();
    $userInfo['kurator'] = $db->query("SELECT * FROM users WHERE id='" . $userInfo['present_comands_id'] . "'")->fetch_assoc();
    $userInfo['count_press'] = $db->query("SELECT COUNT(*) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "' AND date_start > '" . date('Y-m-d H:i:s') . "'")->fetch_assoc()['ct'];
    $userInfo['count_after'] = $db->query("SELECT COUNT(*) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "' AND date_start < '" . date('Y-m-d H:i:s') . "'")->fetch_assoc()['ct'];

    $countAwaitUsers = $db->query("SELECT SUM(count_users) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "' AND date_start > '" . date('Y-m-d H:i:s') . "'")->fetch_assoc()['ct'];
    if ($countAwaitUsers == null) {
        $countAwaitUsers = 0;
    }
    $percentagePodpiskaUser = $totalUsersPresentation > 0
        ? ceil(($totalUsersPay / $totalUsersPresentation) * 100)
        : 0;

    $payPrognoz = ceil(($countAwaitUsers / 100) * $percentagePodpiskaUser);

    $totalUsersPresentation = $db->query("SELECT SUM(count_users) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "'")->fetch_assoc()['ct'];
    $totalUsersPay = $db->query("SELECT SUM(count_pay) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "'")->fetch_assoc()['ct'];

    if ($totalUsersPresentation == null) {
        $totalUsersPresentation = 0;
    }

    if ($totalUsersPay == null) {
        $totalUsersPay = 0;
    }

    $userInfo['stat'] = array(
        "count_users_presentation" => $totalUsersPresentation,
        "count_users_pay" => $totalUsersPay,
        "count_presentation_delay" => $db->query("SELECT COUNT(*) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "' AND date_start > '" . date('Y-m-d H:i:s') . "'")->fetch_assoc()['ct'],
        "count_presentation_after" => $db->query("SELECT COUNT(*) as ct FROM present_event WHERE user_id='" . $_POST['user_id'] . "' AND date_start < '" . date('Y-m-d H:i:s') . "'")->fetch_assoc()['ct'],
        "count_users_presentation_delay" => $countAwaitUsers,
        "summ_prognoz_user" => $payPrognoz,
        "percentage_podpiska_user" => $percentagePodpiskaUser,
    );

    $userInfo['present_delay'] = array();
    $userInfo['tranzactions'] = array();

    echo json_encode(
        array(
            "type" => true,
            "data" => $userInfo,
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Не указан ID пользователя",
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>