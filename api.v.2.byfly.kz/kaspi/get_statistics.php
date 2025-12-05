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
    // Общая статистика
    $sql = "SELECT 
                COUNT(*) as total_operations,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as successful_operations,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_operations,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_operations,
                AVG(CASE WHEN payment_duration_seconds > 0 THEN payment_duration_seconds END) as avg_time,
                SUM(CASE WHEN status = 'paid' THEN amount_received ELSE 0 END) as total_amount,
                SUM(CASE WHEN status = 'paid' AND DATE(date_paid) = CURDATE() THEN amount_received ELSE 0 END) as today_amount,
                AVG(CASE WHEN status = 'paid' THEN amount_received END) as avg_check
            FROM kaspi_payment_queue";

    $result = $db->query($sql);
    $generalStats = $result->fetch_assoc();

    // Статистика по методам оплаты
    $sql = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'paid' THEN amount_received ELSE 0 END) as amount
            FROM kaspi_payment_queue 
            WHERE status = 'paid'
            GROUP BY payment_method";

    $result = $db->query($sql);
    $paymentMethods = [];
    while ($row = $result->fetch_assoc()) {
        $paymentMethods[$row['payment_method']] = [
            'count' => $row['count'],
            'amount' => $row['amount']
        ];
    }

    // Статистика по типам платежей
    $sql = "SELECT 
                payment_type,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'paid' THEN amount_received ELSE 0 END) as amount
            FROM kaspi_payment_queue 
            WHERE status = 'paid'
            GROUP BY payment_type";

    $result = $db->query($sql);
    $paymentTypes = [];
    while ($row = $result->fetch_assoc()) {
        $paymentTypes[$row['payment_type']] = [
            'count' => $row['count'],
            'amount' => $row['amount']
        ];
    }

    // Расчет процента успешности
    $successRate = $generalStats['total_operations'] > 0
        ? round(($generalStats['successful_operations'] / $generalStats['total_operations']) * 100, 2)
        : 0;

    $statistics = [
        'total_operations' => $generalStats['total_operations'],
        'successful_operations' => $generalStats['successful_operations'],
        'cancelled_operations' => $generalStats['cancelled_operations'],
        'expired_operations' => $generalStats['expired_operations'],
        'avg_time' => round($generalStats['avg_time'] ?: 0),
        'total_amount' => $generalStats['total_amount'] ?: 0,
        'today_amount' => $generalStats['today_amount'] ?: 0,
        'avg_check' => round($generalStats['avg_check'] ?: 0),
        'success_rate' => $successRate,
        'kaspi_gold' => $paymentMethods['kaspi_gold']['count'] ?? 0,
        'kaspi_red' => $paymentMethods['kaspi_red']['count'] ?? 0,
        'installment' => $paymentMethods['installment']['count'] ?? 0,
        'credit' => $paymentMethods['credit']['count'] ?? 0,
        'payment_methods' => $paymentMethods,
        'payment_types' => $paymentTypes
    ];

    echo json_encode([
        'type' => true,
        'data' => $statistics
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'type' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>