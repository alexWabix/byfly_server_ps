<?php
include('methods/hotels/functions/search_hotel.php');

if (empty($_POST['country_id']) == false) {
    if (empty($_POST['page_hotel']) == true) {
        $_POST['page_hotel'] = 1;
    }

    $pages = ($_POST['page_hotel'] * 10) - 10;

    $addReq = array();

    if (empty($_POST['namedHotel']) == false) {
        array_push($addReq, 'name LIKE "%' . $_POST['namedHotel'] . '%"');
    }

    if (empty($_POST['stars']) == false and $_POST['stars'] != '0' and $_POST['stars'] != 0 and $_POST['stars'] != null) {
        array_push($addReq, 'countStars = "' . $_POST['stars'] . '"');
    }

    if (empty($_POST['reiting']) == false and $_POST['reiting'] != '0' and $_POST['reiting'] != 0 and $_POST['reiting'] != null) {
        array_push($addReq, 'reiting > "' . $_POST['reiting'] . '"');
    }

    if (empty($_POST['rew']) == false and $_POST['rew'] != '0' and $_POST['rew'] != 0 and $_POST['rew'] != null) {
        array_push($addReq, 'summ_rewiew > "' . $_POST['rew'] . '"');
    }

    $orderBy = 'reiting';

    if ($_POST['sorted'] == 0 or $_POST['sorted'] == '0') {
        $orderBy = 'reiting';
    } else if ($_POST['sorted'] == 1 or $_POST['sorted'] == '1') {
        $orderBy = 'summ_rewiew';
    }

    $query = '';
    if (count($addReq) > 0) {
        $query = ' AND ' . implode(' AND ', $addReq);
    }

    $query1 = "SELECT * FROM hotels WHERE country_id='" . $_POST['country_id'] . "'" . $query . "  ORDER BY " . $orderBy . " DESC LIMIT " . $pages . ",10";
    $query2 = "SELECT COUNT(id) as ct FROM hotels WHERE country_id='" . $_POST['country_id'] . "'" . $query;
    try {
        $listHotelDB = $db2->query($query1);
        $countHotels = $db2->query($query2)->fetch_assoc()['ct'];
        $hotels = array();
        while ($listHotel = $listHotelDB->fetch_assoc()) {
            $hotelInfo = getHotelInfo($listHotel['id']);
            if ($hotelInfo['type']) {
                array_push($hotels, $hotelInfo['hotel']);
            }
        }

        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "pages" => $_POST['page_hotel'],
                    "hotels" => $hotels,
                    "count_hotels" => $countHotels,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
    } catch (\Throwable $th) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $query1,
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error',
        ),
        JSON_UNESCAPED_UNICODE
    );
}


?>