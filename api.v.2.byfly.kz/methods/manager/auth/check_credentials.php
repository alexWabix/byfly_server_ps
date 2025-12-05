<?php
$input = $_POST;
$login = isset($input['login']) ? trim($input['login']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

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

    // Определяем тип логина (телефон или email)
    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

    if ($isEmail) {
        // Поиск по email
        $stmt = $db->prepare("
            SELECT id, fio, phone_call, phone_whatsapp, email, franchaise, 
                   type, work_for_tours, show_spec, isActive, date_start_work
            FROM managers 
            WHERE email = ? AND password = ? AND date_off_works IS NULL
            LIMIT 1
        ");
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . $db->error);
        }

        $stmt->bind_param("ss", $login, $hashedPassword);
    } else {
        // Удаляем все символы кроме цифр из логина (если это номер телефона)
        $phone = preg_replace('/[^0-9]/', '', $login);

        // Поиск по телефону
        $stmt = $db->prepare("
            SELECT id, fio, phone_call, phone_whatsapp, email, franchaise, 
                   type, work_for_tours, show_spec, isActive, date_start_work, avatar
            FROM managers 
            WHERE (phone_call = ? OR phone_whatsapp = ?) AND password = ? AND date_off_works IS NULL
            LIMIT 1
        ");
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . $db->error);
        }

        $stmt->bind_param("sss", $phone, $phone, $hashedPassword);
    }

    if (!$stmt->execute()) {
        throw new Exception("Ошибка выполнения запроса: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $resp = array(
            "type" => false,
            "msg" => "Неверный логин или пароль, либо менеджер уволен"
        );
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $manager = $result->fetch_assoc();
    $stmt->close();

    // Возвращаем успешный ответ с данными менеджера
    $resp = array(
        "type" => true,
        "msg" => "Авторизация успешна",
        "data" => array(
            "manager" => array(
                "id" => $manager['id'],
                "fio" => $manager['fio'],
                "phone_call" => $manager['phone_call'],
                "phone_whatsapp" => $manager['phone_whatsapp'],
                "email" => $manager['email'],
                "franchaise_id" => $manager['franchaise'],
                "type" => $manager['type'], // 0 - обычный, 1 - старший
                "work_for_tours" => $manager['work_for_tours'],
                "show_spec" => $manager['show_spec'],
                "is_active" => $manager['isActive'], // на смене или нет
                "date_start_work" => $manager['date_start_work'],
                "avatar" => $manager['avatar']
            )
        )
    );

} catch (Exception $e) {
    error_log("Manager auth error: " . $e->getMessage());

    $resp = array(
        "type" => false,
        "msg" => "Произошла ошибка при авторизации: " . $e->getMessage()
    );
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
?>