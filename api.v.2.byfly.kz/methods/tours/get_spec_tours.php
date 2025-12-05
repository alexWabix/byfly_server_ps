<?php

function generateTourParams()
{
    return [
        'mindays' => 60,  // Минимум 2 месяца вперед
        'maxdays' => 170, // Максимум 6 месяцев вперед
        'date_label' => 'на сезон вперед',
        'discount_min' => 30,
        'discount_max' => 40,
        'count_place_min' => 0,
        'count_place_max' => 6,
        'min_rating' => 3.0,
        // Параметры для раннего бронирования
        'early_booking' => [
            'mindays' => 90,  // Уменьшаем до 3 месяцев
            'maxdays' => 365, // Максимум 12 месяцев вперед
            'discount_min' => 35, // Скидка от 50%
            'discount_max' => 55, // До 70%
            'count_tours' => [10, 20], // От 3 до 8 туров
            'label' => 'раннее бронирование'
        ],
        // Параметры для супер раннего бронирования
        'super_early_booking' => [
            'mindays' => 180,  // Уменьшаем до 6 месяцев
            'maxdays' => 365, // Максимум 12 месяцев вперед
            'discount' => 60, // Уменьшаем скидку до 75% 
            'count_tours' => 20, // Увеличиваем до 3 туров
            'min_rating' => 3.0, // Снижаем требования к рейтингу
            'count_place_min' => 2, // Минимум 2 места
            'count_place_max' => 6, // Максимум 6 мест
            'label' => 'супер раннее бронирование'
        ]
    ];
}

// Глобальная переменная для включения/отключения спец предложений
$SPEC_OFFERS_ENABLED = true; // Изменить на false для отключения спец предложений

// Функция для проверки включены ли спец предложения
function areSpecOffersEnabled()
{
    global $SPEC_OFFERS_ENABLED;
    return $SPEC_OFFERS_ENABLED;
}

// Функция для получения кастомных туров из базы данных
function getCustomTours($departureCityId, $db)
{
    // Проверяем включены ли спец предложения
    if (!areSpecOffersEnabled()) {
        return [];
    }

    $customTours = [];

    $query = "SELECT * FROM custom_spec_tours 
              WHERE departure_city_id = $departureCityId 
              AND is_active = 1 
              ORDER BY priority DESC, date_created DESC";

    $result = $db->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Декодируем JSON данные тура
            $tourData = json_decode($row['tour_data'], true);

            if ($tourData) {
                // Получаем tour_id из JSON данных
                $tourId = $tourData['tourid'] ?? null;

                if ($tourId) {
                    // Проверяем, есть ли уже запись в spec_tours для этого кастомного тура
                    $specTourQuery = $db->query("SELECT * FROM spec_tours WHERE tour_id = '" . $db->real_escape_string($tourId) . "' LIMIT 1");

                    if ($specTourQuery->num_rows == 0) {
                        // Если записи нет, создаем ее
                        $departureCityName = '';
                        $cityQuery = $db->query("SELECT name FROM departure_citys WHERE id_visor = $departureCityId LIMIT 1");
                        if ($cityQuery && $cityQuery->num_rows > 0) {
                            $cityRow = $cityQuery->fetch_assoc();
                            $departureCityName = $cityRow['name'];
                        }

                        // Сохраняем в базу spec_tours
                        saveTourToDatabase($db, $tourId, $tourData, $row['count_place'], $row['price'], $row['sales_place'], $row['discount'], $departureCityId, $departureCityName);
                    }
                }

                // Добавляем информацию из базы данных
                $tourData['price'] = $row['price'];
                $tourData['countPlace'] = $row['count_place'];
                $tourData['salesPlace'] = $row['sales_place'];
                $tourData['placeForSales'] = $row['count_place'] - $row['sales_place'];
                $tourData['discount'] = $row['discount'];

                // Добавляем метки для кастомных туров
                $tourData['is_custom_tour'] = true;
                $tourData['custom_tour_id'] = $row['id'];
                $tourData['tour_type'] = $row['tour_type'];
                $tourData['priority'] = $row['priority'];

                // Устанавливаем флаги в зависимости от типа тура
                switch ($row['tour_type']) {
                    case 'super_early_booking':
                        $tourData['is_super_early_booking'] = true;
                        $tourData['is_early_booking'] = false;
                        $tourData['super_early_booking_badge'] = true;
                        $tourData['exclusive_offer'] = true;
                        $tourData['limited_places'] = true;
                        break;
                    case 'early_booking':
                        $tourData['is_early_booking'] = true;
                        $tourData['is_super_early_booking'] = false;
                        $tourData['early_booking_badge'] = true;
                        break;
                    default:
                        $tourData['is_early_booking'] = false;
                        $tourData['is_super_early_booking'] = false;
                        break;
                }

                // Рассчитываем старую цену и процент скидки если не указаны
                if (!isset($tourData['priceold']) && $row['discount'] > 0) {
                    $tourData['priceold'] = ceil($tourData['price'] / (1 - $row['discount'] / 100));
                }

                if (isset($tourData['priceold'])) {
                    $tourData['percentDifference'] = ceil(($tourData['priceold'] - $tourData['price']) / $tourData['priceold'] * 100);
                }

                // Добавляем стандартные флаги
                $tourData['no_surcharge'] = !isset($tourData['has_surcharge']) || $tourData['has_surcharge'] != 1;
                $tourData['instant_booking'] = !isset($tourData['hotelstatus']) || $tourData['hotelstatus'] == 2;
                $tourData['has_photo'] = isset($tourData['hotelpicture']) && !empty($tourData['hotelpicture']);
                $tourData['is_charter'] = !isset($tourData['regular']) || $tourData['regular'] != 1;

                $customTours[] = $tourData;
            }
        }
    }

    return $customTours;
}

