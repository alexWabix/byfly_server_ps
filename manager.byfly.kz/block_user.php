<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function pluralizeDays($days)
{
    if ($days % 10 == 1 && $days % 100 != 11) {
        return "$days день";
    } elseif ($days % 10 >= 2 && $days % 10 <= 4 && ($days % 100 < 10 || $days % 100 >= 20)) {
        return "$days дня";
    } else {
        return "$days дней";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id']);
    $days = intval($_POST['days']);
    $reason = $db->real_escape_string(htmlspecialchars($_POST['reason']));

    if ($userId <= 0 || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Некорректные данные.']);
        exit;
    }

    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $userId . "'")->fetch_assoc();

    if (!$userInfo) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден.']);
        exit;
    }

    if ($days > 0) {
        $daysText = pluralizeDays($days);
        $notificationText = "❗ *Вы заблокированы на $daysText* ❗\n\n";
        $notificationText .= "Причина блокировки: $reason\n\n";
        $notificationText .= "⛔ Во время блокировки:\n";
        $notificationText .= "🔹 Агент *не получает пользователей* от рекламы.\n";
        $notificationText .= "🔹 Промокод *не активен*. Все, кто регистрируются по вашей ссылке, получают бонус, но *распределяются под других агентов*.\n";
        $notificationText .= "🔹 Агент *не может продавать туры* с накруткой.\n";
        $notificationText .= "🔹 Агентская комиссия *не начисляется* по всем 5 уровням.\n";
        $notificationText .= "🔹 Доступ к системам автоматизации *закрывается на весь период блокировки*.\n\n";
        $notificationText .= "⚠️ В случае повторного нарушения будет применена блокировка на более длительный период.\n";

        sendWhatsapp($userInfo['phone'], $notificationText);
    } else {
        $notificationText = "❗ *Вы заблокированы навсегда* ❗\n\n";
        $notificationText .= "Причина блокировки: $reason\n\n";
        $notificationText .= "⛔ Во время блокировки:\n";
        $notificationText .= "🔹 Агент *не получает пользователей* от рекламы.\n";
        $notificationText .= "🔹 Промокод *не активен*. Все, кто регистрируются по вашей ссылке, получают бонус, но *распределяются под других агентов*.\n";
        $notificationText .= "🔹 Агент *не может продавать туры* с накруткой.\n";
        $notificationText .= "🔹 Агентская комиссия *не начисляется* по всем 5 уровням.\n";
        $notificationText .= "🔹 Доступ к системам автоматизации *закрывается полностью*.\n\n";
        $notificationText .= "⚠️ В случае дополнительных вопросов свяжитесь с техподдержкой.\n";

        sendWhatsapp($userInfo['phone'], $notificationText);
    }

    $blockUntil = $days > 0 ? date('Y-m-d H:i:s', strtotime("+$days days")) : date('Y-m-d H:i:s', strtotime("+5000000 days"));

    $query = "UPDATE users SET blocked_to_time = '" . $blockUntil . "', block_desc='" . $reason . "' WHERE id = " . $userId;

    if ($db->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось заблокировать пользователя. ' . $db->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса.']);
}
?>