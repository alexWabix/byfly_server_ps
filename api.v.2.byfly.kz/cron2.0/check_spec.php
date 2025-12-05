<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Функция для генерации случайного числа в диапазоне
function randomBetween($min, $max)
{
    return rand($min, $max);
}

// Функция для получения случайной причины отмены
function getRandomCancelReason()
{
    $reasons = [
        'Закончились места на рейс - не удалось подтвердить бронирование',
        'Закончились свободные номера в отеле на выбранные даты',
        'Не успели забронировать - предложение больше недоступно',
        'Отсутствует обратная связь от отеля для подтверждения',
        'Туроператор отменил данное предложение',
        'Изменились условия размещения - номера данной категории недоступны',
        'Технические проблемы у туроператора - бронирование невозможно',
        'Превышен лимит бронирований по данному предложению'
    ];

    return $reasons[array_rand($reasons)];
}

// Функция для получения случайного описания доплаты
function getRandomSurchargeDescription()
{
    $descriptions = [
        'Доплата за трансфер до отеля',
        'Доплата за перелет - изменение тарифа',
        'Топливный сбор авиакомпании',
        'Доплата за багаж на рейсе',
        'Курортный сбор в отеле',
        'Доплата за размещение в номере выбранной категории',
        'Сбор за бронирование у туроператора',
        'Доплата за трансфер из аэропорта'
    ];

    return $descriptions[array_rand($descriptions)];
}

// Функция для проверки, является ли дата декабрьской 2025 года
function isDecember2025($flydate)
{
    if (empty($flydate))
        return false;

    // Парсим дату в формате dd.mm.yyyy
    $date_parts = explode('.', $flydate);
    if (count($date_parts) != 3)
        return false;

    $day = intval($date_parts[0]);
    $month = intval($date_parts[1]);
    $year = intval($date_parts[2]);

    // Проверяем период с 10 декабря 2025 по 25 января 2026
    if ($month == 12 && $year == 2025) {
        // Декабрь 2025: с 10 по 31 число
        return $day >= 10;
    } elseif ($month == 1 && $year == 2026) {
        // Январь 2026: с 1 по 25 число
        return $day <= 25;
    }

    return false;
}

// Функция для генерации времени оплаты в зависимости от времени суток
function generatePaymentDeadline()
{
    $current_hour = date('H');

    // Ночное время (22:00 - 06:00) - от 7 до 12 часов
    if ($current_hour >= 22 || $current_hour < 6) {
        $hours = randomBetween(7, 12);
    } else {
        // Дневное время (06:00 - 22:00) - от 2.5 до 7 часов
        $min_minutes = 150; // 2.5 часа в минутах
        $max_minutes = 420; // 7 часов в минутах
        $minutes = randomBetween($min_minutes, $max_minutes);

        // Округляем до целого часа
        $hours = ceil($minutes / 60);
    }

    return date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
}

// Функция для форматирования суммы
function formatPrice($amount)
{
    return number_format($amount, 0, ',', ' ') . ' ₸';
}

// Функция для проверки количества туров с одинаковым tourId
function checkTourIdLimit($db, $tour_id, $current_order_id)
{
    $query = "SELECT COUNT(*) as tour_count 
              FROM order_tours 
              WHERE tourId = '$tour_id' 
              AND id != $current_order_id 
              AND status_code NOT IN (5)"; // Исключаем отмененные заявки

    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['tour_count'];
    }
    return 0;
}

// Функция для получения общего количества заявок по спецам за сегодня
function getTodaySpecOrdersCount($db)
{
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as total_count 
              FROM order_tours 
              WHERE type = 'spec' 
              AND DATE(date_create) = '$today'
              AND id > 16173";

    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_count'];
    }
    return 0;
}

// Функция для получения количества уже обработанных заявок за сегодня по типам
function getTodayProcessedCounts($db)
{
    $today = date('Y-m-d');

    // Считаем заявки с доплатами (есть записи в order_dop_pays)
    $surcharge_query = "SELECT COUNT(DISTINCT o.id) as surcharge_count 
                        FROM order_tours o
                        INNER JOIN order_dop_pays dp ON o.id = dp.order_id
                        WHERE o.type = 'spec' 
                        AND DATE(o.date_create) = '$today'
                        AND o.id > 16173";

    $surcharge_result = $db->query($surcharge_query);
    $surcharge_count = 0;
    if ($surcharge_result && $surcharge_result->num_rows > 0) {
        $row = $surcharge_result->fetch_assoc();
        $surcharge_count = $row['surcharge_count'];
    }

    // Считаем отмененные заявки
    $cancelled_query = "SELECT COUNT(*) as cancelled_count 
                        FROM order_tours 
                        WHERE type = 'spec' 
                        AND DATE(date_create) = '$today'
                        AND id > 16173
                        AND status_code = 5";

    $cancelled_result = $db->query($cancelled_query);
    $cancelled_count = 0;
    if ($cancelled_result && $cancelled_result->num_rows > 0) {
        $row = $cancelled_result->fetch_assoc();
        $cancelled_count = $row['cancelled_count'];
    }

    // Считаем подтвержденные без доплат (статус 2 или выше, но без записей в order_dop_pays)
    $confirmed_query = "SELECT COUNT(*) as confirmed_count 
                        FROM order_tours o
                        LEFT JOIN order_dop_pays dp ON o.id = dp.order_id
                        WHERE o.type = 'spec' 
                        AND DATE(o.date_create) = '$today'
                        AND o.id > 16173
                        AND o.status_code >= 2
                        AND o.status_code != 5
                        AND dp.id IS NULL";

    $confirmed_result = $db->query($confirmed_query);
    $confirmed_count = 0;
    if ($confirmed_result && $confirmed_result->num_rows > 0) {
        $row = $confirmed_result->fetch_assoc();
        $confirmed_count = $row['confirmed_count'];
    }

    return [
        'surcharge' => $surcharge_count,
        'cancelled' => $cancelled_count,
        'confirmed' => $confirmed_count
    ];
}

// Функция для проверки является ли пользователь агентом
function isUserAgent($db, $user_id)
{
    $query = "SELECT user_status FROM users WHERE id = $user_id";
    $result = $db->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return in_array($row['user_status'], ['agent', 'coach', 'alpha', 'ambasador']);
    }

    return false;
}

// Получаем статистику за сегодня
$today_total = getTodaySpecOrdersCount($db);
$processed_counts = getTodayProcessedCounts($db);

// Рассчитываем лимиты на сегодня (20%, 10%, 70%)
$surcharge_limit = ceil($today_total * 0.20); // 20% с доплатой
$cancel_limit = ceil($today_total * 0.10);    // 10% отменяем
$confirm_limit = ceil($today_total * 0.70);   // 70% подтверждаем

// ЭТАП 1: Обработка новых заявок (статус 0 -> статус 2 или 5)
$random_minutes = randomBetween(5, 10);

$query_new_orders = "
    SELECT o.id, o.price, o.user_id, o.date_create, o.tourId, o.tours_info,
           u.name, u.famale, u.surname, u.phone,
           COALESCE(o.saler_id, 0) as seller_id,
           COALESCE(su.name, '') as seller_name, 
           COALESCE(su.famale, '') as seller_famale,
           COALESCE(su.phone, '') as seller_phone
    FROM order_tours o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN users su ON o.saler_id = su.id
    WHERE o.type = 'spec' 
    AND o.id > 16173 
    AND o.status_code = 0 
    AND TIMESTAMPDIFF(MINUTE, o.date_create, NOW()) >= $random_minutes
    AND o.dateOffPay IS NULL
    ORDER BY o.id ASC
";

$result_new = $db->query($query_new_orders);

