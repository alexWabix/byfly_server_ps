<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш путешествия во Вьетнам</title>
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
            font-size: 28px;
            font-weight: bold;
            margin-top: 20px;
            padding: 20px;
            background: #4caf50;
            color: white;
            border-radius: 10px;
            display: none;
        }

        #timer {
            font-size: 30px;
            font-weight: bold;
            margin-top: 20px;
        }

        .start-btn {
            background: #ff5722;
            border: none;
            font-size: 22px;
            padding: 15px 30px;
            margin-top: 20px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="my-4">🎉 Розыгрыш путешествия во Вьетнам с ByFly Travel 🎉</h1>
        <button class="start-btn" onclick="startRaffle()">🚀 Начать розыгрыш</button>
        <div id="timer" class="mt-3" style="display:none;">⏳ 10 секунд</div>
        <div id="winners" class="mt-4">Победитель: </div>
        <div id="participants" class="d-flex flex-wrap justify-content-center mt-4">
            <?php
            include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
            $listUsersDB = $db->query("SELECT * FROM `users` WHERE grouped='29'");
            $participants = [];
            while ($listUsers = $listUsersDB->fetch_assoc()) {
                $name = $listUsers['name'] . ' ' . $listUsers['famale'] . ' ' . $listUsers['surname'];
                echo "<div class='participant'>$name</div>";
                $participants[] = $name;
            }
            ?>
        </div>
    </div>

    <script>
        let participants = <?php echo json_encode($participants, JSON_UNESCAPED_UNICODE); ?>;
        function startRaffle() {
            if (participants.length === 0) return;

            document.querySelector('.start-btn').style.display = 'none';
            let timerDisplay = document.getElementById('timer');
            let winnersBox = document.getElementById('winners');
            timerDisplay.style.display = 'block';

            let timeLeft = 10;
            let interval = setInterval(() => {
                timerDisplay.innerText = `⏳ ${timeLeft} секунд`;
                if (timeLeft === 0) {
                    clearInterval(interval);
                    timerDisplay.style.display = 'none';
                    let winner = participants[Math.floor(Math.random() * participants.length)];
                    winnersBox.innerText = `🎉 Победитель: ${winner} 🎉`;
                    winnersBox.style.display = 'block';
                }
                timeLeft--;
            }, 1000);
        }
    </script>
</body>

</html>