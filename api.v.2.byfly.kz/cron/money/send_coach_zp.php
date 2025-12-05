<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Включим отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Получаем дату последнего начисления
$lastCalculationQuery = $db->query("SELECT last_calculation_date FROM salary_last_calculation12 ORDER BY id DESC LIMIT 1");
$lastCalculationDate = '2023-01-01'; // Значение по умолчанию

if ($lastCalculationQuery && $lastCalculationQuery->num_rows > 0) {
    $row = $lastCalculationQuery->fetch_assoc();
    $lastCalculationDate = $row['last_calculation_date'];
}

// Дата смены оплаты
$paymentChangeDate = strtotime('2025-03-10');

// Исключаемые потоки
$excludedGroups = [49, 45, 29];

// Массивы для хранения данных
$coachEarnings = []; // Общая статистика по преподавателям
$groupStats = [];    // Статистика по потокам
$allStudents = [];

// Получаем всех студентов (платных и бесплатных) с даты последнего начисления
$studentsQuery = $db->query("
    SELECT u.id as student_id, u.name, u.famale, u.surname, u.phone, u.astestation_bal, u.grouped, u.priced_coach,
           gc.id as group_id, gc.name_grouped_ru, gc.date_validation,
           gc.coach_id_1, gc.coach_id_2, gc.coach_id_3,
           gc.coach_id_4, gc.coach_id_5, gc.coach_id_6
    FROM users u
    JOIN grouped_coach gc ON u.grouped = gc.id
    WHERE u.grouped > '0'
    AND gc.date_validation >= '$lastCalculationDate'
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
    $passedExam = $student['astestation_bal'] > 92;

    // Формируем полное имя студента с телефоном
    $studentName = trim($student['famale'] . ' ' . $student['name'] . ' ' . $student['surname']) . ' (' . $student['phone'] . ')';

    // Сохраняем уникального студента
    if (!isset($allStudents[$studentId])) {
        $allStudents[$studentId] = [
            'name' => $studentName,
            'is_paid' => $isPaid,
            'passed_exam' => $passedExam,
            'groups' => []
        ];
    }

    // Добавляем информацию о группе для студента
    $allStudents[$studentId]['groups'][$groupId] = true;

    // Инициализируем данные потока, если еще не сделали этого
    if (!isset($groupStats[$groupId])) {
        $paymentPerStudent = ($validationDate < $paymentChangeDate) ? 20000 : 60000;

        $groupStats[$groupId] = [
            'name' => $student['name_grouped_ru'],
            'date' => $student['date_validation'],
            'payment_per_student' => $paymentPerStudent,
            'payment_type' => number_format($paymentPerStudent, 0, '.', ' ') . ' ₸',
            'students' => [
                'total' => 0,
                'paid' => ['count' => 0, 'passed' => 0, 'failed' => 0, 'list' => []],
                'free' => ['count' => 0, 'passed' => 0, 'failed' => 0, 'list' => []]
            ],
            'coaches' => [],
            'days' => [
                1 => ['coach_id' => $student['coach_id_1']],
                2 => ['coach_id' => $student['coach_id_2']],
                3 => ['coach_id' => $student['coach_id_3']],
                4 => ['coach_id' => $student['coach_id_4']],
                5 => ['coach_id' => $student['coach_id_5']],
                6 => ['coach_id' => $student['coach_id_6']],
            ],
            'total_payment' => 0,
            'unique_coaches' => [], // Для отслеживания уникальных преподавателей в потоке
            'student_payments' => [] // Для хранения платежей по студентам
        ];

        // Определяем уникальных преподавателей в потоке
        $uniqueCoaches = [];
        foreach ($groupStats[$groupId]['days'] as $day => $dayData) {
            $coachId = $dayData['coach_id'];
            if ($coachId && !in_array($coachId, $uniqueCoaches)) {
                $uniqueCoaches[] = $coachId;
            }
        }
        $groupStats[$groupId]['unique_coaches'] = $uniqueCoaches;

        // Инициализируем данные по дням
        foreach ($groupStats[$groupId]['days'] as $day => &$dayData) {
            $dayData['earnings'] = 0;
            $dayData['students'] = 0;
        }
        unset($dayData);

        // Загружаем информацию о преподавателях
        foreach ($uniqueCoaches as $coachId) {
            $coachQuery = $db->query("SELECT id, name_famale, phone FROM coach WHERE id = '$coachId'");
            if ($coachQuery && $coachQuery->num_rows > 0) {
                $coach = $coachQuery->fetch_assoc();
                $groupStats[$groupId]['coaches'][$coachId] = [
                    'name' => $coach['name_famale'],
                    'phone' => $coach['phone'],
                    'total_earnings' => 0,
                    'students_count' => 0,
                    'days_taught' => 0,
                    'is_single_coach' => (count($uniqueCoaches) == 1) // Флаг, что преподаватель единственный в потоке
                ];
            }
        }
    }

    // Учет студентов в потоке
    $groupStats[$groupId]['students']['total']++;

    if ($isPaid) {
        $groupStats[$groupId]['students']['paid']['count']++;
        if ($passedExam) {
            $groupStats[$groupId]['students']['paid']['passed']++;
            $groupStats[$groupId]['students']['paid']['list'][] = $studentName;
        } else {
            $groupStats[$groupId]['students']['paid']['failed']++;
        }
    } else {
        $groupStats[$groupId]['students']['free']['count']++;
        if ($passedExam) {
            $groupStats[$groupId]['students']['free']['passed']++;
            $groupStats[$groupId]['students']['free']['list'][] = $studentName;
        } else {
            $groupStats[$groupId]['students']['free']['failed']++;
        }
    }

    // Если студент платный и сдал экзамен - распределяем оплату
    if ($isPaid && $passedExam) {
        $paymentPerStudent = $groupStats[$groupId]['payment_per_student'];
        $paymentPerDay = $paymentPerStudent / 6;
        $groupStats[$groupId]['total_payment'] += $paymentPerStudent;

        // Инициализируем запись о платежах для студента
        if (!isset($groupStats[$groupId]['student_payments'][$studentId])) {
            $groupStats[$groupId]['student_payments'][$studentId] = [
                'name' => $studentName,
                'total' => 0,
                'days' => []
            ];
        }

        foreach ($groupStats[$groupId]['days'] as $day => &$dayData) {
            $coachId = $dayData['coach_id'];
            if (!$coachId) {
                continue;
            }

            $dayData['earnings'] += $paymentPerDay;
            $dayData['students']++;

            // Записываем платеж для студента
            $groupStats[$groupId]['student_payments'][$studentId]['total'] += $paymentPerDay;
            $groupStats[$groupId]['student_payments'][$studentId]['days'][$day] = [
                'coach_id' => $coachId,
                'amount' => $paymentPerDay
            ];

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
                        'phone' => $groupStats[$groupId]['coaches'][$coachId]['phone'],
                        'total_earnings' => 0,
                        'students_count' => 0,
                        'groups' => [],
                        'student_payments' => [], // Платежи по студентам
                        'id' => $coachId
                    ];
                }

                $coachEarnings[$coachId]['total_earnings'] += $paymentPerDay;
                $coachEarnings[$coachId]['students_count']++;

                // Добавляем информацию о платеже для преподавателя
                if (!isset($coachEarnings[$coachId]['student_payments'][$studentId])) {
                    $coachEarnings[$coachId]['student_payments'][$studentId] = [
                        'name' => $studentName,
                        'total' => 0,
                        'groups' => []
                    ];
                }
                $coachEarnings[$coachId]['student_payments'][$studentId]['total'] += $paymentPerDay;
                $coachEarnings[$coachId]['student_payments'][$studentId]['groups'][$groupId] = [
                    'name' => $groupStats[$groupId]['name'],
                    'amount' => $paymentPerDay
                ];

                // Добавляем информацию о группе
                if (!isset($coachEarnings[$coachId]['groups'][$groupId])) {
                    $coachEarnings[$coachId]['groups'][$groupId] = [
                        'name' => $groupStats[$groupId]['name'],
                        'date' => $groupStats[$groupId]['date'],
                        'payment_per_student' => $groupStats[$groupId]['payment_per_student'],
                        'earnings' => 0,
                        'students' => 0,
                        'is_single_coach' => $groupStats[$groupId]['coaches'][$coachId]['is_single_coach']
                    ];
                }
                $coachEarnings[$coachId]['groups'][$groupId]['earnings'] += $paymentPerDay;
                $coachEarnings[$coachId]['groups'][$groupId]['students']++;
            }
        }
        unset($dayData);
    }
}

