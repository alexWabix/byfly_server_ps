<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


$listAgentsDB = $db->query("SELECT * FROM users 
    WHERE (date_couch_start IS NOT NULL 
        AND user_status = 'agent' 
        AND blocked_to_time IS NULL 
        AND orient != 'test' 
        AND id NOT IN ('7', '42', '84', '92', '207')) 
    OR `coach_id` = '1' 
    ORDER BY RAND()");

//$count = 0;
while ($listAgents = $listAgentsDB->fetch_assoc()) {
    $encodedId = base64_encode($listAgents['id']);
    $db->query("UPDATE users SET link_online_desc_open = '0' WHERE id='" . $encodedId . "'");
    $link = "https://api.v.2.byfly.kz/get_coach_link.php?LINK=" . urlencode($encodedId);

    $msg = "🌟 Здравствуйте, " . $listAgents['famale'] . ' ' . $listAgents['name'] . ", приглашаем вас на онлайн обучение!\n📅 Сегодня в 19:00\n🔗 Ваша персональная ссылка: $link\nЖдем вас! 🚀";
    sendWhatsapp($listAgents['phone'], $msg);
    $output = "Сообщение отправлено: " . $listAgents['famale'] . " " . $listAgents['name'] . PHP_EOL;
    echo mb_convert_encoding($output, "UTF-8", "Windows-1252");
    sleep(rand(6, 10));
    //echo $listAgents['famale'] . ' ' . $listAgents['name'] . ' - ' . $listAgents['id'] . '<br>';
    //$count++;
}
echo $count;
?>