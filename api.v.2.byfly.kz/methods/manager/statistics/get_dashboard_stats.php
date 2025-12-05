<?php

$manager_id = $_POST['manager_id'] ?? '';
$is_current_manager = $_POST['is_current_manager'] ?? '1';

if (empty($manager_id)) {
    echo json_encode([
        "type" => false,
        "msg" => "ID менеджера обязателен"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Проверяем подключение к базе данных
    if (!$db) {
        echo json_encode([
            "type" => false,
            "msg" => "Ошибка подключения к базе данных"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Получаем информацию о менеджере с отладкой (используем mysqli)
    $managerQuery = "SELECT * FROM managers WHERE id = ?";
    $managerStmt = $db->prepare($managerQuery);

    if (!$managerStmt) {
        echo json_encode([
            "type" => false,
            "msg" => "Ошибка подготовки запроса: " . $db->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $managerStmt->bind_param("i", $manager_id);
    $executeResult = $managerStmt->execute();

    if (!$executeResult) {
        echo json_encode([
            "type" => false,
            "msg" => "Ошибка выполнения запроса: " . $managerStmt->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $managerStmt->get_result();
    $manager = $result->fetch_assoc();
    $managerStmt->close();

    // Отладочная информация
    error_log("Manager found: " . ($manager ? "YES" : "NO"));
    if ($manager) {
        error_log("Manager data: " . json_encode($manager));
    }

    if (!$manager) {
        // Дополнительная проверка - может быть менеджер есть, но с другим ID
        $checkQuery = "SELECT COUNT(*) as count FROM managers";
        $checkResult = $db->query($checkQuery);
        $totalManagers = $checkResult->fetch_assoc();

        // Проверяем есть ли менеджер с таким ID вообще
        $existsQuery = "SELECT id, fio FROM managers WHERE id = ? LIMIT 1";
        $existsStmt = $db->prepare($existsQuery);
        $existsStmt->bind_param("i", $manager_id);
        $existsStmt->execute();
        $existsResult = $existsStmt->get_result();
        $exists = $existsResult->fetch_assoc();
        $existsStmt->close();

        echo json_encode([
            "type" => false,
            "msg" => "Менеджер с ID {$manager_id} не найден. Всего менеджеров в базе: " . $totalManagers['count'],
            "debug" => [
                "manager_id_searched" => $manager_id,
                "manager_id_type" => gettype($manager_id),
                "exists_check" => $exists,
                "total_managers" => $totalManagers['count']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $isAdmin = ($manager['type'] ?? 0) == 1;

    // Основная статистика по турам
    if ($is_current_manager == '1') {
        $totalToursQuery = "
            SELECT 
                COUNT(*) as total_tours,
                COALESCE(SUM(price), 0) as total_amount,
                SUM(CASE WHEN status_code = 3 THEN 1 ELSE 0 END) as waiting_departure,
                SUM(CASE WHEN status_code = 4 THEN 1 ELSE 0 END) as on_vacation,
                SUM(CASE WHEN status_code = 1 THEN 1 ELSE 0 END) as waiting_prepayment,
                SUM(CASE WHEN status_code = 2 THEN 1 ELSE 0 END) as waiting_full_payment
            FROM order_tours 
            WHERE manager_id = ?";

        $totalToursStmt = $db->prepare($totalToursQuery);
        $totalToursStmt->bind_param("i", $manager_id);
    } else {
        $totalToursQuery = "
            SELECT 
                COUNT(*) as total_tours,
                COALESCE(SUM(price), 0) as total_amount,
                SUM(CASE WHEN status_code = 3 THEN 1 ELSE 0 END) as waiting_departure,
                SUM(CASE WHEN status_code = 4 THEN 1 ELSE 0 END) as on_vacation,
                SUM(CASE WHEN status_code = 1 THEN 1 ELSE 0 END) as waiting_prepayment,
                SUM(CASE WHEN status_code = 2 THEN 1 ELSE 0 END) as waiting_full_payment
            FROM order_tours";

        $totalToursStmt = $db->prepare($totalToursQuery);
    }

    error_log("Tours query: " . $totalToursQuery);

    $totalToursStmt->execute();
    $toursResult = $totalToursStmt->get_result();
    $tourStats = $toursResult->fetch_assoc();
    $totalToursStmt->close();

    $statistics = [
        'total_tours' => intval($tourStats['total_tours'] ?? 0),
        'total_amount' => intval($tourStats['total_amount'] ?? 0),
        'waiting_departure' => intval($tourStats['waiting_departure'] ?? 0),
        'on_vacation' => intval($tourStats['on_vacation'] ?? 0),
        'waiting_prepayment' => intval($tourStats['waiting_prepayment'] ?? 0),
        'waiting_full_payment' => intval($tourStats['waiting_full_payment'] ?? 0),
    ];

    // Дополнительная статистика для админов
    if ($isAdmin) {
        // Туры не оплаченные туроператору
        if ($is_current_manager == '1') {
            $unpaidOperatorQuery = "
                SELECT 
                    COUNT(*) as unpaid_count,
                    COALESCE(SUM(ot.price), 0) as unpaid_amount
                FROM order_tours ot
                LEFT JOIN order_tour_operators oto ON ot.id = oto.order_id AND oto.is_from = 1
                WHERE oto.order_id IS NULL AND ot.manager_id = ?";

            $unpaidOperatorStmt = $db->prepare($unpaidOperatorQuery);
            $unpaidOperatorStmt->bind_param("i", $manager_id);
        } else {
            $unpaidOperatorQuery = "
                SELECT 
                    COUNT(*) as unpaid_count,
                    COALESCE(SUM(ot.price), 0) as unpaid_amount
                FROM order_tours ot
                LEFT JOIN order_tour_operators oto ON ot.id = oto.order_id AND oto.is_from = 1
                WHERE oto.order_id IS NULL";

            $unpaidOperatorStmt = $db->prepare($unpaidOperatorQuery);
        }

        $unpaidOperatorStmt->execute();
        $unpaidResult = $unpaidOperatorStmt->get_result();
        $unpaidStats = $unpaidResult->fetch_assoc();
        $unpaidOperatorStmt->close();

        // Статистика по спец предложениям
        if ($is_current_manager == '1') {
            $specOffersQuery = "
                SELECT 
                    COUNT(*) as spec_total,
                    SUM(CASE WHEN status_code = 3 THEN 1 ELSE 0 END) as spec_waiting_departure,
                    SUM(CASE WHEN status_code = 4 THEN 1 ELSE 0 END) as spec_on_vacation,
                    SUM(CASE WHEN status_code IN (1,2) THEN 1 ELSE 0 END) as spec_waiting_payment
                FROM order_tours 
                WHERE type = 'spec' AND manager_id = ?";

            $specOffersStmt = $db->prepare($specOffersQuery);
            $specOffersStmt->bind_param("i", $manager_id);
        } else {
            $specOffersQuery = "
                SELECT 
                    COUNT(*) as spec_total,
                    SUM(CASE WHEN status_code = 3 THEN 1 ELSE 0 END) as spec_waiting_departure,
                    SUM(CASE WHEN status_code = 4 THEN 1 ELSE 0 END) as spec_on_vacation,
                    SUM(CASE WHEN status_code IN (1,2) THEN 1 ELSE 0 END) as spec_waiting_payment
                FROM order_tours 
                WHERE type = 'spec'";

            $specOffersStmt = $db->prepare($specOffersQuery);
        }

        $specOffersStmt->execute();
        $specResult = $specOffersStmt->get_result();
        $specStats = $specResult->fetch_assoc();
        $specOffersStmt->close();

        $statistics = array_merge($statistics, [
            'unpaid_to_operator' => intval($unpaidStats['unpaid_count'] ?? 0),
            'amount_to_pay_operator' => intval($unpaidStats['unpaid_amount'] ?? 0),
            'spec_offers_sold' => intval($specStats['spec_total'] ?? 0),
            'spec_waiting_departure' => intval($specStats['spec_waiting_departure'] ?? 0),
            'spec_on_vacation' => intval($specStats['spec_on_vacation'] ?? 0),
            'spec_waiting_payment' => intval($specStats['spec_waiting_payment'] ?? 0),
        ]);
    }

    echo json_encode([
        "type" => true,
        "msg" => "Статистика загружена",
        "data" => $statistics,
        "debug" => [
            "manager_info" => [
                "id" => $manager['id'],
                "fio" => $manager['fio'],
                "type" => $manager['type'],
                "is_admin" => $isAdmin
            ],
            "filter_info" => [
                "is_current_manager" => $is_current_manager,
                "manager_id_used" => $manager_id
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in get_dashboard_stats: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        "type" => false,
        "msg" => "Ошибка при загрузке статистики: " . $e->getMessage(),
        "debug" => [
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>