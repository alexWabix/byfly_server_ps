<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID заказа из параметров
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    showErrorPage('Неверный запрос', 'ID заказа не указан или имеет неверный формат', 'invalid_id', 0, null);
    exit;
}

// Получаем информацию о заказе
$orderQuery = "SELECT * FROM order_tours WHERE id = $orderId";
$orderResult = $db->query($orderQuery);

if ($orderResult->num_rows == 0) {
    showErrorPage('Заказ не найден', 'Заказ с указанным номером не существует в системе', 'not_found', $orderId, null);
    exit;
}

$order = $orderResult->fetch_assoc();

// Проверяем, не оплачен ли уже заказ
if ($order['includesPrice'] >= $order['price']) {
    header("Location: https://byfly-travel.com/vaucher.php?orderId=$orderId");
    exit;
}

// Проверяем статус заказа
if ($order['status_code'] == 0) {
    showErrorPage('Заявка в обработке', 'Ваша заявка еще не обработана менеджером', 'pending', $orderId, $order);
    exit;
}

if ($order['status_code'] == 5) {
    showErrorPage('Заявка отменена', 'Данная заявка была отменена и не может быть оплачена', 'cancelled', $orderId, $order);
    exit;
}

if ($order['status_code'] == 4) {
    showErrorPage('Турист на отдыхе', 'Турист уже находится на отдыхе, оплата невозможна', 'on_vacation', $orderId, $order);
    exit;
}

