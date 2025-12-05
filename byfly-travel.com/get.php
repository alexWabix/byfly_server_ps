<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/byfly-travel.com/get_agent_report.php');

// Получаем ID пользователя из GET-параметра
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Получаем данные о кэшбэке
$cashBackData = calculateAgentCashback($userId);

// Если передали неверный ID или произошла ошибка
if (!$userId || !$cashBackData['type']) {
    $errorMessage = $cashBackData['msg'] ?? 'Неверный ID пользователя';
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчет по кэшбэку | ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border: none;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }

        .total-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .period-card {
            border-left: 4px solid #4facfe;
        }

        .agent-card {
            transition: all 0.3s ease;
        }

        .agent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .badge-silver {
            background-color: #c0c0c0;
            color: #333;
        }

        .badge-gold {
            background-color: #ffd700;
            color: #333;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }

        .cashback-progress {
            height: 10px;
            border-radius: 5px;
        }

        .agent-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
        }

        .tab-content {
            padding: 20px 0;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #4facfe;
            font-weight: 600;
        }

        .timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #4facfe;
            transform: translateX(-50%);
        }

        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .timeline-content {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }

        .sale-badge {
            font-size: 0.75rem;
        }

        .cashback-details {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .cashback-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .cashback-label {
            font-weight: 500;
        }

        .cashback-value {
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1.5rem 0;
            }

            .display-4 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Отчет по кэшбэку</h1>
            <p class="lead">Детализация вознаграждений за привлечение агентов</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger text-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
            <?php $data = $cashBackData['data']; ?>

            <!-- Общая статистика -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card total-card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-cash-coin me-2"></i>Общий кэшбэк</h5>
                            <h2 class="display-5 fw-bold"><?php echo number_format($data['total_cashback'], 0, '.', ' '); ?>
                                ₸</h2>
                            <p class="mb-0">доступно к выплате</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Агенты в команде</h5>
                            <h2 class="display-5 fw-bold"><?php echo $data['total_agents']; ?></h2>
                            <div class="d-flex justify-content-around mt-3">
                                <div>
                                    <span
                                        class="badge bg-primary"><?php echo $data['periods']['2025-05-09_to_2025-06-16']['count']; ?></span>
                                    <small class="d-block">9 мая - 16 июня</small>
                                </div>
                                <div>
                                    <span
                                        class="badge bg-success"><?php echo $data['periods']['after_2025-06-16']['count']; ?></span>
                                    <small class="d-block">После 16 июня</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Табы с периодами -->
            <ul class="nav nav-tabs" id="periodTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current"
                        type="button" role="tab">
                        После 16 июня (<?php echo $data['periods']['after_2025-06-16']['count']; ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="previous-tab" data-bs-toggle="tab" data-bs-target="#previous" type="button"
                        role="tab">
                        9 мая - 16 июня (<?php echo $data['periods']['2025-05-09_to_2025-06-16']['count']; ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="old-tab" data-bs-toggle="tab" data-bs-target="#old" type="button"
                        role="tab">
                        До 9 мая (<?php echo $data['periods']['before_2025-05-09']['count']; ?>)
                    </button>
                </li>
            </ul>

            <!-- Содержимое табов -->
            <div class="tab-content" id="periodTabsContent">
                <!-- Текущий период (после 16 июня) -->
                <div class="tab-pane fade show active" id="current" role="tabpanel">
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card period-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Правила начисления</span>
                                    <span class="badge bg-success">Актуально</span>
                                </div>
                                <div class="card-body">
                                    <p>Для агентов, зарегистрированных <strong>после 16 июня 2025</strong>:</p>
                                    <ul>
                                        <li>10% от суммы обучения после сдачи экзамена (≥92 баллов)</li>
                                        <li>10% от суммы обучения после первой продажи тура</li>
                                        <li>Кэшбэк начисляется от фактически оплаченной суммы (учет рассрочки)</li>
                                        <li>Учитывается уже полученный кэшбэк</li>
                                    </ul>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Общий кэшбэк по этому периоду:
                                        <strong><?php echo number_format($data['periods']['after_2025-06-16']['cashback'], 0, '.', ' '); ?>
                                            ₸</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php foreach ($data['periods']['after_2025-06-16']['agents'] as $agent): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card agent-card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="agent-avatar me-3">
                                                <?php echo mb_substr($agent['name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo $agent['name']; ?></h6>
                                                <small class="text-muted"><?php echo $agent['phone']; ?></small>
                                            </div>
                                        </div>
                                        <span
                                            class="badge <?php echo $agent['tarif_id'] == 1 ? 'badge-silver' : 'badge-gold'; ?> status-badge">
                                            <?php echo $agent['tarif']; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Оплачено за обучение</small>
                                            <h6 class="mb-0"><?php echo number_format($agent['total_paid'], 0, '.', ' '); ?> ₸
                                            </h6>
                                            <?php if ($agent['is_installment']): ?>
                                                <small class="text-muted">(в рассрочку)</small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="cashback-details mb-3">
                                            <div class="cashback-row">
                                                <span class="cashback-label">Кэшбэк за экзамен:</span>
                                                <span
                                                    class="cashback-value text-success"><?php echo number_format($agent['cashback_details']['exam_cashback'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                            <div class="cashback-row">
                                                <span class="cashback-label">Кэшбэк за продажи:</span>
                                                <span
                                                    class="cashback-value text-success"><?php echo number_format($agent['cashback_details']['sale_cashback'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                            <div class="cashback-row">
                                                <span class="cashback-label">Уже получено:</span>
                                                <span
                                                    class="cashback-value text-primary"><?php echo number_format($agent['cashback_received'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                            <div class="cashback-row">
                                                <span class="cashback-label">Доступно к выплате:</span>
                                                <span
                                                    class="cashback-value text-success fw-bold"><?php echo number_format($agent['cashback_available'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Продажи туров</small>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>Всего продаж:</span>
                                                <span
                                                    class="badge bg-primary sale-badge"><?php echo $agent['sold_tours_count']; ?>
                                                    туров</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span>Сумма продаж:</span>
                                                <span
                                                    class="badge bg-success sale-badge"><?php echo number_format($agent['total_sales_amount'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                        </div>

                                        <div class="timeline mb-3">
                                            <?php if ($agent['exam_passed']): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-date">
                                                        <?php echo date('d.m.Y', strtotime($agent['exam_date'])); ?>
                                                    </div>
                                                    <div class="timeline-content">
                                                        Сдал экзамен (<?php echo $agent['astestation_bal']; ?> баллов)
                                                        <?php if ($agent['cashback_details']['exam_paid']): ?>
                                                            <span class="badge bg-success float-end">Кэшбэк выплачен</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning float-end">Ожидает выплаты</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($agent['has_sold_tour']): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-date">
                                                        <?php echo date('d.m.Y', strtotime($agent['first_sale_date'])); ?>
                                                    </div>
                                                    <div class="timeline-content">
                                                        Первая продажа (<?php echo $agent['sold_tours_count']; ?> туров)
                                                        <?php if ($agent['cashback_details']['sale_paid']): ?>
                                                            <span class="badge bg-success float-end">Кэшбэк выплачен</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning float-end">Ожидает выплаты</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Предыдущий период (9 мая - 16 июня) -->
                <div class="tab-pane fade" id="previous" role="tabpanel">
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card period-card">
                                <div class="card-header">
                                    Правила начисления
                                </div>
                                <div class="card-body">
                                    <p>Для агентов, зарегистрированных <strong>с 9 мая по 16 июня 2025</strong>:</p>
                                    <ul>
                                        <li>25% от суммы обучения после регистрации</li>
                                        <li>Кэшбэк начисляется от фактически оплаченной суммы (учет рассрочки)</li>
                                        <li>Учитывается уже полученный кэшбэк</li>
                                    </ul>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Общий кэшбэк по этому периоду:
                                        <strong><?php echo number_format($data['periods']['2025-05-09_to_2025-06-16']['cashback'], 0, '.', ' '); ?>
                                            ₸</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php foreach ($data['periods']['2025-05-09_to_2025-06-16']['agents'] as $agent): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card agent-card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="agent-avatar me-3">
                                                <?php echo mb_substr($agent['name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo $agent['name']; ?></h6>
                                                <small class="text-muted"><?php echo $agent['phone']; ?></small>
                                            </div>
                                        </div>
                                        <span
                                            class="badge <?php echo $agent['tarif_id'] == 1 ? 'badge-silver' : 'badge-gold'; ?> status-badge">
                                            <?php echo $agent['tarif']; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Оплачено за обучение</small>
                                            <h6 class="mb-0"><?php echo number_format($agent['total_paid'], 0, '.', ' '); ?> ₸
                                            </h6>
                                            <?php if ($agent['is_installment']): ?>
                                                <small class="text-muted">(в рассрочку)</small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="cashback-details mb-3">
                                            <div class="cashback-row">
                                                <span class="cashback-label">Общий кэшбэк (25%):</span>
                                                <span
                                                    class="cashback-value text-success"><?php echo number_format($agent['total_paid'] * 0.25, 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                            <div class="cashback-row">
                                                <span class="cashback-label">Уже получено:</span>
                                                <span
                                                    class="cashback-value text-primary"><?php echo number_format($agent['cashback_received'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                            <div class="cashback-row">
                                                <span class="cashback-label">Доступно к выплате:</span>
                                                <span
                                                    class="cashback-value text-success fw-bold"><?php echo number_format($agent['cashback_available'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Продажи туров</small>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>Всего продаж:</span>
                                                <span
                                                    class="badge bg-primary sale-badge"><?php echo $agent['sold_tours_count']; ?>
                                                    туров</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span>Сумма продаж:</span>
                                                <span
                                                    class="badge bg-success sale-badge"><?php echo number_format($agent['total_sales_amount'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                        </div>

                                        <div class="timeline mb-3">
                                            <div class="timeline-item">
                                                <div class="timeline-date">
                                                    <?php echo date('d.m.Y', strtotime($agent['date_validate_agent'])); ?>
                                                </div>
                                                <div class="timeline-content">
                                                    Регистрация агента
                                                    <?php if ($agent['cashback_received'] > 0): ?>
                                                        <span class="badge bg-success float-end">Кэшбэк выплачен</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning float-end">Ожидает выплаты</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if ($agent['has_sold_tour']): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-date">
                                                        <?php echo date('d.m.Y', strtotime($agent['first_sale_date'])); ?>
                                                    </div>
                                                    <div class="timeline-content">
                                                        Первая продажа (<?php echo $agent['sold_tours_count']; ?> туров)
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Старый период (до 9 мая) -->
                <div class="tab-pane fade" id="old" role="tabpanel">
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card period-card">
                                <div class="card-header">
                                    Правила начисления
                                </div>
                                <div class="card-body">
                                    <p>Для агентов, зарегистрированных <strong>до 9 мая 2025</strong>:</p>
                                    <ul>
                                        <li>Кэшбэк не начисляется</li>
                                        <li>Действовали предыдущие условия программы</li>
                                    </ul>
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Для этих агентов кэшбэк уже был начислен по старым правилам
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php foreach ($data['periods']['before_2025-05-09']['agents'] as $agent): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card agent-card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="agent-avatar me-3">
                                                <?php echo mb_substr($agent['name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo $agent['name']; ?></h6>
                                                <small class="text-muted"><?php echo $agent['phone']; ?></small>
                                            </div>
                                        </div>
                                        <span
                                            class="badge <?php echo $agent['tarif_id'] == 1 ? 'badge-silver' : 'badge-gold'; ?> status-badge">
                                            <?php echo $agent['tarif']; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Оплачено за обучение</small>
                                            <h6 class="mb-0"><?php echo number_format($agent['total_paid'], 0, '.', ' '); ?> ₸
                                            </h6>
                                        </div>

                                        <div class="cashback-details mb-3">
                                            <div class="cashback-row">
                                                <span class="cashback-label">Уже получено:</span>
                                                <span
                                                    class="cashback-value text-primary"><?php echo number_format($agent['cashback_received'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Продажи туров</small>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>Всего продаж:</span>
                                                <span
                                                    class="badge bg-primary sale-badge"><?php echo $agent['sold_tours_count']; ?>
                                                    туров</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span>Сумма продаж:</span>
                                                <span
                                                    class="badge bg-success sale-badge"><?php echo number_format($agent['total_sales_amount'], 0, '.', ' '); ?>
                                                    ₸</span>
                                            </div>
                                        </div>

                                        <div class="alert alert-secondary text-center">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            Кэшбэк по старым правилам
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 text-muted">ByFly Travel &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Активация табов
        var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                // Прокрутка к началу табов после переключения
                document.getElementById('periodTabs').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>

</html>