if ($result_new && $result_new->num_rows > 0) {
    while ($order = $result_new->fetch_assoc()) {
        $order_id = $order['id'];
        $order_price = $order['price'];
        $user_id = $order['user_id'];
        $tour_id = $order['tourId'];

        // Информация о клиенте
        $client_name = trim($order['famale'] . ' ' . $order['name'] . ' ' . $order['surname']);
        $client_phone = $order['phone'];

        // Информация о продавце
        $seller_id = $order['seller_id'];
        $seller_name = trim($order['seller_famale'] . ' ' . $order['seller_name']);
        $seller_phone = $order['seller_phone'];

        // ПРОВЕРЯЕМ - ОДИН И ТОТ ЖЕ ПОЛЬЗОВАТЕЛЬ ИЛИ РАЗНЫЕ
        $is_same_user = ($seller_id > 0 && $seller_id == $user_id);

        // Проверяем является ли пользователь агентом
        $is_agent = isUserAgent($db, $user_id);

        // Информация о туре
        $tour_info = json_decode($order['tours_info'], true);
        $hotel_name = $tour_info['hotelname'] ?? 'Отель не указан';
        $country_name = $tour_info['countryname'] ?? 'Страна не указана';
        $nights = $tour_info['nights'] ?? 0;
        $fly_date = $tour_info['flydate'] ?? '';

        // ПРОВЕРЯЕМ ЛИМИТ ТУРОВ С ОДИНАКОВЫМ tourId
        $existing_tours_count = checkTourIdLimit($db, $tour_id, $order_id);

        if ($existing_tours_count >= 2) {
            // Если уже есть 2 или больше туров с таким же tourId - отменяем заявку
            $cancel_reason = "Все туры выкуплены агентами ByFly Travel";

            // Обновляем заявку - отменяем
            $update_order = "
                UPDATE order_tours 
                SET status_code = 5, 
                    isCancle = 1,
                    cancle_description = '$cancel_reason'
                WHERE id = $order_id
            ";
            $db->query($update_order);

            if ($is_same_user) {
                // Если продавец и клиент один человек - отправляем комбинированное уведомление
                $combined_message = "❌ *Ваша заявка №{$order_id} отменена*\n\n";
                $combined_message .= "📍 *Направление:* {$country_name}\n";
                $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
                $combined_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $combined_message .= "\n🚫 *Причина отмены:*\n";
                $combined_message .= "Все туры по данному предложению выкуплены агентами ByFly Travel\n";
                $combined_message .= "📊 *Количество заявок по туру:* " . ($existing_tours_count + 1) . "\n\n";
                $combined_message .= "💰 *Стоимость:* " . formatPrice($order_price) . "\n\n";
                $combined_message .= "💳 Если была внесена предоплата, она будет возвращена в течение 3-5 рабочих дней\n\n";
                $combined_message .= "🔍 *Рекомендации:*\n";
                $combined_message .= "• Подберите альтернативные туры в том же направлении\n";
                $combined_message .= "• Проверьте другие спец. предложения\n";
                $combined_message .= "• Свяжитесь с менеджером для консультации\n\n";
                $combined_message .= "✅ *ByFly Travel* - всегда найдем лучший вариант! 🌍";

                sendWhatsapp($client_phone, $combined_message);
            } else {
                // Отправляем отдельные уведомления клиенту и продавцу

                // Уведомление клиенту об отмене из-за лимита
                $client_message = "❌ *Заявка №{$order_id} отменена*\n\n";
                $client_message .= "📍 *Направление:* {$country_name}\n";
                $client_message .= "🏨 *Отель:* {$hotel_name}\n";
                $client_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $client_message .= "\n🚫 *Причина отмены:*\n";
                $client_message .= "Все туры по данному предложению выкуплены агентами ByFly Travel\n\n";
                $client_message .= "💰 *Стоимость:* " . formatPrice($order_price) . "\n\n";
                $client_message .= "💳 Если была внесена предоплата, она будет возвращена в течение 3-5 рабочих дней\n\n";
                $client_message .= "🔍 *Не расстраивайтесь!* У нас есть множество других отличных предложений!\n";
                $client_message .= "📞 Обратитесь к вашему менеджеру для подбора альтернативных вариантов\n\n";
                $client_message .= "✅ *ByFly Travel* - всегда найдем лучший вариант для вас! 🌍";

                sendWhatsapp($client_phone, $client_message);

                // Уведомление продавцу об отмене из-за лимита (если есть и это разные люди)
                if ($seller_id > 0 && !empty($seller_phone)) {
                    $seller_message = "❌ *Заявка №{$order_id} ОТМЕНЕНА - ЛИМИТ ТУРОВ*\n\n";
                    $seller_message .= "👤 *Клиент:* {$client_name}\n";
                    $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                    $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $seller_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $seller_message .= "\n🚫 *Причина отмены:*\n";
                    $seller_message .= "Все туры по данному предложению выкуплены агентами ByFly Travel\n";
                    $seller_message .= "📊 *Количество существующих заявок:* " . ($existing_tours_count + 1) . "\n\n";
                    $seller_message .= "💰 *Стоимость была:* " . formatPrice($order_price) . "\n\n";
                    $seller_message .= "📞 *Действия:*\n";
                    $seller_message .= "• ⚡ СРОЧНО свяжитесь с клиентом\n";
                    $seller_message .= "• 🔍 Предложите альтернативные туры\n";
                    $seller_message .= "• 💳 Оформите возврат предоплаты (если была)\n";
                    $seller_message .= "• 🎯 Подберите похожие предложения\n\n";
                    $seller_message .= "⚠️ *Важно:* Клиент ждет альтернативы!\n";
                    $seller_message .= "💼 *ByFly Travel CRM*";

                    sendWhatsapp($seller_phone, $seller_message);
                }
            }

            // Переходим к следующей заявке, так как эту уже отменили
            continue;
        }

        // ПРОВЕРЯЕМ - ДЕКАБРЬСКИЙ ВЫЛЕТ 2025 ГОДА
        $is_december_2025 = isDecember2025($fly_date);

        if ($is_december_2025) {
            // Для новогодних дат (10 декабря - 25 января) - рассчитываем доплату
            // Доплата = 70% от стоимости тура + случайная сумма от 200,000 до 700,000 тенге

            $percentage_surcharge = ceil($order_price * 0.70); // 70% от стоимости тура
            $random_surcharge = rand(200000, 700000); // Случайная сумма от 200,000 до 700,000
            $new_year_surcharge = $percentage_surcharge + $random_surcharge; // Итоговая доплата

            // СТОИМОСТЬ ТУРА НЕ МЕНЯЕМ! Только добавляем доплату в order_dop_pays

            // Добавляем доплату в таблицу order_dop_pays
            $surcharge_description = "Новогодние праздники - дополнительно оплачиваются шоу-программы, гала-ужин и праздничные мероприятия";
            $insert_surcharge = "
        INSERT INTO order_dop_pays (summ, desc_pay, order_id, percentage) 
        VALUES ($new_year_surcharge, '$surcharge_description', $order_id, 0)
    ";
            $db->query($insert_surcharge);

            // Переводим в статус "требуется полная оплата"
            $deadline_pay = generatePaymentDeadline();
            $update_order = "
        UPDATE order_tours 
        SET status_code = 2, 
            dateOffPay = '$deadline_pay'
        WHERE id = $order_id
    ";
            $db->query($update_order);

            $deadline_formatted = date('d.m.Y H:i', strtotime($deadline_pay));

            // Итоговая сумма для отображения в сообщениях
            $total_amount = $order_price + $new_year_surcharge;

            if ($is_same_user) {
                // Если продавец и клиент один человек - отправляем комбинированное уведомление
                $combined_message = "🎄 *НОВОГОДНИЙ ТУР! Заявка №{$order_id}*\n\n";
                $combined_message .= "📍 *Направление:* {$country_name}\n";
                $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
                $combined_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $combined_message .= "\n🎉 *НОВОГОДНИЕ ПРАЗДНИКИ!*\n";
                $combined_message .= "💰 *Стоимость тура:* " . formatPrice($order_price) . "\n";
                $combined_message .= "🎊 *Новогодняя доплата:* " . formatPrice($new_year_surcharge) . "\n";
                $combined_message .= "💳 *ИТОГО к оплате:* " . formatPrice($total_amount) . "\n\n";
                $combined_message .= "⚠️ *ВАЖНАЯ ИНФОРМАЦИЯ:*\n";
                $combined_message .= "При проверке на сайте туроператора выходит дополнительная доплата за новогодние праздники.\n\n";
                $combined_message .= "🎭 *Возможно включено (уточняется у туроператора):*\n";
                $combined_message .= "• Праздничные шоу-программы\n";
                $combined_message .= "• Новогодний гала-ужин\n";
                $combined_message .= "• Праздничные мероприятия в отеле\n";
                $combined_message .= "• Встреча Нового года\n\n";
                $combined_message .= "📋 Точный состав услуг будет уточнен у туроператора после бронирования.\n\n";
                $combined_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                $combined_message .= "🔗 *Для оплаты перейдите:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                $combined_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";

                if ($is_agent) {
                    $combined_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                    $combined_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                    $combined_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n";
                    $combined_message .= "📉 *При блокировке:* Отключается доступ к спец предложениям, накрутка, обнуляются промоушены, возможна пересдача экзамена\n\n";
                }

                $combined_message .= "🎉 Встречайте Новый год в {$country_name}!\n";
                $combined_message .= "✅ *ByFly Travel* - ваш надежный партнер! 🌍";

                sendWhatsapp($client_phone, $combined_message);
            } else {
                // Отправляем отдельные уведомления

                // Уведомление клиенту о новогоднем туре
                $client_message = "🎄 *НОВОГОДНИЙ ТУР! Заявка №{$order_id}*\n\n";
                $client_message .= "📍 *Направление:* {$country_name}\n";
                $client_message .= "🏨 *Отель:* {$hotel_name}\n";
                $client_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $client_message .= "\n🎉 *НОВОГОДНИЕ ПРАЗДНИКИ!*\n";
                $client_message .= "💰 *Стоимость тура:* " . formatPrice($order_price) . "\n";
                $client_message .= "🎊 *Новогодняя доплата:* " . formatPrice($new_year_surcharge) . "\n";
                $client_message .= "💳 *ИТОГО к оплате:* " . formatPrice($total_amount) . "\n\n";
                $client_message .= "⚠️ *ВАЖНАЯ ИНФОРМАЦИЯ:*\n";
                $client_message .= "При проверке на сайте туроператора выходит дополнительная доплата за новогодние праздники.\n\n";
                $client_message .= "🎭 *Возможно включено (уточняется у туроператора):*\n";
                $client_message .= "• Праздничные шоу-программы\n";
                $client_message .= "• Новогодний гала-ужин\n";
                $client_message .= "• Праздничные мероприятия в отеле\n";
                $client_message .= "• Встреча Нового года\n\n";
                $client_message .= "📋 Точный состав услуг будет уточнен у туроператора после бронирования.\n\n";
                $client_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                $client_message .= "🔗 *Для оплаты перейдите:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                $client_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                $client_message .= "🏦 *Рассрочка/кредит:* Свяжитесь с менеджером (Kaspi, Home Credit Bank, Halyk)\n\n";
                $client_message .= "🎉 Встречайте Новый год в {$country_name}!\n";
                $client_message .= "✅ *ByFly Travel* - ваш надежный партнер в мире путешествий! 🌍";

                sendWhatsapp($client_phone, $client_message);

                // Уведомление продавцу о новогоднем туре (если есть и это разные люди)
                if ($seller_id > 0 && !empty($seller_phone)) {
                    $seller_is_agent = isUserAgent($db, $seller_id);

                    $seller_message = "🎄 *НОВОГОДНИЙ ТУР! Заявка №{$order_id}*\n\n";
                    $seller_message .= "👤 *Клиент:* {$client_name}\n";
                    $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                    $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $seller_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $seller_message .= "\n🎉 *НОВОГОДНИЕ ПРАЗДНИКИ!*\n";
                    $seller_message .= "💰 *Стоимость тура:* " . formatPrice($order_price) . "\n";
                    $seller_message .= "🎊 *Новогодняя доплата:* " . formatPrice($new_year_surcharge) . "\n";
                    $seller_message .= "💳 *ИТОГО:* " . formatPrice($total_amount) . "\n";
                    $seller_message .= "⏰ *Срок оплаты:* {$deadline_formatted}\n\n";
                    $seller_message .= "⚠️ *ВАЖНО:* При проверке на сайте туроператора выходит доплата за новогодние праздники.\n\n";
                    $seller_message .= "🎭 *Возможные услуги (уточняется):* Шоу-программы, гала-ужин, праздничные мероприятия\n\n";
                    $seller_message .= "📋 Объясните клиенту, что точный состав услуг будет уточнен у туроператора.\n\n";
                    $seller_message .= "🔗 *Ссылка для оплаты:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $seller_message .= "💳 *Способы оплаты:* Клиент выбирает на сайте\n";
                    $seller_message .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";

                    if ($seller_is_agent) {
                        $seller_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                        $seller_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                        $seller_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n";
                        $seller_message .= "📉 *При блокировке:* Отключается доступ к спец предложениям, накрутка, обнуляются промоушены, возможна пересдача экзамена\n\n";
                    }

                    $seller_message .= "📞 Свяжитесь с клиентом для оплаты\n\n";
                    $seller_message .= "🎉 Отличная продажа новогоднего тура!\n";
                    $seller_message .= "💼 *ByFly Travel CRM*";

                    sendWhatsapp($seller_phone, $seller_message);
                }
            }

            // Переходим к следующей заявке
            continue;
        }

        // Получаем актуальную статистику для обычных туров (не декабрьских)
        $current_processed = getTodayProcessedCounts($db);

        // Определяем действие на основе лимитов
        $action = 'confirm'; // По умолчанию подтверждаем

        if ($current_processed['surcharge'] < $surcharge_limit) {
            $action = 'surcharge'; // Добавляем доплату
        } elseif ($current_processed['cancelled'] < $cancel_limit) {
            $action = 'cancel'; // Отменяем
        } elseif ($current_processed['confirmed'] < $confirm_limit) {
            $action = 'confirm'; // Подтверждаем
        }

        if ($action == 'surcharge') {
            // Обычная доплата для не-декабрьских туров
            $surcharge_amount = randomBetween(10000, 35000);
            $surcharge_description = getRandomSurchargeDescription();

            // Добавляем доплату в таблицу order_dop_pays
            $insert_surcharge = "
                INSERT INTO order_dop_pays (summ, desc_pay, order_id, percentage) 
                VALUES ($surcharge_amount, '$surcharge_description', $order_id, 0)
            ";
            $db->query($insert_surcharge);

            // Обновляем заявку - переводим в статус "требуется полная оплата"
            $deadline_pay = generatePaymentDeadline();
            $update_order = "
                UPDATE order_tours 
                SET status_code = 2, 
                    dateOffPay = '$deadline_pay'
                WHERE id = $order_id
            ";
            $db->query($update_order);

            // Рассчитываем новую общую стоимость
            $total_price = $order_price + $surcharge_amount;
            $deadline_formatted = date('d.m.Y H:i', strtotime($deadline_pay));

            if ($is_same_user) {
                // Если продавец и клиент один человек - отправляем комбинированное уведомление
                $combined_message = "🏖️ *Обновление по вашему туру №{$order_id}*\n\n";
                $combined_message .= "📍 *Направление:* {$country_name}\n";
                $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
                $combined_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $combined_message .= "\n💰 *Требуется доплата:*\n";
                $combined_message .= "📋 {$surcharge_description}\n";
                $combined_message .= "💵 Сумма доплаты: " . formatPrice($surcharge_amount) . "\n\n";
                $combined_message .= "💳 *Общая стоимость тура:* " . formatPrice($total_price) . "\n\n";
                $combined_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                $combined_message .= "🔗 *Для оплаты перейдите:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                $combined_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                $combined_message .= "🏦 *Рассрочка/кредит:* Свяжитесь с менеджером (Kaspi, Home Credit Bank, Halyk)\n\n";

                if ($is_agent) {
                    $combined_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                    $combined_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                    $combined_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n";
                    $combined_message .= "📉 *При блокировке:* Отключается доступ к спец предложениям, накрутка, обнуляются промоушены, возможна пересдача экзамена\n\n";
                }

                $combined_message .= "✅ *ByFly Travel* - ваш надежный партнер! 🌍";

                sendWhatsapp($client_phone, $combined_message);
            } else {
                // Отправляем отдельные уведомления

                // Уведомление клиенту о доплате
                $client_message = "🏖️ *Обновление по вашему туру №{$order_id}*\n\n";
                $client_message .= "📍 *Направление:* {$country_name}\n";
                $client_message .= "🏨 *Отель:* {$hotel_name}\n";
                $client_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $client_message .= "\n💰 *Требуется доплата:*\n";
                $client_message .= "📋 {$surcharge_description}\n";
                $client_message .= "💵 Сумма доплаты: " . formatPrice($surcharge_amount) . "\n\n";
                $client_message .= "💳 *Общая стоимость тура:* " . formatPrice($total_price) . "\n\n";
                $client_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                $client_message .= "🔗 *Для оплаты перейдите:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                $client_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                $client_message .= "🏦 *Рассрочка/кредит:* Свяжитесь с менеджером (Kaspi, Home Credit Bank, Halyk)\n\n";
                $client_message .= "✅ *ByFly Travel* - ваш надежный партнер в мире путешествий! 🌍";

                sendWhatsapp($client_phone, $client_message);

                // Уведомление продавцу о доплате (если есть и это разные люди)
                if ($seller_id > 0 && !empty($seller_phone)) {
                    $seller_is_agent = isUserAgent($db, $seller_id);

                    $seller_message = "💰 *ДОПЛАТА по заявке №{$order_id}*\n\n";
                    $seller_message .= "👤 *Клиент:* {$client_name}\n";
                    $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                    $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $seller_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $seller_message .= "\n💰 *Доплата:*\n";
                    $seller_message .= "📋 {$surcharge_description}\n";
                    $seller_message .= "💵 Сумма: " . formatPrice($surcharge_amount) . "\n";
                    $seller_message .= "💳 Общая стоимость: " . formatPrice($total_price) . "\n";
                    $seller_message .= "⏰ *Срок оплаты:* {$deadline_formatted}\n\n";
                    $seller_message .= "🔗 *Ссылка для оплаты:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $seller_message .= "💳 *Способы оплаты:* Клиент выбирает на сайте\n";
                    $seller_message .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";

                    if ($seller_is_agent) {
                        $seller_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                        $seller_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                        $seller_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n";
                        $seller_message .= "📉 *При блокировке:* Отключается доступ к спец предложениям, накрутка, обнуляются промоушены, возможна пересдача экзамена\n\n";
                    }

                    $seller_message .= "📞 Свяжитесь с клиентом для доплаты\n\n";
                    $seller_message .= "💼 *ByFly Travel CRM*";

                    sendWhatsapp($seller_phone, $seller_message);
                }
            }

        } elseif ($action == 'cancel') {
            // Отменяем заявку
            $cancel_reason = getRandomCancelReason();

            // Обновляем заявку - отменяем
            $update_order = "
                UPDATE order_tours 
                SET status_code = 5, 
                    isCancle = 1,
                    cancle_description = '$cancel_reason'
                WHERE id = $order_id
            ";
            $db->query($update_order);

            if ($is_same_user) {
                // Если продавец и клиент один человек - отправляем комбинированное уведомление
                $combined_message = "❌ *Ваша заявка №{$order_id} отменена*\n\n";
                $combined_message .= "📍 *Направление:* {$country_name}\n";
                $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
                $combined_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $combined_message .= "\n🚫 *Причина отмены:*\n";
                $combined_message .= "{$cancel_reason}\n\n";
                $combined_message .= "💰 *Стоимость:* " . formatPrice($order_price) . "\n\n";
                $combined_message .= "💳 Если была внесена предоплата, она будет возвращена в течение 3-5 рабочих дней\n\n";
                $combined_message .= "💼 *Как агент:* Подберите новые варианты из спец. предложений\n";
                $combined_message .= "📞 При необходимости обратитесь в службу поддержки для нового бронирования\n\n";
                $combined_message .= "✅ *ByFly Travel* - всегда найдем лучший вариант! 🌍";

                sendWhatsapp($client_phone, $combined_message);
            } else {
                // Отправляем отдельные уведомления

                // Уведомление клиенту об отмене
                $client_message = "❌ *Заявка №{$order_id} отменена*\n\n";
                $client_message .= "📍 *Направление:* {$country_name}\n";
                $client_message .= "🏨 *Отель:* {$hotel_name}\n";
                $client_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $client_message .= "\n🚫 *Причина отмены:*\n";
                $client_message .= "{$cancel_reason}\n\n";
                $client_message .= "💰 *Стоимость:* " . formatPrice($order_price) . "\n\n";
                $client_message .= "💳 Если была внесена предоплата, она будет возвращена в течение 3-5 рабочих дней\n\n";
                $client_message .= "🔍 *Не расстраивайтесь!* У нас есть множество других отличных предложений!\n";
                $client_message .= "📞 Обратитесь к вашему менеджеру для подбора альтернативных вариантов\n\n";
                $client_message .= "✅ *ByFly Travel* - всегда найдем лучший вариант для вас! 🌍";

                sendWhatsapp($client_phone, $client_message);

                // Уведомление продавцу об отмене (если есть и это разные люди)
                if ($seller_id > 0 && !empty($seller_phone)) {
                    $seller_message = "❌ *Заявка №{$order_id} ОТМЕНЕНА*\n\n";
                    $seller_message .= "👤 *Клиент:* {$client_name}\n";
                    $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                    $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $seller_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $seller_message .= "\n🚫 *Причина отмены:*\n";
                    $seller_message .= "{$cancel_reason}\n\n";
                    $seller_message .= "💰 *Стоимость была:* " . formatPrice($order_price) . "\n\n";
                    $seller_message .= "📞 *Действия:*\n";
                    $seller_message .= "• ⚡ СРОЧНО свяжитесь с клиентом\n";
                    $seller_message .= "• 🔍 Предложите альтернативные туры\n";
                    $seller_message .= "• 💳 Оформите возврат предоплаты (если была)\n";
                    $seller_message .= "• 🎯 Подберите похожие предложения\n\n";
                    $seller_message .= "⚠️ *Важно:* Клиент ждет альтернативы!\n";
                    $seller_message .= "💼 *ByFly Travel CRM*";

                    sendWhatsapp($seller_phone, $seller_message);
                }
            }

        } else {
            // Подтверждаем заявку без доплат - переводим в статус 2 (требуется полная оплата)
            $deadline_pay = generatePaymentDeadline();

            // Обновляем заявку - переводим в статус "требуется полная оплата"
            $update_order = "
                UPDATE order_tours 
                SET status_code = 2, 
                    dateOffPay = '$deadline_pay'
                WHERE id = $order_id
            ";
            $db->query($update_order);

            $deadline_formatted = date('d.m.Y H:i', strtotime($deadline_pay));

            if ($is_same_user) {
                // Если продавец и клиент один человек - отправляем комбинированное уведомление
                $combined_message = "✅ *Ваша заявка №{$order_id} подтверждена!*\n\n";
                $combined_message .= "📍 *Направление:* {$country_name}\n";
                $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
                $combined_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $combined_message .= "\n💰 *Стоимость тура:* " . formatPrice($order_price) . "\n\n";
                $combined_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                $combined_message .= "🔗 *Для оплаты перейдите:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                $combined_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                $combined_message .= "🏦 *Рассрочка/кредит:* Свяжитесь с менеджером (Kaspi, Home Credit Bank, Halyk)\n\n";

                if ($is_agent) {
                    $combined_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                    $combined_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                    $combined_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n";
                    $combined_message .= "📉 *При блокировке:* Отключается доступ к спец предложениям, накрутка, обнуляются промоушены, возможна пересдача экзамена\n\n";
                }

                $combined_message .= "🎉 Поздравляем! Тур забронирован!\n";
                $combined_message .= "✅ *ByFly Travel* - ваш надежный партнер! 🌍";

                sendWhatsapp($client_phone, $combined_message);
            } else {
                // Отправляем отдельные уведомления

                // Уведомление клиенту о подтверждении
                $client_message = "✅ *Ваша заявка №{$order_id} подтверждена!*\n\n";
                $client_message .= "📍 *Направление:* {$country_name}\n";
                $client_message .= "🏨 *Отель:* {$hotel_name}\n";
                $client_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $client_message .= "\n💰 *Стоимость тура:* " . formatPrice($order_price) . "\n\n";
                $client_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                $client_message .= "🔗 *Для оплаты перейдите:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                $client_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                $client_message .= "🏦 *Рассрочка/кредит:* Свяжитесь с менеджером (Kaspi, Home Credit Bank, Halyk)\n\n";
                $client_message .= "🎉 Поздравляем! Ваш тур забронирован!\n";
                $client_message .= "✅ *ByFly Travel* - ваш надежный партнер в мире путешествий! 🌍";

                sendWhatsapp($client_phone, $client_message);

                // Уведомление продавцу о подтверждении (если есть и это разные люди)
                if ($seller_id > 0 && !empty($seller_phone)) {
                    $seller_is_agent = isUserAgent($db, $seller_id);

                    $seller_message = "✅ *Заявка №{$order_id} ПОДТВЕРЖДЕНА!*\n\n";
                    $seller_message .= "👤 *Клиент:* {$client_name}\n";
                    $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                    $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $seller_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $seller_message .= "\n💰 *Стоимость:* " . formatPrice($order_price) . "\n";
                    $seller_message .= "⏰ *Срок оплаты:* {$deadline_formatted}\n\n";
                    $seller_message .= "🔗 *Ссылка для оплаты:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $seller_message .= "💳 *Способы оплаты:* Клиент выбирает на сайте\n";
                    $seller_message .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";

                    if ($seller_is_agent) {
                        $seller_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                        $seller_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                        $seller_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n";
                        $seller_message .= "📉 *При блокировке:* Отключается доступ к спец предложениям, накрутка, обнуляются промоушены, возможна пересдача экзамена\n\n";
                    }

                    $seller_message .= "📞 Свяжитесь с клиентом для оплаты\n\n";
                    $seller_message .= "🎉 Поздравляем с подтверждением!\n";
                    $seller_message .= "💼 *ByFly Travel CRM*";

                    sendWhatsapp($seller_phone, $seller_message);
                }
            }
        }
    }
}

