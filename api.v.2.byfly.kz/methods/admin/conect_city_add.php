<?php
if (empty($_POST['toCity']) == false and empty($_POST['from_city']) == false) {
    $cityToInfoFrom = $db->query("SELECT name FROM departure_citys WHERE id='" . $_POST['from_city'] . "'")->fetch_assoc()['name'];
    $cityToInfoTo = $db->query("SELECT name FROM departure_citys WHERE id='" . $_POST['toCity'] . "'")->fetch_assoc()['name'];

    $test = array(
        "city1" => $cityToInfoFrom,
        "city2" => $cityToInfoTo
    );


    if ($db->query("INSERT INTO departure_citys_connect (`id`, `city_from`, `city_to`, `distance`) VALUES (NULL, '" . $_POST['from_city'] . "', '" . $_POST['toCity'] . "', NULL);")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $test,
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $db->error,
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Отсутствуют данные!"
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>