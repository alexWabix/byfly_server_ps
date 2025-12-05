<?php
$listCitysToArr = [];
$cityInfo = $db->query("SELECT * FROM departure_citys WHERE id_visor='" . $_POST['oute_city_id'] . "'")->fetch_assoc();

if (!empty($cityInfo)) {
    $listCitysToArr[] = $cityInfo['id_visor']; // Добавляем основной город

    // Если включено "соседние города", добавляем их в список
    if (!empty($_POST['connect_city']) && $_POST['connect_city'] == 1) {
        $getConnectCitysDB = $db->query("SELECT * FROM departure_citys_connect WHERE city_from='" . $cityInfo['id'] . "'");
        while ($getConnectCitys = $getConnectCitysDB->fetch_assoc()) {
            $ToCityInfo = $db->query("SELECT * FROM departure_citys WHERE id='" . $getConnectCitys['city_to'] . "'")->fetch_assoc();
            if (!empty($ToCityInfo)) {
                $listCitysToArr[] = $ToCityInfo['id_visor'];
            }
        }
    }
}

// Убираем дубликаты городов
$listCitysToArr = array_unique($listCitysToArr);

// Теперь делаем многопоточные запросы для всех выбранных городов
$tourvisorUrls = [];
foreach ($listCitysToArr as $cityIdVisor) {
    $tourvisorUrls[$cityIdVisor] = "http://tourvisor.ru/xml/list.php?type=country&cndep=$cityIdVisor&authlogin=$tourvisor_login&authpass=$tourvisor_password";
}

// Многопоточная загрузка данных
$multiHandle = curl_multi_init();
$curlHandles = [];
$responses = [];

foreach ($tourvisorUrls as $cityId => $url) {
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

foreach ($curlHandles as $cityId => $ch) {
    $responses[$cityId] = curl_multi_getcontent($ch);
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

// Обработка и сортировка данных
$uniqueResults = [];
foreach ($responses as $response) {
    if (!empty($response) && $response != 'Authorization Error') {
        $listCountryTo = json_decode($response, true);
        foreach ($listCountryTo['lists']['countries']['country'] as $ct) {
            $countryId = $ct['id'];

            if (!isset($uniqueResults[$countryId])) {
                $countryInfo = $db->query("SELECT * FROM countries WHERE visor_id='" . $countryId . "'");

                if ($countryInfo->num_rows > 0) {
                    $ct['country_info'] = $countryInfo->fetch_assoc();
                    $ct['citys'] = [];

                    $listCitysDB = $db->query("SELECT * FROM regions WHERE countryid = '" . $ct['country_info']['id'] . "'");
                    while ($listCitys = $listCitysDB->fetch_assoc()) {
                        $listCitys['flag'] = $ct['country_info']['icon'];
                        $listCitys['countryId'] = $ct['country_info']['visor_id'];
                        $listCitys['countryTitle'] = $ct['country_info']['title'];
                        $ct['citys'][] = $listCitys;
                    }

                    $uniqueResults[$countryId] = $ct;
                }
            }
        }
    }
}

function customSort($a, $b)
{
    $priorityCountries = ["Турция", "Египет", "Мальдивы", "Тайланд", "Вьетнам"];

    // Сортируем города внутри страны по min_price
    if (!empty($a['citys'])) {
        usort($a['citys'], function ($x, $y) {
            if (($x['min_price'] == 0) !== ($y['min_price'] == 0)) {
                return $x['min_price'] == 0 ? 1 : -1;
            }
            return $x['min_price'] <=> $y['min_price'];
        });
    }

    if (!empty($b['citys'])) {
        usort($b['citys'], function ($x, $y) {
            if (($x['min_price'] == 0) !== ($y['min_price'] == 0)) {
                return $x['min_price'] == 0 ? 1 : -1;
            }
            return $x['min_price'] <=> $y['min_price'];
        });
    }

    // Приоритетные страны идут первыми
    $aPriority = in_array($a['name'], $priorityCountries);
    $bPriority = in_array($b['name'], $priorityCountries);

    if ($aPriority && !$bPriority) {
        return -1;
    }
    if (!$aPriority && $bPriority) {
        return 1;
    }

    // min_price != 0 должны быть выше
    if (($a['country_info']['min_price'] == 0) !== ($b['country_info']['min_price'] == 0)) {
        return $a['country_info']['min_price'] == 0 ? 1 : -1;
    }

    // Сортировка по min_price от меньшего к большему
    return $a['country_info']['min_price'] <=> $b['country_info']['min_price'];
}

$resp = array_values($uniqueResults);
usort($resp, 'customSort');

echo json_encode([
    "type" => true,
    "data" => $resp,
], JSON_UNESCAPED_UNICODE);
