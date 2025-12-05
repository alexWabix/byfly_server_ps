<?php
if (empty($_POST['user_id']) == false) {
    $userInfoDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "' AND is_present_comands = '1'");
    if ($userInfoDB->num_rows > 0) {
        $userInfo = $userInfoDB->fetch_assoc();
        $listUsers = array();
        $listMyUsersDB = $db->query("SELECT * FROM users WHERE present_comands_id='" . $_POST['user_id'] . "'");
        $obshPays = 0;

        // Получаем текущую дату и вычисляем дату начала и конца диапазона
        $currentDate = new DateTime();
        $startOfMonth = new DateTime("first day of this month");
        $startOfMonth->modify("10 days");
        $endOfMonth = new DateTime("first day of next month");
        $endOfMonth->modify("10 days");

        // Преобразуем даты в формат, который используется в базе данных
        $startDate = $startOfMonth->format('Y-m-d');
        $endDate = $endOfMonth->format('Y-m-d');

        // Рассчитываем, сколько дней осталось до конца периода
        $interval = $currentDate->diff($endOfMonth);
        $daysRemaining = $interval->days;

        while ($listMyUsers = $listMyUsersDB->fetch_assoc()) {
            // Фильтруем по дате операций в таблице present_event по полю date_start
            $listMyUsers['countPresents'] = $db->query("SELECT COUNT(*) as ct FROM present_event WHERE user_id='" . $listMyUsers['user_id'] . "' AND date_start BETWEEN '$startDate' AND '$endDate'")->fetch_assoc()['ct'];
            $listMyUsers['countPays'] = $db->query("SELECT SUM(count_pay) as ct FROM present_event WHERE user_id='" . $listMyUsers['user_id'] . "' AND date_start BETWEEN '$startDate' AND '$endDate'")->fetch_assoc()['ct'];
            if ($listMyUsers['countPays'] == null) {
                $listMyUsers['countPays'] = 0;
            }
            $obshPays = $obshPays + $listMyUsers['countPays'];
            array_push($listUsers, $listMyUsers);
        }

        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "users" => $listUsers,
                    "countPays" => $obshPays,
                    "daysRemaining" => $daysRemaining, // Добавляем количество дней до закрытия периода
                )
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Пользователь не является главой презентационной команды..."
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Empty user_id variable."
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>