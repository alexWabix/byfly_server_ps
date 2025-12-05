<?php
session_start();

// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Конфигурация аукциона
$ADMIN_PHONE = '77780021666';
$START_PRICE = 10000;
$MAX_PRICE = 300000;
$BID_STEP = 10000;
$MAX_BIDS_PER_USER = 2;

// Функции для работы с БД
function getAuctionData($db)
{
    $result = $db->query("SELECT * FROM auction_sessions WHERE id = 1");
    $auction = $result->fetch_assoc();

    // Получаем историю ставок
    $result = $db->query("SELECT * FROM auction_bids WHERE auction_id = 1 ORDER BY date_created DESC LIMIT 50");
    $bid_history = [];
    while ($row = $result->fetch_assoc()) {
        $bid_history[] = [
            'amount' => $row['bid_amount'],
            'user' => $row['user_name'],
            'phone' => $row['user_phone'],
            'time' => $row['date_created']
        ];
    }

    // Получаем счетчики ставок пользователей
    $result = $db->query("SELECT user_phone, bids_count FROM auction_user_bids WHERE auction_id = 1");
    $user_bids_count = [];
    while ($row = $result->fetch_assoc()) {
        $user_bids_count[$row['user_phone']] = $row['bids_count'];
    }

    return [
        'auction_active' => (bool) $auction['auction_active'],
        'current_bid' => (int) $auction['current_bid'],
        'current_winner' => $auction['current_winner'],
        'current_winner_phone' => $auction['current_winner_phone'],
        'bid_history' => $bid_history,
        'user_bids_count' => $user_bids_count,
        'last_update' => time()
    ];
}

function saveAuctionBid($db, $bidAmount, $userPhone, $userName)
{
    $bidAmount = intval($bidAmount);
    $userPhone = $db->real_escape_string($userPhone);
    $userName = $db->real_escape_string($userName);

    $sql = "UPDATE auction_sessions SET current_bid = $bidAmount, current_winner = '$userName', current_winner_phone = '$userPhone' WHERE id = 1";
    $db->query($sql);

    $sql = "INSERT INTO auction_bids (auction_id, user_phone, user_name, bid_amount) VALUES (1, '$userPhone', '$userName', $bidAmount)";
    $db->query($sql);

    $sql = "INSERT INTO auction_user_bids (auction_id, user_phone, bids_count) VALUES (1, '$userPhone', 1) ON DUPLICATE KEY UPDATE bids_count = bids_count + 1";
    $db->query($sql);
}

function getUserBidsCount($db, $userPhone)
{
    $userPhone = $db->real_escape_string($userPhone);
    $result = $db->query("SELECT bids_count FROM auction_user_bids WHERE auction_id = 1 AND user_phone = '$userPhone'");
    $row = $result->fetch_assoc();
    return $row ? $row['bids_count'] : 0;
}

function toggleAuctionStatus($db)
{
    $result = $db->query("SELECT auction_active FROM auction_sessions WHERE id = 1");
    $row = $result->fetch_assoc();
    $newStatus = $row['auction_active'] ? 0 : 1;

    $db->query("UPDATE auction_sessions SET auction_active = $newStatus WHERE id = 1");

    return (bool) $newStatus;
}

function cleanPhone($phone)
{
    return preg_replace('/[^0-9]/', '', $phone);
}

