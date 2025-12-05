<?php
header('Content-Type: application/json; charset=utf-8');

include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$tourvisorLogin = $tourvisor_login;
$tourvisorPass = $tourvisor_password;

// Настройки ценовых категорий (в тенге)
define('CHEAP_MAX_PRICE', 400000);
define('MEDIUM_MAX_PRICE', 1000000);
define('RECOMMENDED_MAX_PRICE', 500000);
define('MIN_HOTEL_RATING', 3.6);
define('MIN_HOTEL_STARS', 4);
define('MAX_ATTEMPTS', 10);
define('BASE_WAIT_TIME', 5);

// Создаем таблицу если ее нет
$createTableQuery = "
CREATE TABLE IF NOT EXISTS byfly_super_offers_tours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id VARCHAR(50) NOT NULL,
    request_id VARCHAR(50) NOT NULL,
    departure_city_id INT NOT NULL,
    departure_city_name VARCHAR(100) NOT NULL,
    country_id INT NOT NULL,
    country_name VARCHAR(100) NOT NULL,
    country_visor_id INT NOT NULL,
    region_id INT NOT NULL,
    region_name VARCHAR(100) NOT NULL,
    subregion_id INT DEFAULT NULL,
    subregion_name VARCHAR(100) DEFAULT NULL,
    hotel_code INT NOT NULL,
    hotel_name VARCHAR(255) NOT NULL,
    hotel_stars INT NOT NULL,
    hotel_rating FLOAT,
    hotel_description TEXT,
    hotel_full_desc_link VARCHAR(255),
    hotel_review_link VARCHAR(255),
    hotel_picture_link VARCHAR(255),
    hotel_has_photos TINYINT(1) DEFAULT 0,
    hotel_has_coords TINYINT(1) DEFAULT 0,
    hotel_has_description TINYINT(1) DEFAULT 0,
    hotel_has_reviews TINYINT(1) DEFAULT 0,
    sea_distance INT DEFAULT NULL,
    operator_id INT NOT NULL,
    operator_name VARCHAR(100) NOT NULL,
    departure_date DATE NOT NULL,
    nights INT NOT NULL,
    price INT NOT NULL,
    fuel_charge INT NOT NULL,
    price_ue INT,
    price_per_person INT NOT NULL,
    placement_type VARCHAR(50) NOT NULL,
    adults INT NOT NULL,
    children INT NOT NULL,
    meal_type VARCHAR(50) NOT NULL,
    meal_type_full VARCHAR(100) NOT NULL,
    room_type VARCHAR(100) NOT NULL,
    tour_name VARCHAR(255),
    tour_link VARCHAR(255),
    currency VARCHAR(3) NOT NULL DEFAULT 'KZT',
    is_regular TINYINT(1) DEFAULT 0,
    is_promo TINYINT(1) DEFAULT 0,
    has_surcharge TINYINT(1) DEFAULT 0,
    flight_status TINYINT,
    hotel_status TINYINT,
    night_flight INT DEFAULT NULL,
    base_price INT NOT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    commission_amount INT NOT NULL,
    price_category ENUM('cheap', 'medium', 'luxury') NOT NULL,
    departure_period ENUM('this_month', 'next_month', 'early_booking') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    is_show TINYINT(1) DEFAULT 1,
    dop_sales INT DEFAULT 0,
    date_deleted DATETIME DEFAULT NULL,
    count_places INT DEFAULT 0,
    count_sales INT DEFAULT 0,
    rashodes INT DEFAULT 0,
    max_sales INT DEFAULT 0,
    is_hot_tour TINYINT(1) DEFAULT 0,
    is_recommended TINYINT(1) DEFAULT 0,
    old_price INT DEFAULT NULL,
    discount_percent INT DEFAULT NULL,
    tour_info TEXT,
    UNIQUE KEY unique_tour (tour_id),
    INDEX idx_departure_date (departure_date),
    INDEX idx_price_category (price_category),
    INDEX idx_operator (operator_id),
    INDEX idx_hotel (hotel_code),
    INDEX idx_country (country_id),
    INDEX idx_region (region_id),
    INDEX idx_recommended (is_recommended)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$db->query($createTableQuery);

