<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$db_docs->query("SELECT * FROM docs WHERE id=''")
    ?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ByFly Travel - <?php echo $lang['title']; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-end">
            <form method="post" action="">
                <select name="lang" onchange="this.form.submit()">
                    <option value="en" <?php if ($_SESSION['lang'] == 'en')
                        echo 'selected'; ?>>English</option>
                    <option value="ru" <?php if ($_SESSION['lang'] == 'ru')
                        echo 'selected'; ?>>Русский</option>
                </select>
            </form>
        </div>
        <h1><?php echo $lang['title']; ?></h1>
        <p><?php echo $lang['content']; ?></p>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>