<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

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
            "minDimensionPixels" => 480
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


function checkVideoGenerate($id)
{
    $videoId = $id;
    $apiUrl = "https://ext.videogen.io/v1/get-file?apiFileId=" . urlencode($videoId);
    $apiKey = "bdf68a7b497d535a50f9e5aa3b7cd56e23b7ad80";

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}


$maxProcesses = 10;
$activeProcesses = 0;

$listPlanDB = $db->query("SELECT * FROM agents_content_plan WHERE link_video IS NULL AND date_create >= '" . date('Y-m-d') . " 00:00:00' AND date_create < '" . date('Y-m-d') . " 23:59:00'");

$childPIDs = [];

while ($listPlan = $listPlanDB->fetch_assoc()) {
    while ($activeProcesses >= $maxProcesses) {
        $pid = pcntl_wait($status);
        if ($pid > 0) {
            $activeProcesses--;
            unset($childPIDs[$pid]);
        }
    }

    $pid = pcntl_fork();
    if ($pid == -1) {
        die('Не удалось создать процесс');
    } elseif ($pid) {
        $activeProcesses++;
        $childPIDs[$pid] = true;
    } else {
        processVideoGeneration($listPlan);
        exit(0);
    }
}
foreach (array_keys($childPIDs) as $pid) {
    pcntl_waitpid($pid, $status);
}
function processVideoGeneration($listPlan)
{
    global $db_host;
    global $db_user;
    global $db_pass;
    global $db_name;
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $listPlan['user_id'] . "'")->fetch_assoc();
    $responce = askAssistant('Напиши сценарий ролика на 1 минуту на тему: ' . $listPlan['prompt'] . '. Мой промокод - ' . $userInfo['promo_code'] . ', скидка на первую покупку - ' . $userInfo['refer_registration_bonus'] . ' тенеге.');

    if ($responce['type']) {
        $generateVideo = generateVideo($responce['data'], 'Adam');
        if (!empty($generateVideo['apiFileId'])) {
            do {
                sleep(2);
                $runStatus = checkVideoGenerate($generateVideo['apiFileId']);
            } while (empty($runStatus['apiFileSignedUrl']));

            $db->query("UPDATE agents_content_plan SET link_video='" . $runStatus['apiFileSignedUrl'] . "' WHERE id='" . $listPlan['id'] . "'");
            echo $runStatus['apiFileSignedUrl'] . '<br><br>';
        }
    }
    $db->close();
}
?>