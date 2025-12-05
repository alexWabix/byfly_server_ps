<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Группа админов для уведомлений
$admin_group_phone = "77071234567-1234567890"; // Пример ID группы WhatsApp

// Функция отправки сообщения в WhatsApp
function send_whatsapp($phone, $message)
{
    // Здесь реализация отправки через ваше API WhatsApp
    // Например:
    $url = "https://api.whatsapp.com/send?phone=$phone&text=" . urlencode($message);
    file_get_contents($url);
    return true;
}

// 1. Уведомляем участников за 3 дня до платежа
$three_days_later = date('Y-m-d', strtotime('+3 days'));
$query = $db->query("
    SELECT c.*, u.name, u.famale, u.phone 
    FROM `copilka_ceils` c
    JOIN `users` u ON c.user_id = u.id
    WHERE c.date_dosrok_close IS NULL
");

$total_cells = 0;
$due_today = 0;
$overdue_3days = 0;
$messages_to_send = [];
$overdue_list = [];
$due_today_list = [];

while ($row = $query->fetch_assoc()) {
    $total_cells++;
    $create_date = new DateTime($row['date_create']);
    $payment_day = $create_date->format('d');
    $current_day = date('d');

    // Проверяем платежи за сегодня
    if ($payment_day == $current_day) {
        $due_today++;
        $due_today_list[] = [
            'name' => $row['famale'] . ' ' . $row['name'],
            'phone' => $row['phone']
        ];

        // Проверяем оплачен ли текущий месяц
        $month_number = (date('Y') - $create_date->format('Y')) * 12 + date('m') - $create_date->format('m') + 1;
        $month_key = 'month_' . $month_number . '_money';
        $paid = (float) $row[$month_key] >= 50000;

        if (!$paid) {
            $message = "🔔 *Напоминание о платеже* 🔔\n\n"
                . "Здравствуйте, {$row['name']}! 👋\n\n"
                . "⏰ *Сегодня последний день* для внесения платежа по вашей накопительной ячейке!\n\n"
                . "💰 *Сумма к оплате:* 50 000 ₸\n"
                . "📅 *Срок оплаты:* до 23:59 сегодняшнего дня\n\n"
                . "⚠️ Если платеж не будет внесен сегодня, ячейка будет автоматически закрыта досрочно.\n\n"
                . "С уважением,\n"
                . "Команда ByFly Travel ✈️";

            send_whatsapp($row['phone'], $message);
            $messages_to_send[] = "Отправлено уведомление {$row['famale']} {$row['name']}";
        }
    }

    // Проверяем просрочки более 3 дней
    $last_payment_date = new DateTime($row['date_create']);
    $last_payment_date->modify('+' . ($month_number - 1) . ' months');

    if ($last_payment_date->format('Y-m-d') < date('Y-m-d', strtotime('-3 days'))) {
        $overdue_3days++;
        $overdue_list[] = [
            'name' => $row['famale'] . ' ' . $row['name'],
            'phone' => $row['phone'],
            'days_overdue' => (int) ((time() - strtotime($last_payment_date->format('Y-m-d'))) / (60 * 60 * 24))
        ];

        // Уведомляем о скором удалении
        $message = "⚠️ *Важная информация* ⚠️\n\n"
            . "Здравствуйте, {$row['name']}! 👋\n\n"
            . "Ваша накопительная ячейка будет закрыта досрочно через 3 дня из-за просрочки платежа.\n\n"
            . "💰 *Сумма задолженности:* 50 000 ₸\n"
            . "📅 *Дата последнего платежа:* " . $last_payment_date->format('d.m.Y') . "\n\n"
            . "Если вы внесете платеж в течение 3 дней, ячейка останется активной.\n\n"
            . "С уважением,\n"
            . "Команда ByFly Travel ✈️";

        send_whatsapp($row['phone'], $message);
        $messages_to_send[] = "Отправлено предупреждение о закрытии {$row['famale']} {$row['name']}";
    }

    // Уведомление за 3 дня до платежа
    if ($payment_day == date('d', strtotime('+3 days'))) {
        $message = "🔔 *Напоминание о платеже* 🔔\n\n"
            . "Здравствуйте, {$row['name']}! 👋\n\n"
            . "Через 3 дня необходимо внести очередной платеж по вашей накопительной ячейке.\n\n"
            . "💰 *Сумма к оплате:* 50 000 ₸\n"
            . "📅 *Срок оплаты:* до " . date('d.m.Y', strtotime('+3 days')) . "\n\n"
            . "С уважением,\n"
            . "Команда ByFly Travel ✈️";

        send_whatsapp($row['phone'], $message);
        $messages_to_send[] = "Отправлено напоминание за 3 дня {$row['famale']} {$row['name']}";
    }
}

// 2. Формируем отчет для админов
$admin_message = "📊 *Ежедневный отчет по накопительным ячейкам* 📊\n\n"
    . "📅 *Дата:* " . date('d.m.Y') . "\n\n"
    . "🔹 *Всего активных ячеек:* $total_cells\n"
    . "🔹 *Должны внести платеж сегодня:* $due_today\n"
    . "🔹 *Просрочили более 3 дней:* $overdue_3days\n\n";

// Список должников на сегодня
if (count($due_today_list) > 0) {
    $admin_message .= "📌 *Должны внести сегодня:*\n";
    foreach ($due_today_list as $client) {
        $admin_message .= "• {$client['name']} - {$client['phone']}\n";
    }
    $admin_message .= "\n";
}

// Список просрочивших более 3 дней
if (count($overdue_list) > 0) {
    $admin_message .= "❗ *Просрочили более 3 дней:*\n";
    foreach ($overdue_list as $client) {
        $admin_message .= "• {$client['name']} ({$client['days_overdue']} дн.) - {$client['phone']}\n";
    }
}

// 3. Отправляем отчет админам
send_whatsapp($admin_group_phone, $admin_message);

// Логируем выполнение
file_put_contents(
    '/var/log/byfly/copilka_notifications.log',
    "[" . date('Y-m-d H:i:s') . "] Отправлено уведомлений: " . count($messages_to_send) . "\n" .
    "Админам отправлен отчет\n\n",
    FILE_APPEND
);

echo "Скрипт успешно выполнен. Отправлено " . count($messages_to_send) . " уведомлений.";
?>