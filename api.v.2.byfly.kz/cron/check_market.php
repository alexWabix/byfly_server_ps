<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$url = 'https://apps.apple.com/us/app/byfly-travel/id6737191624';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Не загружать тело ответа
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode === 200) {
    sendWhatsapp("77780021666", 'Ура!!! Александр приложение опубликовано в AppStore.');
    sendWhatsapp("77059006080", 'Ура!!! Алена приложение опубликовано в AppStore передай пожалуйста Александру. Ну че сука понеслась!');
} else {
    echo 'Нет публикации';
}
?>