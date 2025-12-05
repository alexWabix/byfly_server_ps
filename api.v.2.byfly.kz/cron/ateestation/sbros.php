<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


$listUserAtestationDB = $db->query("SELECT * FROM `users` WHERE `start_atestation` IS NOT NULL AND `end_atestation` IS NULL");
while ($listUserAtestation = $listUserAtestationDB->fetch_assoc()) {

    $startAtestation = new DateTime($listUserAtestation['start_atestation']);
    $currentDate = new DateTime();
    $interval = $currentDate->diff($startAtestation);
    $minutesPassed = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    if ($minutesPassed >= 60) {
        $db->query("UPDATE users SET end_atestation='" . date('Y-m-d H:i:s') . "' WHERE id='" . $listUserAtestation['id'] . "'");
    }
}
