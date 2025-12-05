<?php
// Подключаем конфиг с соединением к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Обработка розыгрыша
$winner = null;

if (empty($_POST['draw']) == false) {
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
    $participants = [];

    if ($result->num_rows > 0) {
        $participants = $result->fetch_all(MYSQLI_ASSOC);

        // Справедливый рандомный выбор (без учета количества платежей)
        if (!empty($participants)) {
            $winner = $participants[array_rand($participants)];
            $message = "Поздравляем, {$name}! 🎉\n\nВы выиграли путешествие в Аланью от ByFly Travel!\n\nС вами свяжется наш менеджер в ближайшее время для уточнения деталей.\n\nС уважением,\nКоманда ByFly Travel";


            // sendWhatsapp($winner['phone'], $message);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e63946;
            --primary-dark: #c1121f;
            --secondary: #457b9d;
            --dark: #1d3557;
            --light: #f1faee;
            --accent: #a8dadc;
            --white: #ffffff;
            --black: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark);
            color: var(--white);
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.8;
        }

        .logo {
            width: 250px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
        }

        .btn-draw {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-size: 1.5rem;
            padding: 1.2rem 3rem;
            border: none;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(230, 57, 70, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-weight: 700;
            margin-top: 2rem;
        }

        .btn-draw:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(230, 57, 70, 0.7);
        }

        .btn-draw:active {
            transform: translateY(0);
        }

        .countdown {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.9);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 100;
        }

        .countdown-number {
            font-size: 10rem;
            font-weight: 700;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(230, 57, 70, 0.7);
            margin-bottom: 2rem;
            animation: pulse 1s infinite alternate;
            font-family: 'Arial Black', sans-serif;
        }

        .winner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.9);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 101;
            padding: 2rem;
            text-align: center;
        }

        .winner-avatar {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
            margin-bottom: 2rem;
            box-shadow: 0 0 30px rgba(230, 57, 70, 0.7);
        }

        .winner-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--white);
        }

        .winner-prize {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 2rem;
        }

        .winner-details {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            max-width: 500px;
            width: 100%;
        }

        .winner-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .confetti {
            position: absolute;
            width: 15px;
            height: 15px;
            background-color: var(--primary);
            opacity: 0.7;
            animation: confettiFall 5s linear forwards;
            z-index: 10;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        @keyframes confettiFall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
            }

            100% {
                transform: translateY(100vh) rotate(360deg);
            }
        }

        .title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .subtitle {
            font-size: 1.5rem;
            margin-bottom: 3rem;
            text-align: center;
            max-width: 600px;
            opacity: 0.9;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .participants-count {
            margin-top: 2rem;
            font-size: 1.2rem;
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <div class="gradient-bg"></div>

    <img src="https://byfly-travel.com/images/tild3834-6535-4137-a566-646463623438__noroot.png" alt="ByFly Travel"
        class="logo">

    <h1 class="title">РОЗЫГРЫШ ПУТЕШЕСТВИЯ</h1>
    <p class="subtitle">Нажмите кнопку ниже, чтобы определить победителя</p>

    <form method="POST" id="drawForm">
        <input type="hidden" name="draw" value="sdfasd">
        <button type="submit" class="btn-draw" id="drawButton">
            <i class="fas fa-trophy me-2"></i> РАЗЫГРАТЬ
        </button>
    </form>

    <?php if (isset($participants) && count($participants) > 0): ?>
        <div class="participants-count">
            Всего участников: <?= count(array_unique(array_column($participants, 'id'))) ?>
            | Всего записей в розыгрыше: <?= count($participants) ?>
        </div>
    <?php endif; ?>

    <div class="countdown" id="countdown">
        <div class="countdown-number" id="countdownNumber">10</div>
        <div class="countdown-text">До определения победителя</div>
    </div>

    <?php if ($winner): ?>
        <div class="winner" id="winner" style="display: flex;">
            <img src="<?= $winner['avatar'] ?: 'https://byfly.kz/default-avatar.jpg' ?>" class="winner-avatar">
            <div class="winner-name"><?= $winner['name'] ?>     <?= $winner['famale'] ?></div>
            <div class="winner-prize">
                <i class="fas fa-trophy me-2"></i>
                Путешествие в Аланью!
            </div>

            <div class="winner-details">
                <div class="winner-detail">
                    <span>Телефон:</span>
                    <span><?= $winner['phone'] ?></span>
                </div>
                <div class="winner-detail">
                    <span>Оплаченных месяцев:</span>
                    <span><?= $winner['payments_count'] ?></span>
                </div>
                <div class="winner-detail">
                    <span>Дата регистрации:</span>
                    <span><?= date('d.m.Y', strtotime($winner['date_create'])) ?></span>
                </div>
            </div>

            <a class="btn-draw" href="https://byfly-travel.com/event_copilka.php">
                <i class="fas fa-redo me-2"></i> Заново
            </a>
        </div>

        <script>
            // Создаем конфетти
            function createConfetti() {
                const colors = ['#e63946', '#457b9d', '#1d3557', '#a8dadc', '#f1faee', '#FFD700'];

                for (let i = 0; i < 150; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 5 + 's';
                    confetti.style.width = Math.random() * 15 + 5 + 'px';
                    confetti.style.height = Math.random() * 15 + 5 + 'px';
                    confetti.style.opacity = Math.random() * 0.7 + 0.3;
                    confetti.style.transform = `rotate(${Math.random() * 360}deg)`;

                    document.body.appendChild(confetti);

                    // Удалить конфетти после анимации
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }
            }

            // Запускаем конфетти после загрузки страницы
            window.onload = function () {
                createConfetti();
            };
        </script>
    <?php endif; ?>

    <script>
        // Обработка нажатия кнопки "Разыграть"
        document.getElementById('drawForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Показываем обратный отсчет
            document.getElementById('countdown').style.display = 'flex';
            document.getElementById('drawButton').disabled = true;

            let count = 10;
            const countdownElement = document.getElementById('countdownNumber');

            const countdownInterval = setInterval(() => {
                countdownElement.textContent = count;

                // Анимация для последних 3 секунд
                if (count <= 3) {
                    countdownElement.style.animation = 'none';
                    void countdownElement.offsetWidth; // Trigger reflow
                    countdownElement.style.animation = 'pulse 0.5s infinite alternate';
                }

                count--;

                if (count < 0) {
                    clearInterval(countdownInterval);
                    document.getElementById('countdown').style.display = 'none';

                    // Отправляем форму после завершения отсчета
                    e.target.submit();
                }
            }, 1000);
        });
    </script>
</body>

</html>