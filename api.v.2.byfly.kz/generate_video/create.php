<?php

function generateVideo($scriptText, $voice = "Adam")
{
    $apiUrl = "https://ext.videogen.io/v1/script-to-video";
    $apiKey = "bdf68a7b497d535a50f9e5aa3b7cd56e23b7ad80";

    $postData = json_encode([
        "script" => $scriptText,
        "voice" => $voice,
        "voiceVolume" => 1,
        "musicUrl" => 'https://api.v.2.byfly.kz/music/1.mp3',
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
        "captionVerticalAlignment" => "MIDDLE",
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

// Пример использования
$script = "Ты не поверишь, но есть место, где лазурные волны омывают белоснежные пляжи, а гигантские черепахи спокойно гуляют среди тропических лесов! Здесь можно найти скрытые бухты, скалы, напоминающие инопланетные пейзажи, и коралловые рифы, где плавают сотни экзотических рыб! В этом месте кокосы бывают размером с голову, а закаты кажутся нарисованными акварелью! Хочешь узнать, где это? ByFly Travel предлагает лучшие туры на Сейшелы по выгодным ценам! А по промокоду македонец ты получишь скидку 12 000 тенге! Вылеты из Алматы, Астаны и Шымкента, стоимость от 900 000 тенге!";

$result = generateVideo($script, "Adam");

print_r($result);
