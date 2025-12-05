<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/byfly-travel.com/get_agent_report.php');

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($userId <= 0) {
    die('<div class="alert alert-danger">Не указан ID пользователя</div>');
}


$incomeData = calculateAgentIncome($userId);

if (!isset($incomeData['type'])) {
    die('<div class="alert alert-danger">Некорректный формат данных от системы</div>');
}

if (!$incomeData['type']) {
    die('<div class="alert alert-danger">' . $incomeData['msg'] . '</div>');
}

$data = $incomeData['data'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доход агента | ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px 12px 0 0 !important;
            padding: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .stat-card {
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card .icon.income {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stat-card .icon.agent {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .stat-card .icon.client {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning-color);
        }

        .badge-line {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 500;
            padding: 0.35rem 0.75rem;
        }

        .badge-x2 {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
            font-weight: 500;
            padding: 0.35rem 0.75rem;
        }

        .table th {
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
        }

        .agent-name {
            font-weight: 500;
            margin-bottom: 2px;
        }

        .agent-phone {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .status-badge {
            padding: 0.35rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 6px;
        }

        .status-available {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .status-unavailable {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .amount {
            font-weight: 600;
        }

        .tour-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .badge-nakrutka {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger-color);
        }

        .section-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .tour-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .tour-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .tour-info-label {
            color: #6c757d;
            font-weight: 500;
        }

        .tour-info-value {
            font-weight: 600;
        }

        .hotel-rating {
            display: inline-flex;
            align-items: center;
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning-color);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .hotel-rating i {
            margin-right: 0.3rem;
        }

        .accordion-button:not(.collapsed) {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .predoplata-badge {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .info-tooltip {
            cursor: pointer;
            color: var(--primary-color);
        }

        .badge-paid {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .badge-unpaid {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning-color);
        }

        .sale-type-badge {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <!-- Заголовок страницы -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-person-badge me-2"></i> Доход агента: <?= $data['user']['name'] ?>
                <?= $data['user']['famale'] ?>
            </h4>
            <div>
                <span class="badge-line me-2">
                    <i class="bi bi-people-fill me-1"></i> Доступно линий: <?= $data['settings']['available_lines'] ?>
                </span>
                <?php if ($data['settings']['is_x2']): ?>
                    <span class="badge-x2">
                        <i class="bi bi-lightning-fill me-1"></i> Доход x2
                    </span>
                <?php endif; ?>
                <a href="?user_id=<?= $userId ?>&update_payments=1" class="btn btn-sm btn-outline-primary ms-2">
                    <i class="bi bi-arrow-repeat"></i> Обновить выплаты
                </a>
            </div>
        </div>

        <!-- Статистика по доходам -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon income me-3">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Доход по линиям</h6>
                                <h4 class="mb-0"><?= number_format($data['totals']['line_income'], 0, ' ', ' ') ?> ₸
                                </h4>
                                <small class="text-muted">Выплачено:
                                    <?= number_format($data['totals']['paid_amount'], 0, ' ', ' ') ?> ₸</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-6 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon client me-3">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Оплачено клиентами</h6>
                                <h4 class="mb-0"><?= number_format($data['totals']['client_paid'], 0, ' ', ' ') ?> ₸
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Условия дохода по линиям -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="section-title mb-0">Условия дохода по линиям</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Линия</th>
                                <th class="text-end">Процент дохода</th>
                                <th>Требуется продаж</th>
                                <th class="text-end">Доход x2</th>
                                <th class="text-end">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="agent-avatar"><?= $i ?></div>
                                            <div>Линия <?= $i ?></div>
                                        </div>
                                    </td>
                                    <td class="text-end amount">
                                        <?= $data['settings']['line_percentages'][$i] ?>%
                                    </td>
                                    <td>
                                        <?php
                                        if ($i == 1)
                                            echo 'По умолчанию';
                                        elseif ($i == 2)
                                            echo $data['settings']['required_tours']['line_1'] . '+';
                                        elseif ($i == 3)
                                            echo $data['settings']['required_tours']['line_2'] . '+';
                                        elseif ($i == 4)
                                            echo $data['settings']['required_tours']['line_3'] . '+';
                                        else
                                            echo '10+ (x2 доход)';
                                        ?>
                                    </td>
                                    <td class="text-end amount">
                                        <?= $data['settings']['line_percentages'][$i] * 2 ?>%
                                    </td>
                                    <td class="text-end">
                                        <span
                                            class="status-badge <?= $i <= $data['settings']['available_lines'] ? 'status-available' : 'status-unavailable' ?>">
                                            <?= $i <= $data['settings']['available_lines'] ? 'Доступна' : 'Недоступна' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Собственные туры агента -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="section-title mb-0">Мои проданные туры</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['own_tours'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID тура</th>
                                    <th>Отель</th>
                                    <th class="text-end">Стоимость</th>
                                    <th class="text-end">Накрутка</th>
                                    <th class="text-end">Доход</th>
                                    <th class="text-end">Выплачено</th>
                                    <th class="text-end">Дата вылета</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['own_tours'] as $tour): ?>
                                    <tr class="<?= $tour['is_paid'] ? 'table-success' : 'table-warning' ?>">
                                        <td><?= $tour['id'] ?></td>
                                        <td>
                                            <?php if (isset($tour['hotel_info']['hotelname'])): ?>
                                                <?= $tour['hotel_info']['hotelname'] ?>
                                                <?php if (isset($tour['hotel_info']['hotelstars'])): ?>
                                                    <span class="ms-2"><?= str_repeat('★', $tour['hotel_info']['hotelstars']) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Не указано</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end amount"><?= number_format($tour['price'], 0, ' ', ' ') ?> ₸</td>
                                        <td class="text-end">
                                            <span class="tour-badge badge-nakrutka"><?= $tour['nakrutka'] ?>%</span>
                                        </td>
                                        <td class="text-end amount"><?= number_format($tour['agent_income'], 0, ' ', ' ') ?> ₸
                                        </td>
                                        <td class="text-end amount">
                                            <?php if ($tour['is_paid']): ?>
                                                <?= number_format($tour['agent_paid'], 0, ' ', ' ') ?> ₸
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">К выплате</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= date('d.m.Y', strtotime($tour['fly_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Итого:</th>
                                    <th class="text-end amount">
                                        <?= number_format(array_sum(array_column($data['own_tours'], 'price')), 0, ' ', ' ') ?>
                                        ₸
                                    </th>
                                    <th></th>
                                    <th class="text-end amount">
                                        <?= number_format($data['totals']['own_income'], 0, ' ', ' ') ?> ₸
                                    </th>
                                    <th class="text-end amount">
                                        <?= number_format($data['totals']['own_paid'], 0, ' ', ' ') ?> ₸
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-airplane"></i>
                        <h5>Нет проданных туров</h5>
                        <p class="text-muted">Вы еще не продали ни одного тура</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Структура агентов -->
        <?php if (!empty($data['structure'])): ?>
            <div class="accordion mb-4" id="agentsAccordion">
                <?php foreach ($data['structure'] as $level => $agents): ?>
                    <?php if (!empty($agents)): ?>
                        <div class="accordion-item border-0 mb-3">
                            <h2 class="accordion-header" id="heading<?= $level ?>">
                                <button class="accordion-button collapsed rounded-3" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?= $level ?>" aria-expanded="false"
                                    aria-controls="collapse<?= $level ?>">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary rounded-pill me-3">Линия <?= $level ?></span>
                                        <span class="me-2"><?= count($agents) ?> агентов</span>
                                        <span class="badge-line me-2">
                                            Доход: <?= $data['settings']['line_percentages'][$level] ?>%
                                        </span>
                                        <span
                                            class="amount"><?= number_format(array_sum(array_column($agents, 'total_line_income')), 0, ' ', ' ') ?>
                                            ₸</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $level ?>" class="accordion-collapse collapse"
                                aria-labelledby="heading<?= $level ?>" data-bs-parent="#agentsAccordion">
                                <div class="accordion-body p-0">
                                    <?php foreach ($agents as $agent): ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <div class="agent-avatar">
                                                            <?= substr($agent['name'], 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <div class="agent-name"><?= $agent['name'] ?></div>
                                                            <div class="agent-phone"><?= $agent['phone'] ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="amount">
                                                            <?= number_format($agent['total_line_income'], 0, ' ', ' ') ?> ₸
                                                        </div>
                                                        <small class="text-muted">Доход по линиям (<?= $agent['percentage'] ?>%)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted">Продажи:</span>
                                                            <span
                                                                class="amount"><?= number_format($agent['total_client_paid'], 0, ' ', ' ') ?>
                                                                ₸</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted">Доход агента:</span>
                                                            <span
                                                                class="amount"><?= number_format($agent['total_agent_income'], 0, ' ', ' ') ?>
                                                                ₸</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted">Выплачено агенту:</span>
                                                            <span
                                                                class="amount"><?= number_format($agent['total_agent_paid'], 0, ' ', ' ') ?>
                                                                ₸</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted">Выплачено по линиям:</span>
                                                            <span
                                                                class="amount"><?= number_format($agent['total_paid_amount'], 0, ' ', ' ') ?>
                                                                ₸</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if (!empty($agent['tours'])): ?>
                                                    <h6 class="section-title mb-3">Проданные туры</h6>
                                                    <div class="accordion" id="toursAccordion<?= $level ?>-<?= $agent['id'] ?>">
                                                        <?php foreach ($agent['tours'] as $index => $tour): ?>
                                                            <div class="accordion-item border-0 mb-2">
                                                                <h2 class="accordion-header"
                                                                    id="tourHeading<?= $level ?>-<?= $agent['id'] ?>-<?= $index ?>">
                                                                    <button class="accordion-button collapsed rounded-3" type="button"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#tourCollapse<?= $level ?>-<?= $agent['id'] ?>-<?= $index ?>"
                                                                        aria-expanded="false"
                                                                        aria-controls="tourCollapse<?= $level ?>-<?= $agent['id'] ?>-<?= $index ?>">
                                                                        <div class="d-flex align-items-center w-100">
                                                                            <div class="flex-grow-1">
                                                                                <div class="d-flex justify-content-between align-items-center">
                                                                                    <div>
                                                                                        <span class="me-2">Тур #<?= $tour['id'] ?></span>
                                                                                        <?php if (isset($tour['hotel_info']['hotelname'])): ?>
                                                                                            <span
                                                                                                class="text-muted"><?= $tour['hotel_info']['hotelname'] ?></span>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div>
                                                                                        <span
                                                                                            class="badge <?= $tour['is_paid'] ? 'badge-paid' : 'badge-unpaid' ?> me-2">
                                                                                            <?= $tour['is_paid'] ? 'Выплачено' : 'К выплате' ?>
                                                                                        </span>
                                                                                        <span
                                                                                            class="amount"><?= number_format($tour['price'], 0, ' ', ' ') ?>
                                                                                            ₸</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </button>
                                                                </h2>
                                                                <div id="tourCollapse<?= $level ?>-<?= $agent['id'] ?>-<?= $index ?>"
                                                                    class="accordion-collapse collapse"
                                                                    aria-labelledby="tourHeading<?= $level ?>-<?= $agent['id'] ?>-<?= $index ?>"
                                                                    data-bs-parent="#toursAccordion<?= $level ?>-<?= $agent['id'] ?>">
                                                                    <div class="accordion-body p-0">
                                                                        <div class="tour-info">
                                                                            <?php if (isset($tour['hotel_info']['hotelname'])): ?>
                                                                                <div class="tour-info-item">
                                                                                    <span class="tour-info-label">Отель:</span>
                                                                                    <span
                                                                                        class="tour-info-value"><?= $tour['hotel_info']['hotelname'] ?></span>
                                                                                </div>
                                                                                <?php if (isset($tour['hotel_info']['hotelstars'])): ?>
                                                                                    <div class="tour-info-item">
                                                                                        <span class="tour-info-label">Категория:</span>
                                                                                        <span
                                                                                            class="tour-info-value"><?= str_repeat('★', $tour['hotel_info']['hotelstars']) ?></span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                                <?php if (isset($tour['hotel_info']['countryname'])): ?>
                                                                                    <div class="tour-info-item">
                                                                                        <span class="tour-info-label">Страна:</span>
                                                                                        <span
                                                                                            class="tour-info-value"><?= $tour['hotel_info']['countryname'] ?></span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Дата вылета:</span>
                                                                                <span
                                                                                    class="tour-info-value"><?= date('d.m.Y', strtotime($tour['fly_date'])) ?></span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">ID тура:</span>
                                                                                <span class="tour-info-value"><?= $tour['tourId'] ?></span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Предоплата:</span>
                                                                                <span class="tour-info-value">
                                                                                    <span
                                                                                        class="predoplata-badge"><?= number_format($tour['predoplata'], 0, ' ', ' ') ?>
                                                                                        ₸</span>
                                                                                </span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Оплачено клиентом:</span>
                                                                                <span
                                                                                    class="tour-info-value"><?= number_format($tour['client_paid'], 0, ' ', ' ') ?>
                                                                                    ₸</span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Накрутка агента:</span>
                                                                                <span class="tour-info-value">
                                                                                    <span
                                                                                        class="tour-badge badge-nakrutka"><?= $tour['nakrutka'] ?>%</span>
                                                                                    <?= number_format($tour['agent_income'], 0, ' ', ' ') ?> ₸
                                                                                </span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Выплачено агенту:</span>
                                                                                <span class="tour-info-value">
                                                                                    <?= number_format($tour['agent_paid'], 0, ' ', ' ') ?> ₸
                                                                                </span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Доход по линии (2%):</span>
                                                                                <span
                                                                                    class="tour-info-value"><?= number_format($tour['line_income'], 0, ' ', ' ') ?>
                                                                                    ₸</span>
                                                                            </div>

                                                                            <div class="tour-info-item">
                                                                                <span class="tour-info-label">Статус выплаты по линии:</span>
                                                                                <span class="tour-info-value">
                                                                                    <span
                                                                                        class="badge <?= $tour['is_paid'] ? 'badge-paid' : 'badge-unpaid' ?>">
                                                                                        <?= $tour['is_paid'] ? 'Выплачено ' . number_format($tour['paid_amount'], 0, ' ', ' ') . ' ₸' : 'К выплате' ?>
                                                                                    </span>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="empty-state">
                                                        <i class="bi bi-airplane"></i>
                                                        <h6>Нет проданных туров</h6>
                                                        <p class="text-muted">Агент еще не продал ни одного тура</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-emoji-frown"></i>
                        <h5>Нет данных о структуре агентов</h5>
                        <p class="text-muted">Начните привлекать агентов, чтобы видеть здесь статистику</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Инициализация подсказок
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>