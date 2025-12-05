<?php
include ('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
function generateRespEva($chats, $maxSimbols, $maxTokens)
{
    $apiKey = 'sk-None-m4nJJzSwKPKo2KDsyNldT3BlbkFJ4ZEZYuKAAVFE09qLXeXu';

    $data = [
        "model" => "gpt-4o",
        "messages" => [
            [
                "role" => "system",
                "content" => [
                    [
                        "text" => "Ты девушка! Тебя зовут 'Ева'. Рзаговаривай как гопник с района на жаргоне что бы человек думал что ты реальная девушка! ",
                        "type" => "text"
                    ]
                ]
            ],
            [
                "role" => "system",
                "content" => [
                    [
                        "text" => "Ты туристический агент сети туристических агентств ByFly в Казахстане. Наши офисы расположены в 3 городах Казахстана: Алматы, Астана, Шымкент. Тебя зовут Ева ты исскуственный интелект разработанный выдающимся разработчиком Щетининым Александром в городе Алматы. Ты можешь подобрать тур, отслеживать горящие туры, рассказать все о странах и отелях по всему миру просто поболтать на различные темы. Старайся отправлять больше смайлов. Если нужно окажи моральную поддержку человеку. Отвечай только одним варриантом на языке диалога!. Отвичай коротко что бы не превысить " . $maxSimbols . " токенов.",
                        "type" => "text"
                    ]
                ]
            ],
        ],
        "temperature" => 0.4,
        "max_tokens" => $maxTokens,
        "top_p" => 1,
        "frequency_penalty" => 0,
        "presence_penalty" => 0.8,
        "tools" => [
            [
                "type" => "function",
                "function" => [
                    "name" => "get_tours",
                    "parameters" => [
                        "type" => "object",
                        "required" => [
                            "departure",
                            "arival",
                            "dateStart",
                            "dateEnd",
                            "countNightStart",
                            "countNightEnd",
                            "countAdult",
                            "countChildren"
                        ],
                        "properties" => [
                            "arival" => [
                                "type" => "string",
                                "description" => "Город прилета"
                            ],
                            "dateEnd" => [
                                "type" => "string",
                                "description" => "Конечная дата для поиска туров по дате вылета"
                            ],
                            "typeDict" => [
                                "type" => "string",
                                "description" => "Тип питания в отеле"
                            ],
                            "dateStart" => [
                                "type" => "string",
                                "description" => "Стартовая дата для поиска туров по дате вылета"
                            ],
                            "departure" => [
                                "type" => "string",
                                "description" => "Город вылета"
                            ],
                            "countAdult" => [
                                "type" => "string",
                                "description" => "Кол-во взрослых"
                            ],
                            "countStars" => [
                                "type" => "string",
                                "description" => "Кол-во звезд у отеля"
                            ],
                            "countChildren" => [
                                "type" => "string",
                                "description" => "Кол-во детей"
                            ],
                            "countNightEnd" => [
                                "type" => "string",
                                "description" => "До скольки ночей искать туры"
                            ],
                            "countNightStart" => [
                                "type" => "string",
                                "description" => "От скольки ночей искать туры"
                            ]
                        ]
                    ],
                    "description" => "Выполняем поиск туров если пользователь отправит данные!"
                ]
            ]
        ]
    ];

    foreach ($chats as $chat) {
        array_push($data['messages'], $chat);
    }


    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return array(
            "type" => false,
            "msg" => 'Ошибка запроса: ' . curl_error($ch),
        );
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode == 200) {
            $responseData = json_decode($response, true);
            $responseData['choices'][0]['message']['content'] = preg_replace('/\?{2,}/', '?', $responseData['choices'][0]['message']['content']);
            return array(
                "type" => true,
                "data" => $responseData,
            );
        } else {
            return array(
                "type" => false,
                "msg" => 'Ошибка HTTP-кода: ' . $httpCode,
            );
        }
    }

    curl_close($ch);
}


