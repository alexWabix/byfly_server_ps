<?php
$input = $_POST;

// Проверка на обязательные параметры
if (!isset($input['streamId'], $input['sessionId'], $input['answer'])) {
    echo json_encode([
        "type" => false,
        "msg" => "Необходимы streamId, sessionId и answer"
    ]);
    exit;
}

// Подготовка запроса к D-ID API
$streamId = $input['streamId'];
$sessionId = $input['sessionId'];
$answer = $input['answer'];

// Замените на ваш актуальный API_KEY
$apiKey = 'ВАШ_API_КЛЮЧ';
$url = "https://api.d-id.com/streams/$streamId/answer";

// Формируем JSON тело запроса
$data = [
    "session_id" => $sessionId,
    "answer" => $answer
];

$options = [
    'http' => [
        'header' => [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ],
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

// Обработка результата
if ($response === FALSE) {
    echo json_encode([
        "type" => false,
        "msg" => "Ошибка при отправке SDP answer"
    ]);
} else {
    $decoded = json_decode($response, true);
    echo json_encode([
        "type" => true,
        "data" => $decoded,
        "msg" => "Ответ успешно отправлен"
    ]);
}
?>