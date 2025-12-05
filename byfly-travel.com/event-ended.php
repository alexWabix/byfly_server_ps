<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID мероприятия
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем информацию о мероприятии
$event = [];
if ($event_id > 0) {
    $result = $db->query("SELECT * FROM event_byfly WHERE id = $event_id");
    if ($result && $result->num_rows > 0) {
        $event = $result->fetch_assoc();
    }
}

// Если мероприятие не найдено - 404
if (empty($event)) {
    header("Location: 404.php");
    exit();
}

// Получаем фото и видео с мероприятия
$photos = [];
$videos = [];

$photos_result = $db->query("SELECT * FROM event_byfly_photo WHERE event_id = $event_id ORDER BY date_create DESC");
if ($photos_result && $photos_result->num_rows > 0) {
    while ($row = $photos_result->fetch_assoc()) {
        $photos[] = $row;
    }
}

$videos_result = $db->query("SELECT * FROM event_byfly_videos WHERE event_id = $event_id ORDER BY date_create DESC");
if ($videos_result && $videos_result->num_rows > 0) {
    while ($row = $videos_result->fetch_assoc()) {
        $videos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['name_events']) ?> | ByFly Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.1/css/lightgallery.min.css">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .event-header {
            text-align: center;
            padding: 60px 0 40px;
            position: relative;
        }

        .event-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, white, #E1BEE7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .event-date {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 30px;
        }

        .back-button {
            position: absolute;
            left: 20px;
            top: 60px;
            color: var(--text-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: var(--accent);
        }

        .back-button i {
            margin-right: 8px;
        }

        .section-title {
            font-size: 1.8rem;
            margin: 40px 0 20px;
            color: var(--accent);
            position: relative;
            padding-left: 15px;
        }

        .section-title:before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            height: 80%;
            width: 5px;
            background: linear-gradient(to bottom, var(--accent), var(--pink));
            border-radius: 5px;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 60px;
        }

        .gallery-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
            aspect-ratio: 1 / 1;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .gallery-item.video-item:after {
            content: '\f04b';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 2rem;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .download-button {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }

        .download-button:hover {
            background: var(--accent);
            transform: scale(1.1);
        }

        .no-content {
            text-align: center;
            padding: 40px 0;
            color: var(--text-light);
            font-size: 1.2rem;
        }

        footer {
            background: linear-gradient(135deg, var(--purple), var(--deep-purple));
            padding: 30px 0;
            text-align: center;
            margin-top: 60px;
        }

        .copyright {
            color: var(--text-lighter);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .event-title {
                font-size: 2rem;
                margin-left: 40px;
            }

            .gallery {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .event-title {
                font-size: 1.8rem;
            }

            .gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="event-header">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Все мероприятия
            </a>
            <h1 class="event-title"><?= htmlspecialchars($event['name_events']) ?></h1>
            <div class="event-date">
                <i class="far fa-calendar-alt"></i>
                Состоялось <?= date('d.m.Y', strtotime($event['date_event'])) ?>
            </div>
        </div>

        <!-- Видео с мероприятия -->
        <h2 class="section-title">Видео с мероприятия</h2>

        <?php if (!empty($videos)): ?>
            <div class="gallery" id="video-gallery">
                <?php foreach ($videos as $video): ?>
                    <a href="<?= htmlspecialchars($video['link']) ?>" class="gallery-item video-item"
                        data-sub-html="<h4><?= htmlspecialchars($event['name_events']) ?></h4>">
                        <?php if (!empty($video['link_prewie'])): ?>
                            <img src="<?= htmlspecialchars($video['link_prewie']) ?>"
                                alt="Видео с мероприятия <?= htmlspecialchars($event['name_events']) ?>">
                        <?php else: ?>
                            <img src="https://byfly.kz/assets/video-placeholder.jpg"
                                alt="Видео с мероприятия <?= htmlspecialchars($event['name_events']) ?>">
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($video['link']) ?>" download class="download-button"
                            title="Скачать видео">
                            <i class="fas fa-download"></i>
                        </a>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-content">
                <i class="far fa-frown" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <p>Видеоматериалы с этого мероприятия еще не добавлены</p>
            </div>
        <?php endif; ?>

        <!-- Фото с мероприятия -->
        <h2 class="section-title">Фото с мероприятия</h2>

        <?php if (!empty($photos)): ?>
            <div class="gallery" id="photo-gallery">
                <?php foreach ($photos as $photo): ?>
                    <a href="<?= htmlspecialchars($photo['link']) ?>" class="gallery-item"
                        data-sub-html="<h4><?= htmlspecialchars($event['name_events']) ?></h4>">
                        <img src="<?= htmlspecialchars($photo['link']) ?>"
                            alt="Фото с мероприятия <?= htmlspecialchars($event['name_events']) ?>">
                        <a href="<?= htmlspecialchars($photo['link']) ?>" download class="download-button" title="Скачать фото">
                            <i class="fas fa-download"></i>
                        </a>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-content">
                <i class="far fa-frown" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <p>Фотоматериалы с этого мероприятия еще не добавлены</p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="copyright">
            &copy; <?= date('Y') ?> ByFly Travel. Все права защищены.
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.1/lightgallery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lg-video/2.0.0/lg-video.min.js"></script>
    <script>
        // Инициализация галереи для фото
        if (document.getElementById('photo-gallery')) {
            lightGallery(document.getElementById('photo-gallery'), {
                selector: '.gallery-item',
                download: false,
                counter: false
            });
        }

        // Инициализация галереи для видео
        if (document.getElementById('video-gallery')) {
            lightGallery(document.getElementById('video-gallery'), {
                selector: '.gallery-item',
                download: false,
                counter: false,
                plugins: [lgVideo]
            });
        }
    </script>
</body>

</html>