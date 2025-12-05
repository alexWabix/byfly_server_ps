<?php
$error = '';
$phoneValue = '';
$passwordValue = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phoneValue = htmlspecialchars($_POST['phone']);
    if (!empty($_POST['phone'])) {
        $phone = preg_replace('/\D/', '', $_POST['phone']);

        $searchManagerDB = $db->query("SELECT * FROM managers WHERE phone_call='" . $phone . "'");
        if ($searchManagerDB->num_rows > 0) {
            $searchManager = $searchManagerDB->fetch_assoc();
            if ($searchManager['password'] == md5($_POST['password'])) {
                setcookie("password", $searchManager['password'], time() + (365 * 24 * 60 * 60), "/");
                setcookie("login", $_POST['phone'], time() + (365 * 24 * 60 * 60), "/");

                header("Location: index.php?" . http_build_query($_GET));
                exit();
            } else {
                $error = 'Неверный пароль!';
                $passwordValue = htmlspecialchars($_POST['password']);
            }
        } else {
            $error = 'Пользователь не найден!';
        }
    } else {
        $error = 'Пожалуйста, введите номер телефона!';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления продажами ByFly Travel</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <style>
        body {
            background-image: url('assets/img/back2.jpg');
            background-size: cover;
            background-position: center;
            background-color: rgba(0, 0, 0, 0.5);
            background-blend-mode: darken;
        }

        .logo {
            width: 200px;
            height: auto;
            margin: 0 auto 20px auto;
            display: block;
        }

        .form-control.error,
        .input-group-text.error {
            border-color: #dc3545;
        }

        .btn-primary {
            background: linear-gradient(to right, #ff512f, #dd2476);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #dd2476, #ff512f);
        }

        .input-group-text {
            background-color: #f0f0f0;
            border-right: none;
            border: 1px solid #ced4da;
            border-radius: 0.25rem 0 0 0.25rem;
        }

        .form-control {
            border-left: none;
            border-radius: 0 0.25rem 0.25rem 0;
            box-shadow: none;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .manager-panel {
            text-align: center;
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 20px;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card w-25">
            <div class="card-body">
                <img src="assets/img/logo-dark.png" alt="Логотип" class="logo">

                <h5 class="card-title mb-4 text-center">Войти в систему</h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Телефон</label>
                        <div class="input-group">
                            <span class="input-group-text<?php echo $error ? ' error' : ''; ?>"><i
                                    class="bi bi-phone"></i></span>
                            <input type="text" name="phone" class="form-control<?php echo $error ? ' error' : ''; ?>"
                                id="phone" placeholder="+7 777 777 77 77" value="<?php echo $phoneValue; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <div class="input-group">
                            <span class="input-group-text<?php echo $error ? ' error' : ''; ?>"><i
                                    class="bi bi-lock"></i></span>
                            <input name="password" type="password"
                                class="form-control<?php echo $error ? ' error' : ''; ?>" id="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#phone').mask('+7 000 000 00 00');

            // Toggle password visibility
            $('#togglePassword').click(function () {
                const passwordInput = $('#password');
                const icon = $('#togglePasswordIcon');
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                }
            });
        });
    </script>
</body>

</html>