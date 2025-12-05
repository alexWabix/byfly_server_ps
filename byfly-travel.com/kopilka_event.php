<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Проверяем соединение с БД
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Функция для проверки статуса участника
function checkParticipantStatus($payments_count, $expected_payments)
{
    if ($payments_count < 2) {
        return ['status' => 'not_enough_payments', 'message' => 'Оплачено менее 2 месяцев'];
    }
    if ($payments_count < $expected_payments) {
        return ['status' => 'payment_delay', 'message' => 'Просрочен платеж за ' . ($expected_payments - $payments_count) . ' месяц(ев)'];
    }
    return ['status' => 'eligible', 'message' => 'Участвует в розыгрыше'];
}

// Функция для форматирования телефона
function formatPhoneNumber($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Определяем код страны по первым цифрам
    if (preg_match('/^7(0|7|4)/', $phone)) { // Казахстан
        return preg_replace('/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/', '+$1 ($2) $3-$4-$5', $phone);
    } elseif (preg_match('/^998/', $phone)) { // Узбекистан
        return preg_replace('/(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})/', '+$1 ($2) $3-$4-$5', $phone);
    } else {
        return $phone; // Возвращаем как есть, если не распознан
    }
}

// Обработка AJAX запроса на поиск пользователя
if (isset($_GET['search_phone'])) {
    $phone = preg_replace('/[^0-9]/', '', $_GET['search_phone']);

    if (empty($phone) || strlen($phone) < 5) {
        $response = [['status' => 'invalid', 'message' => 'Введите корректный номер телефона']];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $query = "SELECT 
        u.id, u.name, u.famale, u.phone, u.avatar,
        c.id as plan_id,
        c.date_create,
        c.summ_money,
        (
            (c.month_1_money >= 50000) +
            (c.month_2_money >= 50000) +
            (c.month_3_money >= 50000) +
            (c.month_4_money >= 50000) +
            (c.month_5_money >= 50000) +
            (c.month_6_money >= 50000) +
            (c.month_7_money >= 50000) +
            (c.month_8_money >= 50000) +
            (c.month_9_money >= 50000) +
            (c.month_10_money >= 50000) +
            (c.month_11_money >= 50000) +
            (c.month_12_money >= 50000)
        ) as payments_count
    FROM `byfly.2.0`.`users` u
    LEFT JOIN `byfly.2.0`.`copilka_ceils` c ON u.id = c.user_id AND c.date_dosrok_close IS NULL
    WHERE u.phone LIKE '%$phone%'";

    $result = $db->query($query);
    $response = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $now = new DateTime();
            $create_date = new DateTime($row['date_create']);
            $months_passed = $now->diff($create_date)->m + ($now->diff($create_date)->y * 12);
            $expected_payments = $months_passed + 1;

            $status = checkParticipantStatus($row['payments_count'], $expected_payments);

            $response[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'famale' => $row['famale'],
                'phone' => $row['phone'],
                'formatted_phone' => formatPhoneNumber($row['phone']),
                'avatar' => $row['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($row['name'] . ' ' . $row['famale']) . '&background=random&size=150',
                'plans_count' => $row['plan_id'] ? 1 : 0,
                'payments_count' => $row['payments_count'],
                'expected_payments' => $expected_payments,
                'status' => $status['status'],
                'message' => $status['message']
            ];
        }
    } else {
        $response[] = [
            'status' => 'not_found',
            'message' => 'Пользователь не найден или не участвует в программе накоплений'
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Получаем участников программы ранее бронирования (без просрочек и минимум 2 месяца оплачено)
$participants = [];
$query = "SELECT 
    u.id, u.name, u.famale, u.phone, u.avatar,
    c.id as plan_id,
    c.date_create,
    (
        (c.month_1_money >= 50000) +
        (c.month_2_money >= 50000) +
        (c.month_3_money >= 50000) +
        (c.month_4_money >= 50000) +
        (c.month_5_money >= 50000) +
        (c.month_6_money >= 50000) +
        (c.month_7_money >= 50000) +
        (c.month_8_money >= 50000) +
        (c.month_9_money >= 50000) +
        (c.month_10_money >= 50000) +
        (c.month_11_money >= 50000) +
        (c.month_12_money >= 50000)
    ) as payments_count
FROM `byfly.2.0`.`users` u
JOIN `byfly.2.0`.`copilka_ceils` c ON u.id = c.user_id
WHERE c.date_dosrok_close IS NULL";

$result = $db->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $now = new DateTime();
        $create_date = new DateTime($row['date_create']);
        $months_passed = $now->diff($create_date)->m + ($now->diff($create_date)->y * 12);
        $expected_payments = $months_passed + 1;

        if ($row['payments_count'] >= 2 && $row['payments_count'] >= $expected_payments) {
            if (!isset($participants[$row['id']])) {
                $participants[$row['id']] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'famale' => $row['famale'],
                    'phone' => $row['phone'],
                    'formatted_phone' => formatPhoneNumber($row['phone']),
                    'avatar' => $row['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($row['name'] . ' ' . $row['famale']) . '&background=random&size=150',
                    'plans_count' => 1,
                    'payments_count' => $row['payments_count'],
                    'expected_payments' => $expected_payments
                ];
            } else {
                $participants[$row['id']]['plans_count']++;
                $participants[$row['id']]['payments_count'] += $row['payments_count'];
            }
        }
    }

    $participants = array_values($participants);
}

// Обработка розыгрыша
$winner = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === 'alaniya2025') {
        // Создаем взвешенный массив участников
        $weighted_participants = [];
        foreach ($participants as $p) {
            // Каждый участник имеет количество записей, равное количеству его ячеек
            for ($i = 0; $i < $p['plans_count']; $i++) {
                $weighted_participants[] = $p;
            }
        }

        if (!empty($weighted_participants)) {
            // Улучшенный рандомайзер с использованием random_int
            $winner_index = random_int(0, count($weighted_participants) - 1);
            $winner = $weighted_participants[$winner_index];

            // Логируем результат розыгрыша
            $log_query = "INSERT INTO event_byfly_winners 
                (event_id, user_id, prize, date_win) 
                VALUES (1, {$winner['id']}, 'Путешествие в Аланью', NOW())";
            $db->query($log_query);

            // Отправляем уведомление в WhatsApp
            $message = "🎉 *Поздравляем, {$winner['name']} {$winner['famale']}!*\n\n" .
                "Вы выиграли *путешествие в Аланью* от ByFly Travel! 🏖️✈️\n\n" .
                "🎥 *Ссылка на эфир:* https://youtube.com/live/ZeAHAkZGNdU?feature=share\n\n" .
                "Наш менеджер свяжется с вами в ближайшее время для уточнения деталей.\n\n" .
                "С уважением,\nКоманда ByFly Travel";

            //sendWhatsapp($message, $winner['phone']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш ByFly Travel - Программа ранее бронирования</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Unbounded:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <style>
        :root {
            --primary: #e63946;
            --primary-dark: #9c1a24;
            --secondary: #f1faee;
            --dark: #1d3557;
            --light: #a8dadc;
            --accent: #457b9d;
            --gold: #FFD700;
            --white: #ffffff;
            --gray: #f8f9fa;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--gray);
            color: #333;
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Unbounded', sans-serif;
            font-weight: 700;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .gradient-bg::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1473&q=80') center/cover no-repeat;
            opacity: 0.15;
            z-index: 0;
        }

        .header {
            padding: 5rem 0 4rem;
            margin-bottom: 3rem;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .countdown {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 1.5rem 0;
            letter-spacing: 1px;
        }

        .countdown-number {
            background: rgba(255, 255, 255, 0.25);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            margin: 0 0.5rem;
            min-width: 60px;
            display: inline-block;
            text-align: center;
            backdrop-filter: blur(5px);
        }

        /* Стили для обратного отсчета розыгрыша */
        .draw-countdown {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .draw-countdown-number {
            font-size: 10rem;
            font-weight: 700;
            color: var(--gold);
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.8);
            margin: 1rem 0;
            animation: pulse 0.8s infinite alternate;
            font-family: 'Unbounded', sans-serif;
        }

        .draw-countdown-text {
            color: var(--white);
            font-size: 2.2rem;
            margin-bottom: 3rem;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(1.1);
                opacity: 0.9;
            }
        }

        /* Карточка участника */
        .participant-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: none;
            background: var(--white);
            position: relative;
            border-top: 4px solid var(--primary);
        }

        .participant-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .participant-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .participant-card:hover .participant-avatar {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .participant-badge {
            background: linear-gradient(135deg, var(--accent), var(--dark));
            color: var(--white);
            border-radius: 20px;
            padding: 0.35rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
            display: inline-flex;
            align-items: center;
        }

        .participant-badge i {
            margin-right: 0.3rem;
            font-size: 0.9rem;
        }

        .money-badge {
            background: linear-gradient(135deg, #28a745, #218838);
            color: var(--white);
        }

        /* Статистика */
        .stats-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.8rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            text-align: center;
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stats-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
            font-family: 'Unbounded', sans-serif;
        }

        .stats-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        /* Кнопки */
        .btn-draw {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            margin: 1.5rem 0;
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-draw:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(230, 57, 70, 0.4);
            color: var(--white);
        }

        .btn-draw::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn-draw:hover::before {
            left: 100%;
        }

        .youtube-btn {
            background: linear-gradient(135deg, #ff0000, #cc0000);
            color: var(--white);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 0, 0, 0.2);
            font-family: 'Unbounded', sans-serif;
        }

        .youtube-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 0, 0, 0.3);
            color: var(--white);
        }

        /* Информационный блок */
        .info-box {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--accent);
            position: relative;
            overflow: hidden;
        }

        .info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent), var(--dark));
        }

        .info-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        /* Поиск пользователей */
        .search-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            padding: 1rem 1.5rem;
            border-radius: 50px;
            border: 2px solid var(--light);
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            padding-right: 50px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(69, 123, 157, 0.2);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 5px;
            background: linear-gradient(135deg, var(--accent), var(--dark));
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: scale(1.05);
        }

        /* Результаты поиска */
        .search-result-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--accent);
            display: none;
        }

        .search-result-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .eligible-badge {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
        }

        .not-eligible-badge {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .not-found-badge {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        /* Модальные окна */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .password-content,
        .winner-content {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s;
            position: relative;
            overflow: hidden;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .winner-content {
            max-width: 600px;
        }

        .winner-avatar {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid var(--primary);
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .winner-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        /* Конфетти */
        .confetti {
            position: fixed;
            width: 12px;
            height: 12px;
            background-color: var(--primary);
            animation: confetti 5s ease-in-out forwards;
            z-index: 2000;
        }

        @keyframes confetti {
            0% {
                transform: translateY(-10vh) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(110vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* Адаптивные стили */
        @media (max-width: 992px) {
            .header {
                padding: 4rem 0 3rem;
            }

            .countdown {
                font-size: 1.5rem;
            }

            .countdown-number {
                min-width: 50px;
                padding: 0.4rem 1rem;
            }

            .participant-avatar {
                width: 80px;
                height: 80px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 3rem 0 2.5rem;
            }

            .draw-countdown-number {
                font-size: 8rem;
            }

            .draw-countdown-text {
                font-size: 1.8rem;
                margin-bottom: 2rem;
            }

            .stats-number {
                font-size: 2.2rem;
            }

            .btn-draw {
                padding: 0.9rem 2rem;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .header {
                padding: 2.5rem 0 2rem;
            }

            .countdown {
                font-size: 1.3rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .countdown-number {
                min-width: 40px;
                padding: 0.3rem 0.8rem;
                margin: 0.3rem;
            }

            .draw-countdown-number {
                font-size: 6rem;
            }

            .draw-countdown-text {
                font-size: 1.5rem;
            }

            .participant-avatar {
                width: 70px;
                height: 70px;
            }

            .winner-avatar {
                width: 150px;
                height: 150px;
            }
        }

        /* Стили для маски телефона */
        .iti {
            width: 100%;
        }

        .iti__flag-container {
            padding: 0 10px;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header class="gradient-bg text-center header">
        <div class="container header-content">
            <h1 class="display-4 fw-bold mb-3">РОЗЫГРЫШ ПУТЕШЕСТВИЯ</h1>
            <p class="lead mb-4">18 июня 2025 года в 20:00 на YouTube</p>
            <div class="countdown mb-4">
                <span>До начала:</span>
                <span class="countdown-number" id="hours">00</span>
                <span>:</span>
                <span class="countdown-number" id="minutes">00</span>
                <span>:</span>
                <span class="countdown-number" id="seconds">00</span>
            </div>
            <a href="https://youtube.com/live/ZeAHAkZGNdU?feature=share" target="_blank" class="btn youtube-btn mb-3">
                <i class="fab fa-youtube me-2"></i> Смотреть трансляцию
            </a>
        </div>
    </header>

    <div class="container my-5">
        <!-- Info Box -->
        <div class="info-box text-center">
            <div class="info-icon"><i class="fas fa-gift"></i></div>
            <h3>🎥 Сегодня в 20:00 — ПРЯМОЙ ЭФИР на YouTube!</h3>
            <p class="lead">Ведущий — Александр Щетинин.</p>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h4><i class="fas fa-suitcase me-2"></i> РОЗЫГРЫШ ПУТЕШЕСТВИЯ В АЛАНЬЮ</h4>
                    <p>6 ночей, всё включено + экскурсия, вылет 22 июня! 🌴✈️</p>
                </div>
                <div class="col-md-6">
                    <h4><i class="fas fa-trophy me-2"></i> УСЛОВИЯ УЧАСТИЯ</h4>
                    <p>Участвуют только участники программы ранее бронирования без просрочек платежей (минимум 2 месяца
                        оплачено)!</p>
                </div>
            </div>
        </div>

        <!-- Поиск пользователей -->
        <div class="search-container">
            <input type="tel" class="search-input" id="search-input" placeholder="Введите номер телефона для поиска...">
            <button class="search-btn" id="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <!-- Результаты поиска -->
        <div class="search-result-card" id="search-result">
            <div class="d-flex align-items-center mb-3">
                <img src="" class="search-result-avatar me-3" id="search-avatar">
                <div>
                    <h4 id="search-name"></h4>
                    <p class="text-muted mb-1" id="search-phone"></p>
                </div>
            </div>
            <div class="alert" id="search-status">
                <i class="fas fa-info-circle me-2"></i>
                <span id="search-message"></span>
            </div>
            <div class="mt-3" id="search-details"></div>
        </div>

        <!-- Draw Button (Admin Only) -->
        <div class="text-center">
            <a class="btn btn-draw" href="https://byfly-travel.com/event_copilka.php">
                <i class="fas fa-trophy me-2"></i> РАЗЫГРАТЬ ПРИЗ
            </a>
        </div>

        <!-- Stats Section -->
        <div class="row my-5">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-label">Участников в розыгрыше</div>
                    <div class="stats-number"><?= count($participants) ?></div>
                    <i class="fas fa-users" style="font-size: 2rem; color: var(--accent);"></i>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-label">Ячеек в розыгрыше</div>
                    <div class="stats-number"><?= array_sum(array_column($participants, 'plans_count')) ?></div>
                    <i class="fas fa-piggy-bank" style="font-size: 2rem; color: var(--accent);"></i>
                </div>
            </div>
        </div>

        <!-- Participants List -->
        <h2 class="text-center mb-4" style="position: relative;">
            <span style="background: var(--white); padding: 0 1.5rem; position: relative; z-index: 1;">Участники
                розыгрыша</span>
            <span
                style="position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--primary), transparent); z-index: 0;"></span>
        </h2>

        <div class="row" id="participants-container">
            <?php foreach ($participants as $participant): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card participant-card h-100">
                        <div class="card-body d-flex align-items-center">
                            <img src="<?= $participant['avatar'] ?>" alt="<?= $participant['name'] ?>"
                                class="participant-avatar me-4">
                            <div>
                                <h5 class="card-title mb-2"><?= $participant['name'] ?>     <?= $participant['famale'] ?></h5>
                                <div class="d-flex flex-wrap">
                                    <span class="participant-badge mb-2">
                                        <i class="fas fa-calendar-check"></i> <?= $participant['plans_count'] ?> ячеек
                                    </span>
                                    <span class="participant-badge mb-2">
                                        <i class="fas fa-check-circle"></i> <?= $participant['payments_count'] ?> месяцев
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Модальное окно обратного отсчета -->
    <div class="draw-countdown" id="drawCountdown">
        <div class="draw-countdown-number" id="drawCountdownNumber">10</div>
        <div class="draw-countdown-text">До определения победителя</div>
    </div>

    <!-- Winner Modal -->
    <?php if ($winner): ?>
        <div class="modal-overlay" id="winnerModal" style="display: none;">
            <div class="winner-content">
                <h2 class="mb-4">🎉 ПОБЕДИТЕЛЬ! 🎉</h2>
                <img src="<?= $winner['avatar'] ?>" alt="Победитель" class="winner-avatar">
                <h3 class="mb-2"><?= $winner['name'] ?>     <?= $winner['famale'] ?></h3>
                <p class="lead text-muted mb-4"><?= formatPhoneNumber($winner['phone']) ?></p>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-trophy me-2"></i> Путешествие в Аланью!
                </div>
                <p class="mb-4">Поздравляем с победой! Сообщение с подробностями уже отправлено вам в WhatsApp.</p>
                <button class="btn btn-primary btn-lg px-4" onclick="hideWinnerModal()">
                    <i class="fas fa-check me-2"></i> Закрыть
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>

    <script>
        // Инициализация маски телефона
        const phoneInput = document.querySelector("#search-input");
        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "kz",
            preferredCountries: ["kz", "uz"],
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        });

        // Обратный отсчет до розыгрыша
        function updateCountdown() {
            const now = new Date();
            const eventDate = new Date('June 18, 2025 20:00:00');
            const diff = eventDate - now;

            if (diff <= 0) {
                $('#hours').text('00');
                $('#minutes').text('00');
                $('#seconds').text('00');
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            $('#hours').text(hours.toString().padStart(2, '0'));
            $('#minutes').text(minutes.toString().padStart(2, '0'));
            $('#seconds').text(seconds.toString().padStart(2, '0'));
        }

        // Обновляем отсчет каждую секунду
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Функция для запуска розыгрыша с обратным отсчетом
        function startDrawCountdown() {
            $('#passwordModal').hide();
            $('#drawCountdown').css('display', 'flex');

            let count = 10;
            const countdownElement = $('#drawCountdownNumber');
            const countdownInterval = setInterval(() => {
                countdownElement.text(count);

                // Анимация для последних 3 секунд
                if (count <= 3) {
                    countdownElement.css('animation', 'none');
                    void countdownElement[0].offsetWidth; // Trigger reflow
                    countdownElement.css('animation', 'pulse 0.5s infinite alternate');
                }

                count--;

                if (count < 0) {
                    clearInterval(countdownInterval);
                    $('#drawCountdown').hide();

                    // Показываем победителя после завершения отсчета
                    setTimeout(() => {
                        $('#winnerModal').css('display', 'flex');
                        createConfetti();
                    }, 500);
                }
            }, 1000);
        }

        // Функция для создания конфетти
        function createConfetti() {
            const colors = ['#e63946', '#457b9d', '#1d3557', '#a8dadc', '#f1faee', '#FFD700'];

            for (let i = 0; i < 200; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.width = Math.random() * 12 + 6 + 'px';
                confetti.style.height = Math.random() * 12 + 6 + 'px';
                confetti.style.opacity = Math.random() * 0.7 + 0.3;
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;

                document.body.appendChild(confetti);

                // Удаляем конфетти после анимации
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }

        // Обработка формы ввода пароля
        $('form').on('submit', function (e) {
            e.preventDefault();

            const password = $('#passwordInput').val();
            if (password === 'alaniya2025') {
                startDrawCountdown();
            } else {
                $('.password-error').remove();
                $(this).append('<p class="text-danger mt-3 password-error">Неверный пароль!</p>');
                $('#passwordInput').addClass('is-invalid');
            }
        });

        // Поиск пользователей
        $('#search-btn').on('click', searchUser);
        $('#search-input').on('keypress', function (e) {
            if (e.which === 13) {
                searchUser();
            }
        });

        function searchUser() {
            const phone = iti.getNumber(intlTelInputUtils.numberFormat.E164);
            if (!phone || phone.length < 5) {
                alert('Введите корректный номер телефона');
                return;
            }

            $.ajax({
                url: window.location.href,
                type: 'GET',
                data: { search_phone: phone },
                dataType: 'json',
                beforeSend: function () {
                    $('#search-btn').html('<i class="fas fa-spinner fa-spin"></i>');
                },
                success: function (response) {
                    $('#search-btn').html('<i class="fas fa-search"></i>');

                    if (response.length === 0) {
                        showSearchResult(null, {
                            status: 'not_found',
                            message: 'Пользователь не найден или не участвует в программе накоплений'
                        });
                        return;
                    }

                    const user = response[0];
                    showSearchResult(user, {
                        status: user.status,
                        message: user.message,
                        payments_count: user.payments_count,
                        expected_payments: user.expected_payments
                    });
                },
                error: function () {
                    $('#search-btn').html('<i class="fas fa-search"></i>');
                    alert('Ошибка при поиске пользователя');
                }
            });
        }

        function showSearchResult(user, statusInfo) {
            const resultCard = $('#search-result');
            const statusElement = $('#search-status');
            const detailsElement = $('#search-details');

            if (user) {
                $('#search-avatar').attr('src', user.avatar);
                $('#search-name').text(user.name + ' ' + user.famale);
                $('#search-phone').text(user.formatted_phone);

                // Показываем детали
                detailsElement.html(`
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Детали участия</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Оплаченные месяцы
                                    <span class="badge bg-primary rounded-pill">${user.payments_count}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Ожидаемые платежи
                                    <span class="badge bg-primary rounded-pill">${user.expected_payments}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                `);
            } else {
                $('#search-avatar').attr('src', 'https://ui-avatars.com/api/?name=Unknown&background=random&size=150');
                $('#search-name').text('Неизвестный пользователь');
                $('#search-phone').text('');
                detailsElement.html('');
            }

            $('#search-message').text(statusInfo.message);

            // Устанавливаем классы в зависимости от статуса
            if (statusInfo.status === 'eligible') {
                statusElement.removeClass('not-eligible-badge not-found-badge').addClass('alert-success eligible-badge');
            } else if (statusInfo.status === 'not_found') {
                statusElement.removeClass('eligible-badge not-eligible-badge').addClass('alert-secondary not-found-badge');
            } else {
                statusElement.removeClass('eligible-badge not-found-badge').addClass('alert-danger not-eligible-badge');
            }

            resultCard.fadeIn();
        }

        // Функции для управления модальными окнами
        function showPasswordModal() {
            $('#passwordModal').css('display', 'flex');
            $('#passwordInput').focus().removeClass('is-invalid');
            $('.password-error').remove();
        }

        function hidePasswordModal() {
            $('#passwordModal').hide();
        }

        function hideWinnerModal() {
            $('#winnerModal').hide();
        }

        // Анимация при загрузке страницы
        $(document).ready(function () {
            $('.participant-card').css('opacity', 0).each(function (i) {
                $(this).delay(i * 100).animate({ opacity: 1 }, 300);
            });

            <?php if ($winner): ?>
                // Если есть победитель, показываем модальное окно
                setTimeout(() => {
                    $('#winnerModal').css('display', 'flex');
                    createConfetti();
                }, 1000);
            <?php endif; ?>
        });
    </script>

    <!-- Password Modal -->
    <div class="modal-overlay" id="passwordModal">
        <div class="password-content">
            <form>
                <div class="mb-4">
                    <i class="fas fa-lock" style="font-size: 2.5rem; color: var(--primary);"></i>
                </div>
                <h3 class="mb-3">Введите пароль</h3>
                <p class="mb-4">Для запуска розыгрыша требуется пароль администратора</p>
                <input type="password" id="passwordInput" class="form-control form-control-lg mb-4"
                    placeholder="Введите пароль" style="text-align: center;">
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary btn-lg px-4 me-3">
                        <i class="fas fa-check me-2"></i> Подтвердить
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg px-4" onclick="hidePasswordModal()">
                        <i class="fas fa-times me-2"></i> Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <h4 class="mb-3">ByFly Travel</h4>
                    <p>Путешествуйте с нами и выигрывайте призы!</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <h4 class="mb-3">Контакты</h4>
                    <p><i class="fas fa-phone-alt me-2"></i> +7 708 519 4866 (Дамир)</p>
                    <div class="mt-3">
                        <a href="https://youtube.com/live/ZeAHAkZGNdU?feature=share" class="social-icon me-3"><i
                                class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">© 2025 ByFly Travel. Все права защищены.</p>
            </div>
        </div>
    </footer>
</body>

</html>