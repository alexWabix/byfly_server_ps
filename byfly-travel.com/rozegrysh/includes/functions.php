<?php
function getParticipants()
{
    global $db;

    $query = "SELECT 
                u.id, 
                u.name, 
                u.famale, 
                u.phone, 
                u.avatar,
                u.user_status,
                u.promo_code,
                u.date_registration,
                (SELECT COUNT(*) FROM order_tours 
                 WHERE (user_id = u.id OR saler_id = u.id) AND `type` NOT LIKE 'test'
                 AND status_code NOT IN (0,1,2,5)) as tours_sold,
                (SELECT COUNT(*) FROM users 
                 WHERE parent_user = u.id AND user_status != 'user') as agents_count,
                (SELECT COUNT(*) FROM copilka_ceils 
                 WHERE user_id = u.id AND summ_money >= 100000 
                 AND date_dosrok_close IS NULL) as own_copilka_count,
                (SELECT COUNT(DISTINCT cc.user_id) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND cc.summ_money >= 100000
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_count,
                (SELECT SUM(summ_money) FROM copilka_ceils 
                 WHERE user_id = u.id AND date_dosrok_close IS NULL) as own_copilka_sum,
                (SELECT SUM(cc.summ_money) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_sum,
                (SELECT COUNT(*) FROM users 
                 WHERE parent_user IN (SELECT id FROM users WHERE parent_user = u.id) 
                 AND user_status != 'user') as second_line_agents
              FROM users u
              WHERE u.user_status != 'user'
              HAVING tours_sold >= 2 
                AND agents_count >= 1 
                AND (own_copilka_count >= 1 OR first_line_copilka_count >= 1)
              ORDER BY 
                CASE 
                    WHEN own_copilka_count >= 1 AND first_line_copilka_count >= 1 THEN 
                        (second_line_agents * 3 + agents_count * 2 + tours_sold + FLOOR((own_copilka_sum + first_line_copilka_sum)/50000))
                    WHEN own_copilka_count >= 1 THEN 
                        (second_line_agents * 2 + agents_count + tours_sold + FLOOR(own_copilka_sum/50000))
                    WHEN first_line_copilka_count >= 1 THEN 
                        (second_line_agents + agents_count + tours_sold + FLOOR(first_line_copilka_sum/50000))
                END DESC,
                tours_sold DESC";

    $result = $db->query($query);
    $participants = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['rating'] = calculateParticipantRating($row);
            $participants[] = $row;
        }
    }

    return $participants;
}

function getCandidates()
{
    global $db;

    $query = "SELECT 
                u.id, 
                u.name, 
                u.famale, 
                u.phone, 
                u.avatar,
                u.user_status,
                (SELECT COUNT(*) FROM order_tours 
                 WHERE (user_id = u.id OR saler_id = u.id) 
                 AND status_code NOT IN (0,1,2,5) AND `type` NOT LIKE 'test') as tours_sold,
                (SELECT COUNT(*) FROM users 
                 WHERE parent_user = u.id AND user_status != 'user') as agents_count,
                (SELECT COUNT(*) FROM copilka_ceils 
                 WHERE user_id = u.id AND summ_money >= 100000 
                 AND date_dosrok_close IS NULL) as own_copilka_count,
                (SELECT COUNT(DISTINCT cc.user_id) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND cc.summ_money >= 100000
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_count,
                (SELECT SUM(summ_money) FROM copilka_ceils 
                 WHERE user_id = u.id AND date_dosrok_close IS NULL) as own_copilka_sum,
                (SELECT SUM(cc.summ_money) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_sum
              FROM users u
              WHERE u.user_status IN ('agent', 'user')
              HAVING (tours_sold > 0 OR agents_count > 0 OR own_copilka_count > 0 OR first_line_copilka_count > 0)
              ORDER BY 
                (user_status = 'agent') DESC,
                (tours_sold >= 2) DESC,
                (agents_count >= 1) DESC,
                (own_copilka_count >= 1 OR first_line_copilka_count >= 1) DESC,
                tours_sold DESC, 
                agents_count DESC, 
                (own_copilka_sum + first_line_copilka_sum) DESC
              LIMIT 10";

    $result = $db->query($query);
    $candidates = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['progress'] = calculateProgress($row);
            $candidates[] = $row;
        }
    }

    return $candidates;
}

