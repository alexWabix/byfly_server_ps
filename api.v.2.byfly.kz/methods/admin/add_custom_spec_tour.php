<?php
$departure_city_id = isset($_POST['departure_city_id']) ? intval($_POST['departure_city_id']) : 0;
$tour_data = isset($_POST['tour_data']) ? $_POST['tour_data'] : '';
$original_price = isset($_POST['price']) ? intval($_POST['price']) : 0; // Оригинальная цена за одного
$count_place = isset($_POST['count_place']) ? intval($_POST['count_place']) : 0;
$discount = isset($_POST['discount']) ? intval($_POST['discount']) : 0;
$priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
$tour_type = isset($_POST['tour_type']) ? $_POST['tour_type'] : 'custom';
$country_id = isset($_POST['country_id']) ? intval($_POST['country_id']) : null;
$hotel_name = isset($_POST['hotel_name']) ? $_POST['hotel_name'] : null;
$fly_date = isset($_POST['fly_date']) ? $_POST['fly_date'] : null;
$nights = isset($_POST['nights']) ? intval($_POST['nights']) : null;
$tourId = isset($_POST['tourid']) ? $_POST['tourid'] : null; // Убрал intval, может быть строкой

// Валидация обязательных полей
if ($departure_city_id <= 0) {
    $resp = array(
        "type" => false,
        "msg" => "ID города вылета обязателен"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($tour_data)) {
    $resp = array(
        "type" => false,
        "msg" => "Данные тура обязательны"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($original_price <= 0) {
    $resp = array(
        "type" => false,
        "msg" => "Цена должна быть больше 0"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем валидность типа тура
$allowed_tour_types = ['regular', 'early_booking', 'super_early_booking', 'custom'];
if (!in_array($tour_type, $allowed_tour_types)) {
    $tour_type = 'custom';
}

// ВАЖНО: Рассчитываем цену со скидкой за одного человека
$discounted_price_per_person = $original_price;
if ($discount > 0) {
    $discounted_price_per_person = ceil($original_price * (1 - $discount / 100));
}

// Преобразуем в цену за двоих для совместимости с системой
$price_for_two = $discounted_price_per_person * 2;
$original_price_for_two = $original_price * 2;

// Декодируем данные тура если это JSON строка
if (is_string($tour_data)) {
    $original_tour_data = json_decode($tour_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $resp = array(
            "type" => false,
            "msg" => "Ошибка декодирования JSON данных тура: " . json_last_error_msg()
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    $original_tour_data = $tour_data;
}

// Получаем информацию о городе вылета
$departure_city_query = $db->query("SELECT name, name_en, name_kk FROM departure_citys WHERE id_visor = $departure_city_id LIMIT 1");
$departure_city_name = 'Неизвестный город';
$departure_city_name_en = 'Unknown city';
$departure_city_name_kk = 'Белгісіз қала';
if ($departure_city_query && $departure_city_query->num_rows > 0) {
    $city_row = $departure_city_query->fetch_assoc();
    $departure_city_name = $city_row['name'];
    $departure_city_name_en = $city_row['name_en'] ?? $departure_city_name;
    $departure_city_name_kk = $city_row['name_kk'] ?? $departure_city_name;
}

// Получаем информацию о стране
$country_name = 'Неизвестная страна';
$country_name_en = 'Unknown country';
$country_name_kk = 'Белгісіз ел';
if ($country_id) {
    $country_query = $db->query("SELECT title, title_en, title_kk FROM countries WHERE visor_id = $country_id LIMIT 1");
    if ($country_query && $country_query->num_rows > 0) {
        $country_row = $country_query->fetch_assoc();
        $country_name = $country_row['title'];
        $country_name_en = $country_row['title_en'] ?? $country_name;
        $country_name_kk = $country_row['title_kk'] ?? $country_name;
    }
}

// Генерируем уникальный ID тура если не передан
if (empty($tourId)) {
    $tourId = 'custom_' . time() . '_' . rand(1000, 9999);
}

// Компонуем данные тура в формате как у горящих туров
$compiled_tour_data = array(
    // Основные данные тура
    "tourid" => $tourId,
    "countrycode" => $country_id ?? $original_tour_data['countrycode'] ?? 0,
    "countryname" => $country_name,
    "country_en" => $country_name_en,
    "country_kk" => $country_name_kk,
    "departurecode" => $departure_city_id,
    "departurename" => $departure_city_name,
    "departure_en" => $departure_city_name_en,
    "departure_kk" => $departure_city_name_kk,
    "departurenamefrom" => "из " . $departure_city_name,

    // Данные оператора
    "operatorcode" => $original_tour_data['operatorcode'] ?? 999,
    "operatorname" => $original_tour_data['operatorname'] ?? "ByFly Travel",

    // Данные отеля
    "hotelcode" => $original_tour_data['hotelcode'] ?? rand(10000, 99999),
    "hotelname" => $hotel_name ?? $original_tour_data['hotelname'] ?? "Отель",
    "hotelstars" => $original_tour_data['hotelstars'] ?? 4,
    "hotelregioncode" => $original_tour_data['hotelregioncode'] ?? 0,
    "hotelregionname" => $original_tour_data['hotelregionname'] ?? $country_name,
    "hotelrating" => $original_tour_data['hotelrating'] ?? "4.0",
    "hoteldescription" => $original_tour_data['hoteldescription'] ?? "",

    // Ссылки на изображения и описания
    "fulldesclink" => $original_tour_data['fulldesclink'] ?? "",
    "hotelpicture" => $original_tour_data['hotelpicture'] ?? $original_tour_data['hotelpicturemedium'] ?? $original_tour_data['hotelpicturesmall'] ?? "",
    "hotelpicturesmall" => $original_tour_data['hotelpicturesmall'] ?? $original_tour_data['hotelpicture'] ?? "",
    "hotelpicturemedium" => $original_tour_data['hotelpicturemedium'] ?? $original_tour_data['hotelpicture'] ?? "",
    "hotelpicturebig" => $original_tour_data['hotelpicturebig'] ?? $original_tour_data['hotelpicture'] ?? "",

    // Данные тура
    "flydate" => $fly_date ? date('d.m.Y', strtotime($fly_date)) : $original_tour_data['flydate'] ?? date('d.m.Y', strtotime('+60 days')),
    "nights" => $nights ?? $original_tour_data['nights'] ?? 7,
    "meal" => $original_tour_data['meal'] ?? "BB",
    "mealcode" => $original_tour_data['mealcode'] ?? 3,
    "mealrussian" => $original_tour_data['mealrussian'] ?? "Завтрак",
    "room" => $original_tour_data['room'] ?? "standard room",
    "tourname" => $original_tour_data['tourname'] ?? $hotel_name ?? "Тур",
    "placement" => $original_tour_data['placement'] ?? "2 взрослых",
    "adults" => $original_tour_data['adults'] ?? 2,
    "child" => $original_tour_data['child'] ?? 0,

    // Цены - ВАЖНО: цена уже со скидкой
    "price" => $price_for_two, // Цена за двоих СО СКИДКОЙ
    "priceold" => $discount > 0 ? $original_price_for_two : null, // Старая цена за двоих БЕЗ скидки
    "price_per_person" => $discounted_price_per_person, // Цена за одного СО СКИДКОЙ
    "original_price_per_person" => $original_price, // Оригинальная цена за одного БЕЗ скидки
    "fuelcharge" => $original_tour_data['fuelcharge'] ?? 0,
    "currency" => $original_tour_data['currency'] ?? "KZT",
    "operatorprice" => $original_tour_data['operatorprice'] ?? round($price_for_two / 500, 2),
    "operatorcurrency" => $original_tour_data['operatorcurrency'] ?? "USD",
    "visacharge" => $original_tour_data['visacharge'] ?? 0,

    // Статусы и флаги
    "regular" => $original_tour_data['regular'] ?? 0,
    "promo" => 1, // Кастомный тур всегда промо
    "flightstatus" => $original_tour_data['flightstatus'] ?? 2,
    "hotelstatus" => $original_tour_data['hotelstatus'] ?? 2,
    "nightflight" => $original_tour_data['nightflight'] ?? 0,
    "has_surcharge" => $original_tour_data['has_surcharge'] ?? 0,
    "detailavailable" => 1,

    // Дополнительные поля для кастомных туров
    "is_custom_tour" => true,
    "custom_tour_type" => $tour_type,
    "custom_priority" => $priority,
    "custom_discount" => $discount,
    "custom_count_places" => $count_place,
    "custom_original_price" => $original_price, // Оригинальная цена за одного
    "custom_discounted_price" => $discounted_price_per_person, // Цена со скидкой за одного

    // Информация о перелете (если есть)
    "fly_info" => $original_tour_data['fly_info'] ?? array(
        "flights" => array(),
        "tourinfo" => array(
            "flags" => array(
                "notransfer" => false,
                "nomedinsurance" => false,
                "noflight" => false,
                "nomeal" => false
            )
        ),
        "iserror" => false
    )
);

// Преобразуем в JSON
$tour_data_json = json_encode($compiled_tour_data, JSON_UNESCAPED_UNICODE);

// Преобразуем дату вылета в правильный формат для базы данных
if (!empty($fly_date)) {
    try {
        if (strpos($fly_date, '.') !== false) {
            // Если дата в формате d.m.Y
            $date = DateTime::createFromFormat('d.m.Y', $fly_date);
        } else {
            // Если дата уже в формате Y-m-d
            $date = DateTime::createFromFormat('Y-m-d', $fly_date);
        }

        if ($date) {
            $fly_date = $date->format('Y-m-d');
        } else {
            $fly_date = null;
        }
    } catch (Exception $e) {
        $fly_date = null;
    }
}

try {
    // Экранируем строковые значения для безопасности
    $tour_data_json_escaped = $db->real_escape_string($tour_data_json);
    $hotel_name_escaped = $hotel_name ? $db->real_escape_string($hotel_name) : null;
    $tourId_escaped = $db->real_escape_string($tourId);

    // Подготавливаем SQL запрос
    $sql = "INSERT INTO custom_spec_tours (
        departure_city_id,
        tour_data,
        price,
        count_place,
        sales_place,
        discount,
        is_active,
        date_created,
        date_updated,
        priority,
        tour_type,
        country_id,
        hotel_name,
        fly_date,
        nights,
        touridreal
    ) VALUES (
        $departure_city_id,
        '$tour_data_json_escaped',
        $price_for_two,
        $count_place,
        0,
        $discount,
        1,
        NOW(),
        NOW(),
        $priority,
        '$tour_type',
        " . ($country_id ? $country_id : 'NULL') . ",
        " . ($hotel_name_escaped ? "'$hotel_name_escaped'" : 'NULL') . ",
        " . ($fly_date ? "'$fly_date'" : 'NULL') . ",
        " . ($nights ? $nights : 'NULL') . ",
        '$tourId_escaped'
    )";

    $result = $db->query($sql);

    if ($result) {
        $insert_id = $db->insert_id;

        $resp = array(
            "type" => true,
            "msg" => "Тур успешно добавлен в спец предложения",
            "data" => array(
                "id" => $insert_id,
                "tour_id" => $tourId,
                "departure_city_id" => $departure_city_id,
                "original_price_per_person" => $original_price, // Оригинальная цена за одного
                "discounted_price_per_person" => $discounted_price_per_person, // Цена со скидкой за одного
                "price_for_two" => $price_for_two, // Цена за двоих со скидкой
                "original_price_for_two" => $original_price_for_two, // Оригинальная цена за двоих
                "count_place" => $count_place,
                "discount" => $discount,
                "priority" => $priority,
                "tour_type" => $tour_type,
                "hotel_name" => $hotel_name,
                "savings_per_person" => $original_price - $discounted_price_per_person, // Экономия за одного
                "savings_for_two" => $original_price_for_two - $price_for_two // Экономия за двоих
            )
        );

        // Отправляем уведомление администратору
        $savings_info = $discount > 0 ? " (скидка {$discount}%, экономия " . number_format($original_price - $discounted_price_per_person) . " тенге за человека)" : "";
        adminNotification("Добавлен новый кастомный тур в спец предложения. ID: " . $insert_id . ", Отель: " . $hotel_name . ", Цена за одного: " . number_format($discounted_price_per_person) . " тенге" . $savings_info);

    } else {
        throw new Exception("Ошибка выполнения запроса: " . $db->error);
    }

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => "Ошибка при добавлении тура: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>