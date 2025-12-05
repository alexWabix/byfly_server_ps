<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$json = json_decode(file_get_contents('https://api.v.2.byfly.kz/cron/ateestation/test.json'), true);
foreach ($json as $query) {
    $db->query("INSERT INTO atestation (`id`, `quest_ru`, `quest_en`, `quest_kk`, `answers_ru`, `answers_kk`, `answers_en`) 
    VALUES (NULL, '" . $query['question'] . "', '" . $query['question'] . "', '" . $query['question'] . "', '" . json_encode($query['answers'], JSON_UNESCAPED_UNICODE) . "', '" . json_encode($query['answers'], JSON_UNESCAPED_UNICODE) . "', '" . json_encode($query['answers'], JSON_UNESCAPED_UNICODE) . "');");
}
?>