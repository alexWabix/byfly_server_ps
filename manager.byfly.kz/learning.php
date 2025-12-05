<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$query = $db->query("SELECT * FROM new_year WHERE is_pay='1' AND go = '1' ORDER BY RAND() LIMIT 20");
$winners = [];
while ($row = $query->fetch_assoc()) {
    $winners[] = $row['fio'] . ' - ' . $row['phone'] . ' - ' . $row['city'];

    sleep(2);

    sendWhatsapp(
        preg_replace('/\D/', '', $row['phone']),
        "🎉 Поздравляем! 🎉\n\n" .
        "Вы стали победителем и выиграли **Сертификат на бесплатное обучение в ByFly Travel**! 🎓✨\n\n" .
        "Теперь у вас есть уникальная возможность пройти обучение, стать профессиональным агентом и начать зарабатывать вместе с нашей компанией! 💼💰\n\n" .
        "🌟 В рамках обучения вы узнаете:\n" .
        "✔️ Как привлекать клиентов и продавать туры\n" .
        "✔️ Эффективное продвижение через социальные сети\n" .
        "✔️ Секреты построения команды и работы в сетевом маркетинге\n" .
        "✔️ Использование мобильного приложения ByFly для управления турами и клиентами\n\n" .
        "🚀 Ваша новая карьера начинается прямо сейчас! Мы поможем вам достичь финансовой свободы и открыть новые горизонты.\n\n" .
        "Мы искренне рады за вас и желаем вам успехов в обучении, больших продаж и постоянного роста доходов! 🌟\n\n" .
        "Если у вас возникли вопросы, свяжитесь с нами. Спасибо, что вы с ByFly! ❤️"
    );
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш: Путешествие сертификатов на обучение агентов в компании ByFly</title>
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
            display: none;
            font-size: 1.5rem;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1.5s ease forwards;
            text-align: left;
            max-width: 900px;
            margin: 0 auto;
            overflow-y: auto;
            max-height: 60vh;
        }

        .winner {
            margin-bottom: 10px;
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

        @keyframes countdownPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
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
    </style>
</head>

<body>
    <h1>🎉 Розыгрыш: Сертификатов на обучение агентов в компании ByFly 🎉</h1>
    <div id="countdown">10</div> <!-- Таймер обратного отсчета -->
    <div id="winners">
        <?php foreach ($winners as $winner): ?>
            <div class="winner">✨ <?= htmlspecialchars($winner); ?> ✨</div>
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