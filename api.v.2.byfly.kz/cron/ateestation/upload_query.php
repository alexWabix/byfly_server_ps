<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
if ($_POST['type'] == 'upload') {
    $_POST['array'] = json_decode($_POST['array'], true);
    foreach ($_POST['array'] as $query) {
        $answers_ru = $query['answers'];
        shuffle($answers_ru);

        $answers_kk = $query['answers'];
        shuffle($answers_kk);

        $answers_en = $query['answers'];
        shuffle($answers_en);
        $db->query("INSERT INTO atestation (`id`, `quest_ru`, `quest_en`, `quest_kk`, `answers_ru`, `answers_kk`, `answers_en`, `category`) 
        VALUES (NULL, '" . $query['question'] . "', '" . $query['question'] . "', '" . $query['question'] . "', '" . json_encode($answers_ru, JSON_UNESCAPED_UNICODE) . "', '" . json_encode($answers_kk, JSON_UNESCAPED_UNICODE) . "', '" . json_encode($answers_en, JSON_UNESCAPED_UNICODE) . "', '" . $query['category'] . "');");
    }
}
?>

<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Добавление вопросов для экзамена!</title>
</head>

<body>
    <div class="container" style="padding-top: 200px;">
        <form style="width: 100%;" method="POST" action="upload_query.php">
            <input type="hidden" name="type" value="upload">
            <textarea name="array" style="width: 100%;" rows="20"></textarea>
            <button type="submit" class="btn btn-success">Загрузить</button>
        </form>
    </div>
</body>

</html>