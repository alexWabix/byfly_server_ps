<?php
function uploadImages($files, $uploadDir = 'uploads/')
{
    global $db;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }


    if (isset($files['hotelImages']) && is_array($files['hotelImages']['tmp_name'])) {
        foreach ($files['hotelImages']['tmp_name'] as $key => $tmpName) {
            $originalName = $files['hotelImages']['name'][$key];
            $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileMimeType = mime_content_type($tmpName);

            if (in_array($fileMimeType, $allowedMimeTypes)) {
                $newFileName = date('Y-m-d_H-i-s') . "_" . uniqid() . "." . $fileExtension;
                $filePath = $uploadDir . $newFileName;
                if (move_uploaded_file($tmpName, $filePath)) {
                    $db->query("INSERT INTO mekka_hotel_image (`id`, `hotel_id`, `image`) VALUES (NULL, '" . $_GET['id'] . "', 'https://manager.byfly.kz/uploads/" . $newFileName . "');");
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateHotel'])) {
    $desc_ru = $db->real_escape_string($_POST['description_ru']);
    $desc_en = $db->real_escape_string($_POST['description_en']);
    $desc_kk = $db->real_escape_string($_POST['description_kk']);

    $db->query("UPDATE mekka_list_hotels SET description_ru='$desc_ru', description_en='$desc_en', description_kk='$desc_kk' WHERE id='" . $_GET['id'] . "'");
}


if ($_GET['type'] == 'loadImage') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hotelImages'])) {
        uploadImages($_FILES);
    }
} else if ($_GET['type'] == 'deleteImage') {
    $db->query("DELETE FROM mekka_hotel_image WHERE id='" . $_GET['imageid'] . "'");
} else if ($_GET['type'] == 'addRoomType') {
    $db->query("INSERT INTO mekka_hotel_rooms (`id`, `hotel_id`, `name_ru`, `name_en`, `name_kk`, `desc_ru`, `desc_en`, `desc_kk`) VALUES (NULL, '" . $_GET['id'] . "', 'Название номера', 'Room name', 'Бөлме атауы', '', '', '');");
} else if ($_GET['type'] == 'deleteRoom') {
    $db->query("DELETE FROM mekka_hotel_rooms WHERE id='" . $_GET['deleteId'] . "'");
}

$hotel = $db->query("SELECT * FROM mekka_list_hotels WHERE id='" . $_GET['id'] . "'")->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Редактирование отеля</title>
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
        <h2 class="mb-4 section-title">Редактировать информацию об отеле</h2>

        <!-- Форма для описания отеля -->
        <form method="POST">
            <div class="description-edit">
                <label for="hotelDescription" class="form-label">Описание отеля (RUS)</label>
                <textarea class="form-control wysiwyg" id="description_ru" name="description_ru"
                    rows="5"><?php echo $hotel['description_ru']; ?></textarea>
            </div>
            <div class="description-edit">
                <label for="hotelDescription" class="form-label">Описание отеля (ENG)</label>
                <textarea class="form-control wysiwyg" id="description_en" name="description_en"
                    rows="5"><?php echo $hotel['description_en']; ?></textarea>
            </div>
            <div class="description-edit">
                <label for="hotelDescription" class="form-label">Описание отеля (KAZ)</label>
                <textarea class="form-control wysiwyg" id="description_kk" name="description_kk"
                    rows="5"><?php echo $hotel['description_kk']; ?></textarea>
            </div>
            <button type="submit" name="updateHotel" value="bfdskj" class="btn btn-success mt-4 mb-5">Сохранить
                изменения</button>
        </form>

        <!-- Форма для загрузки фотографий -->
        <form action="index.php?page=update_hotel&id=<?= $_GET['id'] ?>&type=loadImage" class="mt-5" method="POST"
            enctype="multipart/form-data" id="imageUploadForm">
            <input type="hidden" name="type" value="loadImage">
            <h4 class="section-title">Загрузить фотографии отеля</h4>
            <div class="hotel-images">
                <?php
                $hotelImagesDB = $db->query("SELECT * FROM mekka_hotel_image WHERE hotel_id='" . $_GET['id'] . "'");
                while ($image = $hotelImagesDB->fetch_assoc()) {
                    echo '<div class="hotel-image" style="background-image: url(\'' . $image['image'] . '\');">
                    <a style="text-decoration: none;" href="index.php?page=update_hotel&id=' . $_GET['id'] . '&type=deleteImage&imageid=' . $image['id'] . '" class="btn-remove" onclick="return confirm(\'Вы уверены, что хотите удалить это изображение?\')">X</a>
                  </div>';
                }
                ?>
            </div>

            <input type="file" name="hotelImages[]" id="uploadImages" class="form-control-file" multiple
                style="display: none;" onchange="this.form.submit()">
            <button type="button" class="btn btn-custom"
                onclick="document.getElementById('uploadImages').click();">Загрузить фотографии</button>
        </form>

        <form action="index.php?page=update_hotel&id=<?= $_GET['id'] ?>&type=addRoomType" class="mt-5" method="POST">
            <input type="hidden" name="type" value="addRoomType">
            <h4 class="section-title">Добавить типы номеров</h4>
            <div class="rooms-section">
                <?php
                $roomTypesDB = $db->query("SELECT * FROM mekka_hotel_rooms WHERE hotel_id='" . $_GET['id'] . "'");
                while ($room = $roomTypesDB->fetch_assoc()) {
                    $images = '';
                    $listImageDB = $db->query("SELECT * FROM mekka_rooms_image WHERE room_id='" . $room['id'] . "'");
                    $count = 0;
                    while ($listImage = $listImageDB->fetch_assoc()) {
                        $images .= '<div class="col-md-2 d-flex align-items-center justify-content-center" style="height: 60px; background-image: url(\'' . $listImage['image'] . '\'); background-size: cover; background-position: center center;"></div>';
                        $count++;
                    }

                    if ($count == 0) {
                        $images = '<h5 class="text-start w-100">Нет изображений!</h5>';
                    }
                    echo '<div class="room-type w-100">
                            <div class="row w-100 d-flex align-items-center">
                                <div class="col-md-6 h-100">
                                    <div class="row">
                                        ' . $images . '
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex flex-column justify-content-center h-100">
                                    <span>' . $room['name_ru'] . '</span>
                                    <span>' . $room['name_en'] . '</span>
                                    <span>' . $room['name_kk'] . '</span>
                                </div>
                                <div class="col-md-3 d-flex align-items-center justify-content-center h-100">
                                    <div class="btn-group">
                                        <a href="index.php?page=update_hotel&type=deleteRoom&id=' . $_GET['id'] . '&deleteId=' . $room['id'] . '" class="btn btn-danger" onclick="return confirm(\'Вы уверены, что хотите удалить этот тип номера?\')">Удалить</a>
                                        <a href="index.php?page=update_rooms_hotel&id=' . $_GET['id'] . '&room_id=' . $room['id'] . '" class="btn btn-success">Редактировать</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>';
                }
                ?>
            </div>

            <button type="submit" class="btn btn-custom">Добавить тип номера</button>
        </form>
    </div>

    <script>
        // Функция для автоматической отправки формы при выборе фотографий
        document.getElementById("uploadImages").addEventListener("change", function () {
            document.getElementById("imageUploadForm").submit();
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
    <script>
        // Инициализация CKEditor для всех полей
        ClassicEditor
            .create(document.querySelector('#description_ru'))
            .catch(error => {
                console.error(error);
            });
        ClassicEditor
            .create(document.querySelector('#description_en'))
            .catch(error => {
                console.error(error);
            });
        ClassicEditor
            .create(document.querySelector('#description_kk'))
            .catch(error => {
                console.error(error);
            });
    </script>
</body>

</html>