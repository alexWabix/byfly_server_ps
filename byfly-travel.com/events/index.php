<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID мероприятия из URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;



// Если ID не указан, показываем список мероприятий
if ($event_id === 0) {
    header("Location: https://byfly-travel.com/list-event.php");
    exit;
}

// Получаем данные о мероприятии
$event_query = $db->query("SELECT * FROM event_byfly WHERE id = $event_id");
if (!$event_query || $event_query->num_rows === 0) {
    header("Location: https://byfly-travel.com/last-event.php");
    exit;
}

$event = $event_query->fetch_assoc();

// Проверяем статус мероприятия
$current_time = time();
$event_time = strtotime($event['date_event']);
$is_past_event = $current_time > $event_time;

// Получаем количество зарегистрированных участников
$registered_query = $db->query("SELECT COUNT(*) as count FROM event_byfly_user_registered WHERE event_id = $event_id");
$registered_count = $registered_query->fetch_assoc()['count'];

// Получаем данные о призах
$prizes = json_decode($event['prizez'], true) ?? [];

// Получаем данные о программе
$program = json_decode($event['programes'], true) ?? [];

// Получаем контакты организаторов
$contacts = json_decode($event['contakctes'], true) ?? [];

// Получаем фото и видео мероприятия
$photos_query = $db->query("SELECT * FROM event_byfly_photo WHERE event_id = $event_id ORDER BY date_create DESC");
$photos = [];
while ($photo = $photos_query->fetch_assoc()) {
    $photos[] = $photo;
}

$videos_query = $db->query("SELECT * FROM event_byfly_videos WHERE event_id = $event_id ORDER BY date_create DESC");
$videos = [];
while ($video = $videos_query->fetch_assoc()) {
    $videos[] = $video;
}