// ЭТАП 2: Напоминания об оплате (за 1 час и за 15 минут до истечения срока)
$query_reminders = "
    SELECT o.id, o.price, o.user_id, o.dateOffPay,
           COALESCE(SUM(op.summ), 0) as total_paid,
           COALESCE(SUM(dp.summ), 0) as total_surcharges,
           u.name, u.famale, u.surname, u.phone,
           o.tours_info,
           COALESCE(o.saler_id, 0) as seller_id,
           COALESCE(su.name, '') as seller_name, 
           COALESCE(su.famale, '') as seller_famale,
           COALESCE(su.phone, '') as seller_phone,
           TIMESTAMPDIFF(MINUTE, NOW(), o.dateOffPay) as minutes_left
    FROM order_tours o
    LEFT JOIN order_pays op ON o.id = op.order_id
    LEFT JOIN order_dop_pays dp ON o.id = dp.order_id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN users su ON o.saler_id = su.id
    WHERE o.type = 'spec' 
    AND o.id > 16173 
    AND o.status_code = 2 
    AND o.dateOffPay IS NOT NULL 
    AND o.dateOffPay > NOW()
    AND (
        TIMESTAMPDIFF(MINUTE, NOW(), o.dateOffPay) BETWEEN 58 AND 62 OR
        TIMESTAMPDIFF(MINUTE, NOW(), o.dateOffPay) BETWEEN 13 AND 17
    )
    GROUP BY o.id, o.price, o.user_id, o.dateOffPay, u.name, u.famale, u.surname, u.phone, o.tours_info, o.saler_id, su.name, su.famale, su.phone
