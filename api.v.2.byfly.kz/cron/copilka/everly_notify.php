<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function sendOverduePaymentsNotification()
{
    global $db;

    // Получаем текущую дату
    $currentDate = new DateTime();
    $today = $currentDate->format('Y-m-d');
    $todayFormatted = $currentDate->format('d.m.Y');

    // Запрос для получения просроченных платежей
    $sql = "SELECT 
                c.id,
                u.name,
                u.famale,
                u.surname,
                u.phone,
                c.date_create,
                c.summ_money,
                c.summ_bonus,
                c.obrabotan,
                c.date_dosrok_close,
                c.month_1_money, c.month_2_money, c.month_3_money, 
                c.month_4_money, c.month_5_money, c.month_6_money,
                c.month_7_money, c.month_8_money, c.month_9_money,
                c.month_10_money, c.month_11_money, c.month_12_money,
                c.month_1_bonus, c.month_2_bonus, c.month_3_bonus,
                c.month_4_bonus, c.month_5_bonus, c.month_6_bonus,
                c.month_7_bonus, c.month_8_bonus, c.month_9_bonus,
                c.month_10_bonus, c.month_11_bonus, c.month_12_bonus
            FROM copilka_ceils c
            JOIN users u ON c.user_id = u.id
            WHERE c.date_dosrok_close IS NULL";

    $result = $db->query($sql);
    $overduePayments = [];
    $totalOverdueAmount = 0;
    $userNotifications = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $createDate = new DateTime($row['date_create']);
            $createDay = $createDate->format('d');
            $createMonth = (int) $createDate->format('m');
            $createYear = (int) $createDate->format('Y');

            // Проверяем все 12 месяцев на просрочку
            for ($monthNum = 1; $monthNum <= 12; $monthNum++) {
                $moneyField = "month_{$monthNum}_money";
                $moneyValue = (float) $row[$moneyField];

                // Если платеж не внесен
                if ($moneyValue == 0) {
                    try {
                        // Рассчитываем дату платежа для этого месяца
                        $paymentMonth = $createMonth + $monthNum - 1;
                        $paymentYear = $createYear;

                        // Корректируем год, если месяц превышает 12
                        if ($paymentMonth > 12) {
                            $paymentYear += floor(($paymentMonth - 1) / 12);
                            $paymentMonth = ($paymentMonth % 12) ?: 12;
                        }

                        // Проверяем, существует ли такая дата
                        if (!checkdate($paymentMonth, $createDay, $paymentYear)) {
                            // Если дата не существует (например, 31 февраля), берем последний день месяца
                            $paymentDate = new DateTime();
                            $paymentDate->setDate($paymentYear, $paymentMonth, 1);
                            $paymentDate->modify('last day of this month');
                        } else {
                            $paymentDate = new DateTime("$paymentYear-$paymentMonth-$createDay");
                        }

                        // Если дата платежа уже прошла
                        if ($currentDate > $paymentDate) {
                            $daysOverdue = $currentDate->diff($paymentDate)->days;

                            // Запись для администратора
                            $overduePayments[] = [
                                'phone' => $row['phone'],
                                'name' => trim($row['famale'] . ' ' . $row['name'] . ' ' . $row['surname']),
                                'days_overdue' => $daysOverdue,
                                'amount' => 50000,
                                'payment_date' => $paymentDate->format('d.m.Y'),
                                'month_num' => $monthNum
                            ];

                            $totalOverdueAmount += 50000;

                            // Формируем уведомление для пользователя
                            if (!isset($userNotifications[$row['phone']])) {
                                $userNotifications[$row['phone']] = [
                                    'name' => trim($row['famale'] . ' ' . $row['name'] . ' ' . $row['surname']),
                                    'months' => [],
                                    'total_days' => 0
                                ];
                            }

                            $userNotifications[$row['phone']]['months'][] = [
                                'month_num' => $monthNum,
                                'payment_date' => $paymentDate->format('d.m.Y'),
                                'days_overdue' => $daysOverdue
                            ];

                            if ($daysOverdue > $userNotifications[$row['phone']]['total_days']) {
                                $userNotifications[$row['phone']]['total_days'] = $daysOverdue;
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error processing payment for user {$row['id']} month {$monthNum}: " . $e->getMessage());
                        continue;
                    }
                }
            }
        }

        // Отправляем уведомления пользователям
        foreach ($userNotifications as $phone => $data) {
            $userMessage = "Уважаемый(ая) {$data['name']}!\n\n";
            $userMessage .= "❗️ *У вас есть просроченные платежи по накопительной ячейке:*\n";

            foreach ($data['months'] as $month) {
                $userMessage .= "- Месяц {$month['month_num']} (платеж до {$month['payment_date']}) - просрочка {$month['days_overdue']} дн.\n";
            }

            $userMessage .= "\n⚠️ *Важно:* Если платеж не будет внесен в течение 3 дней с даты платежа, ваша накопительная ячейка будет:\n";
            $userMessage .= "1. Автоматически закрыта\n";
            $userMessage .= "2. Без возможности восстановления\n";
            $userMessage .= "3. Все внесенные средства будут возвращены вам в течение 90 календарных дней\n\n";
            $userMessage .= "🔹 Для внесения платежа перейдите в раздел \"Накопительные ячейки\" в приложении ByFly Travel\n";
            $userMessage .= "🔹 Или свяжитесь с вашим менеджером\n\n";
            $userMessage .= "Это автоматическое уведомление. Пожалуйста, не отвечайте на это сообщение.\n";
            $userMessage .= "Дата уведомления: {$todayFormatted}";

            sendWhatsapp($phone, $userMessage);
        }
    } elseif (!$result) {
        error_log("Database error: " . $db->error);
        return [
            'type' => false,
            'msg' => 'Database error',
            'count' => 0,
            'total_amount' => 0
        ];
    }

    // Формируем сообщение для администратора только если есть просрочки
    if (count($overduePayments) > 0) {
        $adminMessage = "🔔 *Список просроченных платежей на {$todayFormatted}* 🔔\n\n";
        $adminMessage .= "Всего просрочек: " . count($overduePayments) . "\n";
        $adminMessage .= "Общая сумма просрочек: " . number_format($totalOverdueAmount, 0, '', ' ') . " ₸\n";
        $adminMessage .= "Уведомления отправлены " . count($userNotifications) . " пользователям\n\n";

        foreach ($overduePayments as $payment) {
            $adminMessage .= "👤 *" . $payment['name'] . "*\n";
            $adminMessage .= "📱 " . $payment['phone'] . "\n";
            $adminMessage .= "💰 Сумма: " . number_format($payment['amount'], 0, '', ' ') . " ₸ (месяц {$payment['month_num']})\n";
            $adminMessage .= "⏳ Просрочка: " . $payment['days_overdue'] . " дн.\n";
            $adminMessage .= "📅 Дата платежа: " . $payment['payment_date'] . "\n\n";
        }

        // Номера для отправки администраторам
        $recipients = [
            '77773700772',
            '77777080808',
            '77780021666'
        ];

        // Отправляем сообщения администраторам
        foreach ($recipients as $phone) {
            sendWhatsapp($phone, $adminMessage);
        }
    } else {
        $message = "На {$todayFormatted} просроченных платежей нет.";
        error_log($message);
    }

    return [
        'type' => true,
        'msg' => count($overduePayments) > 0 ? 'Уведомления отправлены' : 'Нет просроченных платежей',
        'count' => count($overduePayments),
        'users_notified' => count($userNotifications),
        'total_amount' => $totalOverdueAmount
    ];
}

// Вызываем функцию и логируем результат
$result = sendOverduePaymentsNotification();
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>