<?php
include('config.php');

$userId = isset($_GET['id']) ? intval($_GET['id']) : 2;
$data = getUserComandsInfo($userId);
$parent = isset($data['userInfo']['parent_user']) ? infoUserId($data['userInfo']['parent_user']) : null;
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка данных пользователя</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
        }

        .user-info,
        .parent-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 10px;
        }

        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid #ddd;
            border-bottom: none;
            background: #f9f9f9;
        }

        .tab.active {
            background: white;
            font-weight: bold;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        input {
            padding: 5px;
            width: 60px;
            margin-right: 10px;
        }
    </style>
    <script>
        function showTab(tabIndex) {
            document.querySelectorAll(".tab-content").forEach((el, i) => {
                el.style.display = i === tabIndex ? "block" : "none";
            });
            document.querySelectorAll(".tab").forEach((el, i) => {
                el.classList.toggle("active", i === tabIndex);
            });
        }
        document.addEventListener("DOMContentLoaded", () => showTab(0));
    </script>
</head>

<body>

    <div class="container">
        <h2>Проверка данных пользователя</h2>
        <form method="GET">
            <label for="userId">ID пользователя:</label>
            <input type="number" id="userId" name="id" value="<?= $userId ?>" required>
            <button type="submit">Поиск</button>
        </form>

        <h3>Пригласивший</h3>
        <?php if ($parent): ?>
            <div class="parent-info">
                <img class="avatar" src="<?= $parent['avatar'] ?>" alt="Аватар">
                <div>
                    <p><strong><?= $parent['name'] . ' ' . $parent['surname'] ?></strong></p>
                    <p>Телефон: <?= $parent['phone'] ?></p>
                    <p>Email: <?= $parent['email'] ?></p>
                </div>
            </div>
        <?php else: ?>
            <p>Нет данных о пригласившем</p>
        <?php endif; ?>

        <h3>Данные пользователя</h3>
        <?php if ($data['userInfo']): ?>
            <div class="user-info">
                <img class="avatar" src="<?= $data['userInfo']['avatar'] ?>" alt="Аватар">
                <div>
                    <p><strong><?= $data['userInfo']['famale'] . ' ' . $data['userInfo']['name'] . ' ' . $data['userInfo']['surname'] ?></strong>
                    </p>
                    <p>Телефон: <?= $data['userInfo']['phone'] ?></p>
                    <p>Email: <?= $data['userInfo']['email'] ?></p>
                    <p>Баланс: <?= $data['userInfo']['balance'] ?>₸</p>
                </div>
            </div>
        <?php else: ?>
            <p>Пользователь не найден</p>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="showTab(0)">1-я линия</div>
            <div class="tab" onclick="showTab(1)">2-я линия</div>
            <div class="tab" onclick="showTab(2)">3-я линия</div>
            <div class="tab" onclick="showTab(3)">4-я линия</div>
            <div class="tab" onclick="showTab(4)">5-я линия</div>
        </div>

        <?php
        function renderUsersTable($users)
        {
            if ($users && count($users) > 0) {
                echo "<table>
                    <tr>
                        <th>Аватар</th>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Накопительные ячейки</th>
                        <th>Отменено туров</th>
                        <th>На подтверждении</th>
                        <th>Ожидают вылета</th>
                        <th>Ожидают оплату</th>
                        <th>На отдыхе</th>
                        <th>Всего продано туров</th>
                    </tr>";
                foreach ($users as $user) {
                    echo "<tr>
                        <td><img class='avatar' src='{$user['avatar']}' alt='Аватар'></td>
                        <td>{$user['id']}</td>
                        <td>{$user['famale']} {$user['name']} {$user['surname']}</td>
                        <td>{$user['phone']}</td>
                        <td>{$user['email']}</td>
                        <td>" . count($user['copilka_ceils']) . "</td>
                        <td>" . $user['count_tours_cancle'] . "</td>
                        <td>" . $user['count_tours_start'] . "</td>
                        <td>" . $user['count_tours_await'] . "</td>
                        <td>" . $user['count_tours_await_pay'] . "</td>
                        <td>" . $user['count_tours_flight'] . "</td>
                        <td>" . $user['count_tours_sended'] . "</td>
                      </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Нет данных</p>";
            }
        }
        ?>

        <div class="tab-content active"><?php renderUsersTable($data['userLines1']); ?></div>
        <div class="tab-content"><?php renderUsersTable($data['userLines2']); ?></div>
        <div class="tab-content"><?php renderUsersTable($data['userLines3']); ?></div>
        <div class="tab-content"><?php renderUsersTable($data['userLines4']); ?></div>
        <div class="tab-content"><?php renderUsersTable($data['userLines5']); ?></div>

    </div>

</body>

</html>