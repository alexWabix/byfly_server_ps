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
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['terminal_id']) || !isset($input['camera_id'])) {
        throw new Exception('Не указаны обязательные параметры');
    }

    $terminal_id = intval($input['terminal_id']);
    $camera_id = $db->real_escape_string($input['camera_id']);

    $sql = "UPDATE kaspi_terminals 
            SET camera_id = '$camera_id', updated_at = NOW() 
            WHERE id = $terminal_id";

    if ($db->query($sql)) {
        echo json_encode([
            'type' => true,
            'msg' => 'Камера терминала обновлена'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception("Ошибка обновления: " . $db->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'type' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>