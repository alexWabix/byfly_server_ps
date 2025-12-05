<?php
$_POST['data'] = json_decode($_POST['data'], true);

// ДОБАВЬТЕ ОТЛАДКУ В НАЧАЛО:
error_log("=== ОТЛАДКА ПОИСКА ===");
error_log("Входящие данные: " . print_r($_POST['data'], true));
error_log("Страна: " . $_POST['data']['to']['country_id']);
error_log("Регионы: " . print_r($_POST['data']['to']['citys'], true));

// Определяем города вылета
$listCitysToArr = [];
$cityInfo = $db->query("SELECT * FROM departure_citys WHERE id_visor='" . $_POST['data']['oute']['id'] . "'")->fetch_assoc();

if (!empty($cityInfo)) {
    $listCitysToArr[] = $cityInfo['id_visor']; // Основной город

    // Если включены соседние города, добавляем их
    if (!empty($_POST['data']['connect_city']) && $_POST['data']['connect_city'] == 1) {
        $getConnectCitysDB = $db->query("SELECT * FROM departure_citys_connect WHERE city_from='" . $cityInfo['id'] . "'");
        while ($getConnectCitys = $getConnectCitysDB->fetch_assoc()) {
            $ToCityInfo = $db->query("SELECT * FROM departure_citys WHERE id='" . $getConnectCitys['city_to'] . "'")->fetch_assoc();
            if (!empty($ToCityInfo)) {
                $listCitysToArr[] = $ToCityInfo['id_visor'];
            }
        }
    }
}

// Убираем дубликаты
$listCitysToArr = array_unique($listCitysToArr);

// Формируем многопоточные запросы
$multiHandle = curl_multi_init();
$curlHandles = [];
$responses = [];

$reitings = array(
    "0.0" => 0,
    "3.0" => 2,
    "3.5" => 3,
    "4.0" => 4,
    "4.5" => 5,
);
$urls = [];

foreach ($listCitysToArr as $cityId) {
    // Базовые параметры для всех запросов
    $baseQuery = array(
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "departure" => $cityId,
        "country" => $_POST['data']['to']['country_id'],
        "datefrom" => $_POST['data']['startDate'],
        "dateto" => $_POST['data']['endDate'],
        "nightsfrom" => $_POST['data']['nigtsFrom'],
        "nightsto" => $_POST['data']['nigthTo'],
        "adults" => $_POST['data']['adult'],
        "child" => count($_POST['data']['children']),
        "currency" => 3,
    );

    // Добавляем рейтинг если указан
    if (!empty($_POST['data']['reiting']) && $_POST['data']['reiting'] != "0.0") {
        $baseQuery["rating"] = $reitings[$_POST['data']['reiting']];
    }

    // Добавляем типы отелей если указаны
    if (!empty($_POST['data']['type_tours']) && $_POST['data']['type_tours'] !== '') {
        $baseQuery["hoteltypes"] = $_POST['data']['type_tours'];
    }

    // Добавляем питание если указано
    if (!empty($_POST['data']['dict']) && $_POST['data']['dict'] != "0") {
        $baseQuery["meal"] = $_POST['data']['dict'];
        if (!empty($_POST['data']['dict_beetter'])) {
            $baseQuery["mealbetter"] = $_POST['data']['dict_beetter'];
        }
    }

    // Добавляем регионы ТОЛЬКО если они указаны
    if (!empty($_POST['data']['to']['citys']) && is_array($_POST['data']['to']['citys'])) {
        $regions = array_filter($_POST['data']['to']['citys']); // Убираем пустые значения
        if (!empty($regions)) {
            $baseQuery["regions"] = implode(',', $regions);
        }
    }

    // Добавляем скрытие регулярных рейсов
    if (!empty($_POST['data']['regular'])) {
        $baseQuery["hideregular"] = $_POST['data']['regular'];
    }

    // Если указаны конкретные отели
    if (!empty($_POST['data']['hotels'])) {
        $baseQuery["hotels"] = $_POST['data']['hotels'];
    } else {
        // Иначе добавляем звездность
        if (!empty($_POST['data']['hotel']) && $_POST['data']['hotel'] != "0") {
            $baseQuery["stars"] = $_POST['data']['hotel'];
            if (!empty($_POST['data']['hotel_beetter'])) {
                $baseQuery["starsbetter"] = $_POST['data']['hotel_beetter'];
            }
        }
    }

    // Добавляем возраст детей в запрос
    $count = 0;
    if (!empty($_POST['data']['children']) && is_array($_POST['data']['children'])) {
        foreach ($_POST['data']['children'] as $age) {
            $count++;
            $baseQuery['childage' . $count] = $age;
        }
    }

    $url = 'https://tourvisor.ru/xml/search.php?' . http_build_query($baseQuery);
    $urls[] = $url;

    error_log("URL для города $cityId: " . $url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Увеличил таймаут
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[$cityId] = $ch;
}

$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

// Собираем результаты
$requestIds = [];
foreach ($curlHandles as $cityId => $ch) {
    $data = curl_multi_getcontent($ch);
    $resp = json_decode($data, true);

    error_log("Ответ для города $cityId: " . $data);

    if (!empty($resp['result']['requestid'])) {
        $db->query("INSERT INTO citys_search_ids (`id`, `search_id`, `city_id`) VALUES (NULL, '" . $resp['result']['requestid'] . "', '" . $cityId . "');");
        $requestIds[] = $resp['result']['requestid'];
        error_log("Успешно получен requestid: " . $resp['result']['requestid']);
    } else {
        error_log("ОШИБКА: Не получен requestid для города $cityId");
        if (isset($resp['error'])) {
            error_log("Ошибка TourVisor: " . print_r($resp['error'], true));
        }
    }

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

$resp = array(
    "requestid" => implode(',', $requestIds),
    "urls" => $urls,
);

if ($_POST['addInbase'] == 'true' && !empty($requestIds)) {
    $requestIdsStr = implode(',', $requestIds);
    $db->query("INSERT INTO tours_searched (`id`, `visor_id`, `date_create`, `visor_deport_country_id`, `visor_deport_region_id`, `visor_to_country_id`, `visor_to_region_id`, `count_tours`, `time_searched`, `min_price`, `max_price`, `count_adult`, `count_children`, `count_hotels`) 
                VALUES (NULL, '" . $requestIds[0] . "', CURRENT_TIMESTAMP, '" . $_POST['data']['oute']['countryId'] . "', '" . $_POST['data']['oute']['id'] . "', '" . $_POST['data']['to']['country_id'] . "', '" . implode(',', $_POST['data']['to']['citys']) . "', '0', '0', '0', '0', '" . $_POST['data']['adult'] . "', '" . count($_POST['data']['children']) . "', '0')");
    $resp['base_id'] = $db->insert_id;
}

// Возвращаем список requestid через запятую
echo json_encode([
    "type" => true,
    "data" => $resp
]);
?>