// Функция для получения разрешенных стран по типу тура
function getAllowedCountriesByType($departureCityId, $tourType, $db)
{
    // Проверяем включены ли спец предложения
    if (!areSpecOffersEnabled()) {
        return [];
    }

    $typeColumn = '';
    switch ($tourType) {
        case 'regular':
            $typeColumn = 'allow_regular = 1';
            break;
        case 'early_booking':
            $typeColumn = 'allow_early_booking = 1';
            break;
        case 'super_early_booking':
            $typeColumn = 'allow_super_early_booking = 1';
            break;
        default:
            return [];
    }

    $countriesQuery = $db->query("
        SELECT DISTINCT countries_to 
        FROM deportures_for_spec 
        WHERE deporture_visor_id = $departureCityId 
        AND $typeColumn
    ");

    $allowedCountries = [];
    if ($countriesQuery) {
        while ($row = $countriesQuery->fetch_assoc()) {
            $allowedCountries[] = $row['countries_to'];
        }
    }

    return $allowedCountries;
}

// Функция для сохранения тура в базу данных с дополнительной информацией
function saveTourToDatabase($db, $tourId, $tour, $countPlace, $price, $salesPlace, $discount, $departureCityId, $departureCityName)
{
    // Преобразуем дату вылета в формат MySQL
    $flyDate = null;
    if (isset($tour['flydate'])) {
        $flyDateObj = DateTime::createFromFormat('d.m.Y', $tour['flydate']);
        if ($flyDateObj) {
            $flyDate = $flyDateObj->format('Y-m-d');
        }
    }

    // Экранируем строки для безопасности
    $tourIdEscaped = $db->real_escape_string($tourId);
    $departureCityNameEscaped = $db->real_escape_string($departureCityName);
    $countryNameEscaped = $db->real_escape_string($tour['countryname'] ?? '');
    $mealTypeEscaped = $db->real_escape_string($tour['meal'] ?? '');
    $mealTypeFullEscaped = $db->real_escape_string($tour['mealrussian'] ?? '');

    $sql = "INSERT INTO spec_tours (
        tour_id, 
        count_place, 
        price, 
        sales_place, 
        discount,
        departure_city_id,
        departure_city_name,
        country_id,
        country_name,
        meal_type,
        meal_type_full,
        hotel_stars,
        real_price,
        fly_date
    ) VALUES (
        '$tourIdEscaped', 
        '$countPlace', 
        '$price', 
        '$salesPlace', 
        '$discount',
        '$departureCityId',
        '$departureCityNameEscaped',
        '" . ($tour['countrycode'] ?? 0) . "',
        '$countryNameEscaped',
        '$mealTypeEscaped',
        '$mealTypeFullEscaped',
        '" . ($tour['hotelstars'] ?? 0) . "',
        '" . ($tour['price'] ?? 0) . "',
        " . ($flyDate ? "'$flyDate'" : "NULL") . "
    )";

    return $db->query($sql);
}

