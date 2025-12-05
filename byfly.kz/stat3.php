<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$query1 = "
    SELECT u1.name, u1.famale, u1.avatar, COUNT(u2.id) AS agent_count
    FROM users u1
    LEFT JOIN users u2 ON u2.parent_user = u1.id AND u2.date_couch_start IS NOT NULL
    WHERE u1.date_couch_start IS NOT NULL
    GROUP BY u1.id
    HAVING agent_count >= 10
";

$listActivesUserDB = $db->query($query1);
$listActivesUsers = [];
while ($row = $listActivesUserDB->fetch_assoc()) {
    if ($row['famale'] != 'Морозова') {
        $listActivesUsers[] = [
            'name' => $row['name'] . ' ' . $row['famale'],
            'agents' => $row['agent_count'],
            'avatar' => $row['avatar'],
        ];
    }
}

$query2 = "
    SELECT u1.name, u1.famale, u1.avatar, COUNT(u2.id) AS agent_count
    FROM users u1
    LEFT JOIN users u2 ON u2.parent_user = u1.id AND u2.date_couch_start IS NOT NULL
    WHERE u1.date_couch_start IS NOT NULL
    GROUP BY u1.id
    HAVING agent_count < 10
";

$listActivesUserDB2 = $db->query($query2);
$listActivesUsers2 = [];
while ($row = $listActivesUserDB2->fetch_assoc()) {
    $listActivesUsers2[] = [
        'name' => $row['name'] . ' ' . $row['famale'],
        'agents' => $row['agent_count'],
        'avatar' => $row['avatar'],
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Топ Агенты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="15;url=https://byfly.kz/stat.php">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
        }

        h1,
        h2 {
            color: #212529;
        }

        .gradient-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, red, darkred);
            color: white;
            font-weight: bold;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        .avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .search-input {
            margin-bottom: 20px;
            background-color: #fff;
            color: #212529;
            border: 1px solid #ccc;
        }

        .search-input::placeholder {
            color: #888;
        }

        .card {
            background-color: #fff;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-radius: 5px;
            flex-wrap: nowrap;
        }

        .card-title {
            color: #212529;
            margin: 0;
        }

        .leaderboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .user-trip-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            margin-right: 15px;
            flex-wrap: nowrap;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        .user-info h5,
        .user-info p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="mb-5">
            <h2>Агенты, которые отправляются в путешествие</h2>
            <p>Каждый, кто привлек 10 агентов на обучение, получает его бесплатно и отправляется в путешествие.</p>
            <div class="slider-container d-flex overflow-auto p-2"></div>
        </div>
        <div>
            <h2>Рейтинг Агентов</h2>
            <input type="text" id="search" class="form-control search-input" placeholder="Поиск агентов по имени">
            <div class="leaderboard-cards" id="leaderboard"></div>
        </div>
    </div>

    <script>
        <?php
        echo 'const travelingUsers = ' . json_encode($listActivesUsers, JSON_UNESCAPED_UNICODE) . ';';
        echo 'const leaderboardData = ' . json_encode($listActivesUsers2, JSON_UNESCAPED_UNICODE) . ';';
        ?>

        function getAvatar(user) {
            if (user.avatar) {
                return `<img src="${user.avatar}" alt="Аватар" class="avatar-img">`;
            } else {
                const initials = user.name.split(' ').map(word => word[0]).join('');
                return `<div class="gradient-avatar">${initials}</div>`;
            }
        }

        function populateUsers() {
            const travelingContainer = document.querySelector('.slider-container');
            travelingUsers.forEach(user => {
                travelingContainer.innerHTML += `
                    <div class="user-trip-card">
                        ${getAvatar(user)}
                        <div class="user-info">
                            <h5 class="card-title">${user.name}</h5>
                            <p class="card-text">Привлечено агентов: ${user.agents}</p>
                        </div>
                    </div>`;
            });
        }

        function populateLeaderboard(data = leaderboardData) {
            const leaderboardContainer = document.getElementById('leaderboard');
            leaderboardContainer.innerHTML = ''; // Очищаем контейнер перед обновлением
            data.sort((a, b) => b.agents - a.agents).forEach(user => {
                leaderboardContainer.innerHTML += `
                    <div class="card">
                        ${getAvatar(user)}
                        <div class="user-info">
                            <h5 class="card-title">${user.name}</h5>
                            <p class="card-text">Привлечено агентов: ${user.agents}</p>
                        </div>
                    </div>`;
            });
        }

        document.getElementById('search').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const filteredData = leaderboardData.filter(user => user.name.toLowerCase().includes(query));
            populateLeaderboard(filteredData);
        });

        populateUsers();
        populateLeaderboard();
    </script>
</body>

</html>