";

$result_reminders = $db->query($query_reminders);

if ($result_reminders && $result_reminders->num_rows > 0) {
    while ($order = $result_reminders->fetch_assoc()) {
        $order_id = $order['id'];
        $order_price = $order['price'];
        $total_paid = $order['total_paid'];
        $total_surcharges = $order['total_surcharges'];
        $user_id = $order['user_id'];
        $minutes_left = $order['minutes_left'];

        // Информация о клиенте
        $client_name = trim($order['famale'] . ' ' . $order['name'] . ' ' . $order['surname']);
        $client_phone = $order['phone'];

        // Информация о продавце
        $seller_id = $order['seller_id'];
        $seller_name = trim($order['seller_famale'] . ' ' . $order['seller_name']);
        $seller_phone = $order['seller_phone'];

        // ПРОВЕРЯЕМ - ОДИН И ТОТ ЖЕ ПОЛЬЗОВАТЕЛЬ ИЛИ РАЗНЫЕ
        $is_same_user = ($seller_id > 0 && $seller_id == $user_id);

        // Проверяем является ли пользователь агентом
        $is_agent = isUserAgent($db, $user_id);

        // Информация о туре
        $tour_info = json_decode($order['tours_info'], true);
        $hotel_name = $tour_info['hotelname'] ?? 'Отель не указан';
        $country_name = $tour_info['countryname'] ?? 'Страна не указана';
        $nights = $tour_info['nights'] ?? 0;
        $fly_date = $tour_info['flydate'] ?? '';

        // Рассчитываем общую стоимость тура
        $total_required = $order_price + $total_surcharges;
        $remaining_amount = $total_required - $total_paid;

        // Определяем тип напоминания
        $is_final_reminder = ($minutes_left >= 13 && $minutes_left <= 17); // За 15 минут
        $is_hour_reminder = ($minutes_left >= 58 && $minutes_left <= 62); // За 1 час

        if ($remaining_amount > 0) { // Только если есть задолженность
            $deadline_formatted = date('d.m.Y H:i', strtotime($order['dateOffPay']));

            if ($is_final_reminder) {
                // Финальное напоминание за 15 минут
                if ($is_same_user) {
                    $reminder_message = "🚨 *СРОЧНО! Заявка №{$order_id} отменяется через 15 минут!*\n\n";
                    $reminder_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $reminder_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $reminder_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $reminder_message .= "\n💰 *К доплате:* " . formatPrice($remaining_amount) . "\n";
                    $reminder_message .= "⏰ *Срок истекает:* {$deadline_formatted}\n\n";
                    $reminder_message .= "🔗 *СРОЧНО ОПЛАТИТЕ:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $reminder_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                    $reminder_message .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";

                    if ($is_agent) {
                        $reminder_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                        $reminder_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                        $reminder_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n\n";
                    }

                    $reminder_message .= "🚨 *ПОСЛЕДНЕЕ ПРЕДУПРЕЖДЕНИЕ!*\n";
                    $reminder_message .= "✅ *ByFly Travel*";

                    sendWhatsapp($client_phone, $reminder_message);
                } else {
                    // Клиенту
                    $client_reminder = "🚨 *СРОЧНО! Заявка №{$order_id} отменяется через 15 минут!*\n\n";
                    $client_reminder .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $client_reminder .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $client_reminder .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $client_reminder .= "\n💰 *К доплате:* " . formatPrice($remaining_amount) . "\n";
                    $client_reminder .= "⏰ *Срок истекает:* {$deadline_formatted}\n\n";
                    $client_reminder .= "🔗 *СРОЧНО ОПЛАТИТЕ:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $client_reminder .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                    $client_reminder .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";
                    $client_reminder .= "🚨 *ПОСЛЕДНЕЕ ПРЕДУПРЕЖДЕНИЕ!*\n";
                    $client_reminder .= "✅ *ByFly Travel*";

                    sendWhatsapp($client_phone, $client_reminder);

                    // Продавцу (если есть)
                    if ($seller_id > 0 && !empty($seller_phone)) {
                        $seller_is_agent = isUserAgent($db, $seller_id);

                        $seller_reminder = "🚨 *СРОЧНО! Заявка №{$order_id} отменяется через 15 минут!*\n\n";
                        $seller_reminder .= "👤 *Клиент:* {$client_name}\n";
                        $seller_reminder .= "📱 *Телефон:* {$client_phone}\n\n";
                        $seller_reminder .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                        $seller_reminder .= "💰 *К доплате:* " . formatPrice($remaining_amount) . "\n";
                        $seller_reminder .= "⏰ *Срок истекает:* {$deadline_formatted}\n\n";
                        $seller_reminder .= "🔗 *Ссылка:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";

                        if ($seller_is_agent) {
                            $seller_reminder .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                            $seller_reminder .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                            $seller_reminder .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n\n";
                        }

                        $seller_reminder .= "📞 СРОЧНО ЗВОНИТЕ КЛИЕНТУ!\n";
                        $seller_reminder .= "💼 *ByFly Travel CRM*";

                        sendWhatsapp($seller_phone, $seller_reminder);
                    }
                }
            } elseif ($is_hour_reminder) {
                // Напоминание за 1 час
                if ($is_same_user) {
                    $reminder_message = "⏰ *Напоминание! Заявка №{$order_id} - остался 1 час до отмены*\n\n";
                    $reminder_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $reminder_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $reminder_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $reminder_message .= "\n💰 *К доплате:* " . formatPrice($remaining_amount) . "\n";
                    $reminder_message .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                    $reminder_message .= "🔗 *Для оплаты:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $reminder_message .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                    $reminder_message .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";

                    if ($is_agent) {
                        $reminder_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                        $reminder_message .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n\n";
                    }

                    $reminder_message .= "⚠️ *Не забудьте оплатить!*\n";
                    $reminder_message .= "✅ *ByFly Travel*";

                    sendWhatsapp($client_phone, $reminder_message);
                } else {
                    // Клиенту
                    $client_reminder = "⏰ *Напоминание! Заявка №{$order_id} - остался 1 час до отмены*\n\n";
                    $client_reminder .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $client_reminder .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $client_reminder .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $client_reminder .= "\n💰 *К доплате:* " . formatPrice($remaining_amount) . "\n";
                    $client_reminder .= "⏰ *Срок оплаты до:* {$deadline_formatted}\n\n";
                    $client_reminder .= "🔗 *Для оплаты:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";
                    $client_reminder .= "💳 *Способы оплаты:* Выберите удобный способ на сайте\n";
                    $client_reminder .= "🏦 *Рассрочка/кредит:* Kaspi, Home Credit Bank, Halyk\n\n";
                    $client_reminder .= "⚠️ *Не забудьте оплатить!*\n";
                    $client_reminder .= "✅ *ByFly Travel*";

                    sendWhatsapp($client_phone, $client_reminder);

                    // Продавцу (если есть)
                    if ($seller_id > 0 && !empty($seller_phone)) {
                        $seller_is_agent = isUserAgent($db, $seller_id);

                        $seller_reminder = "⏰ *Напоминание! Заявка №{$order_id} - остался 1 час*\n\n";
                        $seller_reminder .= "👤 *Клиент:* {$client_name}\n";
                        $seller_reminder .= "📱 *Телефон:* {$client_phone}\n\n";
                        $seller_reminder .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                        $seller_reminder .= "💰 *К доплате:* " . formatPrice($remaining_amount) . "\n";
                        $seller_reminder .= "⏰ *Срок до:* {$deadline_formatted}\n\n";
                        $seller_reminder .= "🔗 *Ссылка:* https://byfly-travel.com/tour_pay.php?id={$order_id}\n\n";

                        if ($seller_is_agent) {
                            $seller_reminder .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                            $seller_reminder .= "Если заявка не будет оплачена своевременно и за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n\n";
                        }

                        $seller_reminder .= "📞 Напомните клиенту об оплате\n";
                        $seller_reminder .= "💼 *ByFly Travel CRM*";

                        sendWhatsapp($seller_phone, $seller_reminder);
                    }
                }
            }
        }
    }
}

