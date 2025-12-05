<?php
try {
    $countryVisorId = $_POST['country_visor_id'] ?? null;
    $regionVisorId = $_POST['region_visor_id'] ?? null;

    if (empty($countryVisorId)) {
        throw new Exception("ID страны не указан");
    }

    // Формируем URL для запроса к TourVisor
    $url = "http://tourvisor.ru/xml/list.php?format=json&type=hotel&authlogin={$tourvisor_login}&authpass={$tourvisor_password}&hotcountry={$countryVisorId}";

    // Добавляем регион если указан
    if (!empty($regionVisorId) && $regionVisorId != '0') {
        $url .= "&hotregion={$regionVisorId}";
    }

    // Выполняем запрос к TourVisor
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ByFly Travel API Client');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Ошибка cURL: " . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception("HTTP ошибка: " . $httpCode . ". Ответ: " . substr($response, 0, 500));
    }

    // Декодируем JSON ответ
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Ошибка декодирования JSON: " . json_last_error_msg() . ". Ответ: " . substr($response, 0, 500));
    }

    // Извлекаем отели из правильной структуры: lists.hotels.hotel
    $hotelsData = [];

    if (isset($data['lists']['hotels']['hotel']) && is_array($data['lists']['hotels']['hotel'])) {
        $hotelsData = $data['lists']['hotels']['hotel'];
    } elseif (isset($data['hotels']['hotel']) && is_array($data['hotels']['hotel'])) {
        $hotelsData = $data['hotels']['hotel'];
    } elseif (isset($data['hotel']) && is_array($data['hotel'])) {
        $hotelsData = $data['hotel'];
    }

    // Если отелей нет, возвращаем пустой массив
    if (empty($hotelsData)) {
        $resp = array(
            "type" => true,
            "data" => [],
            "total" => 0,
            "blacklisted_count" => 0,
            "message" => "Отели не найдены для указанных параметров",
            "debug_info" => [
                "response_structure" => array_keys($data),
                "url" => $url,
                "has_lists" => isset($data['lists']),
                "has_hotels" => isset($data['lists']['hotels']) ? array_keys($data['lists']['hotels']) : 'no lists',
            ]
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Получаем список отелей уже находящихся в черном списке
    $blacklistedHotels = [];
    $blacklistQuery = "SELECT hotel_code FROM hotels_blacklist WHERE is_active = 1";
    $blacklistResult = $db->query($blacklistQuery);

    if ($blacklistResult && $blacklistResult->num_rows > 0) {
        while ($row = $blacklistResult->fetch_assoc()) {
            $blacklistedHotels[] = (int) $row['hotel_code'];
        }
    }

    // Обрабатываем данные отелей
    $hotels = [];
    foreach ($hotelsData as $hotel) {
        $hotelId = (int) ($hotel['id'] ?? 0);

        // Пропускаем отели с некорректным ID
        if ($hotelId <= 0) {
            continue;
        }

        // Пропускаем отели уже находящиеся в черном списке
        if (in_array($hotelId, $blacklistedHotels)) {
            continue;
        }

        // Получаем название отеля
        $hotelName = trim($hotel['name'] ?? '');
        if (empty($hotelName)) {
            $hotelName = "Отель ID: " . $hotelId;
        }

        // Получаем звездность
        $stars = null;
        if (isset($hotel['stars']) && is_numeric($hotel['stars']) && $hotel['stars'] > 0) {
            $stars = (int) $hotel['stars'];
        }

        // Получаем рейтинг
        $rating = null;
        if (isset($hotel['rating']) && is_numeric($hotel['rating']) && $hotel['rating'] > 0) {
            $rating = (float) $hotel['rating'];
        }

        // Получаем расстояние до моря
        $seadistance = null;
        if (isset($hotel['seadistance']) && is_numeric($hotel['seadistance'])) {
            $seadistance = (int) $hotel['seadistance'];
        }

        // Определяем регион по ID
        $regionName = '';
        if (isset($hotel['region']) && $hotel['region'] > 0) {
            // Можно добавить запрос к базе для получения названия региона по ID
            $regionQuery = "SELECT title FROM regions WHERE visor_id = " . (int) $hotel['region'] . " LIMIT 1";
            $regionResult = $db->query($regionQuery);
            if ($regionResult && $regionResult->num_rows > 0) {
                $regionRow = $regionResult->fetch_assoc();
                $regionName = $regionRow['title'];
            }
        }

        // Определяем страну
        $countryName = '';
        $countryQuery = "SELECT title FROM countries WHERE visor_id = " . (int) $countryVisorId . " LIMIT 1";
        $countryResult = $db->query($countryQuery);
        if ($countryResult && $countryResult->num_rows > 0) {
            $countryRow = $countryResult->fetch_assoc();
            $countryName = $countryRow['title'];
        }

        $hotels[] = [
            'id' => $hotelId,
            'name' => $hotelName,
            'stars' => $stars,
            'rating' => $rating,
            'region' => $regionName,
            'region_id' => (int) ($hotel['region'] ?? 0),
            'subregion_id' => (int) ($hotel['subregion'] ?? 0),
            'seadistance' => $seadistance,
            'description' => trim($hotel['description'] ?? ''),
            'picture' => $hotel['picture'] ?? null,
            'country' => $countryName,
            'is_relax' => isset($hotel['relax']) && $hotel['relax'] == 1,
            'is_city' => isset($hotel['city']) && $hotel['city'] == 1,
            'is_family' => isset($hotel['family']) && $hotel['family'] == 1,
            'is_beach' => isset($hotel['beach']) && $hotel['beach'] == 1,
            'is_active' => isset($hotel['active']) && $hotel['active'] == 1,
            'is_health' => isset($hotel['health']) && $hotel['health'] == 1,
            'is_deluxe' => isset($hotel['deluxe']) && $hotel['deluxe'] == 1,
        ];
    }

    // Сортируем отели по названию
    usort($hotels, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    $resp = array(
        "type" => true,
        "data" => $hotels,
        "total" => count($hotels),
        "blacklisted_count" => count($blacklistedHotels),
        "country_visor_id" => $countryVisorId,
        "region_visor_id" => $regionVisorId,
        "total_from_tourvisor" => count($hotelsData)
    );

} catch (Exception $e) {
    // Уведомляем админа об ошибке
    $errorMessage = "❌ ОШИБКА в загрузке отелей TourVisor\n\n" .
        "🌍 Страна ID: " . ($countryVisorId ?? 'не указан') . "\n" .
        "🏖️ Регион ID: " . ($regionVisorId ?? 'не указан') . "\n" .
        "⚠️ Ошибка: " . $e->getMessage() . "\n" .
        "⏰ Время: " . date('Y-m-d H:i:s') . "\n\n" .
        "🔧 Проверьте подключение к TourVisor!";

    sendWhatsapp('77780021666', $errorMessage);

    $resp = array(
        "type" => false,
        "msg" => $e->getMessage(),
        "debug_url" => $url ?? 'URL не сформирован'
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>