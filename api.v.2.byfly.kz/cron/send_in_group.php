<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function getHotTours($city, $count)
{
    global $tourvisor_login;
    global $tourvisor_password;
    global $db;

    $query = array(
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "items" => $count,
        "city" => $city,
        "currency" => 3,
        "picturetype" => 0,
        "tourtype" => 0,
        "datefrom" => date("d.m.Y"),
        "dateto" => date("d.m.Y", strtotime("+40 days")),
        "sort" => 1,
    );

    $city = explode(',', $city);
    $count = 0;
    foreach ($city as $ctr) {
        $count++;
        if ($count == 1) {
            $query['city'] = $ctr;
        } else {
            $query['city' . $count] = $ctr;
            $query['uniq' . $count] = 1;
        }
    }

    $url = 'http://tourvisor.ru/xml/hottours.php?' . http_build_query($query);
    $data = file_get_contents($url);
    $datae = json_decode($data, true);

    $tours = array();
    if ($datae['data']['status']['state'] != 'no search results') {
        foreach ($datae['hottours']['tour'] as $tour) {
            $tours[] = $tour;
        }
    } else {
        return array(
            "type" => false,
            "msg" => "Не найдено результатов."
        );
    }

    return array(
        "type" => true,
        "tours" => $tours,
    );
}