// ЭТАП 3: Отмена неоплаченных заявок (статус 2 - требуется полная оплата)
$query_expired_orders = "
    SELECT o.id, o.price, o.user_id, o.dateOffPay,
           COALESCE(SUM(op.summ), 0) as total_paid,
           COALESCE(SUM(dp.summ), 0) as total_surcharges,
           u.name, u.famale, u.surname, u.phone,
           o.tours_info,
           COALESCE(o.saler_id, 0) as seller_id,
           COALESCE(su.name, '') as seller_name, 
           COALESCE(su.famale, '') as seller_famale,
           COALESCE(su.phone, '') as seller_phone
    FROM order_tours o
    LEFT JOIN order_pays op ON o.id = op.order_id
    LEFT JOIN order_dop_pays dp ON o.id = dp.order_id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN users su ON o.saler_id = su.id
    WHERE o.type = 'spec' 
    AND o.id > 16173 
    AND o.status_code = 2 
    AND o.dateOffPay IS NOT NULL 
    AND o.dateOffPay < NOW()
    GROUP BY o.id, o.price, o.user_id, o.dateOffPay, u.name, u.famale, u.surname, u.phone, o.tours_info, o.saler_id, su.name, su.famale, su.phone
";

$result_expired = $db->query($query_expired_orders);

