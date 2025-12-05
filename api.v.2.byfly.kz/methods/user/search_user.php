<?php

header('Content-Type: application/json');

// Получаем и очищаем текст для поиска
$text_search = trim($_POST['text_search'] ?? '');

if (!empty($text_search)) {
    // Разбиваем текст на слова
    $search_terms = explode(' ', $text_search);
    $search_conditions = [];

    foreach ($search_terms as $term) {
        $escaped_term = $db->real_escape_string(strtolower($term));
        $search_conditions[] = "LOWER(name) LIKE '%$escaped_term%'";
        $search_conditions[] = "LOWER(famale) LIKE '%$escaped_term%'";
        $search_conditions[] = "LOWER(surname) LIKE '%$escaped_term%'";
        $search_conditions[] = "LOWER(phone) LIKE '%$escaped_term%'";
        $search_conditions[] = "CAST(id AS CHAR) LIKE '%$escaped_term%'";
    }

    $where_clause = implode(' OR ', $search_conditions);
    $sql = "SELECT id, name, famale, surname, phone, avatar FROM users WHERE $where_clause ORDER BY id ASC LIMIT 10";
} else {
    // Если текст поиска пустой, выбираем только администраторов
    $sql = "SELECT id, name, famale, surname, phone, avatar FROM users WHERE is_admin = '1' ORDER BY id ASC LIMIT 10";
}

// Логируем SQL для проверки
error_log("Generated SQL: $sql");

$result = $db->query($sql);

// Формируем JSON-ответ
if ($result && $result->num_rows > 0) {
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['type' => true, 'data' => ['users' => $users, 'sql' => $sql]]);
} else {
    echo json_encode(['type' => true, 'data' => ['users' => [], 'sql' => $sql]]);
}
?>