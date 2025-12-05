<?php
if (empty($_FILES['file']) == false && empty($_POST['user_id']) == false) {
    $uploaddir = '/var/www/www-root/data/www/api.v.2.byfly.kz/methods/user/avatars/';

    $fileName = date("YmdHis") . basename(transliterateAndCleanFileName($_FILES['file']['name']));
    $realPath = 'https://api.v.2.byfly.kz/methods/user/avatars/' . $fileName;
    $uploadfile = $uploaddir . $fileName;


    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        if ($db->query("UPDATE users SET avatar='" . $realPath . "' WHERE id='" . $_POST['user_id'] . "'")) {
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
                    "msg" => 'Error save file...',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }

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