<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_POST['method'] ?? $_GET['method'] ?? '';

try {
    switch ($method) {
        case 'get_manager_info':
            getManagerInfo();
            break;

        case 'toggle_shift_status':
            toggleShiftStatus();
            break;

        case 'upload_avatar':
            uploadAvatar();
            break;

        case 'get_statistics':
            getStatistics();
            break;

        case 'get_chart_data':
            getChartData();
            break;

        case 'get_payment_calendar':
            getPaymentCalendar();
            break;

        case 'get_tours_for_date':
            getToursForDate();
            break;

        default:
            throw new Exception('Неизвестный метод: ' . $method);
    }
} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getManagerInfo()
{
    global $db;

    $managerId = intval($_POST['manager_id'] ?? 0);

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    $stmt = $db->prepare("
        SELECT 
            m.*,
            f.titleFranchaise as franchise_name
        FROM managers m
        LEFT JOIN franchaise f ON m.franchaise = f.id
        WHERE m.id = ?
    ");

    $stmt->bind_param('i', $managerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Менеджер не найден');
    }

    $manager = $result->fetch_assoc();

    // Проверяем права доступа
    $isAdmin = ($manager['type'] == 1);

    $response = [
        'type' => true,
        'data' => [
            'id' => $manager['id'],
            'fio' => $manager['fio'],
            'phone_call' => $manager['phone_call'],
            'phone_whatsapp' => $manager['phone_whatsapp'],
            'avatar' => $manager['avatar'] ?? '',
            'franchise_name' => $manager['franchise_name'] ?? 'Не указана',
            'is_active' => $manager['isActive'],
            'is_admin' => $isAdmin,
            'work_for_tours' => $manager['work_for_tours'],
            'show_spec' => $manager['show_spec'],
            'type' => $manager['type']
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function toggleShiftStatus()
{
    global $db;

    $managerId = intval($_POST['manager_id'] ?? 0);
    $newStatus = intval($_POST['status'] ?? 0);

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Обновляем статус менеджера
    $stmt = $db->prepare("
        UPDATE managers 
        SET isActive = ?
        WHERE id = ?
    ");

    $stmt->bind_param('ii', $newStatus, $managerId);

    if (!$stmt->execute()) {
        throw new Exception('Ошибка обновления статуса смены');
    }

    // Логируем изменение статуса
    $action = $newStatus ? 'Начал смену' : 'Завершил смену';
    $logStmt = $db->prepare("
        INSERT INTO error_logs (text, date_create) 
        VALUES (?, NOW())
    ");

    $logText = "Менеджер ID: {$managerId} - {$action}";
    $logStmt->bind_param('s', $logText);
    $logStmt->execute();

    $response = [
        'type' => true,
        'msg' => $newStatus ? 'Смена начата' : 'Смена завершена',
        'data' => [
            'is_active' => $newStatus
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function uploadAvatar()
{
    global $db, $domain;

    $managerId = intval($_POST['manager_id'] ?? 0);

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Файл не загружен или произошла ошибка');
    }

    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Недопустимый тип файла. Разрешены: JPEG, PNG, GIF, WebP');
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        throw new Exception('Размер файла не должен превышать 5MB');
    }

    // Создаем папку если не существует
    $uploadDir = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/managers_avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Генерируем уникальное имя файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'manager_' . $managerId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Ошибка сохранения файла');
    }

    // Обновляем аватар в базе данных
    $avatarUrl = $domain . 'images/managers_avatars/' . $fileName;

    $stmt = $db->prepare("
        UPDATE managers 
        SET avatar = ?
        WHERE id = ?
    ");

    $stmt->bind_param('si', $avatarUrl, $managerId);

    if (!$stmt->execute()) {
        // Удаляем файл если не удалось обновить БД
        unlink($filePath);
        throw new Exception('Ошибка обновления аватара в базе данных');
    }

    $response = [
        'type' => true,
        'msg' => 'Аватар успешно обновлен',
        'data' => [
            'avatar_url' => $avatarUrl
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getStatistics()
{
    global $db;

    $managerId = intval($_POST['manager_id'] ?? 0);
    $isAdmin = intval($_POST['is_admin'] ?? 0);
    $isCurrentManager = intval($_POST['is_current_manager'] ?? 0);

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Определяем условие фильтрации
    $whereClause = '';
    if ($isCurrentManager && !$isAdmin) {
        $whereClause = "WHERE ot.manager_id = {$managerId}";
    } else {
        $whereClause = "WHERE 1=1";
    }

    $statistics = [];

    // Получаем дополнительные туры
    $addToursResult = $db->query("SELECT COALESCE(SUM(count), 0) as add_count FROM addTours");
    $additionalTours = 0;
    if ($addToursResult && $addToursResult->num_rows > 0) {
        $row = $addToursResult->fetch_assoc();
        $additionalTours = intval($row['add_count']);
    }

    // Основная статистика
    $mainQuery = "
        SELECT 
            COUNT(*) as total_tours,
            COALESCE(SUM(ot.price), 0) as total_amount,
            SUM(CASE WHEN ot.status_code = 3 THEN 1 ELSE 0 END) as waiting_departure,
            SUM(CASE WHEN ot.status_code = 4 THEN 1 ELSE 0 END) as on_vacation,
            SUM(CASE WHEN ot.status_code = 1 THEN 1 ELSE 0 END) as waiting_prepayment,
            SUM(CASE WHEN ot.status_code = 2 THEN 1 ELSE 0 END) as waiting_full_payment
        FROM order_tours ot
        {$whereClause} AND ot.status_code != 5
    ";

    $result = $db->query($mainQuery);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $statistics = [
            'total_tours' => intval($row['total_tours']) + $additionalTours,
            'total_amount' => intval($row['total_amount']),
            'waiting_departure' => intval($row['waiting_departure']),
            'on_vacation' => intval($row['on_vacation']),
            'waiting_prepayment' => intval($row['waiting_prepayment']),
            'waiting_full_payment' => intval($row['waiting_full_payment'])
        ];
    }

    // Статистика по долгам операторам (только для админов)
    if ($isAdmin) {
        $operatorDebtQuery = "
            SELECT 
                COUNT(*) as unpaid_count,
                COALESCE(SUM(
                    CASE 
                        WHEN COALESCE(summ_pay_to_operator, 0) < COALESCE(summ_need_pay, 0)
                        THEN COALESCE(summ_need_pay, 0) - COALESCE(summ_pay_to_operator, 0)
                        ELSE 0 
                    END
                ), 0) as unpaid_amount,
                COUNT(CASE 
                    WHEN date_deadline_pay_in_operarator IS NOT NULL 
                         AND date_deadline_pay_in_operarator != '0000-00-00 00:00:00'
                         AND date_deadline_pay_in_operarator < NOW() 
                         AND COALESCE(summ_pay_to_operator, 0) < COALESCE(summ_need_pay, 0)
                    THEN 1 
                END) as overdue_count,
                COALESCE(SUM(CASE 
                    WHEN date_deadline_pay_in_operarator IS NOT NULL 
                         AND date_deadline_pay_in_operarator != '0000-00-00 00:00:00'
                         AND date_deadline_pay_in_operarator < NOW() 
                         AND COALESCE(summ_pay_to_operator, 0) < COALESCE(summ_need_pay, 0)
                    THEN COALESCE(summ_need_pay, 0) - COALESCE(summ_pay_to_operator, 0)
                    ELSE 0 
                END), 0) as overdue_amount
            FROM order_tours 
            WHERE status_code IN (1, 2, 3, 4)
              AND COALESCE(summ_pay_to_operator, 0) < COALESCE(summ_need_pay, 0)
              " . ($isCurrentManager ? "AND manager_id = {$managerId}" : "") . "
        ";

        $operatorResult = $db->query($operatorDebtQuery);
        if ($operatorResult && $operatorResult->num_rows > 0) {
            $row = $operatorResult->fetch_assoc();
            $statistics['unpaid_to_operator'] = intval($row['unpaid_count']);
            $statistics['amount_to_pay_operator'] = intval($row['unpaid_amount']);
            $statistics['overdue_to_operator'] = intval($row['overdue_count']);
            $statistics['overdue_amount_operator'] = intval($row['overdue_amount']);
        }
    }

    // Статистика по спец предложениям
    $specQuery = "
        SELECT 
            COUNT(*) as spec_total,
            SUM(CASE WHEN status_code = 3 THEN 1 ELSE 0 END) as spec_waiting_departure,
            SUM(CASE WHEN status_code = 4 THEN 1 ELSE 0 END) as spec_on_vacation,
            SUM(CASE WHEN status_code IN (1,2) THEN 1 ELSE 0 END) as spec_waiting_payment,
            COALESCE(SUM(CASE 
                WHEN status_code IN (1,2) 
                THEN price - COALESCE(includesPrice, 0) 
                ELSE 0 
            END), 0) as spec_unpaid_amount
        FROM order_tours 
        WHERE type = 'spec' AND status_code != 5
        " . ($isCurrentManager && !$isAdmin ? "AND manager_id = {$managerId}" : "") . "
    ";

    $specResult = $db->query($specQuery);
    if ($specResult && $specResult->num_rows > 0) {
        $row = $specResult->fetch_assoc();
        $statistics['spec_offers_sold'] = intval($row['spec_total']);
        $statistics['spec_waiting_departure'] = intval($row['spec_waiting_departure']);
        $statistics['spec_on_vacation'] = intval($row['spec_on_vacation']);
        $statistics['spec_waiting_payment'] = intval($row['spec_waiting_payment']);
        $statistics['spec_unpaid_amount'] = intval($row['spec_unpaid_amount']);
    }

    // Статистика по неоплаченным турам (обычным)
    $toursUnpaidQuery = "
        SELECT 
            COALESCE(SUM(CASE 
                WHEN status_code IN (1,2) 
                THEN price - COALESCE(includesPrice, 0) 
                ELSE 0 
            END), 0) as tours_unpaid_amount
        FROM order_tours 
        WHERE (type != 'spec' OR type IS NULL) AND status_code != 5
        " . ($isCurrentManager && !$isAdmin ? "AND manager_id = {$managerId}" : "") . "
    ";

    $toursUnpaidResult = $db->query($toursUnpaidQuery);
    if ($toursUnpaidResult && $toursUnpaidResult->num_rows > 0) {
        $row = $toursUnpaidResult->fetch_assoc();
        $statistics['tours_unpaid_amount'] = intval($row['tours_unpaid_amount']);
    }

    $response = [
        'type' => true,
        'data' => $statistics
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getChartData()
{
    global $db;

    $managerId = intval($_POST['manager_id'] ?? 0);
    $isAdmin = intval($_POST['is_admin'] ?? 0);
    $isCurrentManager = intval($_POST['is_current_manager'] ?? 0);

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Определяем условие фильтрации
    $whereClause = '';
    if ($isCurrentManager && !$isAdmin) {
        $whereClause = "WHERE ot.manager_id = {$managerId}";
    } else {
        $whereClause = "WHERE 1=1";
    }

    $chartQuery = "
        SELECT 
            YEAR(ot.date_create) as year,
            MONTH(ot.date_create) as month,
            COUNT(*) as tours_count,
            COALESCE(SUM(ot.price), 0) as total_amount,
            SUM(CASE WHEN ot.type = 'spec' THEN 1 ELSE 0 END) as spec_count
        FROM order_tours ot
        {$whereClause}
        " . ($whereClause ? 'AND' : 'WHERE') . " ot.date_create >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          AND ot.date_create IS NOT NULL
          AND ot.date_create != '0000-00-00 00:00:00'
          AND ot.status_code != 5
        GROUP BY YEAR(ot.date_create), MONTH(ot.date_create)
        ORDER BY year, month
    ";

    $result = $db->query($chartQuery);
    $chartData = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $chartData[] = [
                'year' => intval($row['year']),
                'month' => intval($row['month']),
                'tours_count' => intval($row['tours_count']),
                'total_amount' => intval($row['total_amount']),
                'spec_count' => intval($row['spec_count'])
            ];
        }
    }

    $response = [
        'type' => true,
        'data' => $chartData
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getPaymentCalendar()
{
    global $db;

    $managerId = intval($_POST['manager_id'] ?? 0);
    $isAdmin = intval($_POST['is_admin'] ?? 0);
    $isCurrentManager = intval($_POST['is_current_manager'] ?? 0);
    $startDate = $_POST['start_date'] ?? date('Y-m-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-t', strtotime('+2 months'));

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    // Только админы могут видеть календарь выплат
    if (!$isAdmin) {
        $response = [
            'type' => true,
            'data' => []
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    $whereClause = '';
    if ($isCurrentManager) {
        $whereClause = "AND manager_id = {$managerId}";
    }

    $calendarQuery = "
        SELECT 
          DATE(
            CASE 
              WHEN date_deadline_pay_in_operarator IS NOT NULL 
                  AND date_deadline_pay_in_operarator != '0000-00-00 00:00:00'
              THEN date_deadline_pay_in_operarator
              ELSE DATE_SUB(flyDate, INTERVAL 4 WEEK)
            END
          ) as payment_date,
          COUNT(*) as tours_count,
          SUM(summ_need_pay - COALESCE(summ_pay_to_operator, 0)) as total_amount,
          SUM(
            CASE 
              WHEN date_deadline_pay_in_operarator IS NOT NULL 
                  AND date_deadline_pay_in_operarator != '0000-00-00 00:00:00'
                  AND date_deadline_pay_in_operarator < NOW()
              THEN 1 
              ELSE 0 
            END
          ) as overdue_count
        FROM order_tours 
        WHERE 
          flyDate IS NOT NULL 
          AND flyDate != '0000-00-00'
          AND DATE(
            CASE 
              WHEN date_deadline_pay_in_operarator IS NOT NULL 
                  AND date_deadline_pay_in_operarator != '0000-00-00 00:00:00'
              THEN date_deadline_pay_in_operarator
              ELSE DATE_SUB(flyDate, INTERVAL 4 WEEK)
            END
          ) BETWEEN '{$startDate}' AND '{$endDate}'
          AND status_code IN (1, 2, 3, 4)
          AND summ_need_pay > 0
          AND (summ_need_pay - COALESCE(summ_pay_to_operator, 0)) > 0
          {$whereClause}
        GROUP BY DATE(
          CASE 
            WHEN date_deadline_pay_in_operarator IS NOT NULL 
                AND date_deadline_pay_in_operarator != '0000-00-00 00:00:00'
            THEN date_deadline_pay_in_operarator
            ELSE DATE_SUB(flyDate, INTERVAL 4 WEEK)
          END
        )
        ORDER BY payment_date
    ";

    $result = $db->query($calendarQuery);
    $calendarData = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $calendarData[] = [
                'payment_date' => $row['payment_date'],
                'tours_count' => intval($row['tours_count']),
                'total_amount' => intval($row['total_amount']),
                'overdue_count' => intval($row['overdue_count'])
            ];
        }
    }

    $response = [
        'type' => true,
        'data' => $calendarData
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getToursForDate()
{
    global $db;

    $managerId = intval($_POST['manager_id'] ?? 0);
    $isAdmin = intval($_POST['is_admin'] ?? 0);
    $isCurrentManager = intval($_POST['is_current_manager'] ?? 0);
    $selectedDate = $_POST['selected_date'] ?? '';

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    if (empty($message)) {
        throw new Exception('Сообщение не указано');
    }

    // Получаем информацию о заявке и клиенте
    $orderQuery = "
        SELECT 
            ot.id,
            u.phone,
            u.name,
            u.famale,
            JSON_EXTRACT(ot.tours_info, '$.hotelname') as hotel_name,
            JSON_EXTRACT(ot.tours_info, '$.countryname') as country_name
        FROM order_tours ot
        LEFT JOIN users u ON ot.user_id = u.id
        WHERE ot.id = ?
    ";

    $stmt = $db->prepare($orderQuery);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Заявка не найдена');
    }

    $orderData = $result->fetch_assoc();
    $phone = $orderData['phone'];
    $clientName = $orderData['famale'] . ' ' . $orderData['name'];

    if (empty($phone)) {
        throw new Exception('У клиента не указан номер телефона');
    }

    // Форматируем сообщение
    $fullMessage = "🏖️ *ByFly Travel*\n\n";
    $fullMessage .= "Здравствуйте, {$clientName}!\n\n";
    $fullMessage .= "По вашей заявке #{$orderId}:\n";
    $fullMessage .= "🏨 " . trim($orderData['hotel_name'], '"') . "\n";
    $fullMessage .= "🌍 " . trim($orderData['country_name'], '"') . "\n\n";
    $fullMessage .= $message;
    $fullMessage .= "\n\n📞 Если у вас есть вопросы, звоните или пишите нам!";

    // Отправляем WhatsApp сообщение
    try {
        sendWhatsapp($phone, $fullMessage);

        // Логируем отправку уведомления
        logManagerAction(
            $managerId,
            'Отправка WhatsApp уведомления',
            "Заявка #{$orderId}, телефон: {$phone}"
        );

        $response = [
            'type' => true,
            'msg' => 'Уведомление успешно отправлено'
        ];

    } catch (Exception $e) {
        throw new Exception('Ошибка отправки WhatsApp сообщения: ' . $e->getMessage());
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function getPaymentMethods()
{
    $paymentMethods = [
        [
            'id' => 'nalichnie',
            'name' => 'Наличные',
            'icon' => 'money',
            'color' => '#4CAF50'
        ],
        [
            'id' => 'kaspi',
            'name' => 'Kaspi Pay',
            'icon' => 'credit_card',
            'color' => '#FF5722'
        ],
        [
            'id' => 'balance',
            'name' => 'Баланс клиента',
            'icon' => 'account_balance_wallet',
            'color' => '#2196F3'
        ],
        [
            'id' => 'bonus',
            'name' => 'Бонусы клиента',
            'icon' => 'stars',
            'color' => '#FF9800'
        ],
        [
            'id' => 'bank_transfer',
            'name' => 'Банковский перевод',
            'icon' => 'account_balance',
            'color' => '#9C27B0'
        ]
    ];

    $response = [
        'type' => true,
        'data' => $paymentMethods
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function processKaspiPayment()
{
    global $db;

    $orderId = intval($_POST['order_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);
    $amount = intval($_POST['amount'] ?? 0);
    $paymentType = $_POST['payment_type'] ?? 'cash'; // cash, credit, installment, kaspi_red

    if ($orderId <= 0) {
        throw new Exception('ID заявки не указан');
    }

    if ($managerId <= 0) {
        throw new Exception('ID менеджера не указан');
    }

    if ($amount <= 0) {
        throw new Exception('Сумма платежа должна быть больше 0');
    }

    // Получаем информацию о заявке
    $orderQuery = "
        SELECT 
            ot.user_id,
            ot.price,
            ot.includesPrice,
            u.phone,
            u.name,
            u.famale
        FROM order_tours ot
        LEFT JOIN users u ON ot.user_id = u.id
        WHERE ot.id = ?
    ";

    $stmt = $db->prepare($orderQuery);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Заявка не найдена');
    }

    $orderData = $result->fetch_assoc();
    $userId = intval($orderData['user_id']);
    $clientPhone = $orderData['phone'];
    $clientName = $orderData['famale'] . ' ' . $orderData['name'];

    // Получаем настройки комиссий
    $settingsQuery = "
        SELECT 
            kaspi_credit_percentage,
            kasp_red_percentage
        FROM app_settings 
        WHERE id = 1
    ";

    $settingsResult = $db->query($settingsQuery);
    $creditPercentage = 0;
    $redPercentage = 0;

    if ($settingsResult && $settingsResult->num_rows > 0) {
        $settings = $settingsResult->fetch_assoc();
        $creditPercentage = intval($settings['kaspi_credit_percentage']);
        $redPercentage = intval($settings['kasp_red_percentage']);
    }

    // Рассчитываем комиссию
    $feePercentage = 0;
    switch ($paymentType) {
        case 'credit':
            $feePercentage = $creditPercentage;
            break;
        case 'installment':
            $feePercentage = $creditPercentage;
            break;
        case 'kaspi_red':
            $feePercentage = $redPercentage;
            break;
        default:
            $feePercentage = 0;
    }

    $totalAmountWithFee = $amount;
    if ($feePercentage > 0) {
        $totalAmountWithFee = $amount + (($amount / 100) * $feePercentage);
    }

    $cleanAmount = $amount; // Сумма которая поступит в компанию

    // Находим свободный терминал
    $terminalQuery = "
        SELECT id 
        FROM kaspi_terminals 
        WHERE status = 'free' AND is_active = 1
        ORDER BY operations_count ASC, priority DESC
        LIMIT 1
    ";

    $terminalResult = $db->query($terminalQuery);
    if (!$terminalResult || $terminalResult->num_rows === 0) {
        throw new Exception('Нет доступных терминалов для проведения операции');
    }

    $terminal = $terminalResult->fetch_assoc();
    $terminalId = intval($terminal['id']);

    // Создаем транзакцию в базе данных
    $insertTransactionQuery = "
        INSERT INTO kaspi_transactions (
            terminal_id, 
            amount, 
            payment_type, 
            percentage_fee, 
            clean_amount, 
            total_amount_with_fee, 
            status, 
            user_id, 
            date_initiated, 
            client_phone, 
            order_id, 
            order_type, 
            client_name
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), ?, ?, 'tour', ?)
    ";

    $stmt = $db->prepare($insertTransactionQuery);
    $stmt->bind_param(
        'iisdiiisiss',
        $terminalId,
        $amount,
        $paymentType,
        $feePercentage,
        $cleanAmount,
        $totalAmountWithFee,
        $userId,
        $clientPhone,
        $orderId,
        $clientName
    );

    if (!$stmt->execute()) {
        throw new Exception('Ошибка создания транзакции');
    }

    $transactionId = $db->insert_id;

    // Обновляем статус терминала
    $updateTerminalQuery = "
        UPDATE kaspi_terminals 
        SET status = 'busy', last_operation_date = NOW()
        WHERE id = ?
    ";

    $stmt = $db->prepare($updateTerminalQuery);
    $stmt->bind_param('i', $terminalId);
    $stmt->execute();

    // Получаем информацию о терминале для API запроса
    $terminalInfoQuery = "SELECT port FROM kaspi_terminals WHERE id = ?";
    $stmt = $db->prepare($terminalInfoQuery);
    $stmt->bind_param('i', $terminalId);
    $stmt->execute();
    $terminalInfo = $stmt->get_result()->fetch_assoc();
    $terminalPort = $terminalInfo['port'];

    try {
        // Инициируем платеж на терминале
        $apiUrl = "http://109.175.215.40:{$terminalPort}/v2/payment?amount=" . intval($totalAmountWithFee);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new Exception('Ошибка соединения с терминалом');
        }

        $terminalResponse = json_decode($response, true);

        if (!$terminalResponse || $terminalResponse['statusCode'] !== 0) {
            throw new Exception('Терминал вернул ошибку: ' . ($terminalResponse['message'] ?? 'Неизвестная ошибка'));
        }

        $processId = $terminalResponse['data']['processId'];

        // Обновляем транзакцию с данными от терминала
        $updateTransactionQuery = "
            UPDATE kaspi_transactions 
            SET 
                terminal_operation_id = ?,
                terminal_response = ?,
                status = 'processing'
            WHERE id = ?
        ";

        $stmt = $db->prepare($updateTransactionQuery);
        $terminalResponseJson = json_encode($terminalResponse, JSON_UNESCAPED_UNICODE);
        $stmt->bind_param('ssi', $processId, $terminalResponseJson, $transactionId);
        $stmt->execute();

        // Логируем создание платежа
        logManagerAction(
            $managerId,
            'Инициация Kaspi платежа',
            "Заявка #{$orderId}, сумма: {$totalAmountWithFee} тенге, терминал: {$terminalId}"
        );

        $response = [
            'type' => true,
            'msg' => 'Платеж инициирован на терминале',
            'data' => [
                'transaction_id' => $transactionId,
                'process_id' => $processId,
                'terminal_id' => $terminalId,
                'amount' => $amount,
                'total_amount_with_fee' => $totalAmountWithFee,
                'fee_percentage' => $feePercentage,
                'payment_type' => $paymentType
            ]
        ];

    } catch (Exception $e) {
        // В случае ошибки освобождаем терминал и обновляем статус транзакции
        $updateTerminalQuery = "UPDATE kaspi_terminals SET status = 'free' WHERE id = ?";
        $stmt = $db->prepare($updateTerminalQuery);
        $stmt->bind_param('i', $terminalId);
        $stmt->execute();

        $updateTransactionQuery = "
            UPDATE kaspi_transactions 
            SET status = 'failed', error_message = ?
            WHERE id = ?
        ";
        $stmt = $db->prepare($updateTransactionQuery);
        $errorMessage = $e->getMessage();
        $stmt->bind_param('si', $errorMessage, $transactionId);
        $stmt->execute();

        throw $e;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

// Вспомогательные функции

function getStatusName($statusCode)
{
    $statuses = [
        0 => 'Новая заявка',
        1 => 'Подтверждена, ожидает предоплату',
        2 => 'Подтверждена, ожидает полную оплату',
        3 => 'Полностью оплачена, ожидает вылета',
        4 => 'Турист на отдыхе',
        5 => 'Заявка отменена'
    ];

    return $statuses[$statusCode] ?? 'Неизвестный статус';
}

function getStatusMessage($statusCode, $orderId)
{
    $messages = [
        0 => "Ваша заявка #{$orderId} принята в обработку. Менеджер свяжется с вами в ближайшее время.",
        1 => "Ваша заявка #{$orderId} подтверждена! Для бронирования тура необходимо внести предоплату.",
        2 => "По заявке #{$orderId} получена предоплата. Для завершения бронирования необходимо доплатить оставшуюся сумму.",
        3 => "Заявка #{$orderId} полностью оплачена! Готовьтесь к отдыху. Документы будут высланы ближе к дате вылета.",
        4 => "Приятного отдыха! Ваш тур по заявке #{$orderId} начался. Желаем незабываемых впечатлений!",
        5 => "Заявка #{$orderId} отменена. Если у вас есть вопросы, обратитесь к менеджеру."
    ];

    return $messages[$statusCode] ?? "Статус заявки #{$orderId} изменен.";
}

function logManagerAction($managerId, $action, $details = '')
{
    global $db;

    $stmt = $db->prepare("
        INSERT INTO error_logs (text, date_create) 
        VALUES (?, NOW())
    ");

    $logText = "Менеджер ID: {$managerId} - Действие: {$action}";
    if (!empty($details)) {
        $logText .= " - Детали: {$details}";
    }

    $stmt->bind_param('s', $logText);
    $stmt->execute();
}
?>