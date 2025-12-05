<?php
$_POST['data'] = json_decode($_POST['data'], true);

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

foreach ($listCitysToArr as $cityId) {
    $query = [
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
        "meal" => $_POST['data']['dict'],
        "mealbetter" => $_POST['data']['dict_beetter'],
        "regions" => implode(',', $_POST['data']['to']['citys']),
        "currency" => 3,
        "hideregular" => $_POST['data']['regular'] ?? 0,
    ];

    // Добавляем возраст детей в запрос
    $count = 0;
    foreach ($_POST['data']['children'] as $age) {
        $count++;
        $query['childage' . $count] = $age;
    }

    $url = 'https://tourvisor.ru/xml/search.php?' . http_build_query($query);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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

    if (!empty($resp['result']['requestid'])) {
        $db->query("INSERT INTO citys_search_ids (`id`, `search_id`, `city_id`) VALUES (NULL, '" . $resp['result']['requestid'] . "', '" . $cityId . "');");
        $requestIds[] = $resp['result']['requestid'];
    }

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

$resp = array(
    "requestid" => implode(',', $requestIds),
);
if ($_POST['addInbase'] == 'true') {
    $requestIdsStr = implode(',', $requestIds);
    $db->query("INSERT INTO tours_searched (`id`, `visor_id`, `date_create`, `visor_deport_country_id`, `visor_deport_region_id`, `visor_to_country_id`, `visor_to_region_id`, `count_tours`, `time_searched`, `min_price`, `max_price`, `count_adult`, `count_children`, `count_hotels`) 
                VALUES (NULL, '" . $requestIdsStr[0] . "', CURRENT_TIMESTAMP, '" . $_POST['data']['oute']['countryId'] . "', '" . $_POST['data']['oute']['id'] . "', '" . $_POST['data']['to']['country_id'] . "', '" . implode(',', $_POST['data']['to']['citys']) . "', '0', '0', '0', '0', '" . $_POST['data']['adult'] . "', '" . count($_POST['data']['children']) . "', '0')");
    $resp['base_id'] = $db->insert_id;
}

// Возвращаем список requestid через запятую
echo json_encode([
    "type" => true,
    "data" => $resp
]);
?>