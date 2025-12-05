<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$apiKey = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiNGUyMmNkMzg4OGFjZmQ4YzY5MTc3ZDk3ZDM5NTkwOTk3OTgyMGI1YjIwZjkxZDc4ZGY1YTM3MzVhZDJiODEwZTUxZmFjNWY4ZDlkMjM4MTEiLCJpYXQiOjE3Mzk5Njg2ODguOTU0NzE1LCJuYmYiOjE3Mzk5Njg2ODguOTU0NzE3LCJleHAiOjQ4OTU2NDIyODguOTMyMDQ1LCJzdWIiOiI3MTEwMTAxMyIsInNjb3BlcyI6WyJ1c2VyLnJlYWQiLCJ1c2VyLndyaXRlIiwidGFzay5yZWFkIiwidGFzay53cml0ZSIsIndlYmhvb2sucmVhZCIsIndlYmhvb2sud3JpdGUiLCJwcmVzZXQucmVhZCIsInByZXNldC53cml0ZSJdfQ.nZFzJZUnQsTy7ge_CfQu0rA0ecp62cABjhTIsesM2ioOoEOIC11TVsreKSwpv5tXIr7tkRD3b4h0Sb2W72WVwCeeiySKMTfXqRVpRX5bpbw1P-3IksUITXggr0zAkqsvCGZ2oH6G_E_Kidt7Ewl4yoIRJZIqwwh0Kmab8ZXPgEjZdXhFG9ACtYjq77qPoTzv0p8UBQcAE6vE1KhHdeLFEvm_gRDxs4EmaFZmPN2HpamyvzCWBLSz6jz3greC7AcRctbxHuWgB5qGOV7bQ59AHUHmM5cB-1dxY1HNf6CO9oElDCVLIP3_mTpEQX1nMkuafeOTI5GHqpyjqsCRGrlzcLwLnZMmYNZFwtPcub_I__MZ5UDcuM7RLlNnHFG8SW5HOJh7KxLULDTBqOB45_Fq5nPKkGkKIygUU2DKRrTaiyc_swiAMoMD1W2K7AGZ6StX4CAU8xZxSAJGjvmus2qFO0jyTbV7v2vl7ibNyZLvxBuf7gJUhRomF21kd6qiW2mDtOzBhktWGxTGJhdGFJj5YfRwAWUIAkBpo7-BAIK_VWf21lKYEHHpLZZsbjJnwCCfCNsR4TsGFsX9LxtXxNZhwsBwdIdij0-QhOY4Ye96SH4aYq2TGdhibcuLr1awFE5tW2SUxtvG7mzUuI7RJ9Gq373d7wuZ_PJfqjl5csQSQ_A";


