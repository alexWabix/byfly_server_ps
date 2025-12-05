<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


$operation = $_POST['operation'] ?? null;

if ($operation === 'addCrm') {
    $description = $db->real_escape_string($_POST['description']);
    $dueDate = $db->real_escape_string($_POST['due_date']);
    $assignedTo = explode(':', $_POST['assigned_to']);
    $assignedToType = $db->real_escape_string($assignedTo[0]);
    $assignedToId = intval($assignedTo[1]);
    $createdBy = intval($_POST['created_by']);
    $createdByType = 'manager';

    $query = "INSERT INTO crm_byfly (text, date_off, user_to, user_to_type, user_from, user_from_type, success)
                      VALUES ('$description', '$dueDate', '$assignedToId', '$assignedToType', '$createdBy', '$createdByType', 0)";

    if ($db->query($query)) {
        // Успешное добавление задачи
        // Получение данных для уведомления
        $fromQuery = "SELECT fio AS name FROM managers WHERE id = $createdBy";
        $fromResult = $db->query($fromQuery);
        $fromUser = $fromResult->fetch_assoc();

        $toTableMapping = [
            'manager' => ['table' => 'managers', 'phone_field' => 'phone', 'name_field' => 'fio'],
            'coach' => ['table' => 'coach', 'phone_field' => 'phone', 'name_field' => 'name_famale'],
            'money_man' => ['table' => 'money_user', 'phone_field' => 'phone', 'name_field' => 'name_famale'],
            'admins' => ['table' => 'admins', 'phone_field' => 'phone', 'name_field' => 'name_famale']
        ];

        if (isset($toTableMapping[$assignedToType])) {
            $toTableInfo = $toTableMapping[$assignedToType];
            $toQuery = "SELECT {$toTableInfo['phone_field']} AS phone, {$toTableInfo['name_field']} AS name 
                                FROM {$toTableInfo['table']} WHERE id = $assignedToId";
            $toResult = $db->query($toQuery);

            if ($toResult && $toResult->num_rows > 0) {
                $toUser = $toResult->fetch_assoc();
                $toPhone = $toUser['phone'];
                $toName = $toUser['name'];
                $fromName = $fromUser['name'];

                // Формируем сообщение
                $message = "📋 *Уведомление о новой задаче*\n\n";
                $message .= "💼 *Описание*: \"$description\"\n";
                $message .= "👤 *Назначена*: $toName\n";
                $message .= "📌 *Поставил задачу*: $fromName\n";
                $message .= "📅 *Крайний срок*: " . strftime('%d %B %Y %H:%M', strtotime($dueDate)) . "\n";
                $message .= "🔔 Пожалуйста, проверьте задачу в системе.";

                // Отправка уведомления
                sendWhatsapp($toPhone, $message);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Задача успешно добавлена и уведомление отправлено.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка при добавлении задачи: ' . $db->error]);
    }
    exit;
}



if ($_POST['operation'] === 'deleteCrm') {
    $taskId = intval($_POST['id']);

    // Получение информации о задаче перед удалением
    $taskQuery = "SELECT text, user_to, user_to_type FROM crm_byfly WHERE id = $taskId";
    $taskResult = $db->query($taskQuery);

    if ($taskResult && $taskResult->num_rows > 0) {
        $task = $taskResult->fetch_assoc();
        $taskText = $task['text'];
        $userToId = intval($task['user_to']);
        $userToType = $task['user_to_type'];

        // Удаление задачи
        $deleteQuery = "DELETE FROM crm_byfly WHERE id = $taskId";
        if ($db->query($deleteQuery)) {
            // Определение таблицы и поля телефона
            $userTableMapping = [
                'manager' => ['table' => 'managers', 'phone_field' => 'phone_whatsapp', 'name_field' => 'fio'],
                'coach' => ['table' => 'coach', 'phone_field' => 'phone', 'name_field' => 'name_famale'],
                'money_man' => ['table' => 'money_user', 'phone_field' => 'phone', 'name_field' => 'name_famale'],
                'admins' => ['table' => 'admins', 'phone_field' => 'phone', 'name_field' => 'name_famale']
            ];

            $toTableInfo = $userTableMapping[$userToType] ?? null;

            if ($toTableInfo) {
                $userToQuery = "SELECT {$toTableInfo['phone_field']} AS phone, {$toTableInfo['name_field']} AS name 
                                FROM {$toTableInfo['table']} WHERE id = $userToId";

                $userToResult = $db->query($userToQuery);

                if ($userToResult && $userToResult->num_rows > 0) {
                    $userToInfo = $userToResult->fetch_assoc();
                    $userToPhone = $userToInfo['phone'];
                    $userToName = $userToInfo['name'];

                    // Формирование уведомления
                    $message = "📋 *Уведомление о задаче*\n\n";
                    $message .= "❌ *Задача была удалена:*\n";
                    $message .= "💼 *Описание*: \"$taskText\"\n";
                    $message .= "👤 *Получатель*: $userToName\n";
                    $message .= "🔔 Пожалуйста, уточните детали у постановщика задачи.";

                    // Логирование перед отправкой
                    error_log("Отправка уведомления об удалении задачи на номер: $userToPhone\nСообщение:\n$message");

                    // Отправка уведомления
                    sendWhatsapp($userToPhone, $message);
                } else {
                    error_log("Ошибка: не удалось получить данные о пользователе (user_to). Task ID: $taskId");
                }
            } else {
                error_log("Ошибка: таблица для типа пользователя (user_to_type) не найдена. UserToType: $userToType");
            }

            echo json_encode(['status' => 'success', 'message' => 'Задача успешно удалена.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при удалении задачи: ' . $db->error]);
        }
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Задача не найдена.']);
    }
    exit;
}


if ($operation === 'toggleTask') {
    $taskId = intval($_POST['id']);
    $success = intval($_POST['success']);

    $query = "UPDATE crm_byfly SET success = $success WHERE id = $taskId";
    if ($db->query($query)) {
        $taskQuery = "SELECT text, user_from, user_to, user_from_type, user_to_type FROM crm_byfly WHERE id = $taskId";
        $taskResult = $db->query($taskQuery);
        if ($taskResult && $taskResult->num_rows > 0) {
            $task = $taskResult->fetch_assoc();
            $taskText = $task['text'];
            $userFromId = $task['user_from'];
            $userToId = $task['user_to'];
            $userFromType = $task['user_from_type'];
            $userToType = $task['user_to_type'];

            $userTableMapping = [
                'manager' => ['table' => 'managers', 'phone_field' => 'phone_whatsapp', 'name_field' => 'fio'],
                'coach' => ['table' => 'coach', 'phone_field' => 'phone', 'name_field' => 'name_famale'],
                'money_man' => ['table' => 'money_user', 'phone_field' => 'phone', 'name_field' => 'name_famale'],
                'admins' => ['table' => 'admins', 'phone_field' => 'phone', 'name_field' => 'name_famale']
            ];

            $fromTableInfo = $userTableMapping[$userFromType];
            $toTableInfo = $userTableMapping[$userToType];

            $userFromQuery = "SELECT {$fromTableInfo['phone_field']} AS phone, {$fromTableInfo['name_field']} AS name FROM {$fromTableInfo['table']} WHERE id = $userFromId";
            $userToQuery = "SELECT {$toTableInfo['name_field']} AS name, {$toTableInfo['phone_field']} AS phone FROM {$toTableInfo['table']} WHERE id = $userToId";

            $userFromInfo = $db->query($userFromQuery)->fetch_assoc();
            $userToInfo = $db->query($userToQuery)->fetch_assoc();

            $userFromName = $userFromInfo['name'];
            $userToName = $userToInfo['name'] ?? 'Неизвестно';

            $statusText = $success ? "✅ Задача выполнена" : "🔄 Задача возвращена в работу";
            $message = "📋 *Уведомление о задаче*\n\n";
            $message .= "💼 *Описание*: \"$taskText\"\n";
            $message .= "👤 *Исполнитель*: $userToName\n";
            $message .= "📌 *Поставил задачу*: $userFromName\n";
            $message .= "📅 *Статус*: $statusText\n";
            $message .= "🔔 Пожалуйста, проверьте детали задачи.";

            sendWhatsapp($userFromInfo['phone'], $message);
            sendWhatsapp($userToInfo['phone'], $message);
            echo json_encode(['status' => 'success', 'message' => 'Состояние задачи успешно обновлено']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Не удалось отправить уведомление!']);
        }


    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Ошибка обновления задачи: ' . $db->error]);
    }
    exit;
}


if ($_POST['operation'] === 'sendPassword') {
    $id = intval($_POST['id']);

    // Получение данных сотрудника
    $result = $db->query("SELECT fio, phone_whatsapp, phone_call, email FROM managers WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $employee = $result->fetch_assoc();

        // Генерация нового пароля
        $newPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $hashedPassword = md5($newPassword);

        $updateQuery = "UPDATE managers SET password = '$hashedPassword' WHERE id = $id";
        if ($db->query($updateQuery)) {
            // Отправка сообщения через WhatsApp
            $message = "Добрый день, {$employee['fio']}!\n\nВаш новый пароль для доступа к системе ByFly: $newPassword\n\nЛогин: {$employee['phone_call']}\n\nС уважением, команда ByFly.\n\nhttps://manager.byfly.kz/";
            sendWhatsapp($employee['phone_whatsapp'], $message);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка обновления пароля в базе данных.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Сотрудник не найден.']);
    }
    exit;
}

if ($_POST['operation'] == 'order_update') {
    $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $_POST['id'] . "'")->fetch_assoc();
    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $orderInfo['user_id'] . "'")->fetch_assoc();

    $newDateOffPay = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['payUntil']);
    $formattedPayUntil = $newDateOffPay ? $newDateOffPay->format('Y-m-d H:i:s') : null;

    function formatFIO($fio)
    {
        $parts = explode(' ', $fio);
        $lastName = $parts[0] ?? ''; // Фамилия
        $firstName = isset($parts[1]) ? mb_substr($parts[1], 0, 1) . '.' : ''; // Первая буква имени
        $middleName = isset($parts[2]) ? mb_substr($parts[2], 0, 1) . '.' : ''; // Первая буква отчества

        return trim("$lastName $firstName$middleName");
    }

    function formatRussianDate($date)
    {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря'
        ];

        $dateTime = new DateTime($date);
        $day = $dateTime->format('d');
        $month = $months[(int) $dateTime->format('m')];
        $time = $dateTime->format('H:i');

        return "$day $month $time";
    }
    $statuses = [
        0 => 'Новая в обработке',
        1 => 'Подтверждена - Требуется предоплата',
        2 => 'Подтверждена - Требуется полная оплата',
        3 => 'Полностью оплачена, ожидает вылета',
        4 => 'Турист на отдыхе',
        5 => 'Отменена',
    ];

    $changes = [];
    if ($orderInfo['price'] != $_POST['price']) {
        $changes[] = "💵 Цена: " . $orderInfo['price'] . " → " . $_POST['price'];
    }
    if ($orderInfo['predoplata'] != $_POST['clientPrice']) {
        $changes[] = "💰 Предоплата: " . $orderInfo['predoplata'] . " → " . $_POST['clientPrice'];
    }
    if ($orderInfo['dateOffPay'] != $formattedPayUntil) {
        $oldDate = $orderInfo['dateOffPay'] ? formatRussianDate($orderInfo['dateOffPay']) : 'Не указана';
        $newDate = $newDateOffPay ? formatRussianDate($formattedPayUntil) : 'Не указана';

        $changes[] = "📅 Дата оплаты: " . $oldDate . " → " . $newDate;
    }
    if ($orderInfo['status_code'] != $_POST['status']) {
        $oldStatus = $statuses[$orderInfo['status_code']] ?? 'Неизвестный статус';
        $newStatus = $statuses[$_POST['status']] ?? 'Неизвестный статус';

        $changes[] = "📋 Статус: " . $oldStatus . " → " . $newStatus;
    }
    if ($orderInfo['manager_id'] != $_POST['manager']) {
        $managerInfoNew = $db->query("SELECT * FROM managers WHERE id='" . $_POST['manager'] . "'")->fetch_assoc();
        $managerInfoOld = $db->query("SELECT * FROM managers WHERE id='" . $orderInfo['manager_id'] . "'")->fetch_assoc();
        $changes[] = "👨‍💼 Менеджер: " . formatFIO($managerInfoOld['fio']) . " → " . formatFIO($managerInfoNew['fio']);

        sendWhatsapp($managerInfoNew['phone_whatsapp'], 'Вас назначили к обработке заявки №' . $_POST['id'] . '.\n\nПожалуйста зайдите в CRM системму: https://manager.byfly.kz/');
        sendWhatsapp($managerInfoOld['phone_whatsapp'], 'Вас сняли с обработки заявки №' . $_POST['id'] . '.\n\nПожалуйста зайдите в CRM системму: https://manager.byfly.kz/');
    }
    if (empty($changes)) {
        $response = [
            'success' => true,
            'message' => 'Нет изменений для обновления',
            'receivedData' => $_POST
        ];
        echo json_encode($response);
        exit();
    }

    $changesMessage = implode("\n", $changes);
    $message = "✨ Изменения по вашему заказу №" . $orderInfo['id'] . "! ✨\n\n";
    $message .= "🔄 Изменения:\n" . $changesMessage . "\n\n";
    $message .= "📋 Перейдите в личный кабинет, чтобы узнать все подробности.\n\nhttps://byfly.kz\n\n❤️ С любовью, ваша команда ByFly Travel!";

    if ($db->query("UPDATE order_tours SET price='" . $_POST['price'] . "', predoplata='" . $_POST['clientPrice'] . "', dateOffPay='" . $formattedPayUntil . "', status_code='" . $_POST['status'] . "', manager_id='" . $_POST['manager'] . "', real_price='" . $_POST['realprice'] . "' WHERE id='" . $_POST['id'] . "'")) {
        sendWhatsapp($userInfo['phone'], $message);

        $response = [
            'success' => true,
            'message' => 'Данные успешно обновлены и сообщение отправлено',
            'receivedData' => $_POST
        ];
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'message' => $db->error,
            'receivedData' => $_POST
        ];
        echo json_encode($response);
    }

    exit();
}


