<?php
include ('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$message = json_decode(file_get_contents('php://input'), true);

if ($message['typeWebhook'] == 'incomingMessageReceived') {
    if ($message['messageData']['typeMessage'] == 'textMessage') {
        include ('/var/www/www-root/data/www/api.v.2.byfly.kz/whatsapp_bot/methods/text_in.php');
    } else if ($message['messageData']['typeMessage'] == 'audioMessage') {
        include ('/var/www/www-root/data/www/api.v.2.byfly.kz/whatsapp_bot/methods/audio_in.php');
    } else if ($message['messageData']['typeMessage'] == 'imageMessage') {
        include ('/var/www/www-root/data/www/api.v.2.byfly.kz/whatsapp_bot/methods/image_in.php');
    } else if ($message['messageData']['typeMessage'] == 'videoMessage') {
        $typeMess = 'video';
        $mess = 'Я отправил видеоролик...';
        if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', '" . $typeMess . "', '" . $mess . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
            echo json_encode(
                array(
                    'type' => true,
                    'msg' => 'Сообщение зарегистрировано!'
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    } else if ($message['messageData']['typeMessage'] == 'locationMessage') {
        $typeMess = 'locations';
        $mess = 'Я отправил местоположение...';
        if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', '" . $typeMess . "', '" . $mess . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
            echo json_encode(
                array(
                    'type' => true,
                    'msg' => 'Сообщение зарегистрировано!'
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    } else if ($message['messageData']['typeMessage'] == 'contactMessage') {
        $typeMess = 'contacts';
        $mess = 'Я отправил контакт...';
        if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', '" . $typeMess . "', '" . $mess . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
            echo json_encode(
                array(
                    'type' => true,
                    'msg' => 'Сообщение зарегистрировано!'
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    } else if ($message['messageData']['typeMessage'] == 'documentMessage') {
        $typeMess = 'document';
        $mess = 'Я отправил документ...';
        if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', '" . $typeMess . "', '" . $mess . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
            echo json_encode(
                array(
                    'type' => true,
                    'msg' => 'Сообщение зарегистрировано!'
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    } else {
        $typeMess = 'no-data';
        $mess = 'Я неизвестный формат сообщения...';
        if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', '" . $typeMess . "', '" . $mess . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
            echo json_encode(
                array(
                    'type' => true,
                    'msg' => 'Сообщение зарегистрировано!'
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    }
}
?>