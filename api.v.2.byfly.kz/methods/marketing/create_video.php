<?php
function askAssistant($prompt)
{
    global $db;
    $botInfo = $db->query("SELECT * FROM asys_bot WHERE id='2'")->fetch_assoc();
    $apiKey = $botInfo['api_key'];
    $assistantId = "asst_6w8grCjDWhzspJmKqm9gsdBZ";
    $url = 'https://api.openai.com/v1/threads';

    // Создание нового треда
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'OpenAI-Beta: assistants=v2'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));

    $response = curl_exec($ch);
    $thread = json_decode($response, true);
    curl_close($ch);



    if (!isset($thread['id'])) {
        return array(
            "type" => false,
            "msg" => 'Ошибка создания треда'
        );
    }

    $threadId = $thread['id'];
    $url = "https://api.openai.com/v1/threads/$threadId/messages";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'OpenAI-Beta: assistants=v2'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'role' => 'user',
        'content' => $prompt
    ]));

    $response = curl_exec($ch);
    curl_close($ch);
    $url = "https://api.openai.com/v1/threads/$threadId/runs";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'OpenAI-Beta: assistants=v2'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'assistant_id' => $assistantId
    ]));

    $response = curl_exec($ch);
    $run = json_decode($response, true);
    curl_close($ch);

    if (!isset($run['id'])) {
        return array(
            "type" => false,
            "msg" => 'Ошибка запуска ассистента'
        );
    }

    $runId = $run['id'];


    do {
        sleep(2);

        $url = "https://api.openai.com/v1/threads/$threadId/runs/$runId";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'OpenAI-Beta: assistants=v2'
        ]);

        $response = curl_exec($ch);
        $runStatus = json_decode($response, true);
        curl_close($ch);

    } while ($runStatus['status'] == 'in_progress');



    $url = "https://api.openai.com/v1/threads/$threadId/messages";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'OpenAI-Beta: assistants=v2'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $messages = json_decode($response, true);
    $assistantMessage = '';

    foreach ($messages['data'] as $message) {
        if ($message['role'] === 'assistant') {
            $assistantMessage = array(
                "type" => true,
                "data" => $message['content'][0]['text']['value']
            );
            break;
        }
    }

    return $assistantMessage ?: array(
        "type" => false,
        "msg" => "Ошибка получения ответа ассистента"
    );
}


function generateVideo($scriptText, $voice = "Adam")
{
    global $genVideoApi;
    $apiUrl = "https://ext.videogen.io/v1/script-to-video";
    $apiKey = $genVideoApi;
    $randomMusic = rand(1, 122);

    $postData = json_encode([
        "script" => $scriptText,
        "voice" => $voice,
        "voiceVolume" => 1,
        "musicUrl" => 'https://api.v.2.byfly.kz/music/music (' . $randomMusic . ').mp3',
        "musicVolume" => 0.35,
        "aspectRatio" => [
            "width" => 9,
            "height" => 16,
            "minDimensionPixels" => 1080
        ],
        "captionFontName" => "Montserrat",
        "captionFontSize" => 100,
        "captionFontWeight" => 700,
        "captionTextColor" => [
            "red" => 255,
            "green" => 255,
            "blue" => 255
        ],
        "captionTextJustification" => "CENTER",
        "captionVerticalAlignment" => "BOTTOM",
        "captionStrokeColor" => [
            "red" => 0,
            "green" => 0,
            "blue" => 0
        ],
        "captionStrokeWeight" => 5,
        "captionBackgroundStyleType" => "RECT",
        "captionBackgroundColor" => [
            "red" => 0,
            "green" => 0,
            "blue" => 0
        ],
        "captionBackgroundOpacity" => 0.5,
    ]);

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}


if (empty($_POST['promt']) == false) {
    $promt = askAssistant($_POST['promt']);
    if ($promt['type']) {
        $genVideo = generateVideo($promt['data'], "Adam");
        if (empty($genVideo['apiFileId']) == false) {
            $getuserInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();
            $getuserInfo['count_video_today'] = $getuserInfo['count_video_today'] + 1;

            $db->query("UPDATE users SET count_video_today = '" . $getuserInfo['count_video_today'] . "' WHERE id='" . $getuserInfo['id'] . "'");
            echo json_encode(
                array(
                    "type" => true,
                    "data" => $genVideo['apiFileId']
                ),
                JSON_UNESCAPED_UNICODE,
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => "Не удалось инициировать создание видео!"
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $promt['msg']
            ),
            JSON_UNESCAPED_UNICODE,
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Пустой запрос"
        ),
        JSON_UNESCAPED_UNICODE,
    );
}
?>