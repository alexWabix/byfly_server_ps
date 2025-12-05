<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            padding: 20px;
            color: white;
        }

        .container {
            max-width: 800px;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 28px;
            font-weight: bold;
        }

        .participant {
            padding: 10px 15px;
            border-radius: 5px;
            background: #ffeb3b;
            color: black;
            margin: 5px;
            display: inline-block;
            font-weight: bold;
        }

        #winners {
            font-size: 24px;
            font-weight: bold;
            margin-top: 20px;
            padding: 15px;
            background: #4caf50;
            color: white;
            border-radius: 10px;
            display: none;
        }

        #timer {
            font-size: 24px;
            font-weight: bold;
            margin-top: 10px;
        }

        .btn-primary {
            background: #ff5722;
            border: none;
            font-size: 18px;
            padding: 10px 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="my-4">🎉 Розыгрыш сертификатов ByFly Travel 🎉</h1>
        <label for="numWinners" class="form-label">Количество победителей:</label>
        <input type="number" id="numWinners" class="form-control w-25 mx-auto text-center" min="1" value="1">
        <button class="btn btn-primary mt-3" onclick="startRaffle()">🎲 Выбрать</button>

        <div id="timer" class="mt-3">⏳ 5 секунд</div>
        <div id="winners" class="mt-4">Победители: </div>
        <div id="participants" class="d-flex flex-wrap justify-content-center mt-4">
            <?php
            include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
            $listUsersDB = $db->query("SELECT * FROM `users` WHERE `date_couch_start` IS NULL AND `for_couch` = 1 ORDER BY `start_test` DESC");
            $participants = [];
            while ($listUsers = $listUsersDB->fetch_assoc()) {
                $name = $listUsers['name'] . ' ' . $listUsers['famale'] . ' ' . $listUsers['surname'];
                echo "<div class='participant'>$name</div>";
                $participants[] = $name;
            }

            echo "<span>Всего участников: " . count($participants) . "</span>";
            ?>
        </div>
    </div>

    <script>
        let participants = <?php echo json_encode($participants, JSON_UNESCAPED_UNICODE); ?>;

        function startRaffle() {
            const numWinners = parseInt(document.getElementById('numWinners').value);
            if (numWinners <= 0 || numWinners > participants.length) return;

            let winnersBox = document.getElementById('winners');
            let timerDisplay = document.getElementById('timer');
            let countdown = 5;
            let interval = setInterval(() => {
                timerDisplay.innerText = `⏳ ${countdown} секунд`;
                countdown--;
                if (countdown < 0) {
                    clearInterval(interval);
                    selectWinners(numWinners);
                }
            }, 1000);

            let displayInterval = setInterval(() => {
                let randomIndex = Math.floor(Math.random() * participants.length);
                timerDisplay.innerText = `🔄 ${participants[randomIndex]}`;
            }, 100);

            setTimeout(() => { clearInterval(displayInterval); }, 5000);
        }

        function selectWinners(numWinners) {
            let shuffled = [...participants].sort(() => 0.5 - Math.random());
            let selectedWinners = shuffled.slice(0, numWinners);
            let winnersBox = document.getElementById('winners');
            winnersBox.style.display = 'block';
            winnersBox.innerHTML = `🏆 Победители: <br>${selectedWinners.join('<br>')}`;
        }
    </script>
</body>

</html>