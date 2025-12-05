<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$countAdd = $db->query("SELECT * FROM addTours WHERE id='1'")->fetch_assoc()['count'];
$countAdd = $countAdd + 1;

$countAdd = $db->query("UPDATE addTours SET count='" . $countAdd . "' WHERE id='1'");
?>