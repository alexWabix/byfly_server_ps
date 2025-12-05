<?php
if ($_POST['type'] == 'code') {
    if (!empty($_POST['user_id']) && !empty($_POST['event_id'])) {
        $userId = intval($_POST['user_id']);
        $eventId = intval($_POST['event_id']);
        $iId = intval($_POST['iId']);

        $userInfoDB = $db->query("SELECT * FROM users WHERE id='$userId'");
        if ($userInfoDB && $userInfoDB->num_rows > 0) {
            $userInfo = $userInfoDB->fetch_assoc();
            $comanderInfoDB = $db->query("SELECT * FROM users WHERE id='$iId'");
            $comanderInfo = $comanderInfoDB ? $comanderInfoDB->fetch_assoc() : null;

            if ($comanderInfo && $userInfo['is_present_comands'] == 0 && $userInfo['present_comands_id'] == 0 && $userInfo['is_admin'] == 0) {
                $code = rand(100000, 999999);
                $codeString = mb_substr($code, 0, 3, 'utf-8') . ' - ' . mb_substr($code, 3, 3, 'utf-8');

                $message = "Представитель компании " . $comanderInfo['famale'] . " " . $comanderInfo['name'] . " приглашает вас на презентацию!\n\n"
                    . "Если вы готовы выслушать нашего сотрудника, пожалуйста, сообщите ему данный код: $codeString\n\n"
                    . "Желаем вам всего доброго и рассчитываем на долгое и плодотворное сотрудничество!";

                sendWhatsapp($userInfo['phone'], $message);

                echo json_encode([
                    "type" => true,
                    "data" => $code,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    "type" => false,
                    "msg" => "Пользователь уже является сотрудником или членом компании ByFly Travel."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode([
                "type" => false,
                "msg" => "Пользователь не найден!"
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            "type" => false,
            "msg" => "Пожалуйста, заполните все необходимые поля!"
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    if (!empty($_POST['user_id']) && !empty($_POST['event_id'])) {
        $userId = intval($_POST['user_id']);
        $eventId = intval($_POST['event_id']);
        $iId = intval($_POST['iId']);

        $userInfoDB = $db->query("SELECT * FROM users WHERE id='$userId'");
        if ($userInfoDB && $userInfoDB->num_rows > 0) {
            $userInfo = $userInfoDB->fetch_assoc();
            $comanderInfoDB = $db->query("SELECT * FROM users WHERE id='$iId'");
            $comanderInfo = $comanderInfoDB ? $comanderInfoDB->fetch_assoc() : null;

            if ($comanderInfo && $userInfo['is_present_comands'] == 0 && $userInfo['present_comands_id'] == 0 && $userInfo['is_admin'] == 0) {
                if ($db->query("INSERT INTO present_event_users (`id`, `event_id`, `user_id`, `date_create`) VALUES (NULL, '" . $eventId . "', '" . $userId . "', CURRENT_TIMESTAMP);")) {
                    $eventInfo = $db->query("SELECT * FROM present_event WHERE id='" . $eventId . "'")->fetch_assoc();
                    $eventInfo['count_users'] = $eventInfo['count_users'] + 1;

                    $db->query("UPDATE present_event SET count_users='" . $eventInfo['count_users'] . "' WHERE id='" . $eventId . "'");

                    echo json_encode([
                        "type" => true,
                        "data" => $userInfo,
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        "type" => false,
                        "msg" => $db->error,
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode([
                    "type" => false,
                    "msg" => "Пользователь уже является сотрудником или членом компании ByFly Travel."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode([
                "type" => false,
                "msg" => "Пользователь не найден!"
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            "type" => false,
            "msg" => "Пожалуйста, заполните все необходимые поля!"
        ], JSON_UNESCAPED_UNICODE);
    }
}

?>