try {
    $monitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();
    if ($monitor['sended_groups'] == 0) {
        $db->query("UPDATE monitor SET sended_groups='1' WHERE id='1'");
        $getGroupsDB = $db->query("SELECT * FROM user_whatsapp_groups");
        while ($getGroups = $getGroupsDB->fetch_assoc()) {
            $listTours = getHotTours($getGroups['city_id'], 10);

            if (!$listTours['type']) {
                echo "Нет туров для города с ID {$getGroups['city_id']}\n";
                continue;
            }

            $msg = "🔥 *Горящие туры*:\n\n";
            foreach ($listTours['tours'] as $tour) {
                $priceForTwo = $tour['price'] * 2;
                $oldPriceForTwo = $priceForTwo * 1.2;

                if ($getGroups['defoult_nakrutka'] > 0) {
                    $priceForTwo = $priceForTwo + (($priceForTwo / 100) * $getGroups['defoult_nakrutka']);
                    $oldPriceForTwo = $oldPriceForTwo + (($oldPriceForTwo / 100) * $getGroups['defoult_nakrutka']);

                    $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'] . "&agent=" . $getGroups['user_id'] . "&pu=" . $getGroups['defoult_nakrutka'];
                } else {
                    $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'] . "&agent=" . $getGroups['user_id'];
                }


                $formattedPriceForTwo = number_format($priceForTwo, 0, '', ' ');
                $formattedOldPriceForTwo = number_format($oldPriceForTwo, 0, '', ' ');

                // Формируем сообщение
                $msg .= "🌍 *Страна*: {$tour['countryname']}\n";
                $msg .= "🏨 *Отель*: {$tour['hotelname']} ({$tour['hotelstars']}⭐)\n";
                $msg .= "📍 *Регион*: {$tour['hotelregionname']}\n";
                $msg .= "✈️ *Вылет*: {$tour['departurename']} - {$tour['flydate']}\n";
                $msg .= "⏳ *Ночей*: {$tour['nights']}\n";
                $msg .= "🍴 *Питание*: {$tour['meal']}\n";
                $msg .= "💰 *Цена за 2 человека*: ~{$formattedOldPriceForTwo}~ ➡️ {$formattedPriceForTwo} KZT\n";
                $msg .= "🔗 " . $link . "\n";
                $msg .= "________________\n\n";
            }

            sendWhatsappGroup($getGroups['group_id'], $msg);
            sleep(3);
        }



        $city = '59';
        $listTours = getHotTours($city, 5);


        $msg = "🔥 *Горящие туры*:\n\n";
        foreach ($listTours['tours'] as $tour) {
            // Умножаем цену на 2 для двух взрослых
            $priceForTwo = $tour['price'] * 2;

            // Рассчитываем старую цену (например, увеличив на 20%)
            $oldPriceForTwo = $priceForTwo * 1.2;

            // Форматируем цену с разделением на разряды
            $formattedPriceForTwo = number_format($priceForTwo, 0, '', ' ');
            $formattedOldPriceForTwo = number_format($oldPriceForTwo, 0, '', ' ');

            // Генерируем ссылку
            if ($getGroups['defoult_nakrutka'] > 0) {
                $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'];
            } else {
                $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'];
            }

            // Формируем сообщение
            $msg .= "🌍 *Страна*: {$tour['countryname']}\n";
            $msg .= "🏨 *Отель*: {$tour['hotelname']} ({$tour['hotelstars']}⭐)\n";
            $msg .= "📍 *Регион*: {$tour['hotelregionname']}\n";
            $msg .= "✈️ *Вылет*: {$tour['departurename']} - {$tour['flydate']}\n";
            $msg .= "⏳ *Ночей*: {$tour['nights']}\n";
            $msg .= "🍴 *Питание*: {$tour['meal']}\n";
            $msg .= "💰 *Цена за 2 человека*: ~{$formattedOldPriceForTwo}~ ➡️ {$formattedPriceForTwo} KZT\n";
            $msg .= "🔗 " . $link . "\n";
            $msg .= "________________\n\n";
        }


        $city = '60';
        $listTours = getHotTours($city, 5);


        foreach ($listTours['tours'] as $tour) {
            // Умножаем цену на 2 для двух взрослых
            $priceForTwo = $tour['price'] * 2;

            // Рассчитываем старую цену (например, увеличив на 20%)
            $oldPriceForTwo = $priceForTwo * 1.2;

            // Форматируем цену с разделением на разряды
            $formattedPriceForTwo = number_format($priceForTwo, 0, '', ' ');
            $formattedOldPriceForTwo = number_format($oldPriceForTwo, 0, '', ' ');

            // Генерируем ссылку
            if ($getGroups['defoult_nakrutka'] > 0) {
                $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'];
            } else {
                $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'];
            }

            // Формируем сообщение
            $msg .= "🌍 *Страна*: {$tour['countryname']}\n";
            $msg .= "🏨 *Отель*: {$tour['hotelname']} ({$tour['hotelstars']}⭐)\n";
            $msg .= "📍 *Регион*: {$tour['hotelregionname']}\n";
            $msg .= "✈️ *Вылет*: {$tour['departurename']} - {$tour['flydate']}\n";
            $msg .= "⏳ *Ночей*: {$tour['nights']}\n";
            $msg .= "🍴 *Питание*: {$tour['meal']}\n";
            $msg .= "💰 *Цена за 2 человека*: ~{$formattedOldPriceForTwo}~ ➡️ {$formattedPriceForTwo} KZT\n";
            $msg .= "🔗 " . $link . "\n";
            $msg .= "________________\n\n";
        }


        $city = '79';
        $listTours = getHotTours($city, 5);


        foreach ($listTours['tours'] as $tour) {
            // Умножаем цену на 2 для двух взрослых
            $priceForTwo = $tour['price'] * 2;

            // Рассчитываем старую цену (например, увеличив на 20%)
            $oldPriceForTwo = $priceForTwo * 1.2;

            // Форматируем цену с разделением на разряды
            $formattedPriceForTwo = number_format($priceForTwo, 0, '', ' ');
            $formattedOldPriceForTwo = number_format($oldPriceForTwo, 0, '', ' ');

            // Генерируем ссылку
            if ($getGroups['defoult_nakrutka'] > 0) {
                $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'];
            } else {
                $link = "https://byfly.kz/?type=tourshotel&hotel=" . $tour['hotelcode'] . "&tourid=" . $tour['tourid'];
            }

            // Формируем сообщение
            $msg .= "🌍 *Страна*: {$tour['countryname']}\n";
            $msg .= "🏨 *Отель*: {$tour['hotelname']} ({$tour['hotelstars']}⭐)\n";
            $msg .= "📍 *Регион*: {$tour['hotelregionname']}\n";
            $msg .= "✈️ *Вылет*: {$tour['departurename']} - {$tour['flydate']}\n";
            $msg .= "⏳ *Ночей*: {$tour['nights']}\n";
            $msg .= "🍴 *Питание*: {$tour['meal']}\n";
            $msg .= "💰 *Цена за 2 человека*: ~{$formattedOldPriceForTwo}~ ➡️ {$formattedPriceForTwo} KZT\n";
            $msg .= "🔗 " . $link . "\n";
            $msg .= "________________\n\n";
        }

        $loastGroupsDB = $db->query("SELECT * FROM `group_dont_byfly` WHERE `checked` = 1");
        while ($loastGroups = $loastGroupsDB->fetch_assoc()) {
            sendWhatsappGroup($loastGroups['chatid'], $msg);
            sleep(3);
        }

        $db->query("UPDATE monitor SET sended_groups='0' WHERE id='1'");
    }
} catch (\Throwable $th) {
    $db->query("UPDATE monitor SET sended_groups='0' WHERE id='1'");
}


