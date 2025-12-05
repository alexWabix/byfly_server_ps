<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

ini_set('memory_limit', '1024M');
include('config.php');



// Объединяем данные из GET и POST
$requestData = array_merge($_GET, $_POST);

// Получаем метод из объединенных данных
$method = empty($requestData['method']) ? '' : $requestData['method'];
header('Content-Type: application/json; charset=utf-8');

if (empty($method)) {
    $resp = array(
        "type" => false,
        "msg" => "Method not specified!",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($requestData['user_id'])) {
    if (isset($_COOKIE['user_id'])) {
        $userId = $_COOKIE['user_id'];
        $_POST['user_id'] = $userId;
    }
}

$method_file = 'methods/' . $method . '.php';

function isUserAgent($userId)
{
    global $db;

    // Запрос для получения данных пользователя
    $sql = "SELECT 
                u.user_status, 
                u.orient, 
                u.astestation_bal, 
                u.blocked_to_time,
                u.date_validate_agent,
                COUNT(us.id) as has_agent_status
            FROM users u
            LEFT JOIN user_statused us ON us.user_id = u.id AND us.code_status = 4
            WHERE u.id = ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return false; // Пользователь не найден
    }

    $user = $result->fetch_assoc();


    // Проверка блокировки
    $isBlocked = ($user['blocked_to_time'] !== null &&
        strtotime($user['blocked_to_time']) > time());

    // Проверка экзамена
    $examPassed = ($user['astestation_bal'] !== null &&
        $user['astestation_bal'] >= 92);

    // Проверка статуса агента (код 4)
    $hasAgentStatus = ($user['has_agent_status'] > 0);

    // Проверка даты валидации агента
    $isAgentValid = ($user['date_validate_agent'] === null ||
        strtotime($user['date_validate_agent']) <= time());

    // Итоговая проверка
    return !$isBlocked &&
        $examPassed &&
        $hasAgentStatus &&
        $isAgentValid;
}



function getUserInfoFromID($id, $parent_user_info = true)
{
    global $db;
    $searchUserDB = $db->query("SELECT * FROM users WHERE id='" . $id . "'");
    if ($searchUserDB->num_rows > 0) {
        $searchUser = $searchUserDB->fetch_assoc();
        $searchUser['statused'] = array();
        $searchUser['status_text'] = array();

        $getStatusedDB = $db->query("SELECT * FROM user_statused WHERE user_id='" . $searchUser['id'] . "'");
        while ($getStatused = $getStatusedDB->fetch_assoc()) {
            $getStatused['status_info'] = $db->query("SELECT * FROM named_status WHERE code_status='" . $getStatused['code_status'] . "'")->fetch_assoc();
            array_push($searchUser['status_text'], $getStatused['status_info']['title']);
            array_push($searchUser['statused'], $getStatused);
        }

        $searchInManagerDB = $db->query("SELECT * FROM managers WHERE phone_call='" . $searchUser['phone'] . "' OR phone_whatsapp='" . $searchUser['phone'] . "'");
        $searchUser['imanager'] = null;
        if ($searchInManagerDB->num_rows > 0) {
            $searchUser['imanager'] = $searchInManagerDB->fetch_assoc();
        }

        $searchUser['atestation_query'] = json_decode(stripslashes($searchUser['atestation_query']), true);

        $searchUser['status_text'] = implode(', ', $searchUser['status_text']);

        if ($parent_user_info) {
            $searchUser['kurator'] = getUserInfoFromID($searchUser['parent_user'], false);
        } else {
            $searchUser['kurator'] = false;
        }

        if (!$searchUser['kurator']) {
            $searchUser['kurator'] = null;
        }

        $searchUser['count_sales'] = $db->query("
            SELECT COUNT(*) as ct 
            FROM order_tours 
            WHERE user_id = '" . $searchUser['id'] . "' 
            AND status_code IN (3,4)
        ")->fetch_assoc()['ct'];

        $searchUser['count_agents'] = $db->query("
            SELECT COUNT(*) as ct 
            FROM users 
            WHERE parent_user = '" . $searchUser['id'] . "' 
            AND date_couch_start IS NOT NULL 
            AND date_couch_start != ''
        ")->fetch_assoc()['ct'];

        $randomMangerDB = $db->query("SELECT * FROM managers WHERE `date_off_works` IS NULL AND type='0' AND isActive='1' AND work_for_tours='1' AND id != '4' ORDER BY RAND() LIMIT 1");
        if ($randomMangerDB->num_rows > 0) {
            $randomManger = $randomMangerDB->fetch_assoc();
            $searchUser['manager'] = $randomManger;
        } else {
            $randomMangerDB = $db->query("SELECT * FROM managers WHERE `date_off_works` IS NULL AND type='0' AND work_for_tours='1' AND id != '4' ORDER BY RAND() LIMIT 1");
            if ($randomMangerDB->num_rows > 0) {
                $randomManger = $randomMangerDB->fetch_assoc();
                $searchUser['manager'] = $randomManger;
            } else {
                $randomMangerDB = $db->query("SELECT * FROM managers WHERE `date_off_works` IS NULL AND id != '4' ORDER BY RAND() LIMIT 1");
                if ($randomMangerDB->num_rows > 0) {
                    $randomManger = $randomMangerDB->fetch_assoc();
                    $searchUser['manager'] = $randomManger;
                }
            }
        }

        return $searchUser;
    } else {
        return false;
    }
}

// Обработка синхронизации контактов из объединенных данных
if (!empty($requestData['user_contact_sinhronizatyion_id'])) {
    $db->query("SET NAMES 'utf8mb4'");
    $parceContact = json_decode($requestData['myContacts'], true);
    foreach ($parceContact as $value) {
        foreach ($value['phones'] as $phone) {
            $db->query("INSERT INTO user_contacts (`id`, `name`, `phone`, `date_create`, `isRegistered`, `isWhatsapp`, `isDialog`,`user_id`) VALUES (NULL, '" . $value['name'] . "', '" . $phone . "', CURRENT_TIMESTAMP, '0', '0', '0', '" . $requestData['user_contact_sinhronizatyion_id'] . "');");
        }
    }
}

// Проверка хэш-кода из объединенных данных
if (empty($requestData['hashcode']) || $requestData['hashcode'] != '745dc9a34fa9a044c8d9b05ab1e847d6') {
    $resp = array(
        "type" => false,
        "msg" => "Key login not found!",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Выполнение метода
if (file_exists($method_file)) {
    try {
        // Передаем объединенные данные в метод
        $_REQUEST = $requestData;
        include($method_file);
    } catch (\Throwable $th) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $th->getMessage(),
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
    $db->close();
    $db2->close();
    $db_docs->close();
} else {
    $db->close();
    $db2->close();
    $db_docs->close();
    $resp = array(
        "type" => false,
        "msg" => "Method not found!",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}



?>