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

// Вычисляем дату за месяц до вылета
function getDocumentDate($flyDate)
{
    $flyTimestamp = strtotime($flyDate);
    $documentTimestamp = strtotime('-1 month', $flyTimestamp);
    return formatDate(date('Y-m-d', $documentTimestamp));
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>ПОДТВЕРЖДЕНИЕ БРОНИРОВАНИЯ - ЗАКАЗ №<?php echo $orderId; ?></title>
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

        .document-container {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            padding: 3mm;
            background: white;
            position: relative;
        }

        .header {
            text-align: center;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 4mm;
            margin: -3mm -3mm 3mm -3mm;
            border-radius: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2mm;
        }

        .company-logo {
            height: 35px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .document-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1mm;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .order-info {
            font-size: 12px;
            font-weight: bold;
            opacity: 0.9;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
            margin-bottom: 3mm;
        }

        .left-column,
        .right-column {
            display: flex;
            flex-direction: column;
        }

        .section {
            margin-bottom: 3mm;
            border: 1px solid #2a5298;
            border-radius: 3px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            padding: 2mm;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-content {
            background: white;
            padding: 3mm;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .info-table td {
            padding: 2mm;
            border: 1px solid #e0e0e0;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            width: 40%;
            background: #f8f9fa;
            color: #2a5298;
        }

        .info-value {
            width: 60%;
            background: white;
        }

        .passengers-section {
            grid-column: 1 / -1;
        }

        .passenger-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 3mm;
        }

        .passenger-card {
            border: 1px solid #2a5298;
            border-radius: 3px;
            background: white;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .passenger-header {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            padding: 2mm;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }

        .passenger-content {
            padding: 3mm;
        }

        .passenger-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
        }

        .passenger-field {
            display: flex;
            flex-direction: column;
        }

        .passenger-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
            color: #2a5298;
        }

        .passenger-value {
            font-size: 9px;
            padding: 1.5mm;
            border: 1px solid #e0e0e0;
            background: #f8f9fa;
            border-radius: 2px;
        }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .flight-table th,
        .flight-table td {
            border: 1px solid #2a5298;
            padding: 2mm;
            text-align: center;
        }

        .flight-table th {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            font-weight: bold;
        }

        .companies-section {
            border: 2px solid #2a5298;
            padding: 3mm;
            margin: 3mm 0;
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 3px;
        }

        .companies-title {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            margin-bottom: 3mm;
            text-transform: uppercase;
            color: #2a5298;
            border-bottom: 2px solid #2a5298;
            padding-bottom: 2mm;
        }

        .companies-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4mm;
            font-size: 9px;
        }

        .company-details {
            background: white;
            padding: 3mm;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .kz-company {
            border-left: 4px solid #28a745;
        }

        .uz-company {
            border-left: 4px solid #2a5298;
        }

        .company-header {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2mm;
            text-transform: uppercase;
            text-align: center;
            padding-bottom: 1mm;
            border-bottom: 1px solid #e0e0e0;
        }

        .kz-company .company-header {
            color: #28a745;
        }

        .uz-company .company-header {
            color: #2a5298;
        }

        .important-box {
            border: 2px solid #dc3545;
            padding: 3mm;
            margin: 3mm 0;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
            grid-column: 1 / -1;
            border-radius: 3px;
        }

        .important-title {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            margin-bottom: 2mm;
            text-transform: uppercase;
            color: #dc3545;
        }

        .important-text {
            font-size: 9px;
            line-height: 1.4;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
        }

        .important-text div {
            margin-bottom: 2mm;
            padding-left: 4mm;
            position: relative;
        }

        .important-text div::before {
            content: '●';
            position: absolute;
            left: 0;
            font-weight: bold;
            color: #dc3545;
        }

        .payment-info {
            border: 2px solid #28a745;
            padding: 3mm;
            margin: 3mm 0;
            background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
            grid-column: 1 / -1;
            border-radius: 3px;
        }

        .payment-title {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            margin-bottom: 2mm;
            text-transform: uppercase;
            color: #28a745;
        }

        .payment-text {
            font-size: 10px;
            text-align: center;
            line-height: 1.4;
            color: #155724;
        }

        .documents-info {
            border: 2px solid #ffc107;
            padding: 3mm;
            margin: 3mm 0;
            background: linear-gradient(135deg, #fffdf0 0%, #ffffff 100%);
            grid-column: 1 / -1;
            border-radius: 3px;
        }

        .documents-title {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            margin-bottom: 2mm;
            text-transform: uppercase;
            color: #856404;
        }

        .documents-text {
            font-size: 10px;
            text-align: center;
            line-height: 1.4;
            color: #856404;
        }

        .footer-signature {
            position: absolute;
            bottom: 5mm;
            right: 5mm;
            text-align: center;
        }

        .signature-image {
            max-width: 300px;
            max-height: 200px;
        }

        .director-info {
            font-size: 10px;
            margin-top: 2mm;
            font-weight: bold;
            text-align: center;
            color: #2a5298;
        }

        .status-badge {
            display: inline-block;
            padding: 2mm 3mm;
            border: 1px solid #2a5298;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            border-radius: 3px;
        }

        .company-info {
            position: absolute;
            bottom: 5mm;
            left: 5mm;
            font-size: 8px;
            color: #666;
            max-width: 60%;
            line-height: 1.3;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
                padding: 0;
            }

            .document-container {
                border: 2px solid #000;
                box-shadow: none;
                height: 277mm;
            }
        }
    </style>
</head>

<body>
    <div class="document-container">
        <!-- Заголовок с логотипом -->
        <div class="header">
            <div class="logo-container">
                <img src="https://byfly.kz/assets/logo-610c625f.svg" alt="ByFly Travel" class="company-logo">
            </div>
            <div class="document-title">Подтверждение бронирования</div>
            <div class="order-info">Заказ №<?php echo str_pad($orderId, 8, '0', STR_PAD_LEFT); ?> от
                <?php echo formatDate($order['date_create']); ?></div>
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
                                    <?php echo $tourInfo['countryname'] . ', ' . $tourInfo['hotelregionname']; ?></td>
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
                                <div style="font-weight: bold; margin-bottom: 2mm; font-size: 9px; color: #2a5298;">
                                    Туда - <?php echo $flightInfo['dateforward']; ?>
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
                                <div style="font-weight: bold; margin: 3mm 0 2mm; font-size: 9px; color: #2a5298;">
                                    Обратно - <?php echo $flightInfo['datebackward']; ?>
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

            <!-- Информация о компаниях -->
            <div class="companies-section">
                <div class="companies-title">Участники сделки</div>
                <div class="companies-info">
                    <!-- Казахстанская компания -->
                    <div class="company-details kz-company">
                        <div class="company-header">Турагентство (продавец)</div>
                        <strong>ТОО "BYFLY"</strong><br>
                        <strong>Адрес:</strong> г. Алматы, ул. Брусиловского, д. 163<br>
                        <strong>БИН:</strong> 231040040048<br>
                        <strong>Банк:</strong> АО "Kaspi Bank"<br>
                        <strong>КБе:</strong> 17<br>
                        <strong>БИК:</strong> CASPKZKA<br>
                        <strong>Номер счета:</strong> KZ43722S000030657761<br><br>
                        <em>Продажа туристических услуг</em>
                    </div>

                    <!-- Узбекская компания -->
                    <div class="company-details uz-company">
                        <div class="company-header">Туроператор (исполнитель)</div>
                        <strong>ООО "BYFLY TRAVEL"</strong><br>
                        <strong>Адрес:</strong> г. Ташкент, Республика Узбекистан<br>
                        Юнусабадский район, Barhayot MFY<br>
                        12 MAVZESI, 20A-UY<br>
                        <strong>ИНН:</strong> 312139239<br>
                        <strong>МФО Банка:</strong> 00974<br>
                        <strong>Номер счета:</strong> 20208 00080 72434 51001<br><br>
                        <em>Оформление документов и бронирование</em>
                    </div>
                </div>
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
                                                <?php echo formatDate($passenger['date_berthday']); ?></div>
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
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Информация об оплате -->
            <div class="payment-info">
                <div class="payment-title">Информация об оплате</div>
                <div class="payment-text">
                    <strong>Чек и подтверждение оплаты находятся в банковском приложении,<br>
                        через которое производилась оплата данного тура.</strong><br><br>
                    Сохраните данное подтверждение бронирования до окончания поездки.
                </div>
            </div>

            <!-- Информация о документах -->
            <div class="documents-info">
                <div class="documents-title">Документы по туру</div>
                <div class="documents-text">
                    <strong>Все документы по туру (билеты, ваучеры, страховка)<br>
                        будут выданы за 1 месяц до вылета<br>
                        (<?php echo getDocumentDate($tourInfo['flydate']); ?>)</strong>
                </div>
            </div>

            <!-- Важная информация -->
            <div class="important-box">
                <div class="important-title">Важная информация</div>
                <div class="important-text">
                    <div>Подтверждение бронирования тура</div>
                    <div>Предъявите при регистрации в отеле</div>
                    <div>Имейте действующий загранпаспорт</div>
                    <div>Прибудьте в аэропорт за 2-3 часа</div>
                    <div>Обязательна медицинская страховка</div>
                    <div>Сохраните до окончания поездки</div>
                    <div>Документы выдаются за месяц до вылета</div>
                    <div>Чек оплаты в банковском приложении</div>
                </div>
            </div>
        </div>

        <!-- Подпись и печать директора -->
        <div class="footer-signature">
            <img src="https://byfly-travel.com/pechat_uzbekistan.png" alt="Подпись и печать" class="signature-image">
            <div class="director-info">
                Директор ООО "BYFLY TRAVEL"
            </div>
        </div>

        <!-- Информация о компании -->
        <div class="company-info">
            Продавец: ТОО "BYFLY" (Казахстан)<br>
            Исполнитель: ООО "BYFLY TRAVEL" (Узбекистан)<br>
            Email: info@byfly.kz | WhatsApp: +7 777 370 07 72
        </div>
    </div>

    <script>
        // Автоматический запуск печати после полной загрузки
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 1000);
        });
    </script>
</body>

</html>