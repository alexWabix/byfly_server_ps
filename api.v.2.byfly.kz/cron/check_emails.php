<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$monitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();
if ($monitor['kaspi_check_pays'] == 0) {
    $db->query("UPDATE monitor SET kaspi_check_pays = '1', last_check_kaspi = '" . date('Y-m-d H:i:s') . "' WHERE id='1'");
    try {
        $hostname = '{imap.mail.ru:993/imap/ssl}INBOX';
        $username = 'byfly.kz@mail.ru';
        $password = 'G9DuGrxYSfyLFdqe1wPc';

        $inbox = imap_open($hostname, $username, $password) or die('Не удалось подключиться: ' . imap_last_error());
        $emails = imap_search($inbox, 'UNSEEN FROM "kaspi.payments@kaspibank.kz"');

        if ($emails) {
            rsort($emails);
            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, 0);
                $structure = imap_fetchstructure($inbox, $email_number);
                $message = '';

                if (isset($structure->parts) && count($structure->parts)) {
                    for ($i = 0; $i < count($structure->parts); $i++) {
                        $part = $structure->parts[$i];
                        if ($part->type == 0) {
                            $message .= imap_fetchbody($inbox, $email_number, $i + 1);
                            if ($part->encoding == 3) {
                                $message = base64_decode($message);
                            } elseif ($part->encoding == 4) {
                                $message = quoted_printable_decode($message);
                            }
                            if (isset($part->parameters)) {
                                foreach ($part->parameters as $param) {
                                    if (strtolower($param->attribute) == 'charset') {
                                        $message = mb_convert_encoding($message, 'UTF-8', $param->value);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $message = imap_fetchbody($inbox, $email_number, 1);
                    if ($structure->encoding == 3) {
                        $message = base64_decode($message);
                    } elseif ($structure->encoding == 4) {
                        $message = quoted_printable_decode($message);
                    }
                    if (isset($structure->parameters)) {
                        foreach ($structure->parameters as $param) {
                            if (strtolower($param->attribute) == 'charset') {
                                $message = mb_convert_encoding($message, 'UTF-8', $param->value);
                            }
                        }
                    }
                }

                $lines = explode("\n", trim($message));
                $parsed_data = [];
                foreach ($lines as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $value) = explode(':', $line, 2);
                        $parsed_data[trim($key)] = trim($value);
                    }
                }

                foreach ($lines as $line) {
                    if (strpos($line, 'Номер заказа') !== false) {
                        $order_parts = explode('=', $line);
                        if (isset($order_parts[1])) {
                            $parsed_data['Номер заказа'] = trim($order_parts[1]);
                        }
                    }
                    if (strpos($line, 'Идентификатор платежа') !== false) {
                        $parsed_data['Идентификатор платежа'] = trim(explode(':', $line)[1]);
                    }
                }

                $subject = imap_mime_header_decode($overview[0]->subject);
                $decoded_subject = implode('', array_map(fn($part) => $part->text, $subject));

                $from = imap_mime_header_decode($overview[0]->from);
                $decoded_from = implode('', array_map(fn($part) => $part->text, $from));

                $parsed_data['Тема'] = $decoded_subject;
                $parsed_data['Отправитель'] = $decoded_from;
                $parsed_data['Дата'] = $overview[0]->date;

                $response = [];
                foreach ($parsed_data as $key => $value) {
                    switch ($key) {
                        case 'Услуга':
                            $response['service'] = $value;
                            break;
                        case 'ФИО отдыхающего':
                            $response['fio'] = $value;
                            break;
                        case 'ИИН отдыхающего':
                            $response['iin'] = $value;
                            break;
                        case 'Платеж на сумму':
                            $response['summ'] = $value;
                            break;
                        case 'Дата':
                            $response['date'] = $value;
                            break;
                        case 'Номер заказа':
                            $response['order_number'] = $value;
                            break;
                        case 'Идентификатор платежа':
                            $response['payment_id'] = $value;
                            break;
                    }
                }

                checkPay($response);
            }
        }

        imap_close($inbox);
    } catch (Exception $e) {
        error_log('Ошибка: ' . $e->getMessage());
    } finally {
        $db->query("UPDATE monitor SET kaspi_check_pays = '0', last_check_kaspi = '" . date('Y-m-d H:i:s') . "' WHERE id='1'");
    }
} else {
    if ($monitor['last_check_kaspi'] !== NULL) {
        $now = new DateTime();
        $lastCheckTime = new DateTime($monitor['last_check_kaspi']);
        $interval = $now->diff($lastCheckTime);
        if ($interval->i >= 5) {
            $db->query("UPDATE monitor SET last_check_kaspi = '" . date('Y-m-d H:i:s') . "', kaspi_check_pays='0' WHERE id='1'");
        }
    }
}

function checkPay($order)
{
    global $db;

    // Сначала ищем по ID в order_kaspi_pays
    $search_order_kaspi_db = $db->query("SELECT * FROM order_kaspi_pays WHERE id='" . $order['order_number'] . "'");

    // Если не найдено по ID, ищем по номеру заявки в order_tours
    if ($search_order_kaspi_db->num_rows == 0) {
        // Проверяем, существует ли заявка с таким ID в order_tours
        $order_tours_check = $db->query("SELECT * FROM order_tours WHERE id='" . $order['order_number'] . "'");

        if ($order_tours_check->num_rows > 0) {
            $order_tour_info = $order_tours_check->fetch_assoc();

            // Создаем запись в order_kaspi_pays для этой заявки
            $insert_kaspi_pay = $db->query("INSERT INTO order_kaspi_pays 
                (`order_id`, `summ`, `user_id`, `date_create`, `type`) 
                VALUES 
                ('" . $order['order_number'] . "', '" . $order['summ'] . "', '" . $order_tour_info['user_id'] . "', CURRENT_TIMESTAMP, 'tour')");

            if ($insert_kaspi_pay) {
                $kaspi_pay_id = $db->insert_id;

                // Теперь получаем созданную запись для дальнейшей обработки
                $search_order_kaspi_db = $db->query("SELECT * FROM order_kaspi_pays WHERE id='" . $kaspi_pay_id . "'");
            }
        }
    }

    if ($search_order_kaspi_db->num_rows > 0) {
        $search_order_kaspi = $search_order_kaspi_db->fetch_assoc();

        if ($search_order_kaspi['type'] == 'tour') {
            $userInfo = null;
            $userInfoDb = $db->query("SELECT * FROM users WHERE id='" . $search_order_kaspi['user_id'] . "'");
            if ($userInfoDb->num_rows > 0) {
                $userInfo = $userInfoDb->fetch_assoc();
            }

            $db->query("UPDATE order_kaspi_pays SET summ='" . $order['summ'] . "', date_sended_pay='" . date('Y-m-d H:i:s') . "', tranzaction_number='" . $order['payment_id'] . "' WHERE id='" . $search_order_kaspi['id'] . "'");

            $orderInfo = null;
            $orderInfoDB = $db->query("SELECT * FROM order_tours WHERE id='" . $search_order_kaspi['order_id'] . "'");
            if ($orderInfoDB->num_rows > 0) {
                $orderInfo = $orderInfoDB->fetch_assoc();
            }

            if ($orderInfo != null) {
                $orderInfo['includesPrice'] = $orderInfo['includesPrice'] + $order['summ'];

                $orderInfoRealPrice = $db->query("SELECT SUM(summ) as ct FROM order_dop_pays WHERE order_id='" . $orderInfo['id'] . "'")->fetch_assoc()['ct'];
                if ($orderInfoRealPrice == null) {
                    $orderInfoRealPrice = 0;
                }
                $orderInfoRealPrice = $orderInfoRealPrice + $orderInfo['price'];

                $searchTranzactionDB = $db->query("SELECT * FROM order_pays WHERE tranzaction_id ='" . $order['payment_id'] . "'");
                if ($searchTranzactionDB->num_rows == 0) {
                    if ($orderInfo['includesPrice'] >= $orderInfo['predoplata']) {
                        if ($orderInfo['includesPrice'] >= $orderInfoRealPrice) {
                            $db->query("UPDATE order_tours SET status_code='3', includesPrice='" . $orderInfo['includesPrice'] . "' WHERE id='" . $orderInfo['id'] . "'");
                        } else {
                            $db->query("UPDATE order_tours SET status_code='2', includesPrice='" . $orderInfo['includesPrice'] . "' WHERE id='" . $orderInfo['id'] . "'");
                        }
                    } else {
                        $db->query("UPDATE order_tours SET includesPrice='" . $orderInfo['includesPrice'] . "' WHERE id='" . $orderInfo['id'] . "'");
                    }

                    $db->query("INSERT INTO order_pays (`order_id`, `summ`, `user_id`, `date_create`, `type`, `tranzaction_id`) 
                        VALUES ('" . $search_order_kaspi['order_id'] . "', '" . $order['summ'] . "', '" . $userInfo['id'] . "', CURRENT_TIMESTAMP, 'kaspi', '" . $order['payment_id'] . "')");

                    // Add transaction record with all fields
                    $db->query("INSERT INTO user_tranzactions 
                                (`date_create`, `summ`, `type_operations`, `user_id`, `pay_info`, `operation`, `user_get_pay`, `payments`, `tour_id`) 
                                VALUES 
                                (CURRENT_TIMESTAMP, '" . $order['summ'] . "', '0', '" . $userInfo['id'] . "', 'Оплата тура через Kaspi', 'tour', '" . $userInfo['id'] . "', 'Kaspi', '" . $orderInfo['id'] . "')");

                    // Отправляем уведомление пользователю об оплате
                    sendPaymentNotification($orderInfo, $userInfo, $order['summ'], $orderInfoRealPrice);
                }
            }

        } else if ($search_order_kaspi['type'] == 'coach') {
            $userInfo = $db->query("SELECT * FROM users WHERE id='" . $search_order_kaspi['user_id'] . "'")->fetch_assoc();
            $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $search_order_kaspi['group_id'] . "'")->fetch_assoc();

            if ($order['summ'] >= $search_order_kaspi['summ']) {
                $userInfo['price_coach'] = $userInfo['price_coach'] - $order['summ'];
                $userInfo['price_coach_tour'] = $userInfo['price_coach_tour'] - $order['summ'];
                $userInfo['price_coach_online'] = $userInfo['price_coach_online'] - $order['summ'];

                $db->query("UPDATE users SET date_validate_agent='" . $groupInfo['date_validation'] . "', date_couch_start='" . $groupInfo['date_start_coaching'] . "', orient='test', grouped='" . $groupInfo['id'] . "', coach_id='" . $groupInfo['coach_id'] . "', price_coach='" . $userInfo['price_coach'] . "', price_coach_tour='" . $userInfo['price_coach_tour'] . "', price_coach_online='" . $userInfo['price_coach_online'] . "' WHERE id='" . $userInfo['id'] . "'");
                $db->query("INSERT INTO user_statused (`code_status`, `date_add`, `user_id`) VALUES ('4', CURRENT_TIMESTAMP, '" . $userInfo['id'] . "')");

                $db->query("INSERT INTO user_tranzactions 
                            (`date_create`, `summ`, `type_operations`, `user_id`, `pay_info`, `operation`, `user_get_pay`, `payments`, `tour_id`) 
                            VALUES 
                            (CURRENT_TIMESTAMP, '" . $order['summ'] . "', '0', '" . $userInfo['id'] . "', 'Полная оплата обучения через Kaspi', 'coach', '" . $userInfo['id'] . "', 'Kaspi', '0')");
            } else {
                $userInfo['price_coach'] = $userInfo['price_coach'] - $order['summ'];
                $userInfo['price_coach_tour'] = $userInfo['price_coach_tour'] - $order['summ'];
                $userInfo['price_coach_online'] = $userInfo['price_coach_online'] - $order['summ'];

                $db->query("UPDATE users SET price_coach='" . $userInfo['price_coach'] . "', price_coach_tour='" . $userInfo['price_coach_tour'] . "', price_coach_online='" . $userInfo['price_coach_online'] . "' WHERE id='" . $userInfo['id'] . "'");

                $db->query("INSERT INTO user_tranzactions 
                            (`date_create`, `summ`, `type_operations`, `user_id`, `pay_info`, `operation`, `user_get_pay`, `payments`, `tour_id`) 
                            VALUES 
                            (CURRENT_TIMESTAMP, '" . $order['summ'] . "', '0', '" . $userInfo['id'] . "', 'Частичная оплата обучения через Kaspi', 'coach', '" . $userInfo['id'] . "', 'Kaspi', '0')");
            }
            $db->query("UPDATE order_kaspi_pays SET summ='" . $order['summ'] . "', date_sended_pay='" . date('Y-m-d H:i:s') . "', tranzaction_number='" . $order['payment_id'] . "' WHERE id='" . $search_order_kaspi['id'] . "'");
        } else if ($search_order_kaspi['type'] == 'copilka') {
            $userInfo = $db->query("SELECT * FROM users WHERE id='" . $search_order_kaspi['user_id'] . "'")->fetch_assoc();
            $ceilsInfo = $db->query("SELECT * FROM copilka_ceils WHERE id='" . $search_order_kaspi['group_id'] . "'")->fetch_assoc();

            $month = getNextPaymentMonth($ceilsInfo);

            $ceilsInfo["month_" . $month . "_money"] = $ceilsInfo["month_" . $month . "_money"] + $order['summ'];
            $ceilsInfo["month_" . $month . "_bonus"] = $ceilsInfo["month_" . $month . "_bonus"] + $order['summ'];

            $ceilsInfo["summ_bonus"] = $ceilsInfo["summ_bonus"] + $order['summ'];
            $ceilsInfo["summ_money"] = $ceilsInfo["summ_money"] + $order['summ'];

            $db->query("UPDATE copilka_ceils SET summ_bonus='" . $ceilsInfo["summ_bonus"] . "', summ_money='" . $ceilsInfo["summ_money"] . "', month_" . $month . "_money='" . $ceilsInfo["month_" . $month . "_money"] . "', month_" . $month . "_bonus='" . $ceilsInfo["month_" . $month . "_bonus"] . "' WHERE id='" . $ceilsInfo['id'] . "'");

            // Updated transaction record with all fields for copilka
            $db->query("INSERT INTO user_tranzactions 
                        (`date_create`, `summ`, `type_operations`, `user_id`, `pay_info`, `operation`, `user_get_pay`, `payments`, `tour_id`) 
                        VALUES 
                        (CURRENT_TIMESTAMP, '" . $order['summ'] . "', '0', '" . $userInfo['id'] . "', 'Пополнение накопительной ячейки через Kaspi', 'copilka', '" . $userInfo['id'] . "', 'Kaspi', '0')");

            $db->query("UPDATE order_kaspi_pays SET summ='" . $order['summ'] . "', date_sended_pay='" . date('Y-m-d H:i:s') . "', tranzaction_number='" . $order['payment_id'] . "' WHERE id='" . $search_order_kaspi['id'] . "'");

            $formattedSum = number_format($order['summ'], 0, '.', ' ');

            $message = "Здравствуйте! 👋\n\n";
            $message .= "Поздравляем вас с пополнением накопительной ячейки на сумму: {$formattedSum} KZT 💰.\n\n";
            $message .= "Оплата произведена через Kaspi.\n\n";
            $message .= "Ваша сумма успешно зачислена и теперь доступна для дальнейшего использования. Мы ценим ваш вклад в систему! 🙏\n\n";
            $message .= "Для проверки и получения подробной информации о балансе, пожалуйста, перейдите в ваш профиль на сайте: www.byfly.kz 🌐.\n\n";
            sendWhatsapp($userInfo['phone'], $message);
        }
    }
}

function sendPaymentNotification($orderInfo, $userInfo, $paidAmount, $totalRequired)
{
    global $db;

    // Получаем информацию о туре
    $tourInfo = json_decode($orderInfo['tours_info'], true);
    $hotelName = isset($tourInfo['hotelname']) ? $tourInfo['hotelname'] : 'Не указан';
    $countryName = isset($tourInfo['countryname']) ? $tourInfo['countryname'] : 'Не указана';
    $nights = isset($tourInfo['nights']) ? $tourInfo['nights'] : 'Не указано';
    $flyDate = isset($tourInfo['flydate']) ? $tourInfo['flydate'] : null;

    $formattedPaid = number_format($paidAmount, 0, '.', ' ');
    $formattedTotal = number_format($totalRequired, 0, '.', ' ');
    $formattedCurrentTotal = number_format($orderInfo['includesPrice'], 0, '.', ' ');

    $remaining = $totalRequired - $orderInfo['includesPrice'];
    $formattedRemaining = number_format($remaining, 0, '.', ' ');

    $message = "💳 *ПЛАТЕЖ ПОЛУЧЕН! Заявка №{$orderInfo['id']}*\n\n";
    $message .= "📍 *Направление:* {$countryName}\n";
    $message .= "🏨 *Отель:* {$hotelName}\n";
    $message .= "🌙 *Ночей:* {$nights}\n";
    if ($flyDate) {
        $message .= "✈️ *Дата вылета:* {$flyDate}\n";
    }
    $message .= "\n💰 *Финансовая информация:*\n";
    $message .= "✅ Получен платеж: {$formattedPaid} ₸\n";
    $message .= "💵 Оплачено всего: {$formattedCurrentTotal} ₸\n";
    $message .= "💳 Общая стоимость: {$formattedTotal} ₸\n";

    if ($remaining > 0) {
        $message .= "⏳ Осталось доплатить: {$formattedRemaining} ₸\n\n";

        if ($orderInfo['status_code'] == '2') {
            $message .= "🎯 *Статус:* Тур подтвержден! Ожидает доплату\n\n";
            $message .= "📋 *Что дальше:*\n";
            $message .= "• Доплатите оставшуюся сумму до полной оплаты\n";
            $message .= "• После полной оплаты получите документы\n";
            $message .= "• Следите за уведомлениями от менеджера\n\n";
        } else {
            $message .= "🎯 *Статус:* Ожидает подтверждения и доплаты\n\n";
            $message .= "📋 *Что дальше:*\n";
            $message .= "• Ожидайте подтверждения от туроператора\n";
            $message .= "• Приготовьте оставшуюся сумму для доплаты\n";
            $message .= "• Менеджер свяжется с вами в ближайшее время\n\n";
        }
    } else {
        $message .= "\n🎯 *Статус:* ТУР ПОЛНОСТЬЮ ОПЛАЧЕН!\n\n";
        $message .= "📋 *Что дальше:*\n";
        $message .= "• В течение 24 часов получите документы\n";
        $message .= "• Проверьте срок действия паспортов\n";
        $message .= "• Следите за информацией о рейсах\n\n";
    }

    $message .= "📞 *Поддержка:* +7 (777) 370-07-73\n";
    $message .= "🌐 *Сайт:* www.byfly.kz\n\n";
    $message .= "Спасибо за выбор ByFly Travel! ✈️";

    sendWhatsapp($userInfo['phone'], $message);
}

function getNextPaymentMonth($ceilInfo)
{
    for ($i = 1; $i <= 12; $i++) {
        $monthColumnMoney = 'month_' . $i . '_money';

        if ($ceilInfo[$monthColumnMoney] < 50000) {
            return $i;
        }
    }
    return 1;
}

$db->close();
?>