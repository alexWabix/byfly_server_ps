<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$db->query('UPDATE users SET count_video_today="0"');
?>