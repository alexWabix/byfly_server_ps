<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID мероприятия из параметра
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем информацию о мероприятии
$event = [];
if ($event_id > 0) {
    $result = $db->query("SELECT * FROM event_byfly WHERE id = $event_id");
    if ($result && $result->num_rows > 0) {
        $event = $result->fetch_assoc();
    }
}

// Если мероприятие не найдено, перенаправляем на главную
if (empty($event)) {
    header("Location: index.php");
    exit();
}

// Проверяем доступность регистрации (например, по дате начала регистрации)
$registration_open = false; // Здесь должна быть ваша логика проверки
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мероприятие ByFly Travel</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .event-coming-soon {
            text-align: center;
            padding: 60px 0;
        }

        .event-image-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto 40px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .event-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .event-image-placeholder {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--purple), var(--accent));
        }

        .event-image-placeholder img {
            max-width: 60%;
            max-height: 60%;
            opacity: 0.8;
        }

        .event-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: linear-gradient(to right, white, #E1BEE7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .event-date {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: var(--text-light);
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .countdown-item {
            background: rgba(74, 20, 140, 0.3);
            border-radius: 10px;
            padding: 20px;
            min-width: 100px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .countdown-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--accent);
        }

        .countdown-label {
            font-size: 1rem;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .event-message {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 40px;
            color: var(--text-light);
            line-height: 1.8;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
            min-width: 200px;
        }

        .button-primary {
            background: linear-gradient(90deg, var(--accent), var(--pink));
            color: white;
        }

        .button-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(156, 39, 176, 0.4);
        }

        .button-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .button-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        footer {
            background: linear-gradient(135deg, var(--purple), var(--deep-purple));
            padding: 30px 0;
            text-align: center;
        }

        .copyright {
            color: var(--text-lighter);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .event-title {
                font-size: 2rem;
            }

            .event-date {
                font-size: 1.2rem;
            }

            .countdown-item {
                min-width: 80px;
                padding: 15px;
            }

            .countdown-value {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .event-title {
                font-size: 1.8rem;
            }

            .countdown {
                gap: 10px;
            }

            .countdown-item {
                min-width: 70px;
                padding: 10px;
            }

            .countdown-value {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .button {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="event-coming-soon">
            <?php if (!empty($event['image']) && isImageAvailable($event['image'])): ?>
                <div class="event-image-container">
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['name_events']) ?>"
                        class="event-image" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\"
                        event-image-placeholder\"><img src=\"https://byfly.kz/assets/logo-610c625f.svg\" alt=\"ByFly
                        Travel\">
                </div>'">
            </div>
        <?php else: ?>
            <div class="event-image-container">
                <div class="event-image-placeholder">
                    <img src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel">
                </div>
            </div>
        <?php endif; ?>

        <h1 class="event-title"><?= htmlspecialchars($event['name_events']) ?></h1>

        <div class="event-date">
            <i class="far fa-calendar-alt"></i>
            <?= date('d.m.Y H:i', strtotime($event['date_event'])) ?>
        </div>

        <div class="countdown" id="countdown">
            <div class="countdown-item">
                <div class="countdown-value" id="days">00</div>
                <div class="countdown-label">Дней</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="hours">00</div>
                <div class="countdown-label">Часов</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="minutes">00</div>
                <div class="countdown-label">Минут</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="seconds">00</div>
                <div class="countdown-label">Секунд</div>
            </div>
        </div>

        <p class="event-message">
            Регистрация на это мероприятие еще не открыта.<br>
            Следите за обновлениями в наших социальных сетях или на главной странице.
        </p>

        <div class="action-buttons">
            <a href="index.php" class="button button-primary">Все мероприятия</a>
        </div>
    </div>
    </div>

    <footer>
        <div class="copyright">
            &copy; <?= date('Y') ?> ByFly Travel. Все права защищены.
        </div>
    </footer>

    <script>
        // Таймер обратного отсчета
        function updateCountdown() {
            const eventDate = new Date("<?= $event['date_event'] ?>").getTime();
            const now = new Date().getTime();
            const distance = eventDate - now;

            if (distance < 0) {
                document.getElementById('countdown').innerHTML = '<div class="event-message">Мероприятие уже началось!</div>';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').innerText = days.toString().padStart(2, '0');
            document.getElementById('hours').innerText = hours.toString().padStart(2, '0');
            document.getElementById('minutes').innerText = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').innerText = seconds.toString().padStart(2, '0');
        }

        // Обновляем таймер каждую секунду
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Обработчик для кнопки подписки
        document.querySelector('.button-secondary').addEventListener('click', function (e) {
            e.preventDefault();
            alert('Спасибо за интерес к нашему мероприятию! Мы уведомим вас, когда регистрация будет открыта.');
        });
    </script>
</body>

</html>