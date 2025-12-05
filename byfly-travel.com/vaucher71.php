<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID заказа из параметров
$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

if ($orderId <= 0) {
    die('Неверный ID заказа');
}

// Получаем информацию о заказе
$orderQuery = "SELECT * FROM order_tours WHERE id = $orderId";
$orderResult = $db->query($orderQuery);

if ($orderResult->num_rows == 0) {
    die('Заказ не найден');
}

$order = $orderResult->fetch_assoc();

// Получаем информацию о пользователе
$userQuery = "SELECT * FROM users WHERE id = " . $order['user_id'];
$userResult = $db->query($userQuery);
$user = $userResult->fetch_assoc();

// Получаем информацию о менеджере
$manager = null;
if ($order['manager_id']) {
    $managerQuery = "SELECT * FROM managers WHERE id = " . $order['manager_id'];
    $managerResult = $db->query($managerQuery);
    if ($managerResult->num_rows > 0) {
        $manager = $managerResult->fetch_assoc();
    }
}

// Получаем информацию о пассажирах из JSON поля listPassangers
$passengers = [];
if (!empty($order['listPassangers'])) {
    $passengers = json_decode($order['listPassangers'], true);
}

// Декодируем информацию о туре
$tourInfo = json_decode($order['tours_info'], true);
$hotelInfo = json_decode($order['visor_hotel_info'], true);

// Функция для форматирования даты
function formatDate($date)
{
    $months = [
        1 => 'янв',
        2 => 'фев',
        3 => 'мар',
        4 => 'апр',
        5 => 'мая',
        6 => 'июн',
        7 => 'июл',
        8 => 'авг',
        9 => 'сен',
        10 => 'окт',
        11 => 'ноя',
        12 => 'дек'
    ];

    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);

    return "$day $month $year";
}

// Получаем информацию о рейсах если есть
$flightInfo = null;
if (isset($tourInfo['fly_info']) && isset($tourInfo['fly_info']['flights'])) {
    $flightInfo = $tourInfo['fly_info']['flights'][0];
}

