<?php

$user_id = $_POST['user_id'] ?? null;
$company_form = $_POST['company_form'] ?? 'ИП';
$company_name = $_POST['company_name'] ?? null;
$country = $_POST['country'] ?? 'Казахстан';
$country_code = $_POST['country_code'] ?? 'KZ';
$owner_full_name = $_POST['owner_full_name'] ?? null;
$iinbiin = $_POST['iinbiin'] ?? null;
$iban = $_POST['iban'] ?? null;
$bik = $_POST['bik'] ?? null;
$iik = $_POST['iik'] ?? null;
$talon_ip = $_POST['talon_ip'] ?? null;
$spravka_bank = $_POST['spravka_bank'] ?? null;
$udv_ip = $_POST['udv_ip'] ?? null;

if (!$user_id || !$owner_full_name || !$iinbiin || !$iban || !$bik || !$iik) {
    echo json_encode([
        "type" => false,
        "msg" => "Заполните все обязательные поля"
    ]);
    exit;
}

// Проверяем, нет ли уже таких реквизитов
$check_sql = "SELECT id FROM user_ip WHERE user_id = ? AND iinbiin = ? AND is_active = 1";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $iinbiin);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        "type" => false,
        "msg" => "Реквизиты с таким ИИН/БИН уже существуют"
    ]);
    exit;
}

$sql = "INSERT INTO user_ip (
    date_create, user_id, company_form, company_name, country, 
    country_code, owner_full_name, iinbiin, iban, bik, iik, 
    talon_ip, spravka_bank, udv_ip, is_active, verification_status
) VALUES (
    NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending'
)";

$stmt = $db->prepare($sql);
$stmt->bind_param(
    "isssssssssssss",
    $user_id,
    $company_form,
    $company_name,
    $country,
    $country_code,
    $owner_full_name,
    $iinbiin,
    $iban,
    $bik,
    $iik,
    $talon_ip,
    $spravka_bank,
    $udv_ip
);

if ($stmt->execute()) {
    // Уведомляем администраторов о новых реквизитах
    $user_info = getUserParams($user_id);
    $message = "🔔 НОВЫЕ РЕКВИЗИТЫ НА ПРОВЕРКУ\n\n" .
        "👤 Пользователь: {$user_info['name']} {$user_info['famale']}\n" .
        "🏢 Форма: $company_form\n" .
        "🌍 Страна: $country\n" .
        "📋 ИИН/БИН: $iinbiin\n" .
        "📱 Телефон: {$user_info['phone']}\n\n" .
        "⚡ Требуется проверка реквизитов!";

    // Отправляем уведомления супер админам и бухгалтерам
    $admin_sql = "SELECT phone FROM users WHERE is_super_user = 1 OR is_buh = 1";
    $admin_result = $db->query($admin_sql);

    while ($admin = $admin_result->fetch_assoc()) {
        sendWhatsapp($admin['phone'], $message);
    }

    echo json_encode([
        "type" => true,
        "msg" => "Реквизиты успешно добавлены и отправлены на проверку"
    ]);
} else {
    echo json_encode([
        "type" => false,
        "msg" => "Ошибка при добавлении реквизитов: " . $db->error
    ]);
}
?>