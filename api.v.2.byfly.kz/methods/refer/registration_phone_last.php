<?php



if (empty($_POST['phoneNumber']) == false && empty($_POST['agentId']) == false) {
    $search_phone_db = $db->query("SELECT * FROM referal WHERE user_phone='" . $_POST['phoneNumber'] . "'");
    $search_phone_users = $db->query("SELECT * FROM users WHERE phone='" . $_POST['phoneNumber'] . "'");

    if ($search_phone_db->num_rows == 0 && $search_phone_users->num_rows == 0) {
        $agentInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['agentId'] . "'");
        if ($agentInfo->num_rows > 0) {
            $agentInfo = $agentInfo->fetch_assoc();
            if ($agentInfo['blocked_to_time'] == null) {
                $db->query("INSERT INTO referal (`id`, `agent_id`, `user_phone`, `referal_balance`, `date_create`, `date_activation`, `isActived`) VALUES (NULL, '" . $agentInfo['id'] . "', '" . $_POST['phoneNumber'] . "', '" . $agentInfo['refer_registration_bonus'] . "', CURRENT_TIMESTAMP, NULL, '0');");

            } else {
                $db->query("INSERT INTO referal (`id`, `agent_id`, `user_phone`, `referal_balance`, `date_create`, `date_activation`, `isActived`) VALUES (NULL, '0', '" . $_POST['phoneNumber'] . "', '" . $agentInfo['refer_registration_bonus'] . "', CURRENT_TIMESTAMP, NULL, '0');");
            }
            $resp = array(
                "type" => true,
            );
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $resp = array(
                "type" => false,
                "msg" => getTextTranslate(68),
            );
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        }

    } else {
        $resp = array(
            "type" => false,
            "msg" => getTextTranslate(69),
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    $resp = array(
        "type" => false,
        "msg" => getTextTranslate(70),
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
?>