<?php
$stringQuery = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/' . $_POST['coordinates'] . '/' . $_POST['dateStart'] . '/' . $_POST['dateOff'] . '?unitGroup=metric&key=75Q8TA5SN24SM2RTVUBXUL4RY&contentType=json';
$getWeather = file_get_contents($stringQuery);



if (empty($getWeather) == false) {
    $getWeather = json_decode($getWeather, true);
    if (empty($getWeather) == false) {
        $db->query("INSERT INTO test (`id`, `post_text`, `get_text`) VALUES (NULL, '" . json_encode($getWeather, JSON_UNESCAPED_UNICODE) . "', '');");
        echo json_encode(
            array(
                "type" => true,
                "data" => $getWeather,
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error get weather data...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }

} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error get weather data...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}


?>