if ($_POST['operation'] === 'order_delete') {
    if ($db->query("UPDATE order_tours SET status_code='5' WHERE id='" . $_POST['id'] . "'")) {
        $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $_POST['id'] . "'")->fetch_assoc();
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $orderInfo['user_id'] . "'")->fetch_assoc();
        sendWhatsapp($userInfo['phone'], "⚠️ Ваша заявка №" . $orderInfo['id'] . " была отменена. ⚠️\n\n❗ Если у вас есть вопросы или требуется помощь, пожалуйста, свяжитесь с нашей службой поддержки.\n\nhttps://byfly.kz\n\n❤️ С заботой, команда ByFly Travel.");

        $response = [
            'success' => true,
            'message' => 'Данные успешно обновлены',
            'receivedData' => $_POST
        ];
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'message' => $db->error,
            'receivedData' => $_POST
        ];
        echo json_encode($response);
    }

    exit();
}


if ($_POST['operation'] === 'delete_docs') {
    if ($db->query("DELETE FROM order_docs WHERE id='" . $_POST['id'] . "'")) {
        $response = [
            'success' => true,
            'message' => 'Документ удален!',
        ];
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'message' => $db->error,
        ];
        echo json_encode($response);
    }

    exit();
}

if ($_POST['operation'] === 'add_cash_payment') {
    $response = ['success' => false];

    if (!isset($_POST['order_id'], $_POST['summ'], $_POST['user_id'])) {
        $response['message'] = 'Недостаточно данных для добавления оплаты.';
        echo json_encode($response);
        exit();
    }

    $orderId = intval($_POST['order_id']);
    // Удаляем пробелы из суммы
    $summ = floatval(str_replace(' ', '', $_POST['summ']));
    $userId = intval($_POST['user_id']);
    $db->begin_transaction();

    try {
        // Обновление суммы в таблице order_tours
        $updateQuery = "UPDATE order_tours SET includesPrice = includesPrice + $summ WHERE id = $orderId";
        if (!$db->query($updateQuery)) {
            throw new Exception('Ошибка обновления суммы оплаты: ' . $db->error);
        }

        // Добавление записи в таблицу order_pays
        $insertQuery = "INSERT INTO order_pays (order_id, summ, user_id, date_create, type, tranzaction_id) 
                        VALUES ($orderId, $summ, $userId, CURRENT_TIMESTAMP, 'nalichnie', '')";
        if (!$db->query($insertQuery)) {
            throw new Exception('Ошибка добавления записи оплаты: ' . $db->error);
        }

        // Формирование суммы прописью
        $formatter = new NumberFormatter('ru_RU', NumberFormatter::SPELLOUT);
        $summInWords = ucfirst($formatter->format($summ)) . ' тенге';

        $db->commit();

        $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $orderId . "'")->fetch_assoc();
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $userId . "'")->fetch_assoc();

        sendWhatsapp($userInfo['phone'], "💳 Поступила оплата по вашей заявке №" . $orderInfo['id'] . " на сумму " . $summInWords . ". 💳\n\n🎉 Благодарим вас за оплату! Ваша заявка на бронирование тура обрабатывается. Если у вас есть вопросы, наша команда всегда готова помочь.\n\nhttps://byfly.kz\n\n❤️ С уважением, ваша команда ByFly Travel.");

        $response = [
            'success' => true,
            'message' => 'Наличный платеж успешно добавлен!',
            'summ' => number_format($summ, 2, '.', ' '),
            'date_create' => date('Y-m-d H:i:s'),
            'type' => 'nalichnie',
            'id' => $db->insert_id,
            'summInWords' => $summInWords, // Возвращаем сумму прописью
        ];
    } catch (Exception $e) {
        $db->rollback();
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

if ($_POST['operation'] === 'delete_dop_pay') {
    $payId = intval($_POST['id']);

    $query = "DELETE FROM order_dop_pays WHERE id = '$payId'";

    if ($db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Оплата успешно удалена!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка удаления оплаты: ' . $db->error]);
    }
    exit();
}

if ($_POST['method'] === 'fireEmployee') {
    $id = intval($_POST['id']);
    $dateOffWorks = date('Y-m-d H:i:s');

    $query = "UPDATE managers SET date_off_works = '$dateOffWorks' WHERE id = $id";
    if ($db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Сотрудник успешно уволен.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при увольнении сотрудника: ' . $db->error]);
    }
    exit();
}

if ($_POST['method'] === 'fireEmployee2') {
    $id = intval($_POST['id']);
    $dateOffWorks = date('Y-m-d H:i:s');

    $query = "UPDATE managers SET date_off_works = NULL WHERE id = $id";
    if ($db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Сотрудник успешно востановлен.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при востановлении сотрудника: ' . $db->error]);
    }
    exit();
}


if ($_POST['operation'] === 'add_dop_pay') {
    $response = ['success' => false];

    if (!isset($_POST['order_id'], $_POST['desc_pay'], $_POST['summ'], $_POST['percentage'])) {
        $response['message'] = 'Переданы не все данные.';
        echo json_encode($response);
        exit();
    }

    $orderId = intval($_POST['order_id']);
    $descPay = $db->real_escape_string($_POST['desc_pay']);
    $summ = floatval($_POST['summ']);
    $percentage = floatval($_POST['percentage']);

    $query = "INSERT INTO order_dop_pays (order_id, desc_pay, summ, percentage)
              VALUES ('$orderId', '$descPay', '$summ', '$percentage')";

    if ($db->query($query)) {
        $response = [
            'success' => true,
            'data' => [
                'id' => $db->insert_id,
                'desc_pay' => $descPay,
                'summ' => $summ,
                'percentage' => $percentage
            ]
        ];
    } else {
        $response['message'] = 'Ошибка добавления оплаты: ' . $db->error;
    }

    echo json_encode($response);
    exit();
}


if ($_POST['operation'] === 'add_dop_pay') {
    $response = ['success' => false];
    if (!isset($_POST['order_id'], $_POST['desc_pay'], $_POST['summ'], $_POST['percentage'])) {
        $response['message'] = 'Недостаточно данных для добавления оплаты.';
        echo json_encode($response);
        exit();
    }

    $orderId = intval($_POST['order_id']);
    $descPay = htmlspecialchars($_POST['desc_pay']);
    $summ = floatval(str_replace(' ', '', $_POST['summ']));
    $percentage = floatval($_POST['percentage']);

    $query = "
        INSERT INTO order_dop_pays (`id`, `summ`, `desc_pay`, `order_id`, `percentage`) 
        VALUES (NULL, '$summ', '$descPay', '$orderId', '$percentage')
    ";

    if ($db->query($query)) {
        // Получение информации о заказе и пользователе
        $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $orderId . "'")->fetch_assoc();
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $orderInfo['user_id'] . "'")->fetch_assoc();

        // Формирование суммы прописью
        $formatter = new NumberFormatter('ru_RU', NumberFormatter::SPELLOUT);
        $summInWords = ucfirst($formatter->format($summ)) . ' тенге';

        // Отправка уведомления пользователю через WhatsApp
        sendWhatsapp(
            $userInfo['phone'],
            "💳 К вашему заказу №" . $orderInfo['id'] . " добавлена дополнительная оплата.\n\n" .
            "📋 За: " . $descPay . "\n" .
            "💰 Сумма: " . $summInWords . ".\n\n" .
            "🎉 Благодарим вас за доверие! Если у вас есть вопросы, наша команда всегда готова помочь.\n\n" .
            "https://byfly.kz\n\n" .
            "❤️ С уважением, ваша команда ByFly Travel."
        );

        // Формирование ответа
        $response = [
            'success' => true,
            'id' => $db->insert_id,
            'desc_pay' => $descPay,
            'summ' => number_format($summ, 2, '.', ' '),
            'percentage' => $percentage,
        ];
    } else {
        $response['message'] = 'Ошибка при добавлении оплаты: ' . $db->error;
    }

    echo json_encode($response);
    exit();
}

