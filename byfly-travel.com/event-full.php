<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID мероприятия
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем информацию о мероприятии
$event = [];
$organizer = [];
if ($event_id > 0) {
    // Основная информация о мероприятии
    $event_result = $db->query("SELECT * FROM event_byfly WHERE id = $event_id");
    if ($event_result && $event_result->num_rows > 0) {
        $event = $event_result->fetch_assoc();

        // Получаем информацию об организаторе
        $organizer_result = $db->query("SELECT * FROM users WHERE id = {$event['user_id']}");
        if ($organizer_result && $organizer_result->num_rows > 0) {
            $organizer = $organizer_result->fetch_assoc();
        }
    }
}

// Если мероприятие не найдено - 404
if (empty($event)) {
    header("Location: 404.php");
    exit();
}

// Обработка формы
$form_sent = false;
$form_errors = [];
$form_data = [
    'name' => '',
    'phone' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация данных
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $form_data['message'] = trim($_POST['message'] ?? '');

    if (empty($form_data['name'])) {
        $form_errors['name'] = 'Пожалуйста, введите ваше имя';
    }

    if (empty($form_data['phone'])) {
        $form_errors['phone'] = 'Пожалуйста, введите ваш телефон';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $form_data['phone'])) {
        $form_errors['phone'] = 'Пожалуйста, введите корректный номер телефона';
    }

    if (empty($form_errors)) {
        // Формируем сообщение для WhatsApp
        $whatsapp_message = "Здравствуйте! Я хотел(а) бы попасть на мероприятие:\n";
        $whatsapp_message .= "*{$event['name_events']}*\n\n";
        $whatsapp_message .= "*Мое имя:* {$form_data['name']}\n";
        $whatsapp_message .= "*Мой телефон:* {$form_data['phone']}\n\n";
        $whatsapp_message .= "*Мое сообщение:*\n{$form_data['message']}\n\n";
        $whatsapp_message .= "Пожалуйста, сообщите мне, если появятся свободные места!";

        // Перенаправляем в WhatsApp
        $phone = preg_replace('/[^0-9]/', '', $organizer['phone'] ?? '');
        header("Location: https://wa.me/$phone?text=" . urlencode($whatsapp_message));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мест нет | <?= htmlspecialchars($event['name_events']) ?> | ByFly Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --deep-purple: #1A033A;
            --purple: #4A148C;
            --light-purple: #7B1FA2;
            --accent: #9C27B0;
            --pink: #E91E63;
            --text: #FFFFFF;
            --text-light: rgba(255, 255, 255, 0.8);
            --text-lighter: rgba(255, 255, 255, 0.6);
            --error: #FF5252;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--deep-purple);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
        }

        .event-header {
            text-align: center;
            padding: 60px 0 40px;
            position: relative;
        }

        .event-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, white, #E1BEE7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .event-date {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 30px;
        }

        .back-button {
            position: absolute;
            left: 20px;
            top: 60px;
            color: var(--text-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: var(--accent);
        }

        .back-button i {
            margin-right: 8px;
        }

        .full-message {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 40px;
            padding: 30px;
            background: rgba(74, 20, 140, 0.3);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .full-icon {
            font-size: 4rem;
            color: var(--pink);
            margin-bottom: 20px;
        }

        .full-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--accent);
        }

        .full-text {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 25px;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto 60px;
            padding: 30px;
            background: rgba(74, 20, 140, 0.3);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-title {
            font-size: 1.5rem;
            margin-bottom: 25px;
            text-align: center;
            color: var(--accent);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-light);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .error-message {
            color: var(--error);
            font-size: 0.9rem;
            margin-top: 5px;
            display: block;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, var(--accent), var(--pink));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 30px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(156, 39, 176, 0.4);
        }

        .whatsapp-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #25D366;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .whatsapp-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.4);
        }

        footer {
            background: linear-gradient(135deg, var(--purple), var(--deep-purple));
            padding: 30px 0;
            text-align: center;
            margin-top: auto;
        }

        .copyright {
            color: var(--text-lighter);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .event-title {
                font-size: 2rem;
                margin-left: 40px;
            }

            .full-title {
                font-size: 1.5rem;
            }

            .full-message,
            .contact-form {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .event-title {
                font-size: 1.8rem;
            }

            .full-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="event-header">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Все мероприятия
            </a>
            <h1 class="event-title"><?= htmlspecialchars($event['name_events']) ?></h1>
            <div class="event-date">
                <i class="far fa-calendar-alt"></i>
                <?= date('d.m.Y H:i', strtotime($event['date_event'])) ?>
                <i class="fas fa-map-marker-alt" style="margin-left: 15px;"></i>
                <?= htmlspecialchars($event['citys']) ?>
            </div>
        </div>

        <div class="full-message">
            <div class="full-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2 class="full-title">К сожалению, мест больше нет</h2>
            <p class="full-text">
                Все места на это мероприятие уже заняты. Вы можете оставить заявку,
                и мы свяжемся с вами, если появятся свободные места или при отмене
                других участников.
            </p>
        </div>

        <div class="contact-form">
            <h3 class="form-title">Оставьте заявку организатору</h3>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name" class="form-label">Ваше имя</label>
                    <input type="text" id="name" name="name" class="form-control"
                        value="<?= htmlspecialchars($form_data['name']) ?>" required>
                    <?php if (!empty($form_errors['name'])): ?>
                        <span class="error-message"><?= $form_errors['name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Ваш телефон</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                        value="<?= htmlspecialchars($form_data['phone']) ?>" required>
                    <?php if (!empty($form_errors['phone'])): ?>
                        <span class="error-message"><?= $form_errors['phone'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label">Ваше сообщение (необязательно)</label>
                    <textarea id="message" name="message"
                        class="form-control"><?= htmlspecialchars($form_data['message']) ?></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    Отправить заявку
                </button>

                <?php if (!empty($organizer['phone'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $organizer['phone']) ?>?text=Здравствуйте!%20Я%20хотел(а)%20бы%20узнать%20о%20свободных%20местах%20на%20мероприятие%20<?= urlencode($event['name_events']) ?>"
                        class="whatsapp-btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> Написать в WhatsApp
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <footer>
        <div class="copyright">
            &copy; <?= date('Y') ?> ByFly Travel. Все права защищены.
        </div>
    </footer>
</body>

</html>