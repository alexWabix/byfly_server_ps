<?php
if (isset($_POST['id'])) {
    $event_id = mysqli_real_escape_string($db, $_POST['id']);
    $delete_images = "DELETE FROM `event_images` WHERE `event_id` = '$event_id'";
    if (!$db->query($delete_images)) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Ошибка при удалении изображений: " . $db->error
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
    $delete_videos = "DELETE FROM `event_video` WHERE `event_id` = '$event_id'";
    if (!$db->query($delete_videos)) {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Ошибка при удалении видео: " . $db->error
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $delete_event = "DELETE FROM `events` WHERE `id` = '$event_id'";
    if ($db->query($delete_event)) {
        echo json_encode(
            array(
                "type" => true,
                "msg" => "Мероприятие успешно удалено"
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Ошибка при удалении мероприятия: " . $db->error
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "ID мероприятия не указан"
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>