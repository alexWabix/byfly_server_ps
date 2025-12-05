<?php
if (empty($_POST['link']) == false) {
    $link = file_get_contents($_POST['link']);
    if (empty($link) == false) {
        $data = json_decode($link, true);
        if (empty($data) == false) {
            if ($data['type']) {
                echo json_encode(
                    array(
                        "type" => true,
                        "data" => $data['link']
                    ),
                    JSON_UNESCAPED_UNICODE,
                );
            } else {
                echo json_encode(
                    array(
                        "type" => false,
                        "msg" => "Не удалось сгенерировать документ!"
                    ),
                    JSON_UNESCAPED_UNICODE,
                );
            }
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => "Не удалось сгенерировать документ!"
                ),
                JSON_UNESCAPED_UNICODE,
            );
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Не удалось сгенерировать документ!"
            ),
            JSON_UNESCAPED_UNICODE,
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Отсутствует ссылка на документ!"
        ),
        JSON_UNESCAPED_UNICODE,
    );
}
?>