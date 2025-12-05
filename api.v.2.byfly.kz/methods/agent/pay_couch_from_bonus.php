<?php
$userInfoDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['userId'] . "'");
if ($userInfoDB->num_rows > 0) {
    try {
        $userInfo = $userInfoDB->fetch_assoc();
        $all = false;
        $summ = 0;
        $type = true;
        $typeErr = '';
        if ($userInfo['bonus'] >= $_POST['price']) {
            $userInfo['bonus'] = $userInfo['bonus'] - $_POST['price'];
            $summ = $_POST['price'];

            $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $_POST['group_id'] . "'")->fetch_assoc();
            $coachInfo = $db->query("SELECT * FROM coach WHERE id='" . $groupInfo['coach_id'] . "'")->fetch_assoc();

            $userInfo['price_coach'] = $userInfo['price_coach'] - $_POST['price'];
            $userInfo['price_coach_tour'] = $userInfo['price_coach_tour'] - $_POST['price'];
            $userInfo['price_coach_online'] = $userInfo['price_coach_online'] - $_POST['price'];


            $db->query("UPDATE users SET orient='test', user_status='agent', date_validate_agent='" . $groupInfo['date_validation'] . "', date_couch_start='" . $groupInfo['date_start_coaching'] . "', coach_id='" . $groupInfo['coach_id'] . "', grouped='" . $groupInfo['id'] . "', bonus='" . $userInfo['bonus'] . "', price_coach='" . $userInfo['price_coach'] . "', price_coach_tour='" . $userInfo['price_coach_tour'] . "', price_coach_online='" . $userInfo['price_coach_online'] . "' WHERE id='" . $userInfo['id'] . "'");
            $db->query("INSERT INTO user_tranzactions (`id`, `date_create`, `summ`, `type_operations`, `user_id`, `pay_info`) VALUES (NULL, CURRENT_TIMESTAMP, '" . $_POST['price'] . "', '0', '" . $_POST['userId'] . "', 'Полная оплата обучения бонусами!');");

            $db->query("INSERT INTO user_statused (`id`, `code_status`, `date_add`, `user_id`) VALUES (NULL, '4', CURRENT_TIMESTAMP, '" . $userInfo['id'] . "')");

            $all = true;
        } else {
            $summ = $userInfo['bonus'];

            $userInfo['price_coach'] = $userInfo['price_coach'] - $summ;
            $userInfo['price_coach_tour'] = $userInfo['price_coach_tour'] - $summ;
            $userInfo['price_coach_online'] = $userInfo['price_coach_online'] - $summ;
            $userInfo['bonus'] = 0;


            if ($db->query("UPDATE users SET bonus='" . $userInfo['bonus'] . "', price_coach='" . $userInfo['price_coach'] . "', price_coach_tour='" . $userInfo['price_coach_tour'] . "', price_coach_online='" . $userInfo['price_coach_online'] . "' WHERE id='" . $userInfo['id'] . "'")) {
                $db->query("INSERT INTO user_tranzactions (`id`, `date_create`, `summ`, `type_operations`, `user_id`, `pay_info`) VALUES (NULL, CURRENT_TIMESTAMP, '" . $userInfo['bonus'] . "', '0', '" . $_POST['userId'] . "', 'Частичная оплата обучения бонусами!');");
                $all = false;
            } else {
                $type = false;
                $typeErr = '';
            }
        }
        if ($type) {
            echo json_encode(
                array(
                    "type" => $type,
                    "data" => array(
                        "all" => $all,
                        "summ" => $summ,
                    ),
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => $type,
                    "msg" => $typeErr,
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


} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Пользователь не существует...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>