// Функция для получения туров супер раннего бронирования
function getSuperEarlyBookingTours($departureCityId, $tourParams, $db, $tourvisor_login, $tourvisor_password)
{
    // Проверяем включены ли спец предложения
    if (!areSpecOffersEnabled()) {
        return [];
    }

    // Получаем разрешенные страны для супер раннего бронирования
    $allowedCountries = getAllowedCountriesByType($departureCityId, 'super_early_booking', $db);

    if (empty($allowedCountries)) {
        return [];
    }

    $superEarlyParams = $tourParams['super_early_booking'];

    // Получаем название города вылета
    $cityQuery = $db->query("SELECT name FROM departure_citys WHERE id_visor = $departureCityId LIMIT 1");
    $departureCityName = '';
    if ($cityQuery && $cityQuery->num_rows > 0) {
        $cityRow = $cityQuery->fetch_assoc();
        $departureCityName = $cityRow['name'];
    }

    // Параметры запроса для супер раннего бронирования - РАЗРЕШАЕМ РЕГУЛЯРНЫЕ РЕЙСЫ
    $query = [
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "items" => 500,
        "city" => $departureCityId,
        "currency" => 3,
        "picturetype" => 1,
        "sort" => 0,
        "tourtype" => 0, // Любые туры, не только пляжные
        "datefrom" => date('d.m.Y', strtotime("+{$superEarlyParams['mindays']} days")),
        "dateto" => date('d.m.Y', strtotime("+{$superEarlyParams['maxdays']} days")),
        "hideregular" => 0, // ВКЛЮЧАЕМ регулярные рейсы
        "countries" => implode(',', $allowedCountries),
        "rating" => 2, // Снижаем требования к рейтингу
        "stars" => 3, // Снижаем требования к звездности
    ];

    $url = 'http://tourvisor.ru/xml/hottours.php?' . http_build_query($query);
    $data = file_get_contents($url);
    $datae = json_decode($data, true);

    $superEarlyTours = [];

    if (isset($datae['hottours']['tour']) && is_array($datae['hottours']['tour'])) {
        $processedTours = [];

        foreach ($datae['hottours']['tour'] as $tour) {
            // Более мягкие проверки для супер раннего бронирования
            if (!isset($tour['hotelpicture']) || empty($tour['hotelpicture'])) {
                continue;
            }

            // Снижаем требования к рейтингу
            if (isset($tour['hotelrating']) && floatval($tour['hotelrating']) > 0 && floatval($tour['hotelrating']) < $superEarlyParams['min_rating']) {
                continue;
            }

            // Снижаем требования к звездности - принимаем от 3* и выше
            if (!isset($tour['hotelstars']) || intval($tour['hotelstars']) < 3) {
                continue;
            }

            // Проверка даты вылета
            $flyDate = DateTime::createFromFormat('d.m.Y', $tour['flydate']);
            $minDate = new DateTime("+{$superEarlyParams['mindays']} days");
            if ($flyDate < $minDate) {
                continue;
            }

            // Проверяем уникальность по отелю и стране
            $tourKey = $tour['hotelcode'] . '_' . $tour['countrycode'];
            if (isset($processedTours[$tourKey])) {
                continue;
            }
            $processedTours[$tourKey] = true;

            // Используем оригинальный ID тура
            $superEarlyTourId = $tour['tourid'];

            // Проверяем, есть ли уже такой тур в базе
            $searchSpecDB = $db->query("SELECT * FROM spec_tours WHERE tour_id = '{$tour['tourid']}' LIMIT 1");

            if ($searchSpecDB->num_rows > 0) {
                $searchSpec = $searchSpecDB->fetch_assoc();
                $tour['price'] = $searchSpec['price'];
                $tour['countPlace'] = $searchSpec['count_place'];
                $tour['salesPlace'] = $searchSpec['sales_place'];
                $tour['placeForSales'] = $searchSpec['count_place'] - $searchSpec['sales_place'];
                $tour['discount'] = $searchSpec['discount'];
            } else {
                // Скидка для супер раннего бронирования
                $discount = $superEarlyParams['discount'];
                $countPlace = rand($superEarlyParams['count_place_min'], $superEarlyParams['count_place_max']);
                $salesPlace = 0;
                $price = ceil($tour['price'] * (1 - $discount / 100));

                // Сохраняем в базу с дополнительной информацией
                saveTourToDatabase($db, $superEarlyTourId, $tour, $countPlace, $price, $salesPlace, $discount, $departureCityId, $departureCityName);

                $tour['price'] = $price;
                $tour['countPlace'] = $countPlace;
                $tour['salesPlace'] = $salesPlace;
                $tour['placeForSales'] = $countPlace - $salesPlace;
                $tour['discount'] = $discount;
            }

            // Формирование данных тура супер раннего бронирования
            $tour['priceold'] = isset($tour['priceold']) ? ceil($tour['priceold'] * (1 + rand(50, 80) / 100)) : ceil($tour['price'] * (1 + rand(80, 120) / 100));
            $tour['percentDifference'] = ceil(($tour['priceold'] - $tour['price']) / $tour['priceold'] * 100);
            $tour['date_label'] = $superEarlyParams['label'];
            $tour['is_super_early_booking'] = true;
            $tour['is_early_booking'] = false;
            $tour['no_surcharge'] = !isset($tour['has_surcharge']) || $tour['has_surcharge'] != 1;
            $tour['instant_booking'] = !isset($tour['hotelstatus']) || $tour['hotelstatus'] == 2;
            $tour['has_photo'] = true;
            $tour['min_rating'] = $superEarlyParams['min_rating'];
            $tour['is_charter'] = !isset($tour['regular']) || $tour['regular'] != 1;
            $tour['super_early_booking_badge'] = true;
            $tour['super_early_booking_discount'] = $discount;
            $tour['exclusive_offer'] = true;
            $tour['limited_places'] = true;

            // Снижаем порог цены для супер раннего бронирования
            if ($tour['price'] > 120000) {
                $superEarlyTours[] = $tour;
            }

            // Ограничиваем количество туров супер раннего бронирования
            if (count($superEarlyTours) >= $superEarlyParams['count_tours']) {
                break;
            }
        }
    }

    return $superEarlyTours;
}

