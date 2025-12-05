<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
include('/var/www/www-root/data/www/api.v.2.byfly.kz/methods/translate/vendor/autoload.php');
use Panda\Yandex\TranslateSdk;

function translate($text, $lang)
{
    try {
        $oauthToken = 'y0_AgAAAABxUOnKAATuwQAAAAEQxDxCAADAVVzInkZJuobEDBX_ngY_Ras53A';
        $cloud = new TranslateSdk\Cloud($oauthToken, 'b1gun83k3qsqg2ena9r9');
        $translate = new TranslateSdk\Translate($text, $lang);
        $translate->setSourceLang('ru')->setTargetLang($lang);
        $data = $cloud->request($translate);
        return json_decode($data, true)['translations'][0]['text'];

    } catch (TranslateSdk\Exception\ClientException | TypeError $e) {
        return null;
    }
}

$listQueriesDB = $db->query("SELECT * FROM `atestation` WHERE `quest_en` = '' AND `quest_kk` = ''");
while ($listQueries = $listQueriesDB->fetch_assoc()) {
    $listQueries['answers_ru'] = json_decode($listQueries['answers_ru'], true);
    $listQueries['answers_kk'] = array();
    $listQueries['answers_en'] = array();

    $listQueries['quest_kk'] = translate($listQueries['quest_ru'], 'kk');
    $listQueries['quest_en'] = translate($listQueries['quest_ru'], 'en');


    foreach ($listQueries['answers_ru'] as $value) {
        $addKk = $value;
        $addKk['text'] = translate($value['text'], 'kk');

        $addEn = $value;
        $addEn['text'] = translate($value['text'], 'en');

        array_push($listQueries['answers_kk'], $addKk);
        array_push($listQueries['answers_en'], $addEn);

    }

    $db->query("UPDATE atestation SET quest_kk='" . $listQueries['quest_kk'] . "', quest_en='" . $listQueries['quest_en'] . "', answers_kk='" . json_encode($listQueries['answers_kk'], JSON_UNESCAPED_UNICODE) . "', answers_en='" . json_encode($listQueries['answers_en'], JSON_UNESCAPED_UNICODE) . "' WHERE id='" . $listQueries['id'] . "'");
}
?>