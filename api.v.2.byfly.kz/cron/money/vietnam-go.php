<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи и их продажи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-card {
            transition: all 0.2s;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .paid-count {
            color: #28a745;
            font-weight: bold;
        }

        .free-count {
            color: #6c757d;
        }

        .sales-count {
            color: #007bff;
            font-weight: bold;
        }

        .user-name {
            font-weight: 500;
        }

        .agent-list {
            display: none;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .agent-item {
            padding: 8px 15px;
            margin-bottom: 5px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .paid-agent {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 3px solid #28a745;
        }

        .free-agent {
            background-color: rgba(108, 117, 125, 0.1);
            border-left: 3px solid #6c757d;
        }

        .badge-paid {
            background-color: #28a745;
        }

        .badge-free {
            background-color: #6c757d;
        }

        .badge-sales {
            background-color: #007bff;
        }

        .rotate-icon {
            transition: transform 0.3s;
        }

        .rotated {
            transform: rotate(180deg);
        }

        .user-rank {
            background-color: #343a40;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 14px;
        }

        .user-header {
            display: flex;
            align-items: center;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .agent-sales {
            font-size: 0.9em;
            color: #6c757d;
        }

        .agent-sales strong {
            color: #007bff;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">Пользователи и их продажи</h1>

        <div class="row">
            <?php
            include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
            $listUsers = array();

            $usersDB = $db->query("SELECT * FROM users WHERE tarif > 1");
            while ($users = $usersDB->fetch_assoc()) {
                $countPaidAgents = $db->query("SELECT COUNT(*) as ct FROM users WHERE tarif > 1 AND priced_coach > 0 AND parent_user ='" . $users['id'] . "'")->fetch_assoc()['ct'];
                $countFreeAgents = $db->query("SELECT COUNT(*) as ct FROM users WHERE tarif > 1 AND priced_coach = 0 AND parent_user ='" . $users['id'] . "'")->fetch_assoc()['ct'];
                $totalAgents = $countPaidAgents + $countFreeAgents;

                // Считаем продажи самого пользователя
                $userSales = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE user_id = '" . $users['id'] . "'")->fetch_assoc()['ct'];

                if ($totalAgents >= 3 || $userSales > 0) {
                    // Получаем список агентов с их продажами
                    $paidAgents = array();
                    $freeAgents = array();

                    // Платные агенты
                    $paidResult = $db->query("SELECT u.id, u.famale, u.name, u.surname, 
                                            (SELECT COUNT(*) FROM order_tours WHERE user_id = u.id) as sales_count
                                            FROM users u 
                                            WHERE u.tarif > 1 AND u.priced_coach > 0 AND u.parent_user ='" . $users['id'] . "'");
                    while ($agent = $paidResult->fetch_assoc()) {
                        $paidAgents[] = $agent;
                    }

                    // Бесплатные агенты
                    $freeResult = $db->query("SELECT u.id, u.famale, u.name, u.surname, 
                                            (SELECT COUNT(*) FROM order_tours WHERE user_id = u.id) as sales_count
                                            FROM users u 
                                            WHERE u.tarif > 1 AND u.priced_coach = 0 AND u.parent_user ='" . $users['id'] . "'");
                    while ($agent = $freeResult->fetch_assoc()) {
                        $freeAgents[] = $agent;
                    }

                    $users['paid_count'] = $countPaidAgents;
                    $users['free_count'] = $countFreeAgents;
                    $users['user_sales'] = $userSales;
                    $users['paid_agents'] = $paidAgents;
                    $users['free_agents'] = $freeAgents;
                    $listUsers[] = $users;
                }
            }

            // Сортируем по количеству продаж пользователя + его агентов
            usort($listUsers, function ($a, $b) {
                $aTotalSales = $a['user_sales'] + array_reduce($a['paid_agents'], function ($carry, $agent) {
                    return $carry + $agent['sales_count'];
                }, 0) + array_reduce($a['free_agents'], function ($carry, $agent) {
                    return $carry + $agent['sales_count'];
                }, 0);

                $bTotalSales = $b['user_sales'] + array_reduce($b['paid_agents'], function ($carry, $agent) {
                    return $carry + $agent['sales_count'];
                }, 0) + array_reduce($b['free_agents'], function ($carry, $agent) {
                    return $carry + $agent['sales_count'];
                }, 0);

                return $bTotalSales - $aTotalSales;
            });

            $rank = 1;
            foreach ($listUsers as $user) {
                $fullName = $user['famale'] . ' ' . $user['name'] . ' ' . $user['surname'];
                $totalAgents = $user['paid_count'] + $user['free_count'];

                // Считаем общее количество продаж пользователя и его агентов
                $totalSales = $user['user_sales'];
                foreach ($user['paid_agents'] as $agent) {
                    $totalSales += $agent['sales_count'];
                }
                foreach ($user['free_agents'] as $agent) {
                    $totalSales += $agent['sales_count'];
                }

                echo '
                <div class="col-md-12">
                    <div class="card user-card" onclick="toggleAgents(this)">
                        <div class="card-body">
                            <div class="user-header">
                                <div class="user-rank">' . $rank++ . '</div>
                                <h5 class="card-title user-name mb-0">' . htmlspecialchars($fullName) . '</h5>
                                <span class="badge badge-sales">Всего продаж: ' . $totalSales . '</span>
                            </div>
                            <div class="stats-row mt-3">
                                <div>
                                    <span class="badge badge-paid">' . $user['paid_count'] . ' платных</span>
                                </div>
                                <div>
                                    <span class="badge badge-free">' . $user['free_count'] . ' бесплатных</span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary">Личные продажи: ' . $user['user_sales'] . '</span>
                                </div>
                            </div>
                            <div class="agent-list" id="agents-' . $user['id'] . '">';

                // Выводим платных агентов
                if (!empty($user['paid_agents'])) {
                    echo '<h6 class="mt-3 text-success">Платные агенты:</h6>';
                    foreach ($user['paid_agents'] as $agent) {
                        $agentName = $agent['famale'] . ' ' . $agent['name'] . ' ' . $agent['surname'];
                        echo '<div class="agent-item paid-agent">
                                <span>' . htmlspecialchars($agentName) . '</span>
                                <span class="agent-sales">Продаж: <strong>' . $agent['sales_count'] . '</strong></span>
                              </div>';
                    }
                }

                // Выводим бесплатных агентов
                if (!empty($user['free_agents'])) {
                    echo '<h6 class="mt-3 text-secondary">Бесплатные агенты:</h6>';
                    foreach ($user['free_agents'] as $agent) {
                        $agentName = $agent['famale'] . ' ' . $agent['name'] . ' ' . $agent['surname'];
                        echo '<div class="agent-item free-agent">
                                <span>' . htmlspecialchars($agentName) . '</span>
                                <span class="agent-sales">Продаж: <strong>' . $agent['sales_count'] . '</strong></span>
                              </div>';
                    }
                }

                echo '
                            </div>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <script>
        function toggleAgents(card) {
            const agentList = card.querySelector('.agent-list');
            const icon = card.querySelector('.rotate-icon');

            if (agentList.style.display === 'block') {
                agentList.style.display = 'none';
                icon.classList.remove('rotated');
            } else {
                agentList.style.display = 'block';
                icon.classList.add('rotated');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>