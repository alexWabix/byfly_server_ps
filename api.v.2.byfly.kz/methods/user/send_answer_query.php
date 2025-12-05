<?php
if (empty($_POST['user_id']) == false) {
    $getUserInfo = getUserInfoFromID($_POST['user_id']);
    if ($getUserInfo != false) {
        $query = $getUserInfo['atestation_query'][$_POST['count_query']]['answers_ru'];
        $selectAnswer = $query[$_POST['select_index']];
        if ($selectAnswer['correct'] != null) {
            $getUserInfo['astestation_bal'] = $getUserInfo['astestation_bal'] + 2;
        }
        $getUserInfo['coun_query'] = $getUserInfo['coun_query'] + 1;

        $getUserInfo['atestation_query'][$_POST['count_query']]['user_selected'] = $_POST['select_index'];



        if ($db->query("UPDATE users SET astestation_bal='" . $getUserInfo['astestation_bal'] . "', coun_query='" . $getUserInfo['coun_query'] . "', atestation_query='" . json_encode($getUserInfo['atestation_query'], JSON_UNESCAPED_UNICODE) . "' WHERE id='" . $_POST['user_id'] . "'")) {
            if ($getUserInfo['coun_query'] >= 50) {
                $db->query("UPDATE users SET end_atestation ='" . date('Y-m-d H:i:s') . "' WHERE id='" . $_POST['user_id'] . "'");

                if ($getUserInfo['astestation_bal'] > 90) {
                    $referBonus = $getUserInfo['refer_registration_bonus'];
                    if ($getUserInfo['astestation_bal'] > 90 && $getUserInfo['astestation_bal'] < 93) {
                        $referBonus = $getUserInfo['refer_registration_bonus'];
                    } else if ($getUserInfo['astestation_bal'] >= 93 && $getUserInfo['astestation_bal'] < 96) {
                        $referBonus = 5000;
                    } else if ($getUserInfo['astestation_bal'] >= 96 && $getUserInfo['astestation_bal'] < 100) {
                        $referBonus = 10000;
                    } else if ($getUserInfo['astestation_bal'] == 100) {
                        $referBonus = 12000;
                    }

                    $db->query("UPDATE users SET orient ='success', refer_registration_bonus='" . $referBonus . "' WHERE id='" . $_POST['user_id'] . "'");
                } else {
                    $currentDate = new DateTime();
                    $currentDate->modify('+7 day');
                    $newDate = $currentDate->format('Y-m-d H:i:s');

                    $db->query("UPDATE users SET orient ='test', date_validate_agent='" . $newDate . "' WHERE id='" . $_POST['user_id'] . "'");
                }
            }

            echo json_encode(
                array(
                    "type" => true,
                    "data" => $getUserInfo,
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => $db->error,
                ),
                JSON_UNESCAPED_UNICODE
            );
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'User not found...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error answer data...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}