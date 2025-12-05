<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$dateYesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
$db->query("DELETE FROM order_tours WHERE date_create < '" . $dateYesterday . "' AND status_code='5'");
?>