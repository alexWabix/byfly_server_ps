<?php
$userId = $_POST['user_id'];
$date = $_POST['date'];
$type = $_POST['type'];
$duration = $_POST['duration'];
$link = $_POST['link'];
$city = $_POST['city'];
$adress = $_POST['address'];

$dateTime = new DateTime($date);
$dateTime->modify('+' . $duration . ' hours');
$newDate = $dateTime->format('Y-m-d H:i:s');

$userInfo = $db->query("SELECT * FROM users WHERE id='" . $userId . "'")->fetch_assoc();

if (empty($userId) == false) {
    if (
        $db->query("INSERT INTO present_event (`id`, `date_start`, `date_off`, `date_create`, `user_id`, `count_pay`, `count_viwe`, `type`, `adress`, `city`, `comand_id`, `count_users`, `link`, `checked`, `showToClient`) 
    VALUES (NULL, '" . $date . "', '" . $newDate . "', CURRENT_TIMESTAMP, '" . $userId . "', '0', '0', '" . $type . "', '" . $adress . "', '" . $city . "', '" . $userInfo['present_comands_id'] . "', '0', '" . $link . "', '0', '0');")
    ) {
        $kuratorInfo = $db->query("SELECT * FROM users WHERE id='" . $userInfo['present_comands_id'] . "'")->fetch_assoc();

        sendWhatsapp(
            $kuratorInfo['phone'],
            "✨ *Новая презентация добавлена!* ✨\n\n" .
            "👤 *Презентер*: " . $userInfo['famale'] . " " . $userInfo['name'] . "\n" .
            "📅 *Дата*: " . $date . "\n" .
            "⏳ *Длительность*: " . $duration . " ч.\n" .
            "📍 *Тип*: " . $type .
            ($type === "Физическая" ? "\n🏢 *Город*: " . $city . "\n🏠 *Адрес*: " . $adress : "\n🔗 *Ссылка на трансляцию*: " . $link) . "\n\n" .
            "🔔 *Напоминание*: Пожалуйста, перейдите в личный кабинет для подтверждения проведения презентации и просмотра деталей.\n\n" .
            "📲 *Личный кабинет*: https://byfly.kz/"
        );

        echo json_encode(
            array(
                "type" => true,
                "data" => [],
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $db->error,
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
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