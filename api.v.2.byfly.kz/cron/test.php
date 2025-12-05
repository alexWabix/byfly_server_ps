<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$sql = "SELECT 
     `u`.`id` AS `user_id`, 
     `u`.`name`, 
     `u`.`famale`, 
     `u`.`surname`, 
     `u`.`phone`, 
     IFNULL(`sub_count`.`subscriber_count`, 0) AS `subscriber_count`
 FROM 
     `users` AS `u`
 LEFT JOIN (
     SELECT 
         `parent_user`, 
         COUNT(*) AS `subscriber_count`
     FROM 
         `users`
     WHERE 
         `parent_user` IS NOT NULL
     GROUP BY 
         `parent_user`
 ) AS `sub_count`
 ON 
     `u`.`id` = `sub_count`.`parent_user`
 ORDER BY 
     `subscriber_count` ASC 
 LIMIT 1;";

$userRandom = $db->query($sql)->fetch_assoc();


print_r($userRandom['user_id']);
?>