// Получаем список организаторов
$organizers_query = $db->query("
    SELECT u.*, e.role 
    FROM event_byfly_users_work e
    JOIN users u ON e.user_id = u.id
    WHERE e.event_id = $event_id
");
$organizers = [];
while ($organizer = $organizers_query->fetch_assoc()) {
    $organizers[] = $organizer;
}

// Обработка формы регистрации
$registration_success = false;
$already_registered = false;
$no_seats_available = false;
$inviter_info = null;
$inviter_error = null;
$referral_id = isset($_GET['referral']) ? intval($_GET['referral']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_past_event && $registered_count < $event['max_people']) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $inviter_code = trim($_POST['inviter_code'] ?? '');
    $clear_phone = preg_replace('/\D/', '', $phone);

    // Проверяем пригласителя
    if (!empty($inviter_code)) {
        $inviter_query = $db->query("
            SELECT id, name, famale, surname, phone, promo_code 
            FROM users 
            WHERE promo_code = '" . $db->real_escape_string($inviter_code) . "' 
            OR phone = '" . $db->real_escape_string($clear_phone) . "'
        ");

        if ($inviter_query && $inviter_query->num_rows > 0) {
            $inviter = $inviter_query->fetch_assoc();
            $inviter_info = [
                'name' => trim($inviter['name'] . ' ' . $inviter['famale'] . ' ' . $inviter['surname']),
                'phone' => $inviter['phone'],
                'promo_code' => $inviter['promo_code']
            ];
            $referral_id = $inviter['id'];
        } else {
            $inviter_error = "Пригласитель не найден. Проверьте правильность введенных данных или оставьте поле пустым.";
        }
    }

    // Проверяем, не зарегистрирован ли уже
    $check_query = $db->query("
        SELECT id 
        FROM event_byfly_user_registered 
        WHERE event_id = $event_id AND user_phone = '" . $db->real_escape_string($clear_phone) . "'
    ");

    if ($check_query && $check_query->num_rows > 0) {
        $already_registered = true;
    } else {
        // Регистрируем участника
        $insert_query = $db->query("
            INSERT INTO event_byfly_user_registered (
                name_user, 
                user_phone, 
                date_registered, 
                is_registered, 
                event_id,
                is_refer_user_in_systems,
                email
            ) VALUES (
                '" . $db->real_escape_string($name) . "',
                '" . $db->real_escape_string($clear_phone) . "',
                NOW(),
                1,
                $event_id,
                " . ($referral_id > 0 ? $referral_id : 'NULL') . ",
                '" . $db->real_escape_string($email) . "'
            )
        ");

        if ($insert_query) {
            $registration_success = true;
            $registered_count++;

            // Отправляем билет в WhatsApp
            $message = "🎉 Поздравляем, $name!\n\n";
            $message .= "Вы успешно зарегистрированы на мероприятие:\n";
            $message .= "📌 " . $event['name_events'] . "\n";
            $message .= "📅 " . date('d.m.Y H:i', strtotime($event['date_event'])) . "\n";
            $message .= "📍 " . $event['adress'] . "\n\n";
            $message .= "Ваш билет:\n";
            $message .= "https://byfly-travel.com/events/$event_id/ticket\n\n";

            if ($referral_id > 0 && $inviter_info) {
                $message .= "🤝 Вас пригласил: " . $inviter_info['name'] . " (" . $inviter_info['phone'] . ")\n";
                $message .= "🎁 За ваше участие он получит бонусы!\n\n";
            }

            $message .= "📞 Контакты организатора: " . $event['contakctes'] . "\n";
            $message .= "До встречи на мероприятии!";

            // Функция отправки WhatsApp (нужно реализовать)
            whatsapp_send($clear_phone, $message);

            // Если мероприятие платное - уведомляем организатора
            if ($event['price_event'] > 0) {
                $payment_message = "💰 Новая регистрация на платное мероприятие:\n";
                $payment_message .= "Мероприятие: " . $event['name_events'] . "\n";
                $payment_message .= "Участник: $name\n";
                $payment_message .= "Телефон: $phone\n";
                $payment_message .= "Email: $email\n";
                $payment_message .= "Стоимость: " . $event['price_event'] . " ₸\n\n";
                $payment_message .= "Свяжитесь для подтверждения оплаты!";

                whatsapp_send("77085194866", $payment_message);
            }
        }
    }
} elseif ($registered_count >= $event['max_people']) {
    $no_seats_available = true;
}

// Форматируем дату мероприятия
$event_date = date('d.m.Y', strtotime($event['date_event']));
$event_time = date('H:i', strtotime($event['date_event']));
$full_date = date('d.m.Y в H:i', strtotime($event['date_event']));
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['name_events']); ?> - ByFly Travel</title>
    <meta name="description" content="<?php echo htmlspecialchars(strip_tags($event['description'])); ?>">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #e63946;
            --primary-dark: #c1121f;
            --secondary: #457b9d;
            --dark: #1d3557;
            --light: #f1faee;
            --accent: #a8dadc;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo !empty($photos) ? $photos[0]['link'] : 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80'; ?>');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            position: relative;
            padding: 100px 0;
        }

        .navbar {
            background-color: rgba(0, 0, 0, 0.8);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
        }

        .navbar.scrolled {
            background-color: var(--dark);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand img {
            height: 40px;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .section {
            padding: 80px 0;
        }

        .section-title {
            position: relative;
            margin-bottom: 50px;
            text-align: center;
        }

        .section-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--primary);
            margin: 20px auto 0;
        }

        .event-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .event-card-img {
            height: 200px;
            object-fit: cover;
        }

        .countdown {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
        }

        .countdown-item {
            display: inline-block;
            text-align: center;
            margin: 0 10px;
        }

        .countdown-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            display: block;
        }

        .countdown-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .speaker-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 30px;
        }

        .speaker-card:hover {
            transform: translateY(-10px);
        }

        .speaker-img {
            height: 300px;
            object-fit: cover;
        }

        .prize-card {
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background-color: var(--light);
            transition: transform 0.3s;
            height: 100%;
        }

        .prize-card:hover {
            transform: translateY(-10px);
        }

        .prize-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .program-item {
            border-left: 3px solid var(--primary);
            padding-left: 20px;
            margin-bottom: 30px;
        }

        .program-time {
            font-weight: 700;
            color: var(--primary);
        }

        .gallery-img {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .gallery-img:hover {
            transform: scale(1.03);
        }

        .modal-img {
            max-width: 100%;
            max-height: 80vh;
        }

        .contact-card {
            background-color: var(--light);
            border-radius: 10px;
            padding: 30px;
            height: 100%;
        }

        .contact-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .footer {
            background-color: var(--dark);
            color: white;
            padding: 50px 0 20px;
        }

        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background-color: var(--primary);
            transform: translateY(-5px);
        }

        /* Анимации */
        .animate-up {
            animation: fadeInUp 1s;
        }

        .animate-delay-1 {
            animation-delay: 0.2s;
        }

        .animate-delay-2 {
            animation-delay: 0.4s;
        }

        .animate-delay-3 {
            animation-delay: 0.6s;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .hero-section {
                min-height: auto;
                padding: 150px 0 80px;
            }

            .countdown-number {
                font-size: 1.8rem;
            }

            .section {
                padding: 60px 0;
            }
        }
    </style>
</head>