// Формируем сообщение для администратора
$adminMessage = "📊 *Отчет по начислению зарплат преподавателям*\n\n";
$adminMessage .= "📅 *Период:* с " . $lastCalculationDate . " по " . date('Y-m-d') . "\n\n";
$adminMessage .= "🔹 *Всего потоков:* " . count($groupStats) . "\n";
$adminMessage .= "👨‍🎓 *Всего студентов:* " . count($allStudents) . "\n";
$adminMessage .= "💰 *Платных студентов:* " . array_reduce($groupStats, function ($carry, $group) {
    return $carry + $group['students']['paid']['count'];
}, 0) . "\n";
$adminMessage .= "💵 *Общая сумма выплат:* " . number_format(array_reduce($groupStats, function ($carry, $group) {
    return $carry + $group['total_payment'];
}, 0), 0, '.', ' ') . " ₸\n\n";

// Добавляем информацию по потокам
foreach ($groupStats as $groupId => $group) {
    $adminMessage .= "────────────────────────────────────\n";
    $adminMessage .= "📌 *Поток:* " . $group['name'] . "\n";
    $adminMessage .= "📅 Дата: " . date('d.m.Y', strtotime($group['date'])) . "\n";
    $adminMessage .= "💲 Оплата за студента: " . $group['payment_type'] . "\n\n";

    $adminMessage .= "👥 *Студентов всего:* " . $group['students']['total'] . "\n";
    $adminMessage .= "💰 *Платных:* " . $group['students']['paid']['count'] . "\n";
    $adminMessage .= "   ✅ Сдали: " . $group['students']['paid']['passed'] . "\n";
    $adminMessage .= "   ❌ Не сдали: " . $group['students']['paid']['failed'] . "\n";
    $adminMessage .= "🆓 *Бесплатных:* " . $group['students']['free']['count'] . "\n";
    $adminMessage .= "   ✅ Сдали: " . $group['students']['free']['passed'] . "\n";
    $adminMessage .= "   ❌ Не сдали: " . $group['students']['free']['failed'] . "\n\n";

    $adminMessage .= "💵 *Сумма выплат по потоку:* " . number_format($group['total_payment'], 0, '.', ' ') . " ₸\n\n";

    // Проверяем, есть ли в потоке только один преподаватель
    $singleCoach = count($group['unique_coaches']) == 1 ? $group['coaches'][$group['unique_coaches'][0]] : null;

    if ($singleCoach) {
        $adminMessage .= "👨‍🏫 *Преподаватель (вел все дни):* " . $singleCoach['name'] . "\n";
        $adminMessage .= "   💰 Сумма: " . number_format($singleCoach['total_earnings'], 0, '.', ' ') . " ₸\n";
    }

    // Список платных студентов, сдавших экзамен
    if (!empty($group['students']['paid']['list'])) {
        $adminMessage .= "\n📝 *Платные студенты (сдали экзамен):*\n";
        foreach ($group['students']['paid']['list'] as $student) {
            $adminMessage .= "- " . $student . "\n";
        }
    }

    // Список бесплатных студентов, сдавших экзамен
    if (!empty($group['students']['free']['list'])) {
        $adminMessage .= "\n📝 *Бесплатные студенты (сдали экзамен):*\n";
        foreach ($group['students']['free']['list'] as $student) {
            $adminMessage .= "- " . $student . "\n";
        }
    }

    // Информация по преподавателям (если не один преподаватель)
    if (!$singleCoach) {
        $adminMessage .= "\n👨‍🏫 *Распределение по преподавателям:*\n";
        foreach ($group['coaches'] as $coachId => $coach) {
            $adminMessage .= "- " . $coach['name'] . ":\n";
            $adminMessage .= "   💰 Сумма: " . number_format($coach['total_earnings'], 0, '.', ' ') . " ₸\n";
            $adminMessage .= "   👥 Студентов: " . $coach['students_count'] . "\n";
            $adminMessage .= "   📅 Дней: " . $coach['days_taught'] . "\n\n";
        }
    }

    $adminMessage .= "\n";
}

