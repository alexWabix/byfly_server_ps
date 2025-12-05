<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

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
            WHERE user_id IN ($placeholders) AND status_code=5
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


echo getUserTurnover(91, 4);

?>