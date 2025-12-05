<?php
// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

header('Content-Type: application/json');

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    $phone = $db->real_escape_string(preg_replace('/\D/', '', $_POST['phone']));

    // 1. Проверяем, есть ли пользователь с таким номером
    $userQuery = $db->query("SELECT id, name, avatar FROM users WHERE phone = '$phone' LIMIT 1");

    if ($userQuery->num_rows === 0) {
        echo json_encode(['error' => 'Пользователь с таким номером не найден']);
        exit;
    }

    $user = $userQuery->fetch_assoc();
    $userId = $user['id'];

    // 2. Получаем всех подписчиков этого пользователя
    $subscribersQuery = $db->query("
        SELECT u.id, u.name, u.phone, u.avatar 
        FROM users u 
        WHERE u.parent_user = '$userId'
    ");

    $totalSubscribers = $subscribersQuery->num_rows;
    $subscribers = [];
    $qualifiedCount = 0;
    $activeCount = 0;

    // 3. Для каждого подписчика проверяем наличие накопительной ячейки и платежей
    while ($subscriber = $subscribersQuery->fetch_assoc()) {
        $subscriberId = $subscriber['id'];

        // Проверяем наличие активной накопительной ячейки
        $copilkaQuery = $db->query("
            SELECT id 
            FROM copilka_ceils 
            WHERE user_id = '$subscriberId' 
            AND date_dosrok_close IS NULL
            LIMIT 1
        ");

        $hasCopilka = $copilkaQuery->num_rows > 0;
        $payments = 0;

        if ($hasCopilka) {
            // Считаем количество месяцев с платежами
            $copilka = $copilkaQuery->fetch_assoc();
            $copilkaId = $copilka['id'];

            $paymentsQuery = $db->query("
                SELECT 
                    (month_1_money > 0) + 
                    (month_2_money > 0) + 
                    (month_3_money > 0) + 
                    (month_4_money > 0) + 
                    (month_5_money > 0) + 
                    (month_6_money > 0) + 
                    (month_7_money > 0) + 
                    (month_8_money > 0) + 
                    (month_9_money > 0) + 
                    (month_10_money > 0) + 
                    (month_11_money > 0) + 
                    (month_12_money > 0) AS payments
                FROM copilka_ceils
                WHERE id = '$copilkaId'
            ");

            $paymentsData = $paymentsQuery->fetch_assoc();
            $payments = (int) $paymentsData['payments'];
        }

        // Определяем, соответствует ли подписчик условиям
        $qualified = $hasCopilka && $payments >= 2;

        if ($qualified) {
            $qualifiedCount++;
        }
        if ($payments >= 2) {
            $activeCount++;
        }

        $subscribers[] = [
            'id' => $subscriber['id'],
            'name' => $subscriber['name'] ?: 'Без имени',
            'phone' => $subscriber['phone'],
            'avatar' => $subscriber['avatar'],
            'has_copilka' => $hasCopilka,
            'payments' => $payments,
            'qualified' => $qualified
        ];
    }

    // 4. Формируем ответ
    echo json_encode([
        'total_subscribers' => $totalSubscribers,
        'qualified_count' => $qualifiedCount,
        'active_count' => $activeCount,
        'subscribers' => $subscribers
    ]);
} else {
    echo json_encode(['error' => 'Неверный запрос']);
}
?>