<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Включим отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Дата начала расчета статистики
$calculationStartDate = '2023-03-10'; // Укажите нужную дату начала расчетов

// Дата смены оплаты
$paymentChangeDate = strtotime('2025-03-10');

// Исключаемые потоки
$excludedGroups = [49, 45, 29];

// Массивы для хранения данных
$coachEarnings = []; // Общая статистика по преподавателям
$groupStats = [];    // Статистика по потокам
$allStudents = [];   // Все уникальные студенты

// Получаем всех студентов (платных и бесплатных)
$studentsQuery = $db->query("
    SELECT u.id as student_id, u.name, u.famale, u.surname, u.phone, u.astestation_bal, u.grouped, u.priced_coach,
           gc.id as group_id, gc.name_grouped_ru, gc.date_validation,
           gc.coach_id_1, gc.coach_id_2, gc.coach_id_3,
           gc.coach_id_4, gc.coach_id_5, gc.coach_id_6
    FROM users u
    JOIN grouped_coach gc ON u.grouped = gc.id
    WHERE u.grouped > '0'
    AND gc.date_validation >= '$calculationStartDate'
    AND gc.date_validation < NOW()
    ORDER BY gc.date_validation DESC
");

if (!$studentsQuery) {
    die("Ошибка запроса студентов: " . $db->error);
}

// Обрабатываем каждого студента
while ($student = $studentsQuery->fetch_assoc()) {
    $groupId = $student['group_id'];

    // Пропускаем исключенные потоки
    if (in_array($groupId, $excludedGroups)) {
        continue;
    }

    $studentId = $student['student_id'];
    $validationDate = strtotime($student['date_validation']);

    // Определяем тип студента
    $isPaid = $student['priced_coach'] > 0;
    $passedExam = $student['astestation_bal'] >= 92;

    // Сохраняем уникального студента
    if (!isset($allStudents[$studentId])) {
        $allStudents[$studentId] = [
            'name' => $student['name'],
            'group_id' => $groupId,
            'is_paid' => $isPaid,
            'passed_exam' => $passedExam
        ];
    }

    // Инициализируем данные потока, если еще не сделали этого
    if (!isset($groupStats[$groupId])) {
        $groupStats[$groupId] = [
            'name' => $student['name_grouped_ru'],
            'date' => $student['date_validation'],
            'payment_type' => ($validationDate < $paymentChangeDate) ? '20 000 ₸' : '60 000 ₸',
            'students' => [
                'total' => 0,
                'paid' => ['count' => 0, 'passed' => 0, 'failed' => 0],
                'free' => ['count' => 0, 'passed' => 0, 'failed' => 0]
            ],
            'students_list' => [],
            'coaches' => [],
            'days' => [
                1 => ['coach_id' => $student['coach_id_1']],
                2 => ['coach_id' => $student['coach_id_2']],
                3 => ['coach_id' => $student['coach_id_3']],
                4 => ['coach_id' => $student['coach_id_4']],
                5 => ['coach_id' => $student['coach_id_5']],
                6 => ['coach_id' => $student['coach_id_6']],
            ],
            'total_payment' => 0
        ];

        // Инициализируем данные по дням
        foreach ($groupStats[$groupId]['days'] as $day => &$dayData) {
            $dayData['earnings'] = 0;
            $dayData['students'] = 0;
        }
        unset($dayData);

        // Загружаем информацию о преподавателях
        foreach ($groupStats[$groupId]['days'] as $day => $dayData) {
            $coachId = $dayData['coach_id'];
            if ($coachId && !isset($groupStats[$groupId]['coaches'][$coachId])) {
                $coachQuery = $db->query("SELECT id, name_famale FROM coach WHERE id = '$coachId'");
                if ($coachQuery && $coachQuery->num_rows > 0) {
                    $coach = $coachQuery->fetch_assoc();
                    $groupStats[$groupId]['coaches'][$coachId] = [
                        'name' => $coach['name_famale'],
                        'total_earnings' => 0,
                        'students_count' => 0,
                        'days_taught' => 0
                    ];
                }
            }
        }
    }

    // Учет студентов в потоке
    $groupStats[$groupId]['students']['total']++;
    $groupStats[$groupId]['students_list'][$studentId] = [
        'name' => $student['famale'] . ' ' . $student['name'] . ' ' . $student['surname'] . ' (' . $student['phone'] . ')',
        'is_paid' => $isPaid,
        'passed_exam' => $passedExam
    ];

    if ($isPaid) {
        $groupStats[$groupId]['students']['paid']['count']++;
        if ($passedExam) {
            $groupStats[$groupId]['students']['paid']['passed']++;
        } else {
            $groupStats[$groupId]['students']['paid']['failed']++;
        }
    } else {
        $groupStats[$groupId]['students']['free']['count']++;
        if ($passedExam) {
            $groupStats[$groupId]['students']['free']['passed']++;
        } else {
            $groupStats[$groupId]['students']['free']['failed']++;
        }
    }

    // Если студент платный и сдал экзамен - распределяем оплату
    if ($isPaid && $passedExam) {
        $paymentPerStudent = ($validationDate < $paymentChangeDate) ? 20000 : 60000;
        $paymentPerDay = $paymentPerStudent / 6;
        $groupStats[$groupId]['total_payment'] += $paymentPerStudent;

        foreach ($groupStats[$groupId]['days'] as $day => &$dayData) {
            $coachId = $dayData['coach_id'];
            if (!$coachId) {
                continue;
            }

            $dayData['earnings'] += $paymentPerDay;
            $dayData['students']++;

            // Убедимся, что преподаватель существует
            if (isset($groupStats[$groupId]['coaches'][$coachId])) {
                // Обновляем статистику преподавателя в потоке
                $groupStats[$groupId]['coaches'][$coachId]['total_earnings'] += $paymentPerDay;
                $groupStats[$groupId]['coaches'][$coachId]['students_count']++;

                // Пересчитываем количество дней преподавания
                $daysTaught = 0;
                foreach ($groupStats[$groupId]['days'] as $d) {
                    if ($d['coach_id'] == $coachId) {
                        $daysTaught++;
                    }
                }
                $groupStats[$groupId]['coaches'][$coachId]['days_taught'] = $daysTaught;

                // Обновляем общую статистику по преподавателю
                if (!isset($coachEarnings[$coachId])) {
                    $coachEarnings[$coachId] = [
                        'name' => $groupStats[$groupId]['coaches'][$coachId]['name'],
                        'total_earnings' => 0,
                        'students_count' => 0,
                        'groups_count' => 0
                    ];
                }

                $coachEarnings[$coachId]['total_earnings'] += $paymentPerDay;
                $coachEarnings[$coachId]['students_count']++;
                $coachEarnings[$coachId]['groups_count'] = count(array_filter(
                    $groupStats,
                    function ($g) use ($coachId) {
                        return isset($g['coaches'][$coachId]);
                    }
                ));
            }
        }
        unset($dayData);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика заработка преподавателей</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }

        .coach-badge {
            font-size: 0.9rem;
            margin-right: 5px;
        }

        .payment-badge {
            font-size: 1rem;
            margin-left: 10px;
        }

        .day-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .coach-name {
            font-weight: 600;
            color: #0d6efd;
        }

        .student-paid {
            color: #198754;
        }

        .student-free {
            color: #6c757d;
        }

        .student-passed {
            font-weight: bold;
        }

        .student-failed {
            text-decoration: line-through;
        }

        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
        }

        .day-6 {
            background-color: #f8f9fa;
            border-left: 4px solid #fd7e14;
        }

        .calculation-info {
            background-color: #e2e3e5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="calculation-info">
            <h4>Параметры расчета</h4>
            <p><strong>Дата начала расчета:</strong> <?= $calculationStartDate ?></p>
            <p><strong>Дата смены оплаты:</strong> 10.03.2025 (до этой даты - 20 000 ₸, после - 60 000 ₸)</p>
            <p><strong>Исключенные потоки:</strong> ID 49, 45, 29</p>
        </div>

        <h1 class="text-center mb-4">Статистика заработка преподавателей</h1>

        <!-- Общая статистика -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Общие результаты</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Всего потоков</h5>
                                <p class="display-6"><?= count($groupStats) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Уникальных слушателей</h5>
                                <p class="display-6"><?= count($allStudents) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Платных студентов</h5>
                                <p class="display-6">
                                    <?= array_reduce($groupStats, function ($carry, $group) {
                                        return $carry + $group['students']['paid']['count'];
                                    }, 0) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Общая сумма выплат</h5>
                                <p class="display-6">
                                    <?= number_format(array_reduce($groupStats, function ($carry, $group) {
                                        return $carry + $group['total_payment'];
                                    }, 0), 0, '.', ' ') ?> ₸
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mt-4 mb-3">Преподаватели</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Преподаватель</th>
                                <th>Потоков</th>
                                <th>Студентов</th>
                                <th>Заработано</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            foreach ($coachEarnings as $coachId => $coach): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($coach['name']) ?></td>
                                    <td><?= $coach['groups_count'] ?></td>
                                    <td><?= $coach['students_count'] ?></td>
                                    <td><?= number_format($coach['total_earnings'], 0, '.', ' ') ?> ₸</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Детализация по потокам -->
        <h2 class="text-center mb-4">Детализация по потокам</h2>

        <?php foreach ($groupStats as $groupId => $group): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Поток: <?= htmlspecialchars($group['name']) ?></h3>
                        <div>
                            <span class="badge bg-light text-dark"><?= date('d.m.Y', strtotime($group['date'])) ?></span>
                            <span class="badge bg-warning text-dark payment-badge">
                                <?= $group['payment_type'] ?> за студента
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Всего слушателей</h5>
                                    <p class="display-6"><?= $group['students']['total'] ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Платных студентов</h5>
                                    <p class="display-6"><?= $group['students']['paid']['count'] ?></p>
                                    <small>
                                        Сдали: <?= $group['students']['paid']['passed'] ?>,
                                        Не сдали: <?= $group['students']['paid']['failed'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Бесплатных</h5>
                                    <p class="display-6"><?= $group['students']['free']['count'] ?></p>
                                    <small>
                                        Сдали: <?= $group['students']['free']['passed'] ?>,
                                        Не сдали: <?= $group['students']['free']['failed'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Общая сумма</h5>
                                    <p class="display-6"><?= number_format($group['total_payment'], 0, '.', ' ') ?> ₸</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Список студентов в аккордеоне -->
                    <div class="accordion mb-4" id="studentsAccordion<?= $groupId ?>">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseStudents<?= $groupId ?>" aria-expanded="false">
                                    Список студентов (<?= count($group['students_list']) ?>)
                                </button>
                            </h2>
                            <div id="collapseStudents<?= $groupId ?>" class="accordion-collapse collapse"
                                data-bs-parent="#studentsAccordion<?= $groupId ?>">
                                <div class="accordion-body">
                                    <div class="row">
                                        <?php foreach ($group['students_list'] as $studentId => $student): ?>
                                            <div class="col-md-6 mb-2">
                                                <span class="<?= $student['is_paid'] ? 'student-paid' : 'student-free' ?> 
                                               <?= $student['passed_exam'] ? 'student-passed' : 'student-failed' ?>">
                                                    <?= htmlspecialchars($student['name']) ?>
                                                    <?php if ($student['is_paid']): ?>
                                                        <span class="badge bg-success">Платный</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Бесплатный</span>
                                                    <?php endif; ?>
                                                    <?php if ($student['passed_exam']): ?>
                                                        <span class="badge bg-primary">Сдал</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Не сдал</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="mb-3">Распределение по дням обучения</h4>
                    <div class="row">
                        <?php foreach ($group['days'] as $day => $dayData): ?>
                            <?php if ($dayData['coach_id']): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="day-card <?= $day == 6 ? 'day-6' : '' ?>">
                                        <h5>День <?= $day ?></h5>
                                        <?php if (isset($group['coaches'][$dayData['coach_id']])): ?>
                                            <p class="coach-name">
                                                <?= htmlspecialchars($group['coaches'][$dayData['coach_id']]['name']) ?>
                                            </p>
                                            <p>Студентов: <?= $dayData['students'] ?></p>
                                            <p>Заработано: <?= number_format($dayData['earnings'], 0, '.', ' ') ?> ₸</p>
                                            <?php if ($dayData['students'] > 0): ?>
                                                <p>(<?= number_format($dayData['earnings'] / $dayData['students'], 0, '.', ' ') ?> ₸ за
                                                    студента)</p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-danger">Преподаватель (ID: <?= $dayData['coach_id'] ?>) не найден</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <h4 class="mt-4 mb-3">Итоги по преподавателям</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Преподаватель</th>
                                    <th>Дней преподавал</th>
                                    <th>Студентов</th>
                                    <th>Заработано</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                foreach ($group['coaches'] as $coachId => $coach): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($coach['name']) ?></td>
                                        <td><?= $coach['days_taught'] ?></td>
                                        <td><?= $coach['students_count'] ?></td>
                                        <td><?= number_format($coach['total_earnings'], 0, '.', ' ') ?> ₸</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>