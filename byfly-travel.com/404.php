<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена | ByFly Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --deep-purple: #1A033A;
            --purple: #4A148C;
            --light-purple: #7B1FA2;
            --accent: #9C27B0;
            --pink: #E91E63;
            --text: #FFFFFF;
            --text-light: rgba(255, 255, 255, 0.8);
            --text-lighter: rgba(255, 255, 255, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--deep-purple);
            color: var(--text);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            text-align: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .error-content {
            padding: 60px 0;
            max-width: 800px;
            margin: 0 auto;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(to right, var(--accent), var(--pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .error-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .error-message {
            font-size: 1.2rem;
            margin-bottom: 40px;
            color: var(--text-light);
            max-width: 600px;
        }

        .error-image {
            max-width: 400px;
            width: 100%;
            margin: 0 auto 40px;
        }

        .error-image img {
            width: 100%;
            height: auto;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(90deg, var(--accent), var(--pink));
            color: white;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(156, 39, 176, 0.4);
        }

        footer {
            background: linear-gradient(135deg, var(--purple), var(--deep-purple));
            padding: 30px 0;
            text-align: center;
        }

        .copyright {
            color: var(--text-lighter);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 6rem;
            }

            .error-title {
                font-size: 2rem;
            }

            .error-message {
                font-size: 1rem;
            }

            .error-image {
                max-width: 300px;
            }
        }

        @media (max-width: 576px) {
            .error-code {
                font-size: 4rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-image {
                max-width: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">Страница не найдена</h1>

            <div class="error-image">
                <img src="https://byfly.kz/assets/404-illustration.svg" alt="Ошибка 404"
                    onerror="this.src='https://byfly.kz/assets/logo-610c625f.svg'">
            </div>

            <p class="error-message">
                К сожалению, запрашиваемая вами страница не существует или была перемещена.
                Возможно, вы ошиблись при вводе адреса или перешли по неработающей ссылке.
            </p>

            <a href="index.php" class="button">Все мероприятия</a>
        </div>
    </div>

    <footer>
        <div class="copyright">
            &copy; <?= date('Y') ?> ByFly Travel. Все права защищены.
        </div>
    </footer>
</body>

</html>