function checkPromoCode($promoCode)
{
    global $db;

    $promoCode = strtoupper($db->real_escape_string(trim($promoCode)));

    if (empty($promoCode)) {
        return [
            'status' => 'error',
            'message' => 'Промокод не может быть пустым'
        ];
    }

    $query = "SELECT 
                u.id, 
                u.name, 
                u.famale, 
                u.phone, 
                u.avatar,
                u.user_status,
                u.promo_code,
                u.date_registration,
                (SELECT COUNT(*) FROM order_tours 
                 WHERE (user_id = u.id OR saler_id = u.id) 
                 AND status_code NOT IN (0,1,2,5) AND `type` NOT LIKE 'test') as tours_sold,
                (SELECT COUNT(*) FROM users 
                 WHERE parent_user = u.id AND user_status != 'user') as agents_count,
                (SELECT COUNT(*) FROM copilka_ceils 
                 WHERE user_id = u.id AND summ_money >= 100000 
                 AND date_dosrok_close IS NULL) as own_copilka_count,
                (SELECT SUM(summ_money) FROM copilka_ceils 
                 WHERE user_id = u.id AND date_dosrok_close IS NULL) as own_copilka_sum,
                (SELECT COUNT(DISTINCT cc.user_id) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND child.user_status != 'user'
                 AND cc.summ_money >= 100000
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_count,
                (SELECT SUM(cc.summ_money) FROM copilka_ceils cc
                 JOIN users child ON child.id = cc.user_id
                 WHERE child.parent_user = u.id 
                 AND child.user_status != 'user'
                 AND cc.date_dosrok_close IS NULL) as first_line_copilka_sum
              FROM users u
              WHERE u.promo_code = ?";

    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $promoCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $has_copilka = ($user['own_copilka_count'] >= 1 || $user['first_line_copilka_count'] >= 1);

        $requirements = [
            'is_agent' => ($user['user_status'] != 'user'),
            'has_agent' => ($user['agents_count'] >= 1),
            'has_copilka' => $has_copilka,
            'has_tours' => ($user['tours_sold'] >= 2)
        ];

        $completed_count = count(array_filter($requirements));
        $all_met = ($completed_count == 4);

        $recommendations = [];
        if (!$requirements['is_agent']) {
            $recommendations[] = 'Пройдите аттестацию для получения статуса агента';
        }
        if (!$requirements['has_agent']) {
            $recommendations[] = 'Пригласите минимум 1 агента в первую линию';
        }
        if (!$requirements['has_copilka']) {
            $recommendations[] = 'Откройте копилку или пригласите агента с копилкой';
        }
        if (!$requirements['has_tours']) {
            $needed = 2 - $user['tours_sold'];
            $recommendations[] = 'Продайте еще ' . $needed . ' тур(а/ов)';
        }

        $next_draw = getNextDraws(1);
        $next_draw_date = !empty($next_draw) ? date('d.m.Y в H:i', strtotime($next_draw[0]['draw_date'])) : 'не запланирован';

        return [
            'status' => 'success',
            'user' => $user,
            'requirements' => $requirements,
            'completed_count' => $completed_count,
            'all_met' => $all_met,
            'progress_percent' => $completed_count * 25,
            'recommendations' => $recommendations,
            'next_draw_date' => $next_draw_date,
            'rating' => $all_met ? calculateParticipantRating($user) : null,
            'valid_tours' => $user['tours_sold']
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Промокод не найден. Проверьте правильность ввода.'
        ];
    }
}

function calculateParticipantRating($participant)
{
    $rating = 0;

    // Базовые критерии
    $rating += ($participant['user_status'] == 'agent') ? 10 : 0;
    $rating += min($participant['tours_sold'], 10) * 2; // Макс 20 баллов
    $rating += min($participant['agents_count'], 5) * 3; // Макс 15 баллов

    // Учитываем копилку (свою или первой линии)
    $copilka_sum = ($participant['own_copilka_sum'] ?? 0) + ($participant['first_line_copilka_sum'] ?? 0);
    $rating += min(floor($copilka_sum / 50000), 20); // 1 балл за каждые 50 000 тенге, макс 20 баллов

    // Дополнительные критерии
    if (($participant['second_line_agents'] ?? 0) > 0) {
        $rating += min($participant['second_line_agents'], 5) * 4; // Макс 20 баллов
    }

    // Бонус за давность регистрации
    $reg_date = new DateTime($participant['date_registration']);
    $now = new DateTime();
    $months = $now->diff($reg_date)->m + ($now->diff($reg_date)->y * 12);
    $rating += min($months, 24) * 0.5; // Макс 12 баллов

    return min(100, $rating); // Ограничиваем максимум 100 баллами
}

function calculateProgress($user)
{
    $criteria = [
        'is_agent' => ($user['user_status'] == 'agent'),
        'has_agent' => ($user['agents_count'] >= 1),
        'has_copilka' => (($user['own_copilka_count'] ?? 0) >= 1 || ($user['first_line_copilka_count'] ?? 0) >= 1),
        'has_tours' => ($user['tours_sold'] >= 2)
    ];

    return [
        'completed' => count(array_filter($criteria)),
        'total' => count($criteria),
        'details' => $criteria
    ];
}

function getNextDraws($limit = 6)
{
    global $db;

    $query = "SELECT 
                id,
                name_events as title,
                date_event as draw_date,
                CONCAT(
                    DATE_FORMAT(date_event, '%H:%i'), 
                    ' (', 
                    TIMESTAMPDIFF(DAY, NOW(), date_event), 
                    ' дн.)'
                ) as time,
                description,
                prizez
              FROM event_byfly
              WHERE date_event > NOW()
                AND moderation_user_id IS NOT NULL
              ORDER BY date_event ASC
              LIMIT ?";

    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $draws = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $draws[] = $row;
        }
    }

    return $draws;
}

?>