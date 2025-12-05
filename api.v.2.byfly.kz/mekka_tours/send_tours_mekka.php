<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/js_bot_wa/api/get_info.php');

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!empty($data)) {
    $_POST = $data;
}

if (empty($_POST['list'])) {
    echo json_encode(["type" => false, "msg" => "Пустой список туров!"]);
    exit;
}

$db->query("TRUNCATE TABLE mekka_tours");

// Функции для форматирования даты и времени
function formatDate($dateString)
{
    if (empty($dateString))
        return null;
    $dateParts = explode(", ", $dateString);
    $date = DateTime::createFromFormat('d.m.Y', $dateParts[0]);
    return $date ? $date->format('Y-m-d') : null;
}

function formatTime($timeString)
{
    return !empty($timeString) ? $timeString : null;
}

// Формируем SQL-запрос
$insertValues = [];
$sendedMsg = false;
foreach ($_POST['list'] as $tour) {
    $hotel_name = $db->real_escape_string($tour['hotelName'] ?? '');
    $searchHotelDB = $db->query("SELECT * FROM mekka_list_hotels WHERE name_hotel LIKE '" . $hotel_name . "'");

    $hotelId = 0;
    if ($searchHotelDB->num_rows == 0) {
        $db->query("INSERT INTO mekka_list_hotels (`id`, `name_hotel`, `description_ru`,`description_en`,`description_kk`, `date_create`) VALUES (NULL, '" . $hotel_name . "', '','','', CURRENT_TIMESTAMP);");
        $sendedMsg = true;
    } else {
        $searchHotel = $searchHotelDB->fetch_assoc();
        $hotelId = $searchHotel['id'];
    }

    $id_tour = $db->real_escape_string($tour['id'] ?? '');
    $link_hotel = $db->real_escape_string($tour['link'] ?? '');
    $date_fly = formatDate($tour['checkInDate'] ?? '');
    $time_fly = formatTime($tour['checkInTime'] ?? '');
    $meal_descriptions = [
        'BB' => 'Завтрак',
        'HB' => 'Завтрак и ужин',
        'FB' => 'Полный пансион',
        'AI' => 'Все включено',
        'UAI' => 'Ультра все включено',
        'RO' => 'Без питания'
    ];

    $meal_type = $db->real_escape_string($meal_descriptions[$tour['mealType']] ?? '');
    if (empty($meal_type)) {
        foreach ($meal_descriptions as $key => $description) {
            if (strpos($description, $tour['mealType']) !== false) {
                $meal_type = $description;
                break;
            }
        }
    }

    if (empty($meal_type)) {
        $meal_type = $tour['mealType'];
    }
    $flight_type = $db->real_escape_string($tour['flightType'] ?? '');
    $room_type = $db->real_escape_string($tour['roomType'] ?? '');
    $price = $db->real_escape_string($tour['price'] ?? '0');
    $nights = $db->real_escape_string($tour['nights'] ?? '0');

    $price = $price - ceil(($price / 100) * 11);

    $date_fly = $date_fly ? "'$date_fly'" : "NULL";
    $time_fly = $time_fly ? "'$time_fly'" : "NULL";

    // Формируем строку для каждого тура
    $insertValues[] = "('$id_tour', '$link_hotel', $date_fly, $time_fly, '$hotel_name', '$meal_type', '$flight_type', '$room_type', '$price', '" . $tour['count_adult'] . "', '" . $tour['city_oute'] . "', '" . $tour['city_oute_id'] . "', '" . $hotelId . "', '$nights')";
}

if ($sendedMsg) {
    sendWhatsapp('77772253305', 'Уважаемая Юлия! В CRM системе добавлен отель у которого нет описания! Пожалуйста наполните информацию о добавленных отелях!');
}

if (!empty($insertValues)) {
    // Объединяем все строки для одного большого запроса
    $sql = "INSERT INTO mekka_tours 
        (id_tour, link_hotel, date_fly, time_fly, hotel_name, meal_type, flight_type, room_type, price, count_adult, city_oute_name, city_oute_id, hotel_id_byfly, count_nights) 
        VALUES " . implode(',', $insertValues);

    if ($db->query($sql)) {
        echo json_encode(["type" => true, "data" => "Результаты успешно обновлены!"]);
    } else {
        echo json_encode(["type" => false, "msg" => "Ошибка при вставке: " . $db->error]);
    }
} else {
    echo json_encode(["type" => false, "msg" => "Нет данных для вставки!"]);
}
?>