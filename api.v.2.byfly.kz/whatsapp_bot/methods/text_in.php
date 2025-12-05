<?php
if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', 'text', '" . $message['messageData']['textMessageData']['textMessage'] . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
    echo json_encode(
        array(
            'type' => true,
            'msg' => 'Сообщение зарегистрировано!'
        ),
        JSON_UNESCAPED_UNICODE,
    );
}
?>