if ($result_expired && $result_expired->num_rows > 0) {
    while ($order = $result_expired->fetch_assoc()) {
        $order_id = $order['id'];
        $order_price = $order['price'];
        $total_paid = $order['total_paid'];
        $total_surcharges = $order['total_surcharges'];
        $user_id = $order['user_id'];

        // Информация о клиенте
        $client_name = trim($order['famale'] . ' ' . $order['name'] . ' ' . $order['surname']);
        $client_phone = $order['phone'];

        // Информация о продавце
        $seller_id = $order['seller_id'];
        $seller_name = trim($order['seller_famale'] . ' ' . $order['seller_name']);
        $seller_phone = $order['seller_phone'];

        // ПРОВЕРЯЕМ - ОДИН И ТОТ ЖЕ ПОЛЬЗОВАТЕЛЬ ИЛИ РАЗНЫЕ
        $is_same_user = ($seller_id > 0 && $seller_id == $user_id);

        // Проверяем является ли пользователь агентом
        $is_agent = isUserAgent($db, $user_id);

        // Информация о туре
        $tour_info = json_decode($order['tours_info'], true);
        $hotel_name = $tour_info['hotelname'] ?? 'Отель не указан';
        $country_name = $tour_info['countryname'] ?? 'Страна не указана';
        $nights = $tour_info['nights'] ?? 0;
        $fly_date = $tour_info['flydate'] ?? '';

        // Рассчитываем общую стоимость тура
        $total_required = $order_price + $total_surcharges;

        // Если оплачено меньше требуемой суммы - отменяем заявку
        if ($total_paid < $total_required) {
            $cancel_reason = "Заявка отменена автоматически - не поступила полная оплата в установленный срок";

            // Обновляем заявку
            $update_expired = "
                UPDATE order_tours 
                SET status_code = 5, 
                    isCancle = 1,
                    cancle_description = '$cancel_reason'
                WHERE id = $order_id
            ";
            $db->query($update_expired);

            $debt_amount = $total_required - $total_paid;

            if ($is_same_user) {
                // Если продавец и клиент один человек - отправляем комбинированное уведомление
                $combined_message = "⏰ *Ваша заявка №{$order_id} отменена по таймауту*\n\n";
                $combined_message .= "📍 *Направление:* {$country_name}\n";
                $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
                $combined_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $combined_message .= "\n💰 *Финансовая информация:*\n";
                $combined_message .= "💳 Требовалось к оплате: " . formatPrice($total_required) . "\n";
                $combined_message .= "✅ Оплачено: " . formatPrice($total_paid) . "\n";
                $combined_message .= "❌ Не доплачено: " . formatPrice($debt_amount) . "\n\n";
                $combined_message .= "🚫 *Причина отмены:*\n";
                $combined_message .= "Не поступила полная оплата в установленный срок\n\n";
                if ($total_paid > 0) {
                    $combined_message .= "💳 Внесенная предоплата будет возвращена в течение 3-5 рабочих дней\n\n";
                }

                if ($is_agent) {
                    $combined_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                    $combined_message .= "Если за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                    $combined_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n\n";
                }

                $combined_message .= "💼 *Как агент:* Подберите новые варианты из спец. предложений\n";
                $combined_message .= "📞 При необходимости обратитесь в службу поддержки для нового бронирования\n\n";
                $combined_message .= "✅ *ByFly Travel* 🌍";

                sendWhatsapp($client_phone, $combined_message);
            } else {
                // Отправляем отдельные уведомления

                // Уведомление клиенту об автоматической отмене
                $client_message = "⏰ *Заявка №{$order_id} отменена по таймауту*\n\n";
                $client_message .= "📍 *Направление:* {$country_name}\n";
                $client_message .= "🏨 *Отель:* {$hotel_name}\n";
                $client_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $client_message .= "\n💰 *Финансовая информация:*\n";
                $client_message .= "💳 Требовалось к оплате: " . formatPrice($total_required) . "\n";
                $client_message .= "✅ Оплачено: " . formatPrice($total_paid) . "\n";
                $client_message .= "❌ Не доплачено: " . formatPrice($debt_amount) . "\n\n";
                $client_message .= "🚫 *Причина отмены:*\n";
                $client_message .= "Не поступила полная оплата в установленный срок\n\n";
                if ($total_paid > 0) {
                    $client_message .= "💳 Внесенная предоплата будет возвращена в течение 3-5 рабочих дней\n\n";
                }
                $client_message .= "🔍 Мы можем подобрать новые варианты!\n";
                $client_message .= "📞 Обратитесь к менеджеру для нового бронирования\n\n";
                $client_message .= "✅ *ByFly Travel* 🌍";

                sendWhatsapp($client_phone, $client_message);

                // Уведомление продавцу об автоматической отмене (если есть и это разные люди)
                if ($seller_id > 0 && !empty($seller_phone)) {
                    $seller_is_agent = isUserAgent($db, $seller_id);

                    $seller_message = "⏰ *АВТООТМЕНА заявки №{$order_id}*\n\n";
                    $seller_message .= "👤 *Клиент:* {$client_name}\n";
                    $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                    $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                    $seller_message .= "🌙 *Ночей:* {$nights}\n";
                    if ($fly_date) {
                        $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                    }
                    $seller_message .= "\n💰 *Финансы:*\n";
                    $seller_message .= "💳 Требовалось: " . formatPrice($total_required) . "\n";
                    $seller_message .= "✅ Оплачено: " . formatPrice($total_paid) . "\n";
                    $seller_message .= "❌ Недоплата: " . formatPrice($debt_amount) . "\n\n";
                    $seller_message .= "🚫 *Причина:* Таймаут оплаты\n\n";

                    if ($seller_is_agent) {
                        $seller_message .= "⚠️ *ВНИМАНИЕ АГЕНТ:*\n";
                        $seller_message .= "Если за неделю неоплаченных более 3 туров - агент будет заблокирован автоматически.\n";
                        $seller_message .= "🚫 *Блокировка:* 1-е нарушение - 7 дней, 2-е - 14 дней, 3-е - навсегда\n\n";
                    }

                    $seller_message .= "📞 *Действия:*\n";
                    $seller_message .= "• Свяжитесь с клиентом\n";
                    $seller_message .= "• Предложите новое бронирование\n";
                    if ($total_paid > 0) {
                        $seller_message .= "• Оформите возврат предоплаты\n";
                    }
                    $seller_message .= "\n💼 *ByFly Travel CRM*";

                    sendWhatsapp($seller_phone, $seller_message);
                }
            }
        }
    }
}

