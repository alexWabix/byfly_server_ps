<?php
if (empty($_POST['countryTo']) == false and empty($_POST['cityOute']) == false) {
    $datesAviaGet = file_get_contents('http://tourvisor.ru/xml/list.php?format=json&type=flydate&flydeparture=' . $_POST['cityOute'] . '&flycountry=' . $_POST['countryTo'] . '&authlogin=' . $tourvisor_login . '&authpass=' . $tourvisor_password);
    $datesAvia = json_decode($datesAviaGet, true);
    if ($datesAvia and $datesAvia != null) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $datesAvia['lists']['flydates']['flydate'],
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error parse responce...',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error empty Country To id OR cityOute id...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

?>