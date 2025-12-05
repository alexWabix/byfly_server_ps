<?php
$page = 0;
$limit = 20;
if (!empty($_POST['page'])) {
    $page = $_POST['page'];
    $page = ($page - 1) * $limit;
}

$country = array();

$listCountriesDB = $db->query("SELECT * FROM countries ORDER BY CASE WHEN min_price = 0 THEN 1 ELSE 0 END, min_price ASC LIMIT " . $page . ", " . $limit);
while ($listCountries = $listCountriesDB->fetch_assoc()) {
    $listCountries['images'] = array();
    $listImageDB = $db->query("SELECT * FROM countries_image WHERE country_id='" . $listCountries['id'] . "'");
    while ($listImage = $listImageDB->fetch_assoc()) {
        array_push($listCountries['images'], $listImage['image']);
    }

    $listCountries['regions'] = array();
    $listRegDB = $db->query("SELECT * FROM regions WHERE countryid='" . $listCountries['id'] . "'");
    while ($listReg = $listRegDB->fetch_assoc()) {
        $listReg['images'] = array();
        $listReg['flag'] = $listCountries['icon'];
        $listReg['countryId'] = $listCountries['visor_id'];
        $listReg['countryTitle'] = $listCountries['title'];

        $listImgDB = $db->query("SELECT * FROM regions_image WHERE regions_id='" . $listReg['id'] . "'");
        while ($listImg = $listImgDB->fetch_assoc()) {
            array_push($listReg['images'], $listImg['image']);
        }

        array_push($listCountries['regions'], $listReg);
    }
    array_push($country, $listCountries);
}

echo json_encode(
    array(
        "type" => true,
        "data" => $country,
    ),
    JSON_UNESCAPED_UNICODE
);

?>