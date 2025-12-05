<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


function addReverbToAudio($inputFilePath, $outputFilePath)
{
    if (!file_exists($inputFilePath)) {
        return array(
            "type" => false,
            "msg" => 'Input file does not exist.'
        );
    }
    $command = "ffmpeg -i " . escapeshellarg($inputFilePath) . " -af 'aecho=1.0:0.7:20:0.5' " . escapeshellarg($outputFilePath);
    exec($command, $output, $returnVar);
    if ($returnVar === 0) {
        return array(
            "type" => true,
            "msg" => 'Reverb added successfully.',
            "path" => $outputFilePath
        );
    } else {
        return array(
            "type" => false,
            "msg" => 'Failed to add reverb to audio file.'
        );
    }
}

function generateUniqueFileName($extension)
{
    $uniqueId = md5(uniqid(rand(), true));
    $fileName = $uniqueId . '.' . $extension;

    return $fileName;
}


function transformText($text)
{
    $translit_map = [
        'ә' => 'а',
        'і' => 'ы',
        'ң' => 'н',
        'ғ' => 'ғ',
        'ү' => 'у',
        'ұ' => 'у',
        'қ' => 'к',
        'ө' => 'о',
        'һ' => 'х',
        'Ә' => 'А',
        'І' => 'Ы',
        'Ң' => 'Н',
        'Ғ' => 'Ғ',
        'Ү' => 'У',
        'Ұ' => 'У',
        'Қ' => 'К',
        'Ө' => 'О',
        'Һ' => 'Х'
    ];

    $text = mb_strtoupper($text, 'UTF-8');
    $text = strtr($text, $translit_map);
    return $text;
}


if (!empty($_GET['text'])) {
    if (empty($_GET['lang']) == false && $_GET['lang'] != 'kk') {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.elevenlabs.io/v1/text-to-speech/Xb7hH8MSUJpSbSDYk0k2",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"model_id\": \"eleven_multilingual_v2\",\n  \"text\": \"" . $_GET['text'] . "\",\n  \"voice_settings\": {\n    \"stability\": 0.94,\n    \"similarity_boost\": 0.71,\n    \"style\": 0.23,\n    \"use_speaker_boost\": true\n  }\n}",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "xi-api-key: 003b263e7fc49596dbb748da49c92590"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
    } else {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.elevenlabs.io/v1/text-to-speech/IMB5XuRE1RryIbUbcf8c",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"model_id\": \"eleven_multilingual_v2\",\n  \"text\": \"" . transformText($_GET['text']) . "\",\n  \"voice_settings\": {\n    \"stability\": 1,\n    \"similarity_boost\": 1,\n    \"style\": 0.4,\n    \"use_speaker_boost\": true\n  }\n}",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "xi-api-key: 003b263e7fc49596dbb748da49c92590"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
    }


    if ($err) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "cURL Error #:" . $err
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        $fileName = generateUniqueFileName('mp3');
        $filePath = '/var/www/www-root/data/www/api.v.2.byfly.kz/eva/audios/' . $fileName;
        if (file_put_contents($filePath, $response)) {
            echo json_encode(
                array(
                    "type" => true,
                    "msg" => 'Audio file generated and saved successfully.',
                    "path" => 'https://api.v.2.byfly.kz/eva/audios/' . $fileName,
                    "real_path" => $filePath,
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Failed to save audio file.'
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error: empty text for generation audio...'
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>