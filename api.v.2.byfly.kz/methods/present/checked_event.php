<?php
if (empty($_POST['eventId']) == false) {
    if ($db->query("UPDATE present_event SET checked='1' WHERE id='" . $_POST['eventId'] . "'")) {


        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();
        $kuratorInfo = $db->query("SELECT * FROM users WHERE id='" . $userInfo['present_comands_id'] . "'")->fetch_assoc();

        $send = sendWhatsapp(
            $userInfo['phone'],
            "✅ *Ваше мероприятие подтверждено!* ✅\n\n" .
            "🎉 Куратор команды презентеров (" . $kuratorInfo['famale'] . " " . $kuratorInfo['name'] . ") подтвердил проведение вашего мероприятия.\n\n" .
            "🎯 Мы желаем вам удачного проведения мероприятия!"
        );

        echo json_encode(
            array(
                "type" => true,
                "data" => $send,
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