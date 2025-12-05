<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

class TourComparator
{
    private $authLogin;
    private $authPass;
    private $currency = 3; // Тенге по умолчанию

    public function __construct($login, $password)
    {
        $this->authLogin = $login;
        $this->authPass = $password;
    }

    /**
     * Получает информацию о туре по его ID
     */
    public function getTourInfo($tourId)
    {
        $url = "http://tourvisor.ru/xml/actualize.php?" . http_build_query([
            'authlogin' => $this->authLogin,
            'authpass' => $this->authPass,
            'tourid' => $tourId,
            'currency' => $this->currency,
            'request' => 0,
            'format' => 'json'
        ]);

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data['data']['tour'])) {
            return $data['data']['tour'];
        }

        return null;
    }

    /**
     * Ищет аналогичные туры от других операторов
     */
    public function findSimilarTours($tourInfo)
    {
        // Параметры для поиска аналогичных туров
        $params = [
            'authlogin' => $this->authLogin,
            'authpass' => $this->authPass,
            'departure' => $tourInfo['departurecode'],
            'country' => $tourInfo['countrycode'],
            'datefrom' => $tourInfo['flydate'],
            'dateto' => $tourInfo['flydate'],
            'nightsfrom' => $tourInfo['nights'],
            'nightsto' => $tourInfo['nights'],
            'adults' => $tourInfo['adults'],
            'child' => $tourInfo['child'],
            'childage1' => $tourInfo['childage1'] ?? null,
            'childage2' => $tourInfo['childage2'] ?? null,
            'childage3' => $tourInfo['childage3'] ?? null,
            'hotels' => $tourInfo['hotelcode'],
            'meal' => $this->getMealCode($tourInfo['meal']),
            'currency' => $this->currency,
            'format' => 'json',
            'operatorstatus' => 1 // Добавляем информацию по операторам
        ];

        // Удаляем пустые параметры
        $params = array_filter($params, function ($value) {
            return $value !== null;
        });

        // 1. Создаем поисковый запрос
        $searchUrl = "http://tourvisor.ru/xml/search.php?" . http_build_query($params);
        $searchResponse = file_get_contents($searchUrl);
        $searchData = json_decode($searchResponse, true);



        if (!isset($searchData['result']['requestid'])) {
            return [];
        }

        $requestId = $searchData['result']['requestid'];

        $tries = 0;
        $maxTries = 10;
        $foundTours = [];

        while ($tries < $maxTries) {
            sleep(1); // Ждем 1 секунду между проверками
            $tries++;

            // Проверяем статус поиска
            $statusUrl = "http://tourvisor.ru/xml/result.php?" . http_build_query([
                'authlogin' => $this->authLogin,
                'authpass' => $this->authPass,
                'requestid' => $requestId,
                'type' => 'result',
                'format' => 'json',
                'operatorstatus' => 1,
                'onpage' => 100 // Увеличиваем количество результатов на странице
            ]);

            $statusResponse = file_get_contents($statusUrl);
            $statusData = json_decode($statusResponse, true);


            if (isset($statusData['data']['status']['state']) && $statusData['data']['status']['state'] == 'finished') {
                // Поиск завершен, собираем результаты
                if (isset($statusData['data']['result']['hotel'])) {
                    // Обрабатываем случай, когда отель один или несколько
                    $hotels = isset($statusData['data']['result']['hotel'][0]) ?
                        $statusData['data']['result']['hotel'] :
                        [$statusData['data']['result']['hotel']];

                    foreach ($hotels as $hotel) {
                        if (isset($hotel['tours']['tour'])) {
                            $tours = isset($hotel['tours']['tour'][0]) ?
                                $hotel['tours']['tour'] :
                                [$hotel['tours']['tour']];

                            foreach ($tours as $tour) {
                                // Исключаем исходный тур
                                if (!isset($tourInfo['tourid']) || $tour['tourid'] != $tourInfo['tourid']) {
                                    $foundTours[] = [
                                        'tourId' => $tour['tourid'],
                                        'operator' => $tour['operatorname'],
                                        'price' => $tour['price'],
                                        'hotel' => $hotel['hotelname'],
                                        'currency' => $tour['currency'],
                                        'link' => $tour,
                                        'fly_date' => $tour['flydate'],
                                        'nights' => $tour['nights'],
                                        'meal' => $tour['mealrussian'] ?? $tour['meal'] ?? 'Не указано',
                                        'room' => $tour['room'] ?? 'Не указано'
                                    ];
                                }
                            }
                        }
                    }
                }
                break;
            }
        }

        return $foundTours;
    }

    /**
     * Преобразует название питания в код для поиска
     */
    private function getMealCode($mealName)
    {
        $meals = [
            'RO' => 1,  // Только завтрак
            'BB' => 1,   // Только завтрак
            'HB' => 2,   // Полупансион
            'FB' => 3,   // Полный пансион
            'AI' => 4,   // Все включено
            'UAI' => 5,  // Ультра все включено
        ];

        return $meals[$mealName] ?? null;
    }


    public function compareTours($tourId)
    {
        // 1. Получаем информацию о туре
        $tourInfo = $this->getTourInfo($tourId);

        if (!$tourInfo) {
            return ['error' => 'Тур не найден'];
        }

        // 2. Ищем аналогичные туры
        $similarTours = $this->findSimilarTours($tourInfo);

        // 3. Формируем результат
        $result = [
            'original_tour' => [
                'operator' => $tourInfo['operatorname'],
                'hotel' => $tourInfo['hotelname'],
                'price' => $tourInfo['price'],
                'currency' => $tourInfo['currency'],
                'link' => $tourInfo['operatorlink'],
                'fly_date' => $tourInfo['flydate'],
                'nights' => $tourInfo['nights'],
                'meal' => $tourInfo['meal'],
                'room' => $tourInfo['room']
            ],
            'similar_tours' => $similarTours
        ];

        return $result;
    }
}

// Использование класса
if (isset($_GET['tourid'])) {
    $comparator = new TourComparator($tourvisor_login, $tourvisor_password);
    $result = $comparator->compareTours($_GET['tourid']);

    header('Content-Type: application/json');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'Не указан ID тура'], JSON_UNESCAPED_UNICODE);
}
?>