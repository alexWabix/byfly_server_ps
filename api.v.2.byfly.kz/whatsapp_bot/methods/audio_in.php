<?php
function downloadFile($url, $saveTo)
{
    $ch = curl_init($url);
    $fp = fopen($saveTo, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $success = curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    return $success;
}
function sendFile($url, $filePath)
{
    $cfile = new CURLFile($filePath, 'audio/mpeg', basename($filePath));
    $postFields = ['audio' => $cfile];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

$fileUrl = $message['messageData']['fileMessageData']['downloadUrl'];
$localFilePath = '/var/www/www-root/data/www/api.v.2.byfly.kz/audio_mess/' . basename($fileUrl);
$postUrl = 'https://appoffice.kz/eva/audio_to_text/converted.php';

if (downloadFile($fileUrl, $localFilePath)) {
    $response = sendFile($postUrl, $localFilePath);
    $data = json_decode($response, true);
    if (empty($data) == false) {
        if ($data['succ']) {
            if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', 'audio', '" . $data['text'][0] . "', '" . date('Y-m-d H:i:s') . "', '0');")) {
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
}


?>