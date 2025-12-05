<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userPhone = trim($_POST['userPhone']);
    $userAnswer = trim($_POST['userAnswer']);

    if (!empty($userPhone) && !empty($userAnswer)) {
        // Форматируем номер (убираем лишние символы)
        $cleanPhone = preg_replace('/[^0-9]/', '', $userPhone);

        // Отправляем сообщение вам
        $messageToAdmin = "НОВЫЙ ОТВЕТ НА КОНКУРС!\nНомер: $cleanPhone\nОтвет: $userAnswer";
        sendWhatsapp('77780021666', $messageToAdmin); // Ваш номер

        // Сообщение пользователю
        $successMessage = "Ваш ответ отправлен! Спасибо за участие!";
    } else {
        $errorMessage = "Пожалуйста, заполните все поля!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конкурс - Путешествие во Вьетнам</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }

        header {
            background: linear-gradient(90deg, #ff7e5f, #feb47b);
            color: white;
            padding: 30px 0;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.5em;
            margin: 0;
        }

        .question-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .answer-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .answer-input,
        .phone-input {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .submit-btn {
            background: linear-gradient(90deg, #ff7e5f, #feb47b);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.1em;
            border-radius: 5px;
            cursor: pointer;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Выиграй путешествие во Вьетнам!</h1>
            <p>Ответь на вопрос и оставь номер для связи!</p>
        </header>

        <div class="question-box">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php elseif (!empty($errorMessage)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <div style="font-size: 24px; margin-bottom: 20px;" class="question">Вопрос: В течении длительного времени на
                нашем Youtube
                крутился ролик что в этом видео
                ты нашел
                необычного?</div>

            <form method="POST" class="answer-form">
                <input type="tel" class="phone-input" name="userPhone"
                    placeholder="Ваш номер телефона (например, 7771234567)" required>
                <input type="text" class="answer-input" name="userAnswer" placeholder="Ваш ответ..." required>
                <button type="submit" class="submit-btn">Отправить ответ</button>
            </form>
        </div>
    </div>
</body>

</html>