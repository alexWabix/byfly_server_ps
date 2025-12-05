<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function generateUniqueCode($length = 8)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function generateVouchers($type, $amount, $count, $db)
{
    $uniqueCodes = [];
    $startDate = '2024-12-30';
    $endDate = '2025-01-30';

    while (count($uniqueCodes) < $count) {
        $code = generateUniqueCode();

        // Проверяем уникальность в базе данных
        $checkQuery = "SELECT COUNT(*) as cnt FROM `byfly.2.0`.`vauchers` WHERE `number` = '$code';";
        $result = $db->query($checkQuery);
        $row = $result->fetch_assoc();

        if ($row['cnt'] == 0 && !in_array($code, $uniqueCodes)) {
            $uniqueCodes[] = $code;
            $query = "INSERT INTO `byfly.2.0`.`vauchers` (`id`, `number`, `type`, `summ`, `date_create`, `date_off`) VALUES (NULL, '$code', '$type', '$amount', '$startDate', '$endDate');";
            $db->query($query);
        }
    }
}

// Создание ваучеров
try {
    // Couch ваучеры
    generateVouchers('couch', 400000, 10, $db);

    echo "Ваучеры успешно созданы и добавлены в базу данных.";
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>