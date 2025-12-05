<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if (isset($_GET['source']))
    setcookie('event_source', $_GET['source'], time() + 3600 * 24 * 100, "/");
if (isset($_GET['agent']))
    setcookie('event_agent', intval($_GET['agent']), time() + 3600 * 24 * 100, "/");
$event_source = $_COOKIE['event_source'] ?? ($_GET['source'] ?? '');
$event_agent = empty($_GET['agent']) ? intval($_COOKIE['event_agent'] ?? ($_GET['agent'] ?? 0)) : intval($_GET['agent']);

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id) {
    header("Location: /index.php");
    exit;
}

$event = $db->query("SELECT * FROM event_byfly WHERE id = " . $event_id)->fetch_assoc();
if (!$event) {
    header("Location: /404.php");
    exit;
}

if ($event['moderation_user_id'] == 0) {
    header("Location: /event-not-approved.php?id=" . $event_id);
    exit;
}

// Проверка если мероприятие уже прошло
$event_time_future = (strtotime($event['date_event']) > time());
if (!$event_time_future) {
    header("Location: /event-ended.php?id=" . $event_id);
    exit;
}

// Проверка свободных мест
$registered_count = $db->query("SELECT COUNT(*) as cnt FROM event_byfly_user_registered WHERE event_id = $event_id")->fetch_assoc()['cnt'];
$available_seats = $event['max_people'] - $registered_count;
if ($available_seats <= 0) {
    header("Location: /event-full.php?id=" . $event_id);
    exit;
}

// ---------- Пригласитель (agent/id, промо/phone) ----------
function find_inviter($id = 0, $code = '', $phone = '')
{
    global $db;
    if (!$id && !$code && !$phone)
        return false;
    if ($id)
        return $db->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
    if ($code)
        return $db->query("SELECT * FROM users WHERE promo_code='" . addslashes($code) . "'")->fetch_assoc();
    if ($phone)
        return $db->query("SELECT * FROM users WHERE phone='" . addslashes($phone) . "'")->fetch_assoc();
    return false;
}
$__inviter = $event_agent ? find_inviter($event_agent) : false;



// ---------- Регистрация ----------
$register_success = false;
$reg_error = '';
$already_registered = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_fio'])) {
    $fio = trim($_POST['reg_fio']);
    $country = $_POST['reg_country'];
    $phone = preg_replace('/\D/', '', $_POST['reg_phone']);
    $codepromo = trim($_POST['reg_inviter_code'] ?? '');
    $telepromo = preg_replace('/\D/', '', $_POST['reg_inviter_phone'] ?? '');
    $refer_whats = $_POST['reg_refer_whats'] ?: $event_source;

    $manual_inv = $codepromo ? find_inviter(0, $codepromo) : ($telepromo ? find_inviter(0, '', $telepromo) : false);
    if (!$manual_inv && $__inviter)
        $manual_inv = $__inviter;

    $isRegisterred = 0;
    $searchUser = $db->query("SELECT * FROM users WHERE phone='" . $phone . "'");
    if ($searchUser->num_rows > 0) {
        $search = $searchUser->fetch_assoc();
        $isRegisterred = $search['id'];
    }

    // Проверка дублирования
    $exist = $db->query("SELECT id FROM event_byfly_user_registered WHERE user_phone='$phone' AND event_id=$event_id LIMIT 1")->fetch_assoc();
    if ($exist) {
        $already_registered = true;
    } else if (!$fio || !$phone) {
        $reg_error = "Заполните ФИО и телефон!";
    } else {
        $manual_inv_id = $manual_inv ? intval($manual_inv['id']) : 'NULL';
        $db->query("INSERT INTO event_byfly_user_registered (name_user,user_phone,date_registered,is_registered,event_id,is_refer_user_in_systems,refer_whats)
          VALUES ('" . addslashes($fio) . "','" . addslashes($phone) . "',NOW(),'" . $isRegisterred . "',
          $event_id,$manual_inv_id,'" . addslashes($refer_whats) . "')");

        $registration_id = $db->insert_id;
        $ticketLink = "https://byfly-travel.com/tickets.php?id=" . $registration_id;

        // 1. Сообщение участнику
        $user_message = "🎉 Спасибо за регистрацию на мероприятие " . $event['name_events'] . "!\n\n"
            . "📅 Дата: " . date('d.m.Y H:i', strtotime($event['date_event'])) . "\n"
            . "📍 Адрес: " . $event['adress'] . "\n\n"
            . "Ваш электронный билет: " . $ticketLink . "\n\n"
            . "Ждем вас на мероприятии!";

        sendWhatsapp($phone, $user_message);

        // 2. Сообщение организатору мероприятия
        $organizer = $db->query("SELECT phone FROM users WHERE id = " . $event['user_id'])->fetch_assoc();
        if ($organizer && $organizer['phone']) {
            $organizer_message = "📢 Новый участник на мероприятие " . $event['name_events'] . "!\n\n"
                . "👤 ФИО: " . $fio . "\n"
                . "📞 Телефон: " . formatPhone($phone) . "\n"
                . "🎫 Номер регистрации: #" . $registration_id;

            sendWhatsapp($organizer['phone'], $organizer_message);
        }

        // 3. Сообщение пригласителю (если есть)
        if ($manual_inv && $manual_inv['phone']) {
            $inviter_message = "👋 Вы пригласили нового участника на мероприятие " . $event['name_events'] . "!\n\n"
                . "👤 ФИО: " . $fio . "\n"
                . "📞 Телефон: " . formatPhone($phone) . "\n\n"
                . "Спасибо за приглашение!";

            sendWhatsapp($manual_inv['phone'], $inviter_message);
        }

        $register_success = true;
    }
}

function cleanPhone($phone)
{
    // Удаляем все символы, кроме цифр
    $cleanPhone = preg_replace('/\D/', '', $phone);

    // Если номер начинается с 8 (для Казахстана), заменяем на 7
    if (strlen($cleanPhone) === 11 && $cleanPhone[0] === '8') {
        $cleanPhone = '7' . substr($cleanPhone, 1);
    }

    // Если номер начинается с +7 или +77, убираем плюс
    if ((strpos($phone, '+7') === 0)) {
        $cleanPhone = '7' . substr($cleanPhone, 2);
    }

    // Возвращаем очищенный номер (только цифры)
    return $cleanPhone;
}