if (empty($_GET['user_id']) == false) {
    if (empty($_GET['template']) == false) {
        $searchUserDb = $db->query("SELECT * FROM users WHERE id='" . $_GET['user_id'] . "'");
        if ($searchUserDb->num_rows > 0) {
            $user = $searchUserDb->fetch_assoc();
            $name = $user['name'];
            if ($user['user_status'] != 'user') {
                $getTemplateHTML = file_get_contents('template/' . $_GET['template'] . '.html');
                $phone = $user['phone'];
                $formattedPhone = preg_replace('/(\d)(\d{3})(\d{3})(\d{2})(\d{2})/', '+$1 $2 $3 $4 $5', $phone);
                $status = 'Агент компании';

                if ($user['user_status'] == 'abmasador') {
                    $status = 'Амбасадор';
                } else if ($user['user_status'] == 'coach') {
                    $status = 'Коуч компании';
                } else if ($user['user_status'] == 'alpha') {
                    $status = 'Топ лидер';
                }
                if ($_GET['template'] == 'buklet_big') {
                    $getTemplateHTML = str_replace('{{phone}}', '<div style="width: 1280px;margin-left: -520px;text-align: center;text-overflow: ellipsis;overflow: hidden;">' . $formattedPhone . '</div>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{adres}}', '<div style="width: 1280px;margin-left: -520px;text-align: center;text-overflow: ellipsis;overflow: hidden;">' . $user['adress'] . '</div>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $referBonus = $user['refer_registration_bonus'];
                    $formattedBonus = number_format($referBonus, 0, '.', ' ') . ' ₸';
                    $getTemplateHTML = str_replace('{{refer_bonus}}', $formattedBonus, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{qr}}', '<div style="border-radius: 30px; width: 350px; height: 350px;  position: absolute; z-index:999999999; overflow: hidden; margin-top: -70px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%;  position: absolute; z-index:999999999;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{qr_big}}', '<div style="border-radius: 30px; width: 830px; height: 830px; position: absolute; z-index:999999999; overflow: hidden; margin-top: -50px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%;  position: absolute; z-index:999999999;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 0.80, 'portrait', 62.9, 29.7, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'buklet_miny') {

                    $referBonus = $user['refer_registration_bonus'];
                    $formattedBonus = number_format($referBonus, 0, '.', ' ') . ' ₸';
                    $getTemplateHTML = str_replace('{{REFERBONUS}}', $formattedBonus, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{phone}}', $formattedPhone, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{adres}}', '<div style="max-width: 700px;overflow: auto;text-overflow: clip;text-wrap-mode: wrap;margin-top: -60px;">' . $user['adress'] . '</div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{QR}}', '<div style="border-radius: 30px; width: 330px; height: 330px; overflow: hidden; margin-top: -40px; margin-left: -10px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 0.73, 'landscape', 21.0, 27.17, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'cards_1') {
                    if (empty($user['avatar'])) {
                        $getTemplateHTML = str_replace('{{avatar}}', '<div style="border-radius: 100%; border: solid 10px red; position: absolute; z-index: 9999999999; width: 230px; height: 230px; overflow: hidden; margin-top: -40px; margin-left: -10px;padding: 0px; padding-bottom: 0px; background-image: url(' . "'https://api.v.2.byfly.kz/genrate_print/template/no_ava.png'" . '); background-size: cover;background-position: center center;"></div>', $getTemplateHTML);
                        $getTemplateHTML = str_replace('{{qr}}', '<div style="border-radius: 10px;z-index: 99999;position: absolute;width: 110px;height: 110px;overflow: hidden;margin-top: -40px;padding: 0px;padding-bottom: 0px;"><img style="height: 100%;margin-top: 100px;position: absolute; z-index: 999999999999;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    } else {
                        $getTemplateHTML = str_replace('{{avatar}}', '<div style="border-radius: 100%; border: solid 10px red; position: absolute; z-index: 9999999999; width: 230px; height: 230px; overflow: hidden; margin-top: -40px; margin-left: -10px;padding: 0px; padding-bottom: 0px; background-image: url(' . "'" . $user['avatar'] . "'" . '); background-size: cover; background-position: center center;"></div>', $getTemplateHTML);
                        $getTemplateHTML = str_replace('{{qr}}', '<div style="border-radius: 10px;z-index: 99999;position: absolute;width: 110px;height: 110px;overflow: hidden;margin-top: -40px;padding: 0px;padding-bottom: 0px;"><img style="height: 100%;margin-top: 100px; position: absolute; z-index: 999999999999;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    }

                    $getTemplateHTML = str_replace('{{adres}}', $user['adress'], $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{phone}}', $formattedPhone, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{email}}', $user['email'], $getTemplateHTML);


                    $getTemplateHTML = str_replace('{{famale_name}}', '<div style=" overflow: hidden;margin-top: -20px; font-size: 16px; font-weight: bold;margin-left: -40px;width: 270px; text-align: center;text-overflow: ellipsis;">' . $user['famale'] . ' ' . $user['name'] . '</div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{status}}', '<div style="overflow: hidden;margin-top: -25px;margin-left: -75px;width: 225px;text-align: center;text-overflow: ellipsis;">' . $status . '</div>', $getTemplateHTML);

                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 1, 'portrait', 9.33, 5.5, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'cards_2') {
                    $avatarStyle = "border-radius: 100%;position: relative; z-index: -20;width: 330px;height: 330px;overflow: hidden;margin-top: -180px;margin-left: -75px;padding: 0;padding-bottom: 0;background-size: cover;background-position: center center;";
                    $qrStyle = "border-radius: 10px;z-index: 99999;position: absolute;width: 200px;height: 200px;overflow: hidden;margin-top: -30px;padding: 0px;padding-bottom: 0px;";
                    if (empty($user['avatar'])) {
                        $getTemplateHTML = str_replace('{{avatar}}', '<div style="' . $avatarStyle . 'background-image: url(' . "'https://api.v.2.byfly.kz/genrate_print/template/no_ava.png'" . ');"></div>', $getTemplateHTML);
                        $getTemplateHTML = str_replace('{{qr}}', '<div style="' . $qrStyle . '"><img style="width: 100%;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    } else {
                        $getTemplateHTML = str_replace('{{avatar}}', '<div style="' . $avatarStyle . 'background-image: url(' . "'" . $user['avatar'] . "'" . ');"></div>', $getTemplateHTML);
                        $getTemplateHTML = str_replace('{{qr}}', '<div style="' . $qrStyle . '"><img style="width: 100%;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    }

                    $getTemplateHTML = str_replace('{{adres}}', '<div style="margin-top: -25px; width: 340px;text-wrap-mode: wrap;">' . $user['adress'] . '</div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{phone}}', $formattedPhone, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{email}}', $user['email'], $getTemplateHTML);


                    $getTemplateHTML = str_replace('{{name_famale}}', '<div style="overflow: hidden;margin-top: -30px;margin-left: -40px;width: 340px;text-align: center;text-overflow: ellipsis;font-family: sans-serif;font-weight: bold;font-size: 30px;">' . $user['famale'] . ' ' . $user['name'] . '</div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{user_status}}', '<div style="overflow: hidden;margin-top: -25px;margin-left: -100px;width: 300px;text-align: center;text-overflow: ellipsis;">' . $status . '</div>', $getTemplateHTML);

                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 1, 'portrait', 5.5, 9.33, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'cards_3') {
                    $qrStyle = "border-radius: 10px;z-index: 99999;position: absolute;width: 270px;height: 270px;overflow: hidden;margin-top: -30px;padding: 0px;padding-bottom: 0px;";
                    $getTemplateHTML = str_replace('{{qr}}', '<div style="' . $qrStyle . '"><img style="width: 100%;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{adres}}', '<div style="margin-top: -25px; width: 340px;text-wrap-mode: wrap;">' . $user['adress'] . '</div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{phone}}', $formattedPhone, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{email}}', $user['email'], $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{web_suite}}', 'www.byfly.kz', $getTemplateHTML);


                    $getTemplateHTML = str_replace('{{famale_name}}', '<div style="overflow: hidden;margin-top: -35px;margin-left: -13px;max-width: 310px;min-width: 250px;text-align: left;text-overflow: ellipsis;font-family: sans-serif;font-weight: bold;font-size: 20px;padding: 15px 20px;border-radius: 5px;background: linear-gradient(45deg, red, darkred);">' . $user['famale'] . ' ' . $user['name'] . '</div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{user_status}}', '<div style="overflow: hidden;margin-top: -25px;margin-left: -100px;width: 300px;text-align: center;text-overflow: ellipsis;">' . $status . '</div>', $getTemplateHTML);

                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 1, 'portrait', 9.33, 5.5, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'journal') {
                    $getTemplateHTML = str_replace('{{phone}}', '<div style="width: 1660px;margin-left: -740px; margin-top: -100px;text-align: center;text-overflow: ellipsis;overflow: hidden;">' . $formattedPhone . '</div>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{adres}}', '<div style="width: 1660px;margin-left: -740px; margin-top: -100px;text-align: center;text-overflow: ellipsis;overflow: hidden;">' . $user['adress'] . '</div>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $referBonus = $user['refer_registration_bonus'];
                    $formattedBonus = number_format($referBonus, 0, '.', ' ') . ' ₸';
                    $getTemplateHTML = str_replace('{{refer_bonus}}', $formattedBonus, $getTemplateHTML);
                    $qr = generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light');
                    $getTemplateHTML = str_replace('{{QR_STRONG}}', '<div style="border-radius: 30px; width: 400px; position: absolute; z-index: 9999999999; padding:0px; height: 400px; overflow: hidden; margin-top: -70px; margin-left: -10px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%; position: absolute; z-index: 999999999999;margin: 0px; padding: 0px;" src="' . $qr . '"></div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{QR_BIG}}', '<div style="border-radius: 30px; width: 650px; position: absolute; z-index: 99999999999; padding:0px; height: 650px; overflow: hidden; margin-top: -85px; margin-left: -10px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%;position: absolute; z-index: 999999999999;margin: 0px; padding: 0px;" src="' . $qr . '"></div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{QR_MINY}}', '<div style="border-radius: 20px; width: 305px; position: absolute; z-index: 9999999999; padding:0px; height: 305px; overflow: hidden; margin-top: -55px; margin-left: -7px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%; position: absolute; z-index: 999999999999;margin: 0px; padding: 0px;" src="' . $qr . '"></div>', $getTemplateHTML);


                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 0.79, 'portrait', 29.7, 21.0, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );

                    exit;
                } else if ($_GET['template'] == 'listed') {
                    $getTemplateHTML = str_replace('{{PHONE}}', $formattedPhone, $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{NAME}}', $user['name'], $getTemplateHTML);



                    $getTemplateHTML = str_replace('{{adres}}', '<div style="width: 1660px;margin-left: -740px; margin-top: -100px;text-align: center;text-overflow: ellipsis;overflow: hidden;">' . $user['adress'] . '</div>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{promocode}}', $user['promo_code'], $getTemplateHTML);
                    $referBonus = $user['refer_registration_bonus'];
                    $formattedBonus = number_format($referBonus, 0, '.', ' ') . ' ₸';
                    $getTemplateHTML = str_replace('{{refer_bonus}}', $formattedBonus, $getTemplateHTML);

                    $whatsGroupUser = $db->query("SELECT * FROM user_whatsapp_groups WHERE user_id='" . $user['id'] . "' ORDER BY RAND() LIMIT 1");
                    if ($whatsGroupUser->num_rows > 0) {
                        $whatsGroupUser = $whatsGroupUser->fetch_assoc();
                    }


                    $getTemplateHTML = str_replace('{{QR_WHATS}}', '<div style="border-radius: 10px; width: 175px; height: 175px; overflow: hidden; margin-top: -25px; margin-left: 2px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?group=' . $whatsGroupUser['group_link'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{QR_MINY}}', '<div style="border-radius: 10px; width: 175px; height: 175px; overflow: hidden; margin-top: -197px; margin-left: 787px;padding: 0px; background-color: red; padding-bottom: 0px;"><img style="width: 100%;margin: 0px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=20&color=light') . '"></div>', $getTemplateHTML);

                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 0.79, 'portrait', 21, 9.33, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'magnits') {
                    $getTemplateHTML = str_replace('{{phone}}', "<span style='font-family: sans-serif;'>" . $formattedPhone . "</span>", $getTemplateHTML);


                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 0.33, 'portrait', 21, 10.5, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'magnits_car') {
                    $qrStyle = "border-radius: 60px;z-index: 99999;position: absolute;width: 1710px;height: 1710px;margin-right: 20px;overflow: hidden;margin-top: -273px;padding: 0px;padding-bottom: 0px;";
                    $getTemplateHTML = str_replace('{{qr}}', '<div style="' . $qrStyle . '"><img style="width: 100%;margin: 0px; padding: 0px; position: absolute; z-index: 99999999;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=15&color=light') . '"></div>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{phone}}', $formattedPhone, $getTemplateHTML);
                    $referBonus = $user['refer_registration_bonus'];
                    $formattedBonus = number_format($referBonus, 0, '.', ' ') . ' ₸';
                    $getTemplateHTML = str_replace('{{refer_bonus}}', $formattedBonus, $getTemplateHTML);


                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 0.4, 'portrait', 21, 9.32, 'print');

                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;
                } else if ($_GET['template'] == 'ads') {
                    $getTemplateHTML = str_replace('{{phone}}', '<span style="color: black;font-family: sans-serif; font-weight: bold;">' . $formattedPhone . '</span>', $getTemplateHTML);
                    $referBonus = $user['refer_registration_bonus'];
                    $formattedBonus = number_format($referBonus, 0, '.', ' ') . ' ₸';
                    $getTemplateHTML = str_replace('{{refer_bonus}}', $formattedBonus, $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{name}}', '<span style="color: black;font-family: sans-serif; font-weight: bold;">' . $name . '</span>', $getTemplateHTML);
                    $getTemplateHTML = str_replace('{{promocode}}', '<span style="color: black;font-family: sans-serif; ">' . $user['promo_code'] . '</span>', $getTemplateHTML);

                    $getTemplateHTML = str_replace('{{qr}}', '<img style="width: 400px;margin-top: 20px; padding: 0px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?data=' . $user['id'] . '&size=260&padding=15&color=light') . '">', $getTemplateHTML);



                    $whatsGroupUser = $db->query("SELECT * FROM user_whatsapp_groups WHERE user_id='" . $user['id'] . "' ORDER BY RAND() LIMIT 1");
                    if ($whatsGroupUser->num_rows > 0) {
                        $whatsGroupUser = $whatsGroupUser->fetch_assoc();
                        $getTemplateHTML = str_replace('{{qr_whats}}', '<img style="margin: 0px;padding: 0px;width: 400px;" src="' . generateQR('https://api.v.2.byfly.kz/genrate_print/generate_qr.php?group=' . $whatsGroupUser['group_link']) . '">', $getTemplateHTML);
                    }




                    $pdfLink = convertHtmlToPdf($apiKey, $getTemplateHTML, 1, 'landscape', 21.0, 29.7, 'print');


                    echo json_encode(
                        array(
                            "type" => true,
                            "link" => $pdfLink['result']['files'][0]['url'],
                        ),
                        JSON_UNESCAPED_UNICODE,
                    );
                    exit;

                }



            } else {
                echo json_encode(
                    array(
                        "type" => false,
                        "msg" => "Пользователю не доступна генерация шаблонов!"
                    ),
                    JSON_UNESCAPED_UNICODE,
                );
            }
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => "Пользователь не найден!"
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Нет данных для генерации"
            ),
            JSON_UNESCAPED_UNICODE,
        );
    }
} else {
    echo json_encode(
        array(),
        JSON_UNESCAPED_UNICODE,
    );
}


function generateQR($url)
{
    $qrUrl = $url;

    $qrImage = file_get_contents($qrUrl);
    if ($qrImage !== false) {
        $base64Qr = 'data:image/png;base64,' . base64_encode($qrImage);
        return $base64Qr;
    } else {
        return null;
    }
}


function convertHtmlToPdf($apiKey, $htmlContent, $zoom = 0.82, $orientation = 'landscape', $width = null, $height = null, $type = 'screen')
{
    $timestamp = date('Ymd_His'); // Уникальный идентификатор на основе даты и времени
    $tempFile = __DIR__ . "/$timestamp.html";
    file_put_contents($tempFile, $htmlContent);

    $settings = [
        "operation" => "convert",
        "input_format" => "html",
        "output_format" => "pdf",
        "engine" => "chrome",
        "input" => ["import_$timestamp"],
        "zoom" => $zoom,
        "page_orientation" => $orientation,
        "print_background" => true,
        "display_header_footer" => false,
        "wait_until" => "networkidle0",
        "css_media_type" => $type,
    ];

    if ($width != null && $height != null) {
        $settings['page_width'] = $width;
        $settings['page_height'] = $height;
    }

    $jobPayload = json_encode([
        "tasks" => [
            "import_$timestamp" => [
                "operation" => "import/upload"
            ],
            "convert_$timestamp" => $settings,
            "export_$timestamp" => [
                "operation" => "export/url",
                "input" => ["convert_$timestamp"]
            ]
        ]
    ]);

    $ch = curl_init("https://api.cloudconvert.com/v2/jobs");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jobPayload);
    $jobResponse = curl_exec($ch);
    curl_close($ch);

    $jobData = json_decode($jobResponse, true);
    if (!isset($jobData['data']['id'])) {
        unlink($tempFile);
        die("Ошибка создания задания: " . $jobResponse);
    }

    $jobId = $jobData['data']['id'];

    // 2. Получаем данные для загрузки файла
    $uploadTask = null;
    foreach ($jobData['data']['tasks'] as $task) {
        if ($task['operation'] === "import/upload") {
            $uploadTask = $task;
            break;
        }
    }

    if (!$uploadTask || !isset($uploadTask['result']['form']['url'])) {
        unlink($tempFile);
        die("Ошибка: Не удалось получить URL для загрузки.");
    }

    $uploadUrl = $uploadTask['result']['form']['url'];
    $uploadParams = $uploadTask['result']['form']['parameters'];

    // 3. Загружаем HTML-файл
    $postFields = [];
    foreach ($uploadParams as $key => $value) {
        $postFields[$key] = $value;
    }
    $postFields['file'] = new CURLFile($tempFile, "text/html", basename($tempFile));

    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $uploadResponse = curl_exec($ch);
    curl_close($ch);

    unlink($tempFile);
    $maxWaitTime = 60; // Максимальное время ожидания (секунды)
    $waitInterval = 2; // Интервал проверки (секунды)
    $elapsedTime = 0;
    $downloadUrl = null;

    while ($elapsedTime < $maxWaitTime) {
        sleep($waitInterval);
        $elapsedTime += $waitInterval;

        $ch = curl_init("https://api.cloudconvert.com/v2/jobs/$jobId");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $apiKey"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $statusResponse = curl_exec($ch);
        curl_close($ch);

        $statusData = json_decode($statusResponse, true);
        if (!$statusData || !isset($statusData['data']['status'])) {
            die("Ошибка при получении статуса: " . $statusResponse);
        }

        if ($statusData['data']['status'] === "finished") {
            foreach ($statusData['data']['tasks'] as $task) {
                if ($task['operation'] === "export/url" && isset($task['result']['files'][0]['url'])) {
                    $downloadUrl = $task;
                    break 2;
                }
            }
        }
    }

    if (!$downloadUrl) {
        die("Ошибка: PDF-файл не сгенерирован.");
    }

    return $downloadUrl;
}

?>