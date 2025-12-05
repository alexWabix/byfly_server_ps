<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// 1. Получаем список агентов для начисления кэшбэка
$listAgentDB = $db->query("SELECT * FROM users WHERE priced_coach > '0' AND grouped > '0' AND price_oute_in_couch_price_from_cashback = '0'");

// 2. Обрабатываем каждого агента
while ($listAgent = $listAgentDB->fetch_assoc()) {
    // Получаем информацию о группе обучения
    $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $listAgent['grouped'] . "'")->fetch_assoc();

    // Рассчитываем сумму кэшбэка
    $summCashBack = ceil(($listAgent['priced_coach'] / 100) * $groupInfo['cash_back']);

    // Если есть пригласивший пользователь
    if ($listAgent['parent_user'] > 0) {
        $parentInfo = $db->query("SELECT id, name, famale, surname, phone, astestation_bal, balance, bonus FROM users WHERE id='" . $listAgent['parent_user'] . "'")->fetch_assoc();

        if ($parentInfo) {
            // Определяем тип начисления (баланс или бонусы)
            $isAgent = $parentInfo['astestation_bal'] > 0;
            $field = $isAgent ? 'balance' : 'bonus';
            $currentValue = $isAgent ? $parentInfo['balance'] : $parentInfo['bonus'];
            $newValue = $currentValue + $summCashBack;

            // 3. Начисляем кэшбэк
            $db->query("UPDATE users SET $field = '$newValue' WHERE id = '{$parentInfo['id']}'");

            // 4. Формируем сообщение
            $message = "🎉 Вам начислен кэшбэк за привлечение агента на обучение!\n\n"
                . "👤 Агент: " . $listAgent['name'] . ' ' . $listAgent['famale'] . ' ' . $listAgent['surname'] . "\n"
                . "🏫 Группа: " . $groupInfo['name_grouped_ru'] . "\n"
                . "💰 Сумма кэшбэка: " . number_format($summCashBack, 0, '.', ' ') . " KZT\n"
                . ($isAgent
                    ? "💳 Текущий баланс: " . number_format($newValue, 0, '.', ' ') . " KZT\n"
                    : "🎁 Текущий бонусный баланс: " . number_format($newValue, 0, '.', ' ') . " KZT\n")
                . "\n✨ Спасибо за привлечение новых агентов!";

            // 5. Записываем сообщение в очередь на отправку
            $escapedMessage = $db->real_escape_string($message);
            $escapedPhone = $db->real_escape_string($parentInfo['phone']);

            $db->query("INSERT INTO send_message_whatsapp 
                (`message`, `date_create`, `phone`, `is_send`, `category`, `user_id`) 
                VALUES 
                ('$escapedMessage', CURRENT_TIMESTAMP, '$escapedPhone', '0', 'cashback', '{$parentInfo['id']}')");
        }
    }

    // 7. Помечаем запись как обработанную
    $db->query("UPDATE users SET price_oute_in_couch_price_from_cashback = '1' WHERE id = '{$listAgent['id']}'");
}

// 8. Закрываем соединение (если нужно)
$db->close();
?>