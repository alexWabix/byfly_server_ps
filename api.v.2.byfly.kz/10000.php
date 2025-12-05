<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function formatPhone($phone)
{
    if (!empty($phone) && strlen($phone) == 11) {
        return '+7 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7, 2) . '-' . substr($phone, 9, 2);
    }
    return $phone;
}

function getUserStatus($status)
{
    $statuses = [
        'user' => 'Пользователь',
        'agent' => 'Агент',
        'coach' => 'Коуч',
        'alpha' => 'Альфа',
        'ambasador' => 'Амбассадор'
    ];
    return $statuses[$status] ?? $status;
}

function priceTextString($price)
{
    $price = intval($price);
    return number_format($price, 0, '', ' ') . ' ₸';
}

function getTotalUsers($db)
{
    $result = $db->query("SELECT COUNT(*) as total FROM users");
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getTotalTours($db)
{
    $result = $db->query("SELECT SUM(count_sales) as total FROM byfly_super_offers_tours");
    return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
}

function getInitialsAvatar($name, $surname)
{
    $firstLetter = mb_substr($name, 0, 1, 'UTF-8');
    $secondLetter = mb_substr($surname, 0, 1, 'UTF-8');
    return "https://ui-avatars.com/api/?name=" . urlencode("$firstLetter $secondLetter") . "&background=random&size=150&color=fff&bold=true&font-size=0.6";
}

try {
    if (!($db instanceof mysqli)) {
        throw new Exception("Не удалось подключиться к базе данных");
    }

    $totalUsers = getTotalUsers($db);
    if ($totalUsers < 10000) {
        throw new Exception("В системе пока нет 10 000 пользователей. Текущее количество: $totalUsers");
    }

    $query = "SELECT * FROM users ORDER BY id ASC LIMIT 9999, 1";
    $result = $db->query($query);

    if (!$result) {
        throw new Exception("Ошибка выполнения запроса: " . $db->error);
    }

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $requiredFields = ['famale', 'name', 'phone', 'date_registration', 'user_status', 'balance', 'bonus'];
        foreach ($requiredFields as $field) {
            if (!isset($user[$field])) {
                throw new Exception("Отсутствует обязательное поле: $field");
            }
        }

        $fullName = trim(($user['famale'] ?? '') . ' ' . ($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''));
        $phone = formatPhone($user['phone'] ?? '');
        $regDate = date('d.m.Y H:i', strtotime($user['date_registration']));
        $status = getUserStatus($user['user_status'] ?? '');
        $balance = priceTextString($user['balance'] ?? 0);
        $bonus = priceTextString($user['bonus'] ?? 0);
        $totalTours = getTotalTours($db);
        $avatarUrl = getInitialsAvatar($user['name'] ?? '', $user['famale'] ?? '');
        ?>
        <!DOCTYPE html>
        <html lang="ru">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>10 000-й пользователь ByFly Travel</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                :root {
                    --primary-color: #0066cc;
                    --secondary-color: #00ccff;
                    --accent-color: #ff6b00;
                }

                body {
                    background-color: #f8f9fa;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }

                .hero-section {
                    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                    color: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
                    position: relative;
                    overflow: hidden;
                }

                .hero-section::before {
                    content: "";
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
                    animation: pulse 8s infinite linear;
                }

                @keyframes pulse {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }

                .user-card {
                    border-radius: 15px;
                    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                    transition: transform 0.3s, box-shadow 0.3s;
                    background: white;
                    position: relative;
                    overflow: hidden;
                    border: none;
                }

                .user-card:hover {
                    transform: translateY(-10px);
                    box-shadow: 0 15px 30px rgba(0, 102, 204, 0.2);
                }

                .user-card::after {
                    content: "";
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    height: 5px;
                    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
                }

                .reward-badge {
                    font-size: 1rem;
                    border-radius: 50px;
                    padding: 8px 15px;
                    font-weight: 600;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                }

                .stat-item {
                    border-left: 4px solid var(--primary-color);
                    padding-left: 15px;
                    margin-bottom: 15px;
                    transition: all 0.3s;
                }

                .stat-item:hover {
                    transform: translateX(5px);
                }

                .initials-avatar {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 42px;
                    font-weight: bold;
                    color: white;
                    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                    box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
                }

                .reward-card {
                    border-radius: 15px;
                    padding: 20px;
                    text-align: center;
                    height: 100%;
                    transition: all 0.3s;
                    background: white;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                }

                .reward-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px rgba(0, 102, 204, 0.15);
                }

                .reward-icon {
                    font-size: 3rem;
                    margin-bottom: 15px;
                    color: var(--primary-color);
                }

                .suitcase-icon {
                    position: relative;
                    display: inline-block;
                }

                .suitcase-icon::before {
                    content: "\f0f2";
                    font-family: "Font Awesome 6 Free";
                    font-weight: 900;
                    font-size: 60px;
                    color: var(--accent-color);
                }

                .suitcase-icon::after {
                    content: attr(data-initials);
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-size: 24px;
                    font-weight: bold;
                    color: white;
                }

                .confetti {
                    position: absolute;
                    width: 10px;
                    height: 10px;
                    background-color: #f00;
                    opacity: 0.7;
                }

                .pulse {
                    animation: pulse-animation 2s infinite;
                }

                @keyframes pulse-animation {
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

                .floating {
                    animation: floating 3s ease-in-out infinite;
                }

                @keyframes floating {
                    0% {
                        transform: translateY(0px);
                    }

                    50% {
                        transform: translateY(-10px);
                    }

                    100% {
                        transform: translateY(0px);
                    }
                }
            </style>
        </head>

        <body>
            <div class="container py-5">
                <!-- Шапка с поздравлением -->
                <div class="hero-section p-5 mb-5 text-center position-relative">
                    <div class="position-relative z-index-1">
                        <h1 class="display-4 fw-bold"><i class="fas fa-trophy me-3"></i>Юбилейный пользователь!</h1>
                        <p class="lead fs-4">Мы рады поздравить 10 000-го пользователя нашей платформы</p>
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <span class="reward-badge bg-white text-primary"><i
                                    class="fas fa-users me-2"></i><?= number_format($totalUsers, 0, '', ' ') ?>
                                пользователей</span>
                            <span class="reward-badge bg-white text-primary"><i
                                    class="fas fa-plane me-2"></i><?= number_format($totalTours, 0, '', ' ') ?> туров</span>
                        </div>
                    </div>
                </div>

                <!-- Основная информация о пользователе -->
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-10">
                        <div class="user-card p-4">
                            <div class="d-flex flex-column flex-md-row align-items-center">
                                <div class="mb-4 mb-md-0 me-md-4 position-relative">
                                    <div class="initials-avatar floating">
                                        <?= mb_substr($user['name'] ?? '', 0, 1, 'UTF-8') ?>        <?= mb_substr($user['famale'] ?? '', 0, 1, 'UTF-8') ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 text-center text-md-start">
                                    <h2 class="mb-2 fw-bold"><?= htmlspecialchars($fullName) ?></h2>
                                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3 mb-3">
                                        <span class="badge bg-primary fs-6"><i
                                                class="fas fa-user-tag me-2"></i><?= htmlspecialchars($status) ?></span>
                                        <span class="badge bg-success fs-6"><i
                                                class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($regDate) ?></span>
                                        <span class="badge bg-info fs-6"><i
                                                class="fas fa-phone-alt me-2"></i><?= htmlspecialchars($phone) ?></span>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="stat-item">
                                                <h5 class="text-muted"><i class="fas fa-wallet me-2"></i>Баланс</h5>
                                                <h3 class="text-success fw-bold"><?= htmlspecialchars($balance) ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="stat-item">
                                                <h5 class="text-muted"><i class="fas fa-gem me-2"></i>Бонусы</h5>
                                                <h3 class="text-warning fw-bold"><?= htmlspecialchars($bonus) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Награды пользователя -->
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="text-center mb-4 fw-bold"><i class="fas fa-gift me-2"></i>Специальные награды</h3>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="reward-card h-100 pulse">
                            <div class="reward-icon text-success">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h4 class="fw-bold">Бесплатное обучение</h4>
                            <p class="text-muted">Полный доступ ко всем обучающим материалам и курсам платформы</p>
                            <span class="badge bg-success bg-opacity-10 text-success">Активировано</span>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="reward-card h-100 pulse" style="animation-delay: 0.2s">
                            <div class="reward-icon text-warning">
                                <i class="fas fa-coins"></i>
                            </div>
                            <h4 class="fw-bold">10 000 бонусов</h4>
                            <p class="text-muted">Бонусные баллы для оплаты туров и дополнительных услуг</p>
                            <span class="badge bg-warning bg-opacity-10 text-warning">Зачислено</span>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="reward-card h-100 pulse" style="animation-delay: 0.4s">
                            <div class="reward-icon text-primary">
                                <div class="suitcase-icon"
                                    data-initials="<?= mb_substr($user['name'] ?? '', 0, 1, 'UTF-8') ?><?= mb_substr($user['famale'] ?? '', 0, 1, 'UTF-8') ?>">
                                </div>
                            </div>
                            <h4 class="fw-bold">Эксклюзивный чемодан</h4>
                            <p class="text-muted">Фирменный дорожный чемодан с персональной гравировкой</p>
                            <span class="badge bg-primary bg-opacity-10 text-primary">Ожидает получения</span>
                        </div>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="alert alert-info text-center">
                            <h4><i class="fas fa-info-circle me-2"></i>О программе</h4>
                            <p>Мы ценим каждого пользователя нашей платформы. 10 000-й пользователь получает специальные
                                привилегии в честь этого важного для нас события.</p>
                            <p class="mb-0">Спасибо, что выбираете ByFly Travel!</p>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                // Создаем конфетти
                function createConfetti() {
                    const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722'];

                    for (let i = 0; i < 50; i++) {
                        const confetti = document.createElement('div');
                        confetti.className = 'confetti';
                        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                        confetti.style.left = Math.random() * 100 + 'vw';
                        confetti.style.top = -10 + 'px';
                        confetti.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';

                        const animation = confetti.style.animation = 'fall ' + (Math.random() * 3 + 2) + 's linear forwards';
                        document.body.appendChild(confetti);

                        // Удаляем конфетти после анимации
                        setTimeout(() => {
                            confetti.remove();
                        }, 5000);
                    }
                }

                // Добавляем CSS для анимации конфетти
                const style = document.createElement('style');
                style.innerHTML = `
            @keyframes fall {
                to {
                    top: 100vh;
                    transform: rotate(360deg);
                }
            }
        `;
                document.head.appendChild(style);

                // Запускаем конфетти при загрузке страницы
                window.addEventListener('load', () => {
                    createConfetti();
                    setInterval(createConfetti, 3000);
                });
            </script>
        </body>

        </html>
        <?php
    } else {
        throw new Exception("10 000-й пользователь не найден, хотя общее количество пользователей: $totalUsers");
    }
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Ошибка: " . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html lang="ru">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ошибка</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="container py-5">
            <div class="alert alert-danger text-center">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Произошла ошибка</h4>
                <p>При обработке запроса произошла ошибка. Пожалуйста, попробуйте позже.</p>
                <small class="text-muted"><?= htmlspecialchars($e->getMessage()) ?></small>
            </div>
        </div>
    </body>

    </html>
    <?php
} finally {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>