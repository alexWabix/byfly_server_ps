<?php
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
                SUM(is_active = 1 AND (blocked_to_time IS NULL)) as active_count 
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

function getUsersByLine($userId, $line)
{
    global $db;
    $currentUserIds = [$userId];

    for ($i = 1; $i <= $line; $i++) {
        $placeholders = implode(',', array_fill(0, count($currentUserIds), '?'));
        $stmt = $db->prepare("SELECT * FROM users WHERE parent_user IN ($placeholders)");
        $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);
        $stmt->execute();
        $resultSet = $stmt->get_result();

        if ($i === $line) {
            $users = [];
            while ($row = $resultSet->fetch_assoc()) {
                $users[] = $row;
            }
            return $users;
        }

        $currentUserIds = [];
        while ($row = $resultSet->fetch_assoc()) {
            $currentUserIds[] = $row['id'];
        }
    }

    return [];
}

function determineUserStatus($user)
{
    if (!is_null($user['blocked_to_time'])) {
        $currentDate = new DateTime();
        $blockedDate = new DateTime($user['blocked_to_time']);
        $interval = $currentDate->diff($blockedDate);
        $daysBlocked = $interval->days;

        if ($daysBlocked > 365) {
            return "Заблокирован на всегда";
        } else {
            return "Заблокирован ({$daysBlocked} дней)";
        }
    }

    if ($user['astestation_bal'] > 92) {
        return 'Агент (обучение завершено)';
    } elseif (!is_null($user['date_couch_start'])) {
        return 'Проходит обучение';
    } else {
        return 'Пользователь';
    }
}

function getMaxLinesByActiveUsers($activeCount)
{
    if ($activeCount >= 2000) {
        return 5; // Альфа
    } elseif ($activeCount >= 1000) {
        return 4; // Коуч
    } elseif ($activeCount >= 500) {
        return 3; // Амбасадор
    } else {
        return 2; // Агент
    }
}

function getUserTurnover($userId, $lineCount)
{
    global $db;
    $currentUserIds = [$userId];
    $totalTurnover = 0;

    for ($i = 1; $i <= $lineCount; $i++) {
        $placeholders = implode(',', array_fill(0, count($currentUserIds), '?'));
        $stmt = $db->prepare("
            SELECT SUM(price) as total_turnover 
            FROM order_tours 
            WHERE user_id IN ($placeholders) AND (status_code = 4 OR status_code = 3)
        ");
        $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $totalTurnover += (float) ($result['total_turnover'] ?? 0);

        $stmt = $db->prepare("SELECT id FROM users WHERE parent_user IN ($placeholders)");
        $stmt->bind_param(str_repeat('s', count($currentUserIds)), ...$currentUserIds);
        $stmt->execute();
        $resultSet = $stmt->get_result();

        $currentUserIds = [];
        while ($row = $resultSet->fetch_assoc()) {
            $currentUserIds[] = $row['id'];
        }

        if (empty($currentUserIds)) {
            break;
        }
    }

    return $totalTurnover;
}

if (!empty($_POST['user_id']) && isset($_POST['line'])) {
    $userId = $_POST['user_id'];
    $requestedLine = (int) $_POST['line'];

    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $userId . "'")->fetch_assoc();
    $userStatus = determineUserStatus($userInfo);

    $userCounts = getUserCountsByLine($userId, 5);
    $activeUsers = array_sum(array_column($userCounts, 'active_users'));
    $maxLines = getMaxLinesByActiveUsers($activeUsers);

    if ($requestedLine > $maxLines) {
        echo json_encode(
            [
                "type" => false,
                "msg" => "Requested line exceeds the allowed lines for the user's status."
            ],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $totalUsers = 0;
    $activeUsers = 0;
    foreach ($userCounts as $line => $counts) {
        if ($line > $maxLines)
            break;
        $totalUsers += $counts['total_users'];
        $activeUsers += $counts['active_users'];
    }

    $users = getUsersByLine($userId, $requestedLine);
    $turnover = getUserTurnover($userId, $maxLines);

    echo json_encode(
        [
            "type" => true,
            "data" => [
                "users" => $users,
                "total_users" => $totalUsers,
                "active_users" => $activeUsers,
                "status" => $userStatus,
                "turnover" => $turnover,
                "max_lines" => $maxLines,
                "user_info" => $userInfo
            ],
        ],
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        [
            "type" => false,
            "msg" => 'Empty user_id or line parameter.',
        ],
        JSON_UNESCAPED_UNICODE
    );
}