// Обработка AJAX запросов
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        switch ($_POST['action']) {
            case 'send_code':
                $phone = cleanPhone($_POST['phone']);
                $country = $_POST['country'];

                if ($country === 'kz' && (strlen($phone) !== 11 || !preg_match('/^7[0-9]{10}$/', $phone))) {
                    throw new Exception('Неверный формат номера для Казахстана');
                }
                if ($country === 'uz' && (strlen($phone) !== 12 || !preg_match('/^998[0-9]{9}$/', $phone))) {
                    throw new Exception('Неверный формат номера для Узбекистана');
                }

                $sql = "SELECT * FROM users WHERE phone = '" . $phone . "' AND user_status != 'user'";
                $res = $db->query($sql);
                $user = $res->fetch_assoc();

                if (!$user) {
                    throw new Exception('Пользователь не найден или не является агентом');
                }

                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['auth_code'] = $code;
                $_SESSION['auth_phone'] = $phone;
                $_SESSION['auth_time'] = time();

                $message = "🎯 Ваш код для входа в аукцион ByFly Travel: *$code*\n⏰ Код действителен 5 минут.";
                sendWhatsApp($phone, $message);

                echo json_encode(['success' => true, 'message' => 'Код отправлен в WhatsApp']);
                break;

            case 'verify_code':
                $code = $_POST['code'];

                if (!isset($_SESSION['auth_code']) || !isset($_SESSION['auth_time'])) {
                    throw new Exception('Код не был отправлен');
                }

                if (time() - $_SESSION['auth_time'] > 300) {
                    throw new Exception('Код истек. Запросите новый код');
                }

                if ($code !== $_SESSION['auth_code']) {
                    throw new Exception('Неверный код');
                }

                $sql = "SELECT * FROM users WHERE phone = '" . $_SESSION['auth_phone'] . "'";
                $res = $db->query($sql);
                $user = $res->fetch_assoc();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['user_name'] = trim($user['name'] . ' ' . $user['famale']);
                $_SESSION['is_admin'] = ($user['phone'] === $ADMIN_PHONE);
                $_SESSION['authenticated'] = true;

                unset($_SESSION['auth_code'], $_SESSION['auth_time'], $_SESSION['auth_phone']);

                echo json_encode([
                    'success' => true,
                    'message' => 'Добро пожаловать в аукцион!',
                    'user' => [
                        'name' => $_SESSION['user_name'],
                        'is_admin' => $_SESSION['is_admin']
                    ]
                ]);
                break;

            case 'make_bid':
                if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                    throw new Exception('Не авторизован');
                }

                $auctionData = getAuctionData($db);

                if (!$auctionData['auction_active']) {
                    throw new Exception('Аукцион приостановлен администратором');
                }

                $bidAmount = intval($_POST['bid_amount']);
                $userPhone = $_SESSION['user_phone'];

                $userBidsCount = getUserBidsCount($db, $userPhone);
                if ($userBidsCount >= $MAX_BIDS_PER_USER) {
                    throw new Exception("Вы уже сделали максимум $MAX_BIDS_PER_USER ставок");
                }

                if ($bidAmount <= $auctionData['current_bid']) {
                    throw new Exception('Ставка должна быть больше текущей');
                }

                if ($bidAmount > $MAX_PRICE) {
                    throw new Exception("Максимальная ставка " . number_format($MAX_PRICE, 0, '', ' ') . " тенге");
                }

                if ($bidAmount !== ($auctionData['current_bid'] + $BID_STEP)) {
                    $requiredBid = $auctionData['current_bid'] + $BID_STEP;
                    throw new Exception("Ставка должна быть точно " . number_format($requiredBid, 0, '', ' ') . " тенге (на " . number_format($BID_STEP, 0, '', ' ') . " тенге больше текущей)");
                }

                saveAuctionBid($db, $bidAmount, $userPhone, $_SESSION['user_name']);

                echo json_encode([
                    'success' => true,
                    'message' => '🎉 Ставка принята! Вы лидируете!',
                    'bids_left' => $MAX_BIDS_PER_USER - ($userBidsCount + 1)
                ]);
                break;

            case 'get_status':
                $auctionData = getAuctionData($db);
                $userPhone = isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '';
                $userBidsCount = getUserBidsCount($db, $userPhone);

                echo json_encode([
                    'success' => true,
                    'current_bid' => $auctionData['current_bid'],
                    'current_winner' => $auctionData['current_winner'],
                    'current_winner_phone' => $auctionData['current_winner_phone'],
                    'auction_active' => $auctionData['auction_active'],
                    'user_bids_left' => $MAX_BIDS_PER_USER - $userBidsCount,
                    'bid_history' => array_slice($auctionData['bid_history'], 0, 10),
                    'last_update' => $auctionData['last_update'],
                    'next_bid_amount' => $auctionData['current_bid'] + $BID_STEP
                ]);
                break;

            case 'toggle_auction':
                if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                    throw new Exception('Нет прав доступа');
                }

                $newStatus = toggleAuctionStatus($db);
                $status = $newStatus ? 'возобновлен' : 'приостановлен';

                echo json_encode([
                    'success' => true,
                    'message' => "Аукцион $status",
                    'auction_active' => $newStatus
                ]);
                break;

            case 'logout':
                session_destroy();
                echo json_encode(['success' => true, 'message' => 'Вы вышли из системы']);
                break;

            default:
                throw new Exception('Неизвестное действие');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>🎯 Аукцион ByFly Travel</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --light-bg: rgba(255, 255, 255, 0.95);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 20px;
            --border-radius-small: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            color: #2c3e50;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .app-container {
            max-width: 420px;
            margin: 0 auto;
            min-height: 100vh;
            background: var(--light-bg);
            position: relative;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
        }

        .app-header {
            background: var(--primary-gradient);
            padding: 60px 20px 30px;
            text-align: center;
            color: white;
            position: relative;
            border-radius: 0 0 30px 30px;
        }

        .app-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .app-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .app-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .logout-btn {
            position: absolute;
            top: 50px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: var(--border-radius-small);
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .app-content {
            padding: 20px;
            padding-bottom: 100px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e8ecef;
            border-radius: var(--border-radius-small);
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .country-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .country-btn {
            padding: 16px;
            border: 2px solid #e8ecef;
            background: #f8f9fa;
            border-radius: var(--border-radius-small);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .country-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .country-btn.active {
            border-color: #667eea;
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .phone-input-wrapper {
            position: relative;
        }

        .phone-prefix {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-weight: 700;
            z-index: 2;
        }

        .phone-input {
            padding-left: 70px;
        }

        .btn {
            width: 100%;
            padding: 16px 25px;
            border: none;
            border-radius: var(--border-radius-small);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: none;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover:before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-danger {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-warning {
            background: var(--warning-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .status-bar {
            background: var(--success-gradient);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius-small);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: var(--shadow);
        }

        .status-bar.paused {
            background: var(--danger-gradient);
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            animation: pulse 2s infinite;
        }

        .user-info {
            background: var(--dark-gradient);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius-small);
            margin-bottom: 20px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .user-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .bids-left {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }

        .current-bid {
            background: var(--secondary-gradient);
            color: white;
            padding: 30px 20px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .current-bid::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        .bid-amount {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .bid-winner {
            font-size: 1rem;
            opacity: 0.95;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .next-bid-info {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #2d3436;
            padding: 15px 20px;
            border-radius: var(--border-radius-small);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .bid-history {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }

        .bid-history::-webkit-scrollbar {
            width: 4px;
        }

        .bid-history::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .bid-history::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: var(--border-radius-small);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .history-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .history-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
        }

        .history-user {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            margin-top: 3px;
        }

        .history-time {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-align: right;
        }

        .empty-state {
            text-align: center;
            color: #7f8c8d;
            padding: 40px 20px;
            font-size: 0.9rem;
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .admin-panel {
            background: var(--warning-gradient);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius-small);
            margin-bottom: 20px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .floating-buttons {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 1000;
        }

        .floating-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .floating-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        .floating-btn.info {
            background: var(--primary-gradient);
        }

        .floating-btn.rules {
            background: var(--secondary-gradient);
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            max-width: 400px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            position: relative;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
        }

        .tour-detail {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .tour-detail i {
            width: 20px;
            margin-right: 12px;
            color: #667eea;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .tour-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius-small);
            text-align: center;
            font-weight: 600;
            margin: 20px 0;
        }

        .rule-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: var(--border-radius-small);
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .rule-number {
            background: var(--primary-gradient);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius-small);
            margin-bottom: 15px;
            font-weight: 500;
            position: relative;
            animation: slideInDown 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loader-text {
            color: white;
            margin-top: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes shimmer {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Адаптивность для очень маленьких экранов */
        @media (max-width: 380px) {
            .app-container {
                max-width: 100%;
            }

            .app-content {
                padding: 15px;
            }

            .card {
                padding: 20px;
            }

            .bid-amount {
                font-size: 2rem;
            }

            .floating-buttons {
                bottom: 15px;
            }

            .floating-btn {
                width: 50px;
                height: 50px;
                font-size: 1rem;
            }
        }

        /* Улучшения для iOS Safari */
        @supports (-webkit-touch-callout: none) {
            .app-container {
                min-height: -webkit-fill-available;
            }
        }

        /* Скрытие элементов по умолчанию */
        .auth-section {
            display: block;
        }

        .auction-section {
            display: none;
        }

        /* Стили для темной темы (автоматическое определение) */
        @media (prefers-color-scheme: dark) {
            .card {
                background: rgba(255, 255, 255, 0.98);
            }

            .form-control {
                background: #f8f9fa;
            }
        }
    </style>
</head>

<body>
    <!-- Загрузчик -->
    <div class="loader-overlay" id="loaderOverlay">
        <div style="text-align: center;">
            <div class="loader"></div>
            <div class="loader-text">Загрузка...</div>
        </div>
    </div>

    <!-- Основной контейнер приложения -->
    <div class="app-container">
        <!-- Шапка приложения -->
        <div class="app-header">
            <h1 class="app-title">🎯 Аукцион ByFly</h1>
            <p class="app-subtitle">Эксклюзивный тур для агентов</p>

            <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            <?php endif; ?>
        </div>

        <!-- Контент приложения -->
        <div class="app-content">
            <!-- Уведомления -->
            <div id="alerts"></div>

            <!-- Секция авторизации -->
            <div class="auth-section" id="authSection">
                <div class="card">
                    <h2 class="section-title">
                        <i class="fas fa-lock"></i> Вход для агентов
                    </h2>

                    <div class="form-group">
                        <label class="form-label">Выберите страну:</label>
                        <div class="country-selector">
                            <button type="button" class="country-btn active" onclick="selectCountry('kz')">
                                🇰🇿 Казахстан
                            </button>
                            <button type="button" class="country-btn" onclick="selectCountry('uz')">
                                🇺🇿 Узбекистан
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Номер телефона:</label>
                        <div class="phone-input-wrapper">
                            <span class="phone-prefix" id="phonePrefix">+7</span>
                            <input type="tel" id="phoneInput" class="form-control phone-input"
                                placeholder="777 123 45 67" maxlength="15">
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="sendCode()" id="sendCodeBtn">
                        <i class="fas fa-paper-plane"></i> Получить код
                    </button>

                    <div class="form-group" id="codeGroup" style="display: none; margin-top: 20px;">
                        <label class="form-label">Код из WhatsApp:</label>
                        <input type="text" id="codeInput" class="form-control" placeholder="123456" maxlength="6">
                        <button type="button" class="btn btn-success" onclick="verifyCode()" id="verifyCodeBtn"
                            style="margin-top: 15px;">
                            <i class="fas fa-check"></i> Войти в аукцион
                        </button>
                    </div>
                </div>
            </div>

            <!-- Секция аукциона -->
            <div class="auction-section" id="auctionSection">
                <!-- Информация о пользователе -->
                <div class="user-info">
                    <div class="user-name">
                        <i class="fas fa-user-circle"></i> <span id="userName"></span>
                    </div>
                    <div class="bids-left">
                        <i class="fas fa-hand-paper"></i>
                        Ставок осталось: <span id="bidsLeft">2</span>
                    </div>
                </div>

                <!-- Статус аукциона -->
                <div class="status-bar" id="statusBar">
                    <span class="status-indicator"></span>
                    <span id="statusText">Аукцион активен</span>
                </div>

                <!-- Админ панель -->
                <div class="admin-panel" id="adminPanel" style="display: none;">
                    <h3><i class="fas fa-crown"></i> Управление аукционом</h3>
                    <button type="button" class="btn" id="toggleAuctionBtn" onclick="toggleAuction()"
                        style="margin-top: 15px;">
                        <span id="adminStatusText">Приостановить аукцион</span>
                    </button>
                </div>

                <!-- Текущая ставка -->
                <div class="current-bid">
                    <div class="bid-amount" id="currentBid">10 000 ₸</div>
                    <div class="bid-winner">
                        <span id="currentWinner">
                            <i class="fas fa-clock"></i> Ставок пока нет
                        </span>
                    </div>
                </div>

                <!-- Форма ставки -->
                <div class="card">
                    <h3 class="section-title">
                        <i class="fas fa-gavel"></i> Сделать ставку
                    </h3>

                    <div class="next-bid-info">
                        <i class="fas fa-info-circle"></i>
                        Следующая ставка: <strong><span id="nextBidAmount">20 000 ₸</span></strong>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Сумма ставки:</label>
                        <input type="number" id="bidAmount" class="form-control" readonly>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="makeBid()" id="makeBidBtn">
                        <i class="fas fa-gavel"></i> Сделать ставку
                    </button>
                </div>

                <!-- История ставок -->
                <div class="card">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i> История ставок
                    </h3>
                    <div class="bid-history" id="bidHistory">
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <div>Ставок пока нет</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Плавающие кнопки -->
        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
            <div class="floating-buttons">
                <button class="floating-btn info" onclick="showTourInfo()" title="Информация о туре">
                    <i class="fas fa-plane"></i>
                </button>
                <button class="floating-btn rules" onclick="showRules()" title="Правила аукциона">
                    <i class="fas fa-book"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно с информацией о туре -->
    <div class="modal" id="tourModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">🌴 Информация о туре</h3>
                <button class="modal-close" onclick="closeTourModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="tour-highlight">
                    <h4>🏝️ Таиланд — Паттайя</h4>
                    <p>21 ночь незабываемого отдыха</p>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-hotel"></i>
                    <div>
                        <strong>Отель:</strong><br>
                        Sawasdee Siam 2* (Central Pattaya)
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <strong>Даты:</strong><br>
                        20.09.2025 – 11.10.2025 (21 ночь)
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-utensils"></i>
                    <div>
                        <strong>Питание:</strong><br>
                        Room Only (без питания)
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-plane"></i>
                    <div>
                        <strong>Авиакомпания:</strong><br>
                        Air Astana (A-321 LR)
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Вылет:</strong><br>
                        20.09.2025 Алматы (00:15) → Бангкок (08:55)
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-undo"></i>
                    <div>
                        <strong>Возврат:</strong><br>
                        11.10.2025 Бангкок (10:15) → Алматы (14:55)
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-suitcase"></i>
                    <div>
                        <strong>Багаж:</strong><br>
                        23 кг + ручная кладь 8 кг
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-bus"></i>
                    <div>
                        <strong>Трансфер:</strong><br>
                        Групповой аэропорт ↔️ отель
                    </div>
                </div>

                <div class="tour-detail">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong>Страхование:</strong><br>
                        Медицинская страховка \$10,000
                    </div>
                </div>

                <div class="tour-highlight" style="margin-top: 20px;">
                    🔥 Эксклюзивный тур для самых смелых агентов!
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно с правилами -->
    <div class="modal" id="rulesModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">📋 Правила аукциона</h3>
                <button class="modal-close" onclick="closeRulesModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">1</span>
                        <div>
                            <strong>Участники:</strong> Только агенты ByFly Travel с активным статусом
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">2</span>
                        <div>
                            <strong>Лимит ставок:</strong> Каждый участник может сделать максимум 2 ставки
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">3</span>
                        <div>
                            <strong>Шаг ставки:</strong> Строго 10,000 тенге к текущей ставке
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">4</span>
                        <div>
                            <strong>Стартовая цена:</strong> 10,000 тенге
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">5</span>
                        <div>
                            <strong>Максимальная ставка:</strong> 300,000 тенге
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">6</span>
                        <div>
                            <strong>Победитель:</strong> Участник с последней действительной ставкой
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <div style="display: flex; align-items: flex-start;">
                        <span class="rule-number">7</span>
                        <div>
                            <strong>Оплата:</strong> Победитель оплачивает свою ставку за тур
                        </div>
                    </div>
                </div>

                <div class="tour-highlight" style="margin-top: 20px;">
                    ⚡ Аукцион может быть приостановлен администратором
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentCountry = 'kz';
        let updateInterval;
        let lastUpdate = 0;
        let nextBidAmount = 20000;

        // Инициализация
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                showAuctionSection();
                startUpdates();
            <?php endif; ?>

            setupEventListeners();
            setupModalEvents();
        });

        // Настройка обработчиков событий
        function setupEventListeners() {
            // Форматирование номера телефона
            document.getElementById('phoneInput').addEventListener('input', formatPhoneNumber);

            // Enter для отправки кода
            document.getElementById('phoneInput').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') sendCode();
            });

            // Enter для проверки кода
            document.getElementById('codeInput').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') verifyCode();
            });

            // Только цифры в коде
            document.getElementById('codeInput').addEventListener('input', function (e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            // Enter для ставки
            document.getElementById('bidAmount').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') makeBid();
            });

            // Автозаполнение правильной суммы при фокусе
            document.getElementById('bidAmount').addEventListener('focus', function (e) {
                if (nextBidAmount) {
                    e.target.value = nextBidAmount;
                }
            });
        }

        // Настройка событий модальных окон
        function setupModalEvents() {
            // Закрытие модальных окон при клике вне их
            document.getElementById('tourModal').addEventListener('click', function (e) {
                if (e.target === this) closeTourModal();
            });

            document.getElementById('rulesModal').addEventListener('click', function (e) {
                if (e.target === this) closeRulesModal();
            });

            // Закрытие по Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeTourModal();
                    closeRulesModal();
                }
            });
        }

        // Выбор страны
        function selectCountry(country) {
            currentCountry = country;

            document.querySelectorAll('.country-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            const phonePrefix = document.getElementById('phonePrefix');
            const phoneInput = document.getElementById('phoneInput');

            if (country === 'kz') {
                phonePrefix.textContent = '+7';
                phoneInput.placeholder = '777 123 45 67';
                phoneInput.maxLength = '13';
            } else {
                phonePrefix.textContent = '+998';
                phoneInput.placeholder = '90 123 45 67';
                phoneInput.maxLength = '12';
            }

            phoneInput.value = '';
        }

        // Форматирование номера телефона
        function formatPhoneNumber(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (currentCountry === 'kz') {
                if (value.length > 3 && value.length <= 6) {
                    value = value.slice(0, 3) + ' ' + value.slice(3);
                } else if (value.length > 6 && value.length <= 8) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
                } else if (value.length > 8) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 8) + ' ' + value.slice(8, 10);
                }
            } else {
                if (value.length > 2 && value.length <= 5) {
                    value = value.slice(0, 2) + ' ' + value.slice(2);
                } else if (value.length > 5 && value.length <= 7) {
                    value = value.slice(0, 2) + ' ' + value.slice(2, 5) + ' ' + value.slice(5);
                } else if (value.length > 7) {
                    value = value.slice(0, 2) + ' ' + value.slice(2, 5) + ' ' + value.slice(5, 7) + ' ' + value.slice(7, 9);
                }
            }

            e.target.value = value;
        }

        // Отправка кода
        async function sendCode() {
            const phoneInput = document.getElementById('phoneInput');
            const phone = phoneInput.value.replace(/\D/g, '');

            if (!phone) {
                showAlert('Введите номер телефона', 'danger');
                return;
            }

            const fullPhone = currentCountry === 'kz' ? '7' + phone : '998' + phone;

            showLoader(true);
            disableButton('sendCodeBtn');

            try {
                const response = await axios.post('', {
                    action: 'send_code',
                    phone: fullPhone,
                    country: currentCountry
                });

                if (response.data.success) {
                    showAlert(response.data.message, 'success');
                    document.getElementById('codeGroup').style.display = 'block';
                    document.getElementById('codeInput').focus();

                    // Добавляем анимацию появления
                    const codeGroup = document.getElementById('codeGroup');
                    codeGroup.style.animation = 'fadeInUp 0.5s ease';
                } else {
                    showAlert(response.data.message, 'danger');
                }
            } catch (error) {
                showAlert('Ошибка отправки кода', 'danger');
            } finally {
                showLoader(false);
                enableButton('sendCodeBtn');
            }
        }

        // Проверка кода
        async function verifyCode() {
            const code = document.getElementById('codeInput').value;

            if (!code || code.length !== 6) {
                showAlert('Введите 6-значный код', 'danger');
                return;
            }

            showLoader(true);
            disableButton('verifyCodeBtn');

            try {
                const response = await axios.post('', {
                    action: 'verify_code',
                    code: code
                });

                if (response.data.success) {
                    showAlert(response.data.message, 'success');
                    document.getElementById('userName').textContent = response.data.user.name;

                    setTimeout(() => {
                        showAuctionSection();
                        startUpdates();
                    }, 1000);
                } else {
                    showAlert(response.data.message, 'danger');
                }
            } catch (error) {
                showAlert('Ошибка проверки кода', 'danger');
            } finally {
                showLoader(false);
                enableButton('verifyCodeBtn');
            }
        }

        // Показать секцию аукциона
        function showAuctionSection() {
            const authSection = document.getElementById('authSection');
            const auctionSection = document.getElementById('auctionSection');

            authSection.style.animation = 'fadeInUp 0.5s ease reverse';
            setTimeout(() => {
                authSection.style.display = 'none';
                auctionSection.style.display = 'block';
                auctionSection.style.animation = 'fadeInUp 0.5s ease';
            }, 300);

            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                document.getElementById('adminPanel').style.display = 'block';
            <?php endif; ?>

            updateStatus();
        }

        // Обновление статуса
        async function updateStatus() {
            try {
                const response = await axios.post('', {
                    action: 'get_status'
                });

                if (response.data.success) {
                    const data = response.data;

                    // Обновляем текущую ставку
                    document.getElementById('currentBid').textContent = formatPrice(data.current_bid);

                    if (data.current_winner) {
                        document.getElementById('currentWinner').innerHTML =
                            `<i class="fas fa-trophy"></i> Лидер: ${data.current_winner}`;
                    } else {
                        document.getElementById('currentWinner').innerHTML =
                            '<i class="fas fa-clock"></i> Ставок пока нет';
                    }

                    // Обновляем следующую ставку
                    nextBidAmount = data.next_bid_amount;
                    document.getElementById('nextBidAmount').textContent = formatPrice(nextBidAmount);
                    document.getElementById('bidAmount').value = nextBidAmount;

                    // Обновляем оставшиеся ставки
                    document.getElementById('bidsLeft').textContent = data.user_bids_left;

                    // Обновляем статус аукциона
                    updateAuctionStatus(data.auction_active);

                    // Блокируем кнопку если нужно
                    const makeBidBtn = document.getElementById('makeBidBtn');
                    const bidAmountInput = document.getElementById('bidAmount');

                    if (data.user_bids_left <= 0 || !data.auction_active) {
                        makeBidBtn.disabled = true;
                        bidAmountInput.disabled = true;

                        if (data.user_bids_left <= 0) {
                            makeBidBtn.innerHTML = '<i class="fas fa-ban"></i> Лимит ставок исчерпан';
                            makeBidBtn.className = 'btn btn-danger';
                        } else if (!data.auction_active) {
                            makeBidBtn.innerHTML = '<i class="fas fa-pause"></i> Аукцион приостановлен';
                            makeBidBtn.className = 'btn btn-warning';
                        }
                    } else {
                        makeBidBtn.disabled = false;
                        bidAmountInput.disabled = false;
                        makeBidBtn.innerHTML = '<i class="fas fa-gavel"></i> Сделать ставку';
                        makeBidBtn.className = 'btn btn-primary';
                    }

                    // Обновляем админ панель
                    updateAdminPanel(data.auction_active);

                    // Обновляем историю
                    updateBidHistory(data.bid_history);
                }
            } catch (error) {
                console.error('Ошибка обновления статуса:', error);
            }
        }

        // Обновление статуса аукциона
        function updateAuctionStatus(isActive) {
            const statusBar = document.getElementById('statusBar');
            const statusText = document.getElementById('statusText');

            if (isActive) {
                statusBar.className = 'status-bar';
                statusText.innerHTML = '<i class="fas fa-play-circle"></i> Аукцион активен';
            } else {
                statusBar.className = 'status-bar paused';
                statusText.innerHTML = '<i class="fas fa-pause-circle"></i> Аукцион приостановлен';
            }
        }

        // Обновление админ панели
        function updateAdminPanel(isActive) {
            const adminStatusText = document.getElementById('adminStatusText');
            const toggleBtn = document.getElementById('toggleAuctionBtn');

            if (adminStatusText && toggleBtn) {
                if (isActive) {
                    adminStatusText.textContent = 'Приостановить аукцион';
                    toggleBtn.className = 'btn btn-danger';
                } else {
                    adminStatusText.textContent = 'Возобновить аукцион';
                    toggleBtn.className = 'btn btn-success';
                }
            }
        }

        // Обновление истории ставок
        function updateBidHistory(history) {
            const historyDiv = document.getElementById('bidHistory');

            if (!history || history.length === 0) {
                historyDiv.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <div>Ставок пока нет</div>
                    </div>
                `;
                return;
            }

            historyDiv.innerHTML = history.map((bid, index) => `
                <div class="history-item" style="animation: fadeInUp 0.3s ease ${index * 0.1}s both;">
                    <div>
                        <div class="history-amount">${formatPrice(bid.amount)}</div>
                        <div class="history-user">
                            <i class="fas fa-user"></i> ${bid.user}
                        </div>
                    </div>
                    <div class="history-time">
                        <i class="fas fa-clock"></i><br>
                        ${formatDateTime(bid.time)}
                    </div>
                </div>
            `).join('');
        }

        // Сделать ставку
        async function makeBid() {
            const bidAmount = parseInt(document.getElementById('bidAmount').value);

            if (!bidAmount) {
                showAlert('Введите сумму ставки', 'danger');
                return;
            }

            if (bidAmount !== nextBidAmount) {
                showAlert(`Ставка должна быть точно ${formatPrice(nextBidAmount)}`, 'danger');
                return;
            }

            showLoader(true);
            disableButton('makeBidBtn');

            try {
                const response = await axios.post('', {
                    action: 'make_bid',
                    bid_amount: bidAmount
                });

                if (response.data.success) {
                    showAlert(response.data.message, 'success');

                    // Немедленно обновляем статус
                    await updateStatus();

                    // Анимация успешной ставки
                    const currentBidCard = document.querySelector('.current-bid');
                    currentBidCard.style.transform = 'scale(1.05)';
                    currentBidCard.classList.add('pulse-animation');

                    setTimeout(() => {
                        currentBidCard.style.transform = 'scale(1)';
                        currentBidCard.classList.remove('pulse-animation');
                    }, 1000);

                    // Эффекты
                    playSuccessSound();
                    showConfetti();
                    vibrate([100, 50, 100]);

                } else {
                    showAlert(response.data.message, 'danger');
                    vibrate([200]);
                }
            } catch (error) {
                showAlert('Ошибка при размещении ставки', 'danger');
                vibrate([200]);
            } finally {
                showLoader(false);
                enableButton('makeBidBtn');
            }
        }

        // Переключение состояния аукциона (только для админа)
        async function toggleAuction() {
            showLoader(true);
            disableButton('toggleAuctionBtn');

            try {
                const response = await axios.post('', {
                    action: 'toggle_auction'
                });

                if (response.data.success) {
                    showAlert(response.data.message, 'success');
                    await updateStatus();
                } else {
                    showAlert(response.data.message, 'danger');
                }
            } catch (error) {
                showAlert('Ошибка изменения статуса аукциона', 'danger');
            } finally {
                showLoader(false);
                enableButton('toggleAuctionBtn');
            }
        }

        // Показать модальное окно с информацией о туре
        function showTourInfo() {
            const modal = document.getElementById('tourModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Анимация появления
            const modalContent = modal.querySelector('.modal-content');
            modalContent.style.animation = 'modalSlideIn 0.3s ease';
        }

        // Закрыть модальное окно с информацией о туре
        function closeTourModal() {
            const modal = document.getElementById('tourModal');
            const modalContent = modal.querySelector('.modal-content');

            modalContent.style.animation = 'modalSlideIn 0.3s ease reverse';
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Показать модальное окно с правилами
        function showRules() {
            const modal = document.getElementById('rulesModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Анимация появления
            const modalContent = modal.querySelector('.modal-content');
            modalContent.style.animation = 'modalSlideIn 0.3s ease';
        }

        // Закрыть модальное окно с правилами
        function closeRulesModal() {
            const modal = document.getElementById('rulesModal');
            const modalContent = modal.querySelector('.modal-content');

            modalContent.style.animation = 'modalSlideIn 0.3s ease reverse';
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Выход
        async function logout() {
            if (confirm('Вы уверены, что хотите выйти из аукциона?')) {
                showLoader(true);

                try {
                    await axios.post('', {
                        action: 'logout'
                    });
                } catch (error) {
                    console.error('Ошибка выхода:', error);
                } finally {
                    stopUpdates();
                    location.reload();
                }
            }
        }

        // Запуск автообновления
        function startUpdates() {
            updateStatus();
            updateInterval = setInterval(updateStatus, 2000); // Каждые 2 секунды для мобильных
        }

        // Остановка автообновления
        function stopUpdates() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }

        // Показать уведомление
        function showAlert(message, type) {
            const alertsDiv = document.getElementById('alerts');
            const alertId = 'alert_' + Date.now();

            const iconMap = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-circle'
            };

            const alertHtml = `
                <div id="${alertId}" class="alert alert-${type}">
                    <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;

            alertsDiv.insertAdjacentHTML('beforeend', alertHtml);

            // Автоматическое скрытие через 4 секунды
            setTimeout(() => {
                const alertElement = document.getElementById(alertId);
                if (alertElement) {
                    alertElement.style.opacity = '0';
                    alertElement.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alertElement.remove();
                    }, 300);
                }
            }, 4000);
        }

        // Показать/скрыть загрузку
        function showLoader(show) {
            const loader = document.getElementById('loaderOverlay');
            if (show) {
                loader.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            } else {
                loader.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Отключить кнопку
        function disableButton(buttonId) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = true;
                button.style.opacity = '0.6';
            }
        }

        // Включить кнопку
        function enableButton(buttonId) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = false;
                button.style.opacity = '1';
            }
        }

        // Форматирование цены
        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₸';
        }

        // Форматирование даты и времени
        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);

            if (diffMins < 1) {
                return 'только что';
            } else if (diffMins < 60) {
                return `${diffMins} мин назад`;
            } else if (diffHours < 24) {
                return `${diffHours} ч назад`;
            } else {
                return date.toLocaleString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }

        // Звуковые эффекты
        function playSuccessSound() {
            if ('AudioContext' in window) {
                try {
                    const audioContext = new AudioContext();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);

                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.3);

                    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.3);
                } catch (error) {
                    console.log('Звук недоступен');
                }
            }
        }

        // Анимация конфетти
        function showConfetti() {
            const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#74b9ff', '#00cec9'];

            for (let i = 0; i < 30; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        left: ${Math.random() * 100}vw;
                        top: -10px;
                        width: 8px;
                        height: 8px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 10000;
                        animation: confettiFall 3s linear forwards;
                    `;

                    document.body.appendChild(confetti);

                    setTimeout(() => {
                        confetti.remove();
                    }, 3000);
                }, i * 100);
            }
        }

        // Функция вибрации для мобильных устройств
        function vibrate(pattern = [200]) {
            if ('vibrate' in navigator) {
                navigator.vibrate(pattern);
            }
        }

        // Обработка видимости страницы
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopUpdates();
            } else {
                <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                    startUpdates();
                <?php endif; ?>
            }
        });

        // Обработка закрытия страницы
        window.addEventListener('beforeunload', function () {
            stopUpdates();
        });

        // Настройка axios
        axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
        axios.defaults.transformRequest = [function (data) {
            if (typeof data === 'object' && data !== null) {
                return Object.keys(data).map(key =>
                    encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
                ).join('&');
            }
            return data;
        }];

        // Обработка ошибок axios
        axios.interceptors.response.use(
            response => response,
            error => {
                console.error('Axios error:', error);
                showAlert('Ошибка соединения с сервером', 'danger');
                return Promise.reject(error);
            }
        );

        // Обработка ошибок JavaScript
        window.addEventListener('error', function (e) {
            console.error('JavaScript error:', e.error);
            showAlert('Произошла техническая ошибка', 'danger');
        });

        // Обработка потери соединения
        window.addEventListener('offline', function () {
            showAlert('Соединение потеряно. Проверьте интернет.', 'warning');
            stopUpdates();
        });

        // Обработка восстановления соединения
        window.addEventListener('online', function () {
            showAlert('Соединение восстановлено', 'success');
            <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                startUpdates();
            <?php endif; ?>
        });

        // Предотвращение случайного обновления страницы
        window.addEventListener('beforeunload', function (e) {
            <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                e.preventDefault();
                e.returnValue = 'Вы уверены, что хотите покинуть аукцион?';
                return e.returnValue;
            <?php endif; ?>
        });

        // Обработка жестов для мобильных устройств
        let touchStartY = 0;
        let touchEndY = 0;

        document.addEventListener('touchstart', function (e) {
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        document.addEventListener('touchend', function (e) {
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartY - touchEndY;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe up - обновить статус
                    <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                        updateStatus();
                        showAlert('Статус обновлен', 'success');
                    <?php endif; ?>
                } else {
                    // Swipe down - показать информацию о туре
                    <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                        showTourInfo();
                    <?php endif; ?>
                }
            }
        }

        // Добавляем CSS анимацию для конфетти
        const confettiStyle = document.createElement('style');
        confettiStyle.textContent = `
            @keyframes confettiFall {
                0% {
                    transform: translateY(-10px) rotate(0deg);
                    opacity: 1;
                }
                100% {
                    transform: translateY(100vh) rotate(720deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(confettiStyle);

        // Функция для проверки поддержки уведомлений
        function checkNotificationSupport() {
            if ('Notification' in window) {
                if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            }
        }

        // Отправка push-уведомления
        function sendNotification(title, body, icon = '/favicon.ico') {
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: body,
                    icon: icon,
                    badge: icon,
                    tag: 'auction-notification',
                    requireInteraction: true,
                    vibrate: [200, 100, 200]
                });

                notification.onclick = function () {
                    window.focus();
                    notification.close();
                };

                setTimeout(() => {
                    notification.close();
                }, 5000);
            }
        }

        // Проверяем поддержку уведомлений при загрузке
        checkNotificationSupport();

        // Функция для отслеживания активности пользователя
        let userActive = true;
        let inactivityTimer;

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            userActive = true;

            inactivityTimer = setTimeout(() => {
                userActive = false;
            }, 30000); // 30 секунд неактивности
        }

        // Отслеживание активности
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer, { passive: true });
        });

        resetInactivityTimer();

        // Модифицированная функция обновления статуса с уведомлениями
        let lastBidAmount = 0;
        let lastWinner = '';

        async function updateStatusWithNotifications() {
            try {
                const response = await axios.post('', {
                    action: 'get_status'
                });

                if (response.data.success) {
                    const data = response.data;

                    // Проверяем изменения для уведомлений
                    if (data.current_bid > lastBidAmount && lastBidAmount > 0) {
                        if (!userActive) {
                            sendNotification(
                                '🎯 Новая ставка в аукционе!',
                                `${data.current_winner} сделал ставку ${formatPrice(data.current_bid)}`
                            );
                        }

                        // Вибрация при новой ставке
                        vibrate([100, 50, 100, 50, 100]);
                    }

                    lastBidAmount = data.current_bid;
                    lastWinner = data.current_winner || '';

                    // Обновляем интерфейс (используем существующую функцию)
                    updateInterfaceWithData(data);
                }
            } catch (error) {
                console.error('Ошибка обновления статуса:', error);
            }
        }

        // Функция обновления интерфейса
        function updateInterfaceWithData(data) {
            // Обновляем текущую ставку
            document.getElementById('currentBid').textContent = formatPrice(data.current_bid);

            if (data.current_winner) {
                document.getElementById('currentWinner').innerHTML =
                    `<i class="fas fa-trophy"></i> Лидер: ${data.current_winner}`;
            } else {
                document.getElementById('currentWinner').innerHTML =
                    '<i class="fas fa-clock"></i> Ставок пока нет';
            }

            // Обновляем следующую ставку
            nextBidAmount = data.next_bid_amount;
            document.getElementById('nextBidAmount').textContent = formatPrice(nextBidAmount);
            document.getElementById('bidAmount').value = nextBidAmount;

            // Обновляем оставшиеся ставки
            document.getElementById('bidsLeft').textContent = data.user_bids_left;

            // Обновляем статус аукциона
            updateAuctionStatus(data.auction_active);

            // Блокируем кнопку если нужно
            const makeBidBtn = document.getElementById('makeBidBtn');
            const bidAmountInput = document.getElementById('bidAmount');

            if (data.user_bids_left <= 0 || !data.auction_active) {
                makeBidBtn.disabled = true;
                bidAmountInput.disabled = true;

                if (data.user_bids_left <= 0) {
                    makeBidBtn.innerHTML = '<i class="fas fa-ban"></i> Лимит ставок исчерпан';
                    makeBidBtn.className = 'btn btn-danger';
                } else if (!data.auction_active) {
                    makeBidBtn.innerHTML = '<i class="fas fa-pause"></i> Аукцион приостановлен';
                    makeBidBtn.className = 'btn btn-warning';
                }
            } else {
                makeBidBtn.disabled = false;
                bidAmountInput.disabled = false;
                makeBidBtn.innerHTML = '<i class="fas fa-gavel"></i> Сделать ставку';
                makeBidBtn.className = 'btn btn-primary';
            }

            // Обновляем админ панель
            updateAdminPanel(data.auction_active);

            // Обновляем историю
            updateBidHistory(data.bid_history);
        }

        // Переопределяем функцию запуска обновлений
        function startUpdates() {
            updateStatusWithNotifications();
            updateInterval = setInterval(updateStatusWithNotifications, 2000);
        }

        // Функция для копирования ссылки на аукцион
        function copyAuctionLink() {
            const url = window.location.href;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(() => {
                    showAlert('Ссылка скопирована в буфер обмена', 'success');
                });
            } else {
                // Fallback для старых браузеров
                const textArea = document.createElement('textarea');
                textArea.value = url;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    document.execCommand('copy');
                    showAlert('Ссылка скопирована в буфер обмена', 'success');
                } catch (err) {
                    showAlert('Не удалось скопировать ссылку', 'danger');
                }

                document.body.removeChild(textArea);
            }
        }

        // Функция для отправки ссылки в WhatsApp
        function shareToWhatsApp() {
            const url = window.location.href;
            const text = encodeURIComponent(`🎯 Присоединяйся к аукциону ByFly Travel!\n\n🏝️ Разыгрываем тур в Таиланд на 21 ночь!\n\n${url}`);
            const whatsappUrl = `https://wa.me/?text=${text}`;

            window.open(whatsappUrl, '_blank');
        }

        // Добавляем кнопки поделиться (только для авторизованных)
        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const floatingButtons = document.querySelector('.floating-buttons');
                if (floatingButtons) {
                    floatingButtons.innerHTML += `
                    <button class="floating-btn" onclick="copyAuctionLink()" title="Копировать ссылку" 
                            style="background: var(--secondary-gradient);">
                        <i class="fas fa-link"></i>
                    </button>
                    <button class="floating-btn" onclick="shareToWhatsApp()" title="Поделиться в WhatsApp"
                            style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);">
                        <i class="fab fa-whatsapp"></i>
                    </button>
                `;
                }
            });
        <?php endif; ?>

        // Функция для проверки соединения
        async function checkConnection() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=ping'
                });
                return response.ok;
            } catch (error) {
                return false;
            }
        }

        // Мониторинг соединения
        let connectionCheckInterval;
        let isOffline = false;

        function startConnectionMonitoring() {
            connectionCheckInterval = setInterval(async () => {
                const isConnected = await checkConnection();

                if (!isConnected && !isOffline) {
                    isOffline = true;
                    showAlert('Соединение потеряно. Попытка переподключения...', 'warning');
                    stopUpdates();
                } else if (isConnected && isOffline) {
                    isOffline = false;
                    showAlert('Соединение восстановлено', 'success');
                    <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                        startUpdates();
                    <?php endif; ?>
                }
            }, 5000); // Проверяем каждые 5 секунд
        }

        function stopConnectionMonitoring() {
            if (connectionCheckInterval) {
                clearInterval(connectionCheckInterval);
                connectionCheckInterval = null;
            }
        }

        // Запускаем мониторинг соединения
        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
            startConnectionMonitoring();
        <?php endif; ?>

        // Очистка при закрытии страницы
        window.addEventListener('beforeunload', function () {
            stopUpdates();
            stopConnectionMonitoring();
        });

        // Функция для показа статистики аукциона
        async function showAuctionStats() {
            try {
                const response = await axios.post('', {
                    action: 'get_auction_stats'
                });

                if (response.data.success) {
                    const stats = response.data.stats;

                    showAlert(`📊 Статистика: ${stats.total_participants} участников, ${stats.total_bids} ставок, средняя ставка ${formatPrice(stats.average_bid)}`, 'success');
                }
            } catch (error) {
                showAlert('Ошибка получения статистики', 'danger');
            }
        }

        // Добавляем обработчик для двойного тапа (показ статистики)
        let lastTap = 0;
        document.addEventListener('touchend', function (e) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;

            if (tapLength < 500 && tapLength > 0) {
                <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                    showAuctionStats();
                <?php endif; ?>
            }

            lastTap = currentTime;
        });

        // Функция для экспорта истории ставок (только для админа)
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            async function exportBidHistory() {
                try {
                    const response = await axios.post('', {
                        action: 'export_bid_history'
                    });

                    if (response.data.success) {
                        const dataStr = JSON.stringify(response.data.history, null, 2);
                        const dataBlob = new Blob([dataStr], { type: 'application/json' });

                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(dataBlob);
                        link.download = `auction_history_${new Date().toISOString().split('T')[0]}.json`;
                        link.click();

                        showAlert('История экспортирована', 'success');
                    }
                } catch (error) {
                    showAlert('Ошибка экспорта', 'danger');
                }
            }

            // Добавляем кнопку экспорта для админа
            document.addEventListener('DOMContentLoaded', function () {
                const adminPanel = document.getElementById('adminPanel');
                if (adminPanel && adminPanel.style.display !== 'none') {
                    adminPanel.innerHTML += `
                    <button type="button" class="btn btn-warning" onclick="exportBidHistory()" 
                            style="margin-top: 10px; width: 100%;">
                        <i class="fas fa-download"></i> Экспорт истории
                    </button>
                `;
                }
            });
        <?php endif; ?>

        console.log('🎯 Аукцион ByFly Travel загружен успешно!');
        console.log('📱 Версия: 1.0.0');
        console.log('🚀 Готов к работе!');
    </script>
</body>

</html>

<?php
// Закрываем соединение с базой данных
if (isset($db)) {
    $db->close();
}
?>