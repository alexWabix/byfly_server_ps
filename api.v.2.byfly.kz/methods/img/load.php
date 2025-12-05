<?php
if (!empty($_POST['user_id']) && empty($_FILES) == false) {
    $uploaddir = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/rew/';
    $fileName = date('Y-m-dHIs') . basename(transliterateAndCleanFileName($_FILES['file']['name']));
    $realPath = 'https://api.v.2.byfly.kz/images/rew/' . $fileName;
    $uploadfile = $uploaddir . $fileName;


    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    'link' => $realPath,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error load file...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty order information...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>