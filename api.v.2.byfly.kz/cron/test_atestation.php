<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем все записи из таблицы
$listQueryesDB = $db->query("SELECT * FROM atestation");
while ($listQueryes = $listQueryesDB->fetch_assoc()) {
    // Очищаем текст вопросов
    $listQueryes['quest_ru'] = preg_replace('/\s+/', ' ', str_replace(['"', "'"], ' ', $listQueryes['quest_ru']));
    $listQueryes['quest_en'] = preg_replace('/\s+/', ' ', str_replace(['"', "'"], ' ', $listQueryes['quest_en']));
    $listQueryes['quest_kk'] = preg_replace('/\s+/', ' ', str_replace(['"', "'"], ' ', $listQueryes['quest_kk']));

    // Обрабатываем ответы
    $answers_ru = json_decode($listQueryes['answers_ru'], true);
    if (is_array($answers_ru)) {
        foreach ($answers_ru as &$ans) {
            $ans['text'] = preg_replace('/\s+/', ' ', str_replace(['"', "'"], ' ', $ans['text']));
        }
    }
    $listQueryes['answers_ru'] = json_encode($answers_ru, JSON_UNESCAPED_UNICODE);

    $answers_en = json_decode($listQueryes['answers_en'], true);
    if (is_array($answers_en)) {
        foreach ($answers_en as &$ans) {
            $ans['text'] = preg_replace('/\s+/', ' ', str_replace(['"', "'"], ' ', $ans['text']));
        }
    }
    $listQueryes['answers_en'] = json_encode($answers_en, JSON_UNESCAPED_UNICODE);

    $answers_kk = json_decode($listQueryes['answers_kk'], true);
    if (is_array($answers_kk)) {
        foreach ($answers_kk as &$ans) {
            $ans['text'] = preg_replace('/\s+/', ' ', str_replace(['"', "'"], ' ', $ans['text']));
        }
    }
    $listQueryes['answers_kk'] = json_encode($answers_kk, JSON_UNESCAPED_UNICODE);

    // Обновляем запись в базе
    $db->query("
        UPDATE atestation 
        SET 
            quest_ru = '" . $db->real_escape_string($listQueryes['quest_ru']) . "',
            quest_en = '" . $db->real_escape_string($listQueryes['quest_en']) . "',
            quest_kk = '" . $db->real_escape_string($listQueryes['quest_kk']) . "',
            answers_ru = '" . $db->real_escape_string($listQueryes['answers_ru']) . "',
            answers_en = '" . $db->real_escape_string($listQueryes['answers_en']) . "',
            answers_kk = '" . $db->real_escape_string($listQueryes['answers_kk']) . "'
        WHERE id = '" . $listQueryes['id'] . "'
    ");
}
echo "Данные успешно обновлены!";
?>