// Итоговая информация по преподавателям
$adminMessage .= "════════════════════════════════════\n";
$adminMessage .= "🎯 *Итоги по преподавателям:*\n\n";
foreach ($coachEarnings as $coachId => $coach) {
    $adminMessage .= "👨‍🏫 *" . $coach['name'] . "*\n";
    $adminMessage .= "💰 Всего начислено: " . number_format($coach['total_earnings'], 0, '.', ' ') . " ₸\n";
    $adminMessage .= "👥 Студентов: " . $coach['students_count'] . "\n";
    $adminMessage .= "📊 Потоков: " . count($coach['groups']) . "\n\n";

    // Детализация по студентам
    if (!empty($coach['student_payments'])) {
        $adminMessage .= "📝 *Студенты и начисления:*\n";
        foreach ($coach['student_payments'] as $studentId => $payment) {
            $adminMessage .= "- " . $payment['name'] . "\n";
            $adminMessage .= "  💰 Всего: " . number_format($payment['total'], 0, '.', ' ') . " ₸\n";

            // Детализация по потокам
            foreach ($payment['groups'] as $groupId => $group) {
                $adminMessage .= "  🔹 " . $group['name'] . ": " . number_format($group['amount'], 0, '.', ' ') . " ₸\n";
            }
            $adminMessage .= "\n";
        }
    }

    $adminMessage .= "────────────────────────────────────\n\n";
}

