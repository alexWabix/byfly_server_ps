<?php
if (!empty($_POST['order_id']) && !empty($_POST['user_id'])) {
    $uploaddir = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/rew/';
    $fileName = date('Y-m-dHIs') . basename(transliterateAndCleanFileName($_FILES['file']['name']));
    $realPath = 'https://api.v.2.byfly.kz/images/rew/' . $fileName;
    $uploadfile = $uploaddir . $fileName;

    $fileType = $_FILES['file']['type'];
    $isImage = strpos($fileType, 'image') !== false;
    $isVideo = strpos($fileType, 'video') !== false;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        if (
            $db->query("INSERT INTO order_media (`id`, `link_media`, `user_id`, `order_id`, `bonus`, `date_create`, `media_type`, `is_checked`) 
                        VALUES (NULL, '" . $realPath . "', '" . $_POST['user_id'] . "', '" . $_POST['order_id'] . "', '0', CURRENT_TIMESTAMP, '" . ($isImage ? 'image' : ($isVideo ? 'video' : 'unknown')) . "', '0');")
        ) {
            echo json_encode(
                array(
                    "type" => true,
                    "data" => array(
                        'link' => $realPath,
                        'type' => $fileType,
                    ),
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
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