function formatPhone($phone)
{
    // Удаляем все нецифровые символы
    $cleanPhone = preg_replace('/\D/', '', $phone);

    // Определяем код страны по длине номера
    if (strlen($cleanPhone) === 11 && $cleanPhone[0] === '7') {
        // Российский номер: 7XXXXXXXXXX
        return '+7 (' . substr($cleanPhone, 1, 3) . ') ' . substr($cleanPhone, 4, 3) . '-' . substr($cleanPhone, 7, 2) . '-' . substr($cleanPhone, 9, 2);
    } elseif (strlen($cleanPhone) === 11 && $cleanPhone[0] === '8') {
        // Номер Казахстана с ведущей 8: 8XXXXXXXXXX
        return '+7 (' . substr($cleanPhone, 1, 3) . ') ' . substr($cleanPhone, 4, 3) . '-' . substr($cleanPhone, 7, 2) . '-' . substr($cleanPhone, 9, 2);
    } elseif (strlen($cleanPhone) === 10 && (substr($cleanPhone, 0, 1) === '7' || substr($cleanPhone, 0, 1) === '8')) {
        // Номер Казахстана без ведущей 7/8: 7XXXXXXXXX
        return '+7 (' . substr($cleanPhone, 0, 3) . ') ' . substr($cleanPhone, 3, 3) . '-' . substr($cleanPhone, 6, 2) . '-' . substr($cleanPhone, 8, 2);
    } elseif (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '77') {
        // Номер Казахстана в международном формате: 77XXXXXXXXX
        return '+7 (' . substr($cleanPhone, 2, 3) . ') ' . substr($cleanPhone, 5, 3) . '-' . substr($cleanPhone, 8, 2) . '-' . substr($cleanPhone, 10, 2);
    } elseif (strlen($cleanPhone) === 10) {
        // Номера других стран (10 цифр без кода страны)
        return '+7 (' . substr($cleanPhone, 0, 3) . ') ' . substr($cleanPhone, 3, 3) . '-' . substr($cleanPhone, 6, 2) . '-' . substr($cleanPhone, 8, 2);
    } else {
        // Для всех остальных случаев возвращаем как есть с плюсом
        return '+' . $cleanPhone;
    }
}

// ---------- Фотки и видео ----------
$photos = [];
$rs = $db->query("SELECT * FROM event_byfly_photo WHERE event_id=$event_id ORDER BY id DESC LIMIT 12");
while ($r = $rs->fetch_assoc())
    $photos[] = $r;

$videos = [];
$rs = $db->query("SELECT * FROM event_byfly_videos WHERE event_id=$event_id ORDER BY id DESC LIMIT 6");
while ($v = $rs->fetch_assoc())
    $videos[] = $v;

// YouTube видео для слайдера
$youtube_videos = [
    'https://www.youtube.com/watch?v=5aXmjfyH62k',
    'https://www.youtube.com/watch?v=YUbopBvbRIA',
    'https://www.youtube.com/watch?v=dbuVlO0yKKM',
    'https://www.youtube.com/watch?v=Q6VrmgMV3ic',
    'https://www.youtube.com/watch?v=rqPGSP0-qz4',
    'https://www.youtube.com/watch?v=q53ROdihtjo'
];

// ---------- Программа (распаковка на строки-массивы) ----------
$program = [];
if ($event['programes']) {
    foreach (explode("\n", $event['programes']) as $l) {
        if (!$l)
            continue;
        $p = explode("-", $l, 2);
        $program[] = ['time' => trim($p[0]), 'what' => isset($p[1]) ? trim($p[1]) : ''];
    }
}

// ---------- Спикеры / оргкомитет ----------
$speakers = [];
$rs = $db->query("SELECT u.*,w.role FROM users u, event_byfly_users_work w WHERE u.id=w.user_id AND w.event_id=$event_id");
while ($sp = $rs->fetch_assoc()) {
    $role = $sp['role'];
    if ($sp['phone'] == '77780021666') {
        $speakers[] = [
            'avatar' => ($sp['avatar'] ?: 'https://via.placeholder.com/150x150?text=Avatar'),
            'name' => $sp['name'] . ' ' . $sp['famale'],
            'desc' => $role,
            'info' => $role,
            'phone' => '77777080808'
        ];
    } else {
        $speakers[] = [
            'avatar' => ($sp['avatar'] ?: 'https://via.placeholder.com/150x150?text=Avatar'),
            'name' => $sp['name'] . ' ' . $sp['famale'],
            'desc' => $role,
            'info' => $role,
            'phone' => $sp['phone']
        ];
    }

}


$contacts = [];
if (trim($event['contakctes'])) {
    foreach (explode("\n", $event['contakctes']) as $c) {
        if ($c)
            $contacts[] = trim($c);
    }
}

