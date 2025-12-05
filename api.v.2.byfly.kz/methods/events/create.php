<?php
$conn = $db;
$_POST['images'] = json_decode($_POST['images']);
$_POST['videos'] = json_decode($_POST['videos']);

// Экранирование входных данных
$title_ru = mysqli_real_escape_string($conn, $_POST['title_ru']);
$title_kz = mysqli_real_escape_string($conn, $_POST['title_kz']);
$title_en = mysqli_real_escape_string($conn, $_POST['title_en']);
$description_ru = mysqli_real_escape_string($conn, $_POST['description_ru']);
$description_kz = mysqli_real_escape_string($conn, $_POST['description_kz']);
$description_en = mysqli_real_escape_string($conn, $_POST['description_en']);
$date = mysqli_real_escape_string($conn, $_POST['date']);
$price = mysqli_real_escape_string($conn, $_POST['price']);
$max_people = mysqli_real_escape_string($conn, $_POST['max_people']);
$responsible_person = mysqli_real_escape_string($conn, $_POST['responsible_person']);
$city = mysqli_real_escape_string($conn, $_POST['city']);
$event_id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : null;

if ($event_id) {
    // Редактирование мероприятия
    $sql_update_event = "UPDATE `events` 
                         SET 
                             `title_ru` = '$title_ru', 
                             `title_en` = '$title_en', 
                             `title_kk` = '$title_kz', 
                             `desc_ru` = '$description_ru', 
                             `desc_en` = '$description_en', 
                             `desc_kk` = '$description_kz', 
                             `price` = '$price', 
                             `date_start` = '$date', 
                             `city` = '$city', 
                             `max_people` = '$max_people', 
                             `user_change` = '$responsible_person'
                         WHERE `id` = '$event_id'";

    if ($conn->query($sql_update_event) === TRUE) {
        $conn->query("DELETE FROM `event_images` WHERE `event_id` = '$event_id'");
        if (!empty($_POST['images'])) {
            foreach ($_POST['images'] as $image) {
                $image = mysqli_real_escape_string($conn, $image);
                $sql_image = "INSERT INTO `event_images` (`id`, `image`, `event_id`) VALUES (NULL, '$image', '$event_id')";
                $conn->query($sql_image);
            }
        }
        $conn->query("DELETE FROM `event_video` WHERE `event_id` = '$event_id'");
        if (!empty($_POST['videos'])) {
            foreach ($_POST['videos'] as $video) {
                $video = mysqli_real_escape_string($conn, $video);
                $sql_video = "INSERT INTO `event_video` (`id`, `video`, `event_id`) VALUES (NULL, '$video', '$event_id')";
                $conn->query($sql_video);
            }
        }

        echo json_encode(
            array(
                "type" => true,
                "msg" => "Мероприятие успешно обновлено"
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Ошибка при обновлении мероприятия. " . $conn->error
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    $sql_event = "INSERT INTO `events` 
                    (`title_ru`, `title_en`, `title_kk`, `desc_ru`, `desc_en`, `desc_kk`, `count_viwe`, `price`, `user_change`, `date_start`, `date_create`, `city`, `max_people`) 
                  VALUES 
                    ('$title_ru', '$title_en', '$title_kz', '$description_ru', '$description_en', '$description_kz', '0', '$price', '$responsible_person', '$date', CURRENT_TIMESTAMP, '$city', '$max_people')";

    if ($conn->query($sql_event) === TRUE) {
        $event_id = $conn->insert_id;

        if (!empty($_POST['images'])) {
            foreach ($_POST['images'] as $image) {
                $image = mysqli_real_escape_string($conn, $image);
                $sql_image = "INSERT INTO `event_images` (`image`, `event_id`) VALUES ('$image', '$event_id')";
                $conn->query($sql_image);
            }
        }

        if (!empty($_POST['videos'])) {
            foreach ($_POST['videos'] as $video) {
                $video = mysqli_real_escape_string($conn, $video);
                $sql_video = "INSERT INTO `event_video` (`video`, `event_id`) VALUES ('$video', '$event_id')";
                $conn->query($sql_video);
            }
        }
        echo json_encode(
            array(
                "type" => true,
                "msg" => "Мероприятие успешно добавлено"
            ),
            JSON_UNESCAPED_UNICODE
        );
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => "Ошибка при добавлении мероприятия. " . $conn->error
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
}
?>