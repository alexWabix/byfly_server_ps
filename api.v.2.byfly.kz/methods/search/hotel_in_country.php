<?php
$query = array(
    "authlogin" => $tourvisor_login,
    "authpass" => $tourvisor_password,
    "format" => "json",
    "type" => 'hotel',
    "hotcountry" => $_POST['country_id'],
    "hotregion" => $_POST['regions'],
);

$url = 'http://tourvisor.ru/xml/list.php?' . http_build_query($query);
$data = file_get_contents($url);

if (empty($data) == false) {
    $data = json_decode($data, true);
    if (empty($data) == false) {
        if (empty($_POST['text_search']) == false) {
            $hotels = array();

            $dontShowArray = explode(',', $_POST['dont_show_id']);

            foreach ($data['lists']['hotels']['hotel'] as $hotelsData) {
                if (stripos($hotelsData['name'], $_POST['text_search']) !== false) {
                    if (!in_array($hotelsData['id'], $dontShowArray)) {
                        array_push($hotels, $hotelsData);
                    }
                }
            }
            echo json_encode(
                array(
                    "type" => true,
                    "data" => $hotels,
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        } else {
            $hotels = array();

            $dontShowArray = explode(',', $_POST['dont_show_id']);

            foreach ($data['lists']['hotels']['hotel'] as $hotelsData) {
                if (!in_array($hotelsData['id'], $dontShowArray)) {
                    array_push($hotels, $hotelsData);
                }
            }


            echo json_encode(
                array(
                    "type" => true,
                    "data" => $hotels,
                ),
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error load list hotels. Please repeat after 10 minutes...',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error load list hotels. Please repeat after 10 minutes...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>