<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

try {
    // Получаем настройки монитора
    $monitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();

    if ($monitor['check_ceils'] == 0) {
        $db->query("UPDATE monitor SET check_ceils='1' WHERE id='1'");

        // Уведомление через 3 часа после открытия ячейки
        $listNotify = $db->query("SELECT * FROM `copilka_ceils` WHERE `summ_money` = '0' AND `date_create` < NOW() - INTERVAL 3 HOUR AND (`last_notify` IS NULL OR `last_notify` < NOW() - INTERVAL 3 HOUR)");

        while ($notifyCeil = $listNotify->fetch_assoc()) {
            $userInfo = $db->query("SELECT * FROM `users` WHERE `id` = '" . $notifyCeil['user_id'] . "'")->fetch_assoc();

            // Проверяем, отправлялось ли сообщение уже этой ячейке
            if (empty($notifyCeil['last_notify'])) {
                $message = "Здравствуйте, " . $userInfo['famale'] . " " . $userInfo['name'] . "!\n\n❗ Напоминание об оплате ❗\n\n" .
                    "Прошло 3 часа с момента открытия вашей накопительной ячейки, но вы ещё не внесли первый платеж. " .
                    "Если оплата не будет внесена в течение следующих 3 часов, ячейка будет удалена.\n\n" .
                    "💳 Kaspi.kz\n💳 Кредит\n💳 Рассрочка\n💳 Сертификат\n💳 Картой любого банка\n\n" .
                    "Оплатить можно по ссылке: www.byfly.kz";

                sendWhatsapp($userInfo['phone'], $message);

                // Обновляем поле last_notify для ячейки
                $db->query("UPDATE `copilka_ceils` SET `last_notify` = NOW() WHERE `id` = '" . $notifyCeil['id'] . "'");
                sleep(2);
            }
        }

        // Удаление ячеек через 6 часов после открытия, если платеж не внесен
        $listDelete = $db->query("SELECT * FROM `copilka_ceils` WHERE `summ_money` = '0' AND `date_create` < NOW() - INTERVAL 6 HOUR");

        while ($deleteCeil = $listDelete->fetch_assoc()) {
            $userInfo = $db->query("SELECT * FROM `users` WHERE `id` = '" . $deleteCeil['user_id'] . "'")->fetch_assoc();

            $message = "Здравствуйте, " . $userInfo['famale'] . " " . $userInfo['name'] . "!\n\n❗ Уведомление ❗\n\n" .
                "Ваша накопительная ячейка была удалена, так как в течение 6 часов после открытия не был внесён первый платеж. " .
                "Теперь она доступна другим пользователям.\n\n" .
                "Вы можете открыть новую ячейку и оплатить её:\n" .
                "💳 Kaspi.kz\n💳 Кредит\n💳 Рассрочка\n💳 Сертификат\n💳 Картой любого банка\n\n" .
                "Ссылка для оплаты: www.byfly.kz";

            sendWhatsapp($userInfo['phone'], $message);
            $db->query("DELETE FROM `copilka_ceils` WHERE `id` = '" . $deleteCeil['id'] . "'");
            sleep(2);
        }

        // Обрабатываем ячейки, которые имеют месячный платеж (оставляем без изменений)
        $listMonthly = $db->query("SELECT * FROM `copilka_ceils` WHERE `summ_money` >= 50000");

        while ($monthlyCeil = $listMonthly->fetch_assoc()) {
            $lastPaymentDate = new DateTime($monthlyCeil['date_last_payment']);
            $nextPaymentDate = clone $lastPaymentDate;
            $nextPaymentDate->modify('+1 month');
            $now = new DateTime();

            // Если просрочка менее 3 дней
            if ($now > $nextPaymentDate && $now <= $nextPaymentDate->modify('+3 days')) {
                $userInfo = $db->query("SELECT * FROM `users` WHERE `id` = '" . $monthlyCeil['user_id'] . "'")->fetch_assoc();

                // Проверяем, отправлялось ли сообщение уже этому пользователю
                $existingNotify = $db->query("SELECT * FROM `copilka_ceils` WHERE `user_id` = '" . $monthlyCeil['user_id'] . "' AND `last_notify` IS NOT NULL")->fetch_assoc();

                if (!$existingNotify) {
                    $message = "Здравствуйте, " . $userInfo['famale'] . " " . $userInfo['name'] . "!\n\n❗ Напоминание об оплате ❗\n\n" .
                        "Вам необходимо внести ежемесячный платёж в размере 50 000 ₸. Если оплата не будет внесена в течение 3 дней, " .
                        "все накопленные бонусы будут обнулены, а ячейка будет закрыта.\n\n" .
                        "Оплатить можно через:\n💳 Kaspi.kz\n💳 Кредит\n💳 Рассрочка\n💳 Сертификат\n💳 Картой любого банка\n\n" .
                        "Ссылка для оплаты: www.byfly.kz";

                    sendWhatsapp($userInfo['phone'], $message);
                    sleep(2);

                    $db->query("UPDATE `copilka_ceils` SET `last_notify` = NOW() WHERE `user_id` = '" . $monthlyCeil['user_id'] . "'");
                }
            }

            // Если просрочка более 3 дней
            if ($now > $nextPaymentDate->modify('+3 days')) {
                $userInfo = $db->query("SELECT * FROM `users` WHERE `id` = '" . $monthlyCeil['user_id'] . "'")->fetch_assoc();

                $db->query("UPDATE `copilka_ceils` SET 
                    `date_dosrok_close` = NOW(), 
                    `date_money_send` = DATE_ADD(NOW(), INTERVAL 90 DAY), 
                    `summ_bonus` = 0, 
                    `month_1_bonus` = 0, 
                    `month_2_bonus` = 0, 
                    `month_3_bonus` = 0, 
                    `month_4_bonus` = 0, 
                    `month_5_bonus` = 0, 
                    `month_6_bonus` = 0, 
                    `month_7_bonus` = 0, 
                    `month_8_bonus` = 0, 
                    `month_9_bonus` = 0, 
                    `month_10_bonus` = 0, 
                    `month_11_bonus` = 0, 
                    `month_12_bonus` = 0 
                    WHERE `id` = '" . $monthlyCeil['id'] . "'");

                $message = "Здравствуйте, " . $userInfo['famale'] . " " . $userInfo['name'] . "!\n\n❗ Уведомление о закрытии ❗\n\n" .
                    "Вы просрочили ежемесячный платёж на более чем 3 дня. Все ваши бонусы были обнулены.";

                sendWhatsapp($userInfo['phone'], $message);
                sleep(2);
            }
        }
        $db->query("UPDATE monitor SET check_ceils='0' WHERE id='1'");
    }
    echo 'Выполнено';
} catch (Exception $e) {
    $db->query("UPDATE monitor SET check_ceils='0' WHERE id='1'");
    echo $e->getMessage();
}