<?php
$query = array(
    "authlogin" => $tourvisor_login,
    "authpass" => $tourvisor_password,
    "format" => "json",
    "requestid" => $_POST['code'],
    "type" => "result",
    "onpage" => 20,
    "page" => $_POST['page'],
);

$url = 'https://tourvisor.ru/xml/result.php?' . http_build_query($query);
$data = file_get_contents($url);
$datae = json_decode($data, true);
if ($datae['data']['status']['state'] != 'no search results') {
    $arrayCountries = array();
    $arrayRegions = array();


    $count = 0;
    foreach ($datae['data']['result']['hotel'] as $hotel) {
        if (empty($arrayCountries[$hotel['countrycode']])) {
            $searchCountriesDB = $db->query("SELECT title_en,title_kk FROM countries WHERE visor_id='" . $hotel['countrycode'] . "'");
            if ($searchCountriesDB->num_rows > 0) {
                $searchCountries = $searchCountriesDB->fetch_assoc();
                $datae['data']['result']['hotel'][$count]['country_en'] = $searchCountries['title_en'];
                $datae['data']['result']['hotel'][$count]['country_kk'] = $searchCountries['title_kk'];

                $arrayCountries[$hotel['countrycode']] = $searchCountries;
            }
        } else {
            $datae['data']['result']['hotel'][$count]['country_en'] = $arrayCountries[$hotel['countrycode']]['title_en'];
            $datae['data']['result']['hotel'][$count]['country_kk'] = $arrayCountries[$hotel['countrycode']]['title_kk'];
        }


        if (empty($arrayRegions[$hotel['regioncode']])) {
            $search_regions_db = $db->query("SELECT title_en, title_kk FROM regions WHERE visor_id='" . $hotel['regioncode'] . "'");
            if ($search_regions_db->num_rows > 0) {
                $search_regions = $search_regions_db->fetch_assoc();
                $datae['data']['result']['hotel'][$count]['region_en'] = $search_regions['title_en'];
                $datae['data']['result']['hotel'][$count]['region_kk'] = $search_regions['title_kk'];

                $arrayRegions[$hotel['regioncode']] = $search_regions;

            }
        } else {
            $datae['data']['result']['hotel'][$count]['region_en'] = $arrayRegions[$hotel['regioncode']]['title_en'];
            $datae['data']['result']['hotel'][$count]['region_kk'] = $arrayRegions[$hotel['regioncode']]['title_kk'];
        }


        $count++;
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => array(
                "data" => $datae['data']['result'],
                "state" => $datae['data']['status'],
            ),
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error get data load...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>