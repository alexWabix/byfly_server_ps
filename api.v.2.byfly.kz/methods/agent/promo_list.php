<?php
if (!empty($_POST['user_id'])) {
    $listPromo = array();
    $listPromoGetDB = $db->query("SELECT * FROM promo_agent");

    while ($listPromoGet = $listPromoGetDB->fetch_assoc()) {
        $progress = 0;
        $totalTourSales = 0; // Переменная для подсчёта суммы продаж туров

        if ($listPromoGet['whats'] == 'agent') {
            $userCounts = getUserCountsByLine($_POST['user_id'], $listPromoGet['lines_arrow']);

            foreach ($userCounts as $line => $counts) {
                $progress += $counts['active_users'];
            }
        } else if ($listPromoGet['whats'] == 'tourMoney') {
            $progress = getTourSalesByLine($_POST['user_id'], $listPromoGet['lines_arrow']);
        }

        $listPromoGet['countAgent'] = $progress;

        array_push($listPromo, $listPromoGet);
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => $listPromo,
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Не удалось загрузить список акций...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}

function getUserCountsByLine($userId, $lineCount)
{
    global $db;
    $counts = [];
    $currentUserIds = [$userId];

    for ($i = 1; $i <= $lineCount; $i++) {
        $placeholders = implode(',', array_fill(0, count($currentUserIds), '?'));
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_count,
                SUM(date_couch_start IS NOT NULL AND (blocked_to_time IS NULL)) as active_count 
            FROM users 
            WHERE parent_user IN ($placeholders)
        ");

        $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $counts[$i] = [
            'total_users' => (int) $result['total_count'],
            'active_users' => (int) $result['active_count']
        ];

        if ($result['total_count'] > 0) {
            $stmt = $db->prepare("SELECT id FROM users WHERE parent_user IN ($placeholders)");
            $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);
            $stmt->execute();
            $resultSet = $stmt->get_result();

            $currentUserIds = [];
            while ($row = $resultSet->fetch_assoc()) {
                $currentUserIds[] = $row['id'];
            }
        } else {
            break;
        }
    }

    return $counts;
}

// Функция для подсчёта суммы продаж туров для линии
function getTourSalesByLine($userId, $line)
{
    global $db;
    $totalSale = 0;
    $currentUserIds = [$userId];

    for ($i = 1; $i <= $line; $i++) {
        if (empty($currentUserIds)) {
            error_log("На линии $i нет пользователей для подсчёта продаж.");
            break;
        }

        $placeholders = implode(',', array_fill(0, count($currentUserIds), '?'));
        $stmt = $db->prepare("
            SELECT id 
            FROM users 
            WHERE parent_user IN ($placeholders)
        ");

        $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);
        $stmt->execute();
        $resultSet = $stmt->get_result();

        $currentUserIds = [];
        while ($row = $resultSet->fetch_assoc()) {
            $currentUserIds[] = $row['id'];
        }

        if (empty($currentUserIds)) {
            error_log("На линии $i нет пользователей.");
            break;
        }
    }

    // Подсчитываем сумму продаж туров по пользователям на текущей линии
    if (count($currentUserIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($currentUserIds), '?'));
        $stmt = $db->prepare("
            SELECT SUM(real_price) as total_sales 
            FROM order_tours 
            WHERE user_id IN ($placeholders) AND status_code IN (3, 4)
        ");

        $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && isset($result['total_sales'])) {
            $totalSale = (float) $result['total_sales'];
        } else {
            error_log("Нет данных о продажах для пользователей на линии $line.");
        }
    }

    return $totalSale;
}
?>