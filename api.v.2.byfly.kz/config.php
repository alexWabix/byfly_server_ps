<?php

$domain = 'https://api.v.2.byfly.kz/';
$dir = '/var/www/www-root/data/www/api.v.2.byfly.kz/';
include $dir . "smsc_api.php";

$db_host = 'localhost';
$db_user = 'by_fly';
$db_pass = '2350298awe';
$db_name = 'byfly.2.0';
$db_name_offer = 'by_fly';


$db_docs_user = 'stanislav';
$db_docs_pass = '2350298awe';
$db_docs_name = 'byfly_documents';


$db_print_user = 'byfly_print';
$db_print_pass = '2350298aweA';
$db_print_name = 'byfly_print';


$tourvisor_login = 'byfly.kz@mail.ru';
$tourvisor_password = 'Vbdqj1NxBDyf';
$domainApi = 'https://api.v.2.byfly.kz/';


$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
$db2 = new mysqli($db_host, $db_user, $db_pass, $db_name_offer);
$db_docs = new mysqli($db_host, $db_docs_user, $db_docs_pass, $db_docs_name);
$dbPrint = new mysqli($db_host, $db_print_user, $db_print_pass, $db_print_name);

$db->set_charset("utf8mb4");
$db2->set_charset("utf8mb4");
$db_docs->set_charset("utf8mb4");
$dbPrint->set_charset("utf8mb4");

$genVideoApi = '7dbb21d0520f883568401d49d5b0ad72a1bb790d';

function generatePromoCode($firstName, $lastName, $patronymic, $phoneNumber, $userId)
{
    $namePart = mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1) . mb_substr($patronymic, 0, 1);
    $cyrillicToLatinMap = [
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'kh',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ы' => 'y',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya'
    ];

    $latinNamePart = strtr(mb_strtolower($namePart), $cyrillicToLatinMap);
    $phonePart = substr($phoneNumber, -2);
    $userIdPart = $userId;
    $promoCode = strtoupper($latinNamePart . $phonePart . $userIdPart);
    return $promoCode;
}

function transliterateAndCleanFileName($filename)
{
    $cyrillicToLatin = array(
        'А' => 'A',
        'Б' => 'B',
        'В' => 'V',
        'Г' => 'G',
        'Д' => 'D',
        'Е' => 'E',
        'Ё' => 'E',
        'Ж' => 'Zh',
        'З' => 'Z',
        'И' => 'I',
        'Й' => 'Y',
        'К' => 'K',
        'Л' => 'L',
        'М' => 'M',
        'Н' => 'N',
        'О' => 'O',
        'П' => 'P',
        'Р' => 'R',
        'С' => 'S',
        'Т' => 'T',
        'У' => 'U',
        'Ф' => 'F',
        'Х' => 'Kh',
        'Ц' => 'Ts',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Sch',
        'Ь' => '',
        'Ы' => 'Y',
        'Ъ' => '',
        'Э' => 'E',
        'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'kh',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sch',
        'ь' => '',
        'ы' => 'y',
        'ъ' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya'
    );

    $pathInfo = pathinfo($filename);
    $name = $pathInfo['filename'];
    $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
    $transliteratedName = strtr($name, $cyrillicToLatin);
    $cleanedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $transliteratedName);
    $cleanedName = preg_replace('/_+/', '_', $cleanedName);
    return $cleanedName . $extension;
}

