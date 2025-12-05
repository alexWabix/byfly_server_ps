<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$input = json_decode(file_get_contents('php://input'), true);

try {
    $payment_id = intval($input['payment_id']);
    $process_id = $db->real_escape_string($input['process_id'] ?? '');

    // Получаем информацию о платеже
    $sql = "SELECT * FROM kaspi_payment_queue WHERE id = $payment_id";
    $result = $db->query($sql);

    if (!$result || $result->num_rows === 0) {
        throw new Exception('Платеж не найден');
    }

    $payment = $result->fetch_assoc();

    // Если есть process_id, пытаемся отменить на терминале
    if (!empty($process_id) && $payment['terminal_id']) {
        $terminalSql = "SELECT ip_address, port FROM kaspi_terminals WHERE id = {$payment['terminal_id']}";
        $terminalResult = $db->query($terminalSql);

        if ($terminalResult && $terminalResult->num_rows > 0) {
            $terminal = $terminalResult->fetch_assoc();

            // Отменяем платеж на терминале
            $url = "https://{$terminal['ip_address']}:{$terminal['port']}/cancel?processId=$process_id";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            curl_close($ch);

            // Освобождаем терминал
            $updateTerminalSql = "UPDATE kaspi_terminals SET status = 'free' WHERE id = {$payment['terminal_id']}";
            $db->query($updateTerminalSql);
        }
    }

    // Обновляем статус платежа
    $updateSql = "UPDATE kaspi_payment_queue 
                  SET status = 'cancelled', 
                      date_completed = NOW(),
                      error_message = 'Отменено пользователем'
                  WHERE id = $payment_id";

    if ($db->query($updateSql)) {
        $resp = array(
            "type" => true,
            "msg" => "Платеж отменен"
        );
    } else {
        throw new Exception("Ошибка отмены платежа: " . $db->error);
    }

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>