// Функция для получения туров раннего бронирования
function getEarlyBookingTours($departureCityId, $tourParams, $db, $tourvisor_login, $tourvisor_password)
{
    // Проверяем включены ли спец предложения
    if (!areSpecOffersEnabled()) {
        return [];
    }

    // Получаем разрешенные страны для раннего бронирования
    $allowedCountries = getAllowedCountriesByType($departureCityId, 'early_booking', $db);

    if (empty($allowedCountries)) {
        return [];
    }

    $earlyParams = $tourParams['early_booking'];

    // Получаем название города вылета
    $cityQuery = $db->query("SELECT name FROM departure_citys WHERE id_visor = $departureCityId LIMIT 1");
    $departureCityName = '';
    if ($cityQuery && $cityQuery->num_rows > 0) {
        $cityRow = $cityQuery->fetch_assoc();
        $departureCityName = $cityRow['name'];
    }

    // Параметры запроса для раннего бронирования - РАЗРЕШАЕМ РЕГУЛЯРНЫЕ РЕЙСЫ
    $query = [
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "items" => 300,
        "city" => $departureCityId,
        "currency" => 3,
        "picturetype" => 1,
        "sort" => 0,
        "tourtype" => 0, // Любые туры
        "datefrom" => date('d.m.Y', strtotime("+{$earlyParams['mindays']} days")),
        "dateto" => date('d.m.Y', strtotime("+{$earlyParams['maxdays']} days")),
        "hideregular" => 0, // ВКЛЮЧАЕМ регулярные рейсы
        "countries" => implode(',', $allowedCountries),
        "rating" => 2, // Снижаем требования
        "stars" => 3 // Снижаем требования
    ];

    $url = 'http://tourvisor.ru/xml/hottours.php?' . http_build_query($query);
    $data = file_get_contents($url);
    $datae = json_decode($data, true);

    $earlyTours = [];

    if (isset($datae['hottours']['tour']) && is_array($datae['hottours']['tour'])) {
        $processedTours = [];

        foreach ($datae['hottours']['tour'] as $tour) {
            // Более мягкие проверки для раннего бронирования
            if (!isset($tour['hotelpicture']) || empty($tour['hotelpicture'])) {
                continue;
            }

            // Снижаем требования к рейтингу
            if (isset($tour['hotelrating']) && floatval($tour['hotelrating']) > 0 && floatval($tour['hotelrating']) < 3.0) {
                continue;
            }

            // Снижаем требования к звездности
            if (!isset($tour['hotelstars']) || intval($tour['hotelstars']) < 3) {
                continue;
            }

            // Проверка даты вылета
            $flyDate = DateTime::createFromFormat('d.m.Y', $tour['flydate']);
            $minDate = new DateTime("+{$earlyParams['mindays']} days");
            if ($flyDate < $minDate) {
                continue;
            }

            // Проверяем уникальность по отелю и стране
            $tourKey = $tour['hotelcode'] . '_' . $tour['countrycode'];
            if (isset($processedTours[$tourKey])) {
                continue;
            }
            $processedTours[$tourKey] = true;

            // Используем оригинальный ID тура
            $earlyTourId = $tour['tourid'];

            // Проверяем, есть ли уже такой тур в базе
            $searchSpecDB = $db->query("SELECT * FROM spec_tours WHERE tour_id = '{$tour['tourid']}' LIMIT 1");

            if ($searchSpecDB->num_rows > 0) {
                $searchSpec = $searchSpecDB->fetch_assoc();
                $tour['price'] = $searchSpec['price'];
                $tour['countPlace'] = $searchSpec['count_place'];
                $tour['salesPlace'] = $searchSpec['sales_place'];
                $tour['placeForSales'] = $searchSpec['count_place'] - $searchSpec['sales_place'];
                $tour['discount'] = $searchSpec['discount'];
            } else {
                // Скидки для раннего бронирования
                $discount = rand($earlyParams['discount_min'], $earlyParams['discount_max']);
                $countPlace = rand(2, 8);
                $salesPlace = 0;
                $price = ceil($tour['price'] * (1 - $discount / 100));

                // Сохраняем в базу с дополнительной информацией
                saveTourToDatabase($db, $earlyTourId, $tour, $countPlace, $price, $salesPlace, $discount, $departureCityId, $departureCityName);

                $tour['price'] = $price;
                $tour['countPlace'] = $countPlace;
                $tour['salesPlace'] = $salesPlace;
                $tour['placeForSales'] = $countPlace - $salesPlace;
                $tour['discount'] = $discount;
            }

            // Формирование данных тура раннего бронирования
            $tour['priceold'] = isset($tour['priceold']) ? ceil($tour['priceold'] * (1 + rand(30, 50) / 100)) : ceil($tour['price'] * (1 + rand(50, 80) / 100));
            $tour['percentDifference'] = ceil(($tour['priceold'] - $tour['price']) / $tour['priceold'] * 100);
            $tour['date_label'] = $earlyParams['label'];
            $tour['is_early_booking'] = true;
            $tour['is_super_early_booking'] = false;
            $tour['no_surcharge'] = !isset($tour['has_surcharge']) || $tour['has_surcharge'] != 1;
            $tour['instant_booking'] = !isset($tour['hotelstatus']) || $tour['hotelstatus'] == 2;
            $tour['has_photo'] = true;
            $tour['min_rating'] = 3.0;
            $tour['is_charter'] = !isset($tour['regular']) || $tour['regular'] != 1;
            $tour['early_booking_badge'] = true;
            $tour['early_booking_discount'] = $discount;

            // Снижаем порог цены
            if ($tour['price'] > 80000) {
                $earlyTours[] = $tour;
            }

            if (count($earlyTours) >= $earlyParams['count_tours'][1]) {
                break;
            }
        }
    }

    // Берем случайное количество туров в заданном диапазоне
    $targetCount = rand($earlyParams['count_tours'][0], min($earlyParams['count_tours'][1], count($earlyTours)));
    if (count($earlyTours) > $targetCount) {
        shuffle($earlyTours);
        $earlyTours = array_slice($earlyTours, 0, $targetCount);
    }

    return $earlyTours;
}

