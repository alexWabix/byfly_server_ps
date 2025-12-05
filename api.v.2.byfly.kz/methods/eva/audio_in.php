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
    $postFields = ['file' => $cfile];

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
    $db->query("INSERT INTO test (`id`, `post_text`, `get_text`) VALUES (NULL, '" . $response . "', '');");


    echo "Ответ от сервера: " . $response;
} else {
    $db->query("INSERT INTO test (`id`, `post_text`, `get_text`) VALUES (NULL, 'Ошибка расшифровки', '');");
    echo "Ошибка при скачивании файла.";
}


?>