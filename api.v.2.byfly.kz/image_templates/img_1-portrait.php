<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function generateQRLink($url)
{
    return generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . urlencode($url) . '&size=260&padding=15&color=light');
}

function generateQR($url)
{
    $qrUrl = $url;
    $qrImage = file_get_contents($qrUrl);
    if ($qrImage !== false) {
        return 'data:image/png;base64,' . base64_encode($qrImage);
    } else {
        return null;
    }
}

function generateRandomGradient()
{
    // Базовые цвета и их более темные оттенки
    $colors = [
        ["rgb(64, 224, 208)", "rgb(0, 139, 139)"],  // Бирюзовый → Тёмно-бирюзовый
        ["rgb(173, 255, 47)", "rgb(85, 107, 47)"],  // Лаймовый → Оливково-зеленый
        ["rgb(220, 20, 60)", "rgb(139, 0, 0)"],     // Красный → Тёмно-красный
        ["rgb(30, 144, 255)", "rgb(0, 0, 139)"],    // Синий → Тёмно-синий
        ["rgb(138, 43, 226)", "rgb(75, 0, 130)"]    // Фиолетовый → Индиго
    ];

    // Выбираем случайную пару цветов
    $randomIndex = array_rand($colors);

    return [
        'color1' => $colors[$randomIndex][0], // Основной цвет
        'color2' => $colors[$randomIndex][1]  // Тёмный оттенок
    ];
}

$user_id = $_GET['user_id'] ?? null;
$title = $_GET['title'] ?? 'Заголовок';
$desc = $_GET['desc'] ?? 'Описание';
$img = $_GET['img'] ?? 'https://api.v.2.byfly.kz/html_to_png/no-image.png';

$qr = null;
$userInfoData = null;

$width = $_GET['width'] ?? 1080;
$height = $_GET['height'] ?? 1920;

if ($user_id != null) {
    $userInfoDB = $db->query("SELECT * FROM users WHERE id='" . $user_id . "'");
    if ($userInfoDB->num_rows > 0) {
        $userInfo = $userInfoDB->fetch_assoc();
        if ($userInfo['user_status'] != 'user') {
            $qr = generateQRLink($userInfo['id']);
            $userInfoData = $userInfo;
        }
    }
}

$logo = $_GET['logo'] ?? 'https://api.v.2.byfly.kz/images/logo-white.png';
$colors = generateRandomGradient();

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Изображение</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fff;
            zoom: 1;
        }

        .description {
            font-size: 55px;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            max-height: calc(45px * 1.25 * 7);
        }

        .image-container {
            position: relative;
            width:
                <?php echo $width . 'px'; ?>
            ;
            height:
                <?php echo $height . 'px'; ?>
            ;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ВЕРХНИЙ БЛОК */
        .top-section {
            position: relative;
            width: 100%;
            height: 80%;
            background-image: url('<?php echo $img; ?>');
            background-size: cover;
            background-position: center bottom;
        }

        /* Затемнение картинки */
        .top-section::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7) 20%, rgba(0, 0, 0, 0) 100%);
        }

        /* Логотип и QR-код */
        .overlay-content {
            position: absolute;
            width: 100%;
            top: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 20px;
            align-items: center;
            z-index: 2;
        }

        .logo {
            max-width: 300px;
            margin-top: -350px;
            margin-left: 10px;
        }

        .qr {
            width: 200px;
            margin-right: 50px;
            margin-bottom: 30px;
        }

        /* ТЕКСТОВЫЙ БЛОК */
        .content {
            width: 100%;
            height: 60%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 30px;
            box-sizing: border-box;
            color: white;
            background: linear-gradient(to top,
                    <?= $colors['color1'] ?>
                    ,
                    <?= $colors['color2'] ?>
                );
            margin-top: -20px;
            z-index: 9999999;
            text-align: left;
        }

        .title {
            font-size: 4rem;
            font-weight: bold;
            text-align: left;
        }

        .price {
            font-size: 6rem;
            margin-top: -10px;
            text-align: left;
            font-weight: bold;
        }

        .pricePromo {
            font-size: 6rem;
            color: white;
            font-weight: bold;
            margin-top: -5px;
            text-align: right;
            color: white;
            margin-right: 60px;
        }

        .nights,
        .dates {
            font-size: 3rem;
            margin-top: 5px;
        }

        .country {
            font-size: 5rem;
            text-align: left;
        }

        .promocode {
            font-size: 4rem;
            text-align: right;
            color: white;
            font-weight: bold;
            margin-top: 10px;
            margin-right: 60px;
        }

        .wave {
            position: absolute;
            top: 77%;
            left: 0;
            width: 100%;
            height: auto;
            transform: rotate(180deg);
            z-index: 9999;
        }
    </style>
</head>

<body>

    <div class="image-container">
        <div class="top-section">
            <div class="overlay-content">
                <img src="<?php echo $logo; ?>" alt="Logo" class="logo">
                <div style="text-align: right;">
                    <?php if ($qr): ?>
                        <img src="<?php echo $qr; ?>" alt="QR Code" class="qr">
                    <?php endif; ?>
                    <div <?= $userInfoData == null ? 'style="display: none;"' : 'style="font-size: 45px; color: white; text-align: right; margin-right: 60px;"' ?> class="title">
                        Скидка по моему промокоду:
                    </div>
                    <div <?= $userInfoData == null ? 'style="display: none;"' : '' ?> class="promocode">
                        "<?= $userInfoData['promo_code'] ?>"</div>
                    <div <?= $userInfoData == null ? 'style="display: none;"' : '' ?> class="pricePromo">
                        -<?= number_format($userInfoData['refer_registration_bonus'], 0, '', ' ') ?> <small>₸</small>
                    </div>
                </div>
            </div>
            <svg class="wave" viewBox="0 0 1440 320">
                <path fill="<?= $colors['color2'] ?>" fill-opacity="1"
                    d="M0,128L80,112C160,96,320,64,480,85.3C640,107,800,181,960,192C1120,203,1280,149,1360,122.7L1440,96V0H0Z">
                </path>
            </svg>
        </div>
        <div class="content">
            <div style="margin-top: -40px; width: 90%; text-align: left;">

                <div class="country">
                    <?= $title ?>
                </div>
                <div
                    style="width: 100%;height: 4px;margin-bottom: 30px;margin-top: 30px;background: repeating-linear-gradient(to right,white,white 10px,transparent 10px,transparent 20px);">
                </div>
                <div class="description">
                    <?= $desc; ?>
                </div>
            </div>
        </div>

    </div>

</body>

</html>