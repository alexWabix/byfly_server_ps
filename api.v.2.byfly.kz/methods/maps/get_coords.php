<?php
// Файл: methods/maps/get_hotels_with_coords.php

if (empty($_POST['search_id'])) {
    $resp = array(
        "type" => false,
        "msg" => "ID поиска не передан",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$search_id = $_POST['search_id'];
$user_id = $_POST['user_id'] ?? '';

// Проверяем права пользователя на снятие комиссии
$commission_remove = false;
$commission_percent = 7;

if (!empty($user_id)) {
    // Используем существующую функцию isUserAgent если она есть
    if (function_exists('isUserAgent')) {
        $isAgent = isUserAgent($user_id);
    } else {
        // Проверяем статус пользователя напрямую
        $userQuery = $db->query("SELECT user_status FROM users WHERE id='$user_id'");
        if ($userQuery->num_rows > 0) {
            $user = $userQuery->fetch_assoc();
            $isAgent = in_array($user['user_status'], ['agent', 'coach', 'alpha', 'ambasador']);
        } else {
            $isAgent = false;
        }
    }

    if ($isAgent) {
        $commission_remove = true;
        $commission_percent = 6;
    }
}

try {
    // Получаем результаты поиска по search_id из TourVisor
    $search_results = getTourVisorResultsBySearchId($search_id);

    if (empty($search_results)) {
        $resp = array(
            "type" => false,
            "msg" => "Результаты поиска не найдены по указанному ID",
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Применяем снятие комиссии если нужно
    if ($commission_remove && $commission_percent > 0) {
        $search_results = applyCommissionRemovalMap($search_results, $commission_percent);
    }

    // Получаем координаты для отелей
    $hotels_with_coords = getHotelsWithCoordinatesMap($search_results);

    // Статистика
    $stats = [
        'total_hotels' => count($search_results),
        'hotels_with_coords' => count($hotels_with_coords),
        'search_id' => $search_id,
        'commission_removed' => $commission_remove
    ];

    $resp = array(
        "type" => true,
        "data" => array(
            "hotels" => $hotels_with_coords,
            "stats" => $stats
        )
    );

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => "Ошибка при получении данных: " . $e->getMessage(),
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);

/**
 * Получает результаты поиска из TourVisor по search_id
 * @param string $search_id ID поиска в TourVisor
 * @return array Массив отелей
 */
function getTourVisorResultsBySearchId($search_id)
{
    global $tourvisor_login, $tourvisor_password;

    $hotels = [];
    $page = 1;
    $max_pages = 10; // Увеличиваем количество страниц для получения всех отелей

    while ($page <= $max_pages) {
        $result_query = [
            "authlogin" => $tourvisor_login,
            "authpass" => $tourvisor_password,
            "format" => "json",
            "requestid" => $search_id,
            "type" => "result",
            "page" => $page,
            "onpage" => 100, // Максимальное количество отелей на страницу
            "nodescription" => 0
        ];

        $result_url = 'https://tourvisor.ru/xml/result.php?' . http_build_query($result_query);

        // Используем cURL для более надежного запроса
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $result_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ByFly Travel API/1.0');

        $result_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result_response !== false && $http_code == 200) {
            $result_data = json_decode($result_response, true);

            // Проверяем статус поиска
            if (isset($result_data['data']['status'])) {
                $status = $result_data['data']['status'];

                // Если поиск еще не завершен, ждем немного
                if (isset($status['state']) && $status['state'] === 'searching' && $page == 1) {
                    sleep(3); // Ждем 3 секунды для завершения поиска
                    continue; // Повторяем запрос
                }
            }

            if (
                isset($result_data['data']['result']['hotel']) &&
                is_array($result_data['data']['result']['hotel'])
            ) {

                $page_hotels = $result_data['data']['result']['hotel'];
                $hotels = array_merge($hotels, $page_hotels);

                // Если отелей на странице меньше чем onpage, значит это последняя страница
                if (count($page_hotels) < 100) {
                    break;
                }
            } else {
                // Если нет отелей на текущей странице, проверяем есть ли ошибка
                if (
                    isset($result_data['data']['status']['state']) &&
                    $result_data['data']['status']['state'] === 'no search results'
                ) {
                    break;
                }

                // Если это первая страница и нет результатов, возможно поиск еще не готов
                if ($page == 1 && empty($hotels)) {
                    sleep(2);
                    continue;
                }

                break;
            }
        } else {
            error_log("Failed to fetch TourVisor results for search_id: $search_id, page: $page, HTTP code: $http_code");

            // Если это первая страница, пытаемся еще раз
            if ($page == 1) {
                sleep(2);
                continue;
            }

            break;
        }

        $page++;

        // Небольшая пауза между страницами
        if ($page <= $max_pages) {
            usleep(100000); // 0.1 секунды
        }
    }

    return $hotels;
}

/**
 * Применяет снятие комиссии к результатам поиска
 * @param array $hotels Массив отелей
 * @param int $commission_percent Процент комиссии
 * @return array Массив отелей с пересчитанными ценами
 */
function applyCommissionRemovalMap($hotels, $commission_percent)
{
    foreach ($hotels as &$hotel) {
        // Пересчитываем цену отеля
        if (isset($hotel['price']) && $hotel['price'] > 0) {
            $price_without_commission = $hotel['price'] / (1 + ($commission_percent / 100));
            $hotel['price'] = round($price_without_commission);
        }

        // Обрабатываем туры в отеле
        if (isset($hotel['tours']['tour']) && is_array($hotel['tours']['tour'])) {
            $new_tours = [];
            foreach ($hotel['tours']['tour'] as $tour) {
                if (isset($tour['price']) && $tour['price'] > 0) {
                    $tour_price_without_commission = $tour['price'] / (1 + ($commission_percent / 100));
                    $tour['price'] = round($tour_price_without_commission);
                }
                $new_tours[] = $tour;
            }
            $hotel['tours']['tour'] = $new_tours;
        }
    }

    // Сортируем по цене
    usort($hotels, function ($a, $b) {
        $price_a = isset($a['price']) ? $a['price'] : 0;
        $price_b = isset($b['price']) ? $b['price'] : 0;
        return $price_a <=> $price_b;
    });

    return $hotels;
}

/**
 * Получает координаты для отелей и возвращает только те, у которых есть координаты
 * @param array $hotels Массив отелей
 * @return array Массив отелей с координатами
 */
function getHotelsWithCoordinatesMap($hotels)
{
    global $db, $tourvisor_login, $tourvisor_password;

    if (empty($hotels)) {
        return [];
    }

    $hotels_with_coords = [];
    $hotel_codes = [];
    $hotels_by_code = [];

    // Собираем уникальные коды отелей
    foreach ($hotels as $hotel) {
        if (isset($hotel['hotelcode'])) {
            $hotel_code = $hotel['hotelcode'];
            if (!in_array($hotel_code, $hotel_codes)) {
                $hotel_codes[] = $hotel_code;
                $hotels_by_code[$hotel_code] = $hotel;
            }
        }
    }

    if (empty($hotel_codes)) {
        return [];
    }

    // Сначала проверяем координаты в нашей базе данных
    $coordinates_from_db = getCoordinatesFromDatabaseMap($hotel_codes);

    // Определяем отели без координат
    $missing_hotel_codes = [];
    foreach ($hotel_codes as $hotel_code) {
        if (!isset($coordinates_from_db[$hotel_code])) {
            $missing_hotel_codes[] = $hotel_code;
        }
    }

    // Получаем недостающие координаты из TourVisor API
    $coordinates_from_api = [];
    if (!empty($missing_hotel_codes)) {
        $coordinates_from_api = getCoordinatesFromTourVisorMap($missing_hotel_codes);
    }

    // Объединяем координаты
    $all_coordinates = array_merge($coordinates_from_db, $coordinates_from_api);

    // Формируем результат - только отели с координатами
    foreach ($hotels_by_code as $hotel_code => $hotel) {
        if (isset($all_coordinates[$hotel_code])) {
            $coord_data = $all_coordinates[$hotel_code];
            $hotel['latitude'] = $coord_data['latitude'];
            $hotel['longitude'] = $coord_data['longitude'];
            $hotel['coord_source'] = $coord_data['source'];
            $hotels_with_coords[] = $hotel;
        }
    }

    return $hotels_with_coords;
}

/**
 * Получает координаты отелей из базы данных
 * @param array $hotel_codes Коды отелей
 * @return array Массив координат
 */
function getCoordinatesFromDatabaseMap($hotel_codes)
{
    global $db;

    $coordinates = [];

    if (empty($hotel_codes)) {
        return $coordinates;
    }

    $hotel_codes_str = implode(',', array_map('intval', $hotel_codes));
    $query = "SELECT hotel_code, latitude, longitude, hotel_name, updated_at 
              FROM hotel_coordinates 
              WHERE hotel_code IN ($hotel_codes_str) 
              AND latitude IS NOT NULL 
              AND longitude IS NOT NULL
              AND latitude != 0 
              AND longitude != 0";

    $result = $db->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $coordinates[$row['hotel_code']] = [
                'hotel_code' => $row['hotel_code'],
                'latitude' => floatval($row['latitude']),
                'longitude' => floatval($row['longitude']),
                'hotel_name' => $row['hotel_name'],
                'source' => 'database',
                'updated_at' => $row['updated_at']
            ];
        }
    }

    return $coordinates;
}

/**
 * Получает координаты отелей из TourVisor API
 * @param array $hotel_codes Массив кодов отелей
 * @return array Массив координат отелей
 */
function getCoordinatesFromTourVisorMap($hotel_codes)
{
    global $db, $tourvisor_login, $tourvisor_password;

    if (empty($hotel_codes)) {
        return [];
    }

    $coordinates = [];
    $batch_size = 5; // Уменьшаем размер батча для стабильности
    $batches = array_chunk($hotel_codes, $batch_size);

    foreach ($batches as $batch_index => $batch) {
        $hotel_requests = [];

        // Подготавливаем запросы для батча
        foreach ($batch as $hotel_code) {
            $query = [
                "authlogin" => $tourvisor_login,
                "authpass" => $tourvisor_password,
                "format" => "json",
                "hotelcode" => $hotel_code
            ];

            $url = 'https://tourvisor.ru/xml/hotel.php?' . http_build_query($query);
            $hotel_requests[$hotel_code] = $url;
        }

        // Выполняем множественные запросы
        $hotel_responses = multiRequestMap($hotel_requests);

        // Обрабатываем ответы
        foreach ($hotel_responses as $hotel_code => $response_data) {
            try {
                if (empty($response_data) || $response_data === '{"error": "Failed to fetch data"}') {
                    continue;
                }

                $hotel_data = json_decode($response_data, true);

                if (
                    isset($hotel_data['data']) &&
                    isset($hotel_data['data']['coord1']) &&
                    isset($hotel_data['data']['coord2']) &&
                    !empty($hotel_data['data']['coord1']) &&
                    !empty($hotel_data['data']['coord2'])
                ) {

                    $latitude = floatval($hotel_data['data']['coord1']);
                    $longitude = floatval($hotel_data['data']['coord2']);
                    $hotel_name = isset($hotel_data['data']['name']) ? $hotel_data['data']['name'] : '';

                    // Проверяем валидность координат
                    if (
                        $latitude != 0 && $longitude != 0 &&
                        $latitude >= -90 && $latitude <= 90 &&
                        $longitude >= -180 && $longitude <= 180
                    ) {

                        $coordinates[$hotel_code] = [
                            'hotel_code' => $hotel_code,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'hotel_name' => $hotel_name,
                            'source' => 'tourvisor_api',
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        // Сохраняем координаты в базу данных для будущего использования
                        saveHotelCoordinatesMap($hotel_code, $latitude, $longitude, $hotel_name);
                    }
                }
            } catch (Exception $e) {
                error_log("Error processing hotel $hotel_code: " . $e->getMessage());
            }
        }

        // Пауза между батчами для соблюдения лимитов API
        if ($batch_index < count($batches) - 1) {
            usleep(400000); // 0.4 секунды
        }
    }

    return $coordinates;
}

/**
 * Сохраняет координаты отеля в базу данных
 * @param string $hotel_code Код отеля
 * @param float $latitude Широта
 * @param float $longitude Долгота
 * @param string $hotel_name Название отеля
 */
function saveHotelCoordinatesMap($hotel_code, $latitude, $longitude, $hotel_name)
{
    global $db;

    try {
        // Проверяем существует ли таблица
        $tableCheck = $db->query("SHOW TABLES LIKE 'hotel_coordinates'");
        if ($tableCheck->num_rows == 0) {
            // Создаем таблицу если не существует
            $createTable = "CREATE TABLE IF NOT EXISTS `hotel_coordinates` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `hotel_code` int(11) NOT NULL,
                `latitude` decimal(10,8) DEFAULT NULL,
                `longitude` decimal(11,8) DEFAULT NULL,
                `hotel_name` varchar(255) DEFAULT NULL,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `hotel_code` (`hotel_code`),
                INDEX `idx_coordinates` (`latitude`, `longitude`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $db->query($createTable);
        }

        $stmt = $db->prepare("
            INSERT INTO hotel_coordinates (hotel_code, latitude, longitude, hotel_name, updated_at) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude), 
                longitude = VALUES(longitude), 
                hotel_name = VALUES(hotel_name),
                updated_at = NOW()
        ");

        if ($stmt) {
            $stmt->bind_param("idds", $hotel_code, $latitude, $longitude, $hotel_name);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error saving hotel coordinates for hotel $hotel_code: " . $e->getMessage());
    }
}

/**
 * Выполняет множественные HTTP запросы параллельно
 * @param array $urls Массив URL для запросов
 * @return array Массив ответов
 */
function multiRequestMap($urls)
{
    if (empty($urls)) {
        return [];
    }

    $mh = curl_multi_init();
    $curl_array = [];

    // Настройки для каждого запроса
    foreach ($urls as $key => $url) {
        $curl_array[$key] = curl_init($url);
        curl_setopt($curl_array[$key], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_array[$key], CURLOPT_TIMEOUT, 15);
        curl_setopt($curl_array[$key], CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($curl_array[$key], CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_array[$key], CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_array[$key], CURLOPT_USERAGENT, 'ByFly Travel API/1.0');
        curl_setopt($curl_array[$key], CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl_array[$key], CURLOPT_MAXREDIRS, 3);
        curl_multi_add_handle($mh, $curl_array[$key]);
    }

    // Выполняем запросы
    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 0.2);
        }
    } while ($running > 0);

    // Собираем ответы
    $responses = [];
    foreach ($urls as $key => $url) {
        $content = curl_multi_getcontent($curl_array[$key]);
        $http_code = curl_getinfo($curl_array[$key], CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl_array[$key]);

        if ($http_code == 200 && $content !== false && empty($curl_error)) {
            $responses[$key] = $content;
        } else {
            error_log("Failed to fetch data for key $key. HTTP Code: $http_code, Error: $curl_error");
            $responses[$key] = '{"error": "Failed to fetch data"}';
        }

        curl_multi_remove_handle($mh, $curl_array[$key]);
        curl_close($curl_array[$key]);
    }

    curl_multi_close($mh);
    return $responses;
}
?>