if ($db->connect_error or $db2->connect_error or $db_docs->connect_error) {
    $resp = array(
        "type" => false,
        "msg" => "Error database connection!",
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($lang)) {
    $lang = 'ru';
    if (empty($_POST['lang']) == false) {
        $lang = $_POST['lang'];
    }
}


function getTextTranslate($id)
{
    global $lang;
    global $db;

    $getStringDB = $db->query("SELECT * FROM site_translate WHERE id='" . $id . "'");
    if ($getStringDB->num_rows > 0) {
        $getString = $getStringDB->fetch_assoc();
        return $getString['text_' . $lang];
    } else {
        return '';
    }
}

function sendCall($phone, $text)
{
    global $lang;
    try {
        $url = 'https://api.v.2.byfly.kz/eva/audio_generator.php?text=' . urlencode($text) . '&lang=' . $lang;
        $generateAudio = file_get_contents($url);
        $generateAudio = json_decode($generateAudio, true);
        if ($generateAudio['type']) {


            $data = send_sms($phone, "||", 0, 0, 0, 9, false, "", array($generateAudio['real_path']));
            if ($data) {
                $resp = array(
                    "type" => true,
                    "codes" => 1,
                    "tipical" => 'call',
                    "path" => $generateAudio,
                    "msg" => $data,
                );
                return $resp;
            } else {
                $resp = array(
                    "type" => false,
                    "codes" => 1,
                    "path" => $generateAudio,
                    "msg" => $data,
                );
                return $resp;
            }
        } else {
            $resp = array(
                "type" => false,
                "codes" => 1,
                "msg" => $generateAudio,
            );
            return $resp;
        }
    } catch (\Throwable $th) {
        $resp = array(
            "type" => false,
            "codes" => 1,
            "msg" => $th->getMessage(),
        );
        return $resp;
    }

}

function sendWhatsapp($phone, $text)
{
    $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendMessage/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';

    $data = array(
        'chatId' => $phone . '@c.us',
        'linkPreview' => true,
        'message' => $text
    );

    $options = array(
        'http' => array(
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        )
    );


    $context = stream_context_create($options);
    $responsed = file_get_contents($url, false, $context);
    $response = json_decode($responsed, true);
    if (empty($response) == false) {
        if (empty($response['idMessage']) == false) {
            return array(
                "type" => true,
                "id" => $response['idMessage'],
                "tipical" => 'whatsapp',
            );
        } else {
            return array(
                "type" => false,
                "code" => 1,
                "msg" => "Не удалось отправить сообщение!" . json_encode($responsed, JSON_UNESCAPED_UNICODE),
            );
        }
    } else {
        return array(
            "type" => false,
            "code" => 1,
            "msg" => "Не удалось отправить сообщение!" . json_encode($responsed, JSON_UNESCAPED_UNICODE),
        );
    }
}

function sendWhatsappGroup($group, $text)
{
    $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendMessage/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';

    $data = array(
        'chatId' => $group,
        'linkPreview' => true,
        'message' => $text
    );

    $options = array(
        'http' => array(
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        )
    );


    $context = stream_context_create($options);
    $responsed = file_get_contents($url, false, $context);
    $response = json_decode($responsed, true);
    if (empty($response) == false) {
        if (empty($response['idMessage']) == false) {
            return array(
                "type" => true,
                "id" => $response['idMessage'],
                "tipical" => 'whatsapp',
            );
        } else {
            return array(
                "type" => false,
                "code" => 1,
                "msg" => "Не удалось отправить сообщение!" . json_encode($responsed, JSON_UNESCAPED_UNICODE),
            );
        }
    } else {
        return array(
            "type" => false,
            "code" => 1,
            "msg" => "Не удалось отправить сообщение!" . json_encode($responsed, JSON_UNESCAPED_UNICODE),
        );
    }
}


function getUserParams($id)
{
    global $db;
    $settings = $db->query("SELECT * FROM app_settings WHERE id='1'")->fetch_assoc();
    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $id . "'")->fetch_assoc();
    $getUserTours = $db->query("
        SELECT COUNT(*) as ct 
        FROM order_tours 
        WHERE 
            user_id = '" . $userInfo['id'] . "' AND 
            status_code != '5' AND 
            type != 'test' AND 
            includesPrice > 0
    ")->fetch_assoc()['ct'];

    $getUserToursBeginMonth = $db->query("
        SELECT COUNT(*) as ct 
        FROM order_tours 
        WHERE 
            user_id = '" . $userInfo['id'] . "' AND 
            status_code != '5' AND 
            type != 'test' AND 
            includesPrice > 0 AND 
            date_create >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND
            date_create < DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ")->fetch_assoc()['ct'];

    $isBalance = false;
    if ($userInfo['cash_back_from_money'] == 1) {
        $isBalance = true;
    } else {
        if ($userInfo['orient'] == 'test') {
            $isBalance = false;
        } else {
            if ($userInfo['astestation_bal'] < 92) {
                $isBalance = false;
            } else {
                if ($userInfo['date_create_vozvrat'] == null) {
                    if ($userInfo['blocked_to_time'] == null) {
                        $isBalance = true;
                    } else {
                        $isBalance = false;
                    }
                } else {
                    $isBalance = false;
                }
            }
        }
    }


    $x2 = $getUserToursBeginMonth >= $settings['x2_count_tours'];


    return array(
        "name_user" => $userInfo['famale'] . ' ' . $userInfo['name'] . ' ' . $userInfo['surname'],
        "user_phone" => $userInfo['phone'],
        "avatar" => $userInfo['avatar'],
        "count_tours" => $getUserTours,
        "count_tours_last_month" => $getUserToursBeginMonth,
        "is_balance" => $isBalance,
        "is_blocked" => $userInfo['blocked_to_time'] == null ? false : true,
        "work_lines" => array(
            "line1" => true,
            "line2" => true,
            "line3" => $getUserToursBeginMonth >= $settings['line_1_count_tours'] ? true : $userInfo['user_status'] == 'ambasador' || $userInfo['user_status'] == 'coach' || $userInfo['user_status'] == 'alpha',
            "line4" => $getUserToursBeginMonth >= $settings['line_2_count_tours'] ? true : $userInfo['user_status'] == 'coach' || $userInfo['user_status'] == 'alpha',
            "line5" => $getUserToursBeginMonth >= $settings['line_3_count_tours'] ? true : $userInfo['user_status'] == 'alpha',
        ),
        "percentage" => array(
            "line1" => $x2 ? $settings['percentage_x2_lne_1'] : $settings['percentage_line_1'],
            "line2" => $x2 ? $settings['percentage_x2_lne_2'] : $settings['percentage_line_2'],
            "line3" => $x2 ? $settings['percentage_x2_lne_3'] : $settings['percentage_line_3'],
            "line4" => $x2 ? $settings['percentage_x2_lne_4'] : $settings['percentage_line_4'],
            "line5" => $x2 ? $settings['percentage_x2_lne_5'] : $settings['percentage_line_5'],
        ),
    );
}

function sendWhatsappGroupVideo($group, $videoUrl, $caption)
{
    $url = 'https://7103.api.greenapi.com/waInstance7103957708/sendFileByUrl/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295';

    $data = array(
        'chatId' => $group, // Пример: "77780021666@c.us" или "120363147048828610@g.us"
        'urlFile' => $videoUrl, // Прямая ссылка на видео
        'fileName' => 'send.mp4', // Можно переопределить
        'caption' => $caption
    );

    $options = array(
        'http' => array(
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        )
    );

    $context = stream_context_create($options);
    $responsed = file_get_contents($url, false, $context);
    $response = json_decode($responsed, true);

    if (!empty($response) && !empty($response['idMessage'])) {
        return array(
            "type" => true,
            "id" => $response['idMessage'],
            "tipical" => 'whatsapp',
        );
    } else {
        return array(
            "type" => false,
            "code" => 1,
            "msg" => "Не удалось отправить видео! " . json_encode($responsed, JSON_UNESCAPED_UNICODE),
        );
    }
}

function checkNumberFromWhatsapp($phone)
{
    $url = "https://7103.api.greenapi.com/waInstance7103957708/checkWhatsapp/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";
    $payload = json_encode([
        "phoneNumber" => $phone
    ]);

    $headers = [
        'Content-Type: application/json',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);
    if (empty($response) == false) {
        if ($response['existsWhatsapp']) {
            return array(
                "type" => true,
            );
        } else {
            return array(
                "type" => false,
                "code" => 2,
                "msg" => "На указанном номере не установлен Whatsapp...",
            );
        }

    } else {
        return array(
            "type" => false,
            "code" => 1,
            "msg" => "Не удалось проверить наличие Whatsapp на указанном номере...",
        );
    }
}


function sendCode($text, $textAudio, $phone)
{
    global $db;
    $checkWhatsApp = checkNumberFromWhatsapp($phone);
    if ($checkWhatsApp['type']) {
        $sendFunc = sendWhatsapp($phone, $text);
        if (!$sendFunc['type']) {
            $sendFunc = sendCall($phone, $textAudio);
        } else {
            $sendFunc = sendCall($phone, $textAudio);
        }
    } else {
        $sendFunc = sendCall($phone, $textAudio);
    }

    return $sendFunc;
}


function getMinPriceToCountryAndRegion($countryId, $regionId = null)
{
    global $db;
    $minPrice = 0;
    $minPriceHot = 0;
    $selectedPrice = 0;
    if ($regionId != null) {
        $minPriceSearchedDB = $db->query("SELECT * FROM tours_searched_details WHERE countrycode='" . $countryId . "' AND regioncode='" . $regionId . "' ORDER BY price ASC LIMIT 1");
    } else {
        $minPriceSearchedDB = $db->query("SELECT * FROM tours_searched_details WHERE countrycode='" . $countryId . "' ORDER BY price ASC LIMIT 1");
    }

    if ($minPriceSearchedDB->num_rows > 0) {
        $minPriceSearched = $minPriceSearchedDB->fetch_assoc();
        $minPrice = $minPriceSearched['price'];
    }

    if ($regionId != null) {
        $minPriceHotTourseDB = $db->query("SELECT * FROM hot_tours_searched WHERE countrycode='" . $countryId . "' AND regioncode='" . $regionId . "' ORDER BY price ASC LIMIT 1");
    } else {
        $minPriceHotTourseDB = $db->query("SELECT * FROM hot_tours_searched WHERE countrycode='" . $countryId . "' ORDER BY price ASC LIMIT 1");
    }

    if ($minPriceHotTourseDB != null && $minPriceHotTourseDB->num_rows > 0) {
        $minPriceHotTourse = $minPriceHotTourseDB->fetch_assoc();
        $minPriceHot = $minPriceHotTourse['price'];
    }

    if ($minPrice > 0 and $minPriceHot > 0) {
        if ($minPrice > $minPriceHot) {
            $selectedPrice = $minPriceHot;
        } else {
            $selectedPrice = $minPrice;
        }
    } else {
        if ($minPrice == 0) {
            if ($minPriceHot == 0) {
                $selectedPrice = 0;
            } else {
                $selectedPrice = $minPriceHot;
            }
        } else {
            $selectedPrice = $minPrice;
        }
    }

    return $selectedPrice;
}

function adminNotification($msg)
{
    global $db;
    $db->query("INSERT INTO error_logs (`id`, `text`, `date_create`) VALUES (NULL, '" . $msg . "', CURRENT_TIMESTAMP);");
}


function infoUserId($id, $dateFrom = null, $dateTo = null)
{
    global $db;

    $id = (int) $id;
    if (!$id)
        return null;

    // Валидация и нормализация дат
    $currentMonthFirstDay = date('Y-m-01 00:00:00');
    $currentMonthLastDay = date('Y-m-t 23:59:59');

    try {
        $dateFromValid = $dateFrom
            ? (new DateTime($dateFrom))->format('Y-m-d 00:00:00')
            : $currentMonthFirstDay;

        $dateToValid = $dateTo
            ? (new DateTime($dateTo))->format('Y-m-d 23:59:59')
            : $currentMonthLastDay;

        if (strtotime($dateFromValid) > strtotime($dateToValid)) {
            throw new Exception('Invalid date range');
        }
    } catch (Exception $e) {
        $dateFromValid = $currentMonthFirstDay;
        $dateToValid = $currentMonthLastDay;
    }

    // Основной запрос данных пользователя
    $stmt = $db->prepare("SELECT 
        u.*,
        COUNT(ot.id) AS count_tours,
        SUM(ot.status_code = 0) AS count_tours_start,
        SUM(ot.status_code = 5) AS count_tours_cancle,
        SUM(ot.status_code = 3) AS count_tours_await,
        SUM(ot.status_code = 4) AS count_tours_flight,
        SUM(ot.status_code IN (1, 2)) AS count_tours_await_pay,
        SUM(ot.status_code = 200) AS count_tours_sended,
        COALESCE(SUM(ot.includesPrice), 0) AS summ_orders,
        EXISTS(SELECT 1 FROM copilka_ceils WHERE user_id = u.id) AS has_savings
        FROM users u
        LEFT JOIN order_tours ot ON ot.user_id = u.id 
            AND ot.date_create BETWEEN ? AND ?
        WHERE u.id = ?
        GROUP BY u.id");

    $stmt->bind_param("ssi", $dateFromValid, $dateToValid, $id);
    $stmt->execute();

    if (!$result = $stmt->get_result())
        return null;
    if ($result->num_rows === 0)
        return null;

    $user = $result->fetch_assoc();

    // Приведение типов
    $intFields = [
        'count_tours',
        'count_tours_start',
        'count_tours_cancle',
        'count_tours_await',
        'count_tours_flight',
        'count_tours_await_pay',
        'count_tours_sended'
    ];

    foreach ($intFields as $field) {
        $user[$field] = (int) ($user[$field] ?? 0);
    }

    $user['summ_orders'] = (float) ($user['summ_orders'] ?? 0);
    $user['has_savings'] = (bool) $user['has_savings'];

    // Получение связанных данных
    $toursStmt = $db->prepare("SELECT * FROM order_tours 
        WHERE user_id = ? AND date_create BETWEEN ? AND ?");
    $toursStmt->bind_param("iss", $id, $dateFromValid, $dateToValid);
    $toursStmt->execute();
    $user['tours'] = $toursStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $savingsStmt = $db->prepare("SELECT * FROM copilka_ceils WHERE user_id = ?");
    $savingsStmt->bind_param("i", $id);
    $savingsStmt->execute();
    $user['copilka_ceils'] = $savingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $user['date_filter'] = [
        'from' => $dateFromValid,
        'to' => $dateToValid
    ];

    return $user;
}

function sortUsers(&$users)
{
    usort($users, static function ($a, $b) {
        return ($b['count_tours'] <=> $a['count_tours']) ?:
            ($b['is_active'] <=> $a['is_active']) ?:
            ($b['has_savings'] <=> $a['has_savings']) ?:
            (strtotime($b['last_visit']) <=> strtotime($a['last_visit']));
    });
}


function getUsersFromLine($line, $rootId, $processedUsers = [], $dateStart = null, $dateTo = null)
{
    global $db;

    if ($line < 1 || $line > 5)
        return [];

    $currentLevel = 1;
    $parentIds = [$rootId];
    $users = [];

    while ($currentLevel <= $line && $parentIds) {
        $placeholders = implode(',', array_fill(0, count($parentIds), '?'));
        $stmt = $db->prepare("SELECT id FROM users 
            WHERE parent_user IN ($placeholders) AND id != parent_user");

        $types = str_repeat('i', count($parentIds));
        $stmt->bind_param($types, ...$parentIds);
        $stmt->execute();

        $currentLevelUsers = array_column($stmt->get_result()->fetch_all(), 0);

        if ($currentLevel === $line) {
            foreach ($currentLevelUsers as $userId) {
                if (!isset($processedUsers[$userId])) {
                    if ($user = infoUserId($userId, $dateStart, $dateTo)) {
                        $users[] = $user;
                        $processedUsers[$userId] = true;
                    }
                }
            }
            break;
        }

        $parentIds = $currentLevelUsers;
        $currentLevel++;
    }

    sortUsers($users);
    return $users;
}

function getCoachedUsers($rootId, $dateStart = null, $dateTo = null)
{
    global $db;

    // Проверка статуса пользователя
    $rootStatus = $db->query("SELECT user_status FROM users WHERE id = $rootId")->fetch_assoc()['user_status'] ?? '';
    if (!in_array($rootStatus, ['agent', 'ambasador', 'coach', 'alpha'])) {
        return [];
    }

    // Получаем всю иерархию до 5 уровней без RECURSIVE
    $allUsers = [];
    $parentIds = [$rootId];
    $level = 1;

    while ($level <= 5 && !empty($parentIds)) {
        $placeholders = implode(',', array_fill(0, count($parentIds), '?'));
        $query = "SELECT id, parent_user, user_status FROM users WHERE parent_user IN ($placeholders) AND id != parent_user";
        $stmt = $db->prepare($query);
        $types = str_repeat('i', count($parentIds));
        $stmt->bind_param($types, ...$parentIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $currentLevelUsers = [];
        while ($row = $result->fetch_assoc()) {
            $allUsers[$row['id']] = $row;
            $currentLevelUsers[] = $row['id'];
        }

        $parentIds = $currentLevelUsers;
        $level++;
    }

    // Фильтрация подопечных
    $coachedUsers = [];
    foreach ($allUsers as $userId => $user) {
        $isCoached = true;
        $currentId = $user['parent_user'];

        // Проверяем цепочку родителей
        while ($currentId != $rootId && $currentId != 0) {
            if (!isset($allUsers[$currentId])) {
                $parentStatus = $db->query("SELECT user_status FROM users WHERE id = $currentId")->fetch_assoc()['user_status'] ?? '';
                if (in_array($parentStatus, ['agent', 'ambasador', 'coach', 'alpha'])) {
                    $isCoached = false;
                    break;
                }
                break;
            } else {
                if (in_array($allUsers[$currentId]['user_status'], ['agent', 'ambasador', 'coach', 'alpha'])) {
                    $isCoached = false;
                    break;
                }
                $currentId = $allUsers[$currentId]['parent_user'];
            }
        }

        if ($isCoached) {
            $userInfo = infoUserId($userId, $dateStart, $dateTo);
            if ($userInfo) {
                $coachedUsers[] = $userInfo;
            }
        }
    }

    sortUsers($coachedUsers);
    return $coachedUsers;
}

function getUserComandsInfo($id, $dateStart = null, $dateTo = null)
{
    global $db;

    // Валидация дат
    $currentMonthFirstDay = date('Y-m-01');
    $currentMonthLastDay = date('Y-m-t 23:59:59');

    try {
        $dateStartValid = $dateStart
            ? (new DateTime($dateStart))->format('Y-m-d')
            : $currentMonthFirstDay;

        $dateToValid = $dateTo
            ? (new DateTime($dateTo))->modify('+1 day -1 second')->format('Y-m-d H:i:s')
            : $currentMonthLastDay;
    } catch (Exception $e) {
        $dateStartValid = $currentMonthFirstDay;
        $dateToValid = $currentMonthLastDay;
    }

    $userInfo = infoUserId($id, $dateStartValid, $dateToValid);
    if (!$userInfo)
        return null;

    // Получение данных по линиям
    $lines = [];
    for ($i = 1; $i <= 5; $i++) {
        $lines[$i] = getUsersFromLine($i, $id, [], $dateStartValid, $dateToValid);
    }

    // Расчет показателей
    $tovarooborot = [];
    foreach ($lines as $lineNum => $users) {
        $tovarooborot["line$lineNum"] = array_sum(array_column($users, 'summ_orders'));
    }

    $dohodes = [
        "line1" => ceil($tovarooborot['line1'] * 0.01),
        "line2" => ceil($tovarooborot['line2'] * 0.003),
        "line3" => ceil($tovarooborot['line3'] * 0.002),
        "line4" => ceil($tovarooborot['line4'] * 0.002),
        "line5" => ceil($tovarooborot['line5'] * 0.002),
    ];

    $dohodes['summ'] = array_sum($dohodes);

    $kNachisleniyu = [
        "line1" => ceil($dohodes['line1']),
        "line2" => ceil($dohodes['line2']),
        "line3" => ceil(($userInfo['count_orders_stay'] >= 5) ? $dohodes['line3'] : 0),
        "line4" => ceil(($userInfo['count_orders_stay'] >= 10) ? $dohodes['line4'] : 0),
        "line5" => ceil(($userInfo['count_orders_stay'] >= 15) ? $dohodes['line5'] : 0),
    ];

    $kNachisleniyu['summ'] = array_sum($kNachisleniyu);

    return [
        "userInfo" => $userInfo,
        "dohodes" => $dohodes,
        "nachislenie" => $kNachisleniyu,
        "userLines1" => $lines[1],
        "userLines2" => $lines[2],
        "userLines3" => $lines[3],
        "userLines4" => $lines[4],
        "userLines5" => $lines[5],
        "coachedUsers" => getCoachedUsers($id, $dateStartValid, $dateToValid),
    ];
}

?>