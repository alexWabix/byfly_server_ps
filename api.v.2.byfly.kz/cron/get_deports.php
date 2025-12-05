<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

try {
    $getMonitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();

    if ($getMonitor['get_deports'] == 0) {
        $db->query("UPDATE monitor SET get_deports='1' WHERE id='1'");

        $data = file_get_contents('https://tourvisor.ru/xml/list.php?authlogin=' . $tourvisor_login . '&authpass=' . $tourvisor_password . '&type=departure,country,region,subregion,meal,stars,operator,currency,services&format=json');

        if ($data == 'Authorization Error') {
            $db->query("UPDATE monitor SET get_deports='0' WHERE id='1'");
        } else {
            $data = json_decode($data, true);
            if (empty($data) === false) {
                $db->query("TRUNCATE tours_countries");
                $db->query("TRUNCATE departure_citys");
                $db->query("TRUNCATE tour_region");
                $db->query("TRUNCATE meals");
                $db->query("TRUNCATE stars");
                $db->query("TRUNCATE curencies");
                $db->query("TRUNCATE hotel_services");
                $deport = $data['lists']['departures']['departure'];
                $countries = $data['lists']['countries']['country'];
                $regions = $data['lists']['regions']['region'];
                $meals = $data['lists']['meals']['meal'];
                $stars = $data['lists']['stars']['star'];
                $operators = $data['lists']['operators']['operator'];
                $curencies = $data['lists']['currencies']['currency'];
                $services = $data['lists']['services']['service'];


                foreach ($countries as $country) {
                    if ($country['name'] == 'Киргизия') {
                        $country['name'] = 'Кыргызстан';
                    }
                    $search_country = $db->query("SELECT * FROM countries WHERE title = '" . $country['name'] . "'");
                    if ($search_country->num_rows > 0) {
                        $ct = $search_country->fetch_assoc();
                        $db->query("UPDATE countries SET visor_id='" . $country['id'] . "' WHERE id='" . $ct['id'] . "'");
                    } else {
                        adminNotification('Не удалось найти страну в нашей базе - ' . $country['name'] . '.');
                    }
                }

                foreach ($deport as $city) {
                    if ($city['name'] == 'Мин.Воды') {
                        $city['name'] = 'Минеральные воды';
                    }
                    if ($city['name'] == 'Н.Новгород') {
                        $city['name'] = 'Нижний Новгород';
                    }
                    if ($city['name'] == 'Наб.Челны') {
                        $city['name'] = 'Набережные Челны';
                    }
                    if ($city['name'] == 'П.Камчатский') {
                        $city['name'] = 'Петропавловск-Камчатский';
                    }
                    if ($city['name'] == 'С.Петербург') {
                        $city['name'] = 'Санкт-Петербург';
                    }
                    if ($city['name'] == 'Ю.Сахалинск') {
                        $city['name'] = 'Южно-Сахалинск';
                    }
                    if ($city['name'] == 'Ю.Сахалинск') {
                        $city['name'] = 'Южно-Сахалинск';
                    }

                    if ($city['id'] == 99) {
                        $db->query("INSERT INTO departure_citys (`id`, `id_in_base`, `id_visor`, `name`,  `name_en`, `name_kk`, `countryid`,`min_price`) VALUES (NULL, '0', '" . $city['id'] . "', '" . $city['name'] . "', 'Without a flight', 'Ұшу жоқ',  '0', '0');");
                    } else {
                        $search_city = $db->query("SELECT * FROM regions WHERE title LIKE '" . $city['name'] . "'");
                        if ($search_city->num_rows > 0) {
                            $dp = $search_city->fetch_assoc();
                            $db->query("INSERT INTO departure_citys (`id`, `id_in_base`, `id_visor`, `name`,  `name_en`, `name_kk`, `countryid`,`min_price`) VALUES (NULL, '" . $dp['id'] . "', '" . $city['id'] . "', '" . $city['name'] . "', '" . $dp['title_en'] . "', '" . $dp['title_kk'] . "', '" . $dp['countryid'] . "', '0');");
                            $db->query("UPDATE regions SET visor_id='" . $city['id'] . "' WHERE id='" . $dp['id'] . "'");
                        } else {
                            adminNotification('Не удалось найти город в нашей базе - ' . $city['name'] . ', ' . $city['id'] . '.');
                        }
                    }
                }

                foreach ($regions as $region) {
                    $searchRegion = $db->query("SELECT * FROM regions WHERE `title` LIKE '" . $region['name'] . "'");
                    if ($searchRegion->num_rows > 0) {
                        $gg = $searchRegion->fetch_assoc();
                        $db->query("UPDATE regions SET visor_id='" . $region['id'] . "' WHERE id='" . $gg['id'] . "'");
                    }
                }



                foreach ($meals as $meal) {
                    $mealsResult = $db->query("SELECT * FROM meals_translate WHERE visor_id='" . $meal['id'] . "'")->fetch_assoc();
                    $db->query("INSERT INTO meals (`id`, `id_visor`, `name`, `fullname`, `russian`, `russianfull`, `title_en`, `title_kk`) VALUES (NULL, '" . $meal['id'] . "', '" . $meal['name'] . "', '" . $meal['fullname'] . "', '" . $meal['russian'] . "', '" . $meal['russianfull'] . "', '" . $mealsResult['title_en'] . "', '" . $mealsResult['title_kk'] . "');");
                }


                foreach ($stars as $star) {
                    $db->query("INSERT INTO stars (`id`, `id_visor`, `name`) VALUES (NULL, '" . $star['id'] . "', '" . $star['name'] . "');");
                }

                foreach ($operators as $operator) {
                    $db->query("
                        INSERT INTO operators (`id_visor`, `name`, `fullname`, `russian`, `login`, `password`, `all_data`, `real_link`)
                        VALUES (
                            '" . $db->real_escape_string($operator['id']) . "',
                            '" . $db->real_escape_string($operator['name']) . "',
                            '" . $db->real_escape_string($operator['fullname']) . "',
                            '" . $db->real_escape_string($operator['russian']) . "',
                            '',
                            '',
                            '" . $db->real_escape_string(json_encode($operator, JSON_UNESCAPED_UNICODE)) . "',
                            ''
                        )
                        ON DUPLICATE KEY UPDATE
                            `name` = VALUES(`name`),
                            `fullname` = VALUES(`fullname`),
                            `russian` = VALUES(`russian`),
                            `all_data` = VALUES(`all_data`),
                            `real_link` = VALUES(`real_link`)
                    ");
                }

                foreach ($curencies as $curencie) {
                    $db->query("INSERT INTO curencies (`id`, `id_visor`, `name`, `usd`, `eur`) VALUES (NULL, '" . $curencie['id'] . "', '" . $curencie['name'] . "', '" . $curencie['usd'] . "', '" . $curencie['eur'] . "');");
                }

                foreach ($services as $service) {
                    $db->query("INSERT INTO hotel_services (`id`, `id_visor`, `name`, `grouped`) VALUES (NULL, '" . $service['id'] . "', '" . $service['name'] . "', '" . $service['group'] . "');");
                }
                $db->query("UPDATE monitor SET get_deports='0' WHERE id='1'");
            } else {
                $db->query("UPDATE monitor SET get_deports='0' WHERE id='1'");
            }
        }

    }
} catch (\Throwable $th) {
    $db->query("UPDATE monitor SET get_deports='0' WHERE id='1'");
}

$db->close();
$db2->close();
$db_docs->close();
?>