<?php
// HTTP-авторизация
function require_auth()
{
    $auth_user = 'byfly';
    $auth_pass = 'byfly2023';

    header('Cache-Control: no-cache, must-revalidate, max-age=0');

    $has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
    $is_not_authenticated = (
        !$has_supplied_credentials ||
        $_SERVER['PHP_AUTH_USER'] != $auth_user ||
        $_SERVER['PHP_AUTH_PW'] != $auth_pass
    );

    if ($is_not_authenticated) {
        header('HTTP/1.1 401 Authorization Required');
        header('WWW-Authenticate: Basic realm="Access denied"');
        exit;
    }
}

require_auth();

// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Создаем таблицу для призов, если ее нет
$createTableSql = "CREATE TABLE IF NOT EXISTS byfly_prizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prize_title VARCHAR(255) NOT NULL,
    prize_description TEXT,
    draw_date DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$db->query($createTableSql);

// Создаем таблицу для подтверждения просмотра эфира
$createConfirmationTable = "CREATE TABLE IF NOT EXISTS byfly_stream_confirmation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    confirmation_code VARCHAR(10) NOT NULL,
    confirmed TINYINT(1) DEFAULT 0,
    confirmed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id)
)";

$db->query($createConfirmationTable);

// Проверяем, есть ли уже приз в базе
$prizeCheck = $db->query("SELECT COUNT(*) as count FROM byfly_prizes");
if ($prizeCheck->fetch_assoc()['count'] == 0) {
    // Добавляем приз по умолчанию
    $defaultPrize = [
        'prize_title' => 'Путешествие на двоих в Дубай',
        'prize_description' => '7 ночей в 5-звездочном отеле с системой "Все включено"',
        'draw_date' => date('Y-m-d H:i:s', strtotime('+1 day'))
    ];

    $stmt = $db->prepare("INSERT INTO byfly_prizes (prize_title, prize_description, draw_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $defaultPrize['prize_title'], $defaultPrize['prize_description'], $defaultPrize['draw_date']);
    $stmt->execute();
}

// Получаем текущий приз
$prize = $db->query("SELECT * FROM byfly_prizes ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Обработка формы обновления приза
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_prize'])) {
    $new_title = $_POST['prize_title'];
    $new_description = $_POST['prize_description'];
    $new_date = $_POST['draw_date'];

    $stmt = $db->prepare("UPDATE byfly_prizes SET prize_title = ?, prize_description = ?, draw_date = ? WHERE id = ?");
    $stmt->bind_param("sssi", $new_title, $new_description, $new_date, $prize['id']);
    $stmt->execute();

    // Обновляем данные приза после изменения
    $prize = $db->query("SELECT * FROM byfly_prizes ORDER BY id DESC LIMIT 1")->fetch_assoc();

    // Простая переадресация
    header("Location: https://byfly-travel.com/random.php");
    exit(); // Важно завершить выполнение скрипта после переадресации
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notify_stream_start'])) {
    // Генерируем случайный код для подтверждения
    $confirmation_code = rand(100000, 999999);

    // Очищаем предыдущие подтверждения
    $db->query("TRUNCATE TABLE byfly_stream_confirmation");

    // Получаем всех участников розыгрыша с полной информацией
    $sql = "SELECT 
                u.id, 
                u.name, 
                u.famale, 
                u.phone, 
                u.avatar,
                u.user_status,
                (SELECT COUNT(*) FROM order_tours 
                 WHERE (user_id = u.id OR saler_id = u.id) AND `type` NOT LIKE 'test'
                 AND status_code NOT IN (0,1,2,5)) as tours_sold,
                (SELECT COUNT(*) FROM users 
                 WHERE parent_user = u.id AND user_status != 'user') as agents_count,
                (SELECT COUNT(*) FROM copilka_ceils 
                 WHERE user_id = u.id AND summ_money >= 100000 
                 AND date_dosrok_close IS NULL) as own_copilka_count,
                (SELECT COUNT(DISTINCT cc.user_id) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND cc.summ_money >= 100000
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_count,
                (SELECT SUM(summ_money) FROM copilka_ceils 
                 WHERE user_id = u.id AND date_dosrok_close IS NULL) as own_copilka_sum,
                (SELECT SUM(cc.summ_money) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_sum,
                (SELECT COUNT(*) FROM users 
                 WHERE parent_user IN (SELECT id FROM users WHERE parent_user = u.id) 
                 AND user_status != 'user') as second_line_agents
              FROM users u
              WHERE u.user_status != 'user'
              HAVING tours_sold >= 2 
                AND agents_count >= 1 
                AND (own_copilka_count >= 1 OR first_line_copilka_count >= 1)";

    $participants = $db->query($sql);

    // Добавляем записи для подтверждения
    $stmt = $db->prepare("INSERT INTO byfly_stream_confirmation (user_id, confirmation_code) VALUES (?, ?)");
    $notification_count = 0;

    while ($participant = $participants->fetch_assoc()) {
        $stmt->bind_param("is", $participant['id'], $confirmation_code);
        $stmt->execute();

        // Формируем ссылку для подтверждения
        $confirmation_link = "https://byfly-travel.com/go_efir.php?user_id=" . $participant['id'] . "&code=" . $confirmation_code;

        // Формируем красивое сообщение для уведомления
        $message = "🌟 *ByFly Travel - Начался эфир!* 🌟\n\n";
        $message .= "Дорогой(ая) " . $participant['famale'] . " " . $participant['name'] . ",\n";
        $message .= "Прямой эфир ByFly Travel уже начался! Для подтверждения вашего участия в розыгрыше призов необходимо:\n\n";
        $message .= "Ссылка на эфир: https://www.youtube.com/watch?v=iUWEwvsKoTs\n\n";
        $message .= "1. Перейти по ссылке: " . $confirmation_link . "\n";
        $message .= "2. Ввести код подтверждения: *" . $confirmation_code . "*\n\n";
        $message .= "❗ *Важно:* Только участники, подтвердившие просмотр эфира, смогут участвовать в розыгрыше!\n\n";
        $message .= "С уважением,\nКоманда ByFly Travel 🚀";

        // Отправляем уведомление (WhatsApp, email или SMS)
        sendWhatsapp($participant['phone'], $message);
        //sendWhatsapp('77780021666', $message);
        $notification_count++;
    }

    // Простая переадресация
    header("Location: https://byfly-travel.com/random.php");
    exit(); // Важно завершить выполнение скрипта после переадресации
}



// Получаем участников из базы данных с информацией о подтверждении просмотра
$sql = "SELECT 
            u.id, 
            u.name, 
            u.famale, 
            u.phone, 
            u.avatar,
            u.user_status,
            u.promo_code,
            u.date_registration,
            sc.confirmation_code,
            sc.confirmed,
            sc.confirmed_at,
            (SELECT COUNT(*) FROM order_tours 
             WHERE (user_id = u.id OR saler_id = u.id) AND `type` NOT LIKE 'test'
             AND status_code NOT IN (0,1,2,5)) as tours_sold,
            (SELECT COUNT(*) FROM users 
             WHERE parent_user = u.id AND user_status != 'user') as agents_count,
            (SELECT COUNT(*) FROM copilka_ceils 
             WHERE user_id = u.id AND summ_money >= 100000 
             AND date_dosrok_close IS NULL) as own_copilka_count,
            (SELECT COUNT(DISTINCT cc.user_id) FROM copilka_ceils cc
             JOIN users child ON child.id = cc.user_id
             WHERE child.parent_user = u.id 
             AND cc.summ_money >= 100000
             AND cc.date_dosrok_close IS NULL) as first_line_copilka_count,
            (SELECT SUM(summ_money) FROM copilka_ceils 
             WHERE user_id = u.id AND date_dosrok_close IS NULL) as own_copilka_sum,
            (SELECT SUM(cc.summ_money) FROM copilka_ceils cc
             JOIN users child ON child.id = cc.user_id
             WHERE child.parent_user = u.id 
             AND cc.date_dosrok_close IS NULL) as first_line_copilka_sum,
            (SELECT COUNT(*) FROM users 
             WHERE parent_user IN (SELECT id FROM users WHERE parent_user = u.id) 
             AND user_status != 'user') as second_line_agents
          FROM users u
          LEFT JOIN byfly_stream_confirmation sc ON sc.user_id = u.id
          WHERE u.user_status != 'user'
          HAVING tours_sold >= 2 
            AND agents_count >= 1 
            AND (own_copilka_count >= 1 OR first_line_copilka_count >= 1)
          ORDER BY 
            CASE 
                WHEN own_copilka_count >= 1 AND first_line_copilka_count >= 1 THEN 
                    (second_line_agents * 3 + agents_count * 2 + tours_sold + FLOOR((own_copilka_sum + first_line_copilka_sum)/50000))
                WHEN own_copilka_count >= 1 THEN 
                    (second_line_agents * 2 + agents_count + tours_sold + FLOOR(own_copilka_sum/50000))
                WHEN first_line_copilka_count >= 1 THEN 
                    (second_line_agents + agents_count + tours_sold + FLOOR(first_line_copilka_sum/50000))
            END DESC,
            tours_sold DESC";

$result = $db->query($sql);
$participants = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $participants[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш призов ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #8a2be2;
            --secondary-color: #4b0082;
            --accent-color: #ff00ff;
            --dark-color: #1a0033;
            --light-color: #f3e6ff;
        }

        body {
            font-family: 'Montserrat', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f3e6ff 0%, #d9b3ff 100%);
            min-height: 100vh;
            color: var(--dark-color);
        }

        .header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://byfly-travel.com/images/tild3834-6535-4137-a566-646463623438__noroot.png') no-repeat center right;
            background-size: contain;
            opacity: 0.1;
        }

        .logo {
            max-height: 100px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
        }

        .participant-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .participant-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .participant-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .avatar-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 48px;
            font-weight: bold;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            border: 4px solid white;
        }

        .participant-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .participant-name {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .participant-stats {
            font-size: 0.9rem;
            color: #666;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 1rem 2.5rem;
            font-weight: 700;
            border-radius: 50px;
            box-shadow: 0 8px 15px rgba(138, 43, 226, 0.4);
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px rgba(138, 43, 226, 0.6);
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            background: linear-gradient(to bottom, #f9f0ff, #f3e6ff);
        }

        .modal-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            letter-spacing: 1px;
        }

        .btn-close {
            filter: invert(1);
        }

        .countdown {
            font-size: 6rem;
            font-weight: 900;
            color: var(--primary-color);
            text-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 1rem 0;
        }

        .winner-card {
            background: linear-gradient(135deg, #fff, #f3e6ff);
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
        }

        .winner-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
        }

        .winner-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 0, 255, 0.4);
            }

            70% {
                transform: scale(1.03);
                box-shadow: 0 0 0 15px rgba(255, 0, 255, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 0, 255, 0);
            }
        }

        .confetti {
            position: fixed;
            width: 15px;
            height: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            opacity: 0;
            z-index: 9999;
            animation: confetti 5s ease-in-out;
        }

        @keyframes confetti {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(1000px) rotate(720deg);
                opacity: 0;
            }
        }

        .travel-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .prize-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 5px solid var(--accent-color);
            position: relative;
            overflow: hidden;
        }

        .prize-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://byfly-travel.com/images/tild3834-6535-4137-a566-646463623438__noroot.png') no-repeat center right;
            background-size: contain;
            opacity: 0.05;
        }

        .prize-title {
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            letter-spacing: 1px;
        }

        .prize-description {
            color: #555;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .footer {
            background: linear-gradient(90deg, var(--dark-color), #330066);
            color: white;
            padding: 3rem 0;
            margin-top: 5rem;
            position: relative;
        }

        .footer::before {
            content: "";
            position: absolute;
            top: -50px;
            left: 0;
            right: 0;
            height: 50px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="%231a0033"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2, 8.66, 59, 6.17, 87.09-7.5, 22.43-10.89, 48-26.93, 60.65-49.24V0Z" opacity=".5" fill="%231a0033"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%231a0033"/></svg>') no-repeat center bottom;
            background-size: cover;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.8rem 1.2rem;
            border: 2px solid #e6e6ff;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(138, 43, 226, 0.25);
        }

        .badge {
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .bg-success {
            background: linear-gradient(135deg, #4b0082, #8a2be2) !important;
        }

        .admin-panel {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .admin-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .draw-info {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .draw-date {
            font-weight: 700;
            color: var(--secondary-color);
        }

        .status-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .status-watching {
            background: linear-gradient(135deg, #34a853, #0d652d);
            color: white;
        }

        .status-not-watching {
            background: linear-gradient(135deg, #ea4335, #a50c0c);
            color: white;
        }

        .copilka-info {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .header {
                padding: 2rem 0;
            }

            .logo {
                max-height: 80px;
            }

            .countdown {
                font-size: 4rem;
            }

            .prize-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="header text-center">
        <div class="container">
            <img src="https://byfly-travel.com/images/tild3834-6535-4137-a566-646463623438__noroot.png"
                alt="ByFly Travel" class="logo">
            <h1 class="display-4 fw-bold">Розыгрыш призов</h1>
            <p class="lead">Среди лучших агентов ByFly Travel</p>
        </div>
    </div>

    <div class="container">
        <!-- Админ-панель -->
        <div class="admin-panel">
            <h3 class="admin-title"><i class="fas fa-lock me-2"></i>Административная панель</h3>

            <div class="draw-info">
                <i class="fas fa-calendar-alt me-2"></i>Дата розыгрыша:
                <span class="draw-date"><?= date('d.m.Y H:i', strtotime($prize['draw_date'])) ?></span>
            </div>

            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="prize_title" class="form-label">Название приза</label>
                    <input type="text" class="form-control" id="prize_title" name="prize_title"
                        value="<?= htmlspecialchars($prize['prize_title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="prize_description" class="form-label">Описание приза</label>
                    <textarea class="form-control" id="prize_description" name="prize_description" rows="3"
                        required><?= htmlspecialchars($prize['prize_description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="draw_date" class="form-label">Дата и время розыгрыша</label>
                    <input type="datetime-local" class="form-control" id="draw_date" name="draw_date"
                        value="<?= date('Y-m-d\TH:i', strtotime($prize['draw_date'])) ?>" required>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" name="update_prize" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Сохранить изменения
                    </button>

                    <button type="submit" name="notify_stream_start" class="btn btn-success">
                        <i class="fas fa-bell me-2"></i>Уведомить о начале эфира
                    </button>
                </div>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-8 mx-auto text-center">
                <div class="prize-section">
                    <i class="fas fa-gift travel-icon"></i>
                    <h2 class="prize-title"><?= htmlspecialchars($prize['prize_title']) ?></h2>
                    <p class="prize-description"><?= htmlspecialchars($prize['prize_description']) ?></p>
                    <div class="mt-3">
                        <i class="fas fa-clock me-2"></i>Розыгрыш:
                        <?= date('d.m.Y в H:i', strtotime($prize['draw_date'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12 text-center">
                <button id="startDraw"
                    class="btn btn-primary btn-lg animate__animated animate__pulse animate__infinite">
                    <i class="fas fa-trophy me-2"></i>Начать розыгрыш
                </button>
            </div>
        </div>

        <div class="row" id="participantsContainer">
            <?php foreach ($participants as $participant):
                $firstLetter = mb_substr($participant['name'], 0, 1, 'UTF-8');
                $hasAvatar = !empty($participant['avatar']);
                $isWatching = $participant['confirmed'] == 1;
                ?>
                <div class="col-md-4 col-lg-3">
                    <div class="participant-card" data-id="<?= $participant['id'] ?>">
                        <span class="status-badge <?= $isWatching ? 'status-watching' : 'status-not-watching' ?>">
                            <?= $isWatching ? 'В эфире' : 'Не в эфире' ?>
                        </span>

                        <div class="avatar-container">
                            <?php if ($hasAvatar): ?>
                                <img src="<?= $participant['avatar'] ?>"
                                    alt="<?= $participant['famale'] . ' ' . $participant['name'] ?>" class="participant-avatar">
                            <?php else: ?>
                                <span><?= $firstLetter ?></span>
                            <?php endif; ?>
                        </div>
                        <h4 class="participant-name"><?= $participant['famale'] . ' ' . $participant['name'] ?></h4>
                        <div class="participant-stats">
                            <div><i class="fas fa-plane me-2"></i> Продано туров: <?= $participant['tours_sold'] ?></div>
                            <div><i class="fas fa-users me-2"></i> Агентов: <?= $participant['agents_count'] ?></div>
                            <div><i class="fas fa-piggy-bank me-2"></i> Накопления:
                                <?= floor($participant['own_copilka_sum'] / 1000) ?>K ₸
                            </div>
                            <div class="copilka-info">
                                <i class="fas fa-layer-group me-2"></i>Ячеек в 1-й линии:
                                <?= $participant['first_line_copilka_count'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Модальное окно для ввода количества победителей -->
    <div class="modal fade" id="winnersCountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog me-2"></i>Настройки розыгрыша</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="winnersCount" class="form-label fw-bold">Сколько победителей выбрать?</label>
                        <input type="number" class="form-control form-control-lg" id="winnersCount" min="1"
                            max="<?= count($confirmedParticipants) ?>"
                            value="<?= min(3, count($confirmedParticipants)) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="fas fa-times me-2"></i>Отмена</button>
                    <button type="button" class="btn btn-primary" id="confirmDraw"><i
                            class="fas fa-play me-2"></i>Начать розыгрыш</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно обратного отсчета -->
    <div class="modal fade" id="countdownModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="countdown" id="countdownDisplay">5</div>
                    <p class="text-muted mt-3"><i class="fas fa-hourglass-half me-2"></i>До объявления победителей
                        осталось...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно победителей -->
    <div class="modal fade" id="winnersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trophy me-2"></i>Победители розыгрыша</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="winnersContainer">
                        <!-- Победители будут добавлены сюда -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><i
                            class="fas fa-check me-2"></i>Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div class="footer text-center">
        <div class="container">
            <img src="https://byfly-travel.com/images/tild3834-6535-4137-a566-646463623438__noroot.png"
                alt="ByFly Travel" width="120" class="mb-3">
            <p class="mb-0">ByFly Travel &copy; <?= date('Y') ?>. Все права защищены.</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php
        $confirmedParticipants = array();
        foreach ($participants as $vl) {
            if ($vl['confirmed'] == 1) {
                $confirmedParticipants[] = $vl;
            }
        }
        ?>
        $(document).ready(function () {
            const confirmedParticipants = <?= json_encode($confirmedParticipants) ?>;
            let winners = [];

            // Начать розыгрыш
            $('#startDraw').click(function () {
                if (!confirmedParticipants || confirmedParticipants.length === 0) {
                    alert('Нет участников, подтвердивших просмотр эфира!');
                    return;
                }
                $('#winnersCountModal').modal('show');

                // Устанавливаем максимальное количество победителей
                $('#winnersCount').attr('max', confirmedParticipants.length);
            });

            // Подтверждение розыгрыша
            $('#confirmDraw').click(function () {
                const winnersCount = parseInt($('#winnersCount').val());
                const maxWinners = confirmedParticipants.length;

                if (winnersCount > 0 && winnersCount <= maxWinners) {
                    $('#winnersCountModal').modal('hide');
                    startCountdown(winnersCount);
                } else {
                    alert('Пожалуйста, введите корректное количество победителей (от 1 до ' + maxWinners + ')');
                }
            });

            // Обратный отсчет
            function startCountdown(winnersCount) {
                $('#countdownModal').modal('show');
                let count = 5;

                const countdownInterval = setInterval(() => {
                    $('#countdownDisplay').text(count);

                    // Добавляем анимацию при смене цифр
                    $('#countdownDisplay').addClass('animate__animated animate__pulse');
                    setTimeout(() => {
                        $('#countdownDisplay').removeClass('animate__animated animate__pulse');
                    }, 800);

                    if (count === 0) {
                        clearInterval(countdownInterval);
                        $('#countdownModal').modal('hide');
                        selectWinners(winnersCount);
                    }
                    count--;
                }, 1000);
            }

            // Выбор победителей
            function selectWinners(winnersCount) {
                // Создаем копию массива участников
                const shuffledParticipants = [...confirmedParticipants];

                // Перемешиваем массив (алгоритм Фишера-Йетса)
                for (let i = shuffledParticipants.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [shuffledParticipants[i], shuffledParticipants[j]] = [shuffledParticipants[j], shuffledParticipants[i]];
                }

                // Выбираем победителей
                winners = shuffledParticipants.slice(0, winnersCount);

                // Показываем победителей
                showWinners();
            }

            // Показать победителей
            function showWinners() {
                $('#winnersContainer').empty();

                // Создаем конфетти
                createConfetti();

                // Добавляем карточки победителей
                winners.forEach((winner, index) => {
                    const firstLetter = winner.name ? winner.name.charAt(0).toUpperCase() : '?';
                    const hasAvatar = winner.avatar && winner.avatar.trim() !== '';

                    const winnerCard = `
                        <div class="col-md-6">
                            <div class="participant-card winner-card mb-4">
                                <span class="winner-badge">${index + 1} место</span>
                                <div class="avatar-container">
                                    ${hasAvatar ?
                            `<img src="${winner.avatar}" alt="${winner.famale} ${winner.name}" class="participant-avatar">` :
                            `<span>${firstLetter}</span>`
                        }
                                </div>
                                <h4 class="participant-name">${winner.famale} ${winner.name}</h4>
                                <div class="participant-stats">
                                    <div><i class="fas fa-phone me-2"></i> ${winner.phone}</div>
                                    <div><i class="fas fa-plane me-2"></i> Продано туров: ${winner.tours_sold}</div>
                                    <div><i class="fas fa-piggy-bank me-2"></i> Накопления: ${Math.floor(winner.own_copilka_sum / 1000)}K ₸</div>
                                    <div class="mt-3">
                                        <span class="badge bg-success animate__animated animate__bounceIn">
                                            <i class="fas fa-trophy me-1"></i> ПОБЕДИТЕЛЬ
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    $('#winnersContainer').append(winnerCard);
                });

                $('#winnersModal').modal('show');
            }

            // Создать эффект конфетти
            function createConfetti() {
                const colors = ['#8a2be2', '#4b0082', '#ff00ff', '#9400d3', '#9932cc'];

                for (let i = 0; i < 150; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.background = `linear-gradient(135deg, ${colors[Math.floor(Math.random() * colors.length)]}, ${colors[Math.floor(Math.random() * colors.length)]})`;
                    confetti.style.width = Math.random() * 15 + 5 + 'px';
                    confetti.style.height = Math.random() * 15 + 5 + 'px';
                    confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                    confetti.style.animationDelay = Math.random() * 2 + 's';

                    // Разные формы конфетти
                    if (Math.random() > 0.5) {
                        confetti.style.borderRadius = '50%';
                    } else {
                        confetti.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                    }

                    document.body.appendChild(confetti);

                    // Удаляем конфетти после анимации
                    setTimeout(() => {
                        confetti.remove();
                    }, 7000);
                }
            }
        });
    </script>
</body>

</html>