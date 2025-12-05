<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/methods/image/vendor/autoload.php');
use Mindee\Client;
use Mindee\Product\InternationalId\InternationalIdV2;
use Mindee\Product\Generated\GeneratedV1;
use Mindee\Input\PredictMethodOptions;


function convertDate($dateString)
{
    $months = [
        'января' => '01',
        'қаңтар' => '01',
        'февраля' => '02',
        'ақпан' => '02',
        'марта' => '03',
        'наурыз' => '03',
        'апреля' => '04',
        'сәуір' => '04',
        'мая' => '05',
        'мамыр' => '05',
        'июня' => '06',
        'маусым' => '06',
        'июля' => '07',
        'шілде' => '07',
        'августа' => '08',
        'тамыз' => '08',
        'сентября' => '09',
        'қыркүйек' => '09',
        'октября' => '10',
        'қазан' => '10',
        'ноября' => '11',
        'қараша' => '11',
        'декабря' => '12',
        'желтоқсан' => '12'
    ];

    $datesExplode = explode(' ', $dateString);
    if ($months[$datesExplode[1]] != null) {
        return $datesExplode[2] . '-' . $months[$datesExplode[1]] . '-' . $datesExplode[0];
    }

    return '';
}

if (empty($_FILES) == false) {

    $uploaddir = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/documents_passangers/';

    $fileName = basename(transliterateAndCleanFileName($_FILES['file']['name']));
    $realPath = 'https://api.v.2.byfly.kz/images/documents_passangers/' . $fileName;
    $uploadfile = $uploaddir . $fileName;


    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        $passport = null;
        try {

            if ($_POST['type'] == 'udv1' || $_POST['type'] == 'udv2' || $_POST['type'] == 'passport') {
                $mindeeClient = new Client("700086cb8c38ecd6ae2e53b83456eff8");
                $inputSource = $mindeeClient->sourceFromPath($uploadfile);
                $apiResponse = $mindeeClient->enqueueAndParse(InternationalIdV2::class, $inputSource);

                $recognize = json_encode($apiResponse->document->inference->prediction, JSON_UNESCAPED_UNICODE);

                $passport = json_decode($recognize, true);

                $iin = '';
                $user_name = '';
                $user_famale = '';
                $berthday = '';
                $dateOffUdv = '';
                $numberDocumentsUDV = '';
                $numberPassword = '';
                $dateOffPasport = '';

                if ($_POST['type'] == 'udv1') {
                    $iin = $passport['documentNumber']['value'];
                    $user_name = $passport['givenNames'][0]['value'];
                    $user_famale = $passport['surnames'][0]['value'];
                    $berthday = $passport['birthDate']['value'];
                } else if ($_POST['type'] == 'udv2') {
                    $numberDocumentsUDV = $passport['documentNumber']['value'];
                    $user_name = $passport['givenNames'][0]['value'];
                    $user_famale = $passport['surnames'][0]['value'];
                    $dateOffUdv = $passport['expiryDate']['value'];
                } else if ($_POST['type'] == 'passport') {
                    $user_name = $passport['givenNames'][0]['value'];
                    $user_famale = $passport['surnames'][0]['value'];
                    $berthday = $passport['birthDate']['value'];
                    $numberPassword = $passport['documentNumber']['value'];
                    $dateOffPasport = $passport['expiryDate']['value'];
                    $iin = $passport['personalNumber']['value'];
                }

                if (empty($user_name) == true) {
                    $user_name = '';
                }

                if (empty($user_famale) == true) {
                    $user_famale = '';
                }

                if (empty($iin) == true) {
                    $iin = '';
                }

                if (empty($berthday) == true) {
                    $berthday = '';
                }


                if (empty($dateOffUdv) == true) {
                    $dateOffUdv = '';
                }

                if (empty($numberDocumentsUDV) == true) {
                    $numberDocumentsUDV = '';
                }

                if (empty($numberPassword) == true) {
                    $numberPassword = '';
                }

                if (empty($dateOffPasport) == true) {
                    $dateOffPasport = '';
                }

                $passport = array(
                    "doc_type" => $passport['documentType']['value'],
                    "user_name" => $user_name,
                    "user_famale" => $user_famale,
                    "iin" => $iin,
                    "berthday_date" => $berthday,
                    "date_off_udv" => $dateOffUdv,
                    "udv_number" => $numberDocumentsUDV,
                    "passport_number" => $numberPassword,
                    "date_off_pasport" => $dateOffPasport,
                );
            } else if ($_POST['type'] == 'child1') {
                $mindeeClient = new Client("700086cb8c38ecd6ae2e53b83456eff8");
                $inputSource = $mindeeClient->sourceFromPath($uploadfile);
                $customEndpoint = $mindeeClient->createEndpoint(
                    "childcard1",
                    "Alexander1994",
                    "1"
                );
                $predictOptions = new PredictMethodOptions();
                $predictOptions->setEndpoint($customEndpoint);
                $apiResponse = $mindeeClient->enqueueAndParse(GeneratedV1::class, $inputSource, $predictOptions);


                $recognize = json_encode($apiResponse->document->inference->prediction, JSON_UNESCAPED_UNICODE);

                $passport = json_decode($recognize, true);

                $iin = $passport['fields']['iin']['value'];
                $user_name = $passport['fields']['user_name']['value'];
                $user_famale = $passport['fields']['user_famale']['value'];
                $berthday = $passport['fields']['berthday_date']['value'];
                $dateOffUdv = '';
                $numberDocumentsUDV = '';
                $numberPassword = '';
                $dateOffPasport = '';

                if (empty($user_name) == true) {
                    $user_name = '';
                }

                if (empty($user_famale) == true) {
                    $user_famale = '';
                }

                if (empty($iin) == true) {
                    $iin = '';
                }

                if (empty($berthday) == true) {
                    $berthday = '';
                }


                if (empty($dateOffUdv) == true) {
                    $dateOffUdv = '';
                }

                if (empty($numberDocumentsUDV) == true) {
                    $numberDocumentsUDV = '';
                }

                if (empty($numberPassword) == true) {
                    $numberPassword = '';
                }

                if (empty($dateOffPasport) == true) {
                    $dateOffPasport = '';
                }

                $passport = array(
                    "doc_type" => 'child1',
                    "user_name" => $user_name,
                    "user_famale" => $user_famale,
                    "iin" => $iin,
                    "berthday_date" => convertDate($berthday),
                    "berthday_date2" => $berthday,
                    "date_off_udv" => $dateOffUdv,
                    "udv_number" => $numberDocumentsUDV,
                    "passport_number" => $numberPassword,
                    "date_off_pasport" => $dateOffPasport,
                );
            } else if ($_POST['type'] == 'child2') {
                $mindeeClient = new Client("700086cb8c38ecd6ae2e53b83456eff8");
                $inputSource = $mindeeClient->sourceFromPath($uploadfile);
                $customEndpoint = $mindeeClient->createEndpoint(
                    "child2",
                    "Alexander1994",
                    "1"
                );
                $predictOptions = new PredictMethodOptions();
                $predictOptions->setEndpoint($customEndpoint);
                $apiResponse = $mindeeClient->enqueueAndParse(GeneratedV1::class, $inputSource, $predictOptions);


                $recognize = json_encode($apiResponse->document->inference->prediction, JSON_UNESCAPED_UNICODE);

                $passport = json_decode($recognize, true);

                $iin = '';
                $user_name = '';
                $user_famale = '';
                $berthday = '';
                $dateOffUdv = '';
                $numberDocumentsUDV = $passport['fields']['number']['value'];
                $numberPassword = '';
                $dateOffPasport = '';


                if (empty($user_name) == true) {
                    $user_name = '';
                }

                if (empty($user_famale) == true) {
                    $user_famale = '';
                }

                if (empty($iin) == true) {
                    $iin = '';
                }

                if (empty($berthday) == true) {
                    $berthday = '';
                }


                if (empty($dateOffUdv) == true) {
                    $dateOffUdv = '';
                }

                if (empty($numberDocumentsUDV) == true) {
                    $numberDocumentsUDV = '';
                }

                if (empty($numberPassword) == true) {
                    $numberPassword = '';
                }

                if (empty($dateOffPasport) == true) {
                    $dateOffPasport = '';
                }

                $passport = array(
                    "doc_type" => 'child1',
                    "user_name" => $user_name,
                    "user_famale" => $user_famale,
                    "iin" => $iin,
                    "berthday_date" => $berthday,
                    "date_off_udv" => $dateOffUdv,
                    "udv_number" => $numberDocumentsUDV,
                    "passport_number" => $numberPassword,
                    "date_off_pasport" => $dateOffPasport,
                );
            }


        } catch (\Throwable $th) {
            $passport = 'Erorr read passport... ' . $th->getMessage();
        }



        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "image" => $realPath,
                    "data" => $passport,
                )
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Error load file in server..."
            ),
            JSON_UNESCAPED_UNICODE
        );
    }



} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Empty images file for this query..."
        ),
        JSON_UNESCAPED_UNICODE
    );
}

?>