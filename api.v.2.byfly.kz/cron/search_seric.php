<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$arrayGroups = [];
$userCounts = [];
$totalGroups = 0;

$listGroupFromSericDB = $db->query("SELECT * FROM `group_dont_byfly` WHERE `search_serik` = 1");
while ($listGroupFromSeric = $listGroupFromSericDB->fetch_assoc()) {
    $chatId = $listGroupFromSeric['chatid'];
    $arrayGroups[$chatId] = [];
    $totalGroups++;

    $url = "https://7103.api.greenapi.com/waInstance7103957708/getGroupData/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";

    $payload = json_encode(["groupId" => $chatId]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    $resp = json_decode($response, true);
    if (!isset($resp['participants'])) {
        continue;
    }

    foreach ($resp['participants'] as $user) {
        $userId = $user['id'];
        $arrayGroups[$chatId][] = $userId;

        if (!isset($userCounts[$userId])) {
            $userCounts[$userId] = 0;
        }
        $userCounts[$userId]++;
    }
}

$usersInAllGroups = array_keys(array_filter($userCounts, function ($count) use ($totalGroups) {
    return $count === $totalGroups;
}));
foreach ($usersInAllGroups as $phone) {
    // Список номеров, которые не нужно обрабатывать
    $excludedNumbers = [
        '77022205088@c.us',
        '77079010041@c.us',
        '77777080808@c.us',
        '77773700772@c.us',
        '777479951387@c.us',
        '77052019563@c.us',
        '77085194866@c.us',
        '77786699999@c.us',
        '77025089335@c.us',
        '77479951387@c.us'
    ];

    if (!in_array($phone, $excludedNumbers)) {
        list($number, $domain) = explode('@', $phone);

        $maskedNumber = '';
        for ($i = 0; $i < strlen($number); $i++) {
            $maskedNumber .= ($i % 2 == 1) ? '*' : $number[$i];
        }

        $phone = explode('@', $phone)[0];

        $searchUserDB = $db->query("SELECT * FROM users WHERE phone='" . $phone . "'");
        if ($searchUserDB->num_rows > 0) {
            $searchUser = $searchUserDB->fetch_assoc();
            echo $phone . ' - <span style="color: green;">' . $searchUser['name'] . ' ' . $searchUser['famale'] . ' ' . $searchUser['surname'] . '</span><br>';
        } else {
            echo $phone . ' - <span style="color: red;">Не зарегистрирован!</span><br>';
        }

    }
}

?>