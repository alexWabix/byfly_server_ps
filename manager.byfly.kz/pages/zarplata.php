<!DOCTYPE html>
<?php
if (empty($_GET['id'])) {
    $_GET['id'] = $userInfo['id'];
}

function getManagerPercentage($price)
{
    return number_format($price, 0, '.', ' ') . ' KZT';
}

// Получение текущего или выбранного месяца
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$startDate = date("Y-m-01 00:00:00", strtotime($selectedMonth));
$endDate = date("Y-m-t 23:59:59", strtotime($selectedMonth));

// Получение информации о менеджере
$managerInfo = $db->query("SELECT fio, oklad, percentage_of_commisiion FROM managers WHERE id = " . intval($_GET['id']))->fetch_assoc();
$managerFio = $managerInfo['fio'];
$managerOklad = $managerInfo['oklad'];
$managerPercentage = $managerInfo['percentage_of_commisiion'];

// Подсчет заработка за выбранный месяц (20% от 7% комиссии)
$zpSumm = 0;
$zp = $db->query("SELECT SUM(price) AS total FROM order_tours WHERE  manager_id='" . $_GET['id'] . "' AND price = includesPrice AND date_create  BETWEEN '$startDate' AND '$endDate'");
if ($zp) {
    $row = $zp->fetch_assoc();
    $sum = $row['total'] ?? 0;
    $commission = ($sum * 7) / 100; // 7% комиссия
    $zpSumm = ($commission * 20) / 100; // 20% от комиссии
}

// Подсчет прогнозируемого заработка за месяц
$zpSumm2 = 0;
$zp2 = $db->query("SELECT SUM(price) AS total FROM order_tours WHERE  manager_id='" . $_GET['id'] . "' AND date_create BETWEEN '$startDate' AND '$endDate'");
if ($zp2) {
    $row2 = $zp2->fetch_assoc();
    $sum2 = $row2['total'] ?? 0;
    $commission2 = ($sum2 * 7) / 100; // 7% комиссия
    $zpSumm2 = ($commission2 * 20) / 100; // 20% от комиссии
}

// Функция для расчета суммы к ЗП по каждой заявке
function getPT($order)
{
    if ($order['price'] == $order['includesPrice']) {
        $commission = ($order['price'] * 7) / 100; // 7% комиссия
        $summ = ($commission * 20) / 100; // 20% от комиссии
        return number_format($summ, 0, '.', ' ') . ' KZT';
    } else {
        return '0 KZT';
    }
}
function getStatus($status)
{
    $statuses = [
        0 => 'Новая в обработке',
        1 => 'Подтверждена - Требуется предоплата',
        2 => 'Подтверждена - Требуется полная оплата',
        3 => 'Полностью оплачена, ожидает вылета',
        4 => 'Турист на отдыхе',
        5 => 'Отменена'
    ];

    // Проверяем наличие статуса в массиве
    return isset($statuses[$status]) ? $statuses[$status] : 'Неизвестный статус';
}
?>