// ЭТАП 4: Переводим полностью оплаченные заявки в статус "полностью оплачено" (статус 3)
$query_fully_paid = "
    SELECT o.id, o.price, o.user_id,
           COALESCE(SUM(op.summ), 0) as total_paid,
           COALESCE(SUM(dp.summ), 0) as total_surcharges,
           u.name, u.famale, u.surname, u.phone,
           o.tours_info,
           COALESCE(o.saler_id, 0) as seller_id,
           COALESCE(su.name, '') as seller_name, 
           COALESCE(su.famale, '') as seller_famale,
           COALESCE(su.phone, '') as seller_phone
    FROM order_tours o
    LEFT JOIN order_pays op ON o.id = op.order_id
    LEFT JOIN order_dop_pays dp ON o.id = dp.order_id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN users su ON o.saler_id = su.id
    WHERE o.type = 'spec' 
    AND o.id > 16173 
    AND o.status_code = 2 
    GROUP BY o.id, o.price, o.user_id, u.name, u.famale, u.surname, u.phone, o.tours_info, o.saler_id, su.name, su.famale, su.phone
    HAVING total_paid >= (o.price + total_surcharges)
";

$result_fully_paid = $db->query($query_fully_paid);

if ($result_fully_paid && $result_fully_paid->num_rows > 0) {
    while ($order = $result_fully_paid->fetch_assoc()) {
        $order_id = $order['id'];
        $order_price = $order['price'];
        $total_paid = $order['total_paid'];
        $total_surcharges = $order['total_surcharges'];
        $user_id = $order['user_id'];

        // Информация о клиенте
        $client_name = trim($order['famale'] . ' ' . $order['name'] . ' ' . $order['surname']);
        $client_phone = $order['phone'];

        // Информация о продавце
        $seller_id = $order['seller_id'];
        $seller_name = trim($order['seller_famale'] . ' ' . $order['seller_name']);
        $seller_phone = $order['seller_phone'];

        // ПРОВЕРЯЕМ - ОДИН И ТОТ ЖЕ ПОЛЬЗОВАТЕЛЬ ИЛИ РАЗНЫЕ
        $is_same_user = ($seller_id > 0 && $seller_id == $user_id);

        // Информация о туре
        $tour_info = json_decode($order['tours_info'], true);
        $hotel_name = $tour_info['hotelname'] ?? 'Отель не указан';
        $country_name = $tour_info['countryname'] ?? 'Страна не указана';
        $nights = $tour_info['nights'] ?? 0;
        $fly_date = $tour_info['flydate'] ?? '';

        // Рассчитываем общую стоимость тура
        $total_required = $order_price + $total_surcharges;

        // Переводим в статус "полностью оплачено" и убираем дедлайн оплаты
        $update_to_status3 = "
            UPDATE order_tours 
            SET status_code = 3, 
                dateOffPay = NULL
            WHERE id = $order_id
        ";
        $db->query($update_to_status3);

        if ($is_same_user) {
            // Если продавец и клиент один человек - отправляем комбинированное уведомление
            $combined_message = "🎉 *ТУР ПОЛНОСТЬЮ ОПЛАЧЕН! Заявка №{$order_id}*\n\n";
            $combined_message .= "📍 *Направление:* {$country_name}\n";
            $combined_message .= "🏨 *Отель:* {$hotel_name}\n";
            $combined_message .= "🌙 *Ночей:* {$nights}\n";
            if ($fly_date) {
                $combined_message .= "✈️ *Дата вылета:* {$fly_date}\n";
            }
            $combined_message .= "\n💰 *Финансовая информация:*\n";
            $combined_message .= "✅ Полностью оплачено: " . formatPrice($total_paid) . "\n";
            $combined_message .= "💳 Общая стоимость тура: " . formatPrice($total_required) . "\n\n";
            $combined_message .= "🎯 *Статус:* Тур забронирован и полностью оплачен!\n\n";
            $combined_message .= "📋 *Что дальше:*\n";
            $combined_message .= "• В течение 24 часов вы получите документы для поездки\n";
            $combined_message .= "• Проверьте срок действия паспортов всех туристов\n";
            $combined_message .= "• Следите за уведомлениями о рейсах\n";
            $combined_message .= "• При необходимости обратитесь в службу поддержки\n\n";
            $combined_message .= "💼 *Как агент:* Поздравляем с успешной продажей! Комиссия будет начислена после вылета клиентов\n\n";
            $combined_message .= "🌟 Спасибо за выбор ByFly Travel!\n";
            $combined_message .= "✈️ Желаем отличного отдыха! 🌍";

            sendWhatsapp($client_phone, $combined_message);
        } else {
            // Отправляем отдельные уведомления

            // Уведомление клиенту о полной оплате
            $client_message = "🎉 *ТУР ПОЛНОСТЬЮ ОПЛАЧЕН! Заявка №{$order_id}*\n\n";
            $client_message .= "📍 *Направление:* {$country_name}\n";
            $client_message .= "🏨 *Отель:* {$hotel_name}\n";
            $client_message .= "🌙 *Ночей:* {$nights}\n";
            if ($fly_date) {
                $client_message .= "✈️ *Дата вылета:* {$fly_date}\n";
            }
            $client_message .= "\n💰 *Финансовая информация:*\n";
            $client_message .= "✅ Полностью оплачено: " . formatPrice($total_paid) . "\n";
            $client_message .= "💳 Общая стоимость тура: " . formatPrice($total_required) . "\n\n";
            $client_message .= "🎯 *Статус:* Тур забронирован и полностью оплачен!\n\n";
            $client_message .= "📋 *Что дальше:*\n";
            $client_message .= "• В течение 24 часов вы получите документы для поездки\n";
            $client_message .= "• Проверьте срок действия паспортов всех туристов\n";
            $client_message .= "• Следите за уведомлениями о рейсах от вашего менеджера\n";
            $client_message .= "• При вопросах обращайтесь в службу поддержки\n\n";
            $client_message .= "🌟 Спасибо за выбор ByFly Travel!\n";
            $client_message .= "✈️ Желаем отличного отдыха! 🌍";

            sendWhatsapp($client_phone, $client_message);

            // Уведомление продавцу о полной оплате (если есть и это разные люди)
            if ($seller_id > 0 && !empty($seller_phone)) {
                $seller_message = "🎉 *ТУР ПОЛНОСТЬЮ ПРОДАН! Заявка №{$order_id}*\n\n";
                $seller_message .= "👤 *Клиент:* {$client_name}\n";
                $seller_message .= "📱 *Телефон:* {$client_phone}\n\n";
                $seller_message .= "📍 *Тур:* {$country_name}, {$hotel_name}\n";
                $seller_message .= "🌙 *Ночей:* {$nights}\n";
                if ($fly_date) {
                    $seller_message .= "✈️ *Дата вылета:* {$fly_date}\n";
                }
                $seller_message .= "\n💰 *Финансы:*\n";
                $seller_message .= "✅ Получено: " . formatPrice($total_paid) . "\n";
                $seller_message .= "💳 Общая стоимость: " . formatPrice($total_required) . "\n\n";
                $seller_message .= "🎯 *Статус:* ПРОДАНО! Тур полностью оплачен!\n\n";
                $seller_message .= "📋 *Ваши действия:*\n";
                $seller_message .= "• ✅ Тур успешно продан - можете расслабиться!\n";
                $seller_message .= "• 📄 Документы будут подготовлены автоматически\n";
                $seller_message .= "• 💰 Комиссия будет начислена после вылета\n";
                $seller_message .= "• 📞 Поддерживайте связь с клиентом до вылета\n\n";
                $seller_message .= "🏆 *ПОЗДРАВЛЯЕМ С УСПЕШНОЙ ПРОДАЖЕЙ!*\n";
                $seller_message .= "💼 *ByFly Travel CRM*";

                sendWhatsapp($seller_phone, $seller_message);
            }
        }
    }
}


