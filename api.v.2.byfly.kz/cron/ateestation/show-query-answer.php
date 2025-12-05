<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if ($_GET['type'] == 'search') {
    $search_user = null;
    $search_user_db = $db->query("SELECT * FROM  users WHERE id='" . $_GET['userId'] . "'");
    if ($search_user_db->num_rows > 0) {
        $search_user = $search_user_db->fetch_assoc();
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

    <title>Проверка вопросов для экзамена!</title>
</head>

<body>
    <div class="container" style="padding-top: 200px;">
        <div class="row">
            <?php
            if ($search_user == null) {
                echo '<h1>Пользователь не найден!</h1>';
            } else {
                $search_user['atestation_query'] = json_decode($search_user['atestation_query'], true);
                foreach ($search_user['atestation_query'] as $query) {
                    $color = 'color: red;';
                    if ($query['answers_ru'][$query['user_selected']]['correct'] != null) {
                        $color = 'color: green;';
                    } else {
                        $color = 'color: red;';
                    }
                    echo '<div class="col-md-12" style="margin-bottom: 30px;">
                        <h3>' . $query['quest_ru'] . '<br><small>
                        <span style="' . $color . 'font-size: 14px; font-style: italic; ">Пользователь выбрал: ' . $query['answers_ru'][$query['user_selected']]['text'] . '</span>
                        </small></h3>
                        
                    ';

                    foreach ($query['answers_ru'] as $answer) {
                        if ($answer['correct'] != null) {
                            echo '<div style="width: 100%; color: green;">' . $answer['text'] . '</div>';
                        } else {
                            echo '<div style="width: 100%; color: red;">' . $answer['text'] . '</div>';
                        }
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</body>

</html>