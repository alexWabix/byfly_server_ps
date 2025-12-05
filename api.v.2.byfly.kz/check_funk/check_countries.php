<?php
include ('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if ($_GET['type'] == 'save') {
    $db->query("UPDATE countries SET checked='1' WHERE id='" . $_GET['id'] . "'");
    header("Location: check_countries.php");
    exit();
}

$countEmpty = 0;
$countChecked = $db->query("SELECT COUNT(*) as ct FROM countries WHERE checked = '0'")->fetch_assoc()['ct'];

$lsCTDB = $db->query("SELECT id FROM countries");
while ($lsCT = $lsCTDB->fetch_assoc()) {
    $countImage = $db->query("SELECT COUNT(*) as ct FROM countries_image WHERE country_id ='" . $lsCT['id'] . "'")->fetch_assoc()['ct'];
    if ($countImage == 0) {
        $countEmpty++;
    }
}



$data = $db->query("SELECT * FROM countries WHERE checked='0' ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($_GET['type'] == 'delete') {
    $db->query("DELETE from countries_image WHERE id='" . $_GET['id'] . "'");
    header("Location: check_countries.php");
    exit();
}
function sanitizeString($string)
{
    $string = strtolower($string);
    $string = str_replace(' ', '_', $string);
    $string = preg_replace('/[^a-z0-9_.]/', '', $string);
    return $string;
}

function translit($st)
{
    $st = mb_strtolower($st, "utf-8");
    $st = str_replace([
        '?',
        '!',
        '.',
        ',',
        ':',
        ';',
        '*',
        '(',
        ')',
        '{',
        '}',
        '[',
        ']',
        '%',
        '#',
        '№',
        '@',
        '$',
        '^',
        '-',
        '+',
        '/',
        '\\',
        '=',
        '|',
        '"',
        '\'',
        'а',
        'б',
        'в',
        'г',
        'д',
        'е',
        'ё',
        'з',
        'и',
        'й',
        'к',
        'л',
        'м',
        'н',
        'о',
        'п',
        'р',
        'с',
        'т',
        'у',
        'ф',
        'х',
        'ъ',
        'ы',
        'э',
        ' ',
        'ж',
        'ц',
        'ч',
        'ш',
        'щ',
        'ь',
        'ю',
        'я',
    ], [
        '_',
        '_',
        '.',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        '_',
        'a',
        'b',
        'v',
        'g',
        'd',
        'e',
        'e',
        'z',
        'i',
        'y',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'r',
        's',
        't',
        'u',
        'f',
        'h',
        'j',
        'i',
        'e',
        '_',
        'zh',
        'ts',
        'ch',
        'sh',
        'shch',
        '',
        'yu',
        'ya',
    ], $st);
    $st = preg_replace("/[^a-z0-9_.]/", "", $st);
    $st = trim($st, '_');

    $prev_st = '';
    do {
        $prev_st = $st;
        $st = preg_replace("/_[a-z0-9]_/", "_", $st);
    } while ($st != $prev_st);

    $st = preg_replace("/_{2,}/", "_", $st);
    return $st;
}



if (empty($_FILES) == false) {
    $folderName = sanitizeString(translit($data['title']));
    $saveDir = '/var/www/www-root/data/www/api.v.2.byfly.kz/images/countries_image/' . $folderName . '/';
    $realDirPath = $domainApi . 'images/countries_image/' . $folderName . '/';

    if (!file_exists($saveDir)) {
        mkdir($saveDir, 0755, true);
    }

    $fileName = sanitizeString(translit(basename($_FILES["fileToUpload"]["name"])));
    $target_file = $saveDir . $fileName;
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $db->query("INSERT INTO countries_image (`id`, `country_id`, `image`) VALUES (NULL, '" . $data['id'] . "', '" . $realDirPath . $fileName . "');");
    }

    header("Location: check_countries.php");
    exit();
}
?>
<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>Обработка изображений стран</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <style>
        h1 {
            padding: 0px;
            margin: 0pxr
        }
    </style>
</head>

<body>
    <div style="margin-top: 50px; margin-bottom: 50px;" class="container">
        <h1 style="line-height: 24px;">
            <?
            echo $data['title'];
            ?>
            <br>
            <small style="font-size: 14px;">Обработка страны (Осталось пустых <?= $countEmpty; ?>, Не обработанных
                <?= $countChecked; ?>)</small>
        </h1>
        <a href="?type=save&id=<?= $data['id']; ?>" class="btn btn-lg btn-success">Сохранить</a>
    </div>
    <div style="margin-top: 50px; margin-bottom: 50px;" class="container">

        <div class="row">
            <?php
            $imagesDB = $db->query("SELECT * FROM countries_image WHERE country_id ='" . $data['id'] . "'");
            if ($imagesDB->num_rows > 0) {
                while ($images = $imagesDB->fetch_assoc()) {
                    echo '
                        <div style="padding-top: 40px; text-align: center; height: 200px; background-image: url(' . "'" . $images['image'] . "'" . '); background-repeat: no-repeat; background-size: contain; background-position: center center;" class="col-md-4">
                            <a href="?type=delete&id=' . $images['id'] . '" class="btn btn-danger btn-sm">Удалить</a>
                        </div>
                    ';
                }

                echo '<div class="col-md-12"><h1 style="text-align: center;  padding-top: 60px; ">
                        <form id="formSubmit" action="check_countries.php" method="POST" style="text-align: center; width: 100%;" enctype="multipart/form-data">
                           <input type="hidden" name="id" value="' . $data['id'] . '">
                           <div style="width: 100%; text-align: center; margin-bottom: -50px; opacity: 0.0; cursor: pointer;">
                             <input onchange="loadImageToServer();" type="file" name="fileToUpload" id="fileToUpload" style="width: 100%; margin: auto;">
                           </div>
                            <button type="submit" class="btn btn-lg btn-danger">Загрузить</button>
                        </form>
                      </h1>
                    </div>';
            } else {
                echo '<div class="col-md-12"><h1 style="text-align: center;  padding-top: 300px; ">
                        Нет картинок!<br>
                        <form id="formSubmit" action="check_countries.php" method="POST" style="text-align: center; width: 100%;" enctype="multipart/form-data">
                           <input type="hidden" name="id" value="' . $data['id'] . '">
                           <div style="width: 100%; text-align: center; margin-bottom: -50px; opacity: 0.0; cursor: pointer;">
                             <input onchange="loadImageToServer();" type="file" name="fileToUpload" id="fileToUpload" style="width: 100%; margin: auto;">
                           </div>
                            <button type="submit" class="btn btn-lg btn-danger">Загрузить</button>
                        </form>
                      </h1>
                    </div>';
            }
            ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script type="text/javaScript">
            function  loadImageToServer() {
                $("#formSubmit").submit();
            }
        </script>
</body>

</html>