<?php
$resp = array();
$countryCities = array();

// Получаем список стран и их города
$getCountriesDB = $db->query("SELECT countryid FROM departure_citys WHERE countryid != '0' GROUP BY countryid");

while ($getCountries = $getCountriesDB->fetch_assoc()) {
    $countryId = $getCountries['countryid'];
    $countriesInfo = $db->query("SELECT * FROM countries WHERE id='$countryId'")->fetch_assoc();

    $add = array();
    $listCitysDB = $db->query("SELECT * FROM departure_citys WHERE countryid='$countryId'");

    while ($listCitys = $listCitysDB->fetch_assoc()) {
        $ct = array(
            "id" => $listCitys['id'],
            "name" => $listCitys['name'],
            "conect_city" => array(),
        );

        // Получаем список соседних городов
        $connectCitysDB = $db->query("SELECT city_to, distance, id FROM departure_citys_connect WHERE city_from='" . $listCitys['id'] . "'");
        while ($connectCitys = $connectCitysDB->fetch_assoc()) {
            $cityToInfo = $db->query("SELECT name FROM departure_citys WHERE id='" . $connectCitys['city_to'] . "'")->fetch_assoc();
            array_push($ct['conect_city'], array(
                "id" => $connectCitys['city_to'],
                "name" => $cityToInfo['name'],
                "distance" => $connectCitys['distance'] ?? 0,
            ));
        }

        array_push($add, $ct);
    }

    $countryCities[$countriesInfo['title']] = $add;
}

// Функция сортировки по количеству городов и соседних городов
uksort($countryCities, function ($a, $b) use ($countryCities) {
    if ($a === "Казахстан")
        return -1; // Казахстан всегда первый
    if ($b === "Казахстан")
        return 1;

    $countA = count($countryCities[$a]);
    $countB = count($countryCities[$b]);

    $connectA = array_sum(array_map(fn($city) => count($city['conect_city']), $countryCities[$a]));
    $connectB = array_sum(array_map(fn($city) => count($city['conect_city']), $countryCities[$b]));

    // Сортируем по количеству городов, затем по количеству соседних городов
    return ($countB <=> $countA) ?: ($connectB <=> $connectA);
});

$allCitys = array();
$getCountriesDB = $db->query("SELECT id,name,countryid FROM departure_citys WHERE countryid != '0'");
while ($getCountries = $getCountriesDB->fetch_assoc()) {
    array_push($allCitys, $getCountries);
}

echo json_encode(
    array(
        "type" => true,
        "data" => array(
            "list" => $countryCities,
            "all" => $allCitys,
        )
    ),
    JSON_UNESCAPED_UNICODE
);
?>