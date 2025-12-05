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
$qr = null;
$userInfoData = null;

$width = $_GET['width'] ?? 1920;
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

$countryInfo = null;
$countryIcon = null;

$dates = array();

if (empty($_GET['country_to']) == false) {
    $countrySearchDB = $db->query("SELECT * FROM countries WHERE visor_id='" . $_GET['country_to'] . "'");
    if ($countrySearchDB->num_rows > 0) {
        $countrySearch = $countrySearchDB->fetch_assoc();
        $countryInfo = $countrySearch;
        $countryInfo['images'] = array();
        $searchImageDB = $db->query("SELECT * FROM countries_image WHERE country_id='" . $countryInfo['id'] . "'");
        while ($searchImage = $searchImageDB->fetch_assoc()) {
            array_push($countryInfo['images'], $searchImage['image']);
        }
        $countryIcon = 'https://byfly.kz/' . $countryInfo['icon'];
    } else {
        header("HTTP/1.1 404 Not Found");
        exit;
    }
} else {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$logo = $_GET['logo'] ?? 'https://api.v.2.byfly.kz/images/logo-white.png';
$colors = generateRandomGradient();


if (empty($_GET['citys']) == false) {
    $arrayCitys = explode(',', $_GET['citys']);
    shuffle($arrayCitys);
    $arrayCitys = array_slice($arrayCitys, 0, 4);

    foreach ($arrayCitys as $cityId) {
        $query = array(
            "authlogin" => $tourvisor_login,
            "authpass" => $tourvisor_password,
            "format" => "json",
            "items" => 1,
            "city" => $cityId,
            "countries" => $_GET['country_to'],
            "currency" => 3,
            "picturetype" => 1,
            "sort" => 1,
            "datefrom" => $_GET['date_from'] ?? date("d.m.Y", strtotime("+5 days")),
            "dateto" => $_GET['date_to'] ?? date("d.m.Y", strtotime("+40 days")),
            "maxdays" => 30,
        );

        if ($_GET['regioncode'] != null) {
            $query['regions'] = $_GET['regioncode'];
        }



        $url = 'http://tourvisor.ru/xml/hottours.php?' . http_build_query($query);
        $data = file_get_contents($url);
        $datae = json_decode($data, true);

        if ($datae['hottours']['hotcount'] > 0) {
            $tour = $datae['hottours']['tour'][0];
            $dates[] = 'Вылет из ' . $tour['departurenamefrom'] . ' - ' . $tour['flydate'] . ', ночей - ' . $tour['nights'] . ', <strong>Стоимость - от ' . number_format($tour['price'], 0, '', ' ') . ' <small>₸</small></strong> / 1 чел. (' . $tour['meal'] . ') <div style="
    width: 100%;
    height: 4px;
    margin-bottom: 30px;
    margin-top: 30px;
    background: repeating-linear-gradient(
        to right,
        white,
        white 10px,
        transparent 10px,
        transparent 20px
    );
"></div>';
        }
    }

} else {
    header("HTTP/1.1 404 Not Found");
    exit;
}
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
            background-image: url('<?php echo $countryInfo['images'][array_rand($countryInfo['images'])]; ?>');
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
            margin-top: -390px;
            margin-left: 10px;
        }

        .qr {
            width: 200px;
            margin-right: 50px;
            margin-bottom: 40px;
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
            color: #fff;
            text-align: right;
            font-weight: bold;
            margin-top: -5px;
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
            font-weight: bold;
            margin-top: 10px;
            color: white;
            margin-right: 50px;
        }

        .wave {
            position: absolute;
            top: 60%;
            left: 0;
            width: 100%;
            height: auto;
            transform: rotate(180deg);
            z-index: 999999;
        }
    </style>
</head>

<body>

    <div class="image-container">
        <div class="top-section">
            <div class="overlay-content">

                <img src="<?php echo $logo; ?>" alt="Logo" class="logo">
                <div style="text-align: right;margin-right: 20px;">
                    <?php if ($qr): ?>
                        <img src="<?php echo $qr; ?>" alt="QR Code" class="qr">
                    <?php endif; ?>
                    <div <?= $userInfoData == null ? 'style="display: none;"' : 'style="font-size: 55px;color: white;text-align: right;margin-right: 50px;"' ?> class="title">
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
            <div style="width: 90%; margin-top: -100px;">

                <div <?= $countryIcon == null ? 'style="display: none;"' : '' ?> class="country"><img height="70px"
                        style="margin-right: 20px;" src="<?= $countryIcon ?>"><?= $countryInfo['title'] ?>
                </div>
                <div style="
    width: 100%;
    height: 4px;
    margin-bottom: 30px;
    margin-top: 30px;
    background: repeating-linear-gradient(
        to right,
        white,
        white 10px,
        transparent 10px,
        transparent 20px
    );
"></div>
                <div <?= $price == null ? 'style="display: none;"' : '' ?> class="price">
                    от <?php echo number_format($price, 0, ',', ' '); ?> KZT
                </div>
                <?php
                if (count($dates) > 0) {
                    foreach ($dates as $date) {
                        echo '<div class="dates">' . $date . '</div>';
                    }
                }
                ?>

            </div>
        </div>

    </div>

</body>

</html>