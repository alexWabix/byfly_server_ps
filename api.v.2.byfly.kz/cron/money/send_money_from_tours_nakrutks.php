<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$toursTodayDB = $db->query("SELECT * FROM order_tours WHERE (status_code='4' OR status_code='3') AND send_money_agent = '0' AND includesPrice > '0'");

while ($tour = $toursTodayDB->fetch_assoc()) {
    $tourId = $tour['id'];
    $price = (int) $tour['includesPrice'];
    $markupPercent = (int) $tour['nakrutka'];
    $markupAmount = ceil(($price / 100) * $markupPercent);

    if ($markupAmount <= 0)
        continue; // Нечего начислять

    // Определяем кому начислять: либо saler_id, либо user_id
    $receiverId = ($tour['saler_id'] > 0) ? $tour['saler_id'] : $tour['user_id'];
    $receiverResult = $db->query("SELECT * FROM users WHERE id = '$receiverId'");
    if ($receiverResult->num_rows == 0)
        continue;
    $receiver = $receiverResult->fetch_assoc();

    // Определяем куда начислить (balance или bonus)
    $isAgent = $receiver['astestation_bal'] > 0;
    $newValue = ($isAgent ? $receiver['balance'] : $receiver['bonus']) + $markupAmount;

    // Обновляем баланс/бонус
    $updateField = $isAgent ? 'balance' : 'bonus';
    $db->query("UPDATE users SET $updateField = '$newValue' WHERE id = '$receiverId'");

    // Обновляем заявку
    $db->query("UPDATE order_tours SET send_money_agent = '$markupAmount' WHERE id = '$tourId'");

    // Готовим сообщение
    $message =
        "🎉 Поздравляем!\n\n" .
        "🧳 Клиент по заявке №$tourId вылетает сегодня.\n" .
        ($isAgent
            ? "💰 Вам начислена прибыль: *$markupAmount KZT* на баланс.\n💳 Текущий баланс: *$newValue KZT*\n"
            : "🎁 Вам начислен бонус: *$markupAmount KZT*.\n💰 Текущий бонусный баланс: *$newValue KZT*\n") .
        "\n✨ Спасибо, что с нами — *ByFly Travel*! ✈️";

    $escapedMessage = $db->real_escape_string($message);
    $escapedPhone = $db->real_escape_string($receiver['phone']);

    // Записываем сообщение в очередь
    $db->query("INSERT INTO send_message_whatsapp 
        (`id`, `message`, `date_create`, `phone`, `is_send`, `category`, `user_id`) 
        VALUES 
        (NULL, '$escapedMessage', CURRENT_TIMESTAMP, '$escapedPhone', '0', 'nakrutka', '$receiverId')");
}
?>