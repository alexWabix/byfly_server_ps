<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$usersDB = $db->query("SELECT * FROM users");
while ($users = $usersDB->fetch_assoc()) {
    sendWhatsapp(
        $users['phone'],
        "✨ Привет, " . $users['name'] . " " . $users['surname'] . "! ✨\n\n🔔 ВАЖНАЯ НОВОСТЬ! 🔔\n\n🟢 Сегодня в 16:00 состоится ЭКСТРЕННЫЙ ПРЯМОЙ ЭФИР на YouTube! 🟢\n\n🎥 Темы эфира:\n- 📚 Экстренная информация о компании\n\n❗ Записи эфира НЕ будет, он доступен ТОЛЬКО по ссылке: https://youtube.com/live/i6joAprZvb0?feature=share. В общем доступе эфир отсутствует, поэтому не упустите шанс! ❗\n\n📲 Подключайтесь вовремя, чтобы быть в курсе всех новостей и задать свои вопросы в прямом эфире!\n\n✨ До встречи на эфире, это ваш шанс узнать все секреты успеха! 🙌"
    );
    sleep(rand(7, 14));
}
?>