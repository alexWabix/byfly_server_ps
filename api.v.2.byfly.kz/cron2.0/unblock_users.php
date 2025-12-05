<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

try {
    $query = $db->query("
        SELECT id, phone, name, block_desc 
        FROM users 
        WHERE blocked_to_time IS NOT NULL 
        AND blocked_to_time <= NOW()
    ");

    $unblockedUsers = $query->fetch_all(MYSQLI_ASSOC);

    if (count($unblockedUsers) > 0) {
        $db->query("
            UPDATE users 
            SET blocked_to_time = NULL, 
                block_desc = NULL 
            WHERE blocked_to_time IS NOT NULL 
            AND blocked_to_time <= NOW()
        ");

        // 3. Отправляем уведомления
        foreach ($unblockedUsers as $user) {
            $message = "🌟 *{$user['name']}*, ваш аккаунт в ByFly Travel автоматически разблокирован!  🌟\n\n";
            $message .= "Блокировка была снята по истечении срока.\n";
            $message .= "Причина блокировки: " . ($user['block_desc'] ?? 'не указана') . "\n\n";
            $message .= "Теперь вы снова можете:\n";
            $message .= "✅ Просматривать туры\n";
            $message .= "✅ Совершать продажи\n";
            $message .= "✅ Получать доход\n\n";
            $message .= "С уважением,\nКоманда ByFly Travel  ✈️";

            sendWhatsapp($user['phone'], $message);
        }
    }

} catch (Exception $e) {
    sendWhatsapp('77780021666', "Ошибка в скрипте разблокировки: " . $e->getMessage());
}
?>