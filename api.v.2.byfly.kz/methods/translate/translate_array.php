<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/methods/translate/vendor/autoload.php');
use Panda\Yandex\TranslateSdk;
if (empty($_POST['text']) == false && empty($_POST['translate_lang']) == false) {
    $oauthToken = 'y0_AgAAAABxUOnKAATuwQAAAAEQxDxCAADAVVzInkZJuobEDBX_ngY_Ras53A';

    try {
        $cloud = new TranslateSdk\Cloud($oauthToken, 'b1gun83k3qsqg2ena9r9');


        $_POST['text'] = explode(',', $_POST['text']);
        $translate = new TranslateSdk\Translate($_POST['text'][0]);

        foreach (array_slice($_POST['text'], 1, count($_POST['text'])) as $value) {
            $translate->addText($value);
        }

        $translate->setSourceLang($_POST['base_lang'] == null ? 'ru' : $_POST['base_lang'])->setTargetLang($_POST['translate_lang']);
        $data = $cloud->request($translate);
        echo json_encode(
            array(
                "type" => true,
                "data" => json_decode($data, true)['translations'],
            ),
            JSON_UNESCAPED_UNICODE
        );

    } catch (TranslateSdk\Exception\ClientException | TypeError $e) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => $e->getMessage(),
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty text variable...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}

?>