<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/js_bot_wa/api/get_info.php');


function searchHotel($hotelName)
{
    global $db;
    $stmt = $db->prepare("SELECT id, name_hotel, id_visor,
                                MATCH(name_hotel) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance 
                          FROM mekka_list_hotels
                          WHERE MATCH(name_hotel) AGAINST (? IN NATURAL LANGUAGE MODE)
                          ORDER BY relevance DESC LIMIT 1");

    $hotelName = $db->real_escape_string($hotelName);

    $stmt->bind_param('ss', $hotelName, $hotelName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id_visor'];
        $name_hotel = $row['name_hotel'];
        $relevance = $row['relevance'];

        $maxRelevance = 1.0;
        $percentageMatch = round(($relevance / $maxRelevance) * 100);

        return [
            'id' => $id,
            'name_hotel' => $name_hotel,
            'percentage_match' => $percentageMatch
        ];
    } else {
        return [
            'id' => null,
            'name_hotel' => null,
            'percentage_match' => 0
        ];
    }
}


$res = searchHotel('Courtyard package 4* (Джидда)');

print_r($res);

?>