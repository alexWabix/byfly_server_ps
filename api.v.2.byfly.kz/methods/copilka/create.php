<?php
if (!empty($_POST['user_id'])) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Программа раннего бронирования закрыта! С 1 ноября 2025 года акция окончена.",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Не указан user_id!",
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>