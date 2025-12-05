<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Выполняем SQL запрос
$sql = "SELECT 
    u.id as user_id,
    CONCAT(u.name, ' ', u.famale) as full_name,
    u.phone,
    u.user_status,
    COUNT(ot.id) as fully_paid_tours_count,
    SUM(ot.price) as total_tour_amount,
    SUM(ot.includesPrice) as total_paid_amount,
    AVG(ot.price) as avg_tour_price
FROM order_tours ot
INNER JOIN users u ON ot.user_id = u.id
WHERE 
    ot.date_create >= '2025-09-08 00:00:00'
    AND ot.date_create <= NOW()
    AND ot.includesPrice > 0
    AND ot.isCancle = 0
GROUP BY 
    u.id, u.name, u.famale, u.phone, u.user_status
ORDER BY 
    fully_paid_tours_count DESC, total_paid_amount DESC
LIMIT 10";

$result = $db->query($sql);
$rankingData = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rankingData[] = [
            'user_id' => (int) $row['user_id'],
            'full_name' => $row['full_name'],
            'phone' => $row['phone'],
            'user_status' => $row['user_status'],
            'fully_paid_tours_count' => (int) $row['fully_paid_tours_count'],
            'total_tour_amount' => (int) $row['total_tour_amount'],
            'total_paid_amount' => (int) $row['total_paid_amount'],
            'avg_tour_price' => (int) $row['avg_tour_price']
        ];
    }
}

// Получаем общую статистику
$statsSql = "SELECT 
    COUNT(DISTINCT u.id) as total_agents,
    COUNT(ot.id) as total_tours,
    SUM(ot.includesPrice) as total_amount,
    AVG(ot.price) as avg_tour_price
FROM order_tours ot
INNER JOIN users u ON ot.user_id = u.id
WHERE 
    ot.date_create >= '2025-09-08 00:00:00'
    AND ot.date_create <= NOW()
    AND ot.includesPrice > 0
    AND ot.isCancle = 0";

