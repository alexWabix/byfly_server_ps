<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$usersDB = $db->query("SELECT * FROM users");
while ($users = $usersDB->fetch_assoc()) {
    $referUserInfoDB = $db->query("SELECT * FROM users WHERE id='" . $users['parent_user'] . "'");
    if ($referUserInfoDB->num_rows > 0) {
        $referUserInfo = $referUserInfoDB->fetch_assoc();
        $db->query("UPDATE users SET bonus = '" . $referUserInfo['refer_registration_bonus'] . "' WHERE id='" . $users['id'] . "'");
    }
}
?>