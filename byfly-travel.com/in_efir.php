<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($userId > 0) {
    // Обновляем статус в эфире
    $db->query("UPDATE users_rozygrysh SET in_efir = 1 WHERE user_id = $userId");
}

// Получаем список всех участников
$result = $db->query("SELECT u.name, u.phone, ur.in_efir 
                     FROM users_rozygrysh ur 
                     JOIN users u ON ur.user_id = u.id 
                     WHERE ur.user_id != 0 
                     ORDER BY ur.in_efir DESC, u.name ASC");
$participants = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Участники розыгрыша ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #6200EA, #03DAC6);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .participant-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .participant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .in-efir {
            border-left: 5px solid #28a745;
        }

        .not-in-efir {
            border-left: 5px solid #6c757d;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
        }

        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <div class="header text-center">
        <div class="container">
            <h1 class="display-4">Розыгрыш ByFly Travel</h1>
            <p class="lead">Список участников и статус их участия</p>
        </div>
    </div>

    <div class="container">
        <?php if ($userId > 0): ?>
            <div class="alert alert-success text-center mb-4">
                <h4 class="alert-heading">Спасибо за участие!</h4>
                <p>Ваш статус в эфире подтверждён. Теперь вы участвуете в розыгрыше.</p>
                <hr>
                <p class="mb-0">Ожидайте начала трансляции и объявления победителей.</p>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Статистика</h5>
                        <div class="d-flex justify-content-between">
                            <div class="text-center">
                                <h2 class="text-primary"><?= count($participants) ?></h2>
                                <small class="text-muted">Всего участников</small>
                            </div>
                            <div class="text-center">
                                <h2 class="text-success">
                                    <?= count(array_filter($participants, fn($p) => $p['in_efir'] == 1)) ?></h2>
                                <small class="text-muted">В эфире</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Информация</h5>
                        <p class="card-text">Для участия в розыгрыше необходимо находиться в эфире во время проведения
                            розыгрыша.</p>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4">Список участников</h3>

        <div class="mb-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary filter-btn active" data-filter="all">Все</button>
                <button type="button" class="btn btn-outline-success filter-btn" data-filter="in-efir">В эфире</button>
            </div>
        </div>

        <div class="row" id="participants-container">
            <?php foreach ($participants as $participant): ?>
                <div class="col-md-6 col-lg-4 mb-4 participant-item"
                    data-status="<?= $participant['in_efir'] == 1 ? 'in-efir' : 'not-in-efir' ?>">
                    <div
                        class="card participant-card h-100 <?= $participant['in_efir'] == 1 ? 'in-efir' : 'not-in-efir' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($participant['name']) ?></h5>
                                <span
                                    class="badge <?= $participant['in_efir'] == 1 ? 'bg-success' : 'bg-secondary' ?> status-badge">
                                    <?= $participant['in_efir'] == 1 ? 'В эфире' : 'Не в эфире' ?>
                                </span>
                            </div>
                            <p class="card-text text-muted"><?= htmlspecialchars($participant['phone']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Фильтрация участников
            $('.filter-btn').click(function () {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                const filter = $(this).data('filter');

                if (filter === 'all') {
                    $('.participant-item').show();
                } else {
                    $('.participant-item').hide();
                    $(`.participant-item[data-status="${filter}"]`).show();
                }
            });

            <?php if ($userId > 0): ?>
                // Эффект конфетти для нового участника
                setTimeout(() => {
                    const confettiSettings = {
                        target: 'confetti-canvas',
                        max: 100,
                        size: 1.5,
                        animate: true,
                        props: ['circle', 'square', 'triangle', 'line'],
                        colors: [[255, 0, 0], [0, 255, 0], [0, 0, 255]],
                        clock: 25,
                        rotate: true,
                        start_from_edge: true,
                        respawn: false
                    };

                    const confetti = new ConfettiGenerator(confettiSettings);
                    confetti.render();

                    setTimeout(() => confetti.clear(), 5000);
                }, 1000);
            <?php endif; ?>
        });
    </script>

    <?php if ($userId > 0): ?>
        <canvas id="confetti-canvas" class="confetti"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/confetti-js@0.0.18/dist/index.min.js"></script>
    <?php endif; ?>
</body>

</html>