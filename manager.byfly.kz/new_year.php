<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
if ($_POST['type'] == 'sendForm') {
    $fio = $_POST['name'];
    $phone = $_POST['phone'];
    $message = $_POST['message'];
    $city = $_POST['city'];

    $price = ($city === 'Алматы') ? 20000 : 5000;

    $searchPhone = $db->query("SELECT * FROM new_year WHERE phone='" . $phone . "'");
    if ($searchPhone->num_rows > 0) {

        echo json_encode(["status" => "error", "message" => "Номер телефона уже зарегистрирован"]);
        exit;
    } else {
        $query = "INSERT INTO `byfly.2.0`.`new_year` (`id`, `fio`, `phone`, `message`, `city`, `price`, `date_create`, `is_pay`) VALUES (NULL, '$fio', '$phone', '$message', '$city', '$price', CURRENT_TIMESTAMP, '0');";
        $db->query($query);

        if ($city == 'Алматы') {
            sendWhatsapp("77079551038", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
        } else if ($city == 'Уральск') {
            sendWhatsapp("77084340334", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
            sendWhatsapp("77052019563", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
        } else if ($city == 'Астана') {
            sendWhatsapp("77021122545", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
            sendWhatsapp("77025089335", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
        } else if ($city == 'Усть-Каменогорск') {
            sendWhatsapp("77021122545", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
            sendWhatsapp("77021511372", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
        } else if ($city == 'Шымкент') {
            sendWhatsapp("77079010041", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
            sendWhatsapp("77771101777", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);
        }

        sendWhatsapp("77780021666", "Записался на корпоратив ByFly Travel:\n" . $fio . "\n" . $phone . "\nГород: " . $city . "\nСообщение: " . $message);


        echo json_encode(["status" => "success", "message" => "Вы добавлены в список! В течение 10 минут с вами свяжутся."]);
        exit;
    }


}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новогодний корпоратив ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <style>
        body {
            background: linear-gradient(180deg, #800000, #330000);
            font-family: 'Arial', sans-serif;
        }

        .hero {
            background: url('https://byfly.kz/assets/img/hn/3032636.jpg') no-repeat center center;
            background-size: cover;
            color: white;
            text-align: center;
            padding: 100px 20px;
        }

        .hero h1 {
            font-size: 3.5rem;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .form-section {
            background: #ffffff;
            padding: 50px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        .form-section button {
            background-color: #ff6666;
            border: none;
        }

        .form-section button:hover {
            background-color: #e63939;
        }

        .logo {
            width: 150px;
            margin-bottom: 20px;
        }

        .price {
            background: #6f9109;
            color: #ffffff;
            font-weight: bold;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }

        footer {
            color: black;
        }

        .video-text-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .video-text-section .text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }

        .video {
            flex: 0 0 50%;
            max-width: 50%;
            margin: auto;
        }

        .video video {
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        ul.extended-list {
            margin-top: 15px;
            list-style: none;
            padding: 0;
        }

        ul.extended-list li {
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .preloader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .vidos {
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: white;
        }

        .preloader div {
            width: 50px;
            height: 50px;
            border: 5px solid #fff;
            border-top: 5px solid #ff6666;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .text-white {
            color: white;
        }

        .contacts {
            text-align: center;
        }
    </style>
</head>

<body>

    <header class="hero text-white">
        <img src="https://byfly.kz/assets/assets/img/logo.svg" alt="Логотип ByFly Travel" class="logo">
        <h1>Новогодний корпоратив ByFly Travel</h1>
        <p class="lead">Приглашаем вас 30 декабря 2024 года на незабываемую шоу-программу с прямым эфиром между
            городами, розыгрышем ценных призов и путешествий!</p>
        <p>Мы подведем итоги года и расскажем о планах на будущее. Присоединяйтесь!</p>
    </header>

    <main class="container my-5">
        <section>
            <div class="row">
                <div class="col-md-6">
                    <img src="https://byfly.kz/assets/assets/img/2.png" alt="Праздничное изображение"
                        class="img-fluid rounded">
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div>
                        <h2 class="text-white">Что вас ждет:</h2>
                        <ul class="extended-list text-white">
                            <li>🎧 Энергичная музыка от профессионального диджея.</li>
                            <li>✨ Уникальная и необычная шоу-программа, которая удивит каждого гостя.</li>
                            <li>🎁 Невероятные подарки и незабываемые сюрпризы для всех участников.</li>
                            <li>🌍 Шанс выиграть незабываемое путешествие.</li>
                            <li>📊 Подведение итогов года и награждение лучших.</li>
                            <li>🎯 Анонсирование грандиозных планов на следующий год.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <main class="vidos">
        <section class="container mt-5 video-text-section">
            <div class="row">
                <div class="col-md-8 text-dark">
                    <h2>Готовьтесь к незабываемому празднику!</h2>
                    <p>На этом мероприятии вы сможете полностью окунуться в атмосферу праздника. Вас ждет зажигательная
                        музыка, уникальные шоу, веселая компания и много сюрпризов. Это вечер, который вы не забудете!
                    </p>
                    <p>Приходите, чтобы разделить радость с коллегами и друзьями, а также получить шанс выиграть
                        удивительные призы и путешествия!</p>
                    <p>Давайте вместе подведем итоги уходящего года, вспомним все достижения и пройденные этапы. Это
                        уникальная возможность провести время в кругу единомышленников, зарядиться позитивной энергией и
                        вместе обсудить планы на новый год, полный перспектив и возможностей!</p>
                </div>
                <div class="col-md-4 text-white">
                    <div class="video">
                        <video controls>
                            <source src="https://byfly.kz/assets/assets/img/newyear2.mp4" type="video/mp4">
                            Ваш браузер не поддерживает воспроизведение видео.
                        </video>
                    </div>
                </div>
            </div>

        </section>
    </main>

    <main class="container my-5">
        <section class="mt-5">
            <h2 class="text-center text-white">Подайте заявку на участие</h2>
            <div class="form-section mx-auto text-white" style="max-width: 600px;">
                <form id="application-form">
                    <input hidden name="type" value="sendForm">
                    <div>
                        <label for="name" class="form-label">Имя</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Введите ваше имя"
                            required>
                    </div>
                    <div>
                        <label for="phone" class="form-label">Телефон</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Введите ваш телефон"
                            required>
                    </div>
                    <div>
                        <label for="city" class="form-label">Город</label>
                        <select class="form-control" id="city" name="city" required>
                            <option disabled value="">Выберите город</option>
                            <option disabled value="Алматы">Алматы (Мест нет!)</option>
                            <option disabled value="Астана">Астана (Мест нет!)</option>
                            <option disabled value="Шымкент">Шымкент (Мест нет!)</option>
                            <option disabled value="Уральск">Уральск (Мест нет)</option>
                            <option disabled value="Усть-Каменогорск">Усть-Каменогорск (Мест нет)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Сообщение</label>
                        <textarea class="form-control" id="message" name="message" rows="3"
                            placeholder="Ваши пожелания"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Отправить заявку</button>
                </form>
            </div>
        </section>

        <section class="mt-5">
            <h2 class="text-center text-white">Контакты организаторов</h2>
            <ul class="text-white contacts" style="list-style: none; padding: 0;">
                <li><strong>Алматы (Мест нет!):</strong> Баян - <a href="https://wa.me/77079551038"
                        class="text-white">+7 707 955
                        1038</a></li>
                <li><strong>Астана (Мест нет!):</strong> Динара - <a href="https://wa.me/77021122545"
                        class="text-white">+7 702 112
                        2545</a></li>
                <li><strong>Шымкент (Мест нет!):</strong> Бердияр - <a href="https://wa.me/77079010041"
                        class="text-white">+7 707
                        901 0041</a></li>
                <li><strong>Уральск (Мест нет!):</strong> Жанар - <a href="https://wa.me/77084340334"
                        class="text-white">+7 708 434
                        0334</a></li>
                <li><strong>Усть-Каменогорск (Мест нет!):</strong> Динара - <a href="https://wa.me/77021122545"
                        class="text-white">+7 702 112 2545</a></li>
            </ul>
        </section>
    </main>

    <footer class="text-center py-4 mt-5 text-white">
        <p>&copy; 2024 ByFly Travel. Все права защищены.</p>
    </footer>

    <div class="preloader">
        <div></div>
    </div>

    <script>
        $(document).ready(function () {
            $('#phone').mask('+7 (000) 000-00-00');

            $('#application-form').on('submit', function (event) {
                event.preventDefault();
                $('.preloader').fadeIn();
                const formData = $(this).serialize();
                $.post(window.location.href, formData, function (response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        alert(data.message);
                        $('#application-form')[0].reset();
                    } else {
                        alert(data.message);
                    }
                }).fail(function (err) {
                    alert('Произошла ошибка при отправке. Попробуйте еще раз.');
                }).always(function () {
                    $('.preloader').fadeOut();
                });
            });
        });
    </script>
</body>

</html>