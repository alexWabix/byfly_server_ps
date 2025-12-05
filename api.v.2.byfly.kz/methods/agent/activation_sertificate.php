<?php
$code = isset($_POST['sertID']) ? trim($_POST['sertID']) : null;

if (!$code) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "ID сертификата не указан",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$code = $db->real_escape_string($code);
$searchVaucherDB = $db->query("SELECT * FROM vauchers WHERE id='$code' AND type='couch'");

if ($searchVaucherDB->num_rows === 0) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Сертификат не найден или неверный код.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$searchVaucher = $searchVaucherDB->fetch_assoc();
if (strtotime($searchVaucher['date_off']) < time()) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Срок действия сертификата уже истек.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
if ($searchVaucher['activated'] == 1) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Данный сертификат уже активирован!",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
if ($searchVaucher['summ'] < $price) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Сумма сертификата недостаточна для оплаты. Лимит: " . $searchVaucher['summ'] . " тенге.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$userId = isset($_POST['userId']) ? (int) $_POST['userId'] : 0;
$userInfoDB = $db->query("SELECT * FROM users WHERE id='$userId'");
if ($userInfoDB->num_rows === 0) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Пользователь не найден.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$userInfo = $userInfoDB->fetch_assoc();
$groupId = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
$groupInfoDB = $db->query("SELECT * FROM grouped_coach WHERE id='$groupId'");
if ($groupInfoDB->num_rows === 0) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Группа не найдена.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$groupInfo = $groupInfoDB->fetch_assoc();
$coachInfoDB = $db->query("SELECT * FROM coach WHERE id='" . $groupInfo['coach_id'] . "'");
if ($coachInfoDB->num_rows === 0) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Преподаватель не найден.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$coachInfo = $coachInfoDB->fetch_assoc();
$newPriceCoach = $userInfo['price_coach'] - $searchVaucher['summ'];
$newPriceCoachTour = $userInfo['price_coach_tour'] - $searchVaucher['summ'];
$newPriceCoachOnline = $userInfo['price_coach_online'] - $searchVaucher['summ'];

$updateUserQuery = "
    UPDATE users SET 
    price_coach='$newPriceCoach', 
    price_coach_tour='$newPriceCoachTour', 
    price_coach_online='$newPriceCoachOnline', 
    date_couch_start='" . $groupInfo['date_start_coaching'] . "', 
    date_validate_agent='" . $groupInfo['date_validation'] . "', 
    grouped='" . $groupInfo['id'] . "', 
    coach_id='" . $groupInfo['coach_id'] . "', 
    orient='test',
    user_status='agent'
    WHERE id='$userId'
";

if (!$db->query($updateUserQuery)) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Ошибка обновления данных пользователя.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$db->query("INSERT INTO user_statused (`id`, `code_status`, `date_add`, `user_id`) VALUES (NULL, '4', CURRENT_TIMESTAMP, '$userId')");
sendWhatsapp($coachInfo['phone'], "В поток '" . $groupInfo['name_grouped_ru'] . "' записан новый участник: " . $userInfo['name'] . " " . $userInfo['famale'] . ".");
sendWhatsapp($userInfo['phone'], "Вы зарегистрированы в поток '" . $groupInfo['name_grouped_ru'] . "'. Преподаватель: " . $coachInfo['name_famale'] . ".");
$db->query("UPDATE vauchers SET activated='1', user_activated='" . $userId . "', date_activated='" . date('Y-m-d H:i:s') . "' WHERE id='$code'");

echo json_encode(
    array(
        "type" => true,
        "msg" => "Сертификат успешно активирован.",
    ),
    JSON_UNESCAPED_UNICODE
);
?>