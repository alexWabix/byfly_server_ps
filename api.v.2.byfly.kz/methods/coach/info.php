<?php
if (!empty($_POST['coach_id'])) {
    $coachId = (int) $_POST['coach_id'];
    $searchCoach = $db->query("SELECT * FROM coach WHERE id='$coachId'");

    if ($searchCoach->num_rows > 0) {
        $searchCoach = $searchCoach->fetch_assoc();
        $currentDateTime = date("Y-m-d H:i:s");

        // Считаем количество активных групп, где тренер прикреплён
        $result = $db->query("
            SELECT COUNT(*) as ct 
            FROM grouped_coach 
            WHERE date_start_coaching <= '$currentDateTime' 
            AND date_end_coaching >= '$currentDateTime'
            AND (
                coach_id_1 = '$coachId' OR 
                coach_id_2 = '$coachId' OR 
                coach_id_3 = '$coachId' OR 
                coach_id_4 = '$coachId' OR 
                coach_id_5 = '$coachId' OR 
                coach_id_6 = '$coachId'
            )
        ");
        $searchCoach['count_this_groups'] = ($result) ? $result->fetch_assoc()['ct'] : 0;

        // Общее количество пользователей у этого тренера
        $result = $db->query("SELECT COUNT(*) as ct FROM users WHERE coach_id='$coachId'");
        $searchCoach['count_users_couch'] = ($result) ? $result->fetch_assoc()['ct'] : 0;

        // Средний балл
        $result = $db->query("
            SELECT AVG(attestation_bal) as average 
            FROM users 
            WHERE coach_id='$coachId'
        ");
        $searchCoach['average_score'] = ($result) ? round($result->fetch_assoc()['average'], 2) : 0;

        // Текущие студенты без завершенной аттестации
        $result = $db->query("
            SELECT COUNT(*) as ct 
            FROM users 
            WHERE date_couch_start <= '$currentDateTime' 
            AND end_atestation IS NULL 
            AND coach_id='$coachId'
        ");
        $searchCoach['count_this_people_this_couch'] = ($result) ? $result->fetch_assoc()['ct'] : 0;

        // Пользователи с баллом > 90
        $result = $db->query("
            SELECT COUNT(*) as ct 
            FROM users 
            WHERE attestation_bal > 90 
            AND coach_id='$coachId'
        ");
        $searchCoach['count_this_exam_users'] = ($result) ? $result->fetch_assoc()['ct'] : 0;

        // Информация о франшизе
        $searchCoach['franchaise_info'] = $db->query("
            SELECT * FROM franchaise WHERE id='" . $searchCoach['franchaise'] . "'
        ")->fetch_assoc();

        echo json_encode([
            "type" => true,
            "data" => $searchCoach,
        ], JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode([
            "type" => false,
            "msg" => 'Coach not found...',
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        "type" => false,
        "msg" => 'Empty coach id...',
    ], JSON_UNESCAPED_UNICODE);
}
?>