// SEO мета-теги
$seo_title = $event['name_events'] . " | ByFly Travel - Путешествуй по-бонусному!";
$seo_description = strip_tags($event['description']);
if (strlen($seo_description) > 160) {
    $seo_description = substr($seo_description, 0, 160) . "...";
}
$seo_image = count($photos) > 0 ? $photos[0]['link'] : 'https://byfly.kz/assets/og-image.jpg';
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($seo_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($seo_description) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($seo_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seo_description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seo_image) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="https://byfly.kz/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://byfly.kz/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://byfly.kz/favicon/favicon-16x16.png">
    <link rel="manifest" href="https://byfly.kz/favicon/site.webmanifest">

    <!-- Fonts & Icons -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://byfly-travel.com/style.css?fksdnk=42323454">
</head>

<body> <!-- Preloader -->
    <div class="preloader"> <svg class="plane-loader" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
            <path fill="var(--primary-color)"
                d="M480 192H365.71L260.61 8.06A16.014 16.014 0 0 0 246.71 0h-65.5c-10.63 0-18.3 10.17-15.38 20.39L214.86 192H112l-43.2-57.6c-3.02-4.03-7.77-6.4-12.8-6.4H16.01C5.6 128-2.04 137.78.49 147.88L32 256 .49 364.12C-2.04 374.22 5.6 384 16.01 384H56c5.04 0 9.78-2.37 12.8-6.4L112 320h102.86l-49.03 171.6c-2.92 10.22 4.75 20.4 15.38 20.4h65.5c5.74 0 11.04-3.08 13.89-8.06L365.71 320H480c17.67 0 32-14.33 32-32v-64c0-17.67-14.33-32-32-32z" />
        </svg> </div> <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container"> <a class="navbar-brand" href="/"> <img src="https://byfly.kz/assets/logo-610c625f.svg"
                    alt="ByFly Travel"> </a> <span class="nav-title d-none d-lg-inline"></span>
            <div class="ms-auto d-flex align-items-center">
                <!-- Language Switcher -->
                <div id="yt-widget"></div>
                <script
                    src="https://translate.yandex.net/website-widget/v1/widget.js?widgetId=yt-widget&widgetLang=ru&widgetTheme=light"
                    async></script>
            </div>
        </div>
    </nav> <!-- Hero Section -->
    <section class="hero-section" style="padding-top:100px;">
        <div class="hero-bg"></div>
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-5 mb-lg-0" data-aos="fade-right">
                    <h1 class="hero-title"><?= htmlspecialchars($event['name_events']) ?></h1>
                    <div class="hero-subtitle"><?= nl2br(htmlspecialchars($event['description'])) ?></div>
                    <div class="hero-meta">
                        <div class="hero-meta-item">
                            <i class="fas fa-calendar-day"></i>
                            <?= date('d.m.Y', strtotime($event['date_event'])) ?>
                        </div>
                        <div class="hero-meta-item">
                            <i class="fas fa-clock"></i>
                            <?= date('H:i', strtotime($event['date_event'])) ?>
                        </div>
                        <div class="hero-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($event['adress']) ?>
                        </div>
                        <?php if ($event['price_event'] > 0): ?>
                            <div class="hero-meta-item">
                                <i class="fas fa-tag"></i>
                                <?= number_format($event['price_event'], 0, ',', ' ') ?> ₸
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="#register" class="btn btn-primary btn-lg mt-3 animate-pulse">
                        <i class="fas fa-ticket-alt me-2"></i> Зарегистрироваться
                    </a>
                </div>

                <div class="col-lg-5" data-aos="fade-left">
                    <div class="timer-container">
                        <div class="timer-title" style="margin: auto; text-align: center;">До начала мероприятия
                            осталось:</div>
                        <div class="d-flex justify-content-center">
                            <div class="timer-element">
                                <div class="timer-value" id="cd_days">00</div>
                                <div class="timer-label">дней</div>
                            </div>
                            <div class="timer-element">
                                <div class="timer-value" id="cd_hours">00</div>
                                <div class="timer-label">часов</div>
                            </div>
                            <div class="timer-element">
                                <div class="timer-value" id="cd_mins">00</div>
                                <div class="timer-label">минут</div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <div class="badge bg-warning text-dark">
                                <i class="fas fa-users me-1"></i>
                                Осталось <?= $available_seats ?> из <?= $event['max_people'] ?> мест
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">Почему стоит участвовать?</h2>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h3 class="feature-title">Розыгрыш призов</h3>
                        <p class="feature-text">Каждый участник автоматически участвует в лотерее ценных подарков от
                            ByFly Travel!</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Нетворкинг</h3>
                        <p class="feature-text">Знакомства с единомышленниками, travel-энтузиастами и экспертами
                            индустрии.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h3 class="feature-title">Сертификат</h3>
                        <p class="feature-text">Официальный сертификат участника от ByFly Travel для вашего портфолио.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-plane"></i>
                        </div>
                        <h3 class="feature-title">Экспертные знания</h3>
                        <p class="feature-text">Уникальные знания от профессионалов travel-индустрии и возможность
                            задать вопросы.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="prizes-section">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title text-white">Призы и подарки</h2>
                <div class="title-divider mx-auto"></div>
            </div>

            <?php if (!empty($event['prizez'])): ?>
                <div class="prizes-card">
                    <div class="prizes-content">
                        <?= nl2br(htmlspecialchars($event['prizez'])) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-prizes text-center py-4">
                    <i class="fas fa-gift mb-3"></i>
                    <p>Список призов будет объявлен позже</p>
                </div>
            <?php endif; ?>
        </div>
    </section>


    <!-- Блок генерации ссылки -->
    <section class="generate-link-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title text-center mb-4" data-aos="fade-up">
                                <i class="fas fa-link me-2"></i> Пригласить друзей
                            </h3>
                            <div class="text-center mb-4" data-aos="fade-up" data-aos-delay="100">
                                <p>Сгенерируйте персональную ссылку для приглашения друзей и получайте бонусы за их
                                    регистрацию!</p>
                            </div>

                            <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                                <button class="btn btn-gradient px-4 py-2" id="generateLinkBtn">
                                    <i class="fas fa-share-alt me-2"></i> Сгенерировать ссылку
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Модальное окно для генерации ссылки -->
    <div class="modal fade" id="linkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Ваша реферальная ссылка</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="referralLink" readonly>
                        <button class="btn btn-outline-secondary" id="copyLinkBtn">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>

                    <div class="share-buttons text-center mt-4">
                        <p class="text-muted mb-3">Поделиться через:</p>
                        <a href="#" class="btn btn-outline-primary me-2 whatsapp-share">
                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                        </a>
                        <a href="#" class="btn btn-outline-info me-2 telegram-share">
                            <i class="fab fa-telegram me-1"></i> Telegram
                        </a>
                        <a href="#" class="btn btn-outline-secondary copy-again">
                            <i class="far fa-copy me-1"></i> Копировать
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <small class="text-muted">
                        За каждого приглашенного друга вы получите бонусы на свой счет!
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для ввода телефона -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Введите ваш телефон</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Чтобы сгенерировать реферальную ссылку, введите ваш номер телефона, который зарегистрирован в
                        системе:</p>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Страна</label>
                            <select class="form-select" id="countrySelect">
                                <option value="KZ" data-prefix="7" selected>Казахстан (+7)</option>
                                <option value="RU" data-prefix="7">Россия (+7)</option>
                                <option value="UZ" data-prefix="998">Узбекистан (+998)</option>
                                <option value="AZ" data-prefix="994">Азербайджан (+994)</option>
                                <option value="BY" data-prefix="375">Беларусь (+375)</option>
                                <option value="KG" data-prefix="996">Кыргызстан (+996)</option>
                                <option value="GE" data-prefix="995">Грузия (+995)</option>
                                <option value="AM" data-prefix="374">Армения (+374)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Телефон</label>
                            <div class="input-group">
                                <span class="input-group-text" id="phonePrefix">+7</span>
                                <input type="tel" class="form-control" id="agentPhone"
                                    placeholder="Введите номер телефона">
                            </div>
                        </div>
                    </div>

                    <div id="phoneCheckResult"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="checkPhoneBtn">Проверить</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Стили для блока генерации ссылки */
        .generate-link-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .btn-gradient {
            background: linear-gradient(45deg, #ff4d4d, #f62459);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(246, 36, 89, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(246, 36, 89, 0.4);
            color: white;
        }

        #referralLink {
            font-size: 0.9rem;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .share-buttons .btn {
            border-radius: 50px;
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .whatsapp-share {
            color: #25D366;
            border-color: #25D366;
        }

        .whatsapp-share:hover {
            background-color: #25D366;
            color: white;
        }

        .telegram-share {
            color: #0088cc;
            border-color: #0088cc;
        }

        .telegram-share:hover {
            background-color: #0088cc;
            color: white;
        }

        .copy-again {
            color: #6c757d;
            border-color: #6c757d;
        }

        .copy-again:hover {
            background-color: #6c757d;
            color: white;
        }

        /* Прелоадер */
        .loader-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
        }

        .loader-spinner {
            width: 3rem;
            height: 3rem;
            border: 0.25em solid rgba(13, 110, 253, 0.2);
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loader-text {
            margin-top: 1rem;
            color: #0d6efd;
            font-weight: 500;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Анимация копирования */
        @keyframes pulse {
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

        .copied {
            animation: pulse 0.5s;
        }
    </style>



    <section class="program-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="section-title text-center" data-aos="fade-up">Программа мероприятия</h2>
                    <?php if (!empty($program)): ?>
                        <div data-aos="fade-up" data-aos-delay="100">
                            <?php foreach ($program as $item): ?>
                                <div class="program-item">
                                    <div class="program-time"><?= $item['time'] ?></div>
                                    <div class="program-desc"><?= $item['what'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4" data-aos="fade-up">
                            <div class="alert alert-info d-inline-block">
                                <i class="fas fa-info-circle me-2"></i> Программа будет опубликована ближе к дате
                                мероприятия
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section> <!-- Gallery Section -->
    <section class="gallery-section">
        <div class="container">
            <h2 class="section-title text-center" data-aos="fade-up">Фото с наших мероприятий</h2>
            <?php if (!empty($photos)): ?>
                <div class="row">
                    <?php foreach ($photos as $index => $photo): ?>
                        <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 100 ?>">
                            <div class="gallery-item">
                                <img src="<?= $photo['link'] ?>" alt="Фото с мероприятия ByFly" class="img-fluid">
                                <div class="gallery-overlay">
                                    <div class="gallery-caption">Мероприятие ByFly Travel</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4" data-aos="fade-up">
                    <div class="alert alert-info d-inline-block">
                        <i class="fas fa-info-circle me-2"></i> Фотографии появятся после мероприятия
                    </div>
                </div>
            <?php endif; ?>

            <!-- Video Slider -->
            <h2 class="section-title text-center mt-5" data-aos="fade-up">Видео с мероприятий</h2>

            <?php if (!empty($videos) || !empty($youtube_videos)): ?>
                <div class="video-slider" data-aos="fade-up">
                    <div class="row">
                        <?php
                        $all_videos = !empty($videos) ? $videos : array_map(function ($url) {
                            return ['link' => $url];
                        }, $youtube_videos);
                        ?>

                        <?php foreach ($all_videos as $index => $video): ?>
                            <div class="col-md-6 col-lg-4 mb-4 video-slide">
                                <div class="video-iframe">
                                    <?php if (strpos($video['link'], 'youtube.com') !== false || strpos($video['link'], 'youtu.be') !== false): ?>
                                        <?php
                                        // Обработка YouTube ссылок
                                        $video_id = '';
                                        if (strpos($video['link'], 'youtube.com') !== false) {
                                            parse_str(parse_url($video['link'], PHP_URL_QUERY), $params);
                                            $video_id = $params['v'] ?? '';
                                        } elseif (strpos($video['link'], 'youtu.be') !== false) {
                                            $video_id = substr(parse_url($video['link'], PHP_URL_PATH), 1);
                                        }
                                        ?>
                                        <iframe width="100%" height="315"
                                            src="https://www.youtube.com/embed/<?= $video_id ?>?rel=0&enablejsapi=1" frameborder="0"
                                            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>

                                    <?php elseif (strpos($video['link'], 'vimeo.com') !== false): ?>
                                        <?php
                                        // Обработка Vimeo ссылок
                                        $video_id = substr(parse_url($video['link'], PHP_URL_PATH), 1);
                                        ?>
                                        <iframe src="https://player.vimeo.com/video/<?= $video_id ?>?color=ffffff&title=0&byline=0"
                                            width="100%" height="315" frameborder="0" allow="autoplay; fullscreen"
                                            allowfullscreen></iframe>

                                    <?php else: ?>
                                        <!-- Для других видео (MP4, WebM и т.д.) -->
                                        <video width="100%" height="315" controls>
                                            <source src="<?= $video['link'] ?>" type="video/mp4">
                                            Ваш браузер не поддерживает видео тег.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center py-4" data-aos="fade-up">
                    <div class="alert alert-info d-inline-block">
                        <i class="fas fa-info-circle me-2"></i> Видео появятся после мероприятия
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section> <!-- Speakers Section -->
    <section class="speakers-section">
        <div class="container">
            <h2 class="section-title text-center" data-aos="fade-up">Наши спикеры</h2> <?php if (!empty($speakers)): ?>
                <div class="row">
                    <?php foreach ($speakers as $index => $speaker): ?>
                        <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 100 ?>">
                            <div class="speaker-card">
                                <img src="<?= $speaker['avatar'] ?>" alt="<?= htmlspecialchars($speaker['name']) ?>"
                                    class="speaker-img">
                                <div class="speaker-body">
                                    <h3 class="speaker-name"><?= htmlspecialchars($speaker['name']) ?></h3>
                                    <span class="speaker-role"><?= $speaker['desc'] ?></span>
                                    <p class="speaker-bio"><?= $speaker['info'] ?></p>
                                    <?php if (!empty($speaker['phone'])): ?>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $speaker['phone']) ?>"
                                            class="speaker-contact">
                                            <i class="fab fa-whatsapp"></i> Написать в WhatsApp
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4" data-aos="fade-up">
                    <div class="alert alert-info d-inline-block">
                        <i class="fas fa-info-circle me-2"></i> Список спикеров будет объявлен позже
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </section> <!-- Registration Section -->
    <section class="registration-section" id="register">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="registration-card" data-aos="zoom-in">
                        <?php if ($register_success): ?>
                            <div class="text-center py-4">
                                <div class="alert alert-success">
                                    <h4 class="alert-heading">Регистрация успешна!</h4>
                                    <p>Спасибо за регистрацию на мероприятие. В ближайшее время вам придет билет и подробная
                                        информация на WhatsApp.</p>
                                    <hr>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <h2 class="registration-title text-center">Регистрация</h2>
                        <?php if ($reg_error): ?>
                            <div class="alert alert-danger"><?= $reg_error ?></div>
                        <?php endif; ?>

                        <!-- Seats Counter -->
                        <div class="seats-counter">
                            <div class="seats-text">Свободных мест:</div>
                            <div class="seats-progress">
                                <div class="seats-progress-bar"
                                    style="width: <?= ($available_seats / $event['max_people']) * 100 ?>%"></div>
                            </div>
                            <div class="seats-number"><?= $available_seats ?> / <?= $event['max_people'] ?></div>
                        </div>

                        <?php if ($event['price_event'] > 0): ?>
                            <div class="price-badge mb-4">
                                Стоимость участия: <?= number_format($event['price_event'], 0, ',', ' ') ?> ₸
                            </div>
                        <?php endif; ?>

                        <form id="regForm" method="POST" autocomplete="off">
                            <input type="hidden" name="reg_refer_whats" value="<?= htmlspecialchars($event_source) ?>">

                            <!-- Inviter Info -->
                            <div id="inviterHolder">
                                <?php if ($__inviter): ?>
                                    <div class="inviter-badge mb-4">
                                        <div style="color: black;" class="inviter-text">
                                            Вы участвуете по приглашению: <span
                                                class="inviter-name"><?= htmlspecialchars($__inviter['name']) ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Name Field -->
                            <div class="mb-3">
                                <label for="reg_fio" class="form-label">ФИО *</label>
                                <input type="text" class="form-control" id="reg_fio" name="reg_fio" required
                                    placeholder="Иванов Иван Иванович">
                            </div>

                            <!-- Country and Phone -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Страна *</label>
                                    <div class="country-select">
                                        <img src="https://flagcdn.com/w20/kz.png" class="country-flag" id="countryFlag">
                                        <select class="form-select" name="reg_country" id="countrySelect" required>
                                            <option value="KZ" data-flag="kz" data-prefix="7">Казахстан (+7)</option>
                                            <option value="RU" data-flag="ru" data-prefix="7">Россия (+7)</option>
                                            <option value="UZ" data-flag="uz" data-prefix="998">Узбекистан (+998)
                                            </option>
                                            <option value="AZ" data-flag="az" data-prefix="994">Азербайджан (+994)
                                            </option>
                                            <option value="BY" data-flag="by" data-prefix="375">Беларусь (+375)</option>
                                            <option value="KG" data-flag="kg" data-prefix="996">Кыргызстан (+996)
                                            </option>
                                            <option value="GE" data-flag="ge" data-prefix="995">Грузия (+995)</option>
                                            <option value="AM" data-flag="am" data-prefix="374">Армения (+374)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Телефон *</label>
                                    <div class="phone-input-container">
                                        <span class="phone-prefix" id="phonePrefix">+7</span>
                                        <input type="tel" class="phone-input-field form-control" id="reg_phone"
                                            name="reg_phone" required placeholder="(777) 123-45-67">
                                    </div>
                                    <div class="check-status" id="phoneCheckStatus"></div>
                                </div>
                            </div>

                            <!-- Inviter Fields -->
                            <div class="mb-3">
                                <label class="form-label">Промокод пригласителя (если есть)</label>
                                <input type="text" class="form-control" name="reg_inviter_code" id="reg_inviter_code"
                                    placeholder="Введите промокод">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Или телефон пригласителя</label>
                                <div class="phone-input-container">
                                    <span class="phone-prefix" id="inviterPhonePrefix">+7</span>
                                    <input type="tel" class="phone-input-field form-control" id="reg_inviter_phone"
                                        name="reg_inviter_phone" placeholder="(777) 123-45-67">
                                </div>
                                <div class="check-status" id="inviterCheckStatus"></div>
                            </div>

                            <div id="inviter_info" class="inviter-info-container"></div>

                            <!-- Checkbox -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="agreeCheck" required checked>
                                <label class="form-check-label" for="agreeCheck" style="color: black;">
                                    Я согласен(а) на обработку персональных данных и получение информационных сообщений
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-paper-plane me-2"></i> Зарегистрироваться
                            </button>

                            <div class="text-center mt-3 small text-muted">
                                После регистрации вам придет билет на WhatsApp
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section class="contacts-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="section-title text-center" data-aos="fade-up">Контакты организаторов</h2>
                    <div class="card shadow-sm p-4" data-aos="fade-up" data-aos-delay="100">
                        <?php if (!empty($contacts)): ?>
                            <?php foreach ($contacts as $contact): ?>
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="contact-text">
                                        <?= nl2br(htmlspecialchars($contact)) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section> <!-- Footer -->
    <footer class="footer bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <!-- Лого и описание -->
                <div class="col-lg-5 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel" class="footer-logo"
                            style="height: 40px;">
                    </div>
                    <p class="text-white-50 mb-4">Путешествуйте с комфортом, оплачивайте бонусами. Официальные офисы в 4
                        городах Казахстана.</p>

                    <!-- Соцсети -->
                    <div class="social-links">
                        <a href="https://www.instagram.com/@byfly.kz/" class="text-white me-3" target="_blank">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="https://t.me/byfly_info" class="text-white me-3" target="_blank">
                            <i class="fab fa-telegram fa-lg"></i>
                        </a>
                        <a href="https://chat.whatsapp.com/LBtf51oL88aA3TWRsMOklW" class="text-white me-3"
                            target="_blank">
                            <i class="fab fa-whatsapp fa-lg"></i>
                        </a>
                    </div>
                </div>

                <!-- Адреса -->
                <div class="col-lg-7">
                    <div class="row">
                        <!-- Алматы -->
                        <div class="col-md-3 col-6 mb-4">
                            <div class="address-card">
                                <div class="city-badge bg-danger text-white mb-2 py-1 px-2 rounded"
                                    style="font-size: 0.8rem; display: inline-block;">Алматы</div>
                                <p class="mb-0 small text-white">Айтеке би 100</p>
                                <a href="tel:+77786699999" style="text-decoration: none;" class="text-white me-4 mb-2">
                                    +7 778 669 99 99
                                </a>
                            </div>
                        </div>

                        <!-- Астана -->
                        <div class="col-md-3 col-6 mb-4">
                            <div class="address-card">
                                <div class="city-badge bg-primary text-white mb-2 py-1 px-2 rounded"
                                    style="font-size: 0.8rem; display: inline-block;">Астана</div>
                                <p class="mb-0 small text-white">Туран 50/2</p>
                                <a href="tel:+77021122545" style="text-decoration: none;" class="text-white me-4 mb-2">
                                    +7 702 112 25 45
                                </a>
                            </div>
                        </div>

                        <!-- Шымкент -->
                        <div class="col-md-3 col-6 mb-4">
                            <div class="address-card">
                                <div class="city-badge bg-success text-white mb-2 py-1 px-2 rounded"
                                    style="font-size: 0.8rem; display: inline-block;">Шымкент</div>
                                <p class="mb-0 small text-white">Туркестанская 3</p>
                                <a href="tel:+77718671100" style="text-decoration: none;" class="text-white me-4 mb-2">
                                    +7 771 867 11 00
                                </a>
                            </div>
                        </div>

                        <!-- Уральск -->
                        <div class="col-md-3 col-6 mb-4">
                            <div class="address-card">
                                <div class="city-badge bg-warning text-dark mb-2 py-1 px-2 rounded"
                                    style="font-size: 0.8rem; display: inline-block;">Уральск</div>
                                <p class="mb-0 small text-white">Жунисова 114</p>
                                <a href="tel:+77052019563" style="text-decoration: none;" class="text-white me-4 mb-2">
                                    +7 705 201 95 63
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Контактная информация -->
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <div class="d-flex flex-wrap">
                            <a href="tel:+77085194866" style="text-decoration: none;" class="text-white me-4 mb-2">
                                <i class="fas fa-phone-alt me-2"></i> +7 708 519 48 66
                            </a>
                            <a href="mailto:info@byfly.kz" style="text-decoration: none;" class="text-white mb-2">
                                <i class="fas fa-envelope me-2"></i> info@byfly.kz
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Копирайт -->
            <div class="text-center mt-5 pt-3 border-top border-secondary">
                <p class="small text-white-50 mb-0">&copy; <?= date('Y') ?> ByFly Travel. Все права защищены.</p>
            </div>
        </div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Инициализация анимаций
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });


            const eventId = <?= $event_id ?>;
            const baseUrl = 'https://byfly-travel.com/register.php?event_id=' + eventId;
            let phoneMask;

            // Проверяем, является ли пользователь агентом
            let isAgent = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
            let userId = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>;

            // Если пользователь не авторизован, показываем поле для ввода телефона
            if (!isAgent) {
                $('#generateLinkBtn').attr('data-bs-toggle', 'modal');
                $('#generateLinkBtn').attr('data-bs-target', '#authModal');
            }

            // Инициализация масок телефона
            function initPhoneMask(countryCode) {
                const maskOptions = {
                    mask: '',
                    lazy: false,
                    placeholderChar: '_'
                };

                // Определяем маску в зависимости от страны
                switch (countryCode) {
                    case 'KZ':
                    case 'RU':
                        maskOptions.mask = '(000) 000-00-00';
                        break;
                    case 'UZ':
                        maskOptions.mask = '00 000 00 00';
                        break;
                    case 'AZ':
                        maskOptions.mask = '00 000 00 00';
                        break;
                    case 'BY':
                        maskOptions.mask = '(00) 000-00-00';
                        break;
                    case 'KG':
                        maskOptions.mask = '000 000 000';
                        break;
                    case 'GE':
                        maskOptions.mask = '000 000 000';
                        break;
                    case 'AM':
                        maskOptions.mask = '00 000 000';
                        break;
                    default:
                        maskOptions.mask = '(000) 000-00-00';
                }

                // Если маска уже существует - обновляем, иначе создаем новую
                if (phoneMask) {
                    phoneMask.destroy();
                }

                phoneMask = new IMask(document.getElementById('agentPhone'), maskOptions);
            }

            // Инициализируем маску при загрузке
            initPhoneMask('KZ');

            // Обработчик изменения страны
            $('#countrySelect').change(function () {
                const selected = $(this).find('option:selected');
                const countryCode = selected.val();
                const prefix = selected.data('prefix');

                $('#phonePrefix').text('+' + prefix);
                initPhoneMask(countryCode);
                $('#agentPhone').val('').trigger('input');
            });

            // Обработчик генерации ссылки
            $('#generateLinkBtn').click(function () {
                if (isAgent && userId) {
                    const referralLink = baseUrl + '&agent=' + userId;
                    showLinkModal(referralLink);
                }
            });

            // Функция показа модального окна с ссылкой
            function showLinkModal(link) {
                $('#referralLink').val(link);

                // Обновляем ссылки для шаринга
                $('.whatsapp-share').attr('href', 'https://wa.me/?text=' + encodeURIComponent('Присоединяйся к мероприятию ByFly Travel! ' + link));
                $('.telegram-share').attr('href', 'https://t.me/share/url?url=' + encodeURIComponent(link) + '&text=' + encodeURIComponent('Присоединяйся к мероприятию ByFly Travel!'));

                $('#linkModal').modal('show');
            }

            // Копирование ссылки
            $('#copyLinkBtn, .copy-again').click(function () {
                const linkInput = $('#referralLink');
                linkInput.select();
                document.execCommand('copy');

                // Показать уведомление
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-check me-1"></i> Скопировано!');
                $(this).addClass('copied');

                setTimeout(() => {
                    $(this).html(originalText);
                    $(this).removeClass('copied');
                }, 2000);
            });

            // Проверка телефона
            $('#checkPhoneBtn').click(async function () {
                const countryCode = $('#countrySelect').val();
                const prefix = $('#countrySelect option:selected').data('prefix');
                const phoneDigits = phoneMask.unmaskedValue;
                const fullPhone = prefix + phoneDigits;

                if (!phoneDigits || phoneDigits.length < 5) {
                    $('#phoneCheckResult').html('<div class="alert alert-danger mt-3">Введите корректный номер телефона</div>');
                    return;
                }

                // Показываем прелоадер
                $('#phoneCheckResult').html(`
                <div class="loader-container">
                    <div class="loader-spinner"></div>
                    <div class="loader-text">Проверяем телефон...</div>
                </div>
                `);

                $('#checkPhoneBtn').prop('disabled', true);

                const cleanPhone = fullPhone.replace(/\D/g, '');


                try {
                    // Реальный AJAX запрос к вашему API
                    const response = await $.ajax({
                        url: '/api/check_agent_phone.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            phone: fullPhone,
                            country: countryCode
                        }
                    });

                    if (response.success && response.user_id) {
                        const referralLink = baseUrl + '&agent=' + response.user_id;
                        $('#authModal').modal('hide');
                        showLinkModal(referralLink);
                    } else {
                        $('#phoneCheckResult').html('<div class="alert alert-danger mt-3">Телефон не найден в системе агентов. Обратитесь в поддержку.</div>');
                    }
                } catch (error) {
                    console.error('Ошибка при проверке телефона:', error);
                    $('#phoneCheckResult').html('<div class="alert alert-danger mt-3">Ошибка сервера. Пожалуйста, попробуйте позже.</div>');
                } finally {
                    $('#checkPhoneBtn').prop('disabled', false);
                }
            });



            // Обратный отсчет
            function updateCountdown() {
                const eventDate = new Date("<?= $event['date_event'] ?>").getTime();
                const now = new Date().getTime();
                const distance = eventDate - now;

                if (distance > 0) {
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

                    document.getElementById("cd_days").innerHTML = days.toString().padStart(2, '0');
                    document.getElementById("cd_hours").innerHTML = hours.toString().padStart(2, '0');
                    document.getElementById("cd_mins").innerHTML = minutes.toString().padStart(2, '0');
                } else {
                    document.getElementById("cd_days").innerHTML = "00";
                    document.getElementById("cd_hours").innerHTML = "00";
                    document.getElementById("cd_mins").innerHTML = "00";
                }
            }

            updateCountdown();
            setInterval(updateCountdown, 60000);

            // Preloader
            window.addEventListener('load', function () {
                setTimeout(function () {
                    const preloader = document.querySelector('.preloader');
                    if (preloader) {
                        preloader.classList.add('fade-out');
                        setTimeout(() => {
                            preloader.style.display = 'none';
                        }, 500);
                    }
                }, 1000);
            });

            // Navbar scroll effect
            window.addEventListener('scroll', function () {
                const navbar = document.querySelector('.navbar');
                if (navbar) {
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                }
            });

            // Phone Input Functionality
            const initPhoneFields = () => {
                const countrySelect = document.getElementById('countrySelect');
                const phonePrefix = document.getElementById('phonePrefix');
                const inviterPhonePrefix = document.getElementById('inviterPhonePrefix');
                const phoneCheckStatus = document.getElementById('phoneCheckStatus');
                const inviterCheckStatus = document.getElementById('inviterCheckStatus');
                const inviterInfo = document.getElementById('inviter_info');
                const regFio = document.getElementById('reg_fio');
                const regInviterCode = document.getElementById('reg_inviter_code');

                // Функция для генерации аватара по умолчанию
                function generateDefaultAvatar(name) {
                    const firstLetter = name ? name.charAt(0).toUpperCase() : '?';
                    const colors = ['#FF5733', '#33FF57', '#3357FF', '#F333FF', '#33FFF5'];
                    const color = colors[Math.floor(Math.random() * colors.length)];

                    return `
                    <div class="default-avatar" style="background-color: ${color}">
                        ${firstLetter}
                    </div>
                `;
                }

                // Phone mask options с автоматической проверкой при полном вводе
                const getMaskOptions = (prefix, countryCode) => {
                    // Определяем длину номера в зависимости от страны
                    let totalDigits;
                    switch (countryCode) {
                        case 'KZ':
                        case 'RU':
                            totalDigits = 11; // 7 + 10
                            break;
                        case 'UZ':
                            totalDigits = 12; // 998 + 9
                            break;
                        case 'AZ':
                        case 'BY':
                        case 'KG':
                        case 'GE':
                            totalDigits = 12; // код + 9
                            break;
                        case 'AM':
                            totalDigits = 11; // 374 + 8
                            break;
                        default:
                            totalDigits = 11; // по умолчанию
                    }

                    return {
                        mask: `+{${prefix}}0000000000`.slice(0, 3 + prefix.length + totalDigits),
                        lazy: false,
                        placeholderChar: '_',
                        blocks: {
                            '0': { mask: IMask.MaskedRange, from: 0, to: 9 }
                        },
                        onComplete: function () {
                            const phone = this.unmaskedValue;
                            if (phone.length === totalDigits) {
                                checkPhone(phone, countryCode);
                            }
                        }
                    };
                };

                // Initialize masks (изначально для Казахстана)
                let phoneMask = IMask(document.querySelector('input[name="reg_phone"]'), getMaskOptions('7', 'KZ'));
                let inviterPhoneMask = IMask(document.querySelector('input[name="reg_inviter_phone"]'), getMaskOptions('7', 'KZ'));

                // Country change handler
                countrySelect?.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    const prefix = selected.getAttribute('data-prefix');
                    const flag = selected.getAttribute('data-flag');
                    const countryCode = selected.value;

                    // Update UI
                    document.getElementById('countryFlag').src = `https://flagcdn.com/w20/${flag}.png`;
                    phonePrefix.textContent = `+${prefix}`;
                    inviterPhonePrefix.textContent = `+${prefix}`;

                    // Update masks с учетом страны
                    phoneMask.updateOptions(getMaskOptions(prefix, countryCode));
                    inviterPhoneMask.updateOptions(getMaskOptions(prefix, countryCode));

                    // Clear values
                    phoneMask.value = '';
                    inviterPhoneMask.value = '';
                });

                // Phone validation
                const validatePhone = (phone, countryCode) => {
                    const phoneRegex = {
                        'KZ': /^7[0-9]{10}$/,      // Россия/Казахстан: 11 цифр (7 + 10)
                        'RU': /^7[0-9]{10}$/,      // Россия/Казахстан: 11 цифр (7 + 10)
                        'UZ': /^998[0-9]{9}$/,     // Узбекистан: 12 цифр (998 + 9)
                        'AZ': /^994[0-9]{9}$/,     // Азербайджан: 12 цифр (994 + 9)
                        'BY': /^375[0-9]{9}$/,     // Беларусь: 12 цифр (375 + 9)
                        'KG': /^996[0-9]{9}$/,     // Кыргызстан: 12 цифр (996 + 9)
                        'GE': /^995[0-9]{9}$/,     // Грузия: 12 цифр (995 + 9)
                        'AM': /^374[0-9]{8}$/      // Армения: 11 цифр (374 + 8)
                    };
                    return phoneRegex[countryCode]?.test(phone) || false;
                };

                // Функция для поиска пользователя с улучшенным прелоадером
                async function searchUser(value, type) {
                    try {
                        // Показываем красивый прелоадер
                        const loader = `
                        <div class="loader-container">
                            <div class="loader-spinner"></div>
                            <div class="loader-text">Ищем пользователя...</div>
                        </div>
                    `;

                        if (type === 'phone') {
                            phoneCheckStatus.innerHTML = loader;
                        } else {
                            inviterCheckStatus.innerHTML = loader;
                        }

                        const response = await fetch('/api/search_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type, value })
                        });
                        return await response.json();
                    } catch (error) {
                        console.error('Ошибка поиска пользователя:', error);
                        return { success: false, message: 'Ошибка соединения' };
                    }
                }

                // Check phone handler с автоматической подстановкой ФИО
                const checkPhone = async (phone, countryCode) => {
                    if (!validatePhone(phone, countryCode)) {
                        phoneCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Неверный формат телефона</div>';
                        return;
                    }

                    try {
                        const data = await searchUser(phone, 'phone');

                        if (data.success && data.user) {
                            // Подставляем ФИО в поле (если оно есть)
                            if (regFio) {
                                const fullName = [
                                    data.user.famale || '',
                                    data.user.name || '',
                                    data.user.surname || ''
                                ].filter(Boolean).join(' ');
                                regFio.value = fullName;
                            }

                            // Показываем информацию о пользователе
                            const avatar = data.user.avatar
                                ? `<img src="${data.user.avatar}" class="user-avatar">`
                                : generateDefaultAvatar(data.user.name);

                            phoneCheckStatus.innerHTML = `
                            <div class="user-info success-message">
                                ${avatar}
                                <div class="user-details">
                                    <div class="user-name">${data.user.famale || ''} ${data.user.name || ''} ${data.user.surname || ''}</div>
                                    <div class="user-phone">${data.user.phone}</div>
                                </div>
                            </div>
                        `;
                        } else {
                            phoneCheckStatus.innerHTML = '<div class="info-message"><i class="fas fa-info-circle"></i> Пользователь не найден</div>';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        phoneCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Ошибка проверки</div>';
                    }
                };

                // Check inviter handler
                const checkInviter = async (phone, countryCode) => {
                    if (!validatePhone(phone, countryCode)) {
                        inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Неверный формат телефона</div>';
                        return;
                    }

                    try {
                        const data = await searchUser(phone, 'phone');

                        if (data.success && data.user) {
                            inviterCheckStatus.innerHTML = '<div class="success-message"><i class="fas fa-check-circle"></i> Пользователь найден</div>';

                            // Показываем информацию о пригласителе
                            const avatar = data.user.avatar
                                ? `<img src="${data.user.avatar}" class="user-avatar">`
                                : generateDefaultAvatar(data.user.name);

                            inviterInfo.innerHTML = `
                            <div class="inviter-info">
                                ${avatar}
                                <div class="inviter-details">
                                    <div class="inviter-name">${data.user.famale || ''} ${data.user.name || ''} ${data.user.surname || ''}</div>
                                    <div class="inviter-phone">${data.user.phone}</div>
                                    ${data.user.promo_code ? `<div class="inviter-promo">Промокод: ${data.user.promo_code}</div>` : ''}
                                    <div class="inviter-bonus">Вы получите бонусы, если этот пользователь посетит мероприятие</div>
                                </div>
                            </div>
                        `;

                            if (regInviterCode) regInviterCode.value = data.user.promo_code;
                        } else {
                            inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Пользователь не найден</div>';
                            inviterInfo.innerHTML = '';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Ошибка проверки</div>';
                    }
                };

                // Проверка по промокоду
                const checkInviterByCode = async (code) => {
                    if (code.length < 3) return;

                    try {
                        const data = await searchUser(code, 'promo');

                        if (data.success && data.user) {
                            inviterCheckStatus.innerHTML = '<div class="success-message"><i class="fas fa-check-circle"></i> Пользователь найден</div>';

                            // Показываем информацию о пригласителе
                            const avatar = data.user.avatar
                                ? `<img src="${data.user.avatar}" class="user-avatar">`
                                : generateDefaultAvatar(data.user.name);

                            inviterInfo.innerHTML = `
                            <div class="inviter-info">
                                ${avatar}
                                <div class="inviter-details">
                                    <div class="inviter-name">${data.user.famale || ''} ${data.user.name || ''} ${data.user.surname || ''}</div>
                                    <div class="inviter-phone">${data.user.phone}</div>
                                    <div class="inviter-promo">Промокод: ${data.user.promo_code}</div>
                                    <div class="inviter-bonus">Вы получите бонусы, если этот пользователь посетит мероприятие</div>
                                </div>
                            </div>
                        `;
                        } else {
                            inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Промокод не найден</div>';
                            inviterInfo.innerHTML = '';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        inviterCheckStatus.innerHTML = '<div class="error-message"><i class="fas fa-times-circle"></i> Ошибка проверки</div>';
                    }
                };

                // Event listeners for phone fields
                document.querySelector('input[name="reg_phone"]')?.addEventListener('input', function () {
                    const phone = phoneMask.unmaskedValue;
                    const countryCode = countrySelect?.value || 'KZ';
                    const totalDigits = getTotalDigits(countryCode);
                    if (phone.length === totalDigits) checkPhone(phone, countryCode);
                });

                document.querySelector('input[name="reg_inviter_phone"]')?.addEventListener('input', function () {
                    const phone = inviterPhoneMask.unmaskedValue;
                    const countryCode = countrySelect?.value || 'KZ';
                    const totalDigits = getTotalDigits(countryCode);
                    if (phone.length === totalDigits) checkInviter(phone, countryCode);
                });

                // Helper function to get total digits for country
                function getTotalDigits(countryCode) {
                    switch (countryCode) {
                        case 'KZ': case 'RU': return 11;
                        case 'UZ': case 'AZ': case 'BY': case 'KG': case 'GE': return 12;
                        case 'AM': return 11;
                        default: return 11;
                    }
                }

                // Обработчик для промокода
                document.getElementById('reg_inviter_code')?.addEventListener('input', function () {
                    const code = this.value.trim();
                    checkInviterByCode(code);
                });

                // Clear inviter info when typing
                document.querySelector('input[name="reg_inviter_phone"]')?.addEventListener('input', function () {
                    if (this.value.length > 0 && inviterInfo) {
                        inviterInfo.innerHTML = '';
                    }
                });

                document.getElementById('reg_inviter_code')?.addEventListener('input', function () {
                    if (this.value.length > 0 && inviterInfo) {
                        inviterInfo.innerHTML = '';
                    }
                });
            };

            // Initialize phone fields functionality
            initPhoneFields();
        });
    </script>
</body>

<style>
    #yt-widget .yt-servicelink {
        display: none;
    }
</style>

</html>