<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем параметры фильтрации
$city = $_GET['city'] ?? 'all';
$price = $_GET['price'] ?? 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 9;

// Запрос мероприятий (только одобренные - moderation_user_id != 0)
$where = "WHERE date_event > NOW() AND moderation_user_id != 0";
if ($city !== 'all') {
    $where .= " AND citys = '" . $db->real_escape_string($city) . "'";
}
if ($price === 'free') {
    $where .= " AND price_event = '0'";
}

// Общее количество мероприятий
$totalResult = $db->query("SELECT COUNT(*) as total FROM event_byfly $where");
$totalEvents = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalEvents / $perPage);

// Получаем мероприятия для текущей страницы
$offset = ($page - 1) * $perPage;
$events = [];
$result = $db->query("
    SELECT e.*, 
        (SELECT COUNT(*) FROM event_byfly_user_registered WHERE event_id = e.id) as participants_count,
        (SELECT link FROM event_byfly_photo WHERE event_id = e.id LIMIT 1) as image
    FROM event_byfly e
    $where
    ORDER BY date_event ASC
    LIMIT $perPage OFFSET $offset
");

if ($result) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
}

// Получаем список городов
$cities = ['all' => 'Все города'];
$result = $db->query("SELECT DISTINCT citys FROM event_byfly WHERE citys IS NOT NULL AND citys != '' AND moderation_user_id != 0");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cities[$row['citys']] = $row['citys'];
    }
}

