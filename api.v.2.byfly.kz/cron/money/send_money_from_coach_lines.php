<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем настройки системы
$settingsDB = $db->query("SELECT * FROM app_settings LIMIT 1");
$settings = $settingsDB->fetch_assoc();
$settings['percentage_line_5'] = $settings['percenage_line_5'];

// Даты для подсчета продаж за прошлый месяц
$firstDayLastMonth = date('Y-m-01', strtotime('first day of last month'));
$lastDayLastMonth = date('Y-m-t', strtotime('last day of last month'));

// Получаем платежи за обучение для распределения
$paymentsDB = $db->query("
    SELECT u.*, gc.name_grouped_ru 
    FROM users u
    JOIN grouped_coach gc ON gc.id = u.grouped
    WHERE u.priced_coach > '0' 
    AND u.grouped > '0' 
    AND u.price_oute_in_couch_price_from_lines = '0'
");

// Массив для группировки выплат
$userPayments = [];

while ($payment = $paymentsDB->fetch_assoc()) {
    $paymentId = $payment['id'];
    $amount = $payment['priced_coach'];
    $dateCreate = $payment['date_payment_couch'];
    $groupName = $payment['name_grouped_ru'];
    $studentName = $payment['name'] . ' ' . $payment['surname'];

    $processedUsers = [];
    $processedUsers[] = $payment['id']; // Исключаем самого студента

    $parent_user = $payment['parent_user'];
    $line = 1;
    $totalDistributed = 0;

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

            // Получаем статистику продаж за прошлый месяц
            $soldTours = $db->query("
                SELECT COUNT(*) as count FROM order_tours 
                WHERE (user_id='{$parent['id']}' OR saler_id='{$parent['id']}')
                AND status_code IN (2,3,4)
                AND date_create BETWEEN '$firstDayLastMonth' AND '$lastDayLastMonth'
            ")->fetch_assoc();

            $toursCount = $soldTours['count'];
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

            // Расчет выплаты или упущенной прибыли
            if ($lineAvailable) {
                $percentage = $x2Active ? $settings["percentage_x2_lne_{$line}"] : $settings["percentage_line_{$line}"];
                $lineAmount = ceil(($amount / 100) * $percentage);

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

                $userPayments[$parent['id']]['total'] += $lineAmount;
                $userPayments[$parent['id']]['payments'][] = [
                    'paymentId' => $paymentId,
                    'studentName' => $studentName,
                    'groupName' => $groupName,
                    'date' => $dateCreate,
                    'line' => $line,
                    'amount' => $lineAmount,
                    'x2Active' => $x2Active
                ];
                $totalDistributed += $lineAmount;
            } else {
                $percentage = $settings["percentage_line_{$line}"];
                $missedIncome = ceil(($amount / 100) * $percentage);

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

    // Помечаем платеж как обработанный
    $db->query("UPDATE users SET price_oute_in_couch_price_from_lines = '$totalDistributed' WHERE id = '$paymentId'");
}

// Начисляем средства и отправляем уведомления
foreach ($userPayments as $userId => $data) {
    $user = $data['user'];
    $isAgent = $user['astestation_bal'] > 0;

    // Начисление средств
    if ($data['total'] > 0) {
        $newValue = ($isAgent ? $user['balance'] : $user['bonus']) + $data['total'];
        $updateField = $isAgent ? 'balance' : 'bonus';
        $db->query("UPDATE users SET $updateField = '$newValue' WHERE id = '$userId'");
    }

    // Формируем детализированное сообщение
    $message = "🎓 *Доход от обучения по линиям*\n\n";

    if ($data['total'] > 0) {
        $message .= "💰 *Начислено: " . number_format($data['total'], 2) . " KZT*\n";
        $message .= ($isAgent ? "💳 Текущий баланс: " : "🎁 Бонусный баланс: ") . number_format($newValue, 2) . " KZT\n\n";

        $message .= "🔍 *Детализация начислений:*\n";
        foreach ($data['payments'] as $p) {
            $message .= "➖ Обучение #{$p['paymentId']} от {$p['date']}\n";
            $message .= "   👨‍🎓 Агент: {$p['studentName']}\n";
            $message .= "   🏫 Курс: {$p['groupName']}\n";
            $message .= "   📌 Линия: {$p['line']}" . ($p['x2Active'] ? " (x2 активен)" : "") . "\n";
            $message .= "   💰 Сумма: " . number_format($p['amount'], 2) . " KZT\n\n";
        }
    } else {
        $message .= "ℹ️ В этот раз начислений не было\n\n";
    }

    // Добавляем статистику и упущенную прибыль
    $message .= "📊 *Ваша статистика продаж за прошлый месяц*\n";
    $message .= "🛒 Продано туров: {$data['toursCount']}\n\n";

    if ($data['missedIncome'] > 0) {
        $message .= "⚠️ *Упущенная прибыль: " . number_format($data['missedIncome'], 2) . " KZT*\n";
        $message .= "Вы могли бы заработать больше при выполнении условий:\n";

        if ($data['toursCount'] < $data['requiredTours'][3]) {
            $needed = $data['requiredTours'][3] - $data['toursCount'];
            $message .= "- 3 линия: {$needed} тур(ов) до {$data['requiredTours'][3]}\n";
        }
        if ($data['toursCount'] < $data['requiredTours'][4]) {
            $needed = $data['requiredTours'][4] - $data['toursCount'];
            $message .= "- 4 линия: {$needed} тур(ов) до {$data['requiredTours'][4]}\n";
        }
        if ($data['toursCount'] < $data['requiredTours'][5]) {
            $needed = $data['requiredTours'][5] - $data['toursCount'];
            $message .= "- 5 линия: {$needed} тур(ов) до {$data['requiredTours'][5]}\n";
        }

        $message .= "\n💪 Увеличьте продажи для доступа к новым доходам!\n";
    }

    $message .= "\n✨ Спасибо за развитие команды ByFly Travel!";

    // Сохраняем сообщение
    $escapedMsg = $db->real_escape_string($message);
    $db->query("INSERT INTO send_message_whatsapp 
        (`message`, `phone`, `user_id`, `category`) 
        VALUES 
        ('$escapedMsg', '{$user['phone']}', '$userId', 'education_lines')");
}
?>