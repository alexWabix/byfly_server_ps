<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => '', 'data' => []];

    try {
        $summ = isset($_POST['summ']) ? (float) $_POST['summ'] : 0;
        $description = isset($_POST['description']) ? $db->real_escape_string($_POST['description']) : '';
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;

        if ($summ <= 0 || empty($description) || $order_id <= 0) {
            throw new Exception('Некорректные данные.');
        }

        $query = "INSERT INTO order_vozvrat (summ, date, description, order_id) VALUES ('$summ', CURRENT_TIMESTAMP, '$description', '$order_id')";
        if (!$db->query($query)) {
            throw new Exception('Ошибка при добавлении возврата: ' . $db->error);
        }

        $last_id = $db->insert_id;
        $current_date = date('Y-m-d H:i:s');

        $response['success'] = true;
        $response['data'] = [
            'id' => $last_id,
            'date' => $current_date,
            'summ' => $summ,
            'description' => $description
        ];
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?>