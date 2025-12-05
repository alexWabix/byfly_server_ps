<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$listUsersDB = $db->query("SELECT * FROM users");
while ($listUsers = $listUsersDB->fetch_assoc()) {
    $db->query("UPDATE users SET orient='test', date_couch_start='2024-10-21 00:00:00', date_validate_agent='2024-10-31 00:00:00', user_status='agent' WHERE id='" . $listUsers['id'] . "'");
    $db->query("INSERT INTO user_statused (`id`, `code_status`, `date_add`, `user_id`) VALUES (NULL, '4', CURRENT_TIMESTAMP, '" . $listUsers['id'] . "');");
    $db->query("INSERT INTO user_statused (`id`, `code_status`, `date_add`, `user_id`) VALUES (NULL, '1', CURRENT_TIMESTAMP, '" . $listUsers['id'] . "');");
}
?>