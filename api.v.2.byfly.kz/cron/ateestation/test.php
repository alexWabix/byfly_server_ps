<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if (empty($_GET['type']) == false) {
    if ($_GET['type'] == 'update') {
        $answers = array(
            array(
                "text" => $_GET['answer1'],
                "correct" => $_GET['valueAnswer1Check'],
            ),
            array(
                "text" => $_GET['answer2'],
                "correct" => $_GET['valueAnswer2Check'],
            ),
            array(
                "text" => $_GET['answer3'],
                "correct" => $_GET['valueAnswer3Check'],
            ),
            array(
                "text" => $_GET['answer4'],
                "correct" => $_GET['valueAnswer4Check'],
            ),
        );
        $db->query("UPDATE atestation SET quest_ru='" . $_GET['query'] . "', quest_en='', quest_kk='', answers_ru='" . json_encode($answers, JSON_UNESCAPED_UNICODE) . "', answers_kk='', answers_en='', checked='1' WHERE id='" . $_GET['id'] . "'");
        header('Location: check_query.php');
    } else if ($_GET['type'] == 'delete') {
        $db->query("DELETE FROM atestation WHERE id='" . $_GET['id'] . "'");
        header('Location: check_query.php');
    }
}

$query = $db->query("SELECT * FROM atestation WHERE checked = '0' ORDER BY RAND() ASC LIMIT 1")->fetch_assoc();
$query['answers_ru'] = json_decode($query['answers_ru'], true);


$countNoCheck = $db->query("SELECT COUNT(*) as ct FROM atestation WHERE checked='0'")->fetch_assoc()['ct'];
$countNoCheck2 = $db->query("SELECT COUNT(*) as ct FROM atestation WHERE checked='1'")->fetch_assoc()['ct'];
$countNoCheck3 = $db->query("SELECT COUNT(*) as ct FROM atestation ")->fetch_assoc()['ct'];
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

        <form action="check_query.php" method="GET">
            <input hidden name="id" value="<?= $query['id'] ?>">
            <input hidden name="type" value="update">
            <div class="row">
                <div class="col-md-12">
                    <input style="width: 100%;" type="text" value="<?= $query['quest_ru'] ?>" name="query">
                </div>

                <br><br><br><br>
                <div id="hidden-block"
                    style="display: none; background-color: green; padding: 20px; color: white; margin-bottom: 20px; border-radius: 10px;">
                    <p>Правильно:</p>
                    <?php
                    foreach ($query['answers_ru'] as $answer) {
                        if ($answer['correct'] == 1 || $answer['correct'] == '1') {
                            echo '<p>
                        ' . $answer['text'] . '
                    </p>';
                        }

                    }
                    ?>
                </div>
                <div class="col-md-12">
                    <label style="width: 90%;" for="valueAnswer1">
                        <input name="answer1" style="width: 90%;" type="text"
                            value="<?= $query['answers_ru'][0]['text'] ?>">
                    </label>
                </div>
                <br><br>
                <div class="col-md-12">
                    <label style="width: 90%;" for="valueAnswer2">
                        <input name="answer2" style="width: 90%;" type="text"
                            value="<?= $query['answers_ru'][1]['text'] ?>">
                    </label>
                </div>
                <br><br>
                <div class="col-md-12">
                    <label style="width: 90%;" for="valueAnswer3">
                        <input name="answer3" style="width: 90%;" type="text"
                            value="<?= $query['answers_ru'][2]['text'] ?>">
                    </label>
                </div>
                <br><br>
                <div class="col-md-12">
                    <label style="width: 90%;" for="valueAnswer4">
                        <input name="answer4" style="width: 90%;" type="text"
                            value="<?= $query['answers_ru'][3]['text'] ?>">
                    </label>
                </div>
                <br><br> <br><br>
                <div class="col-md-12">
                    <div class="btn-group">
                        <a class="btn btn-success btn-lg" href="test.php">Дальше</a>
                        <button type="button" onclick="$('#hidden-block').show(200);"
                            class="btn btn-warning btn-lg">Показать
                            правильные</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</body>

</html>