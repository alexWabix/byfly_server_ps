<?php
session_start();
header('Content-Type: application/json');

// Подключение к базе данных
include 'config.php';

try {
    // Получаем конфигурацию аукциона
    $config_stmt = $db->prepare("SELECT * FROM auction_config WHERE id = 1");
    $config_stmt->execute();
    $auction_config = $config_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$auction_config) {
        throw new Exception('Конфигурация аукциона не найдена');
    }

    // Получаем текущую лидирующую ставку
    $current_bid_stmt = $db->prepare("
        SELECT ab.*, au.name as user_name 
        FROM auction_bids ab 
        JOIN auction_users au ON ab.user_id = au.id 
        ORDER BY ab.bid_amount DESC, ab.created_at ASC 
        LIMIT 1
    ");
    $current_bid_stmt->execute();
    $current_bid = $current_bid_stmt->fetch(PDO::FETCH_ASSOC);

    // Проверяем статус аукциона
    $now = new DateTime();
    $start_time = new DateTime($auction_config['start_time']);
    $end_time = new DateTime($auction_config['end_time']);

    $auction_not_started = $now < $start_time;
    $auction_ended = $now > $end_time;
    $auction_active = $auction_config['is_active'] && !$auction_not_started && !$auction_ended;

    $response = [
        'success' => true,
        'auction_active' => $auction_active,
        'auction_ended' => $auction_ended,
        'current_bid_amount' => $current_bid ? (int) $current_bid['bid_amount'] : (int) $auction_config['starting_price'],
        'current_bid_user' => $current_bid ? $current_bid['user_name'] : '',
        'min_next_bid' => $current_bid ?
            (int) $current_bid['bid_amount'] + (int) $auction_config['min_bid_increment'] :
            (int) $auction_config['starting_price'] + (int) $auction_config['min_bid_increment'],
        'time_remaining' => $auction_active ? $end_time->getTimestamp() - $now->getTimestamp() : 0
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>