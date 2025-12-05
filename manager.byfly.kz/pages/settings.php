<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fio = $db->real_escape_string($_POST['fio']);
    $birth_date = $db->real_escape_string($_POST['birth_date']);
    $phone = $db->real_escape_string($_POST['phone']);
    $whatsapp = $db->real_escape_string($_POST['whatsapp']);
    $email = $db->real_escape_string($_POST['email']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Обновление аватара
    $avatarUrl = $userInfo['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarDir = 'loaded/';
        $avatarPath = $avatarDir . uniqid() . '_' . basename($_FILES['avatar']['name']);
        $avatarUrl = 'https://manager.byfly.kz/' . $avatarPath;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
    }

    // Обновление видео
    $videoUrl = $userInfo['video'];
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoDir = 'loaded/';
        $videoPath = $videoDir . uniqid() . '_' . basename($_FILES['video']['name']);
        $videoUrl = 'https://manager.byfly.kz/' . $videoPath;
        move_uploaded_file($_FILES['video']['tmp_name'], $videoPath);
    }

    // Формирование запроса на обновление данных
    $query = "UPDATE managers SET 
                fio = '$fio', 
                berthday = '$birth_date', 
                phone_call = '$phone', 
                phone_whatsapp = '$whatsapp', 
                email = '$email', 
                avatar = '$avatarUrl', 
                video = '$videoUrl',
                isActive = $isActive
              WHERE id = " . intval($userInfo['id']);

    if ($db->query($query)) {
        $userInfo['fio'] = $fio;
        $userInfo['berthday'] = $birth_date;
        $userInfo['phone_call'] = $phone;
        $userInfo['phone_whatsapp'] = $whatsapp;
        $userInfo['email'] = $email;
        $userInfo['avatar'] = $avatarUrl;
        $userInfo['video'] = $videoUrl;
        $userInfo['isActive'] = $isActive;
        $message = "<div class='alert alert-success'>Данные успешно обновлены.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Ошибка обновления данных: " . $db->error . "</div>";
    }
}

// Обработка данных формы изменения пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        $passwordMessage = "<div class='alert alert-danger'>Все поля обязательны для заполнения.</div>";
    } else {
        $query = "SELECT password FROM managers WHERE id = " . intval($userInfo['id']);
        $result = $db->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            if (md5($currentPassword) === $row['password']) {
                $passwordHash = md5($newPassword);
                $updateQuery = "UPDATE managers SET password = '$passwordHash' WHERE id = " . intval($userInfo['id']);
                if ($db->query($updateQuery)) {
                    setcookie('password', $passwordHash, time() + (86400 * 30), "/"); // Обновление cookie
                    $passwordMessage = "<div class='alert alert-success'>Пароль успешно обновлен.</div>";
                } else {
                    $passwordMessage = "<div class='alert alert-danger'>Ошибка обновления пароля: " . $db->error . "</div>";
                }
            } else {
                $passwordMessage = "<div class='alert alert-danger'>Текущий пароль неверный.</div>";
            }
        } else {
            $passwordMessage = "<div class='alert alert-danger'>Ошибка доступа к базе данных.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(to right, #ae011a, #4a000b);
            padding-left: 20px;
            padding-right: 20px;
        }

        .content {
            margin-top: 20px;
        }

        .form-control,
        .form-select {
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #ae011a;
            border: none;
        }

        .btn-primary:hover {
            background-color: #8b0000;
        }

        .form-text {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ae011a;
            margin-bottom: 20px;
        }

        .video-preview {
            max-width: 100%;
            margin-top: 15px;
            border: 2px solid #ae011a;
            border-radius: 5px;
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
        <h2 class="mb-4 text-start">Редактирование профиля</h2>

        <?php if (!empty($message))
            echo $message; ?>

        <div class="text-start">
            <?php if (!empty($userInfo['avatar'])): ?>
                <img src="<?= htmlspecialchars($userInfo['avatar'], ENT_QUOTES) ?>" alt="Аватар" class="avatar">
            <?php else: ?>
                <img src="default-avatar.png" alt="Аватар" class="avatar">
            <?php endif; ?>
        </div>

        <!-- Форма редактирования профиля -->
        <form id="profileForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <hr>
            <div class="mb-3">
                <label class="form-label">Активность</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                        <?= $userInfo['isActive'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">На смене</label>
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <label for="fio" class="form-label">ФИО</label>
                <input type="text" class="form-control" id="fio" name="fio"
                    value="<?= htmlspecialchars($userInfo['fio'], ENT_QUOTES) ?>" required>
            </div>

            <div class="mb-3">
                <label for="birth_date" class="form-label">Дата рождения</label>
                <input type="date" class="form-control" id="birth_date" name="birth_date"
                    value="<?= htmlspecialchars(substr($userInfo['berthday'], 0, 10), ENT_QUOTES) ?>" required>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Телефон</label>
                <input type="tel" class="form-control" id="phone" name="phone"
                    value="<?= htmlspecialchars($userInfo['phone_call'], ENT_QUOTES) ?>" required>
            </div>

            <div class="mb-3">
                <label for="whatsapp" class="form-label">WhatsApp</label>
                <input type="tel" class="form-control" id="whatsapp" name="whatsapp"
                    value="<?= htmlspecialchars($userInfo['phone_whatsapp'], ENT_QUOTES) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($userInfo['email'], ENT_QUOTES) ?>" required>
            </div>

            <div class="mb-3">
                <label for="avatar" class="form-label">Аватар</label>
                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                <div class="form-text">Загрузите изображение для аватара (форматы: JPG, PNG).</div>
            </div>

            <div class="mb-3">
                <label for="video" class="form-label">Видео</label>
                <input type="file" class="form-control" id="video" name="video" accept="video/*">
                <div class="form-text">Загрузите видео-файл (форматы: MP4, AVI).</div>

                <?php if (!empty($userInfo['video'])): ?>
                    <video style="max-height: 200px;" controls class="video-preview">
                        <source src="<?= htmlspecialchars($userInfo['video'], ENT_QUOTES) ?>" type="video/mp4">
                        Ваш браузер не поддерживает просмотр видео.
                    </video>
                <?php endif; ?>
            </div>



            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>

        <hr>

        <!-- Форма изменения пароля -->
        <h3 class="mt-4">Изменение пароля</h3>

        <?php if (!empty($passwordMessage))
            echo $passwordMessage; ?>

        <form id="changePasswordForm" method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="mb-3">
                <label for="current_password" class="form-label">Текущий пароль</label>
                <input type="password" class="form-control" id="current_password" name="current_password"
                    placeholder="Введите текущий пароль" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Новый пароль</label>
                <input type="password" class="form-control" id="password" name="password"
                    placeholder="Введите новый пароль" required>
            </div>

            <button type="submit" class="btn btn-primary">Обновить пароль</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>