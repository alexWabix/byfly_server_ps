<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$filename = $dir . 'methods/list/start.json';
$appSettings = $db->query("SELECT * FROM settings WHERE id='1'")->fetch_assoc();


$appSettings['banks'] = array();
$listOfBankDB = $db->query("SELECT * FROM banks_from_by_fly");
while ($listOfBank = $listOfBankDB->fetch_assoc()) {
    array_push($appSettings['banks'], $listOfBank);
}

$appSettings['about'] = $db->query("SELECT * FROM about WHERE id='1'")->fetch_assoc();

$resp = array(
    "meals" => array(),
    "meals_desc" => array(),
    "stars" => array(),
    "operators" => array(),
    "curencies" => array(),
    "hotel_services" => array(),
    "deportList" => array(),
    "documents" => array(),
    "version_app" => "30.0.0+33",
    "linkUpdateIos" => "",
    "linkUpdateAndroid" => "",
    "settings" => $appSettings,
);
$deportCtDB = $db->query("SELECT * FROM departure_citys GROUP BY countryid");
while ($dp = $deportCtDB->fetch_assoc()) {
    $dp['country_info'] = $db->query("SELECT * FROM countries WHERE id='" . $dp['countryid'] . "'")->fetch_assoc();
    if ($dp['country_info'] !== null) {
        $dp['country_info']['citys'] = array();
        $dp['country_info']['info'] = $dp;

        $listCitysDB = $db->query("SELECT * FROM departure_citys WHERE countryid='" . $dp['countryid'] . "'");
        while ($listCitys = $listCitysDB->fetch_assoc()) {
            $listCitys['flag'] = $dp['country_info']['icon'];
            $listCitys['countryId'] = $dp['country_info']['visor_id'];
            $listCitys['countryTitle'] = $dp['country_info']['title'];
            $listCitys['countryTitleEn'] = $dp['country_info']['title_en'];
            $listCitys['countryTitleKk'] = $dp['country_info']['title_kk'];

            array_push($dp['country_info']['citys'], $listCitys);
        }
        array_push($resp['deportList'], $dp['country_info']);
    }
}

function customSort($a, $b)
{
    $priorityCountries = ["Казахстан", "Россия"];
    if (in_array($a['title'], $priorityCountries) && in_array($b['title'], $priorityCountries)) {
        return 0;
    }
    if (in_array($a['title'], $priorityCountries)) {
        return -1;
    }
    if (in_array($b['title'], $priorityCountries)) {
        return 1;
    }
    return 0;
}


$listMealsDB = $db->query("SELECT * FROM meals");
while ($listMeals = $listMealsDB->fetch_assoc()) {
    array_push($resp['meals'], $listMeals);
    $resp['meals_desc'][$listMeals['name']] = $listMeals;
}

$listStarsDB = $db->query("SELECT * FROM stars");
while ($listStars = $listStarsDB->fetch_assoc()) {
    array_push($resp['stars'], $listStars);
}

$listOperatorsDB = $db->query("SELECT * FROM operators ORDER BY LENGTH(login) DESC;");
while ($listOperators = $listOperatorsDB->fetch_assoc()) {
    array_push($resp['operators'], $listOperators);
}

$listCurenciesDB = $db->query("SELECT * FROM curencies");
while ($listCurencies = $listCurenciesDB->fetch_assoc()) {
    array_push($resp['curencies'], $listCurencies);
}

$listHotelServicesDB = $db->query("SELECT * FROM hotel_services");
while ($listHotelServices = $listHotelServicesDB->fetch_assoc()) {
    array_push($resp['hotel_services'], $listHotelServices);
}

$listDogovorsDB = $db_docs->query("SELECT * FROM docs");
while ($listDogovors = $listDogovorsDB->fetch_assoc()) {
    $resp['documents'][$listDogovors['id']] = $listDogovors;
}

file_put_contents($filename, json_encode($resp, JSON_UNESCAPED_UNICODE));
?>