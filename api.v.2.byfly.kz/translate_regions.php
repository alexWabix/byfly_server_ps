<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/methods/translate/vendor/autoload.php');
use Panda\Yandex\TranslateSdk;

include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$oauthToken = 'y0_AgAAAABxUOnKAATuwQAAAAEQxDxCAADAVVzInkZJuobEDBX_ngY_Ras53A';
$cloud = new TranslateSdk\Cloud($oauthToken, 'b1gun83k3qsqg2ena9r9');

$listCountriesDB = $db->query("SELECT * FROM `regions` WHERE title_en=''");
while ($listCountries = $listCountriesDB->fetch_assoc()) {

    $translateKZ = new TranslateSdk\Translate($listCountries['title'], 'kk');
    $translateKZ->setSourceLang('ru')->setTargetLang('kk');
    $dataKZ = $cloud->request($translateKZ);

    $textKZ = json_decode($dataKZ, true)['translations'][0]['text'];


    $translateEN = new TranslateSdk\Translate($listCountries['title'], 'en');
    $translateEN->setSourceLang('ru')->setTargetLang('en');
    $dataEN = $cloud->request($translateEN);

    $textEN = json_decode($dataEN, true)['translations'][0]['text'];

    $db->query("UPDATE regions SET title_kk='" . $textKZ . "', title_en='" . $textEN . "' WHERE id='" . $listCountries['id'] . "'");
}