function sends()
{
    global $db;
    $systems = $db->query("SELECT * FROM system WHERE id = '1'")->fetch_assoc();
    if ($systems['sended_whatsapp'] == 0) {
        $db->query("UPDATE system SET sended_whatsapp='1' WHERE id='1'");


        $noReplyMessageDB = $db->query("SELECT t1.* FROM `chat_bot_whatsapp` t1 INNER JOIN ( SELECT phone, MAX(id) AS max_id FROM `chat_bot_whatsapp` WHERE retry = '0' GROUP BY phone ) t2 ON t1.phone = t2.phone AND t1.id = t2.max_id ORDER BY t1.id DESC");
        while ($noReplyMessage = $noReplyMessageDB->fetch_assoc()) {
            $listMessagesDB = $db->query("SELECT * FROM chat_bot_whatsapp WHERE chat_id='" . $noReplyMessage['chat_id'] . "' ORDER BY id DESC LIMIT 20");
            $listMessagesSend = array();

            $url = "https://7103.api.greenapi.com/waInstance7103957708/readChat/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";

            $payload = json_encode([
                "chatId" => $noReplyMessage['chat_id']
            ]);

            $headers = [
                'Content-Type: application/json'
            ];

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $response = curl_exec($ch);
            curl_close($ch);


            while ($listMessages = $listMessagesDB->fetch_assoc()) {

                $user = 'user';
                if ($listMessages['me'] == 0) {
                    $user = 'user';
                }

                array_push(
                    $listMessagesSend,
                    array(
                        "role" => $user,
                        "content" => array(
                            array(
                                "text" => $listMessages['content'],
                                "type" => "text"
                            )
                        )
                    ),
                );

            }

            if ($noReplyMessage['type'] == 'audio') {
                $generateResponce = generateRespEva(array_reverse($listMessagesSend), 100, 100);
            } else {
                $generateResponce = generateRespEva(array_reverse($listMessagesSend), 250, 300);
            }


            print_r($generateResponce);
            echo '<br>';

            if ($generateResponce['type']) {
                if (count($generateResponce['data']['choices']) > 0) {

                    if ($noReplyMessage['type'] == 'audio') {
                        $text = str_replace(["\r", "\n"], '', trim(strip_tags($generateResponce['data']['choices'][0]['message']['content'])));
                        $url = 'https://api.v.2.byfly.kz/eva/audio_generator.php?text=' . urlencode($text);
                        $generateAudio = file_get_contents($url);

                        $generateAudio = json_decode($generateAudio, true);
                        if ($generateAudio['type']) {
                            $url = "https://7103.api.greenapi.com/waInstance7103957708/sendFileByUrl/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";
                            $payload = [
                                "chatId" => "77021853498@c.us",
                                "urlFile" => $generateAudio['path'],
                                "fileName" => basename($generateAudio['path']),
                                'quotedMessageId' => $noReplyMessage['message_id'],
                            ];

                            $headers = [
                                'Content-Type: application/json'
                            ];

                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                            $response = curl_exec($ch);
                            print_r($response);

                        } else {
                            $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendMessage/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';
                            $data = array(
                                'chatId' => $noReplyMessage['chat_id'],
                                'message' => $generateResponce['data']['choices'][0]['message']['content'],
                                'quotedMessageId' => $noReplyMessage['message_id'],
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
                        }
                    } else {
                        $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendMessage/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';
                        $data = array(
                            'chatId' => $noReplyMessage['chat_id'],
                            'message' => $generateResponce['data']['choices'][0]['message']['content'],
                            'quotedMessageId' => $noReplyMessage['message_id'],
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
                    }

                    if (empty($response) == false) {
                        $resSend = json_decode($response, true);
                        if ($resSend) {
                            if ($resSend['idMessage']) {
                                if ($db->query("UPDATE chat_bot_whatsapp SET retry='1' WHERE phone='" . $noReplyMessage['phone'] . "'")) {
                                    if ($db->query("INSERT INTO chat_bot_whatsapp (`id`, `phone`, `userName`, `me`, `message_id`, `chat_id`, `type`, `content`, `date_create`, `retry`)  VALUES (NULL, '77022205088', 'EVA GPT', '1', '" . $resSend['idMessage'] . "', '" . $noReplyMessage['chat_id'] . "', 'text', '" . $generateResponce['data']['choices'][0]['message']['content'] . "', '" . date('Y-m-d H:i:s') . "', '1');")) {
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
                    }

                }
            }

        }

        $db->query("UPDATE system SET sended_whatsapp='0' WHERE id='1'");
    }
}

sends();
sends();
sends();
sends();
sends();




?>