<?php
if (empty($_FILES['file']) == false) {
    $uploaddir = '/var/www/www-root/data/www/api.v.2.byfly.kz/methods/pdf/list_pdf/';

    $fileName = date("YmdHis") . basename(transliterateAndCleanFileName($_FILES['file']['name']));
    $realPath = 'https://api.v.2.byfly.kz/methods/pdf/list_pdf/' . $fileName;
    $uploadfile = $uploaddir . $fileName;


    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        echo json_encode(
            array(
                "type" => true,
                "data" => $realPath,
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error get data load...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
    exit;
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error get data load...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

?>