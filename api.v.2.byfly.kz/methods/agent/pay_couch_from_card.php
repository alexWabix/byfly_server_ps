<?php
try {
    if (empty($_POST['userId']) || empty($_POST['groupId']) || empty($_POST['price'])) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Не хватает данных для активации пользователя.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $userInfoDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['userId'] . "'")->fetch_assoc();
    if (!$userInfoDB) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Пользователь не найден.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $_POST['groupId'] . "'")->fetch_assoc();
    if (!$groupInfo) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Группа не найдена.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $coachInfo = $db->query("SELECT * FROM coach WHERE id='" . $groupInfo['coach_id'] . "'")->fetch_assoc();
    if (!$coachInfo) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Тренер не найден.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $price = (float) $_POST['price'];
    $userInfoDB['price_coach'] -= $price;
    $userInfoDB['price_coach_tour'] -= $price;
    $userInfoDB['price_coach_online'] -= $price;

    $updateUser = $db->query("
        UPDATE users 
        SET 
            date_validate_agent='" . $groupInfo['date_validation'] . "',
            date_couch_start='" . $groupInfo['date_start_coaching'] . "',
            orient='test',
            grouped='" . $groupInfo['id'] . "',
            coach_id='" . $groupInfo['coach_id'] . "',
            price_coach='" . $userInfoDB['price_coach'] . "',
            price_coach_tour='" . $userInfoDB['price_coach_tour'] . "',
            price_coach_online='" . $userInfoDB['price_coach_online'] . "'
        WHERE id='" . $_POST['userId'] . "'
    ");

    if (!$updateUser) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Ошибка обновления данных пользователя.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $addStatus = $db->query("
        INSERT INTO user_statused (id, code_status, date_add, user_id) 
        VALUES (NULL, '4', CURRENT_TIMESTAMP, '" . $_POST['userId'] . "')
    ");

    if (!$addStatus) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Ошибка добавления статуса пользователя.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $addTransaction = $db->query("
        INSERT INTO user_tranzactions (id, date_create, summ, type_operations, user_id, pay_info) 
        VALUES (NULL, CURRENT_TIMESTAMP, '" . $price . "', '0', '" . $_POST['userId'] . "', 'Полная оплата обучения KASPI GOLD (" . $price . ")')
    ");

    if (!$addTransaction) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Ошибка сохранения транзакции.',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // Формируем сообщение для WhatsApp
    $message = "🚀 *Новый ученик в вашей группе!* 🚀\n\n" .
        "📋 *ID ученика:* {$_POST['userId']}\n" .
        "💵 *Сумма оплаты:* $price KZT\n" .
        "Оплатил картой!\n" .
        "📞 *Контактный телефон ученика:* " . $_POST['phone'] . "\n\n" .
        "Пожалуйста, свяжитесь с учеником для уточнения деталей.";

    // Отправляем сообщение преподавателю через WhatsApp
    sendWhatsapp(
        $coachInfo['phone'], // Номер телефона преподавателя
        $message
    );

    echo json_encode(
        array(
            "type" => true,
            "data" => array(),
        ),
        JSON_UNESCAPED_UNICODE
    );

} catch (Exception $e) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $e->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>