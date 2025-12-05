<?php
include('methods/hotels/functions/search_hotel.php');
if (empty($_POST['user_id']) == false) {
    $isAgent = isUserAgent($_POST['user_id']);
    if ($isAgent) {
        $comission_remove = true;
        $commission_percent = 6;
    } else {
        $comission_remove = false;
        $commission_percent = 0;
    }

} else {
    $comission_remove = false;
    $commission_percent = 0;
}

if (empty($_GET['hotel_tours_id']) == false) {
    $_POST['hotel_tours_id'] = $_GET['hotel_tours_id'];
    $_POST['hotel_id'] = $_GET['hotel_id'];
    $_POST['super_sales'] = '0';
}

if (empty($_POST['hotel_id']) == false) {
    $query = array(
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "hotelcode" => $_POST['hotel_id'],
        "imgbig" => 1,
        "reviews" => 1
    );

    $link = 'https://tourvisor.ru/xml/hotel.php?' . http_build_query($query);
    $dataHotel = json_decode(file_get_contents($link), true);

    if (empty($dataHotel) == false) {
        if (empty($dataHotel['error'])) {
            $visorHotelInfo = $dataHotel['data']['hotel'];

            $regionInfo = $db->query("SELECT title_en, title_kk FROM regions WHERE visor_id='" . $visorHotelInfo['regioncode'] . "'")->fetch_assoc();
            $countryInfo = $db->query("SELECT title_en, title_kk FROM countries WHERE visor_id='" . $visorHotelInfo['countrycode'] . "'")->fetch_assoc();

            $visorHotelInfo['region_en'] = $regionInfo['title_en'];
            $visorHotelInfo['region_kk'] = $regionInfo['title_kk'];

            $visorHotelInfo['country_en'] = $countryInfo['title_en'];
            $visorHotelInfo['country_kk'] = $countryInfo['title_kk'];

            $myBaseInfo = searchBaseInfo($visorHotelInfo['name'], $visorHotelInfo['country']);
            if ($myBaseInfo['type'] == 'succ') {
                $myBaseInfo = $myBaseInfo['hotel'];
            } else {
                $myBaseInfo = null;
            }

            if (empty($_POST['hotel_tours_id']) == false) {
                $query = array(
                    "authlogin" => $tourvisor_login,
                    "authpass" => $tourvisor_password,
                    "format" => "json",
                    "tourid" => $_POST['hotel_tours_id'],
                    "request" => 0,
                    "currency" => 3,
                );

                $link = 'http://tourvisor.ru/xml/actualize.php?' . http_build_query($query);

                $dataTours = json_decode(file_get_contents($link), true);
                $departureInfo = $db->query("SELECT name_en, name_kk FROM departure_citys WHERE id_visor='" . $dataTours['data']['tour']['departurecode'] . "'")->fetch_assoc();
                $dataTours['data']['tour']['departure_en'] = $departureInfo['name_en'];
                $dataTours['data']['tour']['departure_kk'] = $departureInfo['name_kk'];

                if (empty($dataTours) == false) {
                    if (empty($dataTours['error'])) {

                        $query = array(
                            "authlogin" => $tourvisor_login,
                            "authpass" => $tourvisor_password,
                            "format" => "json",
                            "tourid" => $_POST['hotel_tours_id'],
                            "currency" => 3,
                        );

                        $link = 'http://tourvisor.ru/xml/actdetail.php?' . http_build_query($query);
                        $actualizeTours = json_decode(file_get_contents($link), true);
                        $actualizationTours = null;
                        $realActualization = null;
                        $actualizeError = null;

                        if (empty($actualizeTours) == false) {
                            if (!$actualizeTours['iserror']) {
                                $actualizationTours = $actualizeTours;
                                $dataTours['data']['tour']['fly_info'] = $actualizationTours;
                            } else {
                                $actualizeError = $actualizeTours;
                            }
                        }

                        if ($comission_remove) {
                            $price_without_commission = $dataTours['data']['tour']['price'] / (1 + ($commission_percent / 100));
                            $dataTours['data']['tour']['price'] = round($price_without_commission);
                        }

                        // Проверяем спец предложения и кастомные туры
                        if ($_POST['super_sales'] == 1) {
                            $customTourFound = false;
                            $specTourFound = false;

                            // Сначала проверяем кастомные туры
                            $searchCustomTourDB = $db->query("SELECT * FROM custom_spec_tours WHERE touridreal='" . $_POST['hotel_tours_id'] . "' AND is_active=1 AND (sales_place < count_place) ORDER BY priority DESC, id DESC LIMIT 1");

                            if ($searchCustomTourDB->num_rows > 0) {
                                $customTour = $searchCustomTourDB->fetch_assoc();
                                $customTourFound = true;

                                // Применяем данные кастомного тура - умножаем цену на 2
                                $dataTours['data']['tour']['price'] = $customTour['price'] * 2;
                                $dataTours['data']['tour']['countPlace'] = $customTour['count_place'];
                                $dataTours['data']['tour']['salesPlace'] = $customTour['sales_place'];
                                $dataTours['data']['tour']['placeForSales'] = ceil($customTour['count_place'] - $customTour['sales_place']);

                                // Рассчитываем скидку если есть
                                if (!empty($customTour['discount']) && $customTour['discount'] > 0) {
                                    $original_price = ($customTour['price'] / (1 - ($customTour['discount'] / 100))) * 2;
                                    $dataTours['data']['tour']['priceold'] = round($original_price);
                                    $dataTours['data']['tour']['discount_percent'] = $customTour['discount'];
                                }

                                $dataTours['data']['tour']['is_custom_tour'] = true;
                                $dataTours['data']['tour']['custom_tour_id'] = $customTour['id'];
                            }

                            // Если ни кастомный тур, ни спец предложение не найдены, используем старую логику
                            if (!$customTourFound && !$specTourFound) {
                                $searchPriceDB = $db->query("SELECT * FROM spec_tours WHERE tour_id='" . $_POST['hotel_tours_id'] . "' ORDER BY id DESC LIMIT 1");
                                if ($searchPriceDB->num_rows > 0) {
                                    $searchPrice = $searchPriceDB->fetch_assoc();

                                    $dataTours['data']['tour']['price'] = $searchPrice['price'] * 2;
                                    $dataTours['data']['tour']['countPlace'] = $searchPrice['count_place'];
                                    $dataTours['data']['tour']['salesPlace'] = $searchPrice['sales_place'];
                                    $dataTours['data']['tour']['placeForSales'] = ceil($searchPrice['count_place'] - $searchPrice['sales_place']);
                                }
                            }
                        }

                        echo json_encode(
                            array(
                                "type" => true,
                                "data" => array(
                                    "visor_info" => $visorHotelInfo,
                                    "byfly_info" => $myBaseInfo,
                                    "tour_info" => $dataTours['data']['tour'],
                                    "actualization" => $actualizationTours,
                                    "actualize_err" => $actualizeError,
                                    "user_id" => $_COOKIE['user_id'],
                                ),
                            ),
                            JSON_UNESCAPED_UNICODE
                        );
                    } else {
                        echo json_encode(
                            array(
                                "type" => true,
                                "data" => array(
                                    "visor_info" => $visorHotelInfo,
                                    "byfly_info" => $myBaseInfo,
                                    "tour_info" => null,
                                    "user_id" => $_COOKIE['user_id'],
                                ),
                            ),
                            JSON_UNESCAPED_UNICODE
                        );
                    }

                } else {
                    echo json_encode(
                        array(
                            "type" => true,
                            "data" => array(
                                "visor_info" => $visorHotelInfo,
                                "byfly_info" => $myBaseInfo,
                                "tour_info" => null,
                            ),
                        ),
                        JSON_UNESCAPED_UNICODE
                    );
                }

            } else {
                echo json_encode(
                    array(
                        "type" => true,
                        "data" => array(
                            "visor_info" => $visorHotelInfo,
                            "byfly_info" => $myBaseInfo,
                            "tour_info" => null,
                        ),
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            }

        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Error get Hotel Information...',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error get Hotel Information...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }

} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Dont send hotel id parameters',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>