if ($_POST['method'] === 'fireEmployee') {
    $id = intval($_POST['id']);
    $dateOffWorks = date('Y-m-d H:i:s');

    $query = "UPDATE managers SET date_off_works = '$dateOffWorks' WHERE id = $id";
    if ($db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Сотрудник успешно уволен.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при увольнении сотрудника: ' . $db->error]);
    }
    exit();
}


include('modules/orders/module/passangers_info.php');
include('modules/orders/module/hotel_info.php');
include('modules/orders/module/fly_info.php');
include('modules/orders/module/tour_info.php');
include('modules/orders/module/user_info.php');
include('modules/orders/module/list_tranzaction.php');
include('modules/orders/module/tour_rew.php');
include('modules/orders/module/docs_tour.php');
include('modules/orders/module/dop_pay.php');
include('modules/orders/module/add_payments.php');
include('modules/orders/module/operator_tranzaction.php');
include('modules/orders/module/vozvrat.php');


if (empty($_COOKIE['login']) || empty($_COOKIE['password'])) {
    include('pages/auth.php');
} else {
    $login = preg_replace('/\D/', '', $_COOKIE['login']);
    $searchUserDB = $db->query("SELECT * FROM managers WHERE phone_call='" . $login . "'");
    if ($searchUserDB->num_rows > 0) {
        $searchUser = $searchUserDB->fetch_assoc();
        if ($searchUser['password'] == $_COOKIE['password']) {
            $userInfo = $searchUser;
            if (empty($_GET['page'])) {
                $status = 0;
                include('pages/index.php');
            } else {
                if ($_GET['page'] == 'home' || $_GET['page'] == 'index') {
                    $status = 0;
                    include('pages/index.php');
                } else if ($_GET['page'] == 'await_predoplata') {
                    $status = 1;
                    include('pages/index.php');
                } else if ($_GET['page'] == 'await_pay') {
                    $status = 2;
                    include('pages/index.php');
                } else if ($_GET['page'] == 'await_fly') {
                    $status = 3;
                    include('pages/index.php');
                } else if ($_GET['page'] == 'in_tours') {
                    $status = 4;
                    include('pages/index.php');
                } else if ($_GET['page'] == 'cancle_tours') {
                    $status = 5;
                    include('pages/index.php');
                } else if ($_GET['page'] == 'search') {
                    $status = 5;
                    $search_text = $_GET['query'];
                    include('pages/index.php');
                } else if ($_GET['page'] == 'zarplata') {
                    include('pages/zarplata.php');
                } else if ($_GET['page'] == 'dogovor') {
                    include('pages/dogovor.php');
                } else if ($_GET['page'] == 'crm') {
                    include('pages/crm.php');
                } else if ($_GET['page'] == 'personal') {
                    include('pages/personal.php');
                } else if ($_GET['page'] == 'settings') {
                    include('pages/settings.php');
                } else if ($_GET['page'] == 'mekka_hotels') {
                    include('pages/hotels_mekka.php');
                } else if ($_GET['page'] == 'update_rooms_hotel') {
                    include('pages/update_hotel_room.php');
                } else if ($_GET['page'] == 'update_hotel') {
                    include('pages/update_hotel.php');
                } else if ($_GET['page'] == 'logoute') {
                    setcookie('login', '', time() - 3600, '/');
                    setcookie('password', '', time() - 3600, '/');
                    unset($_COOKIE['login']);
                    unset($_COOKIE['password']);
                    include('pages/auth.php');
                } else if ($_GET['page'] == 'allOperators') {
                    include('pages/allOperators.php');
                } else {
                    $status = 0;
                    include('pages/index.php');
                }
            }

        } else {
            include('pages/auth.php');
        }
    } else {
        include('pages/auth.php');
    }
}
?>