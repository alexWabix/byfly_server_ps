<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$query = array(
    "authlogin" => $tourvisor_login,
    "authpass" => $tourvisor_password,
    "format" => "json",
    "items" => 100,
    "city" => $_POST['city_oute'],
    "regions" => $_POST['regions'],
    "countries" => $_POST['countries'],
    "currency" => 3,
    "picturetype" => 1,
    "meal" => $_POST['meal'],
    "stars" => $_POST['stars'],
    "sort" => $_POST['sorted'],
    "datefrom" => $_POST['datefrom'],
    "dateto" => $_POST['dateto'],
    "maxdays" => $_POST['maxdays'],
);



$url = 'http://tourvisor.ru/xml/hottours.php?' . http_build_query($query);
$data = file_get_contents($url);
$datae = json_decode($data, true);
$searchedCountries = array();
$searchedRegions = array();
if ($datae['data']['status']['state'] != 'no search results') {
    $count = 0;
    foreach ($datae['hottours']['tour'] as $tour) {

        $dateString = $tour['flydate'];
        $date = DateTime::createFromFormat('d.m.Y', $dateString);
        $formattedDate = $date->format('Y-m-d H:i:s');
        $tour['flydate'] = $formattedDate;

        if (empty($searchedCountries[$tour['countrycode']])) {
            $countryInfoDB = $db->query("SELECT id, title_en, title_kk FROM countries WHERE visor_id='" . $tour['countrycode'] . "'");
            if ($countryInfoDB->num_rows > 0) {
                $countryInfo = $countryInfoDB->fetch_assoc();
                $datae['hottours']['tour'][$count]['title_en'] = $countryInfo['title_en'];
                $datae['hottours']['tour'][$count]['title_kk'] = $countryInfo['title_kk'];

                $searchedCountries[$tour['countrycode']] = $countryInfo;
            }
        } else {
            $datae['hottours']['tour'][$count]['title_en'] = $searchedCountries[$tour['countrycode']]['title_en'];
            $datae['hottours']['tour'][$count]['title_kk'] = $searchedCountries[$tour['countrycode']]['title_kk'];
        }


        if (empty($searchedRegions[$tour['hotelregioncode']])) {
            $region_info_db = $db->query("SELECT title_en, title_kk FROM regions WHERE visor_id='" . $tour['hotelregioncode'] . "'");
            if ($region_info_db->num_rows > 0) {
                $region_info = $region_info_db->fetch_assoc();
                $datae['hottours']['tour'][$count]['title_city_en'] = $region_info['title_en'];
                $datae['hottours']['tour'][$count]['title_city_kk'] = $region_info['title_kk'];

                $searchedRegions[$tour['hotelregioncode']] = $region_info;
            }
        } else {
            $datae['hottours']['tour'][$count]['title_city_en'] = $searchedRegions[$tour['hotelregioncode']]['title_en'];
            $datae['hottours']['tour'][$count]['title_city_kk'] = $searchedRegions[$tour['hotelregioncode']]['title_kk'];
        }


        $sql = "INSERT INTO hot_tours_searched (`id`, `tourid`, `countrycode`, `countryname`, `departurecode`, `departurename`, `departurenamefrom`, `operatorcode`, `operatorname`, `hotelcode`, `hotelname`, `hotelstars`, `hotelregioncode`, `hotelregionname`, `hotelrating`, `fulldesclink`, `hotelpicture`, `flydate`, `nights`, `meal`, `price`, `priceold`, `fuelcharge`, `currency`, `date_create`) 
        VALUES (NULL, '" . $tour['tourid'] . "', '" . $tour['countrycode'] . "', '" . $tour['countryname'] . "', '" . $tour['departurecode'] . "', '" . $tour['departurename'] . "', '" . $tour['departurenamefrom'] . "', '" . $tour['operatorcode'] . "', '" . $tour['operatorname'] . "', '" . $tour['hotelcode'] . "', '" . $tour['hotelname'] . "', 
        '" . $tour['hotelstars'] . "', '" . $tour['hotelregioncode'] . "', '" . $tour['hotelregionname'] . "', '" . $tour['hotelrating'] . "', '" . $tour['fulldesclink'] . "', '" . $tour['hotelpicture'] . "', 
        '" . $tour['flydate'] . "', '" . $tour['nights'] . "', '" . $tour['meal'] . "', '" . $tour['price'] . "', '" . $tour['priceold'] . "', '" . $tour['fuelcharge'] . "', '" . $tour['currency'] . "', CURRENT_TIMESTAMP);";

        $db->query($sql);
        $count++;
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => array(
                "tours" => $datae['hottours']['tour'],
                "count" => $datae['hottours']['hotcount'],
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