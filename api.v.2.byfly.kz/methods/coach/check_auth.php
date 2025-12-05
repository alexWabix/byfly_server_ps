<?php
if (!empty($_POST['login']) && !empty($_POST['password'])) {
    $_POST['login'] = preg_replace('/\D/', '', $_POST['login']);
    $searchCoach = $db->query("SELECT * FROM coach WHERE phone = '" . $_POST['login'] . "'");

    if ($searchCoach->num_rows > 0) {
        $searchCoach = $searchCoach->fetch_assoc();
        $searchCoach['upcoming_groups'] = [];
        $searchCoach['completed_groups'] = [];

        $groupQuery = $searchCoach['root'] == 1 || $searchCoach['root'] == '1'
            ? "SELECT * FROM grouped_coach"
            : "SELECT * FROM grouped_coach WHERE coach_id_1='" . $searchCoach['id'] . "' OR coach_id_2='" . $searchCoach['id'] . "' OR coach_id_3='" . $searchCoach['id'] . "' OR coach_id_4='" . $searchCoach['id'] . "' OR coach_id_5='" . $searchCoach['id'] . "' OR coach_id_6='" . $searchCoach['id'] . "'";
        $listGroupCouchDb = $db->query($groupQuery);

        $now = date('Y-m-d');

        while ($listGroupCouch = $listGroupCouchDb->fetch_assoc()) {
            $countUsers = $db->query("SELECT COUNT(id) as ct FROM users WHERE grouped='" . $listGroupCouch['id'] . "'")->fetch_assoc()['ct'];
            $listGroupCouch['countUsers'] = $countUsers;
            $listGroupCouch['coach_info_1'] = $db->query("SELECT * FROM coach WHERE id='" . $listGroupCouch['coach_id_1'] . "'")->fetch_assoc();
            $listGroupCouch['coach_info_2'] = $db->query("SELECT * FROM coach WHERE id='" . $listGroupCouch['coach_id_2'] . "'")->fetch_assoc();
            $listGroupCouch['coach_info_3'] = $db->query("SELECT * FROM coach WHERE id='" . $listGroupCouch['coach_id_3'] . "'")->fetch_assoc();
            $listGroupCouch['coach_info_4'] = $db->query("SELECT * FROM coach WHERE id='" . $listGroupCouch['coach_id_4'] . "'")->fetch_assoc();
            $listGroupCouch['coach_info_5'] = $db->query("SELECT * FROM coach WHERE id='" . $listGroupCouch['coach_id_5'] . "'")->fetch_assoc();
            $listGroupCouch['coach_info_6'] = $db->query("SELECT * FROM coach WHERE id='" . $listGroupCouch['coach_id_6'] . "'")->fetch_assoc();
            $listGroupCouch['franchaise_info'] = $db->query("SELECT * FROM franchaise WHERE id='" . $listGroupCouch['franchaise_id'] . "'")->fetch_assoc();

            if ($listGroupCouch['date_end_coaching'] >= $now) {
                $searchCoach['upcoming_groups'][] = $listGroupCouch;
            } else {
                $searchCoach['completed_groups'][] = $listGroupCouch;
            }
        }

        if ($searchCoach['password'] == md5($_POST['password'])) {
            echo json_encode([
                "type" => true,
                "data" => [
                    "user_info" => $searchCoach,
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["type" => false, "msg" => 'Указанный пароль не совпадает...'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(["type" => false, "msg" => 'Преподаватель с таким телефоном не существует...'], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["type" => false, "msg" => 'Не указан логин или пароль...'], JSON_UNESCAPED_UNICODE);
}
