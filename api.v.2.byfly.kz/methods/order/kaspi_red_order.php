<?php
try {
    if ($_POST['type'] == 'tour') {
        if (empty($_POST['order_id']) == false) {
            if ($db->query("UPDATE order_tours SET kaspi_pay_to_number ='" . $_POST['phone'] . "' WHERE id='" . $_POST['order_id'] . "'")) {
                $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $_POST['order_id'] . "'")->fetch_assoc();
                if ($orderInfo['manager_id'] != 0 && $orderInfo['manager_id'] != '0') {
                    $managerInfo = $db->query("SELECT * FROM managers WHERE id='" . $orderInfo['manager_id'] . "'")->fetch_assoc();

                    sendWhatsapp(
                        $managerInfo['phone_whatsapp'],
                        "🚀 *Новая заявка на счет!* 🚀\n\n" .
                        "📋 *Номер заявки:* #" . $orderInfo['id'] . "\n" .
                        "💳 *Тип оплаты:* Kaspi Red (Рассрочка)\n" .
                        "📞 *Контактный телефон для счета:* " . $_POST['phone'] . "\n\n" .
                        "Пожалуйста, свяжитесь с клиентом для уточнения деталей."
                    );
                }
                echo json_encode(
                    array(
                        "type" => true,
                        "data" => array(),
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            } else {
                echo json_encode(
                    array(
                        "type" => false,
                        "msg" => 'Error sql query...',
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            }
        }
    } else if ($_POST['type'] == 'coach') {
        if ($_POST['groupId'] != 0 && $_POST['groupId'] != '0') {
            $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $_POST['groupId'] . "'")->fetch_assoc();
            $coachInfo = $db->query("SELECT * FROM coach WHERE id='" . $groupInfo['coach_id'] . "'")->fetch_assoc();
            sendWhatsapp(
                $coachInfo['phone'],
                "🚀 *Новая заявка на оплату обучение в кредит!* 🚀\n\n" .
                $groupInfo['name_grouped_ru'] . "\n" .
                "💳 *Тип оплаты:* Kaspi Red (Рассрочка)\n" .
                "📞 *Контактный телефон для счета:* " . $_POST['phone'] . "\n\n" .
                "Пожалуйста, свяжитесь с клиентом для уточнения деталей."
            );
            echo json_encode(
                array(
                    "type" => true,
                    "data" => array(),
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Not groupId variable....',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
    } else if ($_POST['type'] == 'copilka') {
        $listBuhDB = $db->query("SELECT * FROM money_user");
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'")->fetch_assoc();

        while ($listBuh = $listBuhDB->fetch_assoc()) {
            sendWhatsapp(
                $listBuh['phone'],
                "🚀 *Новая заявка на оплату накопительной ячейки в кредит!* 🚀\n\n" .
                "💳 *Тип оплаты:* Kaspi Red (Рассрочка)\n" .
                "📞 *Контактный телефон для счета:* " . $_POST['phone'] . "\n\n" .
                "Пожалуйста, свяжитесь с клиентом для уточнения деталей." .
                "Телефон пользователя: +" . $userInfo['phone']
            );
        }

        echo json_encode(
            array(
                "type" => true,
                "data" => $userInfo,
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Not type variable....',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }

} catch (\Throwable $th) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}