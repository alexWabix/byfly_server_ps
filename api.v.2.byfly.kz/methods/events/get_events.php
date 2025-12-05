<?php
$events = array();
$listEventsDB = $db->query("SELECT * FROM events WHERE date_start > '" . date('Y-m-d H:i:s') . "'");
while ($listEvents = $listEventsDB->fetch_assoc()) {
    $listEvents['videos'] = [];
    $listEvents['participants'] = [];
    $listEvents['images'] = [];
    $listEvents['userCharge'] = null;

    $listEvents['usersEvent'] = [];

    if ($listEvents['user_change'] != 0 && $listEvents['user_change'] != '0') {
        $listEvents['userCharge'] = $db->query("SELECT id, name, famale, surname, avatar, phone FROM users WHERE id='" . $listEvents['user_change'] . "'")->fetch_assoc();
    }


    $listImageDB = $db->query("SELECT * FROM event_images WHERE event_id='" . $listEvents['id'] . "'");
    while ($listImage = $listImageDB->fetch_assoc()) {
        array_push($listEvents['images'], $listImage['image']);
    }

    $listVideoDB = $db->query("SELECT * FROM event_video WHERE event_id='" . $listEvents['id'] . "'");
    while ($listVideo = $listVideoDB->fetch_assoc()) {
        array_push($listEvents['videos'], $listVideo['video']);
    }

    $listUsersDB = $db->query("SELECT * FROM event_user WHERE event_id='" . $listEvents['id'] . "'");
    while ($listUsers = $listUsersDB->fetch_assoc()) {

        $users = $db->query("SELECT id, name, famale, surname, phone, avatar FROM users WHERE id='" . $listUsers['user_id'] . "'")->fetch_assoc();
        $avatar = $users['avatar'];

        array_push($listEvents['usersEvent'], $users);
        if (mb_strlen($avatar, 'utf-8') > 0) {
            array_push($listEvents['participants'], $avatar);
        }
    }
    array_push($events, $listEvents);
}

echo json_encode(
    array(
        "type" => true,
        "data" => array(
            "events" => $events,
        ),
    ),
    JSON_UNESCAPED_UNICODE
);
?>