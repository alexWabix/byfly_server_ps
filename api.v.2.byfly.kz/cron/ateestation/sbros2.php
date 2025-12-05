<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


$listUserAtestationDB = $db->query("SELECT * FROM `users` WHERE `start_atestation` IS NOT NULL AND `end_atestation` IS NOT NULL AND date_validate_agent > '" . date('Y-m-d H:i:s') . "' AND astestation_bal < 92");
while ($listUserAtestation = $listUserAtestationDB->fetch_assoc()) {
    $db->query("UPDATE users SET start_atestation=NULL, end_atestation=NULL, astestation_bal='0', atestation_query='', coun_query='0' WHERE id='" . $listUserAtestation['id'] . "'");
}