// Функция получения статуса заказа
function getOrderStatus($statusCode)
{
    switch ($statusCode) {
        case 0:
            return 'В обработке';
        case 1:
            return 'Ожидает предоплату';
        case 2:
            return 'Ожидает доплату';
        case 3:
            return 'Оплачена';
        case 4:
            return 'На отдыхе';
        case 5:
            return 'Отменена';
        default:
            return 'Неизвестно';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>ВАУЧЕР BYFLY TRAVEL - ЗАКАЗ №<?php echo $orderId; ?></title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            background: white;
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            padding: 0;
        }

        .voucher-container {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            padding: 3mm;
            background: white;
            position: relative;
        }

        .header {
            text-align: center;
            background: linear-gradient(to bottom, #ff0000, #8b0000);
            color: white;
            padding: 3mm;
            margin: -3mm -3mm 2mm -3mm;
            border-radius: 0;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2mm;
        }

        .company-logo {
            height: 30px;
            width: auto;
        }

        .voucher-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1mm;
        }

        .order-info {
            font-size: 11px;
            font-weight: bold;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
            margin-bottom: 2mm;
        }

        .left-column,
        .right-column {
            display: flex;
            flex-direction: column;
        }

        .section {
            margin-bottom: 2mm;
            border: 1px solid #000;
            flex: 1;
        }

        .section-header {
            background: #f0f0f0;
            color: #000;
            padding: 1mm;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
        }

        .section-content {
            background: white;
            padding: 2mm;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .info-table td {
            padding: 1mm;
            border: 1px solid #ccc;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            width: 40%;
            background: #f8f8f8;
        }

        .info-value {
            width: 60%;
        }

        .passengers-section {
            grid-column: 1 / -1;
        }

        .passenger-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2mm;
        }

        .passenger-card {
            border: 1px solid #000;
            background: white;
            font-size: 8px;
        }

        .passenger-header {
            background: #f0f0f0;
            padding: 1mm;
            border-bottom: 1px solid #000;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
        }

        .passenger-content {
            padding: 2mm;
        }

        .passenger-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1mm;
        }

        .passenger-field {
            display: flex;
            flex-direction: column;
        }

        .passenger-label {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
            color: #666;
        }

        .passenger-value {
            font-size: 8px;
            font-weight: normal;
            padding: 1mm;
            border: 1px solid #ccc;
            background: #fafafa;
        }

        .passport-section {
            text-align: center;
            margin-top: 1mm;
            padding-top: 1mm;
            border-top: 1px solid #ccc;
        }

        .passport-button {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 1mm 2mm;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            color: #000;
            display: inline-block;
        }

        .passport-button:hover {
            background: #e0e0e0;
        }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
        }

        .flight-table th,
        .flight-table td {
            border: 1px solid #000;
            padding: 1mm;
            text-align: center;
        }

        .flight-table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .manager-section {
            border: 1px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            grid-column: 1 / -1;
        }

        .manager-title {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            margin-bottom: 1mm;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
        }

        .important-box {
            border: 1px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            background: #f9f9f9;
            grid-column: 1 / -1;
        }

        .important-title {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .important-text {
            font-size: 8px;
            line-height: 1.3;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
        }

        .important-text div {
            margin-bottom: 1mm;
            padding-left: 3mm;
            position: relative;
        }

        .important-text div::before {
            content: '•';
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .footer-signature {
            position: absolute;
            bottom: 3mm;
            right: 3mm;
            text-align: center;
        }

        .signature-image {
            max-width: 280px;
            max-height: 190px;
        }

        .director-info {
            font-size: 16px;
            margin-top: 1mm;
            font-weight: bold;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 1mm 2mm;
            border: 1px solid #000;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            background: white;
        }

        .company-info {
            position: absolute;
            bottom: 3mm;
            left: 3mm;
            font-size: 14px;
            color: #666;
            max-width: 60%;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
                padding: 0;
            }

            .voucher-container {
                border: 2px solid #000;
                box-shadow: none;
                height: 277mm;
            }

            .passport-button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="voucher-container">
        <!-- Заголовок с логотипом -->
        <div class="header">
            <div class="logo-container">
                <img src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel" class="company-logo">
            </div>
            <div class="voucher-title">Туристический ваучер</div>
            <div class="order-info">Заказ №<?php echo str_pad($orderId, 8, '0', STR_PAD_LEFT); ?> от
                <?php echo formatDate($order['date_create']); ?>
            </div>
        </div>

        <div class="content-wrapper">
            <!-- Левая колонка -->
            <div class="left-column">
                <!-- Информация о туре -->
                <div class="section">
                    <div class="section-header">Информация о туре</div>
                    <div class="section-content">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Направление:</td>
                                <td class="info-value">
                                    <?php echo $tourInfo['countryname'] . ', ' . $tourInfo['hotelregionname']; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="info-label">Отель:</td>
                                <td class="info-value"><?php echo $tourInfo['hotelname']; ?>
                                    (<?php echo str_repeat('★', $tourInfo['hotelstars']); ?>)</td>
                            </tr>
                            <tr>
                                <td class="info-label">Заезд:</td>
                                <td class="info-value"><?php echo formatDate($tourInfo['flydate']); ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Ночей:</td>
                                <td class="info-value"><?php echo $tourInfo['nights']; ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Питание:</td>
                                <td class="info-value"><?php echo $tourInfo['meal']; ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Размещение:</td>
                                <td class="info-value"><?php echo $tourInfo['placement']; ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Статус:</td>
                                <td class="info-value">
                                    <span
                                        class="status-badge"><?php echo getOrderStatus($order['status_code']); ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Правая колонка -->
            <div class="right-column">
                <!-- Информация о рейсах -->
                <?php if ($flightInfo): ?>
                    <div class="section">
                        <div class="section-header">Перелет</div>
                        <div class="section-content">
                            <?php if (isset($flightInfo['forward'])): ?>
                                <div style="font-weight: bold; margin-bottom: 1mm; font-size: 8px;">Туда -
                                    <?php echo $flightInfo['dateforward']; ?>
                                </div>
                                <table class="flight-table">
                                    <tr>
                                        <th>Рейс</th>
                                        <th>Откуда</th>
                                        <th>Куда</th>
                                        <th>Время</th>
                                    </tr>
                                    <?php foreach ($flightInfo['forward'] as $flight): ?>
                                        <tr>
                                            <td style="font-weight: bold;"><?php echo $flight['number']; ?></td>
                                            <td><?php echo $flight['departure']['port']['shortName']; ?></td>
                                            <td><?php echo $flight['arrival']['port']['shortName']; ?></td>
                                            <td><?php echo $flight['departure']['time']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>

                            <?php if (isset($flightInfo['backward'])): ?>
                                <div style="font-weight: bold; margin: 2mm 0 1mm; font-size: 8px;">Обратно -
                                    <?php echo $flightInfo['datebackward']; ?>
                                </div>
                                <table class="flight-table">
                                    <tr>
                                        <th>Рейс</th>
                                        <th>Откуда</th>
                                        <th>Куда</th>
                                        <th>Время</th>
                                    </tr>
                                    <?php foreach ($flightInfo['backward'] as $flight): ?>
                                        <tr>
                                            <td style="font-weight: bold;"><?php echo $flight['number']; ?></td>
                                            <td><?php echo $flight['departure']['port']['shortName']; ?></td>
                                            <td><?php echo $flight['arrival']['port']['shortName']; ?></td>
                                            <td><?php echo $flight['departure']['time']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Пассажиры -->
            <div class="section passengers-section">
                <div class="section-header">Пассажиры</div>
                <div class="section-content">
                    <div class="passenger-grid">
                        <?php foreach ($passengers as $index => $passenger): ?>
                            <div class="passenger-card">
                                <div class="passenger-header">
                                    №<?php echo $index + 1; ?> -
                                    <?php echo $passenger['passanger_famale'] . ' ' . $passenger['passanger_name']; ?>
                                </div>

                                <div class="passenger-content">
                                    <div class="passenger-info">
                                        <div class="passenger-field">
                                            <div class="passenger-label">Фамилия</div>
                                            <div class="passenger-value"><?php echo $passenger['passanger_famale']; ?></div>
                                        </div>
                                        <div class="passenger-field">
                                            <div class="passenger-label">Имя</div>
                                            <div class="passenger-value"><?php echo $passenger['passanger_name']; ?></div>
                                        </div>
                                        <div class="passenger-field">
                                            <div class="passenger-label">Дата рождения</div>
                                            <div class="passenger-value">
                                                <?php echo formatDate($passenger['date_berthday']); ?>
                                            </div>
                                        </div>
                                        <div class="passenger-field">
                                            <div class="passenger-label">Документ</div>
                                            <div class="passenger-value"><?php echo $passenger['number_pasport']; ?></div>
                                        </div>
                                        <div class="passenger-field">
                                            <div class="passenger-label">Телефон</div>
                                            <div class="passenger-value"><?php echo $passenger['passangers_phone']; ?></div>
                                        </div>
                                        <?php if (!empty($passenger['iin'])): ?>
                                            <div class="passenger-field">
                                                <div class="passenger-label">ИИН</div>
                                                <div class="passenger-value"><?php echo $passenger['iin']; ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($passenger['pasport_link'])): ?>
                                        <div class="passport-section">
                                            <a href="<?php echo $passenger['pasport_link']; ?>" target="_blank"
                                                class="passport-button">
                                                Паспорт
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Менеджер -->
            <?php if ($manager): ?>
                <div class="manager-section">
                    <div class="manager-title">Персональный менеджер</div>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">ФИО:</td>
                            <td class="info-value"><?php echo $manager['fio']; ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Телефон:</td>
                            <td class="info-value"><?php echo $manager['phone_call']; ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">WhatsApp:</td>
                            <td class="info-value"><?php echo $manager['phone_whatsapp']; ?></td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Важная информация -->
            <div class="important-box">
                <div class="important-title">Важная информация</div>
                <div class="important-text">
                    <div>Ваучер — подтверждение бронирования</div>
                    <div>Предъявите при регистрации в отеле</div>
                    <div>Имейте действующий загранпаспорт</div>
                    <div>Прибудьте в аэропорт за 2-3 часа</div>
                    <div>Обязательна медстраховка</div>
                    <div>Сохраните до окончания поездки</div>
                </div>
            </div>
        </div>

        <!-- Подпись и печать директора -->
        <div class="footer-signature">
            <img src="https://api.v.2.byfly.kz/images/pechat_rospis.png" alt="Подпись и печать" class="signature-image">
            <div class="director-info">
                Директор: Щетинин А.В.<br>
                ТОО "ByFly Travel"
            </div>
        </div>

        <!-- Информация о компании -->
        <div class="company-info">
            ТОО "ByFly Travel" | Сеть туристических агентств<br>
            Email: info@byfly.kz
        </div>
    </div>

    <script>
        // Автоматический запуск печати после полной загрузки
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 800);
        });
    </script>
</body>

</html>