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
    // Получаем платежи в очереди
    $sql = "SELECT p.*, t.ip_address, t.port, t.camera_id 
            FROM kaspi_payment_queue p
            JOIN kaspi_terminals t ON p.terminal_id = t.id
            WHERE p.status = 'queue' 
            AND t.status = 'free' 
            AND t.is_enabled = 1
            ORDER BY p.priority ASC, p.date_created ASC
            LIMIT 1";

    $result = $db->query($sql);

    if ($result && $result->num_rows > 0) {
        $payment = $result->fetch_assoc();

        // Отмечаем терминал как занятый
        $updateTerminal = "UPDATE kaspi_terminals 
                           SET status = 'busy', last_operation_id = '{$payment['id']}'
                           WHERE id = {$payment['terminal_id']}";
        $db->query($updateTerminal);

        // Создаем платеж на терминале
        $url = "https://{$payment['ip_address']}:{$payment['port']}/v2/payment";
        $params = "amount={$payment['amount_total']}&owncheque=true";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);

            if ($data && isset($data['statusCode']) && $data['statusCode'] === 0 && isset($data['data']['processId'])) {
                // Обновляем статус платежа
                $updatePayment = "UPDATE kaspi_payment_queue 
                                  SET status = 'waiting_payment', 
                                      process_id = '{$data['data']['processId']}',
                                      date_payment_started = NOW(),
                                      expires_at = DATE_ADD(NOW(), INTERVAL 3 MINUTE)
                                  WHERE id = {$payment['id']}";
                $db->query($updatePayment);

                echo json_encode([
                    'type' => true,
                    'data' => [
                        'payment_id' => $payment['id'],
                        'process_id' => $data['data']['processId'],
                        'terminal_id' => $payment['terminal_id'],
                        'camera_id' => $payment['camera_id'],
                        'amount' => $payment['amount_total']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Ошибка создания платежа на терминале');
            }
        } else {
            throw new Exception('Терминал недоступен');
        }
    } else {
        echo json_encode([
            'type' => true,
            'data' => null,
            'msg' => 'Нет платежей в очереди'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'type' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>