// Функция для получения обычных туров
function getRegularTours($departureCityId, $tourParams, $db, $tourvisor_login, $tourvisor_password)
{
    // Проверяем включены ли спец предложения
    if (!areSpecOffersEnabled()) {
        return [];
    }

    // Получаем разрешенные страны для обычных туров
    $allowedCountries = getAllowedCountriesByType($departureCityId, 'regular', $db);

    if (empty($allowedCountries)) {
        return [];
    }

    // Получаем название города вылета
    $cityQuery = $db->query("SELECT name FROM departure_citys WHERE id_visor = $departureCityId LIMIT 1");
    $departureCityName = '';
    if ($cityQuery && $cityQuery->num_rows > 0) {
        $cityRow = $cityQuery->fetch_assoc();
        $departureCityName = $cityRow['name'];
    }

    // Параметры запроса для обычных спец предложений
    $query = [
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "items" => 800,
        "city" => $departureCityId,
        "currency" => 3,
        "picturetype" => 1,
        "sort" => $_POST['sorted'] ?? 0,
        "tourtype" => 1,
        "datefrom" => date('d.m.Y', strtotime("+{$tourParams['mindays']} days")),
        "dateto" => date('d.m.Y', strtotime("+{$tourParams['maxdays']} days")),
        "hideregular" => 1, // Для обычных туров исключаем регулярные
        "hotelstatus" => 2,
        "countries" => implode(',', $allowedCountries),
        "hotactive" => 1,
        "rating" => 2
    ];

    $url = 'http://tourvisor.ru/xml/hottours.php?' . http_build_query($query);
    $data = file_get_contents($url);
    $datae = json_decode($data, true);

    $regularTours = [];

    if (isset($datae['hottours']['tour']) && is_array($datae['hottours']['tour'])) {
        foreach ($datae['hottours']['tour'] as $tour) {
            // Проверка обязательных условий для обычных туров
            if (!isset($tour['hotelpicture']) || empty($tour['hotelpicture'])) {
                continue;
            }

            if (!isset($tour['hotelrating']) || floatval($tour['hotelrating']) < $tourParams['min_rating']) {
                continue;
            }

            if (isset($tour['regular']) && $tour['regular'] == 1) {
                continue;
            }

            if (isset($tour['hotelstatus']) && $tour['hotelstatus'] == 1) {
                continue;
            }

            $flyDate = DateTime::createFromFormat('d.m.Y', $tour['flydate']);
            $minDate = new DateTime("+{$tourParams['mindays']} days");
            if ($flyDate < $minDate) {
                continue;
            }

            if (isset($tour['has_surcharge']) && $tour['has_surcharge'] == 1) {
                continue;
            }

            // Обработка цены и мест для обычных туров
            $searchSpecDB = $db->query("SELECT * FROM spec_tours WHERE tour_id='" . $db->real_escape_string($tour['tourid']) . "' LIMIT 1");

            if ($searchSpecDB->num_rows > 0) {
                $searchSpec = $searchSpecDB->fetch_assoc();
                $tour['price'] = $searchSpec['price'];
                $tour['countPlace'] = $searchSpec['count_place'];
                $tour['salesPlace'] = $searchSpec['sales_place'];
                $tour['placeForSales'] = $searchSpec['count_place'] - $searchSpec['sales_place'];
            } else {
                $discount = rand($tourParams['discount_min'], $tourParams['discount_max']);
                $countPlace = rand($tourParams['count_place_min'] / 2, $tourParams['count_place_max'] / 2) * 2;
                $salesPlace = $countPlace > 4 ? rand(0, floor(($countPlace - 2) / 2)) * 2 : 0;
                $price = ceil($tour['price'] * (1 - $discount / 100));

                // Сохраняем в базу с дополнительной информацией
                saveTourToDatabase($db, $tour['tourid'], $tour, $countPlace, $price, $salesPlace, $discount, $departureCityId, $departureCityName);

                $tour['price'] = $price;
                $tour['countPlace'] = $countPlace;
                $tour['salesPlace'] = $salesPlace;
                $tour['placeForSales'] = $countPlace - $salesPlace;
            }

            // Формирование данных обычного тура
            $tour['priceold'] = isset($tour['priceold']) ? ceil($tour['priceold'] * (1 + rand(20, 30) / 100)) : ceil($tour['price'] * (1 + rand(30, 50) / 100));
            $tour['percentDifference'] = ceil(($tour['priceold'] - $tour['price']) / $tour['priceold'] * 100);
            $tour['date_label'] = $tourParams['date_label'];
            $tour['is_early_booking'] = false;
            $tour['is_super_early_booking'] = false;
            $tour['no_surcharge'] = true;
            $tour['instant_booking'] = true;
            $tour['has_photo'] = true;
            $tour['min_rating'] = $tourParams['min_rating'];
            $tour['is_charter'] = true;
            $tour['hotelstatus'] = 2;

            if ($tour['price'] > 80000) {
                $regularTours[] = $tour;
            }
        }
    }

    return $regularTours;
}

