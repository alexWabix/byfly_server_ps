<?php
// cron_kaspi_cleanup.php
// Запускать каждый день в 2:00: 0 2 * * * /usr/bin/php /path/to/cron_kaspi_cleanup.php

include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

try {
    // Очищаем старые логи операций (старше 30 дней)
    $db->query("DELETE FROM kaspi_operation_logs WHERE date_created < DATE_SUB(NOW(), INTERVAL 30 DAY)");

    // Очищаем старые фотографии терминалов (старше 7 дней)
    $sql = "SELECT last_photo_url FROM kaspi_terminals 
            WHERE last_photo_url IS NOT NULL 
            AND last_health_check < DATE_SUB(NOW(), INTERVAL 7 DAY)";

    $result = $db->query($sql);
    while ($row = $result->fetch_assoc()) {
        if ($row['last_photo_url']) {
            $filename = basename($row['last_photo_url']);
            $filepath = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/terminal_photos/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    // Обнуляем ссылки на удаленные фото
    $db->query("UPDATE kaspi_terminals SET last_photo_url = NULL 
                WHERE last_health_check < DATE_SUB(NOW(), INTERVAL 7 DAY)");

    // Архивируем старые транзакции (старше 90 дней) - перемещаем в архивную таблицу
    $db->query("INSERT INTO kaspi_transactions_archive 
                SELECT * FROM kaspi_transactions 
                WHERE date_initiated < DATE_SUB(NOW(), INTERVAL 90 DAY)");

    $db->query("DELETE FROM kaspi_transactions 
                WHERE date_initiated < DATE_SUB(NOW(), INTERVAL 90 DAY)");

} catch (Exception $e) {
    $errorMessage = "🚨 *Ошибка в KASPI CLEANUP*\n\n";
    $errorMessage .= "📅 Время: " . date('Y-m-d H:i:s') . "\n";
    $errorMessage .= "❌ Ошибка: " . $e->getMessage();

    adminNotification($errorMessage);
}
?>