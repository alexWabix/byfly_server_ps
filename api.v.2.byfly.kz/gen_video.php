<?php

function generateVideo()
{
    $apiKey = "D6UYDING5R5b3iXVYYLlL-NXemXHyBtaO2B6Ou6Cdl8";
    $generateUrl = "https://viralapi.vadoo.tv/api/generate_video";

    $text = "Мальдивы: Факты, которые вас удивят! Мальдивы — это рай на Земле, но знаешь ли ты, что этот архипелаг может исчезнуть? Средняя высота островов всего полтора метра над уровнем моря, и учёные считают, что из-за глобального потепления Мальдивы могут полностью уйти под воду уже к концу века! Здесь нет рек и озёр, но есть подводные рифы, которые называют небоскрёбами океана. Они уходят в глубину на сотни метров и являются домом для тысяч морских существ. А видел ли ты светящиеся пляжи? Ночью мальдивские волны загораются голубым светом благодаря биолюминесцентному планктону — настоящее волшебство природы! Но самое удивительное — острова постоянно меняют форму! Океанские течения переносят песок, создавая новые берега прямо на глазах. И, конечно, здесь находится первый в мире подводный ресторан! Ты ешь ужин, а за стеклом проплывают акулы. Какой факт удивил тебя больше всего?";

    $data = [
        "topic" => "Custom",
        "prompt" => "Интересные факты о Мальдивах",
        "script" => $text,
        "theme" => "Hormozi_1",
        "style" => "None",
        "language" => "Russian",
        "duration" => "30-60",
        "aspect_ratio" => "9:16",
        "use_ai" => "1",
        "url" => ""
    ];

    $ch = curl_init($generateUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (!isset($result['vid'])) {
        die("Ошибка: Не удалось получить ID видео");
    }

    return $result['vid'];
}

function getVideoStatus($videoId)
{
    $apiKey = "D6UYDING5R5b3iXVYYLlL-NXemXHyBtaO2B6Ou6Cdl8";
    $getVideoUrl = "https://viralapi.vadoo.tv/api/get_video_url";

    $ch = curl_init("$getVideoUrl?id=$videoId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $apiKey"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $videoData = json_decode($response, true);

    return $videoData;
}

$type = 'generate';
if (empty($_GET['video_id']) == false) {
    $type = 'get';
} else {
    $type = 'generate';
}


if ($type == 'generate') {
    $videoId = generateVideo();
    if (empty($videoId) == false) {
        echo json_encode(
            array(
                "type" => true,
                "video_id" => $videoId
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Не удалось запустить генерацию!"
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    $statusData = getVideoStatus($_GET['video_id']);
    if (empty($statusData) == false) {
        if ($statusData['status'] == 'completed') {
            echo json_encode(
                array(
                    "type" => true,
                    "video_info" => $statusData,
                    "url" => $statusData['url']
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => true,
                    "video_info" => $statusData,
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
        echo '<br>' . $statusData['url'];
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Не удалось получить статус генерации!"
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
}



?>