<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function redirectToTourOperatorBooking()
{
    global $tourvisor_login;
    global $tourvisor_password;
    // Проверяем, что передан ID тура
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        die('Не указан ID тура');
    }

    $tourId = $_GET['id'];

    // Ваши учетные данные TourVisor (можно использовать либо логин/пароль, либо API-ключ)
    $authParams = [
        'authlogin' => $tourvisor_login,      // Ваш логин в TourVisor
        'authpass' => $tourvisor_password,
    ];

    // Параметры запроса к API TourVisor
    $apiParams = [
        'format' => 'json',              // Можно изменить на xml, если нужно
        'tourid' => $tourId,
        'request' => 0                    // 0 - автоматически, 1 - всегда запрашивать, 2 - никогда
    ];

    // Объединяем параметры
    $requestParams = array_merge($authParams, $apiParams);

    // Формируем URL запроса
    $apiUrl = 'http://tourvisor.ru/xml/actualize.php?' . http_build_query($requestParams);

    // Выполняем запрос к API
    $response = file_get_contents($apiUrl);

    if ($response === false) {
        die('Ошибка при запросе к API TourVisor');
    }


    // Парсим ответ (в зависимости от формата)
    $data = json_decode($response, true);

    // Если используется XML:
    // $xml = simplexml_load_string($response);
    // $operatorLink = (string)$xml->tour->operatorlink;

    // Проверяем наличие ссылки на бронирование
    if (empty($data['data']['tour']['operatorlink'])) {
        die('Ссылка на бронирование не найдена');
    }

    $bookingUrl = $data['data']['tour']['operatorlink'];

    // Выполняем редирект
    header('Location: ' . $bookingUrl);
    exit;
}

// Вызываем метод
redirectToTourOperatorBooking();
?>