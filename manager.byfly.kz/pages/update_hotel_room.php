<?php
$hotel_id = $_GET['id'] ?? 0;

// Обновление данных номера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateRoom'])) {
    $name_ru = $db->real_escape_string($_POST['name_ru']);
    $name_en = $db->real_escape_string($_POST['name_en']);
    $name_kk = $db->real_escape_string($_POST['name_kk']);
    $desc_ru = $db->real_escape_string($_POST['desc_ru']);
    $desc_en = $db->real_escape_string($_POST['desc_en']);
    $desc_kk = $db->real_escape_string($_POST['desc_kk']);

    $db->query("UPDATE mekka_hotel_rooms SET name_ru='$name_ru', name_en='$name_en', name_kk='$name_kk', desc_ru='$desc_ru', desc_en='$desc_en', desc_kk='$desc_kk' WHERE id='" . $_GET['id'] . "'");
}

// Загрузка фотографий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hotelImages'])) {
    uploadImages($_FILES);
}

// Удаление фотографии
if ($_GET['type'] == 'deleteImage') {
    $db->query("DELETE FROM mekka_rooms_image WHERE id='" . $_GET['imageid'] . "'");
}

// Получение данных номера
$room = $db->query("SELECT * FROM mekka_hotel_rooms WHERE hotel_id='$hotel_id'")->fetch_assoc();
$images = $db->query("SELECT * FROM mekka_rooms_image WHERE room_id='$hotel_id'");

function uploadImages($files, $uploadDir = 'uploads/')
{
    global $db, $hotel_id;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($files['hotelImages']['tmp_name'] as $key => $tmpName) {
        $originalName = $files['hotelImages']['name'][$key];
        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileMimeType = mime_content_type($tmpName);

        if (in_array($fileMimeType, $allowedMimeTypes)) {
            $newFileName = date('Y-m-d_H-i-s') . "_" . uniqid() . "." . $fileExtension;
            $filePath = $uploadDir . $newFileName;
            if (move_uploaded_file($tmpName, $filePath)) {
                $db->query("INSERT INTO mekka_rooms_image (room_id, image) VALUES ('$hotel_id', 'https://manager.byfly.kz/uploads/$newFileName')");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование номера</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <style>
        body {
            background-color: #f9f9f9;
        }

        .navbar {
            background: linear-gradient(to right, #ae011a, #4a000b);
            padding: 15px 20px;
        }

        .navbar-brand {
            color: white;
            font-weight: bold;
        }

        .nav-link {
            color: white;
            font-size: 1.1rem;
        }

        .container {
            max-width: 1200px;
        }

        .content {
            margin-top: 40px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .hotel-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .hotel-image {
            position: relative;
            width: 200px;
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .hotel-image:hover {
            transform: scale(1.05);
        }

        .btn-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .btn-remove:hover {
            background-color: #e74c3c;
        }

        .form-control-file {
            display: none;
        }

        .form-label {
            font-weight: bold;
        }

        .description-edit,
        .rooms-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .room-type {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .room-type input {
            margin-right: 10px;
            flex-grow: 1;
        }

        .room-type .btn-danger {
            margin-left: 10px;
        }

        .btn-custom {
            background-color: #ae011a;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #9e0016;
        }

        .modal-content {
            border-radius: 10px;
        }

        .modal-footer .btn-secondary {
            background-color: #ddd;
        }

        .modal-footer .btn-danger {
            background-color: #e74c3c;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <?php include('modules/logo.php'); ?>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <?php include('modules/user_info.php'); ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php include('modules/header.php'); ?>
    <div class="container content">
        <h2 class="section-title">Информация о номере</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <label>Название (RU):</label>
            <input type="text" name="name_ru" value="<?= $room['name_ru'] ?>" class="form-control" required>
            <label class="mt-3">Название (EN):</label>
            <input type="text" name="name_en" value="<?= $room['name_en'] ?>" class="form-control" required>
            <label class="mt-3">Название (KK):</label>
            <input type="text" name="name_kk" value="<?= $room['name_kk'] ?>" class="form-control" required>
            <label class="mt-3">Описание (RU):</label>
            <textarea name="desc_ru" id="desc_ru" class="form-control"><?= $room['desc_ru'] ?></textarea>
            <label class="mt-3">Описание (EN):</label>
            <textarea name="desc_en" id="desc_en" class="form-control"><?= $room['desc_en'] ?></textarea>
            <label class="mt-3">Описание (KK):</label>
            <textarea name="desc_kk" id="desc_kk" class="form-control"><?= $room['desc_kk'] ?></textarea>
            <button type="submit" name="updateRoom" class="btn btn-primary mt-3">Сохранить</button>
        </form>
        <h2 class="section-title mt-4">Фотографии</h2>
        <form class="mt-5" method="POST" enctype="multipart/form-data">
            <input type="file" name="hotelImages[]" multiple class="form-control">
            <button type="submit" class="btn btn-success mt-2">Загрузить</button>
        </form>
        <div class="row mt-3 mb-5">
            <?php while ($img = $images->fetch_assoc()): ?>
                <div class="col-md-3">
                    <img src="<?= $img['image'] ?>" class="img-thumbnail">
                    <a href="?page=update_rooms_hotel&id=<?= $hotel_id ?>&type=deleteImage&imageid=<?= $img['id'] ?>"
                        class="btn btn-danger btn-sm mt-1">Удалить</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
    <script>
        // Инициализация CKEditor для всех полей
        ClassicEditor
            .create(document.querySelector('#desc_ru'))
            .catch(error => {
                console.error(error);
            });
        ClassicEditor
            .create(document.querySelector('#desc_en'))
            .catch(error => {
                console.error(error);
            });
        ClassicEditor
            .create(document.querySelector('#desc_kk'))
            .catch(error => {
                console.error(error);
            });
    </script>
</body>

</html>