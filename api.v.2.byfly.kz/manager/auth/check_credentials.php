<?php

$input = $_POST;
$login = isset($input['login']) ? trim($input['login']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

// Удаляем все символы кроме цифр из логина (если это номер телефона)
$login = preg_replace('/[^0-9]/', '', $login);

if (empty($login) || empty($password)) {
    $resp = array(
        "type" => false,
        "msg" => "Логин и пароль обязательны для заполнения"
    );
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Хешируем пароль для проверки
    $hashedPassword = md5($password);

    // Проверяем пользователя по телефону и паролю
    $stmt = $db->prepare("
        SELECT id, name, famale, surname, phone, is_manager, is_admin, is_super_user, 
               last_visit, blocked_to_time
        FROM users 
        WHERE phone = ? AND password = ? 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $login, $hashedPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Неверный логин или пароль"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user = $result->fetch_assoc();

    // Проверяем не заблокирован ли пользователь
    if ($user['blocked_to_time'] && strtotime($user['blocked_to_time']) > time()) {
        $resp = array(
            "type" => false,
            "msg" => "Ваш аккаунт заблокирован до " . date('d.m.Y H:i', strtotime($user['blocked_to_time']))
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Проверяем права доступа к CRM системе
    if ($user['is_manager'] != 1 && $user['is_admin'] != 1 && $user['is_super_user'] != 1) {
        $resp = array(
            "type" => false,
            "msg" => "У вас нет доступа к CRM системе"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Обновляем время последнего визита
    $updateStmt = $db->prepare("UPDATE users SET last_visit = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();

    // Возвращаем успешный ответ с данными пользователя
    $resp = array(
        "type" => true,
        "msg" => "Авторизация успешна",
        "user" => array(
            "id" => $user['id'],
            "name" => $user['name'],
            "famale" => $user['famale'],
            "surname" => $user['surname'],
            "phone" => $user['phone'],
            "is_manager" => $user['is_manager'],
            "is_admin" => $user['is_admin'],
            "is_super_user" => $user['is_super_user'],
            "full_name" => trim($user['famale'] . ' ' . $user['name'] . ' ' . $user['surname'])
        )
    );

} catch (Exception $e) {
    $resp = array(
        "type" => false,
        "msg" => "Произошла ошибка при авторизации: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>