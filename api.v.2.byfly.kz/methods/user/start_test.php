<?php

if (empty($_POST['user_id']) == false) {
    $checkStatus = $db->query("SELECT * FROM user_statused WHERE user_id='" . $_POST['user_id'] . "' AND code_status='4'");
    if ($checkStatus->num_rows > 0) {


        $userInfo = getUserInfoFromID($_POST['user_id']);
        if ($userInfo['start_atestation'] == null) {
            $querys = array();
            $queriesRandomDb = $db->query("SELECT * FROM atestation ORDER BY RAND() LIMIT 50");
            while ($queriesRandom = $queriesRandomDb->fetch_assoc()) {
                $queriesRandom['user_select_answer'] = 0;
                $queriesRandom['answers_ru'] = json_decode($queriesRandom['answers_ru'], true);
                $queriesRandom['answers_kk'] = json_decode($queriesRandom['answers_ru'], true);
                $queriesRandom['answers_en'] = json_decode($queriesRandom['answers_ru'], true);
                array_push($querys, $queriesRandom);
            }
            $db->query("UPDATE users SET start_atestation='" . date('Y-m-d H:i:s') . "', end_atestation=NULL, astestation_bal='0', coun_query='0', atestation_query = '" . json_encode($querys, JSON_UNESCAPED_UNICODE) . "' WHERE id='" . $_POST['user_id'] . "'");
            $userInfo['atestation_query'] = json_encode($querys);
            $userInfo['start_atestation'] = date('Y-m-d H:i:s');
        }

        $userInfo = getUserInfoFromID($_POST['user_id']);

        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "user_info" => $userInfo,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Вы не являетесь агентом компании!',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }

} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Not user id...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
