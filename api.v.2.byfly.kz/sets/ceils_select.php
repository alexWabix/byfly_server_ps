<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш путевки во Вьетнам 9 мая!</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: white;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }

        h1 {
            font-size: 3em;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }

        .subtitle {
            font-size: 1.5em;
            margin-bottom: 40px;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .participants-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .participant-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            width: 200px;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .participant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .participant-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 10px;
            border: 3px solid white;
        }

        .participant-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .participant-phone {
            font-size: 0.9em;
            opacity: 0.8;
        }

        .controls {
            margin: 40px 0;
        }

        .btn {
            background: #ff5722;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2em;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .btn:hover {
            background: #ff7043;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .countdown {
            font-size: 5em;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.7);
            display: none;
        }

        .winner-display {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }

        .winner-card {
            background: linear-gradient(45deg, #ff5722, #ff9800);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            animation: winnerPulse 2s infinite;
            box-shadow: 0 0 50px rgba(255, 87, 34, 0.7);
            transform: scale(0);
            transition: transform 1s;
        }

        .winner-card.show {
            transform: scale(1);
        }

        .winner-title {
            font-size: 2.5em;
            margin-bottom: 20px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .winner-name {
            font-size: 2em;
            margin: 20px 0;
            color: #fff;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .winner-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .winner-destination {
            font-size: 1.5em;
            margin-top: 20px;
            color: #fff;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f00;
            opacity: 0;
        }

        .highlight {
            animation: highlight 0.5s infinite alternate;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes winnerPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 50px rgba(255, 87, 34, 0.7);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 70px rgba(255, 87, 34, 0.9);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 50px rgba(255, 87, 34, 0.7);
            }
        }

        @keyframes highlight {
            from {
                box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            }

            to {
                box-shadow: 0 0 30px gold, 0 0 20px gold, 0 0 10px gold;
            }
        }

        @keyframes confettiFall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>РОЗЫГРЫШ ПУТЕВКИ ВО ВЬЕТНАМ</h1>
        <div class="subtitle">9 мая 2025 года в составе группового тура</div>

        <?php
        include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

        $ceilsInRozigrish = array();

        // Добавим отладочную информацию
        echo "<!-- Отладка: начало получения данных -->\n";

        $ceilsDB = $db->query("SELECT * FROM copilka_ceils WHERE date_dosrok_close IS NULL AND summ_money > 0");
        if (!$ceilsDB) {
            echo "<!-- Ошибка запроса copilka_ceils: " . $db->error . " -->\n";
        } else {
            echo "<!-- Найдено ячеек: " . $ceilsDB->num_rows . " -->\n";
        }

        while ($ceils = $ceilsDB->fetch_assoc()) {
            $userInfo = $db->query("SELECT * FROM users WHERE id='" . $ceils['user_id'] . "'");
            if (!$userInfo) {
                echo "<!-- Ошибка запроса users для user_id " . $ceils['user_id'] . ": " . $db->error . " -->\n";
                continue;
            }

            $userInfo = $userInfo->fetch_assoc();
            $searchTranzaction = $db->query("SELECT * FROM `user_tranzactions` WHERE `pay_info` NOT LIKE '%сертификат%' AND `pay_info` LIKE '%накопительной%' AND `summ` >= '50000' AND user_id='" . $userInfo['id'] . "'");

            if ($searchTranzaction->num_rows > 0) {
                array_push($ceilsInRozigrish, array(
                    "name" => $userInfo['famale'] . ' ' . $userInfo['name'] . ' ' . $userInfo['surname'],
                    "phone" => $userInfo['phone'],
                    "avatar" => $userInfo['avatar'],
                    "ceil_id" => $ceils['id'],
                ));
            }
        }

        echo "<!-- Участников для розыгрыша: " . count($ceilsInRozigrish) . " -->\n";
        ?>

        <div class="participants-container" id="participantsContainer">
            <?php if (count($ceilsInRozigrish) > 0): ?>
                <?php foreach ($ceilsInRozigrish as $participant): ?>
                    <div class="participant-card" data-id="<?= $participant['ceil_id'] ?>">
                        <img src="<?= !empty($participant['avatar']) ? $participant['avatar'] : 'https://i.pinimg.com/236x/8f/76/61/8f766151ed3c5e57d297c783a4a4b7e7.jpg' ?>"
                            alt="Аватар" class="participant-avatar">
                        <div class="participant-name"><?= htmlspecialchars($participant['name']) ?></div>
                        <div class="participant-phone"><?= htmlspecialchars($participant['phone']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div
                    style="color: white; font-size: 1.5em; padding: 20px; background: rgba(255,0,0,0.3); border-radius: 10px;">
                    Нет участников для розыгрыша. Проверьте условия отбора.
                </div>
            <?php endif; ?>
        </div>

        <div class="controls">
            <button id="startBtn" class="btn" <?= count($ceilsInRozigrish) === 0 ? 'disabled' : '' ?>>Выбрать
                победителя</button>
        </div>

        <div class="countdown" id="countdown">10</div>
    </div>

    <div class="winner-display" id="winnerDisplay">
        <div class="winner-card" id="winnerCard">
            <div class="winner-title">ПОБЕДИТЕЛЬ!</div>
            <img src="" alt="Победитель" class="winner-avatar" id="winnerAvatar">
            <div class="winner-name" id="winnerName"></div>
            <div class="winner-destination">Летит с нами 9 мая во Вьетнам!</div>
        </div>
    </div>

    <script>
        const participants = <?= json_encode($ceilsInRozigrish) ?>;
        console.log("Участники:", participants); // Отладочная информация в консоли

        const startBtn = document.getElementById('startBtn');
        const countdownEl = document.getElementById('countdown');
        const winnerDisplay = document.getElementById('winnerDisplay');
        const winnerCard = document.getElementById('winnerCard');
        const winnerName = document.getElementById('winnerName');
        const winnerAvatar = document.getElementById('winnerAvatar');
        const participantsContainer = document.getElementById('participantsContainer');

        let isRunning = false;
        let countdownValue = 10;
        let highlightInterval;
        let winnerSelected = false;

        startBtn.addEventListener('click', startSelection);

        function startSelection() {
            if (isRunning || participants.length === 0) return;

            isRunning = true;
            startBtn.disabled = true;
            countdownEl.style.display = 'block';

            // Начать обратный отсчет
            const countdownInterval = setInterval(() => {
                countdownEl.textContent = countdownValue;
                countdownValue--;

                if (countdownValue < 0) {
                    clearInterval(countdownInterval);
                    countdownEl.style.display = 'none';
                    startHighlighting();
                }
            }, 1000);
        }

        function startHighlighting() {
            const cards = document.querySelectorAll('.participant-card');
            if (cards.length === 0) {
                alert('Нет участников для выбора!');
                return;
            }

            let currentIndex = 0;
            let speed = 100; // начальная скорость в мс

            // Сначала быстро перебираем участников
            highlightInterval = setInterval(() => {
                // Удаляем highlight у всех
                cards.forEach(card => card.classList.remove('highlight'));

                // Добавляем highlight текущему
                cards[currentIndex].classList.add('highlight');

                // Переходим к следующему
                currentIndex = (currentIndex + 1) % cards.length;

                // Постепенно замедляемся
                if (speed < 300) {
                    clearInterval(highlightInterval);
                    speed += 20;
                    highlightInterval = setInterval(arguments.callee, speed);
                }

                // Когда достигаем определенной скорости, выбираем победителя
                if (speed >= 300 && !winnerSelected) {
                    winnerSelected = true;
                    setTimeout(() => {
                        clearInterval(highlightInterval);
                        selectWinner();
                    }, 2000);
                }
            }, speed);
        }

        function selectWinner() {
            if (participants.length === 0) {
                alert('Нет участников для выбора победителя!');
                return;
            }

            // Выбираем случайного победителя
            const winnerIndex = Math.floor(Math.random() * participants.length);
            const winner = participants[winnerIndex];
            console.log("Выбран победитель:", winner); // Отладочная информация

            // Показываем победителя
            winnerName.textContent = winner.name;
            winnerAvatar.src = winner.avatar || 'https://i.pinimg.com/236x/8f/76/61/8f766151ed3c5e57d297c783a4a4b7e7.jpg';

            // Показываем экран победителя
            winnerDisplay.style.display = 'flex';
            setTimeout(() => {
                winnerCard.classList.add('show');
                createConfetti();
            }, 100);

            // Помечаем победителя в списке
            const winnerCardEl = document.querySelector(`.participant-card[data-id="${winner.ceil_id}"]`);
            if (winnerCardEl) {
                winnerCardEl.classList.add('highlight');
                winnerCardEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function createConfetti() {
            const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];

            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animation = `confettiFall ${Math.random() * 3 + 2}s linear forwards`;
                confetti.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(confetti);
            }
        }
    </script>
</body>

</html>