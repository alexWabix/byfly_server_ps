<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $sql = "SELECT 
                t.id, t.terminal_name, t.ip_address, t.port, t.camera_id, t.camera_name,
                t.status, t.is_enabled, t.last_operation_id, t.last_operation_date,
                t.last_check_date, t.priority, t.max_amount, t.min_amount, t.error_count,
                t.last_error_message, t.location, t.response_time_avg, t.operations_count_today,
                t.operations_count_total, t.last_success_date, t.maintenance_mode,
                t.maintenance_reason, t.created_at, t.updated_at,
                COUNT(p.id) as active_payments,
                SUM(CASE WHEN p.status IN ('queue', 'waiting_payment') THEN p.amount_total ELSE 0 END) as waiting_amount
            FROM kaspi_terminals t
            LEFT JOIN kaspi_payment_queue p ON t.id = p.terminal_id AND p.status IN ('queue', 'waiting_payment')
            GROUP BY t.id
            ORDER BY t.priority ASC, t.terminal_name ASC";

    $result = $db->query($sql);

    if ($result) {
        $terminals = [];
        while ($row = $result->fetch_assoc()) {
            $terminals[] = $row;
        }

        echo json_encode([
            'type' => true,
            'data' => $terminals
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception("Ошибка выполнения запроса: " . $db->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'type' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>