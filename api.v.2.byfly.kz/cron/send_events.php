<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


try {
    $monitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();
    if ($monitor['sended_group_events'] == 0) {
        $db->query("UPDATE monitor SET sended_group_events='1' WHERE id='1'");

        $events = '';
        $listEventsDB = $db->query("SELECT * FROM events WHERE date_start > '" . date('Y-m-d H:i:s') . "'");
        if ($listEventsDB->num_rows > 0) {
            $events = "🎉 *Предстоящие мероприятия компании:*\n\n";
            while ($event = $listEventsDB->fetch_assoc()) {
                $title = $event['title_ru'];
                $desc = $event['desc_ru'];
                $dateStart = date("d.m.Y H:i", strtotime($event['date_start']));
                $city = $event['city'];
                $price = number_format($event['price'], 0, '', ' ');
                $maxPeople = $event['max_people'];

                $events .= "📍 *Название*: {$title}\n";
                $events .= "🗓️ *Дата*: {$dateStart}\n";
                $events .= "🏙️ *Город*: {$city}\n";
                $events .= "💰 *Цена*: {$price} KZT\n";
                $events .= "👥 *Максимальное количество участников*: {$maxPeople}\n";
                $events .= "ℹ️ *Описание*: {$desc}\n";
                $events .= "_________________\n\n";
            }
        }



        $couchEvents = '';
        $listPotoks = $db->query("SELECT * FROM grouped_coach WHERE date_start_coaching > '" . date('Y-m-d H:i:s') . "'");
        if ($listPotoks->num_rows > 0) {
            $couchEvents = "🎓 *Предстоящие потоки обучения агентов:*\n\n";
            while ($potok = $listPotoks->fetch_assoc()) {
                $name = $potok['name_grouped_ru'];
                $lang = $potok['lang_groups'];
                $dateStart = date("d.m.Y H:i", strtotime($potok['date_start_coaching']));
                $dateEnd = date("d.m.Y H:i", strtotime($potok['date_end_coaching']));
                $city = $potok['coaching_city'];
                $address = $potok['coaching_adress'];
                $maxPeople = $potok['max_people'];
                $whatsappLink = $potok['group_whatsapp'];

                $couchEvents .= "📚 *Название группы*: {$name}\n";
                $couchEvents .= "🌐 *Язык обучения*: {$lang}\n";
                $couchEvents .= "🗓️ *Дата начала*: {$dateStart}\n";
                $couchEvents .= "🗓️ *Дата окончания*: {$dateEnd}\n";
                $couchEvents .= "🏙️ *Город*: {$city}\n";
                $couchEvents .= "📍 *Адрес*: {$address}\n";
                $couchEvents .= "👥 *Максимальное количество участников*: {$maxPeople}\n";
                if (!empty($whatsappLink)) {
                    $couchEvents .= "🔗 *Группа WhatsApp*: {$whatsappLink}\n";
                }
                $couchEvents .= "_________________\n\n";
            }
        }


        $listAkciyas = $db->query("SELECT * FROM promo_agent WHERE date_stop IS NULL OR date_stop > '" . date('Y-m-d H:i:s') . "'");


        $akciyas = '';
        if ($listAkciyas->num_rows > 0) {
            $akciyas = "🔥 *Текущие акции для агентов:*\n\n";
            while ($akciya = $listAkciyas->fetch_assoc()) {
                $title = $akciya['title'];
                $description = $akciya['description'];

                $akciyas .= "🎁 *Акция*: {$title}\n";
                $akciyas .= "ℹ️ *Описание*: {$description}\n";
                $akciyas .= "🎓 Чтобы участвовать в акции, необходимо пройти обучение.\n";
                $akciyas .= "_________________\n\n";
            }
        }

        $msg .= $events . $couchEvents . $akciyas;
        $groupsDB = $db->query("SELECT * FROM user_whatsapp_groups");
        while ($groups = $groupsDB->fetch_assoc()) {
            $presents = '';
            $listPresentsDB = $db->query("SELECT * FROM present_event WHERE date_start > '" . date("Y-m-d H:i:s") . "'");

            if ($listPresentsDB->num_rows > 0) {
                $presents = "📅 *Предстоящие презентации компании:*\n\n";
                while ($listPresents = $listPresentsDB->fetch_assoc()) {
                    $dateStart = date("d.m.Y H:i", strtotime($listPresents['date_start']));
                    $dateOff = date("d.m.Y H:i", strtotime($listPresents['date_off']));
                    $type = $listPresents['type']; // 0 - физическая, 1 - онлайн
                    $city = $listPresents['city'];
                    $address = $listPresents['adress'];
                    $link = 'https://byfly.kz/?type=event&eventId=' . $listPresents['id'] . '&agent=' . $groups['user_id'];

                    $presents .= "📍 *Дата*: с {$dateStart} по {$dateOff}\n";

                    if ($type == 1) { // Онлайн
                        $presents .= "🌐 *Тип*: Онлайн\n";
                        $presents .= "🔗 *Ссылка на презентацию*: {$link}\n";
                    } else { // Физическая
                        $presents .= "🏢 *Тип*: Физическая\n";
                        $presents .= "🏙️ *Город*: {$city}\n";
                        $presents .= "📍 *Адрес*: {$address}\n";
                        if (!empty($link)) {
                            $presents .= "🔗 *Подробнее*: {$link}\n";
                        }
                    }
                    $presents .= "_________________\n\n";
                }
            }

            $send = $msg . $presents;

            sendWhatsappGroup($groups['group_id'], $send);
            sleep(3);
        }



        $db->query("UPDATE monitor SET sended_group_events='0' WHERE id='1'");
    }
} catch (\Throwable $th) {
    $db->query("UPDATE monitor SET sended_group_events='0' WHERE id='1'");
}


