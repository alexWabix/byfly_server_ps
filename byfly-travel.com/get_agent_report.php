<?php
function calculateAgentCashback($userId)
{
    global $db;

    // Проверяем существование пользователя
    $userCheck = $db->query("SELECT id, user_status FROM users WHERE id = $userId");
    if ($userCheck->num_rows == 0) {
        return [
            'type' => false,
            'msg' => 'Пользователь не найден'
        ];
    }

    $user = $userCheck->fetch_assoc();

    // Проверяем, что пользователь является агентом
    if ($user['user_status'] == 'user') {
        return [
            'type' => false,
            'msg' => 'Кэшбэк начисляется только агентам'
        ];
    }

    // Получаем информацию о тарифах из базы данных
    $tariffs = [];
    $tariffsQuery = $db->query("SELECT id, name FROM tariffs");
    while ($tariff = $tariffsQuery->fetch_assoc()) {
        $tariffs[$tariff['id']] = $tariff['name'];
    }

    // Получаем всех агентов первой линии с полной информацией
    $agents = $db->query("SELECT 
        u.id, 
        u.name, 
        u.famale, 
        u.phone,
        u.user_status,
        u.is_active,
        u.blocked_to_time,
        u.date_validate_agent,
        u.date_registration,
        u.astestation_bal,
        u.tarif,
        u.is_installment,
        u.payment_1_amount,
        u.payment_2_amount,
        u.payment_3_amount,
        u.payment_4_amount,
        u.payment_5_amount,
        u.payment_6_amount,
        u.payment_1_cashback_1,
        u.payment_1_cashback_2,
        u.payment_2_cashback_1,
        u.payment_2_cashback_2,
        u.payment_3_cashback_1,
        u.payment_3_cashback_2,
        u.payment_4_cashback_1,
        u.payment_4_cashback_2,
        u.payment_5_cashback_1,
        u.payment_5_cashback_2,
        u.payment_6_cashback_1,
        u.payment_6_cashback_2,
        u.has_sold_tour,
        u.date_payment_couch,
        u.priced_coach,
        u.price_oute_in_couch_price_from_cashback,
        (SELECT COUNT(*) FROM order_tours WHERE (user_id = u.id OR saler_id = u.id) AND status_code IN (3,4) AND includesPrice > 0) as sold_tours_count,
        (SELECT MIN(date_create) FROM order_tours WHERE (user_id = u.id OR saler_id = u.id) AND status_code IN (3,4) AND includesPrice > 0) as first_sale_date,
        (SELECT SUM(price) FROM order_tours WHERE (user_id = u.id OR saler_id = u.id) AND status_code IN (3,4) AND includesPrice > 0) as total_sales_amount
    FROM users u 
    WHERE u.parent_user = $userId AND u.user_status = 'agent'");

    $result = [
        'total_cashback' => 0,
        'total_agents' => 0,
        'agents' => [],
        'periods' => [
            'before_2025-05-09' => [
                'count' => 0,
                'cashback' => 0,
                'agents' => []
            ],
            '2025-05-09_to_2025-06-16' => [
                'count' => 0,
                'cashback' => 0,
                'agents' => []
            ],
            'after_2025-06-16' => [
                'count' => 0,
                'cashback' => 0,
                'agents' => []
            ]
        ]
    ];

    while ($agent = $agents->fetch_assoc()) {
        // Определяем сумму оплаты за обучение
        if ($agent['is_installment'] == 1) {
            // Для рассрочки - сумма внесенных платежей
            $totalPaid = ($agent['payment_1_amount'] ?? 0) +
                ($agent['payment_2_amount'] ?? 0) +
                ($agent['payment_3_amount'] ?? 0) +
                ($agent['payment_4_amount'] ?? 0) +
                ($agent['payment_5_amount'] ?? 0) +
                ($agent['payment_6_amount'] ?? 0);
        } else {
            // Для полной оплаты - priced_coach
            $totalPaid = $agent['priced_coach'] ?? 0;
        }

        $agentData = [
            'id' => $agent['id'],
            'name' => $agent['name'] . ' ' . $agent['famale'],
            'phone' => $agent['phone'],
            'status' => $agent['is_active'] == 1 && ($agent['blocked_to_time'] == null || $agent['blocked_to_time'] < date('Y-m-d H:i:s')) ? 'active' : 'blocked',
            'tarif' => $tariffs[$agent['tarif']] ?? 'Unknown',
            'tarif_id' => $agent['tarif'],
            'exam_passed' => $agent['astestation_bal'] >= 92,
            'has_sold_tour' => $agent['sold_tours_count'] > 0,
            'sold_tours_count' => $agent['sold_tours_count'],
            'total_sales_amount' => $agent['total_sales_amount'] ?? 0,
            'first_sale_date' => $agent['first_sale_date'],
            'exam_date' => $agent['date_payment_couch'],
            'is_installment' => $agent['is_installment'] == 1,
            'total_paid' => $totalPaid,
            'cashback_available' => 0,
            'cashback_received' => $agent['price_oute_in_couch_price_from_cashback'] ?? 0,
            'cashback_details' => [
                'exam_cashback' => 0,
                'exam_paid' => false,
                'sale_cashback' => 0,
                'sale_paid' => false
            ],
            'period' => ''
        ];

        // Определяем период регистрации агента
        $registrationDate = new DateTime($agent['date_registration']);
        $period1 = new DateTime('2025-05-09 09:00:00');
        $period2 = new DateTime('2025-06-16 00:00:00');

        if ($registrationDate < $period1) {
            // До 9 мая 2025 - показываем сколько положено и сколько выплачено
            $agentData['period'] = 'before_2025-05-09';
            $cashback = $agentData['total_paid'] * 0.25;

            $agentData['cashback_details'] = [
                'total_cashback' => $cashback,
                'received_cashback' => $agentData['cashback_received'],
                'available_cashback' => max(0, $cashback - $agentData['cashback_received'])
            ];

            if ($agentData['status'] == 'active') {
                $result['periods']['before_2025-05-09']['cashback'] += $agentData['cashback_details']['available_cashback'];
                $result['total_cashback'] += $agentData['cashback_details']['available_cashback'];
            }

            $result['periods']['before_2025-05-09']['count']++;
            $result['periods']['before_2025-05-09']['agents'][] = $agentData;
        } elseif ($registrationDate < $period2) {
            // С 9 мая по 16 июня - 25% кэшбэк
            $agentData['period'] = '2025-05-09_to_2025-06-16';
            $cashback = $agentData['total_paid'] * 0.25;

            $agentData['cashback_details'] = [
                'total_cashback' => $cashback,
                'received_cashback' => $agentData['cashback_received'],
                'available_cashback' => max(0, $cashback - $agentData['cashback_received'])
            ];

            if ($agentData['status'] == 'active') {
                $agentData['cashback_available'] = $agentData['cashback_details']['available_cashback'];
                $result['periods']['2025-05-09_to_2025-06-16']['cashback'] += $agentData['cashback_available'];
                $result['total_cashback'] += $agentData['cashback_available'];
            }

            $result['periods']['2025-05-09_to_2025-06-16']['count']++;
            $result['periods']['2025-05-09_to_2025-06-16']['agents'][] = $agentData;
        } else {
            // После 16 июня - 10% после экзамена, 10% после первой продажи
            $agentData['period'] = 'after_2025-06-16';
            $cashback = 0;

            // Проверяем, не заблокирован ли агент
            if ($agentData['status'] == 'active') {
                // Первые 10% после сдачи экзамена
                if ($agentData['exam_passed']) {
                    $cashback1 = $agentData['total_paid'] * 0.1;
                    $agentData['cashback_details']['exam_cashback'] = $cashback1;

                    // Проверяем, не был ли уже начислен этот кэшбэк
                    $cashbackReceived1 = 0;
                    for ($i = 1; $i <= 6; $i++) {
                        $cashbackField = "payment_{$i}_cashback_1";
                        if ($agent[$cashbackField] == 1) {
                            $cashbackReceived1 = $agentData['total_paid'] * 0.1;
                            $agentData['cashback_details']['exam_paid'] = true;
                            break;
                        }
                    }

                    $cashback += max(0, $cashback1 - $cashbackReceived1);
                }

                // Вторые 10% после первой продажи
                if ($agentData['has_sold_tour']) {
                    $cashback2 = $agentData['total_paid'] * 0.1;
                    $agentData['cashback_details']['sale_cashback'] = $cashback2;

                    // Проверяем, не был ли уже начислен этот кэшбэк
                    $cashbackReceived2 = 0;
                    for ($i = 1; $i <= 6; $i++) {
                        $cashbackField = "payment_{$i}_cashback_2";
                        if ($agent[$cashbackField] == 1) {
                            $cashbackReceived2 = $agentData['total_paid'] * 0.1;
                            $agentData['cashback_details']['sale_paid'] = true;
                            break;
                        }
                    }

                    $cashback += max(0, $cashback2 - $cashbackReceived2);
                }

                $agentData['cashback_available'] = $cashback;
                $result['periods']['after_2025-06-16']['cashback'] += $cashback;
                $result['total_cashback'] += $cashback;
            }

            $result['periods']['after_2025-06-16']['count']++;
            $result['periods']['after_2025-06-16']['agents'][] = $agentData;
        }

        $result['agents'][] = $agentData;
        $result['total_agents']++;
    }

    return [
        'type' => true,
        'data' => $result
    ];
}


function calculateTrainingIncome($userId)
{
    global $db;

    // Инициализируем базовую структуру ответа
    $response = [
        'type' => false,
        'msg' => '',
        'data' => []
    ];

    try {
        // Получаем настройки системы
        $settingsResult = $db->query("SELECT 
            defoul_lines, 
            line_1_count_tours, 
            line_2_count_tours, 
            line_3_count_tours,
            percentage_line_1,
            percentage_line_2,
            percentage_line_3,
            percentage_line_4,
            percenage_line_5,
            percentage_x2_lne_1,
            percentage_x2_lne_2,
            percentage_x2_lne_3,
            percentage_x2_lne_4,
            percentage_x2_lne_5
        FROM app_settings LIMIT 1");

        if (!$settingsResult) {
            throw new Exception("Ошибка получения настроек системы: " . $db->error);
        }

        $settings = $settingsResult->fetch_assoc();
        if (!$settings) {
            throw new Exception("Настройки системы не найдены");
        }

        // Получаем информацию о пользователе
        $userResult = $db->query("SELECT 
            id, name, famale, phone, 
            (SELECT COUNT(*) FROM order_tours WHERE (user_id = id OR saler_id = id) AND status_code IN (3,4) AND includesPrice > 0) as sold_tours_count
        FROM users WHERE id = $userId");

        if (!$userResult) {
            throw new Exception("Ошибка получения данных пользователя: " . $db->error);
        }

        $user = $userResult->fetch_assoc();
        if (!$user) {
            throw new Exception("Пользователь с ID $userId не найден");
        }

        // Определяем сколько линий доступно пользователю
        $availableLines = $settings['defoul_lines'];
        if ($user['sold_tours_count'] >= $settings['line_3_count_tours']) {
            $availableLines = 5;
        } elseif ($user['sold_tours_count'] >= $settings['line_2_count_tours']) {
            $availableLines = 4;
        } elseif ($user['sold_tours_count'] >= $settings['line_1_count_tours']) {
            $availableLines = 3;
        }

        // Проверяем x2 доход
        $isX2 = $user['sold_tours_count'] >= 10;

        // Получаем проценты по линиям
        $linePercentages = [
            1 => $isX2 ? $settings['percentage_x2_lne_1'] : $settings['percentage_line_1'],
            2 => $isX2 ? $settings['percentage_x2_lne_2'] : $settings['percentage_line_2'],
            3 => $isX2 ? $settings['percentage_x2_lne_3'] : $settings['percentage_line_3'],
            4 => $isX2 ? $settings['percentage_x2_lne_4'] : $settings['percentage_line_4'],
            5 => $isX2 ? $settings['percentage_x2_lne_5'] : $settings['percenage_line_5']
        ];

        // Получаем всех агентов в структуре
        $structure = [];
        $levels = 5; // Максимальная глубина структуры

        for ($level = 1; $level <= $levels; $level++) {
            if ($level > $availableLines)
                break;

            if ($level == 1) {
                // Первая линия - прямые рефералы
                $query = "SELECT 
                    u.id, u.name, u.famale, u.phone, u.date_validate_agent,
                    u.is_installment, u.priced_coach,
                    u.payment_1_amount, u.payment_2_amount, u.payment_3_amount,
                    u.payment_4_amount, u.payment_5_amount, u.payment_6_amount,
                    u.price_oute_in_couch_price_from_lines,
                    (SELECT SUM(summ) FROM user_tranzactions WHERE user_id = u.id AND operation = 'coach') as total_paid
                FROM users u 
                WHERE u.parent_user = $userId AND u.user_status = 'agent'";
            } else {
                // Последующие линии - рекурсивно
                $parents = implode(',', array_column($structure[$level - 1], 'id'));
                if (empty($parents))
                    break;

                $query = "SELECT 
                    u.id, u.name, u.famale, u.phone, u.date_validate_agent,
                    u.is_installment, u.priced_coach,
                    u.payment_1_amount, u.payment_2_amount, u.payment_3_amount,
                    u.payment_4_amount, u.payment_5_amount, u.payment_6_amount,
                    u.price_oute_in_couch_price_from_lines,
                    (SELECT SUM(summ) FROM user_tranzactions WHERE user_id = u.id AND operation = 'coach') as total_paid
                FROM users u 
                WHERE u.parent_user IN ($parents) AND u.user_status = 'agent'";
            }

            $result = $db->query($query);
            if (!$result) {
                throw new Exception("Ошибка получения агентов линии $level: " . $db->error);
            }

            $structure[$level] = [];

            while ($agent = $result->fetch_assoc()) {
                // Рассчитываем сумму оплаты за обучение
                if ($agent['is_installment'] == 1) {
                    $paidAmount = ($agent['payment_1_amount'] ?? 0) +
                        ($agent['payment_2_amount'] ?? 0) +
                        ($agent['payment_3_amount'] ?? 0) +
                        ($agent['payment_4_amount'] ?? 0) +
                        ($agent['payment_5_amount'] ?? 0) +
                        ($agent['payment_6_amount'] ?? 0);
                } else {
                    $paidAmount = $agent['priced_coach'] ?? 0;
                }

                // Рассчитываем доход с этого агента
                $income = $paidAmount * ($linePercentages[$level] / 100);
                $paidIncome = $agent['price_oute_in_couch_price_from_lines'] ?? 0;
                $availableIncome = max(0, $income - $paidIncome);

                $structure[$level][] = [
                    'id' => $agent['id'],
                    'name' => $agent['name'] . ' ' . $agent['famale'],
                    'phone' => $agent['phone'],
                    'registration_date' => $agent['date_validate_agent'],
                    'is_installment' => $agent['is_installment'] == 1,
                    'paid_amount' => $paidAmount,
                    'income' => $income,
                    'paid_income' => $paidIncome,
                    'available_income' => $availableIncome,
                    'percentage' => $linePercentages[$level]
                ];
            }
        }

        // Считаем общие суммы
        $totalIncome = 0;
        $totalPaidIncome = 0;
        $totalAvailableIncome = 0;

        foreach ($structure as $level => $agents) {
            foreach ($agents as $agent) {
                $totalIncome += $agent['income'];
                $totalPaidIncome += $agent['paid_income'];
                $totalAvailableIncome += $agent['available_income'];
            }
        }

        // Формируем успешный ответ
        $response['type'] = true;
        $response['data'] = [
            'user' => $user,
            'settings' => [
                'available_lines' => $availableLines,
                'is_x2' => $isX2,
                'line_percentages' => $linePercentages,
                'required_tours' => [
                    'line_1' => $settings['line_1_count_tours'],
                    'line_2' => $settings['line_2_count_tours'],
                    'line_3' => $settings['line_3_count_tours']
                ]
            ],
            'structure' => $structure,
            'totals' => [
                'income' => $totalIncome,
                'paid_income' => $totalPaidIncome,
                'available_income' => $totalAvailableIncome
            ]
        ];

    } catch (Exception $e) {
        $response['msg'] = $e->getMessage();
    }

    return $response;
}


function calculateAgentIncome($userId)
{
    global $db;

    $response = [
        'type' => false,
        'msg' => '',
        'data' => []
    ];

    try {
        // Получаем настройки системы
        $settings = $db->query("SELECT 
            percentage_line_1,
            percentage_line_2,
            percentage_line_3,
            percentage_line_4,
            percenage_line_5,
            percentage_x2_lne_1,
            percentage_x2_lne_2,
            percentage_x2_lne_3,
            percentage_x2_lne_4,
            percentage_x2_lne_5,
            line_1_count_tours,
            line_2_count_tours,
            line_3_count_tours,
            defoul_lines
        FROM app_settings LIMIT 1")->fetch_assoc();

        if (!$settings) {
            throw new Exception("Настройки системы не найдены");
        }

        // Получаем информацию о пользователе
        $user = $db->query("SELECT 
            id, name, famale, phone, 
            (SELECT COUNT(*) FROM order_tours WHERE (user_id = id OR saler_id = id) AND status_code IN (3,4) AND type != 'test') as sold_tours_count
        FROM users WHERE id = $userId")->fetch_assoc();

        if (!$user) {
            throw new Exception("Пользователь не найден");
        }

        // Проверяем x2 доход
        $isX2 = $user['sold_tours_count'] >= 10;

        // Получаем проценты по линиям
        $linePercentages = [
            1 => $isX2 ? $settings['percentage_x2_lne_1'] : $settings['percentage_line_1'],
            2 => $isX2 ? $settings['percentage_x2_lne_2'] : $settings['percentage_line_2'],
            3 => $isX2 ? $settings['percentage_x2_lne_3'] : $settings['percentage_line_3'],
            4 => $isX2 ? $settings['percentage_x2_lne_4'] : $settings['percentage_line_4'],
            5 => $isX2 ? $settings['percentage_x2_lne_5'] : $settings['percenage_line_5']
        ];

        // Определяем сколько линий доступно пользователю
        $availableLines = $settings['defoul_lines'];
        if ($user['sold_tours_count'] >= $settings['line_3_count_tours']) {
            $availableLines = 5;
        } elseif ($user['sold_tours_count'] >= $settings['line_2_count_tours']) {
            $availableLines = 4;
        } elseif ($user['sold_tours_count'] >= $settings['line_1_count_tours']) {
            $availableLines = 3;
        }

        // Получаем всех агентов в структуре
        $structure = [];
        $levels = 5; // Максимальная глубина структуры

        for ($level = 1; $level <= $levels; $level++) {
            if ($level > $availableLines)
                break;

            if ($level == 1) {
                // Первая линия - прямые рефералы
                $query = "SELECT 
                    u.id, u.name, u.famale, u.phone, u.date_validate_agent
                FROM users u 
                WHERE u.parent_user = $userId AND u.user_status = 'agent'
                AND EXISTS (
                    SELECT 1 FROM order_tours ot 
                    WHERE ot.user_id = u.id 
                    AND ot.status_code IN (3,4) 
                    AND ot.type != 'test'
                    AND ot.flyDate <= NOW()
                )";
            } else {
                // Последующие линии - рекурсивно
                $parents = implode(',', array_column($structure[$level - 1], 'id'));
                if (empty($parents))
                    break;

                $query = "SELECT 
                    u.id, u.name, u.famale, u.phone, u.date_validate_agent
                FROM users u 
                WHERE u.parent_user IN ($parents) AND u.user_status = 'agent'
                AND EXISTS (
                    SELECT 1 FROM order_tours ot 
                    WHERE ot.user_id = u.id 
                    AND ot.status_code IN (3,4) 
                    AND ot.type != 'test'
                    AND ot.flyDate <= NOW()
                )";
            }

            $result = $db->query($query);
            if (!$result) {
                throw new Exception("Ошибка получения агентов линии $level: " . $db->error);
            }

            $structure[$level] = [];

            while ($agent = $result->fetch_assoc()) {
                // Получаем только проданные туры агента (уже вылетевшие, не тестовые)
                $toursQuery = "SELECT 
                    ot.id, ot.tourId, ot.price, ot.flyDate, ot.tours_info,
                    ot.user_id, ot.nakrutka, ot.includesPrice, ot.send_money_agent,
                    ot.predoplata, ot.type, ot.summ_send_money,
                    ot.date_create, ot.manager_id, ot.franchaice_id
                FROM order_tours ot
                WHERE ot.user_id = {$agent['id']}
                AND ot.status_code IN (3,4)
                AND ot.flyDate <= NOW()
                AND ot.type != 'test'
                AND ot.includesPrice > 0
                ORDER BY ot.flyDate DESC";

                $toursResult = $db->query($toursQuery);
                $agentTours = [];
                $agentHasTours = false;

                while ($tour = $toursResult->fetch_assoc()) {
                    // Рассчитываем доход агента с тура (накрутка)
                    $agentIncome = $tour['price'] * ($tour['nakrutka'] / 100);

                    // Рассчитываем доход по линиям (2% от includesPrice)
                    $lineIncome = $tour['includesPrice'] * 0.02;

                    // Получаем сумму выплаты (если была)
                    $paidAmount = $tour['summ_send_money'] > 0 ? $tour['summ_send_money'] : 0;

                    // Получаем сумму выплаты агенту (накрутка)
                    $agentPaidAmount = $tour['send_money_agent'] > 0 ? $tour['send_money_agent'] : 0;

                    // Получаем информацию о туре
                    $tourInfo = json_decode($tour['tours_info'], true);
                    $hotelInfo = isset($tourInfo['hotelname']) ? $tourInfo : (isset($tourInfo['hotel']) ? $tourInfo['hotel'] : []);

                    $agentTours[] = [
                        'id' => $tour['id'],
                        'tourId' => $tour['tourId'],
                        'price' => $tour['price'],
                        'fly_date' => $tour['flyDate'],
                        'nakrutka' => $tour['nakrutka'],
                        'agent_income' => $agentIncome,
                        'agent_paid' => $agentPaidAmount,
                        'line_income' => $lineIncome,
                        'client_paid' => $tour['includesPrice'],
                        'predoplata' => $tour['predoplata'],
                        'is_paid' => $tour['summ_send_money'] > 0,
                        'paid_amount' => $paidAmount,
                        'date_create' => $tour['date_create'],
                        'manager_id' => $tour['manager_id'],
                        'franchaice_id' => $tour['franchaice_id'],
                        'tour_info' => $tourInfo,
                        'hotel_info' => $hotelInfo
                    ];
                    $agentHasTours = true;
                }

                // Добавляем агента только если у него есть туры
                if ($agentHasTours) {
                    // Суммируем доходы
                    $totalAgentIncome = array_sum(array_column($agentTours, 'agent_income'));
                    $totalAgentPaid = array_sum(array_column($agentTours, 'agent_paid'));
                    $totalLineIncome = array_sum(array_column($agentTours, 'line_income'));
                    $totalClientPaid = array_sum(array_column($agentTours, 'client_paid'));
                    $totalPaidAmount = array_sum(array_column($agentTours, 'paid_amount'));

                    $structure[$level][] = [
                        'id' => $agent['id'],
                        'name' => $agent['name'] . ' ' . $agent['famale'],
                        'phone' => $agent['phone'],
                        'registration_date' => $agent['date_validate_agent'],
                        'tours' => $agentTours,
                        'total_agent_income' => $totalAgentIncome,
                        'total_agent_paid' => $totalAgentPaid,
                        'total_line_income' => $totalLineIncome,
                        'total_client_paid' => $totalClientPaid,
                        'total_paid_amount' => $totalPaidAmount,
                        'percentage' => $linePercentages[$level]
                    ];
                }
            }
        }

        // Получаем все туры самого агента
        $agentToursQuery = "SELECT 
            ot.id, ot.tourId, ot.price, ot.flyDate, ot.tours_info,
            ot.user_id, ot.nakrutka, ot.includesPrice, ot.send_money_agent,
            ot.predoplata, ot.type, ot.summ_send_money,
            ot.date_create, ot.manager_id, ot.franchaice_id
        FROM order_tours ot
        WHERE ot.user_id = $userId
        AND ot.status_code IN (3,4)
        AND ot.flyDate <= NOW()
        AND ot.type != 'test'
        AND ot.includesPrice > 0
        ORDER BY ot.flyDate DESC";

        $agentToursResult = $db->query($agentToursQuery);
        $agentOwnTours = [];
        $totalOwnIncome = 0;
        $totalOwnClientPaid = 0;
        $totalOwnPaid = 0;

        while ($tour = $agentToursResult->fetch_assoc()) {
            $agentIncome = $tour['price'] * ($tour['nakrutka'] / 100);
            $totalOwnIncome += $agentIncome;
            $totalOwnClientPaid += $tour['includesPrice'];
            $totalOwnPaid += $tour['send_money_agent'] > 0 ? $tour['send_money_agent'] : 0;

            $tourInfo = json_decode($tour['tours_info'], true);
            $hotelInfo = isset($tourInfo['hotelname']) ? $tourInfo : (isset($tourInfo['hotel']) ? $tourInfo['hotel'] : []);

            $agentOwnTours[] = [
                'id' => $tour['id'],
                'tourId' => $tour['tourId'],
                'price' => $tour['price'],
                'fly_date' => $tour['flyDate'],
                'nakrutka' => $tour['nakrutka'],
                'agent_income' => $agentIncome,
                'agent_paid' => $tour['send_money_agent'] > 0 ? $tour['send_money_agent'] : 0,
                'client_paid' => $tour['includesPrice'],
                'predoplata' => $tour['predoplata'],
                'is_paid' => $tour['send_money_agent'] > 0,
                'date_create' => $tour['date_create'],
                'manager_id' => $tour['manager_id'],
                'franchaice_id' => $tour['franchaice_id'],
                'tour_info' => $tourInfo,
                'hotel_info' => $hotelInfo
            ];
        }

        // Считаем общие суммы по линиям
        $totalLineIncome = 0;
        $totalAgentIncome = 0;
        $totalAgentPaid = 0;
        $totalClientPaid = 0;
        $totalPaidAmount = 0;

        foreach ($structure as $level => $agents) {
            foreach ($agents as $agent) {
                $totalLineIncome += $agent['total_line_income'];
                $totalAgentIncome += $agent['total_agent_income'];
                $totalAgentPaid += $agent['total_agent_paid'];
                $totalClientPaid += $agent['total_client_paid'];
                $totalPaidAmount += $agent['total_paid_amount'];
            }
        }

        // Формируем успешный ответ
        $response['type'] = true;
        $response['data'] = [
            'user' => $user,
            'settings' => [
                'available_lines' => $availableLines,
                'default_lines' => $settings['defoul_lines'],
                'is_x2' => $isX2,
                'line_percentages' => $linePercentages,
                'required_tours' => [
                    'line_1' => $settings['line_1_count_tours'],
                    'line_2' => $settings['line_2_count_tours'],
                    'line_3' => $settings['line_3_count_tours']
                ]
            ],
            'structure' => $structure,
            'own_tours' => $agentOwnTours,
            'totals' => [
                'line_income' => $totalLineIncome,
                'agent_income' => $totalAgentIncome,
                'agent_paid' => $totalAgentPaid,
                'client_paid' => $totalClientPaid,
                'paid_amount' => $totalPaidAmount,
                'own_income' => $totalOwnIncome,
                'own_paid' => $totalOwnPaid,
                'own_client_paid' => $totalOwnClientPaid
            ]
        ];

    } catch (Exception $e) {
        $response['msg'] = $e->getMessage();
    }

    return $response;
}