<body>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/images/logo-white.png" alt="ByFly Travel">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#about">О мероприятии</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#program">
                            Программа</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#speakers">Спикеры</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#prizes">Призы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Галерея</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#register">Регистрация</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Герой секция -->
<section class="hero-section" id="home">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-3 fw-bold mb-4 animate-up"><?php echo htmlspecialchars($event['name_events']); ?></h1>
                <p class="lead mb-5 animate-up animate-delay-1"><?php echo htmlspecialchars(strip_tags($event['description'])); ?></p>
                
                <?php if ($is_past_event): ?>
                    <div class="alert alert-warning animate-up animate-delay-2">
                        <i class="fas fa-info-circle me-2"></i> Это мероприятие уже завершено
                    </div>
                <?php elseif ($no_seats_available): ?>
                    <div class="alert alert-danger animate-up animate-delay-2">
                        <i class="fas fa-times-circle me-2"></i> Регистрация закрыта - все места заняты
                    </div>
                <?php else: ?>
                    <div class="countdown animate-up animate-delay-2">
                        <h5 class="mb-4">До начала мероприятия осталось:</h5>
                        <div class="d-flex justify-content-center">
                            <div class="countdown-item">
                                <span id="days" class="countdown-number">00</span>
                                <span class="countdown-label">дней</span>
                            </div>
                            <div class="countdown-item">
                                <span id="hours" class="countdown-number">00</span>
                                <span class="countdown-label">часов</span>
                            </div>
                            <div class="countdown-item">
                                <span id="minutes" class="countdown-number">00</span>
                                <span class="countdown-label">минут</span>
                            </div>
                            <div class="countdown-item">
                                <span id="seconds" class="countdown-number">00</span>
                                <span class="countdown-label">секунд</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 animate-up animate-delay-3">
                        <a href="#register" class="btn btn-primary btn-lg px-5 py-3">
                            <i class="fas fa-ticket-alt me-2"></i> Зарегистрироваться
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- О мероприятии -->
<section class="section" id="about">
    <div class="container">
        <h2 class="section-title">О мероприятии</h2>
        
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="pe-lg-5">
                    <h3 class="mb-4"><?php echo htmlspecialchars($event['name_events']); ?></h3>
                    <div class="mb-4">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <i class="fas fa-calendar-alt text-primary me-3 mt-1"></i>
                                <div>
                                    <h5>Дата и время</h5>
                                    <p><?php echo $full_date; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <i class="fas fa-map-marker-alt text-primary me-3 mt-1"></i>
                                <div>
                                    <h5>Место проведения</h5>
                                    <p><?php echo htmlspecialchars($event['adress']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <i class="fas fa-users text-primary me-3 mt-1"></i>
                                <div>
                                    <h5>Участники</h5>
                                    <p><?php echo $registered_count; ?> / <?php echo $event['max_people']; ?> мест</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <i class="fas fa-tag text-primary me-3 mt-1"></i>
                                <div>
                                    <h5>Стоимость</h5>
                                    <p><?php echo $event['price_event'] > 0 ? $event['price_event'] . ' ₸' : 'Бесплатно'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="ratio ratio-16x9">
                    <?php if (!empty($videos)): ?>
                        <iframe src="<?php echo str_replace('watch?v=', 'embed/', $videos[0]['link']); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen></iframe>
                    <?php else: ?>
                        <img src="<?php echo !empty($photos) ? $photos[0]['link'] : 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80'; ?>" 
                             alt="<?php echo htmlspecialchars($event['name_events']); ?>" 
                             class="img-fluid rounded">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Программа -->
<section class="section bg-light" id="program">
    <div class="container">
        <h2 class="section-title">Программа</h2>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (!empty($program)): ?>
                    <?php foreach ($program as $time => $item): ?>
                        <div class="program-item mb-4 animate-up">
                            <h5 class="program-time"><?php echo htmlspecialchars($time); ?></h5>
                            <p><?php echo htmlspecialchars($item); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Программа мероприятия будет объявлена позже
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Спикеры -->
<section class="section" id="speakers">
    <div class="container">
        <h2 class="section-title">Наши спикеры</h2>
        
        <div class="row">
            <?php if (!empty($organizers)): ?>
                <?php foreach ($organizers as $organizer): ?>
                    <div class="col-md-6 col-lg-4 mb-4 animate-up">
                        <div class="speaker-card">
                            <img src="<?php echo !empty($organizer['avatar']) ? $organizer['avatar'] : '/images/avatar-default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($organizer['name'] . ' ' . $organizer['famale']); ?>" 
                                 class="img-fluid speaker-img">
                            <div class="p-4">
                                <h4><?php echo htmlspecialchars($organizer['name'] . ' ' . $organizer['famale']); ?></h4>
                                <p class="text-primary mb-2"><?php echo htmlspecialchars($organizer['role']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($organizer['user_status']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Список спикеров будет объявлен позже
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Призы -->
<section class="section bg-light" id="prizes">
    <div class="container">
        <h2 class="section-title">Призы и подарки</h2>
        
        <div class="row">
            <?php if (!empty($prizes)): ?>
                <?php foreach ($prizes as $prize): ?>
                    <div class="col-md-4 mb-4 animate-up">
                        <div class="prize-card">
                            <div class="prize-icon">
                                <i class="fas fa-gift text-primary"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($prize['name']); ?></h4>
                            <p><?php echo htmlspecialchars($prize['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Информация о призах будет объявлена позже
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Галерея -->
<section class="section" id="gallery">
    <div class="container">
        <h2 class="section-title">Фото и видео</h2>
        
        <div class="row">
            <?php if (!empty($photos) || !empty($videos)): ?>
                <?php foreach ($photos as $photo): ?>
                    <div class="col-md-4 mb-4 animate-up">
                        <div class="gallery-img">
                            <img src="<?php echo $photo['link']; ?>" 
                                 alt="Фото с мероприятия <?php echo htmlspecialchars($event['name_events']); ?>" 
                                 class="img-fluid rounded" 
                                 data-bs-toggle="modal" 
                                 data-bs-target="#galleryModal"
                                 data-img="<?php echo $photo['link']; ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($videos as $video): ?>
                    <div class="col-md-4 mb-4 animate-up">
                        <div class="ratio ratio-16x9">
                            <iframe src="<?php echo str_replace('watch?v=', 'embed/', $video['link']); ?>" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Фото и видео появятся после мероприятия
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Регистрация -->
<section class="section bg-primary text-white" id="register">
    <div class="container">
        <h2 class="section-title text-white">Регистрация</h2>
        
        <?php if ($is_past_event): ?>
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="alert alert-info">
                        <h4>Мероприятие уже завершено</h4>
                        <p class="mb-0">Спасибо всем участникам! Следите за нашими новыми событиями.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($no_seats_available): ?>
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="alert alert-danger">
                        <h4>Регистрация закрыта</h4>
                        <p class="mb-0">К сожалению, все места на мероприятие уже заняты.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <?php if ($registration_success): ?>
                        <div class="alert alert-success animate-up">
                            <h4><i class="fas fa-check-circle me-2"></i> Спасибо за регистрацию!</h4>
                            <p>Мы отправили билет на ваш WhatsApp. До встречи на мероприятии!</p>
                            <?php if ($event['price_event'] > 0): ?>
                                <div class="mt-3">
                                    <p>Для подтверждения участия необходимо произвести оплату:</p>
                                    <a href="#" class="btn btn-light">Оплатить участие</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($already_registered): ?>
                        <div class="alert alert-warning animate-up">
                            <h4><i class="fas fa-exclamation-circle me-2"></i> Вы уже зарегистрированы</h4>
                            <p class="mb-0">Мы уже отправили вам билет на WhatsApp.</p>
                        </div>
                    <?php else: ?>
                        <div class="card shadow animate-up">
                            <div class="card-body p-5">
                                <h3 class="text-center mb-4 text-dark">Заполните форму</h3>
                                
                                <?php if ($inviter_error): ?>
                                    <div class="alert alert-danger">
                                        <?php echo htmlspecialchars($inviter_error); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($inviter_info): ?>
                                    <div class="alert alert-info mb-4">
                                        <h5><i class="fas fa-user-check me-2"></i> Ваш пригласитель</h5>
                                        <p class="mb-1"><strong>Имя:</strong> <?php echo htmlspecialchars($inviter_info['name']); ?></p>
                                        <p class="mb-1"><strong>Телефон:</strong> <?php echo htmlspecialchars($inviter_info['phone']); ?></p>
                                        <p class="mb-0"><strong>Промокод:</strong> <?php echo htmlspecialchars($inviter_info['promo_code']); ?></p>
                                        <hr>
                                        <p class="mb-0"><i class="fas fa-gift me-2"></i> За ваше участие пригласитель получит бонусы!</p>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="#register">
                                    <div class="mb-4">
                                        <label for="name" class="form-label text-dark">ФИО</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="phone" class="form-label text-dark">Телефон</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="email" class="form-label text-dark">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="inviter_code" class="form-label text-dark">Промокод или телефон пригласителя (необязательно)</label>
                                        <input type="text" class="form-control" id="inviter_code" name="inviter_code">
                                        <small class="text-muted">Если вас кто-то пригласил, укажите его промокод или номер телефона</small>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i> Зарегистрироваться
                                        </button>
                                    </div>
                                    
                                    <div class="form-text mt-3 text-muted">
                                        Нажимая кнопку, вы соглашаетесь с обработкой персональных данных и получением информационных сообщений от ByFly Travel.
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Контакты -->
<section class="section" id="contacts">
    <div class="container">
        <h2 class="section-title">Контакты</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4 animate-up">
                <div class="contact-card text-center">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4>Адрес</h4>
                    <p><?php echo htmlspecialchars($event['adress']); ?></p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4 animate-up animate-delay-1">
                <div class="contact-card text-center">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h4>Телефон</h4>
                    <p><a href="tel:<?php echo htmlspecialchars(str_replace([' ', '-', '(', ')'], '', event['contakctes'])); ?>"><?php echo htmlspecialchars(event['contakctes']); ?></a>
                                <div class="col-md-4 mb-4 animate-up animate-delay-2">
                <div class="contact-card text-center">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>Email</h4>
                    <p><a href="mailto:info@byfly.kz">info@byfly.kz</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Футер -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <img src="/images/logo-white.png" alt="ByFly Travel" class="mb-3" width="150">
                <p>Инновационный туризм с искусственным интеллектом. Путешествуйте выгодно и комфортно с ByFly Travel.</p>
                
                <div class="mt-4">
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-telegram-plane"></i></a>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <h5>Быстрые ссылки</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/" class="text-white">Главная</a></li>
                    <li class="mb-2"><a href="/tours" class="text-white">Туры</a></li>
                    <li class="mb-2"><a href="/events" class="text-white">Мероприятия</a></li>
                    <li class="mb-2"><a href="/about" class="text-white">О компании</a></li>
                    <li class="mb-2"><a href="/contact" class="text-white">Контакты</a></li>
                </ul>
            </div>
            
            <div class="col-lg-4 mb-4">
                <h5>Подписка на новости</h5>
                <p>Будьте в курсе наших акций и новых мероприятий</p>
                
                <form class="mt-3">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Ваш email">
                        <button class="btn btn-primary" type="submit">OK</button>
                    </div>
                </form>
            </div>
        </div>
        
        <hr class="my-4 bg-light">
        
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> ByFly Travel. Все права защищены.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">
                    <a href="/privacy" class="text-white me-3">Политика конфиденциальности</a>
                    <a href="/terms" class="text-white">Условия использования</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Модальное окно для галереи -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="" class="modal-img" id="modalGalleryImage">
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Inputmask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>

<!-- Custom JS -->
<script>
    // Обратный отсчет
    function updateCountdown() {
        const eventDate = new Date("<?php echo $event['date_event']; ?>").getTime();
        const now = new Date().getTime();
        const distance = eventDate - now;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("days").innerHTML = days.toString().padStart(2, "0");
        document.getElementById("hours").innerHTML = hours.toString().padStart(2, "0");
        document.getElementById("minutes").innerHTML = minutes.toString().padStart(2, "0");
        document.getElementById("seconds").innerHTML = seconds.toString().padStart(2, "0");

        if (distance < 0) {
            clearInterval(countdownInterval);
            document.getElementById("days").innerHTML = "00";
            document.getElementById("hours").innerHTML = "00";
            document.getElementById("minutes").innerHTML = "00";
            document.getElementById("seconds").innerHTML = "00";
        }
    }

    const countdownInterval = setInterval(updateCountdown, 1000);
    updateCountdown();

    // Маска для телефона
    $(document).ready(function() {
        $('#phone').inputmask({
            mask: '+7 (999) 999-99-99',
            placeholder: '_',
            showMaskOnHover: false,
            showMaskOnFocus: true
        });
    });

    // Галерея
    $(document).ready(function() {
        $('#galleryModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const imgSrc = button.data('img');
            $('#modalGalleryImage').attr('src', imgSrc);
        });
    });

    // Плавная прокрутка
    $(document).ready(function() {
        $('a[href^="#"]').on('click', function(event) {
            event.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 70
                }, 1000);
            }
        });
    });

    // Изменение навигации при скролле
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('.navbar').addClass('scrolled');
        } else {
            $('.navbar').removeClass('scrolled');
        }
    });

    // Проверка пригласителя
    $('#inviter_code').on('input', function() {
        const code = $(this).val().trim();
        if (code.length > 3) {
            $.ajax({
                url: '/api/check_inviter',
                method: 'POST',
                data: { code: code },
                success: function(response) {
                    if (response.success) {
                        // Показать информацию о пригласителе
                    } else {
                        // Показать ошибку
                    }
                }
            });
        }
    });
</script>

</body>
</html>