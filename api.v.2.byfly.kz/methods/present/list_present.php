<?php
if (empty($_POST['user_id']) == false) {
    $listEvent = array();
    $listEventInDB = $db->query("SELECT * FROM present_event WHERE user_id='" . $_POST['user_id'] . "'");
    while ($listEventIn = $listEventInDB->fetch_assoc()) {
        $listEventIn['users'] = array();
        $listEventIn['rashodes'] = array();
        $listEventIn['summRashod'] = 0;
        $listEventIn['showToClient'] = $listEventIn['showToClient'] == 1 ? true : false;


        $listRashodesDB = $db->query("SELECT * FROM present_event_rashod WHERE present_id='" . $listEventIn['id'] . "'");
        while ($listRashodes = $listRashodesDB->fetch_assoc()) {
            $listEventIn['summRashod'] = $listEvent['summRashod'] + $listRashodes['summ'];
            $listEventIn['rashodes'][] = $listRashodes;
        }

        $listUsersDB = $db->query("SELECT * FROM present_event_users WHERE event_id='" . $listEventIn['id'] . "'");
        while ($listUsers = $listUsersDB->fetch_assoc()) {
            $userInfoInder = $db->query("SELECT `id`, `name`, `surname`, `famale`, `phone`, `blocked_to_time`, `user_status`, `avatar` FROM users WHERE id='" . $listUsers['user_id'] . "'")->fetch_assoc();
            array_push($listEventIn['users'], $userInfoInder);
        }
        array_push($listEvent, $listEventIn);
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => $listEvent,
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