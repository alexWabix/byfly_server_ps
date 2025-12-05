<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поздравляем! Вы нашли секретный приз ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
        }

        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, rgba(241, 250, 238, 0.95) 0%, rgba(168, 218, 220, 0.9) 100%), url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            position: relative;
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

        .prize-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(29, 53, 87, 0.15);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            border: none;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .prize-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(29, 53, 87, 0.25);
        }

        .prize-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .prize-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .prize-header h2 {
            font-weight: 900;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
        }

        .prize-body {
            padding: 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .prize-image {
            height: 200px;
            overflow: hidden;
            position: relative;
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

        .prize-content {
            padding: 25px;
            flex-grow: 1;
        }

        .prize-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(230, 57, 70, 0.2);
        }

        .prize-title {
            color: var(--secondary);
            font-weight: 900;
            margin-bottom: 15px;
            font-size: 1.5rem;
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

        .prize-description {
            color: #555;
            margin-bottom: 20px;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .congrats-container {
            background: linear-gradient(135deg, var(--secondary), var(--dark));
            color: white;
            border-radius: 20px;
            padding: 60px 40px;
            margin: 50px 0;
            box-shadow: 0 20px 50px rgba(29, 53, 87, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 15px solid white;
        }

        .congrats-container::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .congrats-container::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .congrats-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
            letter-spacing: 2px;
        }

        .congrats-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            font-weight: 300;
        }

        .gift-icon {
            font-size: 5rem;
            color: var(--gold);
            margin-bottom: 20px;
            display: inline-block;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
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

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: var(--primary);
            opacity: 0;
            z-index: 10;
            animation: confetti 5s ease-in-out infinite;
        }

        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(500px) rotate(720deg);
                opacity: 0;
            }
        }

        @media (max-width: 992px) {
            .congrats-title {
                font-size: 2.8rem;
            }

            .congrats-subtitle {
                font-size: 1.3rem;
            }

            .prize-image {
                height: 180px;
            }
        }

        @media (max-width: 768px) {
            .congrats-title {
                font-size: 2.2rem;
            }

            .congrats-subtitle {
                font-size: 1.1rem;
            }

            .prize-header {
                padding: 20px;
            }

            .prize-content {
                padding: 20px;
            }

            .prize-title {
                font-size: 1.3rem;
            }

            .watermark {
                font-size: 10rem;
            }
        }

        @media (max-width: 576px) {
            .congrats-container {
                padding: 40px 20px;
                margin: 30px 0;
            }

            .congrats-title {
                font-size: 1.8rem;
                letter-spacing: 1px;
            }

            .gift-icon {
                font-size: 3.5rem;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="container py-5">
        <!-- Конфетти -->
        <div id="confetti-container"></div>

        <div class="congrats-container animate__animated animate__fadeIn">
            <div class="watermark">BFT</div>
            <div class="gift-icon animate__animated animate__bounceIn">
                <i class="fas fa-gift"></i>
            </div>
            <h1 class="congrats-title animate__animated animate__fadeInDown">ПОЗДРАВЛЯЕМ!</h1>
            <p class="congrats-subtitle animate__animated animate__fadeIn" data-wow-delay="0.2s">Вы нашли секретный
                QR-код ByFly Travel в Турции!</p>
            <p class="animate__animated animate__fadeIn" data-wow-delay="0.4s">Ваша наблюдательность и любовь к
                путешествиям принесли вам эти удивительные призы:</p>
        </div>

        <div class="row">
            <!-- Пожизненная подписка -->
            <div class="col-md-6 col-lg-3 mb-4 animate__animated animate__fadeInUp" data-wow-delay="0.1s">
                <div class="prize-card">
                    <div class="prize-header">
                        <h2><i class="fas fa-crown me-2"></i> Premium</h2>
                    </div>
                    <div class="prize-image">
                        <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80"
                            alt="Пожизненная подписка">
                    </div>
                    <div class="prize-content">
                        <div class="text-center">
                            <div class="prize-icon">
                                <i class="fas fa-infinity"></i>
                            </div>
                            <h3 class="prize-title">Пожизненная подписка</h3>
                        </div>
                        <p class="prize-description">Полный доступ ко всем турам в приложении ByFly Travel без комиссий
                            от туроператоров. Экономия на каждом путешествии!</p>
                        <div class="text-center">
                            <span class="badge-custom"><i class="fas fa-check-circle me-1"></i> Безлимитно</span>
                            <span class="badge-custom"><i class="fas fa-percentage me-1"></i> 0% комиссии</span>
                            <span class="badge-custom"><i class="fas fa-globe me-1"></i> Все страны</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Яхта -->
            <div class="col-md-6 col-lg-3 mb-4 animate__animated animate__fadeInUp" data-wow-delay="0.2s">
                <div class="prize-card">
                    <div class="prize-header">
                        <h2><i class="fas fa-ship me-2"></i> Яхтинг</h2>
                    </div>
                    <div class="prize-image">
                        <img src="https://images.unsplash.com/photo-1508974576580-36a2f92ad3bc?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80"
                            alt="Прогулка на яхте">
                    </div>
                    <div class="prize-content">
                        <div class="text-center">
                            <div class="prize-icon">
                                <i class="fas fa-water"></i>
                            </div>
                            <h3 class="prize-title">Прогулка на яхте</h3>
                        </div>
                        <p class="prize-description">Роскошная прогулка на частной яхте по бирюзовым водам Аланьи.
                            Закат, свежий бриз и незабываемые виды Средиземного моря!</p>
                        <div class="text-center">
                            <span class="badge-custom"><i class="fas fa-clock me-1"></i> 4 часа</span>
                            <span class="badge-custom"><i class="fas fa-umbrella-beach me-1"></i> Пляжи</span>
                            <span class="badge-custom"><i class="fas fa-camera me-1"></i> Фотосессия</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Параплан -->
            <div class="col-md-6 col-lg-3 mb-4 animate__animated animate__fadeInUp" data-wow-delay="0.3s">
                <div class="prize-card">
                    <div class="prize-header">
                        <h2><i class="fas fa-parachute-box me-2"></i> Параплан</h2>
                    </div>
                    <div class="prize-image">
                        <img src="https://images.unsplash.com/photo-1551632436-cbf8dd35adfa?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80"
                            alt="Полет на параплане">
                    </div>
                    <div <div class="prize-content">
                        <div class="text-center">
                            <div class="prize-icon">
                                <i class="fas fa-wind"></i>
                            </div>
                            <h3 class="prize-title">Полет на параплане</h3>
                        </div>
                        <p class="prize-description">Захватывающий полет на параплане над побережьем с профессиональным
                            пилотом. Виды, от которых захватывает дух!</p>
                        <div class="text-center">
                            <span class="badge-custom"><i class="fas fa-mountain me-1"></i> Высота 2000м</span>
                            <span class="badge-custom"><i class="fas fa-video me-1"></i> Видеосъемка</span>
                            <span class="badge-custom"><i class="fas fa-life-ring me-1"></i> Безопасность</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Экскурсия -->
            <div class="col-md-6 col-lg-3 mb-4 animate__animated animate__fadeInUp" data-wow-delay="0.4s">
                <div class="prize-card">
                    <div class="prize-header">
                        <h2><i class="fas fa-map-marked-alt me-2"></i> Экскурсия</h2>
                    </div>
                    <div class="prize-image">
                        <img src="https://images.unsplash.com/photo-1591703291603-2150887a3db5?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80"
                            alt="Экскурсия по Анталье">
                    </div>
                    <div class="prize-content">
                        <div class="text-center">
                            <div class="prize-icon">
                                <i class="fas fa-monument"></i>
                            </div>
                            <h3 class="prize-title">Экскурсия по Анталье</h3>
                        </div>
                        <p class="prize-description">Обзорная экскурсия по Анталье с посещением парка Yukari Düden
                            Selalesi, Старого города и других знаковых мест.</p>
                        <div class="text-center">
                            <span class="badge-custom"><i class="fas fa-history me-1"></i> 8 часов</span>
                            <span class="badge-custom"><i class="fas fa-water me-1"></i> Водопады</span>
                            <span class="badge-custom"><i class="fas fa-utensils me-1"></i> Обед включен</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Как получить призы -->
        <div class="text-center mt-5 animate__animated animate__fadeIn" data-wow-delay="0.5s">
            <h2 class="mb-4" style="color: var(--secondary); font-weight: 900;">Как получить призы?</h2>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="alert alert-light"
                        style="border-left: 5px solid var(--primary); box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                        <div class="d-flex align-items-center">
                            <div class="me-4 text-primary" style="font-size: 2rem;">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="text-start">
                                <h4 class="alert-heading" style="color: var(--secondary);">Мы уже занимаемся
                                    органихацией вашего отдыха!</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пожелания -->
        <div class="text-center mt-5 pt-4 animate__animated animate__fadeIn" data-wow-delay="0.6s">
            <h3 style="color: var(--secondary); font-weight: 900;">Желаем вам яркого отдыха в Турции!</h3>
            <p class="lead mb-4">Пусть это путешествие запомнится вам на всю жизнь!</p>

            <div class="d-flex justify-content-center mt-3 mb-5">
                <div class="animate__animated animate__bounceIn" style="animation-delay: 0.7s;">
                    <i class="fas fa-sun fa-2x me-4" style="color: var(--gold);"></i>
                </div>
                <div class="animate__animated animate__bounceIn" style="animation-delay: 0.8s;">
                    <i class="fas fa-umbrella-beach fa-2x me-4" style="color: var(--primary);"></i>
                </div>
                <div class="animate__animated animate__bounceIn" style="animation-delay: 0.9s;">
                    <i class="fas fa-cocktail fa-2x me-4" style="color: var(--accent);"></i>
                </div>
                <div class="animate__animated animate__bounceIn" style="animation-delay: 1s;">
                    <i class="fas fa-smile-beam fa-2x" style="color: var(--primary-dark);"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <footer class="footer animate__animated animate__fadeIn" data-wow-delay="0.7s">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h4 class="mb-3">ByFly Travel</h4>
                    <p class="mb-4">Делаем ваши путешествия незабываемыми с 2023 года</p>



                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <p class="small text-center mb-0">© 2023 ByFly Travel. Все права защищены.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>
        // Инициализация анимаций
        new WOW().init();

        // Создание конфетти
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            const colors = ['#e63946', '#1d3557', '#a8dadc', '#ffd166', '#457b9d'];

            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 3 + 's';
                container.appendChild(confetti);
            }
        }

        // Запуск конфетти при загрузке
        document.addEventListener('DOMContentLoaded', function () {
            createConfetti();

            // Анимация при скролле
            const animateElements = document.querySelectorAll('.animate__animated');

            function checkScroll() {
                animateElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;

                    if (elementTop < windowHeight - 100) {
                        element.style.opacity = '1';
                    }
                });
            }

            window.addEventListener('scroll', checkScroll);
            checkScroll(); // Проверить при загрузке
        });
    </script>
</body>

</html>