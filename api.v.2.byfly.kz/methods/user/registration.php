<?php
$password = md5($_POST['password']);
$agent = 0;
$bonus = 0;
$source = 'unknown';

// Проверяем предварительную регистрацию
$preRegisterQuery = "SELECT pr.*, u.refer_registration_bonus, u.phone as agent_phone, u.name as agent_name, u.famale as agent_famale 
                     FROM pre_register pr 
                     LEFT JOIN users u ON pr.user_add = u.id 
                     WHERE pr.phone = '" . $db->real_escape_string($_POST['phone']) . "' 
                     AND pr.status = 'pending'
                     ORDER BY pr.date_create DESC 
                     LIMIT 1";

$preRegister = $db->query($preRegisterQuery);

if ($preRegister && $preRegister->num_rows > 0) {
    $preRegisterData = $preRegister->fetch_assoc();
    $agent = $preRegisterData['user_add'];
    $bonus = $preRegisterData['refer_registration_bonus'] ?? 2000;
    $source = 'pre_register';

    // Обновляем статус в pre_register
    $db->query("UPDATE pre_register SET status = 'registered', date_registered = NOW() WHERE id = " . $preRegisterData['id']);
}

// Если нет предварительной регистрации, но указан parent_user
if ($agent == 0 && !empty($_POST['parent_user'])) {
    $agent = intval($_POST['parent_user']);

    // Получаем бонус от агента
    $agentBonusQuery = $db->query("SELECT refer_registration_bonus FROM users WHERE id = $agent");
    if ($agentBonusQuery && $agentBonusQuery->num_rows > 0) {
        $agentBonusData = $agentBonusQuery->fetch_assoc();
        $bonus = $agentBonusData['refer_registration_bonus'] ?? 2000;
    } else {
        $bonus = 2000; // Дефолтный бонус
    }

    $source = 'promocode';
}

// Если агент все еще не определен, выбираем лучшего
if ($agent == 0) {
    $sqlSelect = "
        SELECT `id`, `first_line_agents_count`, `refer_registration_bonus`
        FROM `users`
        WHERE `user_status` IN ('agent', 'coach', 'alpha', 'ambasador')
        AND `astestation_bal` >= 92
        AND `blocked_to_time` IS NULL
        AND `has_sold_tour` = 1
        ORDER BY `first_line_agents_count` ASC, `id` ASC
        LIMIT 1;
    ";

    $result = $db->query($sqlSelect);
    if ($result && $result->num_rows > 0) {
        $userWithHighBalance = $result->fetch_assoc();
        $agent = $userWithHighBalance['id'];
        $bonus = $userWithHighBalance['refer_registration_bonus'] ?? 2000;
        $source = 'auto_best';
    } else {
        // Выбираем случайного агента
        $sqlRandom = "
            SELECT `id`, `refer_registration_bonus`
            FROM `users`
            WHERE `user_status` IN ('agent', 'coach', 'alpha', 'ambasador')
            AND `blocked_to_time` IS NULL
            ORDER BY RAND()
            LIMIT 1;
        ";

        $result = $db->query($sqlRandom);
        if ($result && $result->num_rows > 0) {
            $randomAgent = $result->fetch_assoc();
            $agent = $randomAgent['id'];
            $bonus = $randomAgent['refer_registration_bonus'] ?? 2000;
            $source = 'auto_random';
        }
    }
}

// Исправляем запрос для менеджера (date_off_works должен быть NULL для активных)
$managerQuery = "
    SELECT * 
    FROM managers 
    WHERE `date_off_works` IS NULL 
    AND `work_for_tours` = '1' 
    AND id NOT IN (4, 16, 13, 14) 
    ORDER BY RAND() 
    LIMIT 1
";

$managerResult = $db->query($managerQuery);
$manager = $managerResult ? $managerResult->fetch_assoc() : null;

if (!$manager) {
    // Если не найден менеджер, используем дефолтного
    $manager = ['id' => 1];
}

// Получаем информацию о пригласителе
$agentInfo = null;
if ($agent > 0) {
    $agentResult = $db->query("SELECT * FROM users WHERE id = $agent");
    if ($agentResult && $agentResult->num_rows > 0) {
        $agentInfo = $agentResult->fetch_assoc();
    }
}

