<?php

$userInfo = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'");
if ($userInfo->num_rows > 0) {
    $listDZArr = array();
    $userInfo = $userInfo->fetch_assoc();

    $dateStartCoach = new DateTime();
    $dateStartCoach->modify('-1 year');

    if ($userInfo['grouped'] > 0) {
        $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $userInfo['grouped'] . "'");
        if ($groupInfo->num_rows > 0) {
            $groupInfo = $groupInfo->fetch_assoc();
            $dateStartCoach = new DateTime($groupInfo['date_start_coaching']);

            // Подсчет реального количества учебных дней
            $today = new DateTime();
            $studyDate = clone $dateStartCoach;
            $dayCoach = 0;

            while ($studyDate <= $today) {
                $dayCoach++;
                $studyDate->modify('+2 days'); // Обучение через день
            }
        }
    }

    $listDZdb = $db->query("SELECT * FROM home_work");
    $groupedDZ = [];

    while ($listDZ = $listDZdb->fetch_assoc()) {
        $isAvailable = false;

        // Определяем дату и время открытия ДЗ
        $lessonDate = clone $dateStartCoach;
        $lessonDate->modify('+' . (($listDZ['day'] - 1) * 2) . ' days')->setTime(19, 0, 0);

        $now = new DateTime();
        if ($now >= $lessonDate) {
            $isAvailable = true;
        }

        if ($isAvailable) {
            $listDZ['status'] = 1;
            $listDZ['work'] = 0;
            $listDZ['time_left'] = null;

            $searchResult = $db->query("SELECT * FROM home_work_user WHERE user_dz='" . $_POST['user_id'] . "' AND home_work_id='" . $listDZ['id'] . "'");
            if ($searchResult->num_rows > 0) {
                $listDZ['work'] = 1;
                $listDZ['work_info'] = $searchResult->fetch_assoc();
            }
        } else {
            $listDZ['status'] = 0;

            // Оставшееся время до открытия ДЗ
            $diff = $now->diff($lessonDate);
            $listDZ['time_left'] = $diff->days . ' дн. ' . $diff->h . ' ч.';
        }

        $listDZ['day_coach'] = $dayCoach;
        $groupedDZ[$listDZ['day']][] = $listDZ;
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => $groupedDZ,
        )
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Нет информации о пользователе!",
        )
    );
}

?>