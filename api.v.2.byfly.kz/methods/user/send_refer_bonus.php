<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$usersDB = $db->query("SELECT * FROM users");
while ($users = $usersDB->fetch_assoc()) {
    echo $users['name'] . ' ' . $users['famale'] . '<br>';
}
?>