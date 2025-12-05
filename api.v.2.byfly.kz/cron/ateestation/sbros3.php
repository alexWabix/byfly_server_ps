<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$currentDate = new DateTime();
$currentDate->modify('-7 days');


$listUserAtestationDB = $db->query("SELECT * FROM coach WHERE start_atestation IS NOT NULL AND start_atestation < '" . $currentDate->format('Y-m-d H:i:s') . "' AND astestation_bal < '98'");
while ($listUserAtestation = $listUserAtestationDB->fetch_assoc()) {
    $db->query("UPDATE coach SET astestation_bal = '0', last_query='0', start_atestation=NULL, last_atestation_date=NULL, atestation_query='[]' WHERE id='" . $listUserAtestation['id'] . "'");
}