// Обновляем балансы преподавателей в базе данных
foreach ($coachEarnings as $coachId => $coach) {
    $db->query("UPDATE coach SET balance = balance + " . $coach['total_earnings'] . " WHERE id = " . $coachId);

    // Формируем персональное сообщение для преподавателя
    $coachMessage = "👋 *Уважаемый(ая) " . $coach['name'] . "*!\n\n";
    $coachMessage .= "💰 *Вам начислено:* " . number_format($coach['total_earnings'], 0, '.', ' ') . " ₸\n";
    $coachMessage .= "📅 *За период:* с " . $lastCalculationDate . " по " . date('Y-m-d') . "\n";
    $coachMessage .= "👥 *Всего студентов:* " . $coach['students_count'] . "\n";
    $coachMessage .= "📊 *Потоков:* " . count($coach['groups']) . "\n\n";

    $coachMessage .= "════════════════════════════════════\n";
    $coachMessage .= "📌 *Детализация по потокам:*\n\n";
    foreach ($coach['groups'] as $groupId => $group) {
        $coachMessage .= "🔹 *" . $group['name'] . "*\n";
        $coachMessage .= "📅 Дата: " . date('d.m.Y', strtotime($group['date'])) . "\n";
        $coachMessage .= "💰 Сумма: " . number_format($group['earnings'], 0, '.', ' ') . " ₸\n";
        $coachMessage .= "👥 Студентов: " . $group['students'] . "\n";
        $coachMessage .= "💲 Оплата за студента: " . number_format($group['payment_per_student'], 0, '.', ' ') . " ₸\n\n";

        // Для потоков, где преподаватель вел все дни
        if ($group['is_single_coach']) {
            $groupInfo = $groupStats[$groupId];

            $coachMessage .= "📝 *Все студенты потока:*\n";
            $coachMessage .= "💰 Платных: " . $groupInfo['students']['paid']['count'] . "\n";
            $coachMessage .= "   ✅ Сдали: " . $groupInfo['students']['paid']['passed'] . "\n";
            $coachMessage .= "   ❌ Не сдали: " . $groupInfo['students']['paid']['failed'] . "\n";
            $coachMessage .= "🆓 Бесплатных: " . $groupInfo['students']['free']['count'] . "\n";
            $coachMessage .= "   ✅ Сдали: " . $groupInfo['students']['free']['passed'] . "\n";
            $coachMessage .= "   ❌ Не сдали: " . $groupInfo['students']['free']['failed'] . "\n\n";

            // Список всех платных студентов
            if (!empty($groupInfo['students']['paid']['list'])) {
                $coachMessage .= "✅ *Платные студенты (сдали экзамен):*\n";
                foreach ($groupInfo['students']['paid']['list'] as $student) {
                    $coachMessage .= "- " . $student . "\n";
                }
                $coachMessage .= "\n";
            }

            // Список бесплатных студентов (сдавших)
            if (!empty($groupInfo['students']['free']['list'])) {
                $coachMessage .= "✅ *Бесплатные студенты (сдали экзамен):*\n";
                foreach ($groupInfo['students']['free']['list'] as $student) {
                    $coachMessage .= "- " . $student . "\n";
                }
                $coachMessage .= "\n";
            }
        }

        $coachMessage .= "────────────────────────────────────\n\n";
    }

    // Детализация по студентам и начислениям
    if (!empty($coach['student_payments'])) {
        $coachMessage .= "════════════════════════════════════\n";
        $coachMessage .= "📝 *Детализация по студентам:*\n\n";

        foreach ($coach['student_payments'] as $studentId => $payment) {
            $coachMessage .= "👤 *" . $payment['name'] . "*\n";
            $coachMessage .= "💰 Всего начислено: " . number_format($payment['total'], 0, '.', ' ') . " ₸\n";

            // Группируем платежи по потокам
            $groupPayments = [];
            foreach ($payment['groups'] as $groupId => $group) {
                if (!isset($groupPayments[$groupId])) {
                    $groupPayments[$groupId] = [
                        'name' => $group['name'],
                        'total' => 0
                    ];
                }
                $groupPayments[$groupId]['total'] += $group['amount'];
            }

            // Выводим информацию по потокам
            foreach ($groupPayments as $groupId => $group) {
                $coachMessage .= "🔹 " . $group['name'] . ": " . number_format($group['total'], 0, '.', ' ') . " ₸\n";
            }

            $coachMessage .= "\n";
        }
    }

    $coachMessage .= "\nСпасибо за вашу работу! 😊";

    // Отправляем уведомление преподавателю
    if (!empty($coach['phone'])) {
        sendWhatsapp($coach['phone'], $coachMessage);
    }
}
sendWhatsapp('77773700772', $adminMessage);


$currentDate = date('Y-m-d');
$db->query("INSERT INTO salary_last_calculation12 (last_calculation_date) VALUES ('$currentDate')");

echo "Начисления выполнены успешно. Последняя дата расчета: " . $currentDate;
?>