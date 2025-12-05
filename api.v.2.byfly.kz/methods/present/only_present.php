<?php
// Проверка подключения к БД
if (!$db) {
    echo json_encode(array(
        "type" => false,
        "msg" => "Ошибка подключения к базе данных!",
    ), JSON_UNESCAPED_UNICODE);
    exit();
}

// Проверка наличия ID в POST
if (!isset($_POST['id'])) {
    echo json_encode(array(
        "type" => false,
        "msg" => "Некорректный ID!",
    ), JSON_UNESCAPED_UNICODE);
    exit();
}

$id = (int) $_POST['id'];

// Подготовленный запрос для получения информации о событии
$stmt = $db->prepare("SELECT * FROM present_event WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $presentInfo = $result->fetch_assoc();
    $presentInfo['users'] = array();

    // Получение информации о ведущем
    $stmtUser = $db->prepare("SELECT id, avatar, phone, name, surname, user_status, famale FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $presentInfo['user_id']);
    $stmtUser->execute();
    $presentUser = $stmtUser->get_result()->fetch_assoc();

    // Получение участников
    $stmtParticipants = $db->prepare("SELECT user_id FROM present_event_users WHERE event_id = ?");
    $stmtParticipants->bind_param("i", $id);
    $stmtParticipants->execute();
    $participantsResult = $stmtParticipants->get_result();

    while ($listUser = $participantsResult->fetch_assoc()) {
        $stmtUserInfo = $db->prepare("SELECT id, avatar, phone, name, surname, user_status, famale FROM users WHERE id = ?");
        $stmtUserInfo->bind_param("i", $listUser['user_id']);
        $stmtUserInfo->execute();
        $userInfo = $stmtUserInfo->get_result()->fetch_assoc();
        $presentInfo['users'][] = $userInfo;
    }

    echo json_encode(array(
        "type" => true,
        "data" => array(
            'id' => $presentInfo['id'],
            'startDate' => $presentInfo['date_start'],
            'endDate' => $presentInfo['date_off'],
            'participants' => count($presentInfo['users']),
            'avatar' => $presentInfo['avatar'],
            'firstName' => $presentUser['name'],
            'lastName' => $presentUser['surname'],
            'phone' => '+' . $presentUser['phone'],
            'city' => $presentInfo['city'],
            'address' => $presentInfo['adress'],
            'isOnline' => stripos($presentInfo['type'], 'онлайн') !== 'false' ? 'true' : 'false',
            'link' => $presentInfo['link'],
            'users' => $presentInfo['users'],
        ),
    ), JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(array(
        "type" => false,
        "msg" => "Презентация не существует!",
    ), JSON_UNESCAPED_UNICODE);
}
?>