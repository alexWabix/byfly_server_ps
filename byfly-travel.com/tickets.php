<?php
// Подключаемся к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Получаем ID билета из GET параметра
$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Запрос к базе данных для получения информации о билете
$ticketQuery = $db->query("
    SELECT 
        ebu.*, 
        eb.name_events, 
        eb.date_event, 
        eb.adress, 
        eb.programes,
        eb.citys,
        eb.contakctes
    FROM event_byfly_user_registered ebu
    JOIN event_byfly eb ON ebu.event_id = eb.id
    WHERE ebu.id = $ticketId
");

$ticketData = $ticketQuery ? $ticketQuery->fetch_assoc() : null;
$isValidTicket = (bool) $ticketData;
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Электронный билет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --border-color: #ddd;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Montserrat', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        .ticket {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            background: white;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .ticket-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .ticket-header::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: radial-gradient(circle at 10px 0, transparent 10px, white 11px);
            background-size: 20px 20px;
        }

        .ticket-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .ticket-subtitle {
            font-weight: 400;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .ticket-body {
            padding: 25px;
        }

        .ticket-section {
            margin-bottom: 25px;
        }

        .ticket-section-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .ticket-section-title i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .ticket-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--border-color);
        }

        .ticket-info:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .ticket-info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
        }

        .ticket-info-value {
            font-weight: 600;
            text-align: right;
            font-size: 1rem;
        }

        .ticket-qr {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 25px 0;
            border: 1px solid var(--border-color);
        }

        .ticket-qr-code {
            width: 220px;
            height: 220px;
            margin: 0 auto;
            background: white;
            padding: 10px;
            border: 1px solid var(--border-color);
        }

        .ticket-qr-text {
            margin-top: 50px;
            font-size: 0.9rem;
            color: #666;
        }

        .ticket-footer {
            padding: 20px;
            background: var(--light-bg);
            text-align: center;
            font-size: 0.8rem;
            color: #666;
            border-top: 1px dashed var(--border-color);
        }

        .ticket-number {
            position: relative;
            top: -10px;
            right: auto;
            background: rgba(255, 255, 255, .2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
        }

        .ticket-perforation {
            position: relative;
            height: 20px;
            margin: 0 20px;
            overflow: hidden;
        }

        .ticket-perforation::before {
            content: "";
            position: absolute;
            top: 0;
            left: -10px;
            right: -10px;
            height: 20px;
            background: radial-gradient(circle at 10px 10px, transparent 8px, var(--border-color) 9px);
            background-size: 20px 20px;
        }

        .invalid-ticket {
            text-align: center;
            padding: 50px 20px;
        }

        .invalid-icon {
            font-size: 5rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            z-index: 100;
            border: none;
        }

        @media print {
            body {
                background: white;
            }

            .ticket {
                box-shadow: none;
                border: none;
            }

            .btn-print {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .ticket-body {
                padding: 15px;
            }

            .ticket-qr-code {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>

<body>
    <?php if ($isValidTicket): ?>
        <div class="container py-4">
            <div class="ticket">
                <div class="ticket-header">
                    <div class="ticket-number">Билет №<?= $ticketId ?></div>
                    <h1 class="ticket-title"><i class="fas fa-ticket-alt"></i> Электронный билет</h1>
                    <div class="ticket-subtitle">ByFly Travel Events</div>
                </div>

                <div class="ticket-body">
                    <div class="ticket-section">
                        <div class="ticket-section-title">
                            <i class="fas fa-user"></i> Участник
                        </div>
                        <div class="ticket-info">
                            <div class="ticket-info-label">Имя</div>
                            <div class="ticket-info-value"><?= htmlspecialchars($ticketData['name_user']) ?></div>
                        </div>
                        <?php if (!empty($ticketData['user_phone'])): ?>
                            <div class="ticket-info">
                                <div class="ticket-info-label">Телефон</div>
                                <div class="ticket-info-value"><?= htmlspecialchars($ticketData['user_phone']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ticket-section">
                        <div class="ticket-section-title">
                            <i class="fas fa-calendar-alt"></i> Мероприятие
                        </div>
                        <div class="ticket-info">
                            <div class="ticket-info-label">Название</div>
                            <div class="ticket-info-value"><?= htmlspecialchars($ticketData['name_events']) ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="ticket-info-label">Дата</div>
                            <div class="ticket-info-value"><?= date('d.m.Y', strtotime($ticketData['date_event'])) ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="ticket-info-label">Время</div>
                            <div class="ticket-info-value"><?= date('H:i', strtotime($ticketData['date_event'])) ?></div>
                        </div>
                    </div>

                    <div class="ticket-section">
                        <div class="ticket-section-title">
                            <i class="fas fa-map-marker-alt"></i> Место проведения
                        </div>
                        <div class="ticket-info">
                            <div class="ticket-info-label">Город</div>
                            <div class="ticket-info-value"><?= htmlspecialchars($ticketData['citys']) ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="ticket-info-label">Адрес</div>
                            <div class="ticket-info-value"><?= htmlspecialchars($ticketData['adress']) ?></div>
                        </div>
                    </div>

                    <div class="ticket-perforation"></div>

                    <div class="ticket-qr">
                        <div class="ticket-qr-code">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= $ticketId ?>"
                                alt="QR Code" width="200" height="200">
                        </div>
                        <div class="ticket-qr-text">Отсканируйте QR-код при входе</div>
                    </div>

                    <?php if (!empty($ticketData['programes'])): ?>
                        <div class="ticket-section">
                            <div class="ticket-section-title">
                                <i class="fas fa-list-alt"></i> Программа
                            </div>
                            <div class="ticket-program">
                                <?= nl2br(htmlspecialchars($ticketData['programes'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($ticketData['contakctes'])): ?>
                        <div class="ticket-section">
                            <div class="ticket-section-title">
                                <i class="fas fa-phone-alt"></i> Контакты
                            </div>
                            <div class="ticket-contacts">
                                <?= nl2br(htmlspecialchars($ticketData['contakctes'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ticket-footer">
                    <p>Предъявите этот билет на входе. Билет действителен только для указанного лица.</p>
                    <p class="mb-0"><i class="far fa-copyright"></i> ByFly Travel <?= date('Y') ?></p>
                </div>
            </div>
        </div>

        <button class="btn-print" title="Распечатать билет" onclick="window.print()">
            <i class="fas fa-print"></i>
        </button>
    <?php else: ?>
        <div class="container py-4">
            <div class="ticket">
                <div class="ticket-header"
                    style="background: linear-gradient(135deg, var(--accent-color) 0%, #f8a5c2 100%);">
                    <h1 class="ticket-title"><i class="fas fa-exclamation-triangle"></i> Недействительный билет</h1>
                </div>

                <div class="ticket-body">
                    <div class="invalid-ticket">
                        <div class="invalid-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h3>Билет не найден</h3>
                        <p class="mt-3">Пожалуйста, проверьте правильность ссылки или обратитесь к организаторам
                            мероприятия.</p>
                        <a href="/" class="btn btn-primary mt-4"><i class="fas fa-home"></i> На главную</a>
                    </div>
                </div>

                <div class="ticket-footer">
                    <p class="mb-0"><i class="far fa-copyright"></i> ByFly Travel <?= date('Y') ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Анимация при загрузке билета
            $('.ticket').hide().fadeIn(500);

            // Копирование ID билета при клике на номер
            $('.ticket-number').click(function () {
                const ticketId = $(this).text().replace('Билет №', '');
                navigator.clipboard.writeText(ticketId).then(function () {
                    const originalText = $('.ticket-number').html();
                    $('.ticket-number').html('<i class="fas fa-check"></i> Скопировано');
                    setTimeout(function () {
                        $('.ticket-number').html(originalText);
                    }, 2000);
                });
            });
        });
    </script>
</body>

</html>