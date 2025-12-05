<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$query = $db->query("SELECT * FROM new_year WHERE is_pay='1' AND city='Алматы' AND go = '1' ORDER BY RAND() LIMIT 1");
$winner = $query->fetch_assoc();
$winnerName = $winner['fio'] . ' - ' . $winner['phone'] . ' - ' . $winner['city'];

sendWhatsapp(
    preg_replace('/\D/', '', $winner['phone']),
    "🎉 Уважаемый партнер! 🎉\n\n" .
    "Мы рады сообщить, что вам предоставлено слово на нашем мероприятии! 🎤✨\n\n" .
    "Это прекрасная возможность поделиться своим опытом, вдохновить участников и рассказать о вашем вкладе в развитие ByFly Travel. 🌟\n\n" .
    "📍 Напоминаем, что мероприятие проходит в формате прямого эфира. Убедитесь, что ваше оборудование готово, а ваше выступление будет запоминающимся и вдохновляющим! 🎬\n\n" .
    "Мы ценим вашу активность и уверены, что ваше слово станет ярким моментом этого события. 🙌\n\n" .
    "Спасибо за вашу преданность и вклад в нашу общую цель — сделать путешествия доступными и незабываемыми для всех! ❤️"
);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Слово предоставляется:</title>
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

        #winner {
            display: none;
            font-size: 2.5rem;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1.5s ease forwards;
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
    <h1>Слово предоставляется:</h1>
    <div id="countdown">3</div> <!-- Таймер обратного отсчета -->
    <div id="winner">✨ <?= htmlspecialchars($winnerName); ?> ✨</div> <!-- Победитель -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const countdownElement = document.getElementById('countdown');
            const winnerElement = document.getElementById('winner');
            let countdown = 3;

            // Таймер обратного отсчета
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;

                if (countdown === 0) {
                    clearInterval(timer);
                    countdownElement.style.display = 'none'; // Скрываем таймер
                    winnerElement.style.display = 'block'; // Показываем победителя
                }
            }, 1000); // Обновление каждую секунду
        });
    </script>
</body>

</html>