<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/js_bot_wa/api/get_info.php');

$monitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();

try {
    if ($monitor['task_sended'] == 0) {
        $db->query("UPDATE monitor SET task_sended='1' WHERE id='1'");
        // Текущее время
        $currentDateTime = new DateTime('now');

        // Получаем задачи, срок которых истекает через 1 час и уведомление еще не отправлено
        $tasksQuery = $db->query("
            SELECT * 
            FROM task_user 
            WHERE notify_sended = '0' 
            AND TIMESTAMPDIFF(MINUTE, NOW(), date_off) <= 60 
            AND TIMESTAMPDIFF(MINUTE, NOW(), date_off) > 0
        ");

        while ($task = $tasksQuery->fetch_assoc()) {
            $phoneFrom = $task['phone_from'];
            $phoneTo = $task['phone_to'];
            $taskDesc = $task['text'];
            $dateOff = new DateTime($task['date_off']);

            // Формируем дату в удобном формате
            $formattedDateOff = $dateOff->format('d F Y года в H:i');
            $months = [
                'January' => 'января',
                'February' => 'февраля',
                'March' => 'марта',
                'April' => 'апреля',
                'May' => 'мая',
                'June' => 'июня',
                'July' => 'июля',
                'August' => 'августа',
                'September' => 'сентября',
                'October' => 'октября',
                'November' => 'ноября',
                'December' => 'декабря'
            ];
            $formattedDateOff = str_replace(array_keys($months), array_values($months), $formattedDateOff);

            // Получаем данные о пользователях
            $userFrom = $db->query("SELECT * FROM users WHERE phone = '$phoneFrom'")->fetch_assoc();
            $userTo = $db->query("SELECT * FROM users WHERE phone = '$phoneTo'")->fetch_assoc();

            $fromName = $userFrom ? trim($userFrom['famale'] . ' ' . $userFrom['name'] . ' ' . $userFrom['surname']) : "Абонент с номером $phoneFrom";
            $toName = $userTo ? trim($userTo['famale'] . ' ' . $userTo['name'] . ' ' . $userTo['surname']) : "Абонент с номером $phoneTo";

            // Уведомление для получателя задачи
            if ($phoneFrom !== $phoneTo) {
                $messageTo = "🔔 *Напоминание о задаче!*\n\n" .
                    "Вам поставлена задача от $fromName:\n" .
                    "📋 *Описание*: $taskDesc\n" .
                    "⏰ *Срок выполнения*: до $formattedDateOff.\n\n" .
                    "Пожалуйста, выполните задачу вовремя! 😊";
                sendWhatsapp($phoneTo, $messageTo);

                // Уведомление для постановщика задачи
                $messageFrom = "🔔 *Напоминание!*\n\n" .
                    "Вы ранее поставили задачу пользователю $toName:\n" .
                    "📋 *Описание*: $taskDesc\n" .
                    "⏰ *Срок выполнения*: до $formattedDateOff.\n\n" .
                    "Не забудьте проконтролировать выполнение задачи! 👍";
                sendWhatsapp($phoneFrom, $messageFrom);
            } else {
                // Уведомление только для себя, если задача поставлена самому себе
                $messageTo = "🔔 *Напоминание о задаче!*\n\n" .
                    "Вы поставили задачу самому себе:\n" .
                    "📋 *Описание*: $taskDesc\n" .
                    "⏰ *Срок выполнения*: до $formattedDateOff.\n\n" .
                    "Пожалуйста, выполните задачу вовремя! 😊";
                sendWhatsapp($phoneTo, $messageTo);
            }

            // Отмечаем задачу как уведомленную
            $db->query("UPDATE task_user SET notify_sended = '1' WHERE id = " . $task['id']);
            sleep(2);
        }
        $db->query("UPDATE monitor SET task_sended='0' WHERE id='1'");
    }
} catch (\Throwable $th) {
    $db->query("UPDATE monitor SET task_sended='0' WHERE id='1'");
}


?>