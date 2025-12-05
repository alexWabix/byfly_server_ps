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
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $sql = "SELECT 
                p.id, p.payment_type, p.payment_comment, p.initiator_user_id,
                p.payer_phone, p.payer_name, p.amount_total, p.amount_received,
                p.status, p.payment_method, p.terminal_id, p.process_id,
                p.kaspi_order_number, p.kaspi_rrn, p.kaspi_transaction_id,
                p.date_created, p.date_payment_started, p.date_paid, p.date_completed,
                p.payment_duration_seconds, p.error_message, p.retry_count,
                p.related_order_id, p.installment_term, p.expires_at,
                t.terminal_name, t.ip_address
            FROM kaspi_payment_queue p
            LEFT JOIN kaspi_terminals t ON p.terminal_id = t.id
            ORDER BY p.date_created DESC
            LIMIT $limit OFFSET $offset";

    $result = $db->query($sql);

    if ($result) {
        $operations = [];
        while ($row = $result->fetch_assoc()) {
            $operations[] = $row;
        }

        echo json_encode([
            'type' => true,
            'data' => $operations
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