// Функция проверки доступности изображения
function isImageAvailable($url)
{
    if (empty($url))
        return false;

    $headers = @get_headers($url);
    if ($headers && strpos($headers[0], '200')) {
        return strpos($headers[0], 'image/') !== false;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мероприятия ByFly Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="https://byfly.kz/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://byfly.kz/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://byfly.kz/favicon/favicon-16x16.png">
    <meta name="mailru-domain" content="0UeUnt61uJGav63i" />
    <link rel="manifest" href="https://byfly.kz/favicon/site.webmanifest">
    <style>
        :root {
            --deep-purple: #1A033A;
            --purple: #4A148C;
            --light-purple: #7B1FA2;
            --accent: #9C27B0;
            --pink: #E91E63;
            --text: #FFFFFF;
            --text-light: rgba(255, 255, 255, 0.8);
            --text-lighter: rgba(255, 255, 255, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--deep-purple);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Hero Section */
        .hero {
            padding: 60px 0;
            text-align: center;
            background: linear-gradient(rgba(26, 3, 58, 0.9), rgba(26, 3, 58, 0.9)),
                url('https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(to right, white, #E1BEE7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            color: var(--text-light);
        }

        /* Filters */
        .filters {
            background: rgba(74, 20, 140, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: -50px auto 50px;
            max-width: 1200px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 2;
        }

        .filters-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            font-weight: 600;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 15px;
            justify-content: center;
        }

        .filter-group {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 500;
            color: var(--text-light);
            font-size: 1rem;
        }

        select {
            width: 100%;
            padding: 15px 20px;
            border-radius: 10px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
            font-size: 16px;
            font-family: 'Montserrat', sans-serif;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 20px;
        }

        select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.3);
        }

        .toggle-container {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            height: 56px;
        }

        .toggle-container:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .toggle-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            width: 100%;
            justify-content: space-between;
        }

        .toggle-text {
            font-size: 1rem;
            color: var(--text-light);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-left: 15px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--accent);
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            padding: 0 0 60px;
        }

        .event-card {
            background: rgba(74, 20, 140, 0.3);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(156, 39, 176, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .event-image-placeholder {
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--purple), var(--accent));
        }

        .event-image-placeholder img {
            max-width: 60%;
            max-height: 60%;
            opacity: 0.8;
        }

        .event-content {
            padding: 25px;
        }

        .event-date {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .event-price-tag {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--accent);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(156, 39, 176, 0.4);
        }

        .event-price-tag.free {
            background: #4CAF50;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.4);
        }

        .event-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            color: var(--text-light);
            font-size: 14px;
        }

        .event-meta i {
            margin-right: 5px;
            color: var(--accent);
        }

        .event-description {
            margin-bottom: 20px;
            color: var(--text-light);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-button {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(90deg, var(--accent), var(--pink));
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
        }

        .event-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(156, 39, 176, 0.5);
        }

        /* No Events */
        .no-events {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .no-events i {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--accent);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 40px 0;
        }

        .pagination-list {
            display: flex;
            list-style: none;
            gap: 10px;
        }

        .pagination-item {
            margin: 0 5px;
        }

        .pagination-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .pagination-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .pagination-link.active {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 10px rgba(156, 39, 176, 0.4);
        }

        .pagination-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--purple), var(--deep-purple));
            padding: 60px 0 30px;
            text-align: center;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .footer-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .footer-logo img {
            height: 50px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .social-links a {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 20px;
            text-decoration: none;
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(156, 39, 176, 0.4);
        }

        .copyright {
            color: var(--text-lighter);
            font-size: 14px;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .events-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }

            .filter-group {
                min-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .hero {
                padding: 40px 0;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .filters {
                margin-top: -30px;
                padding: 20px;
            }

            .filter-group {
                min-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .events-grid {
                grid-template-columns: 1fr;
            }

            .event-card {
                max-width: 100%;
            }

            .pagination-list {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <section class="hero">
        <div class="container" style="text-align: center;">
            <a href="/" style="margin: auto; text-align: center;">
                <img width="200px" src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel">
            </a>
            <h1>Откройте мир с ByFly Travel</h1>
            < <p>Присоединяйтесь к нашим уникальным мероприятиям и получите незабываемые впечатления</p>
        </div>
    </section>

    <div class="container">
        <form class="filters" id="filter-form">
            <h3 class="filters-title">Найдите идеальное мероприятие</h3>
            <div class="filter-row">
                <div class="filter-group">
                    <label for="city">Город проведения</label>
                    <select id="city" name="city">
                        <?php foreach ($cities as $value => $name): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $city === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="price-filter">Тип мероприятий</label>
                    <div class="toggle-container">
                        <span class="toggle-text">Только бесплатные мероприятия</span>
                        <div class="toggle-switch">
                            <input type="checkbox" id="price-toggle" name="price" <?= $price === 'free' ? 'checked' : '' ?>
                                value="<?= $price === 'free' ? 'free' : 'all' ?>">
                            <span class="slider"></span>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="events-grid">
            <?php if (empty($events)): ?>
                <div class="no-events">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Мероприятий не найдено</h3>
                    <p>Попробуйте изменить параметры фильтрации или загляните позже</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <?php
                        $imageAvailable = !empty($event['image']) && isImageAvailable($event['image']);
                        if ($imageAvailable): ?>
                            <img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['name_events']) ?>"
                                class="event-image"
                                onerror="this.onerror=null;this.src='https://byfly.kz/assets/logo-610c625f.svg'">
                        <?php else: ?>
                            <div class="event-image-placeholder">
                                <img src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel">
                            </div>
                        <?php endif; ?>

                        <div class="event-date">
                            <?= date('d M', strtotime($event['date_event'])) ?>
                        </div>

                        <div class="event-price-tag <?= $event['price_event'] == 0 ? 'free' : '' ?>">
                            <?= $event['price_event'] == 0 ? 'Бесплатно' : number_format($event['price_event'], 0, '', ' ') . ' ₸' ?>
                        </div>

                        <div class="event-content">
                            <h3 class="event-title"><?= htmlspecialchars($event['name_events']) ?></h3>

                            <div class="event-meta">
                                <span><i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($event['citys'] ?: 'Онлайн') ?></span>
                                <span><i class="fas fa-users"></i> <?= (int) $event['participants_count'] ?> участников</span>
                            </div>

                            <p class="event-description">
                                <?= nl2br(htmlspecialchars(mb_substr($event['description'], 0, 200))) ?>
                                <?= mb_strlen($event['description']) > 200 ? '...' : '' ?>
                            </p>

                            <a href="register.php?event_id=<?= $event['id'] ?>" class="event-button">Подробнее</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <ul class="pagination-list">
                    <li class="pagination-item">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>"
                            class="pagination-link <?= $page == 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="pagination-item">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>"
                            class="pagination-link <?= $page == 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                        <li class="pagination-item">
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                class="pagination-link <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="pagination-item">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>"
                            class="pagination-link <?= $page == $totalPages ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="pagination-item">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"
                            class="pagination-link <?= $page == $totalPages ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container footer-content">
            <div class="footer-logo">
                <img src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel" style="height: 60px;">
            </div>

            <div class="social-links">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-telegram"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>

            <div class="copyright">
                &copy; <?= date('Y') ?> ByFly Travel. Все права защищены.
            </div>
        </div>
    </footer>

    <script>
        // Фильтрация без перезагрузки страницы
        document.getElementById('city').addEventListener('change', function () {
            document.getElementById('filter-form').submit();
        });

        document.getElementById('price-toggle').addEventListener('change', function () {
            this.value = this.checked ? 'free' : 'all';
            document.getElementById('filter-form').submit();
        });

        // Проверка изображений перед загрузкой
        document.querySelectorAll('.event-image').forEach(img => {
            img.onerror = function () {
                this.src = 'https://byfly.kz/assets/logo-610c625f.svg';
                this.style.objectFit = 'contain';
                this.style.padding = '20px';
                this.parentElement.classList.add('event-image-placeholder');
            };
        });
    </script>
</body>

</html>