$statsResult = $db->query($statsSql);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Рейтинг топ агентов ByFly Travel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .period-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 30px;
            border-radius: 25px;
            display: inline-block;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #FFD700;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .ranking-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .table-header h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .ranking-list {
            padding: 0;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
            position: relative;
        }

        .ranking-item:hover {
            background-color: #f8f9ff;
        }

        .ranking-item:last-child {
            border-bottom: none;
        }

        .rank-badge {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #fff;
        }

        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #A0A0A0);
            color: #fff;
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #B8860B);
            color: #fff;
        }

        .rank-other {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .user-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }

        .user-detail {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }

        .user-detail i {
            margin-right: 5px;
            width: 16px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-agent {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-coach {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-alpha {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-ambasador {
            background: #e8f5e8;
            color: #388e3c;
        }

        .status-user {
            background: #f5f5f5;
            color: #616161;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-box {
            text-align: center;
            padding: 12px;
            background: #f8f9ff;
            border-radius: 10px;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 2px;
        }

        .stat-title {
            font-size: 0.8rem;
            color: #666;
        }

        .crown-icon {
            position: absolute;
            top: -5px;
            right: 20px;
            font-size: 1.5rem;
            color: #FFD700;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            opacity: 0.8;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ccc;
        }

        /* Мобильная адаптация */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .period-info {
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 30px;
            }

            .stat-card {
                padding: 20px 15px;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .ranking-item {
                padding: 15px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .rank-badge {
                margin-right: 0;
                margin-bottom: 15px;
                align-self: center;
            }

            .user-info {
                width: 100%;
                text-align: center;
            }

            .user-details {
                justify-content: center;
                font-size: 0.8rem;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-box {
                padding: 10px 8px;
            }

            .stat-value {
                font-size: 1rem;
            }

            .crown-icon {
                position: static;
                margin-left: 10px;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .ranking-item {
                padding: 15px;
            }

            .user-details {
                flex-direction: column;
                gap: 8px;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }
        }

        /* Анимации */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ranking-item {
            animation: fadeInUp 0.6s ease forwards;
        }

        .ranking-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .ranking-item:nth-child(2) {
            animation-delay: 0.2s;
        }

        .ranking-item:nth-child(3) {
            animation-delay: 0.3s;
        }

        .ranking-item:nth-child(4) {
            animation-delay: 0.4s;
        }

        .ranking-item:nth-child(5) {
            animation-delay: 0.5s;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Рейтинг топ агентов</h1>
            <p>Лучшие продавцы туров ByFly Travel</p>
            <div class="period-info">
                <i class="fas fa-calendar-alt"></i>
                Период: с 8 сентября 2025 по сегодня
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo number_format($stats['total_agents']); ?></div>
                <div class="stat-label">Активных агентов</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-plane"></i>
                <div class="stat-number"><?php echo number_format($stats['total_tours']); ?></div>
                <div class="stat-label">Продано туров</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave"></i>
                <div class="stat-number"><?php
                $totalAmount = $stats['total_amount'];
                if ($totalAmount >= 1000000) {
                    echo number_format($totalAmount / 1000000, 1) . 'М';
                } else if ($totalAmount >= 1000) {
                    echo number_format($totalAmount / 1000, 0) . 'К';
                } else {
                    echo number_format($totalAmount);
                }
                ?></div>
                <div class="stat-label">Общая сумма ₸</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="stat-number"><?php
                $avgTour = $stats['avg_tour_price'];
                if ($avgTour >= 1000000) {
                    echo number_format($avgTour / 1000000, 1) . 'М';
                } else if ($avgTour >= 1000) {
                    echo number_format($avgTour / 1000, 0) . 'К';
                } else {
                    echo number_format($avgTour);
                }
                ?></div>
                <div class="stat-label">Средний чек ₸</div>
            </div>
        </div>

        <div class="ranking-table">
            <div class="table-header">
                <h2><i class="fas fa-medal"></i> Топ-10 агентов по продажам</h2>
                <p>Рейтинг обновляется в реальном времени</p>
            </div>

            <div class="ranking-list">
                <?php if (empty($rankingData)): ?>
                    <div class="no-data">
                        <i class="fas fa-chart-line"></i>
                        <h3>Данные отсутствуют</h3>
                        <p>За указанный период продажи не найдены</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rankingData as $index => $user): ?>
                        <?php
                        $rank = $index + 1;
                        $rankClass = $rank <= 3 ? "rank-{$rank}" : 'rank-other';

                        // Определяем класс статуса
                        $statusClass = 'status-' . $user['user_status'];

                        // Определяем название статуса
                        $statusNames = [
                            'user' => 'Пользователь',
                            'agent' => 'Агент',
                            'coach' => 'Коуч',
                            'alpha' => 'Альфа',
                            'ambasador' => 'Амбассадор'
                        ];
                        $statusName = $statusNames[$user['user_status']] ?? 'Пользователь';

                        // Форматируем телефон
                        $phone = $user['phone'];
                        if (strlen($phone) == 11 && substr($phone, 0, 1) == '7') {
                            $phone = '+7 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7, 2) . '-' . substr($phone, 9, 2);
                        }
                        ?>

                        <div class="ranking-item">
                            <?php if ($rank <= 3): ?>
                                <i class="fas fa-crown crown-icon"></i>
                            <?php endif; ?>

                            <div class="rank-badge <?php echo $rankClass; ?>">
                                <?php echo $rank; ?>
                            </div>

                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <?php if ($rank === 1): ?>
                                        <i class="fas fa-crown" style="color: #FFD700; margin-left: 10px;"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="user-details">
                                    <div class="user-detail">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($phone); ?>
                                    </div>
                                    <div class="user-detail">
                                        <i class="fas fa-user-tag"></i>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusName; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="stats-row">
                                    <div class="stat-box">
                                        <div class="stat-value"><?php echo number_format($user['fully_paid_tours_count']); ?>
                                        </div>
                                        <div class="stat-title">Туров продано</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">
                                            <?php
                                            $totalPaid = $user['total_paid_amount'];
                                            if ($totalPaid >= 1000000) {
                                                echo number_format($totalPaid / 1000000, 1) . 'М ₸';
                                            } else if ($totalPaid >= 1000) {
                                                echo number_format($totalPaid / 1000, 0) . 'К ₸';
                                            } else {
                                                echo number_format($totalPaid) . ' ₸';
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-title">Сумма оплат</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">
                                            <?php
                                            $totalAmount = $user['total_tour_amount'];
                                            if ($totalAmount >= 1000000) {
                                                echo number_format($totalAmount / 1000000, 1) . 'М ₸';
                                            } else if ($totalAmount >= 1000) {
                                                echo number_format($totalAmount / 1000, 0) . 'К ₸';
                                            } else {
                                                echo number_format($totalAmount) . ' ₸';
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-title">Общая сумма</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">
                                            <?php
                                            $avgPrice = $user['avg_tour_price'];
                                            if ($avgPrice >= 1000000) {
                                                echo number_format($avgPrice / 1000000, 1) . 'М ₸';
                                            } else if ($avgPrice >= 1000) {
                                                echo number_format($avgPrice / 1000, 0) . 'К ₸';
                                            } else {
                                                echo number_format($avgPrice) . ' ₸';
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-title">Средний чек</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <p>
                <i class="fas fa-clock"></i>
                Последнее обновление: <?php echo date('d.m.Y H:i'); ?> (Алматы)
            </p>
            <p style="margin-top: 10px;">
                <i class="fas fa-info-circle"></i>
                Данные обновляются автоматически каждые 5 минут
            </p>
        </div>
    </div>

    <script>
        // Автообновление страницы каждые 5 минут
        setInterval(function () {
            location.reload();
        }, 300000); // 5 минут

        // Анимация чисел при загрузке
        document.addEventListener('DOMContentLoaded', function () {
            const statNumbers = document.querySelectorAll('.stat-number');

            statNumbers.forEach(element => {
                const finalText = element.textContent;
                const hasM = finalText.includes('М');
                const hasK = finalText.includes('К');
                const numericValue = parseFloat(finalText.replace(/[^\d.]/g, ''));

                if (numericValue > 0) {
                    let currentValue = 0;
                    const increment = numericValue / 50;
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= numericValue) {
                            element.textContent = finalText;
                            clearInterval(timer);
                        } else {
                            if (hasM) {
                                element.textContent = currentValue.toFixed(1) + 'М';
                            } else if (hasK) {
                                element.textContent = Math.floor(currentValue) + 'К';
                            } else {
                                element.textContent = Math.floor(currentValue).toLocaleString();
                            }
                        }
                    }, 20);
                }
            });

            // Анимация появления элементов рейтинга
            const rankingItems = document.querySelectorAll('.ranking-item');
            rankingItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';

                setTimeout(() => {
                    item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Добавляем эффект при наведении на карточки статистики
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-10px) scale(1.05)';
            });

            card.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Добавляем пульсацию для топ-3 позиций
        document.querySelectorAll('.rank-1, .rank-2, .rank-3').forEach(badge => {
            setInterval(() => {
                badge.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    badge.style.transform = 'scale(1)';
                }, 200);
            }, 3000);
        });

        // Показываем уведомление об обновлении
        let lastUpdateTime = new Date().getTime();

        setInterval(() => {
            const now = new Date().getTime();
            const timeDiff = now - lastUpdateTime;

            if (timeDiff >= 295000) { // За 5 секунд до обновления
                showUpdateNotification();
            }
        }, 1000);

        function showUpdateNotification() {
            const notification = document.createElement('div');
            notification.innerHTML = `
                <div style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    padding: 15px 25px;
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                    z-index: 1000;
                    animation: slideIn 0.5s ease;
                ">
                    <i class="fas fa-sync-alt fa-spin"></i>
                    Обновление данных...
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // CSS анимация для уведомления
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);

        // Добавляем конфетти для первого места
        <?php if (!empty($rankingData)): ?>
            setTimeout(() => {
                const firstPlace = document.querySelector('.rank-1');
                if (firstPlace) {
                    createConfetti(firstPlace);
                }
            }, 2000);
        <?php endif; ?>

        function createConfetti(element) {
            const colors = ['#FFD700', '#FFA500', '#FF6347', '#32CD32', '#1E90FF'];
            const rect = element.getBoundingClientRect();

            for (let i = 0; i < 20; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        left: ${rect.left + rect.width / 2}px;
                        top: ${rect.top}px;
                        width: 6px;
                        height: 6px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 1000;
                        animation: confettiFall 2s ease-out forwards;
                    `;

                    document.body.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 2000);
                }, i * 100);
            }
        }

        // CSS анимация для конфетти
        const confettiStyle = document.createElement('style');
        confettiStyle.textContent = `
            @keyframes confettiFall {
                0% {
                    transform: translateY(0) rotate(0deg);
                    opacity: 1;
                }
                100% {
                    transform: translateY(200px) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(confettiStyle);
    </script>
</body>

</html>