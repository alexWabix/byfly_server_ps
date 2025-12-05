<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$mediaDB = $db->query("SELECT * FROM order_media WHERE is_checked='0' AND bonus > '0'");
while ($media = $mediaDB->fetch_assoc()) {
    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $media['user_id'] . "'");
    if ($userInfo->num_rows > 0) {
        $userInfo = $userInfo->fetch_assoc();
        $userInfo['bonus'] = $userInfo['bonus'] + $media['bonus'];
        if ($db->query("UPDATE users SET bonus='" . $userInfo['bonus'] . "' WHERE id='" . $userInfo['id'] . "'")) {
            if ($db->query("UPDATE order_media SET is_checked='1' WHERE id='" . $media['id'] . "'")) {

                $message =
                    "🎉 Спасибо за ваш отзыв!\n\n" .
                    "💰 Вам начислен бонус: *" . $media['bonus'] . " KZT*\n" .
                    "💳 Ваш текущий бонусный баланс: *" . $userInfo['bonus'] . " KZT*\n\n" .
                    "✨ Мы ценим, что вы делитесь своими впечатлениями с нами!\n" .
                    "🌍 Пусть каждое ваше путешествие будет незабываемым,\nвместе с *ByFly Travel*! ✈️";

                $db->query("INSERT INTO send_message_whatsapp 
                (`id`, `message`, `date_create`, `phone`, `is_send`, `category`, `user_id`) 
                VALUES 
                (NULL, '" . $db->real_escape_string($message) . "', CURRENT_TIMESTAMP, '" . $userInfo['phone'] . "', '0', 'bonusmedia', '" . $userInfo['id'] . "');");
            }
        }
    }
}
?>