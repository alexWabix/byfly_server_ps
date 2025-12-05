<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/byfly-travel.com/get_agent_report.php');

// Получаем ID пользователя из GET параметра
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($userId <= 0) {
    die('<div class="alert alert-danger">Не указан ID пользователя</div>');
}

// Получаем данные о доходах
$incomeData = calculateTrainingIncome($userId);

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
    <title>Доход от продажи обучения | ByFly Travel</title>
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

        .stat-card .icon.paid {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .stat-card .icon.available {
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

        .installment-badge {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning-color);
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
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
    </style>
</head>

<body>
    <div class="container py-4">
        <!-- Заголовок страницы -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-graph-up me-2"></i> Доход от продажи обучения
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
                                <h6 class="text-muted mb-2">Общий доход</h6>
                                <h4 class="mb-0"><?= number_format($data['totals']['income'], 0, ' ', ' ') ?> ₸</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon available me-3">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Доступно к выплате</h6>
                                <h4 class="mb-0"><?= number_format($data['totals']['available_income'], 0, ' ', ' ') ?>
                                    ₸</h4>
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

        <!-- Структура агентов -->
        <?php if (!empty($data['structure'])): ?>
            <?php foreach ($data['structure'] as $level => $agents): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="section-title mb-0">Линия <?= $level ?></h5>
                            <div>
                                <span class="badge bg-primary rounded-pill"><?= count($agents) ?> агентов</span>
                                <span class="badge-line ms-2">
                                    Доход: <?= $data['settings']['line_percentages'][$level] ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($agents)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Агент</th>
                                            <th>Дата регистрации</th>
                                            <th class="text-end">Оплачено</th>
                                            <th class="text-end">Доход</th>
                                            <th class="text-end">Выплачено</th>
                                            <th class="text-end">Доступно</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($agents as $agent): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="agent-avatar">
                                                            <?= substr($agent['name'], 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <div class="agent-name"><?= $agent['name'] ?></div>
                                                            <div class="agent-phone"><?= $agent['phone'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= date('d.m.Y', strtotime($agent['registration_date'])) ?>
                                                </td>
                                                <td class="text-end amount">
                                                    <?= number_format($agent['paid_amount'], 0, ' ', ' ') ?> ₸
                                                    <?php if ($agent['is_installment']): ?>
                                                        <span class="installment-badge">рассрочка</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end amount">
                                                    <?= number_format($agent['income'], 0, ' ', ' ') ?> ₸
                                                </td>
                                                <td class="text-end amount">
                                                    <?= number_format($agent['paid_income'], 0, ' ', ' ') ?> ₸
                                                </td>
                                                <td class="text-end amount text-success">
                                                    <?= number_format($agent['available_income'], 0, ' ', ' ') ?> ₸
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h5>В этой линии пока нет агентов</h5>
                                <p class="text-muted">Привлекайте новых агентов, чтобы увеличить свой доход</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
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
</body>

</html>