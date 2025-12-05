<?php
function askAssistant($prompt)
{
    global $db;
    $botInfo = $db->query("SELECT * FROM asys_bot WHERE id='2'")->fetch_assoc();
    $apiKey = $botInfo['api_key'];
    $assistantId = "asst_zMFMnVrsCCl4C8xTL3gnVN6X";
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


if (empty($_POST['prompt']) == false) {
    $text = askAssistant($_POST['prompt']);
    if ($text['type']) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $text['data']
            ),
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $text['msg']
            ),
            JSON_UNESCAPED_UNICODE,
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Empty prompt."
        ),
        JSON_UNESCAPED_UNICODE,
    );
}

?>