// Основной код
$tourParams = generateTourParams();
$departureCityId = $_POST['city_oute'];

// Проверяем включены ли спец предложения глобально
if (!areSpecOffersEnabled()) {
    echo json_encode([
        'type' => true,
        'data' => [
            'tours' => [],
            'count' => 0,
            'custom_count' => 0,
            'regular_count' => 0,
            'early_booking_count' => 0,
            'super_early_booking_count' => 0,
            'date_label' => 'спец предложения отключены',
            'available_tour_types' => [],
            'filters' => [
                'spec_offers_enabled' => false
            ],
            'tour_type_settings' => [
                'custom_allowed' => false,
                'regular_allowed' => false,
                'early_booking_allowed' => false,
                'super_early_booking_allowed' => false
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем, есть ли вообще настройки для данного города
$settingsQuery = $db->query("
    SELECT COUNT(*) as count 
    FROM deportures_for_spec 
    WHERE deporture_visor_id = $departureCityId
");

$settingsRow = $settingsQuery->fetch_assoc();
if ($settingsRow['count'] == 0) {
    echo json_encode([
        'type' => false,
        'msg' => 'Для выбранного города вылета не настроены спецпредложения'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем кастомные туры из базы данных
$customTours = getCustomTours($departureCityId, $db);

// Получаем туры каждого типа в зависимости от настроек
$superEarlyBookingTours = getSuperEarlyBookingTours($departureCityId, $tourParams, $db, $tourvisor_login, $tourvisor_password);
$earlyBookingTours = getEarlyBookingTours($departureCityId, $tourParams, $db, $tourvisor_login, $tourvisor_password);
$regularTours = getRegularTours($departureCityId, $tourParams, $db, $tourvisor_login, $tourvisor_password);

// Объединяем все типы туров, кастомные туры добавляем в начало
$allTours = array_merge($customTours, $regularTours, $earlyBookingTours, $superEarlyBookingTours);

if (!empty($allTours)) {
    // Сортировка с учетом кастомных туров
    if (($_POST['sorted'] ?? 0) == 1) {
        // При сортировке по цене сначала идут кастомные туры с высоким приоритетом
        usort($allTours, function ($a, $b) {
            // Сначала сортируем по приоритету (кастомные туры)
            $priorityA = isset($a['priority']) ? $a['priority'] : 0;
            $priorityB = isset($b['priority']) ? $b['priority'] : 0;

            if ($priorityA != $priorityB) {
                return $priorityB <=> $priorityA; // По убыванию приоритета
            }

            // Затем по цене
            return $a['price'] <=> $b['price'];
        });
    } else {
        // Разделяем туры по типам для правильной сортировки
        $customToursFiltered = array_filter(
            $allTours,
            fn($tour) => isset($tour['is_custom_tour']) && $tour['is_custom_tour']
        );

        $regularToursFiltered = array_filter(
            $allTours,
            fn($tour) =>
            (!isset($tour['is_custom_tour']) || !$tour['is_custom_tour']) &&
            (!isset($tour['is_early_booking']) || !$tour['is_early_booking']) &&
            (!isset($tour['is_super_early_booking']) || !$tour['is_super_early_booking'])
        );

        $earlyToursFiltered = array_filter(
            $allTours,
            fn($tour) =>
            (!isset($tour['is_custom_tour']) || !$tour['is_custom_tour']) &&
            isset($tour['is_early_booking']) && $tour['is_early_booking'] &&
            (!isset($tour['is_super_early_booking']) || !$tour['is_super_early_booking'])
        );

        $superEarlyToursFiltered = array_filter(
            $allTours,
            fn($tour) =>
            (!isset($tour['is_custom_tour']) || !$tour['is_custom_tour']) &&
            isset($tour['is_super_early_booking']) && $tour['is_super_early_booking']
        );

        // Сортируем кастомные туры по приоритету
        usort($customToursFiltered, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));

        shuffle($regularToursFiltered);
        shuffle($earlyToursFiltered);
        // Супер ранние туры не перемешиваем, показываем в том порядке, как получили

        // Приоритет: сначала кастомные, потом супер ранние, потом ранние, потом обычные
        $allTours = array_merge($customToursFiltered, $superEarlyToursFiltered, $earlyToursFiltered, $regularToursFiltered);
    }

    // Проверяем, какие типы туров доступны для данного города
    $availableTypes = [];
    if (!empty($customTours))
        $availableTypes[] = 'custom';
    if (!empty($regularTours))
        $availableTypes[] = 'regular';
    if (!empty($earlyBookingTours))
        $availableTypes[] = 'early_booking';
    if (!empty($superEarlyBookingTours))
        $availableTypes[] = 'super_early_booking';

    $response = [
        'type' => true,
        'data' => [
            'tours' => $allTours,
            'count' => count($allTours),
            'custom_count' => count($customTours),
            'regular_count' => count($regularTours),
            'early_booking_count' => count($earlyBookingTours),
            'super_early_booking_count' => count($superEarlyBookingTours),
            'date_label' => $tourParams['date_label'],
            'available_tour_types' => $availableTypes,
            'filters' => [
                'min_rating' => $tourParams['min_rating'],
                'with_photos' => true,
                'instant_booking' => true,
                'no_surcharge' => true,
                'only_charter' => true,
                'hotel_not_on_request' => true,
                'custom_included' => count($customTours) > 0,
                'early_booking_included' => count($earlyBookingTours) > 0,
                'super_early_booking_included' => count($superEarlyBookingTours) > 0,
                'regular_included' => count($regularTours) > 0,
                'spec_offers_enabled' => areSpecOffersEnabled()
            ],
            'tour_type_settings' => [
                'custom_allowed' => !empty($customTours),
                'regular_allowed' => !empty($regularTours),
                'early_booking_allowed' => !empty($earlyBookingTours),
                'super_early_booking_allowed' => !empty($superEarlyBookingTours),
                'spec_offers_globally_enabled' => areSpecOffersEnabled()
            ]
        ]
    ];
} else {
    // Проверяем, какие типы туров разрешены, но не найдены
    $allowedRegular = !empty(getAllowedCountriesByType($departureCityId, 'regular', $db));
    $allowedEarly = !empty(getAllowedCountriesByType($departureCityId, 'early_booking', $db));
    $allowedSuperEarly = !empty(getAllowedCountriesByType($departureCityId, 'super_early_booking', $db));

    // Проверяем наличие кастомных туров
    $hasCustomTours = !empty($customTours);

    $allowedTypes = [];
    if ($hasCustomTours)
        $allowedTypes[] = 'custom';
    if ($allowedRegular)
        $allowedTypes[] = 'regular';
    if ($allowedEarly)
        $allowedTypes[] = 'early_booking';
    if ($allowedSuperEarly)
        $allowedTypes[] = 'super_early_booking';

    if (empty($allowedTypes)) {
        $response = [
            'type' => false,
            'msg' => 'Для выбранного города вылета не настроены спецпредложения ни одного типа',
            'error_code' => 'NO_TOUR_TYPES_CONFIGURED',
            'city_id' => $departureCityId,
            'spec_offers_enabled' => areSpecOffersEnabled()
        ];
    } else {
        $response = [
            'type' => false,
            'msg' => 'Нет доступных туров, соответствующих критериям для разрешенных типов туров',
            'allowed_tour_types' => $allowedTypes,
            'error_code' => 'NO_TOURS_FOUND',
            'required_filters' => [
                'min_rating' => $tourParams['min_rating'],
                'with_photos' => true,
                'instant_booking' => 'flexible',
                'no_surcharge' => 'flexible',
                'only_charter' => 'flexible',
                'hotel_not_on_request' => 'flexible',
                'spec_offers_enabled' => areSpecOffersEnabled()
            ],
            'tour_type_settings' => [
                'custom_allowed' => $hasCustomTours,
                'regular_allowed' => $allowedRegular,
                'early_booking_allowed' => $allowedEarly,
                'super_early_booking_allowed' => $allowedSuperEarly,
                'spec_offers_globally_enabled' => areSpecOffersEnabled()
            ]
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>