<?php
$agent = null;
$referInfo = null;

if (!empty($_POST['phone'])) {
    // Очистка номера телефона от лишних символов
    $_POST['phone'] = preg_replace('/\D/', '', $_POST['phone']);

    // Проверка промокода, если он указан
    if (!empty($_POST['promocode'])) {
        $searchAgentInfoDB = $db->query("SELECT * FROM users WHERE promo_code='" . $db->real_escape_string($_POST['promocode']) . "'");
        if ($searchAgentInfoDB->num_rows > 0) {
            $agent = $searchAgentInfoDB->fetch_assoc();
        }
    }

    // Если агент по промокоду не найден, проверяем ID агента
    if ($agent === null && !empty($_POST['myAgentCash'])) {
        $searchAgentInfoDB = $db->query("SELECT * FROM users WHERE id='" . $db->real_escape_string($_POST['myAgentCash']) . "'");
        if ($searchAgentInfoDB->num_rows > 0) {
            $agent = $searchAgentInfoDB->fetch_assoc();
        }
    }

    // Проверяем реферала по номеру телефона
    $searchReferDB = $db->query("SELECT * FROM referal WHERE user_phone='" . $db->real_escape_string($_POST['phone']) . "'");
    if ($searchReferDB->num_rows > 0) {
        $referInfo = $searchReferDB->fetch_assoc();
        if ($agent === null) {
            $searchAgentInfoDB = $db->query("SELECT * FROM users WHERE id='" . $referInfo['agent_id'] . "'");
            if ($searchAgentInfoDB->num_rows > 0) {
                $agent = $searchAgentInfoDB->fetch_assoc();
            }
        }
    }

    // Проверяем пользователя по номеру телефона
    $userInfo = null;
    $search_user = $db->query("SELECT * FROM users WHERE phone='" . $db->real_escape_string($_POST['phone']) . "'");
    if ($search_user->num_rows > 0) {
        $userInfo = $search_user->fetch_assoc();
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "isReg" => true,
                    "agent" => $agent,
                    "userInfo" => $userInfo,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else {
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "isReg" => false,
                    "referInfo" => $referInfo,
                    "agent" => $agent,
                    "userInfo" => $userInfo,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty phone number...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>