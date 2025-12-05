<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if (empty($_POST) == false) {
    $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendMessage/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';
    $data = array(
        'chatId' => '120363322245653175@g.us',
        "message" => "Всем общий саламчик! Один из пользователей instagram проявил инетерес к нашей рекламе нужно с ним связаться побыренькому! Телефон клиента: " . $_POST['raw__Номер_телефона*'] . ". Объявление: " . $_POST['ad_name']
    );

    $options = array(
        'http' => array(
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        )
    );

    $context = stream_context_create($options);

    $response = file_get_contents($url, false, $context);
}
$db->query("INSERT INTO test (`id`, `post_text`, `get_text`) VALUES (NULL, '" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "', '" . json_encode($_GET, JSON_UNESCAPED_UNICODE) . "');");
?>