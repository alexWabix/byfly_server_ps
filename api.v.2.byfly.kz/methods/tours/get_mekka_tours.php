<?php
// Проверка на наличие необходимых параметров в POST-запросе
if (isset($_POST['start_date'], $_POST['end_date'], $_POST['startNignt'], $_POST['endNignt'], $_POST['cityOute'], $_POST['adult'])) {

    $arrayTours = array();

    // Проверяем, что переданные даты в формате 'YYYY-MM-DD'
    if (!strtotime($_POST['start_date']) || !strtotime($_POST['end_date'])) {
        echo json_encode(array("type" => false, "message" => "Неверный формат даты"));
        exit;
    }

    // Проверяем, что ночи и количество взрослых - это числа
    if (!is_numeric($_POST['startNignt']) || !is_numeric($_POST['endNignt']) || !is_numeric($_POST['adult']) || !is_numeric($_POST['cityOute'])) {
        echo json_encode(array("type" => false, "message" => "Некорректные параметры"));
        exit;
    }

    // Используем подготовленные выражения для защиты от SQL-инъекций
    $stmt = $db->prepare("SELECT * FROM mekka_tours WHERE date_fly > ? AND date_fly < ? AND count_nights >= ? AND count_nights <= ? AND city_oute_id = ? AND count_adult = ?");

    // Привязываем параметры
    $stmt->bind_param("ssiiii", $_POST['start_date'], $_POST['end_date'], $_POST['startNignt'], $_POST['endNignt'], $_POST['cityOute'], $_POST['adult']);

    // Выполняем запрос
    $stmt->execute();

    // Получаем результат
    $result = $stmt->get_result();

    while ($searchTours = $result->fetch_assoc()) {
        if ($searchTours['hotel_id_byfly'] != 0) {
            $searchTours['hotel'] = $db->query("SELECT * FROM mekka_list_hotels WHERE id='" . $searchTours['hotel_id_byfly'] . "'")->fetch_assoc();
            $searchTours['hotel']['images'] = array();
            $searchImagesDb = $db->query("SELECT * FROM mekka_hotel_image WHERE hotel_id='" . $searchTours['hotel']['id'] . "'");
            while ($searchImages = $searchImagesDb->fetch_assoc()) {
                $searchTours['hotel']['images'][] = $searchImages['image'];
            }

            $searchTours['hotel']['rooms'] = array();
            $searchRoomsDB = $db->query("SELECT * FROM mekka_hotel_rooms WHERE hotel_id='" . $searchTours['hotel']['id'] . "'");
            while ($searchRooms = $searchRoomsDB->fetch_assoc()) {
                $searchRooms['images'] = array();
                $hotelImagesDB = $db->query("SELECT * FROM mekka_rooms_image WHERE room_id='" . $searchRooms['id'] . "'");
                while ($hotelImages = $hotelImagesDB->fetch_assoc()) {
                    $searchRooms['images'][] = $hotelImages['image'];
                }
                $searchTours['hotel']['rooms'][] = $searchRooms;
            }

            $arrayTours[] = $searchTours;
        }

    }

    // Закрываем подготовленное выражение
    $stmt->close();

    // Возвращаем результат в формате JSON
    echo json_encode(
        array(
            "type" => true,
            "data" => array(
                "list" => $arrayTours,
            ),
        ),
        JSON_UNESCAPED_UNICODE
    );

} else {
    echo json_encode(
        array(
            "type" => false,
            "message" => "Неверные параметры запроса"
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>