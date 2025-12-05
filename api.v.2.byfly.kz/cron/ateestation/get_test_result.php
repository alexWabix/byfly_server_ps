<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
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
        <form action="show-query-answer.php" method="GET">
            <div class="row">
                <div class="col-md-12">
                    <input type="number" id="userId" name="userId" placeholder="ID пользователя">
                </div>

                <br><br> <br><br>
                <div class="col-md-12">
                    <div class="btn-group">
                        <button name="type" value="search" class="btn btn-success btn-lg"
                            type="submit">Получить</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>

</html>