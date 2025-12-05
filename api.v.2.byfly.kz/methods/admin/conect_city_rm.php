<?php
if (empty($_POST['toCity']) == false and empty($_POST['from_city']) == false) {
    if ($db->query("DELETE FROM departure_citys_connect WHERE city_from='" . $_POST['from_city'] . "' AND city_to='" . $_POST['toCity'] . "'")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => "Город удален"
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