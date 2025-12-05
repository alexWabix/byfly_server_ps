<?php
// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


// Получаем топ-20 агентов по балансу
$topBalanceQuery = $db->query("
    SELECT id, name, famale, surname, phone, balance, bonus, avatar 
    FROM users 
    ORDER BY balance DESC 
    LIMIT 200
");

$topBalanceAgents = [];
while ($row = $topBalanceQuery->fetch_assoc()) {
    $topBalanceAgents[] = $row;
}

// Получаем топ-20 агентов по бонусам
$topBonusQuery = $db->query("
    SELECT id, name, famale, surname, phone, balance, bonus, avatar 
    FROM users 
    ORDER BY bonus DESC 
    LIMIT 200
");

$topBonusAgents = [];
while ($row = $topBonusQuery->fetch_assoc()) {
    $topBonusAgents[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рейтинг агентов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            border: none;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }

        .agent-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .agent-card:last-child {
            border-bottom: none;
        }

        .agent-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid #fff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .agent-info {
            flex-grow: 1;
        }

        .agent-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .agent-phone {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .agent-balance {
            font-weight: 700;
            font-size: 1.1rem;
            color: #28a745;
            text-align: right;
        }

        .agent-bonus {
            font-weight: 700;
            font-size: 1.1rem;
            color: #ffc107;
            text-align: right;
        }

        .rank {
            width: 30px;
            height: 30px;
            background-color: #2575fc;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 0.9rem;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }

        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%);
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32 0%, #A0522D 100%);
        }

        .tab-content {
            padding: 20px 0;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 12px 20px;
        }

        .nav-tabs .nav-link.active {
            color: #2575fc;
            border-bottom: 3px solid #2575fc;
            background: transparent;
        }

        .section-title {
            position: relative;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            color: #333;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 3px;
        }

        .badge-balance {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .badge-bonus {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
    </style>
</head>

<body>
    <div class="header text-center">
        <div class="container">
            <h1><i class="bi bi-trophy-fill"></i> Рейтинг агентов</h1>
            <p class="lead">Топ-200 агентов по балансам и бонусам</p>
        </div>
    </div>

    <div class="container">
        <ul class="nav nav-tabs" id="agentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="balance-tab" data-bs-toggle="tab" data-bs-target="#balance"
                    type="button" role="tab">
                    <i class="bi bi-wallet2"></i> По балансам
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bonus-tab" data-bs-toggle="tab" data-bs-target="#bonus" type="button"
                    role="tab">
                    <i class="bi bi-gift-fill"></i> По бонусам
                </button>
            </li>
        </ul>

        <div class="tab-content" id="agentTabsContent">
            <!-- Таблица по балансам -->
            <div class="tab-pane fade show active" id="balance" role="tabpanel">
                <h3 class="section-title">Топ-200 агентов по балансам</h3>

                <div class="card">
                    <div class="card-body p-0">
                        <?php foreach ($topBalanceAgents as $index => $agent): ?>
                            <div class="agent-card">
                                <div class="rank <?= $index < 3 ? 'rank-' . ($index + 1) : '' ?>">
                                    <?= $index + 1 ?>
                                </div>

                                <?php if (!empty($agent['avatar'])): ?>
                                    <img src="<?= $agent['avatar'] ?>" class="agent-avatar" alt="Аватар">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($agent['name'] . '+' . $agent['famale']) ?>&background=random"
                                        class="agent-avatar" alt="Аватар">
                                <?php endif; ?>

                                <div class="agent-info">
                                    <div class="agent-name"><?= $agent['famale'] ?>     <?= $agent['name'] ?>
                                        <?= $agent['surname'] ?>
                                    </div>
                                    <div class="agent-phone"><i class="bi bi-telephone"></i> <?= $agent['phone'] ?></div>
                                </div>

                                <div class="agent-balance">
                                    <span class="badge badge-balance p-2">
                                        <i class="bi bi-wallet2"></i> <?= number_format($agent['balance'], 0, ',', ' ') ?> ₸
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Таблица по бонусам -->
            <div class="tab-pane fade" id="bonus" role="tabpanel">
                <h3 class="section-title">Топ-200 агентов по бонусам</h3>

                <div class="card">
                    <div class="card-body p-0">
                        <?php foreach ($topBonusAgents as $index => $agent): ?>
                            <div class="agent-card">
                                <div class="rank <?= $index < 3 ? 'rank-' . ($index + 1) : '' ?>">
                                    <?= $index + 1 ?>
                                </div>

                                <?php if (!empty($agent['avatar'])): ?>
                                    <img src="<?= $agent['avatar'] ?>" class="agent-avatar" alt="Аватар">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($agent['name'] . '+' . $agent['famale']) ?>&background=random"
                                        class="agent-avatar" alt="Аватар">
                                <?php endif; ?>

                                <div class="agent-info">
                                    <div class="agent-name"><?= $agent['famale'] ?>     <?= $agent['name'] ?>
                                        <?= $agent['surname'] ?>
                                    </div>
                                    <div class="agent-phone"><i class="bi bi-telephone"></i> <?= $agent['phone'] ?></div>
                                </div>

                                <div class="agent-bonus">
                                    <span class="badge badge-bonus p-2">
                                        <i class="bi bi-gift-fill"></i> <?= number_format($agent['bonus'], 0, ',', ' ') ?> ₸
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Анимация при загрузке
            $('.agent-card').each(function (i) {
                $(this).delay(i * 100).animate({ opacity: 1 }, 200);
            });
        });
    </script>
</body>

</html>