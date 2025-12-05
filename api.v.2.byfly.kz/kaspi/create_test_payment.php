<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$input = json_decode(file_get_contents('php://input'), true);

try {
    $payment_type = $db->real_escape_string($input['payment_type']);
    $payment_comment = $db->real_escape_string($input['payment_comment']);
    $payer_phone = $db->real_escape_string($input['payer_phone']);
    $payer_name = $db->real_escape_string($input['payer_name']);
    $amount_total = intval($input['amount_total']);
    $amount_received = intval($input['amount_received']);
    $payment_method = $db->real_escape_string($input['payment_method']);
    $initiator_user_id = intval($input['initiator_user_id']);

    // Находим свободный терминал с наивысшим приоритетом
    $terminalSql = "SELECT id FROM kaspi_terminals 
                    WHERE status = 'free' AND is_enabled = 1 
                    ORDER BY priority ASC, operations_count_today ASC 
                    LIMIT 1";

    $terminalResult = $db->query($terminalSql);

    if (!$terminalResult || $terminalResult->num_rows === 0) {
        throw new Exception('Нет доступных терминалов');
    }

    $terminal = $terminalResult->fetch_assoc();
    $terminal_id = $terminal['id'];

    // Создаем запись в очереди платежей
    $sql = "INSERT INTO kaspi_payment_queue (
                payment_type, payment_comment, initiator_user_id, payer_phone, payer_name,
                amount_total, amount_received, status, payment_method, terminal_id,
                date_created, expires_at, priority
            ) VALUES (
                '$payment_type', '$payment_comment', $initiator_user_id, '$payer_phone', '$payer_name',
                $amount_total, $amount_received, 'queue', '$payment_method', $terminal_id,
                NOW(), DATE_ADD(NOW(), INTERVAL 3 MINUTE), 100
            )";

    if ($db->query($sql)) {
        $payment_id = $db->insert_id;

        // Генерируем QR ссылку (пока заглушка, в реальности будет генерироваться после создания платежа на терминале)
        $qr_link = "https://pay.kaspi.kz/pay/test_" . $payment_id . "_" . time();

        // Обновляем запись с QR ссылкой
        $updateSql = "UPDATE kaspi_payment_queue SET qr_link = '$qr_link' WHERE id = $payment_id";
        $db->query($updateSql);

        $resp = array(
            "type" => true,
            "data" => array(
                "payment_id" => $payment_id,
                "qr_link" => $qr_link,
                "terminal_id" => $terminal_id
            )
        );
    } else {
        throw new Exception("Ошибка создания платежа: " . $db->error);
    }

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>