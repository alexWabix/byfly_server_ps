<?php
$group_id = $_POST['group_id'] ?? null;

if (!$group_id) {
    echo json_encode(["type" => false, "msg" => "Не указан group_id"], JSON_UNESCAPED_UNICODE);
    exit;
}

$listUsersDZ = [];

$usersDZDB = $db->query("SELECT * FROM home_work_user WHERE group_id='" . $group_id . "'");

while ($usersDZ = $usersDZDB->fetch_assoc()) {
    $userInfo = $db->query("SELECT id, name, famale, surname, avatar FROM users WHERE id='" . $usersDZ['user_dz'] . "'")->fetch_assoc();
    $dzInfo = $db->query("SELECT id, type, title_ru, day FROM home_work WHERE id='" . $usersDZ['home_work_id'] . "'")->fetch_assoc();

    if ($userInfo && $dzInfo) {
        $listUsersDZ[] = [
            "user_id" => $userInfo['id'],
            "name" => trim($userInfo['name'] . " " . $userInfo['famale'] . " " . $userInfo['surname']),
            "avatar" => $userInfo['avatar'],
            "homework" => [
                "task_id" => $dzInfo['id'],
                "day" => (int) $dzInfo['day'],
                "type" => $dzInfo['type'],
                "title" => $dzInfo['title_ru'],
                "id" => $usersDZ['id'],
                "completed" => !empty($usersDZ['result']),
                "grade" => (int) $usersDZ['coach_count'] ?: null,
                "coach_comment" => $usersDZ['coach_coment'],
                "result" => $usersDZ['result'],
                "coach_count" => $usersDZ['coach_count'],
                "coach_coment" => $usersDZ['coach_coment']
            ]
        ];
    }
}

echo json_encode([
    "type" => true,
    "data" => $listUsersDZ
], JSON_UNESCAPED_UNICODE);
?>