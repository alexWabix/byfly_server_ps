<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json');
$query = $db->query("SELECT * FROM atestation WHERE id='6'")->fetch_assoc();
$query['answers_ru'] = json_decode($query['answers_ru'], true);
$query['answers_en'] = json_decode($query['answers_en'], true);
$query['answers_kk'] = json_decode($query['answers_kk'], true);


echo json_encode($query, JSON_UNESCAPED_UNICODE);
