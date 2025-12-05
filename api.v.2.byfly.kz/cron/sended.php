<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Функция для генерации случайных имен
function getRandomName()
{
    $names = [
        "Алихан",
        "Ержан",
        "Нурлан",
        "Айсулу",
        "Диана",
        "Гульжанат",
        "Андрей",
        "Мария",
        "Сергей",
        "Екатерина",
        "Владимир",
        "Ольга",
        "Алексей",
        "Наталья",
        "Тимур",
        "Аружан",
        "Жанна",
        "Дмитрий",
        "Камила",
        "Алина",
        "Руслан",
        "Зарина",
        "Кирилл",
        "Светлана"
    ];
    return $names[array_rand($names)];
}

// Функция для генерации случайных казахстанских номеров
function getRandomPhoneNumber()
{
    $prefixes = ['7701', '7702', '7705', '7707', '7712', '7717', '7747', '7757', '7775', '7777'];
    $prefix = $prefixes[array_rand($prefixes)];
    $number = mt_rand(1000000, 9999999);
    return $prefix . $number;
}

// Функция для генерации случайных email
function getRandomEmail($name)
{
    $domains = ['gmail.com', 'mail.ru', 'yahoo.com', 'byfly.kz'];
    $domain = $domains[array_rand($domains)];
    return strtolower($name) . mt_rand(10, 99) . '@' . $domain;
}

// Количество записей для генерации
$numberOfRecords = 17;

// Заполнение базы данных
for ($i = 0; $i < $numberOfRecords; $i++) {
    $name = getRandomName();
    $phone = getRandomPhoneNumber();
    $email = getRandomEmail($name);

    // Выполнение запроса на вставку
    $insertQuery = "
        INSERT INTO `uralsk_preza` (`date_create`, `name`, `phone`, `email`)
        VALUES (CURRENT_TIMESTAMP, '$name', '$phone', '$email')
    ";

    if ($db->query($insertQuery) === TRUE) {
        echo "Запись добавлена: $name, $phone, $email<br>";
    } else {
        echo "Ошибка при добавлении записи: " . $db->error . "<br>";
    }
}

echo "<br>Генерация завершена!";
?>