// Находим куратора (вышестоящего агента)
function findCurator($db, $userId)
{
    if ($userId <= 0)
        return null;

    $result = $db->query("SELECT parent_user, user_status FROM users WHERE id = $userId");
    if (!$result || $result->num_rows == 0)
        return null;

    $user = $result->fetch_assoc();
    if (!$user || $user['parent_user'] == 0)
        return null;

    $parentResult = $db->query("SELECT * FROM users WHERE id = " . $user['parent_user']);
    if (!$parentResult || $parentResult->num_rows == 0)
        return null;

    $parent = $parentResult->fetch_assoc();

    // Если родитель является агентом, он и есть куратор
    if ($parent && in_array($parent['user_status'], ['agent', 'coach', 'alpha', 'ambasador'])) {
        return $parent;
    }

    // Если родитель не агент, ищем выше по иерархии
    return findCurator($db, $parent['id']);
}

$curator = findCurator($db, $agent);

// Подготовка SQL запроса с экранированием данных
$name = $db->real_escape_string(trim($_POST['name']));
$famale = $db->real_escape_string(trim($_POST['famale']));
$surname = $db->real_escape_string(trim($_POST['surname'] ?? ''));
$phone = $db->real_escape_string($_POST['phone']);

$genPromocode = generatePromoCode($_POST['name'], $_POST['famale'], $_POST['surname'] ?? '', $_POST['phone'], 0);

// Упрощенный SQL запрос (только необходимые поля)
$sql = "
    INSERT INTO users 
    (`name`, `famale`, `surname`, `phone`, `manager`, `date_registration`, `last_visit`, 
     `balance`, `bonus`, `password`, `refer_registration_bonus`, `parent_user`, `promo_code`, 
     `user_status`, `orient`, `defoult_nakrutka`, `show_my_data`, `latter_my_contacts`, 
     `latter_is_me`, `is_active`, `for_couch`, `start_test`, `is_admin`, `search_nakrutka`, 
     `show_clear_nakrutka`, `tarif`, `obrabotan`, `first_line_agents_count`, `coach_rating`, 
     `is_beneficiary`, `has_sold_tour`, `distribiutor`, `create_group_tours`, `is_dalboeb`,
     `is_investor`, `is_manager`, `is_coach`, `is_distributor`, `is_copilka_manager`, `is_teh_support`, `reiting`
    ) VALUES (
     '$name', '$famale', '$surname', '$phone', '" . $manager['id'] . "', NOW(), NOW(),
     0, $bonus, '$password', 2000, $agent, '$genPromocode',
     'user', 'test', 0, 0, 0,
     0, 0, 0, 0, 0, 0,
     0, 1, 0, 0, 0.0,
     0, 0, 0, 0, 0,
     0, 0, 0, 0, 0, 0, 0
    )
";

