<?php
$apiKey = 'sk-None-m4nJJzSwKPKo2KDsyNldT3BlbkFJ4ZEZYuKAAVFE09qLXeXu';

$data = [
    "model" => "gpt-4o",
    "messages" => [
        [
            "role" => "user",
            "content" => [
                [
                    "type" => "text",
                    "text" => "Расскаи что изображенно на картинке!"
                ],
                [
                    "type" => "image_url",
                    "image_url" => [
                        "url" => $message['messageData']['fileMessageData']['downloadUrl']
                    ]
                ]
            ]
        ]
    ],
    "max_tokens" => 300
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $db->query("INSERT INTO test (`id`, `post_text`, `get_text`) VALUES (NULL, 'Не удалось распознать что изображенно на картинке! " . curl_error($ch) . "', '');");
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        if (count($responseData['choices']) > 0) {
            if (
                $db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  
            VALUES (NULL, '" . explode('@', $message['senderData']['chatId'])[0] . "', '" . $message['senderData']['senderName'] . "', '0', '" . $message['idMessage'] . "', '" . $message['senderData']['chatId'] . "', 'image', '" . $responseData['choices'][0]['message']['content'] . "', '" . date('Y-m-d H:i:s') . "', '1');")
            ) {


                $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendMessage/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';
                $data = array(
                    'chatId' => $message['senderData']['chatId'],
                    'message' => $responseData['choices'][0]['message']['content'],
                    'quotedMessageId' => $message['idMessage'],
                    'linkPreview' => true
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
                if (empty($response) == false) {
                    $resSend = json_decode($response, true);
                    if ($resSend) {
                        if ($resSend['idMessage']) {


                        }
                    }
                }
            }

        }
    }
}

curl_close($ch);

?>