<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$db->query("DELETE FROM `spec_tours` WHERE `date_create` < NOW() - INTERVAL 1 HOUR");
?>