<?php
if (empty($_POST['eventId']) == false) {
    if ($db->query("UPDATE present_event SET checked='0' WHERE id='" . $_POST['eventId'] . "'")) {
        $userId = $_POST['user_id'];
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $userId . "'")->fetch_assoc();
        $kuratorInfo = $db->query("SELECT * FROM users WHERE id='" . $userInfo['present_comands_id'] . "'")->fetch_assoc();

        sendWhatsapp(
            $userInfo['phone'],
            "❌ *Ваше мероприятие отклонено!* ❌\n\n" .
            "Куратор команды презентеров (" . $kuratorInfo['famale'] . " " . $kuratorInfo['name'] . ") отклонил проведение вашего мероприятия.\n\n"
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
            "msg" => "Не указан ID презентации",
        ),
        JSON_UNESCAPED_UNICODE
    );
}

?>