<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(to right, #ae011a, #4a000b);
            padding-left: 20px;
            padding-right: 20px;
        }

        .content {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            background: #8b0000;
            color: white;
        }

        table tbody tr:nth-child(even) {
            background: #f7f7f7;
        }

        table tbody tr:hover {
            background: #f2f2f2;
        }

        .filter {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter input,
        .filter button,
        .filter select {
            padding: 10px;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
        }

        .filter button {
            background: #8b0000;
            color: white;
            cursor: pointer;
        }

        .filter button:hover {
            background: #ff4b4b;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #printable-section,
            #printable-section * {
                visibility: visible;
            }

            #printable-section {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
        }
    </style>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(to right, #ae011a, #4a000b);
            padding-left: 20px;
            padding-right: 20px;
        }

        .navbar-brand {
            margin-right: auto;
        }

        .nav-link,
        .dropdown-item {
            color: black;
            margin-right: 15px;
            text-decoration: none;
        }

        .nav-link.active {
            color: red !important;
        }

        .content {
            margin-top: 20px;
        }

        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .card {
            flex: 1 1 calc(25% - 15px);
            background: linear-gradient(to bottom, #ff4b4b, #8b0000);
            color: white;
            border-radius: 10px;
            text-align: center;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .card h3 {
            margin-bottom: 10px;
        }

        .card p {
            font-size: 18px;
            font-weight: bold;
        }

        .card i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            background: #8b0000;
            color: white;
        }

        table tbody tr:nth-child(even) {
            background: #f7f7f7;
        }

        table tbody tr:hover {
            background: #f2f2f2;
        }

        .filter {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter input,
        .filter button,
        .filter select {
            padding: 10px;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
        }

        .filter button {
            background: #8b0000;
            color: white;
            cursor: pointer;
        }

        .filter button:hover {
            background: #ff4b4b;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <?php include('modules/logo.php'); ?>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <?php include('modules/user_info.php'); ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php include('modules/header.php'); ?>

    <div class="container content">
        <!-- Информация о менеджере -->
        <div class="filter">
            <h4>Информация о менеджере</h4>
            <p><strong>ФИО:</strong> <?= $managerFio; ?></p>
            <p><strong>Оклад:</strong> <?= number_format($managerOklad, 0, '.', ' ') . ' KZT'; ?></p>
            <p><strong>Процент от комиссии:</strong> <?= $managerPercentage; ?>%</p>
        </div>

        <!-- Выбор месяца -->
        <div class="filter">
            <h4>Выбор месяца</h4>
            <form method="GET" action="">
                <label for="month">Выберите месяц:</label>
                <input hidden name="page" value="zarplata">
                <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                    <?php for ($i = 0; $i < 24; $i++): ?>
                        <?php $month = date('Y-m', strtotime("-$i months")); ?>
                        <option value="<?= $month; ?>" <?= $month == $selectedMonth ? 'selected' : ''; ?>>
                            <?= date('F Y', strtotime($month)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <!-- Карточки -->
        <div class="summary-cards">
            <div class="card">
                <i class="ion-social-usd"></i>
                <h3>Заработано</h3>
                <p><?= number_format($zpSumm, 0, '.', ' ') . ' KZT'; ?></p>
            </div>
            <div class="card">
                <i class="ion-ios-analytics"></i>
                <h3>Прогноз</h3>
                <p><?= number_format($zpSumm2, 0, '.', ' ') . ' KZT'; ?></p>
            </div>
            <div class="card">
                <i class="ion-ios-briefcase"></i>
                <h3>Оклад</h3>
                <p><?= number_format($managerOklad, 0, '.', ' ') . ' KZT'; ?></p>
            </div>
            <div class="card">
                <i class="ion-cash"></i>
                <h3>К выдаче</h3>
                <p><?= number_format($zpSumm + $managerOklad, 0, '.', ' ') . ' KZT'; ?></p>
            </div>
        </div>

        <!-- Кнопка печати -->
        <div class="filter">
            <button class="btn btn-primary" onclick="printReport()">Печать отчета</button>
        </div>

        <!-- Таблица -->
        <div id="printable-section">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>№</th>
                        <th style="text-align: center;">Дата оформления</th>
                        <th style="text-align: center;">Стоимость</th>
                        <th style="text-align: center;">Клиент внес</th>
                        <th style="text-align: center;">Начислено к ЗП</th>
                        <th style="text-align: right;">Статус заявки</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $listOrdersDB = $db->query("SELECT * FROM order_tours WHERE  manager_id='" . $_GET['id'] . "' AND date_create BETWEEN '$startDate' AND '$endDate'");
                    $totalEarnings = 0;
                    $totalPrice = 0;
                    $totalIncludes = 0;
                    while ($listOrders = $listOrdersDB->fetch_assoc()) {
                        $earnings = $listOrders['price'] == $listOrders['includesPrice'] ? getPT($listOrders) : '0 KZT';
                        echo '<tr>
                            <td>' . $listOrders['id'] . '</td>
                            <td style="text-align: center;">' . $listOrders['date_create'] . '</td>
                            <td style="text-align: center;">' . number_format($listOrders['price'], 0, '.', ' ') . ' KZT</td>
                            <td style="text-align: center;">' . number_format($listOrders['includesPrice'], 0, '.', ' ') . ' KZT</td>
                            <td style="text-align: center;">' . $earnings . '</td>
                            <td style="text-align: right;">' . getStatus($listOrders['status_code']) . '</td>
                        </tr>';
                        $totalEarnings += $listOrders['price'] == $listOrders['includesPrice'] ? ($listOrders['price'] * 0.07 * 0.2) : 0;
                        $totalPrice += $listOrders['price'];
                        $totalIncludes += $listOrders['includesPrice'];
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Итого:</th>
                        <th style="text-align: center;"><?= number_format($totalPrice, 0, '.', ' ') . ' KZT'; ?></th>
                        <th style="text-align: center;"><?= number_format($totalIncludes, 0, '.', ' ') . ' KZT'; ?></th>
                        <th style="text-align: center;"><?= number_format($totalEarnings, 0, '.', ' ') . ' KZT'; ?></th>
                        <th></th>
                    </tr>
                    <tr>
                        <td colspan="6" class="text-end">
                            <strong>К начислению:
                                <?= number_format($totalEarnings + $managerOklad, 0, '.', ' ') . ' KZT'; ?></strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script>
        function printReport() {
            window.print();
        }
    </script>
</body>

</html>