// ЭТАП 6: Статистика выполнения
$stats_message = "📊 *Статистика обработки спец. предложений*\n";
$stats_message .= "🕐 Время выполнения: " . date('Y-m-d H:i:s') . "\n\n";

// Подсчитываем статистику по каждому этапу
$new_orders_count = $result_new ? $result_new->num_rows : 0;
$reminders_count = $result_reminders ? $result_reminders->num_rows : 0;
$expired_orders_count = $result_expired ? $result_expired->num_rows : 0;
$fully_paid_count = $result_fully_paid ? $result_fully_paid->num_rows : 0;

// Получаем финальную статистику за сегодня
$final_processed = getTodayProcessedCounts($db);
$final_total = getTodaySpecOrdersCount($db);

$stats_message .= "🔄 *Обработано заявок в этом запуске:*\n";
$stats_message .= "• Новых заявок (статус 0→2/5): {$new_orders_count}\n";
$stats_message .= "• Отправлено напоминаний: {$reminders_count}\n";
$stats_message .= "• Отмена по таймауту оплаты: {$expired_orders_count}\n";
$stats_message .= "• Полностью оплаченных туров: {$fully_paid_count}\n\n";

$total_processed_now = $new_orders_count + $expired_orders_count + $fully_paid_count;
$stats_message .= "📈 *Обработано в этом запуске:* {$total_processed_now} заявок\n";
$stats_message .= "📢 *Отправлено напоминаний:* {$reminders_count}\n\n";

$stats_message .= "📊 *Статистика за сегодня:*\n";
$stats_message .= "• Всего заявок за день: {$final_total}\n";
$stats_message .= "• С доплатами: {$final_processed['surcharge']} ({$surcharge_limit} лимит)\n";
$stats_message .= "• Отменено: {$final_processed['cancelled']} ({$cancel_limit} лимит)\n";
$stats_message .= "• Подтверждено: {$final_processed['confirmed']} ({$confirm_limit} лимит)\n\n";

// Рассчитываем проценты
if ($final_total > 0) {
    $surcharge_percent = round(($final_processed['surcharge'] / $final_total) * 100, 1);
    $cancel_percent = round(($final_processed['cancelled'] / $final_total) * 100, 1);
    $confirm_percent = round(($final_processed['confirmed'] / $final_total) * 100, 1);

    $stats_message .= "📈 *Процентное соотношение:*\n";
    $stats_message .= "• С доплатами: {$surcharge_percent}% (цель: 20%)\n";
    $stats_message .= "• Отменено: {$cancel_percent}% (цель: 10%)\n";
    $stats_message .= "• Подтверждено: {$confirm_percent}% (цель: 70%)\n\n";
}

$stats_message .= "⏰ *Настройки времени оплаты:*\n";
$stats_message .= "• Ночное время (22:00-06:00): 7-12 часов\n";
$stats_message .= "• Дневное время (06:00-22:00): 2.5-7 часов\n";
$stats_message .= "• Напоминания: за 1 час и за 15 минут\n\n";

$stats_message .= "✅ Скрипт завершен успешно!";

if ($total_processed_now > 0 || $reminders_count > 0) {
    sendWhatsapp('77773700772', $stats_message); // Отправляем статистику администратору
}

// Закрываем соединение с базой данных
if ($db) {
    $db->close();
}
?>