if ($db->query($sql)) {
    $lastId = $db->insert_id;

    // Обновляем счетчик агентов у пригласителя
    if ($agent > 0) {
        $db->query("UPDATE `users` SET `first_line_agents_count` = `first_line_agents_count` + 1 WHERE `id` = $agent");
    }

    // Формируем и отправляем сообщения
    $newUserName = $_POST['name'] . " " . $_POST['famale'];
    $newUserPhone = $_POST['phone'];

    // Проверяем функцию sendWhatsapp
    if (!function_exists('sendWhatsapp')) {
        echo json_encode([
            "type" => false,
            "msg" => "sendWhatsapp function not available"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Определяем, является ли пригласитель и куратор одним лицом
    $isInviterAndCuratorSame = ($curator && $agentInfo && $curator['id'] == $agentInfo['id']);

    // 1. Сообщение новому пользователю
    $newUserMessage = "🎉 Добро пожаловать в ByFly Travel!\n\n";
    $newUserMessage .= "Здравствуйте, $newUserName! 👋\n\n";
    $newUserMessage .= "Поздравляем с успешной регистрацией в нашей системе!";

    if ($bonus > 0) {
        $newUserMessage .= " Вы получили бонус в размере " . number_format($bonus, 0, ',', ' ') . " тенге на ваш счет! 💰";
    }

    $newUserMessage .= "\n\n🚀 НАЧНИТЕ ЗАРАБАТЫВАТЬ УЖЕ СЕГОДНЯ!\n";
    $newUserMessage .= "Станьте агентом ByFly Travel и получите доступ к:\n";
    $newUserMessage .= "• Продаже туров с накруткой до 40%\n";
    $newUserMessage .= "• Доходу по 5 линиям от команды\n";
    $newUserMessage .= "• Эксклюзивным предложениям от 95+ туроператоров\n\n";

    if ($curator) {
        $newUserMessage .= "👨‍💼 ВАШ КУРАТОР:\n";
        $newUserMessage .= $curator['name'] . " " . $curator['famale'] . "\n";
        $newUserMessage .= "📱 Телефон: +" . $curator['phone'] . "\n\n";
        $newUserMessage .= "Обращайтесь к куратору по любым вопросам - он поможет вам начать зарабатывать! 💪\n\n";
    }

    $newUserMessage .= "📚 СЛЕДУЮЩИЕ ШАГИ:\n";
    $newUserMessage .= "1. Изучите приложение ByFly Travel\n";
    $newUserMessage .= "2. Пройдите обучение на агента (всего 8 дней)\n";
    $newUserMessage .= "3. Начните продавать туры и зарабатывать\n";
    $newUserMessage .= "4. Приглашайте друзей и получайте % с их продаж\n\n";

    $newUserMessage .= "💡 Ваш промокод для приглашения друзей: $genPromocode\n\n";
    $newUserMessage .= "📱 Скачайте приложение ByFly Travel в App Store или Google Play\n\n";
    $newUserMessage .= "Успехов в развитии! 🌟";

    sendWhatsapp($newUserPhone, $newUserMessage);

    // 2. Сообщения пригласителю и куратору
    if ($isInviterAndCuratorSame && $agentInfo) {
        // Универсальное сообщение для пригласителя-куратора
        $inviterCuratorMessage = "🎯 НОВАЯ РЕГИСТРАЦИЯ В ВАШЕЙ КОМАНДЕ!\n\n";
        $inviterCuratorMessage .= "Поздравляем! Под вас зарегистрирован новый пользователь:\n";
        $inviterCuratorMessage .= "👤 $newUserName\n";
        $inviterCuratorMessage .= "📱 +$newUserPhone\n";
        $inviterCuratorMessage .= "🎫 Промокод: $genPromocode\n";
        $inviterCuratorMessage .= "📋 Источник: $source\n\n";

        $inviterCuratorMessage .= "💰 ВАШ ДОХОД:\n";
        $inviterCuratorMessage .= "• Вы получите 10% кэшбэк когда он сдаст экзамен на 92+ балла\n";
        $inviterCuratorMessage .= "• Еще 10% кэшбэк когда он продаст первый тур\n";
        $inviterCuratorMessage .= "• Постоянный доход 1% с его продаж\n";
        $inviterCuratorMessage .= "• Доход с его команды по линиям\n\n";

        $inviterCuratorMessage .= "👨‍🏫 ВАШИ ЗАДАЧИ КАК КУРАТОРА:\n";
        $inviterCuratorMessage .= "1. Свяжитесь с новичком в течение 24 часов\n";
        $inviterCuratorMessage .= "2. Помогите ему разобраться с приложением\n";
        $inviterCuratorMessage .= "3. Мотивируйте пройти обучение на агента\n";
        $inviterCuratorMessage .= "4. Поддерживайте его на пути к первым продажам\n\n";

        $inviterCuratorMessage .= "🎯 Помните: успех вашего подопечного = ваш успех!\n";
        $inviterCuratorMessage .= "Инвестируйте время в его развитие! 💪";

        sendWhatsapp($agentInfo['phone'], $inviterCuratorMessage);

    } else {
        // Отдельные сообщения для пригласителя и куратора

        // Сообщение пригласителю
        if ($agentInfo) {
            $inviterMessage = "🎉 ПОЗДРАВЛЯЕМ С НОВОЙ РЕГИСТРАЦИЕЙ!\n\n";
            $inviterMessage .= "Под вас зарегистрирован новый пользователь:\n";
            $inviterMessage .= "👤 $newUserName\n";
            $inviterMessage .= "📱 +$newUserPhone\n";
            $inviterMessage .= "🎫 Промокод: $genPromocode\n";
            $inviterMessage .= "📋 Источник: $source\n\n";

            $inviterMessage .= "💰 ВАШ ДОХОД:\n";
            $inviterMessage .= "• 10% кэшбэк когда он сдаст экзамен на 92+ балла\n";
            $inviterMessage .= "• Еще 10% кэшбэк когда он продаст первый тур\n";
            $inviterMessage .= "• Постоянный доход 1% с его продаж\n\n";

            if ($curator && $curator['id'] != $agentInfo['id']) {
                $inviterMessage .= "👨‍💼 Куратором назначен: " . $curator['name'] . " " . $curator['famale'] . "\n";
                $inviterMessage .= "📱 +" . $curator['phone'] . "\n\n";
            }

            $inviterMessage .= "🤝 Поддерживайте связь с новичком и помогайте ему развиваться!";

            sendWhatsapp($agentInfo['phone'], $inviterMessage);
        }

        // Сообщение куратору (если он отличается от пригласителя)
        if ($curator && (!$agentInfo || $curator['id'] != $agentInfo['id'])) {
            $curatorMessage = "👨‍🏫 НОВЫЙ ПОДОПЕЧНЫЙ В ВАШЕЙ КОМАНДЕ!\n\n";
            $curatorMessage .= "Вы назначены куратором для нового пользователя:\n";
            $curatorMessage .= "👤 $newUserName\n";
            $curatorMessage .= "📱 +$newUserPhone\n";
            $curatorMessage .= "🎫 Промокод: $genPromocode\n\n";

            if ($agentInfo && $agentInfo['id'] != $curator['id']) {
                $curatorMessage .= "👥 Пригласитель: " . $agentInfo['name'] . " " . $agentInfo['famale'] . "\n";
                $curatorMessage .= "📱 +" . $agentInfo['phone'] . "\n\n";
            }

            $curatorMessage .= "🎯 ВАШИ ЗАДАЧИ:\n";
            $curatorMessage .= "1. Свяжитесь с новичком в течение 24 часов\n";
            $curatorMessage .= "2. Проведите вводный инструктаж\n";
            $curatorMessage .= "3. Помогите с обучением на агента\n";
            $curatorMessage .= "4. Поддерживайте до первых продаж\n\n";

            $curatorMessage .= "💡 Ваш опыт и поддержка - ключ к успеху новичка!\n";
            $curatorMessage .= "Инвестируйте время в его развитие! 🚀";

            sendWhatsapp($curator['phone'], $curatorMessage);
        }
    }

    // 3. Уведомление администраторам
    if (function_exists('adminNotification')) {
        $adminMessage = "📊 НОВАЯ РЕГИСТРАЦИЯ В СИСТЕМЕ\n\n";
        $adminMessage .= "👤 Пользователь: $newUserName\n";
        $adminMessage .= "📱 Телефон: +$newUserPhone\n";
        $adminMessage .= "🎫 Промокод: $genPromocode\n";

        if ($agentInfo) {
            $adminMessage .= "👥 Пригласитель: " . $agentInfo['name'] . " " . $agentInfo['famale'] . " (ID: " . $agentInfo['id'] . ")\n";
        }

        if ($curator) {
            $adminMessage .= "👨‍🏫 Куратор: " . $curator['name'] . " " . $curator['famale'] . " (ID: " . $curator['id'] . ")\n";
        }

        $adminMessage .= "📋 Источник: $source\n";
        $adminMessage .= "💰 Бонус: " . number_format($bonus, 0, ',', ' ') . " тенге\n";
        $adminMessage .= "📅 Дата: " . date('d.m.Y H:i:s');

        adminNotification($adminMessage);
    }

    echo json_encode([
        "type" => true,
        "data" => [
            "user_info" => getUserInfoFromID($lastId)
        ]
    ], JSON_UNESCAPED_UNICODE);

} else {
    // Добавляем отладочную информацию
    $error = $db->error;
    error_log("Registration error: " . $error);

    // Если была предварительная регистрация, возвращаем статус в pending
    if (isset($preRegisterData)) {
        $db->query("UPDATE pre_register SET status = 'pending', date_registered = NULL WHERE id = " . $preRegisterData['id']);
    }

    echo json_encode([
        "type" => false,
        "msg" => 'Error in registration: ' . $error
    ], JSON_UNESCAPED_UNICODE);
}
?>