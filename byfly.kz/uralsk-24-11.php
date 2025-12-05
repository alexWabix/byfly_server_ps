<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$message = '';

// Проверка и обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $email = htmlspecialchars(trim($_POST['email']));

    // Проверка на пустые поля
    if (empty($name) || empty($phone) || empty($email)) {
        $message = "Пожалуйста, заполните все поля!";
    } else {
        // Проверка на существование записи
        $query = "SELECT COUNT(*) AS count FROM `uralsk_preza` WHERE phone = '$phone' OR email = '$email'";
        $result = $db->query($query);

        if ($result) {
            $row = $result->fetch_assoc();
            $count = $row['count'];

            if ($count > 0) {
                $message = "Вы уже зарегистрированы!";
            } else {
                // Выполнение INSERT-запроса
                $insertQuery = "
                    INSERT INTO `uralsk_preza` (`date_create`, `name`, `phone`, `email`)
                    VALUES (CURRENT_TIMESTAMP, '$name', '$phone', '$email')
                ";

                if ($db->query($insertQuery) === TRUE) {
                    $message = "Спасибо за регистрацию! Мы свяжемся с вами.";
                } else {
                    $message = "Произошла ошибка при регистрации. Попробуйте еще раз.";
                }
            }
        } else {
            $message = "Ошибка при выполнении запроса.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация на презентацию ByFly Travel</title>
    <!-- Подключение Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Фирменные цвета и градиенты */
        :root {
            --red-light: #ff6f61;
            --red-dark: #d32f2f;
            --green-light: #a8e063;
            --green-dark: #4caf50;
            --gradient-red: linear-gradient(135deg, #ff6f61, #d32f2f);
            --gradient-green: linear-gradient(135deg, #a8e063, #4caf50);
        }

        body {
            background: #f5f5f5;
        }

        header {
            background: var(--gradient-red);
            color: white;
        }

        .btn-primary {
            background-color: var(--green-dark);
            border-color: var(--green-dark);
        }

        .btn-primary:hover {
            background-color: var(--green-light);
            border-color: var(--green-light);
        }

        .card {
            border: none;
            border-top: 5px solid var(--red-dark);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        footer {
            background-color: var(--red-dark);
            color: white;
        }
    </style>
</head>

<body>
    <!-- Шапка -->
    <header class="text-center py-5">
        <h1>Презентация ByFly Travel в Уральске</h1>
        <p class="lead">Узнайте, как зарабатывать от 700 000 до 15 000 000 тенге ежемесячно с ByFly
            Travel!</p>
        <h3>9 апреля 2025 года</h3>
    </header>

    <!-- Основной контент -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center">О мероприятии</h2>
                        <p>Основатели компании ByFly Travel посетят Уральск, чтобы рассказать, как любой может стать
                            частью туристического бизнеса и зарабатывать с помощью нашего мобильного приложения.</p>
                        <hr>
                        <h3 class="text-center">Зарегистрируйтесь прямо сейчас!</h3>

                        <!-- Сообщение о статусе регистрации -->
                        <?php if ($message): ?>
                            <div class="alert alert-info text-center">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <!-- Форма регистрации -->
                        <form method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Ваше имя</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Ваш телефон</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Ваш Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Записаться на презентацию</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <footer class="text-center py-4">
        <p>&copy; 2024 ByFly Travel. Все права защищены.</p>
    </footer>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>