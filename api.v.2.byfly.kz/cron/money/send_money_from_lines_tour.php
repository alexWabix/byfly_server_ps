<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем настройки системы для распределения прибыли
$settingsDB = $db->query("SELECT * FROM app_settings LIMIT 1");
$settings = $settingsDB->fetch_assoc();
$settings['percentage_line_5'] = $settings['percenage_line_5'];

// Даты для подсчёта туров за прошлый месяц
$firstDayOfLastMonth = date('Y-m-01', strtotime('first day of last month'));
$lastDayOfLastMonth = date('Y-m-t', strtotime('last day of last month'));

// Получаем заявки для обработки
$toursDB = $db->query("SELECT * FROM order_tours WHERE summ_send_money = '0' AND includesPrice > 0");

// Массив для группировки выплат по пользователям
$userPayments = [];

while ($tour = $toursDB->fetch_assoc()) {
    $tourId = $tour['id'];
    $user_id = $tour['user_id'];
    $includesPrice = $tour['includesPrice'];
    $nakrutka = $tour['nakrutka'];
    $nakrutkaSumm = ceil(($includesPrice / 100) * $nakrutka);
    $amountToDistribute = $includesPrice - $nakrutkaSumm;
    $dateCreate = $tour['date_create'];

    $salerInfo = $db->query("SELECT * FROM users WHERE id='$user_id'");
    $saler = $salerInfo->fetch_assoc();
    $salerName = $saler['name'] . ' ' . $saler['surname'];

    $processedUsers = [];
    $processedUsers[] = $saler['id'];

    $parent_user = $saler['parent_user'];
    $line = 1;

    while ($parent_user != 0 && $line <= 5) {
        $parentInfo = $db->query("SELECT * FROM users WHERE id='$parent_user'");
        if ($parentInfo->num_rows > 0) {
            $parent = $parentInfo->fetch_assoc();

            if (in_array($parent['id'], $processedUsers)) {
                $parent_user = $parent['parent_user'];
                $line++;
                continue;
            }

            $processedUsers[] = $parent['id'];

            // Получаем статистику продаж пользователя за прошлый месяц
            $soldToursLastMonthDB = $db->query("
                SELECT COUNT(*) as sold_count 
                FROM order_tours 
                WHERE (user_id='{$parent['id']}' OR saler_id='{$parent['id']}')
                AND status_code IN (2,3,4)
                AND date_create BETWEEN '$firstDayOfLastMonth' AND '$lastDayOfLastMonth'
            ");
            $soldToursLastMonth = $soldToursLastMonthDB->fetch_assoc();
            $toursCount = $soldToursLastMonth['sold_count'];

            $lineAvailable = false;
            $x2Active = ($toursCount >= $settings["x2_count_tours"]);
            $missedIncome = 0;
            $requiredTours = 0;

            // Проверяем доступность линии
            if ($parent['user_status'] == 'alpha') {
                $lineAvailable = ($line <= 5);
            } elseif ($parent['user_status'] == 'coach') {
                $lineAvailable = ($line <= 4);
            } elseif ($parent['user_status'] == 'ambasador') {
                $lineAvailable = ($line <= 3);
            } else {
                if ($line <= $settings['defoul_lines']) {
                    $lineAvailable = true;
                } else {
                    if ($line == 3) {
                        $requiredTours = $settings['line_1_count_tours'];
                        $lineAvailable = ($toursCount >= $requiredTours);
                    } elseif ($line == 4) {
                        $requiredTours = $settings['line_2_count_tours'];
                        $lineAvailable = ($toursCount >= $requiredTours);
                    } elseif ($line == 5) {
                        $requiredTours = $settings['line_3_count_tours'];
                        $lineAvailable = ($toursCount >= $requiredTours);
                    }
                }
            }

            if ($lineAvailable) {
                $percentage = $x2Active ? $settings["percentage_x2_lne_{$line}"] : $settings["percentage_line_{$line}"];
                $lineIncome = $amountToDistribute * ($percentage / 100);

                // Добавляем выплату в массив для группировки
                if (!isset($userPayments[$parent['id']])) {
                    $userPayments[$parent['id']] = [
                        'user' => $parent,
                        'payments' => [],
                        'total' => 0,
                        'toursCount' => $toursCount,
                        'missedIncome' => 0,
                        'requiredTours' => [
                            3 => $settings['line_1_count_tours'],
                            4 => $settings['line_2_count_tours'],
                            5 => $settings['line_3_count_tours']
                        ]
                    ];
                }

                $userPayments[$parent['id']]['payments'][] = [
                    'tourId' => $tourId,
                    'dateCreate' => $dateCreate,
                    'salerName' => $salerName,
                    'line' => $line,
                    'amount' => $lineIncome,
                    'x2Active' => $x2Active
                ];

                $userPayments[$parent['id']]['total'] += $lineIncome;
            } else {
                // Рассчитываем упущенную прибыль
                $percentage = $settings["percentage_line_{$line}"];
                $missedIncome = $amountToDistribute * ($percentage / 100);

                if (!isset($userPayments[$parent['id']])) {
                    $userPayments[$parent['id']] = [
                        'user' => $parent,
                        'payments' => [],
                        'total' => 0,
                        'toursCount' => $toursCount,
                        'missedIncome' => 0,
                        'requiredTours' => [
                            3 => $settings['line_1_count_tours'],
                            4 => $settings['line_2_count_tours'],
                            5 => $settings['line_3_count_tours']
                        ]
                    ];
                }

                $userPayments[$parent['id']]['missedIncome'] += $missedIncome;
            }

            $parent_user = $parent['parent_user'];
            $line++;
        } else {
            break;
        }
    }

    $db->query("UPDATE order_tours SET summ_send_money = '1' WHERE id = '$tourId'");
}

// Отправляем группированные сообщения
foreach ($userPayments as $userId => $data) {
    $user = $data['user'];
    $isAgent = $user['astestation_bal'] > 0;

    // Обновляем баланс/бонус
    if ($data['total'] > 0) {
        $newValue = ($isAgent ? $user['balance'] : $user['bonus']) + $data['total'];
        $updateField = $isAgent ? 'balance' : 'bonus';
        $db->query("UPDATE users SET $updateField = '$newValue' WHERE id = '$userId'");
    }

    // Формируем сообщение
    $message = "📊 *Ваш доход от партнерской программы*\n\n";

    if ($data['total'] > 0) {
        $message .= "💰 *Начислено: " . number_format($data['total'], 2) . " KZT*\n";
        $message .= ($isAgent ? "💳 Баланс: " : "🎁 Бонусный баланс: ") . number_format($newValue, 2) . " KZT\n\n";

        $message .= "🔍 *Детализация начислений:*\n";
        foreach ($data['payments'] as $payment) {
            $message .= "➖ Заявка #{$payment['tourId']} от {$payment['dateCreate']}\n";
            $message .= "   👤 Продавец: {$payment['salerName']}\n";
            $message .= "   📌 Линия: {$payment['line']}" . ($payment['x2Active'] ? " (x2 активен)" : "") . "\n";
            $message .= "   💰 Сумма: " . number_format($payment['amount'], 2) . " KZT\n\n";
        }
    } else {
        $message .= "ℹ️ В этот раз начислений не было\n\n";
    }

    // Добавляем статистику и мотивацию
    $message .= "📈 *Ваша статистика личных продаж за прошлый месяц*\n";
    $message .= "🛒 Продано туров: {$data['toursCount']}\n\n";

    if ($data['missedIncome'] > 0) {
        $message .= "⚠️ *Упущенная прибыль: " . number_format($data['missedIncome'], 2) . " KZT*\n";
        $message .= "Вы могли бы заработать больше, если бы достигли необходимого количества продаж:\n";

        if ($data['toursCount'] < $data['requiredTours'][3]) {
            $needed = $data['requiredTours'][3] - $data['toursCount'];
            $message .= "- Доступ к 3 линии: {$needed} тур(ов) до {$data['requiredTours'][3]}\n";
        }
        if ($data['toursCount'] < $data['requiredTours'][4]) {
            $needed = $data['requiredTours'][4] - $data['toursCount'];
            $message .= "- Доступ к 4 линии: {$needed} тур(ов) до {$data['requiredTours'][4]}\n";
        }
        if ($data['toursCount'] < $data['requiredTours'][5]) {
            $needed = $data['requiredTours'][5] - $data['toursCount'];
            $message .= "- Доступ к 5 линии: {$needed} тур(ов) до {$data['requiredTours'][5]}\n";
        }

        $message .= "\n💪 Не останавливайтесь! Каждая продажа приближает вас к новым доходам!\n";
    }

    $message .= "\n✨ Спасибо, что с нами — *ByFly Travel*! ✈️";

    $escapedMessage = $db->real_escape_string($message);
    $escapedPhone = $db->real_escape_string($user['phone']);

    $db->query("INSERT INTO send_message_whatsapp 
        (`id`, `message`, `date_create`, `phone`, `is_send`, `category`, `user_id`) 
        VALUES 
        (NULL, '$escapedMessage', CURRENT_TIMESTAMP, '$escapedPhone', '0', 'tourslines', '$userId')");
}
?>