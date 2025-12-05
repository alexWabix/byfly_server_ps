<?php
// Подключаем конфигурацию
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

try {
    // Получаем все переводы со статусом 'approved', где прошло 3 дня с момента подтверждения
    $query = "
        SELECT ut.*, 
               u.name as user_name, 
               u.famale as user_famale, 
               u.phone as user_phone,
               old_parent.name as old_parent_name, 
               old_parent.famale as old_parent_famale,
               old_parent.phone as old_parent_phone,
               new_parent.name as new_parent_name, 
               new_parent.famale as new_parent_famale,
               new_parent.phone as new_parent_phone
        FROM user_transfers ut
        LEFT JOIN users u ON ut.user_id = u.id
        LEFT JOIN users old_parent ON ut.old_parent_id = old_parent.id
        LEFT JOIN users new_parent ON ut.new_parent_id = new_parent.id
        WHERE ut.status = 'approved' 
        AND ut.approve_date IS NOT NULL 
        AND DATEDIFF(NOW(), ut.approve_date) >= 3
    ";

    $result = $db->query($query);

    if ($result && $result->num_rows > 0) {
        $processedCount = 0;

        while ($transfer = $result->fetch_assoc()) {
            try {
                // Начинаем транзакцию
                $db->begin_transaction();

                // Обновляем parent_user у пользователя
                $updateUserQuery = "
                    UPDATE users 
                    SET parent_user = {$transfer['new_parent_id']} 
                    WHERE id = {$transfer['user_id']}
                ";

                if (!$db->query($updateUserQuery)) {
                    throw new Exception("Ошибка обновления куратора пользователя: " . $db->error);
                }

                // Обновляем статус перевода на 'completed' и устанавливаем дату завершения
                $updateTransferQuery = "
                    UPDATE user_transfers 
                    SET status = 'completed', 
                        complete_date = NOW() 
                    WHERE id = {$transfer['id']}
                ";

                if (!$db->query($updateTransferQuery)) {
                    throw new Exception("Ошибка обновления статуса перевода: " . $db->error);
                }

                // Подтверждаем транзакцию
                $db->commit();

                // Отправляем уведомления участникам
                $userName = trim($transfer['user_name'] . ' ' . $transfer['user_famale']);
                $oldParentName = trim($transfer['old_parent_name'] . ' ' . $transfer['old_parent_famale']);
                $newParentName = trim($transfer['new_parent_name'] . ' ' . $transfer['new_parent_famale']);

                // Уведомление пользователю
                $userMessage = "✅ Смена куратора завершена!\n\n" .
                    "👤 Ваш новый куратор: $newParentName\n" .
                    "📞 Для связи обращайтесь к новому куратору.\n\n" .
                    "🔄 Переход выполнен автоматически через 3 дня после подтверждения.\n\n" .
                    "💼 Желаем успехов в работе с новой командой!";

                sendWhatsapp($transfer['user_phone'], $userMessage);

                // Уведомление старому куратору
                if (!empty($transfer['old_parent_phone'])) {
                    $oldParentMessage = "📤 Участник покинул вашу команду\n\n" .
                        "👤 Участник: $userName\n" .
                        "📱 Телефон: {$transfer['user_phone']}\n" .
                        "➡️ Новый куратор: $newParentName\n\n" .
                        "🔄 Перевод выполнен автоматически через 3 дня после подтверждения.\n\n" .
                        "💪 Продолжайте развивать свою команду!";

                    sendWhatsapp($transfer['old_parent_phone'], $oldParentMessage);
                }

                // Уведомление новому куратору
                if (!empty($transfer['new_parent_phone'])) {
                    $newParentMessage = "🎉 Новый участник присоединился к вашей команде!\n\n" .
                        "👤 Участник: $userName\n" .
                        "📱 Телефон: {$transfer['user_phone']}\n" .
                        "⬅️ Предыдущий куратор: $oldParentName\n\n" .
                        "🔄 Перевод выполнен автоматически через 3 дня после вашего подтверждения.\n\n" .
                        "🤝 Добро пожаловать в команду! Свяжитесь с новым участником для знакомства.";

                    sendWhatsapp($transfer['new_parent_phone'], $newParentMessage);
                }

                $processedCount++;

            } catch (Exception $e) {
                // Откатываем транзакцию в случае ошибки
                $db->rollback();

                // Уведомляем админа об ошибке
                $errorMessage = "❌ ОШИБКА в обработке перевода пользователя\n\n" .
                    "🆔 ID перевода: {$transfer['id']}\n" .
                    "👤 Пользователь: " . trim($transfer['user_name'] . ' ' . $transfer['user_famale']) . "\n" .
                    "📱 Телефон: {$transfer['user_phone']}\n" .
                    "⚠️ Ошибка: " . $e->getMessage() . "\n\n" .
                    "🔧 Требуется ручная обработка!";

                sendWhatsapp('77780021666', $errorMessage);
            }
        }
    }

} catch (Exception $e) {
    // Уведомляем админа о критической ошибке
    $criticalErrorMessage = "🚨 КРИТИЧЕСКАЯ ОШИБКА в cron задаче переводов\n\n" .
        "⚠️ Ошибка: " . $e->getMessage() . "\n" .
        "📁 Файл: " . $e->getFile() . "\n" .
        "📍 Строка: " . $e->getLine() . "\n" .
        "⏰ Время: " . date('Y-m-d H:i:s') . "\n\n" .
        "🔧 Требуется немедленное вмешательство!";

    sendWhatsapp('77780021666', $criticalErrorMessage);
}

// Закрываем соединение с базой данных
if (isset($db)) {
    $db->close();
}
?>