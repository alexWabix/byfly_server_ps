<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$tableName = 'oskemen';

$listUsersAstanaDB = $db->query("SELECT * FROM registrations_" . $tableName . " WHERE invited_by > 0");

$invitedStats = [];

while ($reg = $listUsersAstanaDB->fetch_assoc()) {
    $invitedId = $reg['invited_by'];
    $invitedUserDB = $db->query("SELECT * FROM users WHERE id='$invitedId'");
    $userDB = $db->query("SELECT * FROM users WHERE id='" . $reg['user_id'] . "'");

    if (!$invitedUserDB || $invitedUserDB->num_rows == 0)
        continue;

    $invitedUser = $invitedUserDB->fetch_assoc();
    $userInfo = ($userDB && $userDB->num_rows > 0) ? $userDB->fetch_assoc() : null;

    if (!isset($invitedStats[$invitedId])) {
        $invitedStats[$invitedId] = [
            'name' => $invitedUser['name'] . ' ' . $invitedUser['surname'] . ' ' . $invitedUser['famale'],
            'phone' => $invitedUser['phone'],
            'user_id' => $invitedUser['id'],
            'came_with_bonus' => [],
            'came_no_bonus' => [],
            'not_came' => []
        ];
    }

    $fullName = $reg['name'];
    if ($reg['came'] == '1') {
        if ($userInfo == null || $userInfo['astestation_bal'] == 0) {
            $invitedStats[$invitedId]['came_with_bonus'][] = $fullName;
        } else {
            $invitedStats[$invitedId]['came_no_bonus'][] = $fullName;
        }
    } else {
        $invitedStats[$invitedId]['not_came'][] = $fullName;
    }
}

// Формируем и вставляем сообщение
foreach ($invitedStats as $invitedId => $data) {
    $bonusCount = count($data['came_with_bonus']);
    if ($bonusCount === 0)
        continue;

    $bonusAmount = $bonusCount * 5000;

    $msg = "🎉 Поздравляем, " . $data['name'] . "!\n\n";
    $msg .= "Вы пригласили гостей на мероприятие ByFly Travel в Усть-Каменогорске, и некоторые из них действительно пришли!\n\n";

    $msg .= "💸 Вам начислен бонус за:\n";
    foreach ($data['came_with_bonus'] as $name) {
        $msg .= "  — $name\n";
    }

    if (!empty($data['came_no_bonus'])) {
        $msg .= "\n👥 Пришли, но не начислен бонус (агенты / аттестованные):\n";
        foreach ($data['came_no_bonus'] as $name) {
            $msg .= "  — $name\n";
        }
    }

    if (!empty($data['not_came'])) {
        $msg .= "\n🚫 Не пришли на мероприятие:\n";
        foreach ($data['not_came'] as $name) {
            $msg .= "  — $name\n";
        }
    }

    $msg .= "\n💳 Ваш бонус составляет: *{$bonusAmount} KZT*\n\n";
    $msg .= "🔥 Благодарим вас за активность! Совсем скоро вы сможете получить своё путешествие мечты 🌍✈️, если продолжите приглашать людей на мероприятия от *ByFly Travel*.\n\n";
    $msg .= "С любовью, команда ByFly 💙";

    // Запись в БД
    $db->query("INSERT INTO send_message_whatsapp 
        (`id`, `message`, `date_create`, `phone`, `is_send`, `category`, `user_id`) 
        VALUES 
        (NULL, '" . $db->real_escape_string($msg) . "', CURRENT_TIMESTAMP, '" . $data['phone'] . "', '0', 'bonusevent', '" . $data['user_id'] . "');");

    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $data['user_id'] . "'")->fetch_assoc();
    $userInfo['bonus'] = $userInfo['bonus'] + $bonusAmount;

    $db->query("UPDATE users SET bonus='" . $userInfo['bonus'] . "' WHERE id='" . $data['user_id'] . "'");
}
?>