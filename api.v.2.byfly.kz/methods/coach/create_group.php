<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/methods/translate/vendor/autoload.php');
use Panda\Yandex\TranslateSdk;

function translateText($text, $targetLang, $sourceLang = 'ru')
{
    $oauthToken = 'y0_AgAAAABxUOnKAATuwQAAAAEQxDxCAADAVVzInkZJuobEDBX_ngY_Ras53A';
    try {
        $cloud = new TranslateSdk\Cloud($oauthToken, 'b1gun83k3qsqg2ena9r9');
        $translate = new TranslateSdk\Translate($text, $targetLang);
        $translate->setSourceLang($sourceLang)->setTargetLang($targetLang);
        $data = $cloud->request($translate);
        $result = json_decode($data, true);
        return $result['translations'][0]['text'] ?? null;
    } catch (TranslateSdk\Exception\ClientException | TypeError $e) {
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['type' => false, 'msg' => 'Неверный метод запроса']);
    exit;
}

if (!isset($_POST['method']) || $_POST['method'] !== 'coach/create_group') {
    echo json_encode(['type' => false, 'msg' => 'Неверный метод']);
    exit;
}

$requiredFields = [
    'groupName',
    'maxPeople',
    'language',
    'whatsappLink',
    'startDate',
    'endDate',
    'examDate',
    'coach_id_1',
    'franchaise_id'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(['type' => false, 'msg' => "Поле $field не заполнено"]);
        exit;
    }
}

$groupName = trim($_POST['groupName']);
$maxPeople = (int) trim($_POST['maxPeople']);
$language = trim($_POST['language']);
$whatsappLink = trim($_POST['whatsappLink']);
$startDate = date('Y-m-d H:i:s', strtotime(trim($_POST['startDate'])));
$endDate = date('Y-m-d H:i:s', strtotime(trim($_POST['endDate'])));
$examDate = date('Y-m-d H:i:s', strtotime(trim($_POST['examDate'])));
$coachId1 = trim($_POST['coach_id_1']);
$coachId2 = isset($_POST['coach_id_2']) ? trim($_POST['coach_id_2']) : NULL;
$coachId3 = isset($_POST['coach_id_3']) ? trim($_POST['coach_id_3']) : NULL;
$coachId4 = isset($_POST['coach_id_4']) ? trim($_POST['coach_id_4']) : NULL;
$coachId5 = isset($_POST['coach_id_5']) ? trim($_POST['coach_id_5']) : NULL;
$coachId6 = isset($_POST['coach_id_6']) ? trim($_POST['coach_id_6']) : NULL;
$franchaiseId = trim($_POST['franchaise_id']);
$linkZoom = trim($_POST['link_zoom'] ?? '');
$day1Video = trim($_POST['day_1_video'] ?? '');
$day2Video = trim($_POST['day_2_video'] ?? '');
$day3Video = trim($_POST['day_3_video'] ?? '');
$day4Video = trim($_POST['day_4_video'] ?? '');
$day5Video = trim($_POST['day_5_video'] ?? '');
$day6Video = trim($_POST['day_6_video'] ?? '');

// Значения по умолчанию
$priceStart = isset($_POST['price_start']) ? (int) $_POST['price_start'] : 200000;
$priceCoach = isset($_POST['price_coach']) ? (int) $_POST['price_coach'] : 400000;
$priceTravel = isset($_POST['price_travel']) ? (int) $_POST['price_travel'] : 800000;
$cashback = isset($_POST['cash_back']) ? (int) $_POST['cash_back'] : 25;

// SQL-запрос
$query = "INSERT INTO `grouped_coach` 
(`name_grouped_ru`, `name_grouped_kk`, `name_grouped_en`, `coach_id_1`, `coach_id_2`, `coach_id_3`, 
 `coach_id_4`, `coach_id_5`, `coach_id_6`, `lang_groups`, `date_start_coaching`, `date_end_coaching`, 
 `date_validation`, `group_whatsapp`, `franchaise_id`, `max_people`, `link_zoom`, `day_1_video`, `day_2_video`, `day_3_video`, `day_4_video`, `day_5_video`, `day_6_video`, `price_start`,
 `price_coach`, `price_travel`, `template_id`, `cash_back`) 
VALUES 
('$groupName', '" . translateText($groupName, 'kk') . "', '" . translateText($groupName, 'en') . "', '$coachId1', '$coachId2', '$coachId3', 
 '$coachId4', '$coachId5', '$coachId6', '$language', '$startDate', '$endDate', 
 '$examDate', '$whatsappLink', '$franchaiseId', '$maxPeople',
 '$linkZoom', '$day1Video', '$day2Video', '$day3Video', '$day4Video', '$day5Video', '$day6Video', 
 '$priceStart', '$priceCoach', '$priceTravel', '1', '$cashback')";

// Выполнение запроса
if ($db->query($query)) {
    echo json_encode(['type' => true, 'msg' => 'Группа успешно создана']);
} else {
    echo json_encode(['type' => false, 'msg' => 'Ошибка: ' . $db->error]);
}

$db->close();
?>