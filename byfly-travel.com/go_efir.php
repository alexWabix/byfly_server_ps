<?php
// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Проверяем, передан ли код подтверждения
$confirmation_code = isset($_GET['code']) ? trim($_GET['code']) : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Получаем информацию о пользователе
$user = [];
if ($user_id > 0) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_code = trim($_POST['confirmation_code']);
    $user_id = intval($_POST['user_id']);

    // Проверяем код подтверждения
    $stmt = $db->prepare("SELECT * FROM byfly_stream_confirmation 
                         WHERE user_id = ? AND confirmation_code = ?");
    $stmt->bind_param("is", $user_id, $entered_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Обновляем статус подтверждения
        $update = $db->prepare("UPDATE byfly_stream_confirmation 
                              SET confirmed = 1, confirmed_at = NOW() 
                              WHERE user_id = ?");
        $update->bind_param("i", $user_id);
        $update->execute();

        $success = true;
    } else {
        $error = "Неверный код подтверждения. Пожалуйста, проверьте и попробуйте еще раз.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение просмотра эфира | ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #8a2be2;
            --secondary-color: #4b0082;
            --accent-color: #ff00ff;
            --light-color: #f3e6ff;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f3e6ff 0%, #d9b3ff 100%);
            min-height: 100vh;
            color: #333;
        }

        .confirmation-card {
            max-width: 500px;
            margin: 2rem auto;
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-top: 5px solid var(--primary-color);
        }

        .logo {
            max-height: 80px;
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin: 0 auto 1rem;
            display: block;
        }

        .initials-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            font-size: 40px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .confirmation-code {
            letter-spacing: 0.5em;
            font-size: 1.5rem;
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 1rem 0;
        }

        .btn-confirm {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 8px 15px rgba(138, 43, 226, 0.4);
            transition: all 0.3s ease;
            width: 100%;
            color: white;
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px rgba(138, 43, 226, 0.6);
            color: white;
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 5px solid #28a745;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="text-center mb-4">
            <img src="https://byfly-travel.com/images/tild3834-6535-4137-a566-646463623438__noroot.png"
                alt="ByFly Travel" class="logo">
        </div>

        <div class="confirmation-card animate__animated animate__fadeIn">
            <?php if (isset($success)): ?>
                <div class="success-message animate__animated animate__bounceIn">
                    <i class="fas fa-check-circle fa-3x mb-3" style="color: #28a745;"></i>
                    <h3 class="mb-3">Подтверждение получено!</h3>
                    <p class="lead">Спасибо, <?= htmlspecialchars($user['famale'] . ' ' . $user['name']) ?>!</p>
                    <p>Ваше участие в эфире подтверждено. Теперь вы можете участвовать в розыгрыше призов.</p>
                    <a href="https://byfly-travel.com" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-home me-2"></i>Вернуться на сайт
                    </a>
                </div>
            <?php else: ?>
                <h2 class="text-center mb-4"><i class="fas fa-tv me-2"></i>Подтверждение просмотра эфира</h2>

                <?php if (!empty($user)): ?>
                    <div class="text-center mb-4">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>"
                                alt="<?= htmlspecialchars($user['famale'] . ' ' . $user['name']) ?>" class="user-avatar">
                        <?php else: ?>
                            <div class="initials-avatar">
                                <?= mb_substr($user['name'], 0, 1, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($user['famale'] . ' ' . $user['name']) ?></h4>
                        <div><?= htmlspecialchars($user['phone']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">

                    <div class="mb-3">
                        <label for="confirmation_code" class="form-label">Код подтверждения</label>
                        <input type="text" class="form-control form-control-lg text-center confirmation-code"
                            id="confirmation_code" name="confirmation_code"
                            value="<?= htmlspecialchars($confirmation_code) ?>" placeholder="XXXXXX" required>
                        <div class="form-text text-center mt-2">
                            Введите код, который вы получили в уведомлении
                        </div>
                    </div>

                    <button type="submit" class="btn btn-confirm mt-3">
                        <i class="fas fa-check-circle me-2"></i>Подтвердить просмотр
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>