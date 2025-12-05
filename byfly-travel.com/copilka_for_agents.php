<?php
// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Розыгрыш путешествия в Анталию | ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e63946;
            --primary-light: #ff758f;
            --primary-dark: #c1121f;
            --secondary: #1d3557;
            --secondary-light: #457b9d;
            --accent: #a8dadc;
            --accent-light: #d8f3dc;
            --light: #f1faee;
            --dark: #14213d;
            --gold: #ffd166;
            --success: #2a9d8f;
        }

        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, rgba(241, 250, 238, 0.95) 0%, rgba(168, 218, 220, 0.9) 100%), url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        .display-font {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        .header-section {
            background: linear-gradient(135deg, rgba(29, 53, 87, 0.9) 0%, rgba(20, 33, 61, 0.95) 100%), url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0 60px;
            position: relative;
            margin-bottom: 50px;
            border-bottom: 5px solid var(--primary);
        }

        .header-section::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(135deg, var(--primary), var(--gold));
            z-index: 1;
        }

        .main-title {
            font-size: 3.5rem;
            font-weight: 900;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }

        .sub-title {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .prize-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(29, 53, 87, 0.15);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            border: none;
        }

        .prize-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(29, 53, 87, 0.25);
        }

        .prize-image {
            height: 200px;
            overflow: hidden;
        }

        .prize-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .prize-card:hover .prize-image img {
            transform: scale(1.1);
        }

        .prize-body {
            padding: 25px;
        }

        .prize-title {
            color: var(--secondary);
            font-weight: 900;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .prize-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .badge-custom {
            background: var(--accent);
            color: var(--secondary);
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            margin-right: 8px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .conditions-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin: 50px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .condition-item {
            margin-bottom: 25px;
            position: relative;
            padding-left: 40px;
        }

        .condition-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            width: 25px;
            height: 25px;
            background-color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 12px;
        }

        .condition-item:nth-child(1)::before {
            content: '\f023';
        }

        .condition-item:nth-child(2)::before {
            content: '\f5d3';
        }

        .condition-item:nth-child(3)::before {
            content: '\f073';
        }

        .condition-item:nth-child(4)::before {
            content: '\f559';
        }

        .condition-item:nth-child(5)::before {
            content: '\f02b';
        }

        .check-section {
            background: linear-gradient(135deg, var(--secondary), var(--dark));
            color: white;
            border-radius: 15px;
            padding: 40px;
            margin: 50px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .check-section::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .check-section::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .form-control-custom {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 15px 20px;
            border-radius: 50px;
            transition: all 0.3s;
        }

        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
        }

        .form-control-custom::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 600;
            padding: 15px 40px;
            border-radius: 50px;
            border: none;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(230, 57, 70, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(230, 57, 70, 0.6);
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn-custom:hover::before {
            left: 100%;
        }

        .result-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .progress-container {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-bar-custom {
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--gold));
            border-radius: 5px;
            transition: width 1s ease;
        }

        .user-list {
            margin-top: 20px;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-info {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .user-status {
            margin-left: auto;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-success {
            background: rgba(42, 157, 143, 0.1);
            color: var(--success);
        }

        .status-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .footer {
            background: var(--dark);
            color: white;
            padding: 50px 0 30px;
            text-align: center;
            margin-top: 80px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(135deg, var(--primary), var(--gold));
        }

        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            margin: 0 10px;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .watermark {
            position: absolute;
            font-size: 15rem;
            font-weight: 900;
            color: rgba(255, 255, 255, 0.03);
            z-index: 0;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            pointer-events: none;
            user-select: none;
            font-family: 'Playfair Display', serif;
        }

        @media (max-width: 992px) {
            .main-title {
                font-size: 2.8rem;
            }

            .sub-title {
                font-size: 1.3rem;
            }

            .prize-image {
                height: 180px;
            }
        }

        @media (max-width: 768px) {
            .main-title {
                font-size: 2.2rem;
            }

            .sub-title {
                font-size: 1.1rem;
            }

            .conditions-section,
            .check-section {
                padding: 30px;
            }

            .watermark {
                font-size: 10rem;
            }
        }

        @media (max-width: 576px) {
            .header-section {
                padding: 60px 0 40px;
            }

            .main-title {
                font-size: 1.8rem;
            }

            .btn-custom {
                padding: 12px 30px;
                font-size: 0.9rem;
            }

            .watermark {
                font-size: 7rem;
            }
        }
    </style>
</head>

<body>
    <!-- Шапка с заголовком -->
    <section class="header-section text-center">
        <div class="container">
            <h1 class="main-title animate__animated animate__fadeInDown">РОЗЫГРЫШ ПУТЕШЕСТВИЯ В АНТАЛИЮ</h1>
            <p class="sub-title animate__animated animate__fadeIn" data-wow-delay="0.2s">Выиграй незабываемый отдых на
                побережье Средиземного моря!</p>
            <div class="animate__animated animate__fadeIn" data-wow-delay="0.4s">
                <span class="badge bg-primary fs-5 px-4 py-2 mb-3">Главный приз: 7 дней в Анталье</span>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Приз -->
        <section class="animate__animated animate__fadeIn">
            <div class="row">
                <div class="col-md-6">
                    <div class="prize-card">
                        <div class="prize-image">
                            <img src="https://images.unsplash.com/photo-1519046904884-53103b34b206?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                                alt="Отель в Анталье">
                        </div>
                        <div class="prize-body">
                            <h3 class="prize-title">Отель 4* в Анталье</h3>
                            <p>Роскошный отель с видом на море, бассейном и полным пансионом. Номер на двоих с
                                возможностью заселения одного человека.</p>
                            <div>
                                <span class="badge-custom"><i class="fas fa-umbrella-beach me-1"></i> Первая
                                    линия</span>
                                <span class="badge-custom"><i class="fas fa-utensils me-1"></i> Все включено</span>
                                <span class="badge-custom"><i class="fas fa-calendar-alt me-1"></i> 7 ночей</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="prize-card">
                        <div class="prize-image">
                            <img src="https://images.unsplash.com/photo-1527631746610-bca00a040d60?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                                alt="Авиабилеты">
                        </div>
                        <div class="prize-body">
                            <h3 class="prize-title">Авиабилеты туда-обратно</h3>
                            <p>Перелет эконом-классом из вашего города в Анталью и обратно. Удобные рейсы без пересадок.
                            </p>
                            <div>
                                <span class="badge-custom"><i class="fas fa-plane me-1"></i> Прямой перелет</span>
                                <span class="badge-custom"><i class="fas fa-suitcase me-1"></i> Багаж 20 кг</span>
                                <span class="badge-custom"><i class="fas fa-calendar-day me-1"></i> Любая дата</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Условия розыгрыша -->
        <section class="conditions-section animate__animated animate__fadeIn" data-wow-delay="0.2s">
            <h2 class="text-center mb-4">Условия участия в розыгрыше</h2>

            <div class="condition-item">
                <h4>Активный аккаунт в системе ByFly Travel</h4>
                <p>Вы должны быть зарегистрированы в приложении ByFly Travel и иметь подтвержденный профиль.</p>
            </div>

            <div class="condition-item">
                <h4>5 подписчиков с накопительными ячейками</h4>
                <p>На ваш аккаунт должно быть подписано минимум 5 пользователей, у которых открыты накопительные ячейки.
                </p>
            </div>

            <div class="condition-item">
                <h4>Платежи за 2 месяца</h4>
                <p>У каждого из подписчиков должны быть платежи в накопительных ячейках минимум за 2 месяца.</p>
            </div>

            <div class="condition-item">
                <h4>Срок действия ячеек</h4>
                <p>Накопительные ячейки подписчиков должны быть активны (не просрочены) на момент розыгрыша.</p>
            </div>

            <div class="condition-item">
                <h4>Дата розыгрыша</h4>
                <p>Розыгрыш состоится 15 августа 2023 года. Победитель будет выбран случайным образом среди всех
                    участников, соответствующих условиям.</p>
            </div>
        </section>

        <!-- Проверка условий -->
        <section class="check-section animate__animated animate__fadeIn" data-wow-delay="0.4s">
            <div class="watermark">BFT</div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="text-center text-white mb-4">Проверьте, выполняете ли вы условия</h2>
                    <p class="text-center text-white mb-5">Введите ваш номер телефона, чтобы проверить сколько у вас
                        подписчиков с накопительными ячейками</p>

                    <form id="checkForm" method="POST" action="">
                        <div class="mb-3">
                            <input type="tel" class="form-control form-control-custom" name="phone"
                                placeholder="Ваш номер телефона" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-custom btn-lg">Проверить</button>
                        </div>
                    </form>

                    <!-- Результаты проверки -->
                    <div class="result-card" id="resultCard">
                        <h3 class="text-center mb-4">Результаты проверки</h3>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Всего подписчиков:</span>
                            <strong id="totalSubscribers">0</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Подписчиков с ячейками:</span>
                            <strong id="qualifiedCount">0</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Подписчиков с платежами ≥ 2 мес.:</span>
                            <strong id="activeCount">0</strong>
                        </div>

                        <div class="progress-container">
                            <div class="progress-bar-custom" id="progressBar" style="width: 0%"></div>
                        </div>

                        <div class="text-center mb-3">
                            <span id="progressText">0 из 5 подписчиков соответствуют условиям</span>
                        </div>

                        <div id="successMessage" class="alert alert-success text-center" style="display: none;">
                            <i class="fas fa-check-circle me-2"></i> Поздравляем! Вы участвуете в розыгрыше!
                        </div>

                        <div id="warningMessage" class="alert alert-warning text-center" style="display: none;">
                            <i class="fas fa-exclamation-circle me-2"></i> Вам нужно еще подписчиков с накопительными
                            ячейками для участия
                        </div>

                        <h4 class="mt-4 mb-3">Ваши подписчики:</h4>
                        <div class="user-list" id="userList">
                            <!-- Список пользователей будет загружен здесь -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Как увеличить шансы -->
        <section class="conditions-section animate__animated animate__fadeIn" data-wow-delay="0.6s">
            <h2 class="text-center mb-4">Как увеличить шансы на победу</h2>

            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="bg-light p-4 rounded h-100">
                        <div class="icon-wrapper mb-3" style="font-size: 3rem; color: var(--primary);">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4>Привлекайте больше подписчиков</h4>
                        <p>Чем больше у вас подписчиков с накопительными ячейками, тем выше шансы на победу.</p>
                    </div>
                </div>

                <div class="col-md-4 text-center mb-4">
                    <div class="bg-light p-4 rounded h-100">
                        <div class="icon-wrapper mb-3" style="font-size: 3rem; color: var(--primary);">
                            <i class="fas fa-comment-dollar"></i>
                        </div>
                        <h4>Мотивируйте подписчиков</h4>
                        <p>Расскажите о преимуществах накопительных ячеек и помогите открыть их.</p>
                    </div>
                </div>

                <div class="col-md-4 text-center mb-4">
                    <div class="bg-light p-4 rounded h-100">
                        <div class="icon-wrapper mb-3" style="font-size: 3rem; color: var(--primary);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4>Следите за сроками</h4>
                        <p>Убедитесь, что платежи в ячейках ваших подписчиков регулярные.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Футер -->
    <footer class="footer animate__animated animate__fadeIn" data-wow-delay="0.7s">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h4 class="mb-3">ByFly Travel</h4>
                    <p class="mb-4">Делаем ваши путешествия незабываемыми с 2015 года</p>

                    <div class="d-flex justify-content-center mb-4">
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-telegram-plane"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                    </div>

                    <p class="small mb-1">Круглосуточная поддержка: <a href="tel:+77770000000" class="text-white">+7
                            (777) 000-00-00</a></p>
                    <p class="small mb-0">Email: <a href="mailto:info@byfly-travel.com"
                            class="text-white">info@byfly-travel.com</a></p>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <p class="small text-center mb-0">© 2023 ByFly Travel. Все права защищены.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            // Обработка формы проверки
            $('#checkForm').on('submit', function (e) {
                e.preventDefault();

                // Покажем анимацию загрузки
                $('#resultCard').hide();
                $('#successMessage').hide();
                $('#warningMessage').hide();
                $('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i> Проверяем...').prop('disabled', true);

                // Получаем номер телефона
                const phone = $('input[name="phone"]').val().replace(/\D/g, '');

                // AJAX запрос к серверу
                $.ajax({
                    url: 'check_conditions.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { phone: phone },
                    success: function (response) {
                        if (response.error) {
                            alert(response.error);
                            $('button[type="submit"]').html('Проверить').prop('disabled', false);
                            return;
                        }

                        // Обновляем данные на странице
                        updateResults(response);

                        // Возвращаем кнопку в исходное состояние
                        $('button[type="submit"]').html('Проверить').prop('disabled', false);
                    },
                    error: function (xhr, status, error) {
                        alert('Произошла ошибка при проверке. Пожалуйста, попробуйте позже.');
                        $('button[type="submit"]').html('Проверить').prop('disabled', false);
                        console.error(error);
                    }
                });
            });

            // Функция обновления результатов
            function updateResults(data) {
                const qualifiedCount = data.qualified_count;
                const totalSubscribers = data.total_subscribers;
                const activeCount = data.active_count;
                const totalNeeded = 5;
                const percentage = Math.min(100, (qualifiedCount / totalNeeded) * 100);

                // Обновляем счетчики
                $('#totalSubscribers').text(totalSubscribers);
                $('#qualifiedCount').text(qualifiedCount);
                $('#activeCount').text(activeCount);

                // Обновляем прогресс бар
                $('#progressBar').css('width', percentage + '%');

                // Обновляем текст
                $('#progressText').text(qualifiedCount + ' из ' + totalNeeded + ' подписчиков соответствуют условиям');

                // Показываем соответствующее сообщение
                if (qualifiedCount >= totalNeeded) {
                    $('#successMessage').show();
                    $('#warningMessage').hide();
                } else {
                    $('#successMessage').hide();
                    $('#warningMessage').show();
                }

                // Обновляем список пользователей
                const userList = $('#userList');
                userList.empty();

                data.subscribers.forEach(user => {
                    const statusClass = user.qualified ? 'status-success' : 'status-warning';
                    const statusText = user.qualified ? 'Соответствует' : 'Не соответствует';
                    const paymentsText = user.payments >= 2 ?
                        `${user.payments} мес. (достаточно)` :
                        `${user.payments} мес. (недостаточно)`;

                    // Определяем аватар
                    let avatarHtml = '';
                    if (user.avatar) {
                        avatarHtml = `<img src="${user.avatar}" alt="${user.name}" class="user-avatar">`;
                    } else {
                        const initials = user.name.split(' ').map(n => n[0]).join('');
                        avatarHtml = `<div class="user-avatar">${initials}</div>`;
                    }

                    userList.append(`
                        <div class="user-item">
                            ${avatarHtml}
                            <div>
                                <div class="user-name">${user.name}</div>
                                <div class="user-info">Телефон: ${user.phone}</div>
                                <div class="user-info">Платежей: ${paymentsText}</div>
                            </div>
                            <span class="user-status ${statusClass}">${statusText}</span>
                        </div>
                    `);
                });

                // Показываем карточку с результатами
                $('#resultCard').show();
            }
        });
    </script>
</body>

</html>