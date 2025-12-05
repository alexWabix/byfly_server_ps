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
    // Получаем все активные терминалы
    $sql = "SELECT id, ip_address, port, status, error_count 
            FROM kaspi_terminals 
            WHERE is_enabled = 1";

    $result = $db->query($sql);
    $statuses = [];

    while ($terminal = $result->fetch_assoc()) {
        $url = "https://{$terminal['ip_address']}:{$terminal['port']}/v2/deviceinfo";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $newStatus = 'offline';
        $errorMessage = null;

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['statusCode']) && $data['statusCode'] === 0) {
                $newStatus = 'free';
            } else {
                $newStatus = 'error';
                $errorMessage = 'Неверный ответ терминала';
            }
        } else {
            $newStatus = 'offline';
            $errorMessage = $error ?: "HTTP код: $httpCode";
        }

        // Обновляем статус
        $errorCount = ($newStatus === 'offline' || $newStatus === 'error')
            ? $terminal['error_count'] + 1
            : 0;

        $updateSql = "UPDATE kaspi_terminals 
                      SET status = '$newStatus', 
                          last_check_date = NOW(), 
                          error_count = $errorCount" .
            ($errorMessage ? ", last_error_message = '" . $db->real_escape_string($errorMessage) . "'" : "") .
            " WHERE id = {$terminal['id']}";

        $db->query($updateSql);

        $statuses[] = [
            'terminal_id' => $terminal['id'],
            'status' => $newStatus,
            'last_check_date' => date('Y-m-d H:i:s'),
            'error_count' => $errorCount
        ];
    }

    echo json_encode([
        'type' => true,
        'data' => $statuses
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'type' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>