function makeTourvisorRequest($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Ошибка cURL: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        throw new Exception("HTTP ошибка: $httpCode");
    }

    curl_close($ch);
    return $response;
}

function checkSearchStatus($requestId, $login, $pass)
{
    $attempt = 0;
    $waitTime = BASE_WAIT_TIME;

    while ($attempt < MAX_ATTEMPTS) {
        $attempt++;
        $statusUrl = 'https://tourvisor.ru/xml/result.php?authlogin=' . $login . '&authpass=' . $pass . '&requestid=' . $requestId . '&type=status&format=json';

        try {
            $statusResponse = makeTourvisorRequest($statusUrl);
            $statusData = json_decode($statusResponse, true);

            if (!empty($statusData['data']['status'])) {
                $status = $statusData['data']['status'];
                if ($status['state'] == 'finished' || $status['progress'] >= 100) {
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("Ошибка проверки статуса: " . $e->getMessage());
        }

        sleep($waitTime);
        $waitTime = min($waitTime * 1.5, 10);
    }

    return false;
}

function getPriceCategory($price)
{
    if ($price <= CHEAP_MAX_PRICE)
        return 'cheap';
    if ($price <= MEDIUM_MAX_PRICE)
        return 'medium';
    return 'luxury';
}

function getMealTypeFull($mealCode)
{
    $mealTypes = [
        'RO' => 'Без питания',
        'BB' => 'Только завтрак',
        'HB' => 'Завтра и Ужин',
        'FB' => 'Завтра, Обед, Ужин',
        'AI' => 'Все включено',
        'UAI' => 'Ультра все включено',
    ];
    return $mealTypes[$mealCode] ?? 'Не указано';
}

function calculateCommission($price, $operatorId, $isPromo)
{
    $commissionPercent = $isPromo ? 4.00 : 5.00;
    $basePrice = round($price / (1 + ($commissionPercent / 100)));
    return [
        'percent' => $commissionPercent,
        'base_price' => $basePrice,
        'amount' => $price - $basePrice
    ];
}

function isRecommendedTour($price, $hotelRating, $hotelStars, $mealType)
{
    return $price <= RECOMMENDED_MAX_PRICE &&
        $hotelRating >= MIN_HOTEL_RATING &&
        $hotelStars >= MIN_HOTEL_STARS;
}

function processHotTours($direction, $login, $pass, $db)
{
    $hotToursParams = [
        'authlogin' => $login,
        'authpass' => $pass,
        'city' => $direction['departure_visor_id'],
        'items' => 100,
        'sort' => 1,
        'currency' => 3,
        'format' => 'json'
    ];

    $hotToursUrl = 'https://tourvisor.ru/xml/hottours.php?' . http_build_query($hotToursParams);

    try {
        $hotToursResponse = makeTourvisorRequest($hotToursUrl);
        $hotToursData = json_decode($hotToursResponse, true);

        if (empty($hotToursData['hottours']['tour'])) {
            return 0;
        }

        $hotTours = isset($hotToursData['hottours']['tour'][0])
            ? $hotToursData['hottours']['tour']
            : [$hotToursData['hottours']['tour']];

        $countAdded = 0;
        $hotelPrices = []; // Для хранения минимальных цен по отелям

        foreach ($hotTours as $hotTour) {
            // Проверка обязательных полей
            $requiredFields = ['countrycode', 'hotelcode', 'price', 'hotelrating', 'hotelstars', 'meal', 'flydate', 'nights'];
            foreach ($requiredFields as $field) {
                if (!isset($hotTour[$field])) {
                    continue 2;
                }
            }

            if (
                $hotTour['countrycode'] != $direction['country_visor_id'] ||
                empty($hotTour['hotelpicture']) ||
                $hotTour['hotelstars'] < MIN_HOTEL_STARS
            ) {
                continue;
            }

            $price = (int) $hotTour['price'];
            $price = $price * 2;

            $hotelCode = (int) $hotTour['hotelcode'];

            // Проверяем, есть ли уже более дешевое предложение для этого отеля
            if (isset($hotelPrices[$hotelCode]) && $price >= $hotelPrices[$hotelCode]) {
                continue; // Пропускаем более дорогие предложения
            }

            $hotelPrices[$hotelCode] = $price; // Запоминаем минимальную цену

            $hotelRating = (float) $hotTour['hotelrating'];
            $hotelStars = (int) $hotTour['hotelstars'];
            $mealType = $hotTour['meal'];

            // Значения по умолчанию
            $flightStatus = 2;
            $hotelStatus = 2;
            $isRegular = 0;
            $isPromo = 0;
            $nightFlight = null;
            $hasSurcharge = 0;

            $isRecommended = isRecommendedTour($price, $hotelRating, $hotelStars, $mealType);
            $category = getPriceCategory($price);

            $oldPrice = isset($hotTour['priceold']) ? (int) $hotTour['priceold'] : null;
            $discountPercent = $oldPrice ? round(($oldPrice - $price) / $oldPrice * 100) : null;
            $oldPrice = $oldPrice * 2;

            $commissionData = calculateCommission($price, $hotTour['operatorcode'], $isPromo);
            $rashod = ceil(($commissionData['amount'] / 100) * 20) + ceil(($price / 100) * 1);

            try {
                $departureDate = new DateTime($hotTour['flydate']);
                $now = new DateTime();
                $interval = $now->diff($departureDate);
                $departurePeriod = ($interval->days <= 14) ? 'this_month' :
                    (($interval->days <= 45) ? 'next_month' : 'early_booking');
            } catch (Exception $e) {
                $departurePeriod = 'early_booking';
            }

            $tourData = [
                'tour_id' => $db->real_escape_string($hotTour['tourid']),
                'request_id' => 'hot_tour_' . time(),
                'departure_city_id' => (int) $direction['departure_visor_id'],
                'departure_city_name' => $db->real_escape_string($direction['departure_city_name']),
                'country_id' => (int) $direction['country_id'],
                'country_name' => $db->real_escape_string($hotTour['countryname'] ?? ''),
                'country_visor_id' => (int) $direction['country_visor_id'],
                'region_id' => isset($hotTour['hotelregioncode']) ? (int) $hotTour['hotelregioncode'] : 0,
                'region_name' => $db->real_escape_string($hotTour['hotelregionname'] ?? ''),
                'subregion_id' => null,
                'subregion_name' => null,
                'hotel_code' => (int) $hotTour['hotelcode'],
                'hotel_name' => $db->real_escape_string($hotTour['hotelname'] ?? ''),
                'hotel_stars' => $hotelStars,
                'hotel_rating' => $hotelRating,
                'hotel_description' => null,
                'hotel_full_desc_link' => null,
                'hotel_review_link' => null,
                'hotel_picture_link' => $db->real_escape_string($hotTour['hotelpicture']),
                'hotel_has_photos' => 1,
                'hotel_has_coords' => 0,
                'hotel_has_description' => 0,
                'hotel_has_reviews' => 0,
                'sea_distance' => null,
                'operator_id' => (int) $hotTour['operatorcode'],
                'operator_name' => $db->real_escape_string($hotTour['operatorname'] ?? ''),
                'departure_date' => $db->real_escape_string(date('Y-m-d', strtotime($hotTour['flydate']))),
                'nights' => (int) $hotTour['nights'],
                'price' => $price,
                'fuel_charge' => 0,
                'price_ue' => null,
                'price_per_person' => (int) ($price / 2),
                'placement_type' => 'DBL',
                'adults' => 2,
                'children' => 0,
                'meal_type' => $db->real_escape_string($mealType),
                'meal_type_full' => $db->real_escape_string(getMealTypeFull($mealType)),
                'room_type' => 'Standard',
                'tour_name' => '',
                'tour_link' => isset($hotTour['tourlink']) ? $db->real_escape_string($hotTour['tourlink']) : '',
                'currency' => 'KZT',
                'is_regular' => $isRegular,
                'is_promo' => $isPromo,
                'has_surcharge' => $hasSurcharge,
                'flight_status' => $flightStatus,
                'hotel_status' => $hotelStatus,
                'night_flight' => $nightFlight,
                'base_price' => (int) $commissionData['base_price'],
                'commission_percent' => (float) $commissionData['percent'],
                'commission_amount' => (int) $commissionData['amount'],
                'price_category' => $category,
                'departure_period' => $departurePeriod,
                'rashodes' => (int) $rashod,
                'max_sales' => 10,
                'is_hot_tour' => 1,
                'is_recommended' => $isRecommended ? 1 : 0,
                'old_price' => $oldPrice,
                'discount_percent' => $discountPercent,
                'tour_info' => $db->real_escape_string(json_encode($hotTour, JSON_UNESCAPED_UNICODE)),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'is_show' => 0,
                'dop_sales' => 0,
                'date_deleted' => null,
                'count_places' => 2,
                'count_sales' => 0
            ];

            $inserted = true;
            $searchHotelCodeDB = $db->query("SELECT * FROM byfly_super_offers_tours WHERE hotel_code = '" . $tourData['hotel_code'] . "' AND departure_city_id = '" . $tourData['departure_city_id'] . "'");
            if ($searchHotelCodeDB->num_rows > 0) {
                $searchHotelCode = $searchHotelCodeDB->fetch_assoc();
                if ($searchHotelCode['price'] > $tourData['price']) {
                    $db->query("DELETE FROM byfly_super_offers_tours WHERE hotel_code = '" . $tourData['hotel_code'] . "' AND departure_city_id = '" . $tourData['departure_city_id'] . "'");
                    $inserted = true;
                } else {
                    $inserted = false;
                }
            } else {
                $inserted = true;
            }


            if (
                empty($tourData['tour_id']) == false && empty($tourData['hotel_code']) == false &&
                empty($tourData['departure_city_name']) == false &&
                (!empty($tourData['hotel_rating']) && $tourData['hotel_rating'] >= 3.2 && $inserted)
            ) {

                $columns = implode(',', array_keys($tourData));
                $values = "'" . implode("','", array_values($tourData)) . "'";

                $insertQuery = "INSERT INTO byfly_super_offers_tours ($columns) VALUES ($values)";

                if ($db->query($insertQuery)) {
                    $countAdded++;
                }
            }
        }

        return $countAdded;
    } catch (Exception $e) {
        error_log("Ошибка обработки горящих туров: " . $e->getMessage());
        return 0;
    }
}

function processRegularTours($direction, $period, $priceFrom, $priceTo, $login, $pass, $db)
{
    $queryParams = [
        'authlogin' => $login,
        'authpass' => $pass,
        'departure' => $direction['departure_visor_id'],
        'country' => $direction['country_visor_id'],
        'datefrom' => $period['datefrom'],
        'dateto' => $period['dateto'],
        'nightsfrom' => 2,
        'nightsto' => 14,
        'adults' => 2,
        'currency' => 3,
        'hideregular' => 0,
        'pricetype' => 0,
        'items' => 30,
        'pricefrom' => $priceFrom,
        'stars' => MIN_HOTEL_STARS,
        'hotelstatus' => 2,
        'flightstatus' => 2,
        'format' => 'json'
    ];

    if ($priceTo > 0) {
        $queryParams['priceto'] = $priceTo;
    }

    try {
        $searchUrl = 'https://tourvisor.ru/xml/search.php?' . http_build_query($queryParams);
        $searchResponse = makeTourvisorRequest($searchUrl);
        $searchData = json_decode($searchResponse, true);

        if (empty($searchData['result']['requestid'])) {
            return 0;
        }

        $requestId = $searchData['result']['requestid'];
        if (!checkSearchStatus($requestId, $login, $pass)) {
            return 0;
        }

        $resultUrl = 'https://tourvisor.ru/xml/result.php?authlogin=' . $login . '&authpass=' . $pass . '&requestid=' . $requestId . '&format=json&onpage=20';
        $resultResponse = makeTourvisorRequest($resultUrl);
        $resultData = json_decode($resultResponse, true);

        if (empty($resultData['data']['result']['hotel'])) {
            return 0;
        }

        $countAdded = 0;
        $hotels = isset($resultData['data']['result']['hotel'][0])
            ? $resultData['data']['result']['hotel']
            : [$resultData['data']['result']['hotel']];

        $hotelPrices = []; // Для хранения минимальных цен по отелям

        foreach ($hotels as $hotel) {
            if (
                empty($hotel['tours']['tour']) ||
                empty($hotel['picturelink']) ||
                empty($hotel['hotelrating']) ||
                $hotel['hotelstars'] < MIN_HOTEL_STARS
            ) {
                continue;
            }

            $tours = isset($hotel['tours']['tour'][0])
                ? $hotel['tours']['tour']
                : [$hotel['tours']['tour']];

            foreach ($tours as $tour) {
                // Проверка статусов тура
                $flightStatus = isset($tour['flightstatus']) ? (int) $tour['flightstatus'] : 2;
                $hotelStatus = isset($tour['hotelstatus']) ? (int) $tour['hotelstatus'] : 2;
                $hasSurcharge = isset($tour['has_surcharge']) ? (int) $tour['has_surcharge'] : 0;
                $isOnRequest = isset($tour['onrequest']) ? (int) $tour['onrequest'] : 0;
                $isRegular = isset($tour['regular']) ? (int) $tour['regular'] : 0;
                $isPromo = isset($tour['promo']) ? (int) $tour['promo'] : 0;
                $nightFlight = isset($tour['nightflight']) ? (int) $tour['nightflight'] : null;

                if ($isOnRequest || $hasSurcharge || $flightStatus == 1 || $hotelStatus == 1) {
                    continue;
                }

                $price = (int) $tour['price'];
                $hotelCode = (int) $hotel['hotelcode'];

                // Проверяем, есть ли уже более дешевое предложение для этого отеля
                if (isset($hotelPrices[$hotelCode]) && $price >= $hotelPrices[$hotelCode]) {
                    continue; // Пропускаем более дорогие предложения
                }

                $hotelPrices[$hotelCode] = $price; // Запоминаем минимальную цену

                $category = getPriceCategory($price);
                $hotelRating = (float) $hotel['hotelrating'];
                $hotelStars = (int) $hotel['hotelstars'];
                $mealType = $tour['meal'] ?? '';
                $mealTypeFull = getMealTypeFull($mealType);
                $isRecommended = isRecommendedTour($price, $hotelRating, $hotelStars, $mealType);

                $commissionData = calculateCommission($price, $tour['operatorcode'], $isPromo);
                $rashod = ceil(($commissionData['amount'] / 100) * 20) + ceil(($price / 100) * 1);

                // Подготовка данных для вставки
                $tourData = [
                    'tour_id' => $db->real_escape_string($tour['tourid']),
                    'request_id' => $db->real_escape_string($requestId),
                    'departure_city_id' => (int) $direction['departure_visor_id'],
                    'departure_city_name' => $db->real_escape_string($direction['departure_city_name']),
                    'country_id' => (int) $direction['country_id'],
                    'country_name' => $db->real_escape_string($hotel['countryname'] ?? ''),
                    'country_visor_id' => (int) $direction['country_visor_id'],
                    'region_id' => (int) $hotel['regioncode'],
                    'region_name' => $db->real_escape_string($hotel['regionname'] ?? ''),
                    'subregion_id' => isset($hotel['subregioncode']) ? (int) $hotel['subregioncode'] : null,
                    'subregion_name' => isset($hotel['subregionname']) ? $db->real_escape_string($hotel['subregionname']) : null,
                    'hotel_code' => (int) $hotel['hotelcode'],
                    'hotel_name' => $db->real_escape_string($hotel['hotelname']),
                    'hotel_stars' => $hotelStars,
                    'hotel_rating' => $hotelRating,
                    'hotel_description' => $db->real_escape_string($hotel['hoteldescription'] ?? ''),
                    'hotel_full_desc_link' => $db->real_escape_string($hotel['fulldesclink'] ?? ''),
                    'hotel_review_link' => $db->real_escape_string($hotel['reviewlink'] ?? ''),
                    'hotel_picture_link' => $db->real_escape_string($hotel['picturelink']),
                    'hotel_has_photos' => (int) $hotel['isphoto'],
                    'hotel_has_coords' => (int) $hotel['iscoords'],
                    'hotel_has_description' => (int) $hotel['isdescription'],
                    'hotel_has_reviews' => (int) $hotel['isreviews'],
                    'sea_distance' => isset($hotel['seadistance']) ? (int) $hotel['seadistance'] : null,
                    'operator_id' => (int) $tour['operatorcode'],
                    'operator_name' => $db->real_escape_string($tour['operatorname']),
                    'departure_date' => $db->real_escape_string(date('Y-m-d', strtotime($tour['flydate']))),
                    'nights' => (int) $tour['nights'],
                    'price' => $price,
                    'fuel_charge' => (int) $tour['fuelcharge'],
                    'price_ue' => (int) $tour['priceue'],
                    'price_per_person' => (int) ($price / 2),
                    'placement_type' => $db->real_escape_string($tour['placement']),
                    'adults' => (int) $tour['adults'],
                    'children' => (int) $tour['child'],
                    'meal_type' => $db->real_escape_string($mealType),
                    'meal_type_full' => $db->real_escape_string($mealTypeFull),
                    'room_type' => $db->real_escape_string($tour['room'] ?? 'Standard'),
                    'tour_name' => $db->real_escape_string($tour['tourname'] ?? ''),
                    'tour_link' => $db->real_escape_string($tour['tourlink'] ?? ''),
                    'currency' => 'KZT',
                    'is_regular' => $isRegular,
                    'is_promo' => $isPromo,
                    'has_surcharge' => $hasSurcharge,
                    'flight_status' => $flightStatus,
                    'hotel_status' => $hotelStatus,
                    'night_flight' => $nightFlight,
                    'base_price' => (int) $commissionData['base_price'],
                    'commission_percent' => (float) $commissionData['percent'],
                    'commission_amount' => (int) $commissionData['amount'],
                    'price_category' => $category,
                    'departure_period' => $period['type'],
                    'rashodes' => (int) $rashod,
                    'max_sales' => 10,
                    'is_hot_tour' => 0,
                    'is_recommended' => $isRecommended ? 1 : 0,
                    'old_price' => null,
                    'discount_percent' => null,
                    'tour_info' => $db->real_escape_string(json_encode(array("tour" => $tour, "hotel" => $hotel, ), JSON_UNESCAPED_UNICODE)),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_show' => 0,
                    'dop_sales' => 0,
                    'date_deleted' => null,
                    'count_places' => 2,
                    'count_sales' => 0
                ];

                $inserted = true;
                $searchHotelCodeDB = $db->query("SELECT * FROM byfly_super_offers_tours WHERE hotel_code = '" . $tourData['hotel_code'] . "' AND departure_city_id = '" . $tourData['departure_city_id'] . "'");
                if ($searchHotelCodeDB->num_rows > 0) {
                    $searchHotelCode = $searchHotelCodeDB->fetch_assoc();
                    if ($searchHotelCode['price'] > $tourData['price']) {
                        $db->query("DELETE FROM byfly_super_offers_tours WHERE hotel_code = '" . $tourData['hotel_code'] . "' AND departure_city_id = '" . $tourData['departure_city_id'] . "'");
                        $inserted = true;
                    } else {
                        $inserted = false;
                    }
                } else {
                    $inserted = true;
                }


                if (
                    empty($tourData['tour_id']) == false && empty($tourData['hotel_code']) == false &&
                    empty($tourData['departure_city_name']) == false &&
                    (!empty($tourData['hotel_rating']) && $tourData['hotel_rating'] >= 3.2 && $inserted)
                ) {

                    $columns = implode(',', array_keys($tourData));
                    $values = "'" . implode("','", array_values($tourData)) . "'";

                    $insertQuery = "INSERT INTO byfly_super_offers_tours ($columns) VALUES ($values)";

                    if ($db->query($insertQuery)) {
                        $countAdded++;
                    }
                }
            }
        }

        return $countAdded;
    } catch (Exception $e) {
        error_log("Ошибка обработки обычных туров: " . $e->getMessage());
        return 0;
    }
}

// Основной блок выполнения
try {
    // Получаем направления для поиска
    $directions = $db->query("SELECT dfs.id, dc.id_visor AS departure_visor_id, dc.name AS departure_city_name, c.id AS country_id, c.title AS country_name, c.visor_id AS country_visor_id, c.min_price FROM deportures_for_spec dfs JOIN departure_citys dc ON dfs.deporture_visor_id = dc.id_visor JOIN countries c ON dfs.countries_to = c.visor_id ORDER BY c.sorter ASC");

    $totalHotTours = 0;
    $totalRegularTours = 0;

    if ($directions && $directions->num_rows > 0) {
        while ($direction = $directions->fetch_assoc()) {
            // Обрабатываем горящие туры
            $totalHotTours += processHotTours($direction, $tourvisorLogin, $tourvisorPass, $db);

            // Даты для поиска обычных туров
            $searchPeriods = [
                ['datefrom' => date('d.m.Y'), 'dateto' => date('d.m.Y', strtotime('last day of this month')), 'type' => 'this_month'],
                ['datefrom' => date('d.m.Y', strtotime('first day of next month')), 'dateto' => date('d.m.Y', strtotime('last day of next month')), 'type' => 'next_month'],
                ['datefrom' => date('d.m.Y', strtotime('first day of +2 month')), 'dateto' => date('d.m.Y', strtotime('+3 months')), 'type' => 'early_booking']
            ];

            // Обрабатываем обычные туры по категориям цен
            foreach ($searchPeriods as $period) {
                // Дешевые туры
                $totalRegularTours += processRegularTours($direction, $period, 0, CHEAP_MAX_PRICE, $tourvisorLogin, $tourvisorPass, $db);

                // Средние туры
                $totalRegularTours += processRegularTours($direction, $period, CHEAP_MAX_PRICE + 1, MEDIUM_MAX_PRICE, $tourvisorLogin, $tourvisorPass, $db);

                // Дорогие туры
                $totalRegularTours += processRegularTours($direction, $period, MEDIUM_MAX_PRICE + 1, 0, $tourvisorLogin, $tourvisorPass, $db);
            }
        }
    }


    // Статистика
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN price_category = 'cheap' THEN 1 ELSE 0 END) as cheap,
                    SUM(CASE WHEN price_category = 'medium' THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN price_category = 'luxury' THEN 1 ELSE 0 END) as luxury,
                    SUM(CASE WHEN is_hot_tour = 1 THEN 1 ELSE 0 END) as hot_tours,
                    SUM(CASE WHEN is_recommended = 1 THEN 1 ELSE 0 END) as recommended
                  FROM byfly_super_offers_tours
                  WHERE date_deleted IS NULL";

    $statsResult = $db->query($statsQuery);
    $stats = $statsResult ? $statsResult->fetch_assoc() : [];
    echo json_encode([
        'status' => 'success',
        'message' => 'Туры успешно загружены',
        'stats' => $stats,
        'hot_tours_added' => $totalHotTours,
        'regular_tours_added' => $totalRegularTours
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
