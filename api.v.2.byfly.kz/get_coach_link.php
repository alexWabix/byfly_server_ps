<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if (!isset($_GET['LINK'])) {
    die("Ошибка: Отсутствует параметр LINK");
}

$id = base64_decode($_GET['LINK']);
$id = $db->real_escape_string($id);

$userInfoDB = $db->query("SELECT * FROM users WHERE id='" . $id . "'");

if ($userInfoDB->num_rows > 0) {
    $userInfo = $userInfoDB->fetch_assoc();

    header("Location: https://us06web.zoom.us/j/85036027746?pwd=r2fdFNvo9wo22XUPHpqD9On4gLZ0GM.1");
    exit();

} else {
    echo "Ошибка: Пользователь не найден.";
}
?>