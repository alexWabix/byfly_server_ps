<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$count = isset($_GET['count']) && is_numeric($_GET['count']) ? (int) $_GET['count'] : 10;
$query = $db->query("SELECT * FROM new_year WHERE city = 'Алматы' AND is_pay='1' AND go = '1' ORDER BY RAND() LIMIT $count");
$winners = [];
while ($row = $query->fetch_assoc()) {
    $winners[] = $row['fio'] . ' - ' . $row['phone'] . ' - ' . $row['city'];

    sleep(2);

    sendWhatsapp(
        preg_replace('/\D/', '', $row['phone']),
        "🎉 Внимание, дорогие участники мероприятия! 🎉\n\n" .
        "Мы рады сообщить, что начинается розыгрыш эксклюзивных призов от ByFly Travel! 🏆✨\n\n" .
        "📍 Если вы находитесь в Алматы, приглашаем вас выйти на сцену, чтобы принять участие лично. Это ваш шанс стать звездой нашего мероприятия! 🌟\n\n" .
        "🌍 Если вы из другого города, подключайтесь к нашему онлайн-эфиру по ссылке: \nhttps://us06web.zoom.us/j/85199598406?pwd=ZU5QhQ9VOeEanKg353C0bVQ6WoS3Yb.1\n\n" .
        "Розыгрыш начнется совсем скоро, не упустите возможность выиграть замечательные призы! 🎊\n\n" .
        "Спасибо, что вы с ByFly Travel. Пусть удача будет на вашей стороне! ❤️"
    );
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш для участников из Алматы</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #ff4b5c, #33cc99);
            font-family: 'Arial', sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            animation: textPulse 2s infinite;
            text-shadow: 0 0 15px #fff, 0 0 30px #ff4b5c, 0 0 45px #33cc99;
        }

        #countdown {
            font-size: 5rem;
            font-weight: bold;
            margin-bottom: 30px;
            text-shadow: 0 0 15px #fff, 0 0 30px #ff4b5c, 0 0 45px #33cc99;
            animation: countdownPulse 1s infinite;
        }

        #winners {
            margin-top: 30px;
            width: 90%;
            max-width: 800px;
            overflow-y: auto;
            max-height: 60vh;
            padding-right: 10px;
            display: none;
            /* Скрываем список победителей до завершения отсчета */
        }

        .winner {
            font-size: 1.8rem;
            margin: 15px 0;
            opacity: 0;
            animation: fadeIn 1.5s ease forwards;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: left;
            word-wrap: break-word;
        }

        @keyframes textPulse {

            0%,
            100% {
                text-shadow: 0 0 15px #fff, 0 0 30px #ff4b5c, 0 0 45px #33cc99;
            }

            50% {
                text-shadow: 0 0 25px #fff, 0 0 45px #ff4b5c, 0 0 65px #33cc99;
            }
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes countdownPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }
    </style>
</head>

<body>
    <h1>🎉 Розыгрыш для участников из Алматы 🎉</h1>
    <div id="countdown">10</div> <!-- Таймер обратного отсчета -->
    <div id="winners">
        <?php foreach ($winners as $index => $winner): ?>
            <div class="winner" style="animation-delay: <?= $index * 0.5; ?>s;">✨ <?= htmlspecialchars($winner); ?> ✨</div>
        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const countdownElement = document.getElementById('countdown');
            const winnersElement = document.getElementById('winners');
            let countdown = 10;

            // Таймер обратного отсчета
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;

                if (countdown === 0) {
                    clearInterval(timer);
                    countdownElement.style.display = 'none'; // Скрываем таймер
                    winnersElement.style.display = 'block'; // Показываем победителей
                }
            }, 1000); // Обновление каждую секунду
        });
    </script>
</body>

</html>