// Функция для отображения страницы с ошибкой
function showErrorPage($title, $message, $type, $orderId, $order)
{
    $orderNumber = str_pad($orderId, 8, '0', STR_PAD_LEFT);

    // Определяем иконку и цвета в зависимости от типа ошибки
    $iconClass = '';
    $primaryColor = '';
    $bgGradient = '';
    $actionButton = '';
    $statusDescription = '';
    $showTourInfo = false;

    if ($order) {
        $tourInfo = json_decode($order['tours_info'], true);
        $showTourInfo = true;
    }

    switch ($type) {
        case 'pending':
            $iconClass = '⏳';
            $primaryColor = '#ffa726';
            $bgGradient = 'linear-gradient(135deg, #ffa726 0%, #fb8c00 100%)';
            $actionButton = '<a href="tel:+77273700773" class="btn btn-primary">📞 Связаться с менеджером</a>';
            $statusDescription = 'Ваша заявка находится в очереди на обработку. Наш менеджер свяжется с вами в ближайшее время для подтверждения всех деталей тура.';
            break;

        case 'cancelled':
            $iconClass = '❌';
            $primaryColor = '#ef5350';
            $bgGradient = 'linear-gradient(135deg, #ef5350 0%, #e53935 100%)';
            $actionButton = '<a href="https://byfly-travel.com" class="btn btn-primary">🏠 На главную</a>';
            $statusDescription = 'Заявка могла быть отменена по вашему запросу, из-за отсутствия мест на выбранный тур, или по другим техническим причинам.';
            break;

        case 'awaiting_prepayment':
            $iconClass = '💰';
            $primaryColor = '#42a5f5';
            $bgGradient = 'linear-gradient(135deg, #42a5f5 0%, #1976d2 100%)';
            $actionButton = '<a href="tel:+77273700773" class="btn btn-primary">📞 Связаться с менеджером</a>';
            $statusDescription = 'Тур подтвержден менеджером, но требуется внесение предоплаты для окончательного бронирования. Свяжитесь с менеджером для уточнения деталей.';
            break;

        case 'fully_paid':
            $iconClass = '✅';
            $primaryColor = '#66bb6a';
            $bgGradient = 'linear-gradient(135deg, #66bb6a 0%, #4caf50 100%)';
            $actionButton = '<a href="https://byfly-travel.com/vaucher.php?orderId=' . $orderId . '" class="btn btn-primary">🎫 Получить ваучер</a>';
            $statusDescription = 'Отличные новости! Ваш тур полностью оплачен. Вы можете получить ваучер и подготовиться к путешествию.';
            break;

        case 'on_vacation':
            $iconClass = '🏖️';
            $primaryColor = '#26c6da';
            $bgGradient = 'linear-gradient(135deg, #26c6da 0%, #00acc1 100%)';
            $actionButton = '<a href="https://byfly-travel.com" class="btn btn-primary">🏠 На главную</a>';
            $statusDescription = 'Турист уже находится на отдыхе. Надеемся, что путешествие проходит замечательно!';
            break;

        case 'not_found':
            $iconClass = '🔍';
            $primaryColor = '#ab47bc';
            $bgGradient = 'linear-gradient(135deg, #ab47bc 0%, #8e24aa 100%)';
            $actionButton = '<a href="https://byfly-travel.com" class="btn btn-primary">🏠 На главную</a>';
            $statusDescription = 'Заказ с указанным номером не найден в нашей системе. Возможно, номер указан неверно или заказ был удален.';
            $showTourInfo = false;
            break;

        case 'invalid_id':
            $iconClass = '⚠️';
            $primaryColor = '#ff7043';
            $bgGradient = 'linear-gradient(135deg, #ff7043 0%, #f4511e 100%)';
            $actionButton = '<a href="https://byfly-travel.com" class="btn btn-primary">🏠 На главную</a>';
            $statusDescription = 'Неверный формат номера заказа. Пожалуйста, проверьте ссылку или обратитесь в службу поддержки.';
            $showTourInfo = false;
            break;
    }

    ?>
    <!DOCTYPE html>
    <html lang="ru">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - <?php echo $orderId > 0 ? "Заказ №$orderNumber" : "ByFly Travel"; ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background:
                    <?php echo $bgGradient; ?>
                ;
                min-height: 100vh;
                color: #333;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow-x: hidden;
            }

            .background-animation {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: -1;
            }

            .floating-shape {
                position: absolute;
                opacity: 0.1;
                animation: float 20s infinite linear;
            }

            .floating-shape:nth-child(1) {
                top: 10%;
                left: 10%;
                width: 60px;
                height: 60px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                animation-delay: 0s;
            }

            .floating-shape:nth-child(2) {
                top: 20%;
                right: 20%;
                width: 40px;
                height: 40px;
                background: rgba(255, 255, 255, 0.15);
                border-radius: 50%;
                animation-delay: 7s;
            }

            .floating-shape:nth-child(3) {
                bottom: 30%;
                left: 15%;
                width: 80px;
                height: 80px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                animation-delay: 14s;
            }

            @keyframes float {
                0% {
                    transform: translateY(0px) rotate(0deg);
                }

                33% {
                    transform: translateY(-30px) rotate(120deg);
                }

                66% {
                    transform: translateY(20px) rotate(240deg);
                }

                100% {
                    transform: translateY(0px) rotate(360deg);
                }
            }

            .container {
                max-width: 500px;
                width: 90%;
                background: white;
                border-radius: 20px;
                box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
                overflow: hidden;
                position: relative;
                animation: slideUp 0.8s ease-out;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background:
                    <?php echo $bgGradient; ?>
                ;
            }

            .header {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .header::before {
                content: '';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
                background-size: 20px 20px;
                animation: headerFloat 25s infinite linear;
            }

            @keyframes headerFloat {
                0% {
                    transform: rotate(0deg) translate(-50%, -50%);
                }

                100% {
                    transform: rotate(360deg) translate(-50%, -50%);
                }
            }

            .header-content {
                position: relative;
                z-index: 2;
            }

            .header h1 {
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }

            .order-number {
                font-size: 16px;
                opacity: 0.9;
                font-weight: 500;
            }

            .content {
                padding: 40px 30px;
                text-align: center;
            }

            .status-icon {
                font-size: 80px;
                margin-bottom: 20px;
                display: block;
                animation: statusPulse 2s infinite;
            }

            @keyframes statusPulse {

                0%,
                100% {
                    transform: scale(1);
                    opacity: 1;
                }

                50% {
                    transform: scale(1.05);
                    opacity: 0.8;
                }
            }

            .status-title {
                font-size: 28px;
                font-weight: 700;
                color:
                    <?php echo $primaryColor; ?>
                ;
                margin-bottom: 15px;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .status-message {
                font-size: 18px;
                color: #666;
                line-height: 1.6;
                margin-bottom: 30px;
            }

            .status-details {
                background: rgba(255, 255, 255, 0.8);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                border: 2px solid
                    <?php echo $primaryColor; ?>
                    33;
                backdrop-filter: blur(10px);
            }

            .status-details h4 {
                font-size: 16px;
                margin-bottom: 10px;
                color:
                    <?php echo $primaryColor; ?>
                ;
                font-weight: 600;
            }

            .status-details p {
                font-size: 14px;
                color: #666;
                line-height: 1.5;
            }

            <?php if ($showTourInfo): ?>
                .order-info {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 15px;
                    padding: 25px;
                    margin-bottom: 30px;
                    border-left: 5px solid
                        <?php echo $primaryColor; ?>
                    ;
                    text-align: left;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                }

                .order-info h3 {
                    font-size: 18px;
                    margin-bottom: 15px;
                    color: #495057;
                    display: flex;
                    align-items: center;
                    font-weight: 600;
                }

                .order-info h3::before {
                    content: '🏖️';
                    margin-right: 10px;
                    font-size: 20px;
                }

                .info-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 0;
                    border-bottom: 1px solid #e9ecef;
                }

                .info-row:last-child {
                    border-bottom: none;
                }

                .info-label {
                    font-size: 14px;
                    color: #6c757d;
                    font-weight: 500;
                }

                .info-value {
                    font-size: 14px;
                    font-weight: 600;
                    color: #495057;
                    text-align: right;
                }

                .status-value {
                    color:
                        <?php echo $primaryColor; ?>
                    ;
                    font-weight: 700;
                    padding: 4px 8px;
                    background:
                        <?php echo $primaryColor; ?>
                        20;
                    border-radius: 6px;
                }

            <?php endif; ?>

            .btn {
                display: inline-block;
                padding: 15px 30px;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                transition: all 0.3s ease;
                margin: 10px;
                position: relative;
                overflow: hidden;
                text-align: center;
                min-width: 200px;
            }

            .btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .btn:hover::before {
                left: 100%;
            }

            .btn-primary {
                background:
                    <?php echo $bgGradient; ?>
                ;
                color: white;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }

            .btn-secondary {
                background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }

            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            }

            .contact-info {
                background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
                border-radius: 15px;
                padding: 20px;
                margin-top: 30px;
                text-align: left;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            .contact-info h4 {
                font-size: 16px;
                margin-bottom: 12px;
                color: #1976d2;
                display: flex;
                align-items: center;
                font-weight: 600;
            }

            .contact-info h4::before {
                content: '📞';
                margin-right: 8px;
            }

            .contact-item {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
                font-size: 14px;
                color: #1565c0;
            }

            .contact-item:last-child {
                margin-bottom: 0;
            }

            .contact-item::before {
                content: '•';
                margin-right: 8px;
                color: #1976d2;
                font-weight: bold;
            }

            .additional-info {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #90caf9;
            }

            .additional-info small {
                color: #1565c0;
                font-style: italic;
                display: block;
                line-height: 1.4;
            }

            @media (max-width: 480px) {
                .container {
                    width: 95%;
                    margin: 10px;
                }

                .content {
                    padding: 30px 20px;
                }

                .status-icon {
                    font-size: 60px;
                }

                .status-title {
                    font-size: 24px;
                }

                .status-message {
                    font-size: 16px;
                }

                .btn {
                    min-width: auto;
                    width: 100%;
                }
            }
        </style>
    </head>

    <body>
        <div class="background-animation">
            <div class="floating-shape"></div>
            <div class="floating-shape"></div>
            <div class="floating-shape"></div>
        </div>

        <div class="container">
            <div class="header">
                <div class="header-content">
                    <h1>ByFly Travel</h1>
                    <?php if ($orderId > 0): ?>
                        <div class="order-number">Заказ №<?php echo $orderNumber; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content">
                <span class="status-icon"><?php echo $iconClass; ?></span>
                <h2 class="status-title"><?php echo $title; ?></h2>
                <p class="status-message"><?php echo $message; ?></p>

                <div class="status-details">
                    <h4><?php
                    switch ($type) {
                        case 'pending':
                            echo 'Что происходит?';
                            break;
                        case 'cancelled':
                            echo 'Почему заявка отменена?';
                            break;
                        case 'awaiting_prepayment':
                            echo 'Что нужно сделать?';
                            break;
                        case 'fully_paid':
                            echo 'Что дальше?';
                            break;
                        case 'on_vacation':
                            echo 'Приятного отдыха!';
                            break;
                        case 'not_found':
                            echo 'Что делать?';
                            break;
                        case 'invalid_id':
                            echo 'Как исправить?';
                            break;
                    }
                    ?></h4>
                    <p><?php echo $statusDescription; ?></p>
                </div>

                <?php if ($showTourInfo && $tourInfo): ?>
                    <!-- Информация о туре -->
                    <div class="order-info">
                        <h3><?php echo $tourInfo['countryname']; ?>, <?php echo $tourInfo['hotelname']; ?></h3>
                        <div class="info-row">
                            <span class="info-label">Дата вылета:</span>
                            <span class="info-value"><?php echo formatDate($tourInfo['flydate']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ночей:</span>
                            <span class="info-value"><?php echo $tourInfo['nights']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Питание:</span>
                            <span class="info-value"><?php echo $tourInfo['mealrussian']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Статус:</span>
                            <span class="info-value status-value">
                                <?php
                                switch ($order['status_code']) {
                                    case 0:
                                        echo 'В обработке';
                                        break;
                                    case 1:
                                        echo 'Ожидает предоплату';
                                        break;
                                    case 2:
                                        echo 'Ожидает доплату';
                                        break;
                                    case 3:
                                        echo 'Полностью оплачен';
                                        break;
                                    case 4:
                                        echo 'На отдыхе';
                                        break;
                                    case 5:
                                        echo 'Отменена';
                                        break;
                                    default:
                                        echo 'Неизвестен';
                                        break;
                                }
                                ?>
                            </span>
                        </div>
                        <?php if ($order['price'] > 0): ?>
                            <div class="info-row">
                                <span class="info-label">Стоимость тура:</span>
                                <span class="info-value"><?php echo formatPrice($order['price']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['includesPrice'] > 0): ?>
                            <div class="info-row">
                                <span class="info-label">Уже оплачено:</span>
                                <span class="info-value"><?php echo formatPrice($order['includesPrice']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Кнопки действий -->
                <div style="margin-bottom: 20px;">
                    <?php echo $actionButton; ?>

                    <?php if (in_array($type, ['pending', 'awaiting_prepayment'])): ?>
                        <a href="https://wa.me/77273700773?text=Здравствуйте! У меня вопрос по заказу №<?php echo $orderNumber; ?>"
                            class="btn btn-secondary">💬 WhatsApp</a>
                    <?php endif; ?>
                </div>

                <!-- Контактная информация -->
                <div class="contact-info">
                    <h4>Нужна помощь?</h4>
                    <div class="contact-item">Call-центр: +7 (727) 370-07-73</div>
                    <div class="contact-item">WhatsApp: +7 (727) 370-07-73</div>
                    <div class="contact-item">Режим работы: 24/7</div>

                    <?php if ($type == 'pending'): ?>
                        <div class="additional-info">
                            <small>💡 Обычно обработка заявки занимает от 30 минут до 2 часов в рабочее время</small>
                        </div>
                    <?php elseif ($type == 'awaiting_prepayment'): ?>
                        <div class="additional-info">
                            <small>💰 Размер предоплаты обычно составляет 30-50% от стоимости тура</small>
                        </div>
                    <?php elseif ($type == 'not_found'): ?>
                        <div class="additional-info">
                            <small>🔍 Проверьте правильность номера заказа или обратитесь в службу поддержки</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            // Добавляем интерактивность
            document.addEventListener('DOMContentLoaded', function () {
                // Анимация появления контента
                const container = document.querySelector('.container');

                // Эффект для кнопок
                const buttons = document.querySelectorAll('.btn');
                buttons.forEach(button => {
                    button.addEventListener('mouseenter', function () {
                        this.style.transform = 'translateY(-3px) scale(1.02)';
                    });

                    button.addEventListener('mouseleave', function () {
                        this.style.transform = 'translateY(-2px) scale(1)';
                    });
                });

                <?php if ($type == 'pending'): ?>
                    // Автоматическое обновление страницы каждые 2 минуты для проверки статуса
                    let autoRefreshTimer = setTimeout(() => {
                        if (confirm('Проверить актуальный статус заявки?')) {
                            location.reload();
                        } else {
                            // Если пользователь отказался, предложим еще раз через 5 минут
                            setTimeout(() => {
                                if (confirm('Проверить актуальный статус заявки?')) {
                                    location.reload();
                                }
                            }, 300000); // 5 минут
                        }
                    }, 120000); // 2 минуты

                    // Добавляем кнопку для ручного обновления
                    const refreshButton = document.createElement('button');
                    refreshButton.className = 'btn btn-secondary';
                    refreshButton.innerHTML = '🔄 Проверить статус';
                    refreshButton.style.marginTop = '10px';
                    refreshButton.onclick = () => location.reload();

                    const lastBtn = document.querySelector('.btn');
                    if (lastBtn && lastBtn.parentNode) {
                        lastBtn.parentNode.appendChild(refreshButton);
                    }
                <?php endif; ?>

                // Добавляем эффект снега для новогоднего настроения (если декабрь или январь)
                const currentMonth = new Date().getMonth();
                if (currentMonth === 11 || currentMonth === 0) { // Декабрь или январь
                    createSnowEffect();
                }
            });

            function createSnowEffect() {
                const snowContainer = document.createElement('div');
                snowContainer.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: -1;
                `;
                document.body.appendChild(snowContainer);

                for (let i = 0; i < 50; i++) {
                    const snowflake = document.createElement('div');
                    snowflake.innerHTML = '❄';
                    snowflake.style.cssText = `
                        position: absolute;
                        color: rgba(255, 255, 255, 0.8);
                        font-size: ${Math.random() * 10 + 10}px;
                        left: ${Math.random() * 100}%;
                        animation: fall ${Math.random() * 3 + 2}s linear infinite;
                        animation-delay: ${Math.random() * 2}s;
                    `;
                    snowContainer.appendChild(snowflake);
                }

                const style = document.createElement('style');
                style.textContent = `
                    @keyframes fall {
                        0% { transform: translateY(-100vh) rotate(0deg); }
                        100% { transform: translateY(100vh) rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
        </script>
    </body>

    </html>
    <?php
}

// Функция для форматирования цены
function formatPrice($price)
{
    return number_format($price, 0, ',', ' ') . ' ₸';
}

// Функция для форматирования даты
function formatDate($date)
{
    $months = [
        1 => 'янв',
        2 => 'фев',
        3 => 'мар',
        4 => 'апр',
        5 => 'мая',
        6 => 'июн',
        7 => 'июл',
        8 => 'авг',
        9 => 'сен',
        10 => 'окт',
        11 => 'ноя',
        12 => 'дек'
    ];

    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);

    return "$day $month $year";
}

// Получаем дополнительные оплаты
$dopPaysQuery = "SELECT SUM(summ) as total_dop FROM order_dop_pays WHERE order_id = $orderId";
$dopPaysResult = $db->query($dopPaysQuery);
$totalDopPays = 0;
if ($dopPaysResult->num_rows > 0) {
    $totalDopPays = intval($dopPaysResult->fetch_assoc()['total_dop'] ?? 0);
}

// Рассчитываем сумму к доплате
$totalOrderPrice = $order['price'] + $totalDopPays;
$remainingAmount = $totalOrderPrice - $order['includesPrice'];

if ($remainingAmount <= 0) {
    header("Location: https://byfly-travel.com/vaucher.php?orderId=$orderId");
    exit;
}

// Получаем информацию о туре
$tourInfo = json_decode($order['tours_info'], true);

// Получаем настройки приложения
$settingsQuery = "SELECT kaspi_credit_percentage, kasp_red_percentage FROM app_settings WHERE id = 1";
$settingsResult = $db->query($settingsQuery);
$settings = $settingsResult->fetch_assoc();

// AJAX обработка
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_terminal':
            // Получаем свободный терминал
            $terminalQuery = "SELECT id, port, terminal_name, camera_id, status, operations_count
                             FROM kaspi_terminals 
                             WHERE is_active = 1 AND status = 'free'
                             ORDER BY priority DESC, operations_count ASC
                             LIMIT 1";
            $terminalResult = $db->query($terminalQuery);

            if ($terminalResult->num_rows > 0) {
                echo json_encode(['success' => true, 'terminal' => $terminalResult->fetch_assoc()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Нет свободных терминалов']);
            }
            break;

        case 'create_payment':
            $terminalId = intval($_POST['terminal_id']);
            $amount = intval($_POST['amount']);
            $paymentType = $_POST['payment_type'];
            $totalAmount = intval($_POST['total_amount']);
            $percentage = floatval($_POST['percentage']);

            // Создаем транзакцию
            $insertQuery = "INSERT INTO kaspi_transactions 
                           (terminal_id, amount, payment_type, percentage_fee, clean_amount, total_amount_with_fee, 
                            status, date_initiated, order_id, order_type)
                           VALUES ($terminalId, $amount, '$paymentType', $percentage, $amount, $totalAmount, 
                                   'pending', NOW(), $orderId, 'tour')";

            if ($db->query($insertQuery)) {
                $transactionId = $db->insert_id;

                // Занимаем терминал
                $updateTerminalQuery = "UPDATE kaspi_terminals 
                                       SET status = 'busy', 
                                           operations_count = operations_count + 1,
                                           last_operation_date = NOW(),
                                           last_operation_id = '$transactionId'
                                       WHERE id = $terminalId";
                $db->query($updateTerminalQuery);

                echo json_encode(['success' => true, 'transaction_id' => $transactionId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка создания транзакции']);
            }
            break;

        case 'initiate_terminal_payment':
            $terminalId = intval($_POST['terminal_id']);
            $transactionId = intval($_POST['transaction_id']);
            $totalAmount = intval($_POST['total_amount']);

            // Получаем порт терминала
            $terminalQuery = "SELECT port FROM kaspi_terminals WHERE id = $terminalId";
            $terminalResult = $db->query($terminalQuery);
            $terminal = $terminalResult->fetch_assoc();

            // Инициируем платеж на терминале
            $url = "http://109.175.215.40:{$terminal['port']}/v2/payment?amount=$totalAmount";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'method' => 'GET'
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data['statusCode'] == 0 && isset($data['data']['processId'])) {
                    $processId = $data['data']['processId'];

                    // Обновляем транзакцию
                    $updateQuery = "UPDATE kaspi_transactions 
                                   SET terminal_operation_id = '$processId', 
                                       status = 'processing',
                                       terminal_response = '" . base64_encode($response) . "'
                                   WHERE id = $transactionId";
                    $db->query($updateQuery);

                    echo json_encode(['success' => true, 'process_id' => $processId]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка терминала']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Терминал недоступен']);
            }
            break;

        case 'get_qr_code':
            $terminalId = intval($_POST['terminal_id']);
            $transactionId = intval($_POST['transaction_id']);

            // Получаем camera_id терминала
            $terminalQuery = "SELECT camera_id FROM kaspi_terminals WHERE id = $terminalId";
            $terminalResult = $db->query($terminalQuery);
            $terminal = $terminalResult->fetch_assoc();

            // Сканируем QR код
            $url = "http://109.175.215.40:3000/scan-qr/{$terminal['camera_id']}?timeout=120000";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 150,
                    'method' => 'GET'
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data['success'] && isset($data['qrData'])) {
                    $qrData = $data['qrData'];
                    $paymentUrl = str_replace('qr.kaspi.kz', 'pay.kaspi.kz/pay', $qrData);

                    // Обновляем транзакцию
                    $updateQuery = "UPDATE kaspi_transactions 
                                   SET qr_code_url = '" . addslashes($qrData) . "', 
                                       payment_url = '" . addslashes($paymentUrl) . "'
                                   WHERE id = $transactionId";
                    $db->query($updateQuery);

                    echo json_encode(['success' => true, 'qr_data' => $qrData, 'payment_url' => $paymentUrl]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Не удалось считать QR код']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка сканирования QR']);
            }
            break;

        case 'check_payment_status':
            $terminalId = intval($_POST['terminal_id']);
            $processId = $_POST['process_id'];
            $transactionId = intval($_POST['transaction_id']);
            $selectedPaymentType = $_POST['selected_payment_type'] ?? 'cash';

            // Сначала проверяем, не был ли уже обработан платеж кроном
            $checkProcessedQuery = "SELECT kt.status, ot.includesPrice 
                                   FROM kaspi_transactions kt
                                   JOIN order_tours ot ON kt.order_id = ot.id
                                   WHERE kt.id = $transactionId";
            $checkResult = $db->query($checkProcessedQuery);
            $checkData = $checkResult->fetch_assoc();

            if ($checkData['status'] === 'completed') {
                // Платеж уже обработан кроном
                echo json_encode(['success' => true, 'status' => 'completed', 'processed_by' => 'cron']);
                break;
            }

            // Получаем порт терминала
            $terminalQuery = "SELECT port FROM kaspi_terminals WHERE id = $terminalId";
            $terminalResult = $db->query($terminalQuery);
            $terminal = $terminalResult->fetch_assoc();

            // Проверяем статус на терминале
            $url = "http://109.175.215.40:{$terminal['port']}/v2/status?processId=$processId";
            $response = file_get_contents($url);

            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data['statusCode'] == 0 && isset($data['data']['status'])) {
                    $status = $data['data']['status'];
                    $subStatus = $data['data']['subStatus'] ?? '';



                    if ($status == 'success') {
                        // Получаем информацию о платеже для проверки способа оплаты
                        $chequeInfo = $data['data']['chequeInfo'] ?? [];
                        $actualPaymentMethod = $chequeInfo['method'] ?? '';

                        // Проверяем, соответствует ли способ оплаты выбранному
                        $paymentMismatch = false;
                        $mismatchMessage = '';

                        if ($selectedPaymentType === 'cash' && in_array($actualPaymentMethod, ['credit', 'installment'])) {
                            $paymentMismatch = true;
                            $mismatchMessage = 'Вы выбрали оплату Kaspi Gold, но оплатили в кредит/рассрочку. За тур поступит сумма за минусом процентов банка. Потребуется доплата.';
                        } elseif ($selectedPaymentType === 'kaspi_red' && $actualPaymentMethod === 'credit') {
                            $paymentMismatch = true;
                            $mismatchMessage = 'Вы выбрали Kaspi Red, но оплатили в кредит. За тур поступит сумма за минусом процентов банка. Потребуется доплата.';
                        }

                        try {
                            // Еще раз проверяем статус транзакции
                            $recheckQuery = "SELECT status, clean_amount FROM kaspi_transactions WHERE id = $transactionId";
                            $recheckResult = $db->query($recheckQuery);
                            $recheckTransaction = $recheckResult->fetch_assoc();

                            if ($recheckTransaction['status'] === 'completed') {
                                echo json_encode(['success' => true, 'status' => 'completed', 'processed_by' => 'cron']);
                                break;
                            }

                            $transactionNumber = $data['data']['transactionId'] ?? $processId;

                            $orderQuery = "SELECT includesPrice FROM order_tours WHERE id = $orderId";
                            $orderResult = $db->query($orderQuery);
                            $currentOrder = $orderResult->fetch_assoc();


                            $response = [
                                'success' => true,
                                'status' => 'completed',
                                'processed_by' => 'page',
                                'data' => $data['data']
                            ];

                            // Добавляем предупреждение о несоответствии способа оплаты
                            if ($paymentMismatch) {
                                $response['payment_mismatch'] = true;
                                $response['mismatch_message'] = $mismatchMessage;
                            }

                            echo json_encode($response);

                        } catch (Exception $e) {
                            echo json_encode(['success' => false, 'message' => 'Ошибка обработки платежа']);
                        }
                    } elseif ($status == 'fail') {
                        echo json_encode(['success' => true, 'status' => 'failed']);
                    } else {
                        echo json_encode(['success' => true, 'status' => $status]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка проверки статуса']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Терминал недоступен']);
            }
            break;

        case 'cancel_payment':
            $terminalId = intval($_POST['terminal_id']);
            $processId = $_POST['process_id'];
            $transactionId = intval($_POST['transaction_id']);

            // Получаем порт терминала
            $terminalQuery = "SELECT port FROM kaspi_terminals WHERE id = $terminalId";
            $terminalResult = $db->query($terminalQuery);
            $terminal = $terminalResult->fetch_assoc();

            // Отменяем платеж на терминале
            $url = "http://109.175.215.40:{$terminal['port']}/v2/cancel?processId=$processId";
            file_get_contents($url);

            // Обновляем транзакцию
            $updateQuery = "UPDATE kaspi_transactions 
                           SET status = 'cancelled', 
                               error_message = 'Отменено пользователем',
                               date_completed = NOW()
                           WHERE id = $transactionId";
            $db->query($updateQuery);

            // Освобождаем терминал
            $freeTerminalQuery = "UPDATE kaspi_terminals SET status = 'free' WHERE id = $terminalId";
            $db->query($freeTerminalQuery);

            echo json_encode(['success' => true]);
            break;
    }
    exit;
}

// Функция отправки уведомлений
function sendPaymentNotifications($orderId, $amount, $transactionNumber)
{
    global $db;

    // Получаем информацию о заказе
    $query = "SELECT ot.*, u.phone as user_phone, u.name as user_name, u.famale as user_famale,
                     m.phone_whatsapp as manager_phone, m.fio as manager_name
              FROM order_tours ot
              LEFT JOIN users u ON ot.user_id = u.id
              LEFT JOIN managers m ON ot.manager_id = m.id
              WHERE ot.id = $orderId";

    $result = $db->query($query);
    if ($result->num_rows == 0)
        return;

    $order = $result->fetch_assoc();
    $tourInfo = json_decode($order['tours_info'], true);
    $orderNumber = str_pad($orderId, 8, '0', STR_PAD_LEFT);

    // Уведомление клиенту
    if ($order['user_phone']) {
        $clientMessage = "✅ Платеж успешно получен!\n\n";
        $clientMessage .= "🎫 Заказ №$orderNumber\n";
        $clientMessage .= "🏖️ {$tourInfo['countryname']}, {$tourInfo['hotelname']}\n";
        $clientMessage .= "💰 Оплачено: " . number_format($amount, 0, ',', ' ') . " ₸\n";
        $clientMessage .= "🧾 № транзакции: $transactionNumber\n\n";
        $clientMessage .= "💡 Средства будут зачислены по туру в течение 2 минут\n\n";
        $clientMessage .= "📋 Получить ваучер: https://byfly-travel.com/vaucher.php?orderId=$orderId\n\n";
        $clientMessage .= "Спасибо за выбор ByFly Travel! 🌟";

        sendWhatsapp($order['user_phone'], $clientMessage);
    }

    // Уведомление продавцу (если есть)
    if ($order['sub_user'] > 0) {
        $sellerQuery = "SELECT phone, name, famale FROM users WHERE id = {$order['sub_user']}";
        $sellerResult = $db->query($sellerQuery);
        if ($sellerResult->num_rows > 0) {
            $seller = $sellerResult->fetch_assoc();

            $sellerMessage = "💰 Получена оплата по вашей продаже!\n\n";
            $sellerMessage .= "🎫 Заказ №$orderNumber\n";
            $sellerMessage .= "👤 Клиент: {$order['user_name']} {$order['user_famale']}\n";
            $sellerMessage .= "🏖️ Тур: {$tourInfo['countryname']}, {$tourInfo['hotelname']}\n";
            $sellerMessage .= "💰 Сумма: " . number_format($amount, 0, ',', ' ') . " ₸\n";
            $sellerMessage .= "🧾 № транзакции: $transactionNumber\n\n";
            $sellerMessage .= "Отличная работа! 👏";

            sendWhatsapp($seller['phone'], $sellerMessage);
        }
    }

    // Уведомление менеджеру
    if ($order['manager_phone']) {
        $managerMessage = "💳 Поступила оплата по заказу\n\n";
        $managerMessage .= "🎫 Заказ №$orderNumber\n";
        $managerMessage .= "👤 Клиент: {$order['user_name']} {$order['user_famale']}\n";
        $managerMessage .= "📞 Телефон: {$order['user_phone']}\n";
        $managerMessage .= "🏖️ Тур: {$tourInfo['countryname']}, {$tourInfo['hotelname']}\n";
        $managerMessage .= "💰 Сумма: " . number_format($amount, 0, ',', ' ') . " ₸\n";
        $managerMessage .= "🧾 № транзакции: $transactionNumber\n\n";
        $managerMessage .= "Требуется обработка заказа 📋";

        sendWhatsapp($order['manager_phone'], $managerMessage);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата тура - Заказ №<?php echo str_pad($orderId, 8, '0', STR_PAD_LEFT); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 480px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.3;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .logo {
            height: 40px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .order-number {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 20px;
        }

        .order-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .order-info h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #495057;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 14px;
            color: #6c757d;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            text-align: right;
        }

        .payment-amount {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .payment-amount h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .payment-amount p {
            font-size: 14px;
            opacity: 0.9;
        }

        .payment-methods {
            margin-bottom: 20px;
        }

        .payment-methods h3 {
            font-size: 18px;
            margin-bottom: 16px;
            color: #495057;
        }

        .payment-option {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payment-option:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }

        .payment-option.selected {
            border-color: #007bff;
            background: #f8f9ff;
        }

        .payment-option-left {
            display: flex;
            align-items: center;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 18px;
        }

        .payment-icon.kaspi-gold {
            background: #e3f2fd;
            color: #1976d2;
        }

        .payment-icon.kaspi-red {
            background: #ffebee;
            color: #d32f2f;
        }

        .payment-icon.kaspi-credit {
            background: #fff3e0;
            color: #f57c00;
        }

        .payment-details h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .payment-details p {
            font-size: 12px;
            color: #6c757d;
        }

        .payment-amount-detail {
            text-align: right;
        }

        .payment-amount-detail .original {
            font-size: 12px;
            color: #6c757d;
        }

        .payment-amount-detail .total {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-content {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .waiting-terminals,
        .payment-screen,
        .success-screen,
        .error-screen {
            display: none;
            text-align: center;
            padding: 40px 20px;
        }

        .pulse-icon {
            font-size: 60px;
            animation: pulse 2s infinite;
            margin-bottom: 20px;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.7;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .timer {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .timer.warning {
            animation: blink 1s infinite;
        }

        @keyframes blink {

            0%,
            50% {
                opacity: 1;
            }

            51%,
            100% {
                opacity: 0.7;
            }
        }

        .timer-display {
            font-size: 32px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .payment-link-ready {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payment-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .waiting-terminals h2,
        .payment-screen h2,
        .success-screen h2,
        .error-screen h2 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #495057;
        }

        .waiting-terminals p,
        .payment-screen p,
        .success-screen p,
        .error-screen p {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .warning-message {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #fd7e14;
        }

        .warning-message h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .warning-message h4::before {
            content: '⚠️';
            margin-right: 8px;
            font-size: 18px;
        }

        .warning-message p {
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        @media (max-width: 480px) {
            .container {
                max-width: 100%;
            }

            .content {
                padding: 16px;
            }

            .payment-amount h2 {
                font-size: 24px;
            }

            .timer-display {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Главный экран -->
        <div id="main-screen">
            <div class="header">
                <div class="header-content">
                    <h1>Оплата тура</h1>
                    <div class="order-number">Заказ №<?php echo str_pad($orderId, 8, '0', STR_PAD_LEFT); ?></div>
                </div>
            </div>

            <div class="content">
                <!-- Информация о туре -->
                <div class="order-info">
                    <h3>🏖️ <?php echo $tourInfo['countryname']; ?>, <?php echo $tourInfo['hotelname']; ?></h3>
                    <div class="info-row">
                        <span class="info-label">Дата вылета:</span>
                        <span class="info-value"><?php echo formatDate($tourInfo['flydate']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ночей:</span>
                        <span class="info-value"><?php echo $tourInfo['nights']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Питание:</span>
                        <span class="info-value"><?php echo $tourInfo['mealrussian']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Общая стоимость:</span>
                        <span class="info-value"><?php echo formatPrice($totalOrderPrice); ?></span>
                    </div>
                    <?php if ($totalDopPays > 0): ?>
                        <div class="info-row">
                            <span class="info-label">Доп. услуги:</span>
                            <span class="info-value"><?php echo formatPrice($totalDopPays); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Уже оплачено:</span>
                        <span class="info-value"><?php echo formatPrice($order['includesPrice']); ?></span>
                    </div>
                </div>

                <!-- Сумма к доплате -->
                <div class="payment-amount">
                    <h2><?php echo formatPrice($remainingAmount); ?></h2>
                    <p>К доплате</p>
                </div>

                <!-- Способы оплаты -->
                <div class="payment-methods">
                    <h3>Выберите способ оплаты:</h3>

                    <!-- Kaspi Gold -->
                    <div class="payment-option" data-type="cash" data-percentage="0">
                        <div class="payment-option-left">
                            <div class="payment-icon kaspi-gold">💳</div>
                            <div class="payment-details">
                                <h4>Kaspi Gold</h4>
                                <p>Без комиссии</p>
                            </div>
                        </div>
                        <div class="payment-amount-detail">
                            <div class="total"><?php echo formatPrice($remainingAmount); ?></div>
                        </div>
                    </div>

                    <!-- Kaspi Red -->
                    <div class="payment-option" data-type="kaspi_red"
                        data-percentage="<?php echo $settings['kasp_red_percentage']; ?>">
                        <div class="payment-option-left">
                            <div class="payment-icon kaspi-red">🔴</div>
                            <div class="payment-details">
                                <h4>Kaspi Red</h4>
                                <p>Комиссия <?php echo $settings['kasp_red_percentage']; ?>%</p>
                            </div>
                        </div>
                        <div class="payment-amount-detail">
                            <div class="original"><?php echo formatPrice($remainingAmount); ?></div>
                            <div class="total">
                                <?php echo formatPrice($remainingAmount + (($remainingAmount * $settings['kasp_red_percentage']) / 100)); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kaspi Кредит -->
                    <div class="payment-option" data-type="credit"
                        data-percentage="<?php echo $settings['kaspi_credit_percentage']; ?>">
                        <div class="payment-option-left">
                            <div class="payment-icon kaspi-credit">💰</div>
                            <div class="payment-details">
                                <h4>Kaspi Кредит</h4>
                                <p>Рассрочка <?php echo $settings['kaspi_credit_percentage']; ?>%</p>
                            </div>
                        </div>
                        <div class="payment-amount-detail">
                            <div class="original"><?php echo formatPrice($remainingAmount); ?></div>
                            <div class="total">
                                <?php echo formatPrice(ceil(($remainingAmount * 100) / (100 - $settings['kaspi_credit_percentage']))); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Кнопка оплаты -->
                <button id="pay-btn" class="btn btn-primary" disabled>
                    Оплатить
                </button>
            </div>
        </div>

        <!-- Экран ожидания терминала -->
        <div id="waiting-terminals" class="waiting-terminals">
            <div class="pulse-icon">⏳</div>
            <h2>Все терминалы заняты</h2>
            <p>Ожидаем освобождения терминала...</p>
            <div style="margin: 20px 0;">
                <div>Время ожидания: <span id="waiting-time">0с</span></div>
            </div>
            <button id="refresh-terminals" class="btn btn-secondary">Проверить снова</button>
        </div>

        <!-- Экран оплаты -->
        <div id="payment-screen" class="payment-screen">
            <!-- Таймер -->
            <div id="timer" class="timer">
                <div>Время на оплату:</div>
                <div id="timer-display" class="timer-display">02:30</div>
            </div>

            <!-- Информация о платеже -->
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">Способ оплаты:</span>
                    <span id="selected-method" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">К получению:</span>
                    <span class="info-value"><?php echo formatPrice($remainingAmount); ?></span>
                </div>
                <div class="info-row" id="total-amount-row" style="display: none;">
                    <span class="info-label">К оплате:</span>
                    <span id="total-amount" class="info-value"></span>
                </div>
            </div>

            <!-- Уведомление о готовности ссылки -->
            <div id="payment-link-ready" class="payment-link-ready" style="display: none;">
                <h3>🎉 Ссылка для оплаты готова!</h3>
                <p style="color: white;">Используйте кнопки ниже для оплаты или отправки ссылки</p>
            </div>

            <!-- Кнопки управления -->
            <div class="payment-actions">
                <button id="open-payment" class="btn btn-success" style="display: none;">
                    💳 Оплатить сейчас
                </button>

                <button id="share-link" class="btn btn-info" style="display: none;">
                    📤 Поделиться ссылкой
                </button>

                <button id="cancel-payment" class="btn btn-secondary">
                    ❌ Отменить платеж
                </button>
            </div>
        </div>

        <!-- Экран успешной оплаты -->
        <div id="success-screen" class="success-screen">
            <div class="success-icon">✓</div>
            <h2>Платеж успешно завершен!</h2>
            <p>Средства будут зачислены по туру в течение 2 минут.</p>

            <!-- Предупреждение о несоответствии способа оплаты -->
            <div id="payment-mismatch-warning" class="warning-message" style="display: none;">
                <h4>Внимание!</h4>
                <p id="mismatch-message"></p>
            </div>

            <div style="margin: 20px 0;">
                <div id="payment-details"></div>
            </div>
            <a href="https://byfly-travel.com/vaucher.php?orderId=<?php echo $orderId; ?>" class="btn btn-primary">
                Получить ваучер
            </a>
        </div>

        <!-- Экран ошибки -->
        <div id="error-screen" class="error-screen">
            <div class="error-icon">✕</div>
            <h2 id="error-title">Ошибка платежа</h2>
            <p id="error-message">Произошла ошибка при обработке платежа</p>
            <button id="retry-payment" class="btn btn-primary" style="margin-top: 20px;">
                Попробовать снова
            </button>
        </div>

        <!-- Экран загрузки -->
        <div id="loading-screen" class="loading-screen">
            <div class="loading-content">
                <div class="spinner"></div>
                <h3 id="loading-text">Инициализация платежа...</h3>
                <p id="loading-subtext">Подготовка терминала к оплате</p>
            </div>
        </div>
    </div>

    <script>
        class PaymentProcessor {
            constructor() {
                this.orderId = <?php echo $orderId; ?>;
                this.remainingAmount = <?php echo $remainingAmount; ?>;
                this.selectedPaymentType = null;
                this.selectedPercentage = 0;
                this.terminal = null;
                this.transactionId = null;
                this.processId = null;
                this.paymentUrl = null;
                this.statusCheckInterval = null;
                this.timerInterval = null;
                this.remainingSeconds = 150; // 2 минуты 30 секунд
                this.waitingTime = 0;
                this.waitingInterval = null;

                this.init();
            }

            init() {
                this.bindEvents();
                this.checkTerminalAvailability();
            }

            bindEvents() {
                // Выбор способа оплаты
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.addEventListener('click', () => {
                        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
                        option.classList.add('selected');

                        this.selectedPaymentType = option.dataset.type;
                        this.selectedPercentage = parseFloat(option.dataset.percentage);

                        document.getElementById('pay-btn').disabled = false;
                    });
                });

                // Кнопка оплаты
                document.getElementById('pay-btn').addEventListener('click', () => {
                    this.initiatePayment();
                });

                // Кнопка отмены платежа
                document.getElementById('cancel-payment').addEventListener('click', () => {
                    this.cancelPayment();
                });

                // Кнопка повтора
                document.getElementById('retry-payment').addEventListener('click', () => {
                    this.resetToMainScreen();
                });

                // Кнопка обновления терминалов
                document.getElementById('refresh-terminals').addEventListener('click', () => {
                    this.checkTerminalAvailability();
                });

                // Кнопка открытия оплаты
                document.getElementById('open-payment').addEventListener('click', () => {
                    this.openPaymentLink();
                });

                // Кнопка поделиться ссылкой
                document.getElementById('share-link').addEventListener('click', () => {
                    this.sharePaymentLink();
                });
            }

            async checkTerminalAvailability() {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get_terminal'
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.terminal = data.terminal;
                        this.showMainScreen();
                    } else {
                        this.showWaitingTerminals();
                    }
                } catch (error) {
                    console.error('Ошибка проверки терминалов:', error);
                    this.showError('Ошибка', 'Не удалось проверить доступность терминалов');
                }
            }

            showMainScreen() {
                this.hideAllScreens();
                document.getElementById('main-screen').style.display = 'block';
                this.stopWaitingTimer();
            }

            showWaitingTerminals() {
                this.hideAllScreens();
                document.getElementById('waiting-terminals').style.display = 'block';
                this.startWaitingTimer();
            }

            startWaitingTimer() {
                this.waitingTime = 0;
                this.waitingInterval = setInterval(() => {
                    this.waitingTime += 5;
                    document.getElementById('waiting-time').textContent = this.formatWaitingTime(this.waitingTime);

                    // Проверяем терминалы каждые 5 секунд
                    this.checkTerminalAvailability();
                }, 5000);
            }

            stopWaitingTimer() {
                if (this.waitingInterval) {
                    clearInterval(this.waitingInterval);
                    this.waitingInterval = null;
                }
            }

            formatWaitingTime(seconds) {
                if (seconds < 60) {
                    return seconds + 'с';
                } else {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = seconds % 60;
                    return minutes + 'м ' + remainingSeconds + 'с';
                }
            }

            async initiatePayment() {
                if (!this.terminal || !this.selectedPaymentType) {
                    this.showError('Ошибка', 'Терминал не выбран или не выбран способ оплаты');
                    return;
                }

                this.showLoading('Инициализация платежа...', 'Подготовка терминала к оплате');

                try {
                    // Рассчитываем итоговую сумму
                    const totalAmount = this.calculateTotalAmount();

                    // Создаем транзакцию
                    const createResponse = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=create_payment&terminal_id=${this.terminal.id}&amount=${this.remainingAmount}&payment_type=${this.selectedPaymentType}&total_amount=${totalAmount}&percentage=${this.selectedPercentage}`
                    });

                    const createData = await createResponse.json();

                    if (!createData.success) {
                        throw new Error(createData.message || 'Ошибка создания транзакции');
                    }

                    this.transactionId = createData.transaction_id;

                    // Инициируем платеж на терминале
                    this.showLoading('Подключение к терминалу...', 'Инициализация платежа');

                    const initiateResponse = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=initiate_terminal_payment&terminal_id=${this.terminal.id}&transaction_id=${this.transactionId}&total_amount=${totalAmount}`
                    });

                    const initiateData = await initiateResponse.json();

                    if (!initiateData.success) {
                        throw new Error(initiateData.message || 'Ошибка инициализации платежа');
                    }

                    this.processId = initiateData.process_id;

                    // Получаем QR код
                    this.showLoading('Сканирование QR кода...', 'Ожидание QR кода с терминала (до 2 минут)');

                    await this.delay(3000); // Ждем 3 секунды перед сканированием QR

                    const qrResponse = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_qr_code&terminal_id=${this.terminal.id}&transaction_id=${this.transactionId}`
                    });

                    const qrData = await qrResponse.json();

                    if (!qrData.success) {
                        throw new Error(qrData.message || 'Ошибка получения QR кода');
                    }

                    this.paymentUrl = qrData.payment_url;

                    // Показываем экран оплаты
                    this.showPaymentScreen(totalAmount);

                } catch (error) {
                    console.error('Ошибка инициализации платежа:', error);
                    this.showError('Ошибка', error.message);
                }
            }

            calculateTotalAmount() {
                if (this.selectedPaymentType === 'cash') {
                    return this.remainingAmount;
                } else if (this.selectedPaymentType === 'kaspi_red') {
                    return this.remainingAmount + Math.ceil((this.remainingAmount * this.selectedPercentage) / 100);
                } else if (this.selectedPaymentType === 'credit') {
                    return Math.ceil((this.remainingAmount * 100) / (100 - this.selectedPercentage));
                }
                return this.remainingAmount;
            }

            getPaymentTypeTitle(type) {
                switch (type) {
                    case 'cash': return 'Kaspi Gold';
                    case 'kaspi_red': return 'Kaspi Red';
                    case 'credit': return 'Kaspi Кредит';
                    default: return type;
                }
            }

            showPaymentScreen(totalAmount) {
                this.hideAllScreens();
                document.getElementById('payment-screen').style.display = 'block';

                // Заполняем информацию
                document.getElementById('selected-method').textContent = this.getPaymentTypeTitle(this.selectedPaymentType);

                if (this.selectedPaymentType !== 'cash') {
                    document.getElementById('total-amount-row').style.display = 'flex';
                    document.getElementById('total-amount').textContent = this.formatPrice(totalAmount);
                }

                // Показываем уведомление о готовности ссылки
                document.getElementById('payment-link-ready').style.display = 'block';

                // Показываем кнопки
                document.getElementById('open-payment').style.display = 'block';
                document.getElementById('share-link').style.display = 'block';

                // Запускаем таймер и мониторинг статуса
                this.startTimer();
                this.startStatusMonitoring();
            }

            openPaymentLink() {
                if (this.paymentUrl) {
                    const ref = window.open(this.paymentUrl, '_blank', 'location=yes');
                    if (ref) {
                        setTimeout(() => ref.close(), 100);
                    }
                }
            }


            sharePaymentLink() {
                if (this.paymentUrl) {
                    const totalAmount = this.calculateTotalAmount();
                    const orderNumber = String(this.orderId).padStart(8, '0');

                    const message = `🏖️ Оплата тура ByFly Travel
📋 Заказ №${orderNumber}

💰 Сумма к оплате: ${this.formatPrice(totalAmount)}
💳 Способ оплаты: ${this.getPaymentTypeTitle(this.selectedPaymentType)}

⚠️ ВАЖНО! На оплату выделено 2 минуты 30 секунд с момента получения этого сообщения.

🔗 Ссылка для оплаты:
${this.paymentUrl}

После оплаты вы получите подтверждение и сможете получить ваучер на сайте.`;

                    if (navigator.share) {
                        navigator.share({
                            title: 'Оплата тура ByFly Travel',
                            text: message
                        }).catch(err => {
                            console.log('Ошибка при попытке поделиться:', err);
                            this.copyToClipboard(message);
                        });
                    } else {
                        this.copyToClipboard(message);
                    }
                }
            }

            copyToClipboard(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('Сообщение с ссылкой скопировано в буфер обмена');
                    }).catch(() => {
                        this.showTextToCopy(text);
                    });
                } else {
                    this.showTextToCopy(text);
                }
            }

            showTextToCopy(text) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();

                try {
                    document.execCommand('copy');
                    alert('Сообщение с ссылкой скопировано в буфер обмена');
                } catch (err) {
                    prompt('Скопируйте текст ниже:', text);
                }

                document.body.removeChild(textarea);
            }

            startTimer() {
                this.remainingSeconds = 150; // 2 минуты 30 секунд

                this.timerInterval = setInterval(() => {
                    this.remainingSeconds--;

                    const minutes = Math.floor(this.remainingSeconds / 60);
                    const seconds = this.remainingSeconds % 60;
                    const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    document.getElementById('timer-display').textContent = timeString;

                    // Меняем цвет таймера если осталось меньше 30 секунд
                    const timerElement = document.getElementById('timer');
                    if (this.remainingSeconds <= 30) {
                        timerElement.classList.add('warning');
                    }

                    if (this.remainingSeconds <= 0) {
                        this.timeoutPayment();
                    }
                }, 1000);
            }

            startStatusMonitoring() {
                this.statusCheckInterval = setInterval(async () => {
                    await this.checkPaymentStatus();
                }, 1000); // Проверяем каждую секунду
            }

            async checkPaymentStatus() {
                if (!this.processId || !this.terminal) return;

                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=check_payment_status&terminal_id=${this.terminal.id}&process_id=${this.processId}&transaction_id=${this.transactionId}&selected_payment_type=${this.selectedPaymentType}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (data.status === 'completed') {
                            this.completePayment(data.data || {}, data.payment_mismatch, data.mismatch_message);
                        } else if (data.status === 'failed') {
                            this.failPayment('Платеж отклонен');
                        } else if (data.status === 'cancelled_by_user') {
                            this.failPayment('Платеж отменен пользователем');
                        }
                        // Для других статусов (wait, processing) ничего не делаем - просто ждем
                    }
                } catch (error) {
                    console.error('Ошибка проверки статуса:', error);
                }
            }

            completePayment(paymentData, paymentMismatch = false, mismatchMessage = '') {
                this.stopTimers();
                this.hideAllScreens();

                document.getElementById('success-screen').style.display = 'block';

                // Показываем предупреждение о несоответствии способа оплаты
                if (paymentMismatch && mismatchMessage) {
                    const warningElement = document.getElementById('payment-mismatch-warning');
                    const messageElement = document.getElementById('mismatch-message');

                    messageElement.textContent = mismatchMessage;
                    warningElement.style.display = 'block';
                }

                // Заполняем детали платежа
                const paymentDetails = document.getElementById('payment-details');
                const totalAmount = this.calculateTotalAmount();

                paymentDetails.innerHTML = `
                    <div class="order-info">
                        <div class="info-row">
                            <span class="info-label">Способ оплаты:</span>
                            <span class="info-value">${this.getPaymentTypeTitle(this.selectedPaymentType)}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Получено:</span>
                            <span class="info-value">${this.formatPrice(this.remainingAmount)}</span>
                        </div>
                        ${this.selectedPaymentType !== 'cash' ? `
                        <div class="info-row">
                            <span class="info-label">Оплачено клиентом:</span>
                            <span class="info-value">${this.formatPrice(totalAmount)}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Комиссия:</span>
                            <span class="info-value">${this.formatPrice(totalAmount - this.remainingAmount)}</span>
                        </div>
                        ` : ''}
                        <div class="info-row">
                            <span class="info-label">№ транзакции:</span>
                            <span class="info-value">${paymentData.transactionId || this.processId}</span>
                        </div>
                    </div>
                `;
            }

            failPayment(reason) {
                this.stopTimers();
                this.showError('Платеж отклонен', reason);
            }

            timeoutPayment() {
                this.stopTimers();
                this.cancelPayment();
                this.showError('Время истекло', 'Время на оплату истекло (2 минуты 30 секунд). Попробуйте еще раз.');
            }

            async cancelPayment() {
                if (this.processId && this.terminal && this.transactionId) {
                    try {
                        await fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=cancel_payment&terminal_id=${this.terminal.id}&process_id=${this.processId}&transaction_id=${this.transactionId}`
                        });
                    } catch (error) {
                        console.error('Ошибка отмены платежа:', error);
                    }
                }

                this.stopTimers();
                this.resetToMainScreen();
            }

            stopTimers() {
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                    this.timerInterval = null;
                }

                if (this.statusCheckInterval) {
                    clearInterval(this.statusCheckInterval);
                    this.statusCheckInterval = null;
                }
            }

            resetToMainScreen() {
                this.stopTimers();
                this.stopWaitingTimer();

                // Сбрасываем состояние
                this.selectedPaymentType = null;
                this.selectedPercentage = 0;
                this.transactionId = null;
                this.processId = null;
                this.paymentUrl = null;
                this.remainingSeconds = 150;

                // Убираем выделение с опций оплаты
                document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
                document.getElementById('pay-btn').disabled = true;

                // Скрываем предупреждение о несоответствии
                document.getElementById('payment-mismatch-warning').style.display = 'none';

                // Проверяем доступность терминалов
                this.checkTerminalAvailability();
            }

            showLoading(title, subtitle) {
                this.hideAllScreens();
                document.getElementById('loading-screen').style.display = 'flex';
                document.getElementById('loading-text').textContent = title;
                document.getElementById('loading-subtext').textContent = subtitle;
            }

            showError(title, message) {
                this.hideAllScreens();
                document.getElementById('error-screen').style.display = 'block';
                document.getElementById('error-title').textContent = title;
                document.getElementById('error-message').textContent = message;
            }

            hideAllScreens() {
                document.getElementById('main-screen').style.display = 'none';
                document.getElementById('waiting-terminals').style.display = 'none';
                document.getElementById('payment-screen').style.display = 'none';
                document.getElementById('success-screen').style.display = 'none';
                document.getElementById('error-screen').style.display = 'none';
                document.getElementById('loading-screen').style.display = 'none';
            }

            formatPrice(amount) {
                return new Intl.NumberFormat('ru-RU').format(amount) + ' ₸';
            }

            delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }
        }

        // Инициализируем обработчик платежей
        document.addEventListener('DOMContentLoaded', () => {
            new PaymentProcessor();
        });

        // Обработка видимости страницы для остановки таймеров при переходе на другую вкладку
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                // Страница скрыта - можно приостановить некритичные операции
                console.log('Страница скрыта');
            } else {
                // Страница снова видна - возобновляем операции
                console.log('Страница снова видна');
            }
        });

        // Предотвращение случайного закрытия страницы во время оплаты
        window.addEventListener('beforeunload', function (e) {
            // Проверяем, идет ли процесс оплаты
            const paymentScreen = document.getElementById('payment-screen');
            if (paymentScreen && paymentScreen.style.display !== 'none') {
                e.preventDefault();
                e.returnValue = 'Вы уверены, что хотите покинуть страницу? Процесс оплаты будет прерван.';
                return e.returnValue;
            }
        });

        // Обработка ошибок JavaScript
        window.addEventListener('error', function (e) {
            console.error('JavaScript Error:', e.error);
            // Можно отправить ошибку на сервер для логирования
        });

        // Обработка необработанных промисов
        window.addEventListener('unhandledrejection', function (e) {
            console.error('Unhandled Promise Rejection:', e.reason);
            // Можно отправить ошибку на сервер для логирования
        });

        // Функция для отправки аналитики (если нужно)
        function trackEvent(eventName, eventData = {}) {
            try {
                // Здесь можно добавить отправку событий в аналитику
                console.log('Analytics Event:', eventName, eventData);

                // Пример отправки в Google Analytics (если подключен)
                if (typeof gtag !== 'undefined') {
                    gtag('event', eventName, eventData);
                }

                // Пример отправки в Яндекс.Метрику (если подключена)
                if (typeof ym !== 'undefined') {
                    ym(window.yaCounterId, 'reachGoal', eventName, eventData);
                }
            } catch (error) {
                console.error('Analytics tracking error:', error);
            }
        }

        // Отслеживание времени на странице
        let pageStartTime = Date.now();

        window.addEventListener('beforeunload', function () {
            const timeOnPage = Math.round((Date.now() - pageStartTime) / 1000);
            trackEvent('page_time', {
                time_on_page: timeOnPage,
                page_type: 'payment'
            });
        });

        // Отслеживание кликов по кнопкам оплаты
        document.addEventListener('click', function (e) {
            if (e.target.matches('.payment-option')) {
                const paymentType = e.target.dataset.type;
                trackEvent('payment_method_selected', {
                    payment_method: paymentType
                });
            }

            if (e.target.matches('#pay-btn')) {
                trackEvent('payment_initiated');
            }

            if (e.target.matches('#open-payment')) {
                trackEvent('payment_link_opened');
            }

            if (e.target.matches('#share-link')) {
                trackEvent('payment_link_shared');
            }

            if (e.target.matches('#cancel-payment')) {
                trackEvent('payment_cancelled');
            }
        });

        // Функция для проверки поддержки браузером необходимых функций
        function checkBrowserSupport() {
            const requiredFeatures = [
                'fetch',
                'Promise',
                'addEventListener',
                'JSON'
            ];

            const unsupportedFeatures = requiredFeatures.filter(feature => {
                return typeof window[feature] === 'undefined';
            });

            if (unsupportedFeatures.length > 0) {
                alert('Ваш браузер не поддерживает некоторые функции, необходимые для работы системы оплаты. Пожалуйста, обновите браузер или используйте другой.');
                console.error('Unsupported features:', unsupportedFeatures);
                return false;
            }

            return true;
        }

        // Проверяем поддержку браузера при загрузке
        document.addEventListener('DOMContentLoaded', function () {
            if (!checkBrowserSupport()) {
                // Показываем сообщение об ошибке совместимости
                document.body.innerHTML = `
                    <div style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    ">
                        <div style="
                            background: white;
                            border-radius: 20px;
                            padding: 40px;
                            text-align: center;
                            max-width: 400px;
                            margin: 20px;
                            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
                        ">
                            <div style="font-size: 60px; margin-bottom: 20px;">⚠️</div>
                            <h2 style="color: #dc3545; margin-bottom: 15px;">Браузер не поддерживается</h2>
                            <p style="color: #666; margin-bottom: 20px; line-height: 1.5;">
                                Для работы системы оплаты требуется современный браузер. 
                                Пожалуйста, обновите ваш браузер или используйте другой.
                            </p>
                            <a href="tel:+77273700773" style="
                                display: inline-block;
                                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                                color: white;
                                padding: 15px 30px;
                                border-radius: 12px;
                                text-decoration: none;
                                font-weight: 600;
                            ">📞 Связаться с поддержкой</a>
                        </div>
                    </div>
                `;
            }
        });

        // Функция для обработки сетевых ошибок
        function handleNetworkError(error) {
            console.error('Network error:', error);

            // Проверяем, есть ли соединение с интернетом
            if (!navigator.onLine) {
                alert('Отсутствует подключение к интернету. Проверьте соединение и попробуйте снова.');
                return;
            }

            // Показываем общее сообщение об ошибке сети
            alert('Произошла ошибка соединения с сервером. Попробуйте обновить страницу или повторить попытку позже.');
        }

        // Отслеживание состояния сети
        window.addEventListener('online', function () {
            console.log('Соединение с интернетом восстановлено');
            trackEvent('network_online');
        });

        window.addEventListener('offline', function () {
            console.log('Соединение с интернетом потеряно');
            trackEvent('network_offline');
            alert('Соединение с интернетом потеряно. Проверьте подключение.');
        });

        // Функция для безопасного выполнения fetch запросов
        async function safeFetch(url, options = {}) {
            try {
                // Добавляем таймаут для запроса
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 секунд

                const response = await fetch(url, {
                    ...options,
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response;
            } catch (error) {
                if (error.name === 'AbortError') {
                    throw new Error('Превышено время ожидания ответа от сервера');
                }
                throw error;
            }
        }

        // Функция для повторных попыток выполнения запроса
        async function retryFetch(url, options = {}, maxRetries = 3) {
            let lastError;

            for (let i = 0; i <= maxRetries; i++) {
                try {
                    return await safeFetch(url, options);
                } catch (error) {
                    lastError = error;

                    if (i < maxRetries) {
                        // Ждем перед повторной попыткой (экспоненциальная задержка)
                        const delay = Math.pow(2, i) * 1000;
                        await new Promise(resolve => setTimeout(resolve, delay));
                        console.log(`Повторная попытка ${i + 1}/${maxRetries} через ${delay}мс`);
                    }
                }
            }

            throw lastError;
        }

        // Добавляем стили для мобильных устройств
        const mobileStyles = `
            @media (max-width: 768px) {
                .container {
                    margin: 0;
                    border-radius: 0;
                }
                
                .header {
                    padding: 15px;
                }
                
                .content {
                    padding: 15px;
                }
                
                .payment-option {
                    padding: 12px;
                }
                
                .payment-icon {
                    width: 35px;
                    height: 35px;
                    font-size: 16px;
                }
                
                .btn {
                    padding: 14px;
                    font-size: 15px;
                }
                
                .timer-display {
                    font-size: 24px;
                }
                
                .success-icon,
                .error-icon {
                    font-size: 60px;
                }
            }
            
            @media (max-width: 480px) {
                .payment-amount h2 {
                    font-size: 22px;
                }
                
                .payment-details h4 {
                    font-size: 14px;
                }
                
                .payment-details p {
                    font-size: 11px;
                }
                
                .info-label,
                .info-value {
                    font-size: 13px;
                }
            }
        `;

        // Добавляем мобильные стили в документ
        const styleSheet = document.createElement('style');
        styleSheet.textContent = mobileStyles;
        document.head.appendChild(styleSheet);

        // Функция для определения типа устройства
        function getDeviceType() {
            const userAgent = navigator.userAgent.toLowerCase();
            const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/.test(userAgent);
            const isTablet = /ipad|android(?!.*mobile)/.test(userAgent);

            if (isMobile && !isTablet) {
                return 'mobile';
            } else if (isTablet) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }

        // Отправляем информацию об устройстве в аналитику
        document.addEventListener('DOMContentLoaded', function () {
            const deviceType = getDeviceType();
            trackEvent('page_view', {
                device_type: deviceType,
                user_agent: navigator.userAgent,
                screen_resolution: `${screen.width}x${screen.height}`,
                viewport_size: `${window.innerWidth}x${window.innerHeight}`
            });
        });

        // Обработка изменения ориентации экрана на мобильных устройствах
        window.addEventListener('orientationchange', function () {
            setTimeout(function () {
                // Пересчитываем размеры после изменения ориентации
                const newViewport = `${window.innerWidth}x${window.innerHeight}`;
                trackEvent('orientation_change', {
                    new_viewport: newViewport,
                    orientation: screen.orientation ? screen.orientation.angle : 'unknown'
                });
            }, 100);
        });
    </script>
</body>

</html>