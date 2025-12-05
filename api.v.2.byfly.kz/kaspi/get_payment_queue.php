<?php
include('../config.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

    // Базовый запрос
    $where_conditions = [];
    $params = [];
    $types = '';

    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Получаем очередь платежей
    $query = "SELECT 
        kpq.*,
        kt.terminal_name,
        kt.ip_address,
        kt.status as terminal_status
        FROM kaspi_payment_queue kpq
        LEFT JOIN kaspi_terminals kt ON kpq.terminal_id = kt.id
        $where_clause
        ORDER BY 
            CASE 
                WHEN kpq.status = 'queue' THEN 1
                WHEN kpq.status = 'waiting_payment' THEN 2
                ELSE 3
            END,
            kpq.priority ASC,
            kpq.date_created ASC
        LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $db->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = [
            'id' => (int) $row['id'],
            'payment_type' => $row['payment_type'],
            'payment_comment' => $row['payment_comment'],
            'initiator_user_id' => (int) $row['initiator_user_id'],
            'payer_phone' => $row['payer_phone'],
            'payer_name' => $row['payer_name'],
            'amount_total' => (int) $row['amount_total'],
            'amount_received' => (int) $row['amount_received'],
            'status' => $row['status'],
            'payment_method' => $row['payment_method'],
            'terminal_id' => $row['terminal_id'] ? (int) $row['terminal_id'] : null,
            'terminal_name' => $row['terminal_name'],
            'terminal_ip' => $row['ip_address'],
            'terminal_status' => $row['terminal_status'],
            'process_id' => $row['process_id'],
            'kaspi_order_number' => $row['kaspi_order_number'],
            'kaspi_rrn' => $row['kaspi_rrn'],
            'kaspi_transaction_id' => $row['kaspi_transaction_id'],
            'qr_link' => $row['qr_link'],
            'date_created' => $row['date_created'],
            'date_payment_started' => $row['date_payment_started'],
            'date_paid' => $row['date_paid'],
            'date_completed' => $row['date_completed'],
            'payment_duration_seconds' => $row['payment_duration_seconds'] ? (int) $row['payment_duration_seconds'] : null,
            'error_message' => $row['error_message'],
            'retry_count' => (int) $row['retry_count'],
            'max_retries' => (int) $row['max_retries'],
            'priority' => (int) $row['priority'],
            'expires_at' => $row['expires_at'],
            'related_order_id' => $row['related_order_id'] ? (int) $row['related_order_id'] : null,
            'installment_term' => $row['installment_term'] ? (int) $row['installment_term'] : null,
            'created_by_user_id' => $row['created_by_user_id'] ? (int) $row['created_by_user_id'] : null
        ];
    }

    // Получаем общее количество записей
    $count_query = "SELECT COUNT(*) as total FROM kaspi_payment_queue kpq $where_clause";
    $count_params = array_slice($params, 0, -2); // Убираем limit и offset
    $count_types = substr($types, 0, -2); // Убираем типы для limit и offset

    $count_stmt = $db->prepare($count_query);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];

    $response = [
        'type' => true,
        'data' => [
            'payments' => $payments,
            'pagination' => [
                'total' => (int) $total_count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => 'Ошибка получения очереди платежей: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>