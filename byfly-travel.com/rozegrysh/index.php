<?php
include('includes/config.php');
include('includes/functions.php');

// Получаем данные
$participants = getParticipants();
$candidates = getCandidates();
$next_draws = getNextDraws(3); // Ближайшие 3 розыгрыша

include('includes/header.php');
?>

<style>
    :root {
        --primary-color: #e63946;
        --secondary-color: #457b9d;
        --accent-color: #a8dadc;
        --dark-color: #1d3557;
        --light-color: #f1faee;
        --success-color: #2a9d8f;
        --warning-color: #f4a261;
        --danger-color: #e76f51;
    }

    /* Основные стили */
    body {
        font-family: 'Montserrat', sans-serif;
        background-color: #f8f9fa;
        color: #333;
        line-height: 1.6;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--danger-color) 100%);
    }

    .bg-gradient-secondary {
        background: linear-gradient(135deg, var(--secondary-color) 0%, var(--dark-color) 100%);
    }

    /* Заголовки */
    .display-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        letter-spacing: -0.5px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--danger-color) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Карточки */
    .card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        margin-bottom: 25px;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        border-bottom: none;
        padding: 1.25rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Аватары */
    .avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
        text-transform: uppercase;
        margin-right: 12px;
    }

    .avatar-initial {
        font-size: 18px;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Призы */
    .prize-card {
        transition: all 0.3s ease;
        height: 100%;
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        background: white;
        padding: 20px;
        text-align: center;
    }

    .prize-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
    }

    .prize-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        color: var(--primary-color);
    }

    /* Таймер */
    .countdown-container {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 20px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .countdown-item {
        display: inline-block;
        margin: 0 10px;
        text-align: center;
    }

    .countdown-value {
        font-size: 2.2rem;
        font-weight: 800;
        color: white;
        line-height: 1;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .countdown-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: white;
        opacity: 0.9;
    }

    /* Таблица */
    .participant-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .participant-table thead th {
        background-color: #f8f9fa;
        border: none;
        padding: 12px 15px;
        font-weight: 600;
    }

    .participant-table tbody tr {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .participant-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .participant-table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-top: none;
        border-bottom: none;
    }

    .participant-table tbody td:first-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }

    .participant-table tbody td:last-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    /* Прогресс бар */
    .progress-thin {
        height: 8px;
        border-radius: 4px;
        background-color: #f0f0f0;
    }

    .progress-bar {
        border-radius: 4px;
    }

    /* Кнопки */
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .btn-primary:hover {
        background-color: #c1121f;
        border-color: #c1121f;
    }

    /* Бейджи */
    .badge-pill {
        padding: 6px 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Форма */
    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(230, 57, 70, 0.25);
    }

    /* Адаптивность */
    @media (max-width: 768px) {
        .countdown-item {
            margin: 0 5px;
        }

        .countdown-value {
            font-size: 1.8rem;
        }

        .countdown-label {
            font-size: 0.7rem;
        }

        .card-body {
            padding: 1rem;
        }
    }

    /* Анимации */
    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    /* Дополнительные стили */
    .divider {
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: 3px;
        margin: 20px 0;
        opacity: 0.7;
    }

    .list-item-icon {
        width: 24px;
        text-align: center;
        margin-right: 12px;
        color: var(--primary-color);
    }

    .empty-state {
        text-align: center;
        padding: 30px 0;
    }

    .empty-state i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
    }
</style>

<div class="container py-4">
    <!-- Заголовок -->
    <div class="text-center mb-5">
        <h1 class="display-title display-4 mb-3">Розыгрыш</h1>
        <p class="lead text-muted">Для агентов ByFly Travel с лучшими показателями</p>
        <div class="divider mx-auto" style="width: 100px;"></div>
    </div>

    <!-- Таймер до следующего розыгрыша -->
    <?php if (!empty($next_draws)): ?>
        <div class="bg-gradient-primary rounded-3 p-4 mb-5 shadow">
            <h4 class="text-center text-white mb-4">До следующего розыгрыша:</h4>
            <div class="countdown-container text-center">
                <div class="d-inline-block">
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-days">00</div>
                        <div class="countdown-label">дней</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-hours">00</div>
                        <div class="countdown-label">часов</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-minutes">00</div>
                        <div class="countdown-label">минут</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-seconds">00</div>
                        <div class="countdown-label">секунд</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Левая колонка -->
        <div class="col-lg-6">
            <!-- Условия участия -->
            <div class="card">
                <div class="card-header bg-gradient-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-list-check me-2"></i>Условия участия</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="d-flex align-items-center mb-3">
                            <div class="list-item-icon"><i class="fas fa-user-tie"></i></div>
                            <div>Быть агентом компании</div>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <div class="list-item-icon"><i class="fas fa-users"></i></div>
                            <div>Иметь агента в первой линии</div>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <div class="list-item-icon"><i class="fas fa-piggy-bank"></i></div>
                            <div>Иметь активную копилку (мин. 2 платежа)</div>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <div class="list-item-icon"><i class="fas fa-plane"></i></div>
                            <div>Продать минимум 2 тура</div>
                        </li>
                    </ul>
                    <div class="text-center mt-4">
                        <a href="#check-promo" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Проверить свой статус
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ближайшие розыгрыши -->
            <?php
            // Расчёт 5 ближайших воскресений в 20:00
            $next_draws = [];
            $now = new DateTime();
            $now->setTime(0, 0); // обнуляем время
            $weekday = (int) $now->format('w'); // 0 - воскресенье, 6 - суббота
            $days_until_sunday = ($weekday === 0) ? 0 : 7 - $weekday;

            for ($i = 0; $i < 5; $i++) {
                $draw_date = (clone $now)->modify("+" . ($days_until_sunday + ($i * 7)) . " days");
                $draw_datetime = (clone $draw_date)->setTime(20, 0);

                $interval = $now->diff($draw_date);
                $days_left = (int) $interval->format('%a');

                // Форматируем остаток дней с правильным склонением
                if ($days_left === 0) {
                    $days_text = 'сегодня';
                } else {
                    $days_text = "через $days_left " . getDaysSuffix($days_left);
                }

                $next_draws[] = [
                    'draw_date' => $draw_datetime->format('Y-m-d H:i:s'),
                    'time' => $days_text
                ];
            }

            // Функция для склонения слова "день"
            function getDaysSuffix($number)
            {
                $n = $number % 100;
                if ($n >= 11 && $n <= 14) {
                    return 'дней';
                }
                $n = $number % 10;
                if ($n == 1) {
                    return 'день';
                }
                if ($n >= 2 && $n <= 4) {
                    return 'дня';
                }
                return 'дней';
            }
            ?>

            <div class="card">
                <div class="card-header bg-gradient-secondary text-white">
                    <h3 class="mb-0"><i class="fas fa-calendar-days me-2"></i>Ближайшие розыгрыши</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($next_draws)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($next_draws as $draw): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-gift text-primary me-3"></i>
                                        <div>
                                            <strong><?= date('d.m.Y', strtotime($draw['draw_date'])) ?></strong>
                                            <div class="text-muted small">20:00</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?= $draw['time'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p class="text-muted">Нет запланированных розыгрышей</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Правая колонка -->
        <div class="col-lg-6">
            <!-- Призы -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-trophy me-2"></i>Призы</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="prize-card">
                                <div class="prize-icon">
                                    <i class="fas fa-suitcase"></i>
                                </div>
                                <h5 class="mb-2">Чемоданы</h5>
                                <p class="text-muted small">Качественные дорожные чемоданы</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="prize-card">
                                <div class="prize-icon">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <h5 class="mb-2">Путешествия</h5>
                                <p class="text-muted small">Туры в разные страны</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="prize-card">
                                <div class="prize-icon">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <h5 class="mb-2">Гаджеты</h5>
                                <p class="text-muted small">Полезные устройства</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-muted mt-2"><small>* Чем больше участников, тем ценнее призы!</small></p>
                </div>
            </div>

            <!-- Участники/кандидаты -->
            <div class="card">
                <div class="card-header <?= !empty($participants) ? 'bg-warning' : 'bg-info' ?> text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        <?= !empty($participants) ? 'Текущие участники' : 'Ближайшие кандидаты' ?>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="participant-table">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Участник</th>
                                    <th>Прогресс</th>
                                    <th width="120">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($participants)): ?>
                                    <?php foreach ($participants as $index => $participant): ?>
                                        <?php
                                        $name = htmlspecialchars($participant['name'] . ' ' . $participant['famale']);
                                        $initial = mb_substr($participant['name'], 0, 1, 'UTF-8');
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $index + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($participant['avatar'])): ?>
                                                        <img src="<?= $participant['avatar'] ?>" alt="<?= $name ?>" class="avatar">
                                                    <?php else: ?>
                                                        <div class="avatar">
                                                            <span class="avatar-initial"><?= $initial ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?= $name ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success rounded-pill">Участник</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($candidates as $index => $candidate): ?>
                                        <?php
                                        $name = htmlspecialchars($candidate['name'] . ' ' . $candidate['famale']);
                                        $initial = mb_substr($candidate['name'], 0, 1, 'UTF-8');
                                        $missing = [];

                                        if ($candidate['user_status'] != 'agent')
                                            $missing[] = 'агент';
                                        if ($candidate['agents_count'] < 1)
                                            $missing[] = 'агент в линии';
                                        if ($candidate['copilka_count'] < 1)
                                            $missing[] = 'копилка';
                                        if ($candidate['tours_sold'] < 2)
                                            $missing[] = (2 - $candidate['tours_sold']) . ' продажи';

                                        $missingText = implode(', ', $missing);
                                        $progress = 4 - count($missing);
                                        $progressPercent = $progress * 25;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $index + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($candidate['avatar'])): ?>
                                                        <img src="<?= $candidate['avatar'] ?>" alt="<?= $name ?>" class="avatar">
                                                    <?php else: ?>
                                                        <div class="avatar">
                                                            <span class="avatar-initial"><?= $initial ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?= $name ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted d-block mb-1">Не хватает: <?= $missingText ?></small>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-<?= $progress >= 3 ? 'warning' : 'info' ?>"
                                                        style="width: <?= $progressPercent ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-<?= $progress >= 3 ? 'warning' : 'secondary' ?> rounded-pill">
                                                    <?= $progress ?>/4
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($candidates)): ?>
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state py-4">
                                                    <i class="fas fa-user-slash"></i>
                                                    <p class="text-muted">Нет кандидатов, близких к участию</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Форма проверки промокода -->
    <div class="row mt-5" id="check-promo">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0"><i class="fas fa-search me-2"></i>Проверить статус участия</h3>
                </div>
                <div class="card-body">
                    <form id="promo-check-form">
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="promo-code" required
                                placeholder="Например: BYFLY2023">
                            <label for="promo-code">Введите ваш промокод</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search me-2"></i>Проверить
                        </button>
                    </form>
                    <div id="check-result" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#promo-check-form').submit(function (e) {
            e.preventDefault();
            const promoCode = $('#promo-code').val().trim();

            if (!promoCode) {
                $('#check-result').html('<div class="alert alert-danger">Пожалуйста, введите промокод</div>');
                return;
            }

            $('#check-result').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>');

            $.ajax({
                url: 'check_promo.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    promo_code: promoCode
                },
                success: function (response) {
                    if (response.success) {
                        let html = `
                            <div class="alert alert-success">
                                <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Поздравляем!</h4>
                                <p>Вы участвуете в розыгрыше!</p>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Ваш прогресс:</strong></p>
                                        <div class="progress mb-3" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Следующий розыгрыш:</strong></p>
                                        <p class="fw-bold">${response.next_draw_date}</p>
                                    </div>
                                </div>
                                <div class="d-grid gap-2 mt-3">
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#prizeDetails">
                                        <i class="fas fa-gift me-2"></i>Посмотреть возможные призы
                                    </button>
                                </div>
                                <div class="collapse mt-3" id="prizeDetails">
                                    <div class="card card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-suitcase text-danger me-2"></i>Чемоданы</span>
                                                <span class="badge bg-danger rounded-pill">3 шт</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-plane text-primary me-2"></i>Путешествия</span>
                                                <span class="badge bg-primary rounded-pill">2 шт</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-medal text-warning me-2"></i>Гаджеты</span>
                                                <span class="badge bg-warning rounded-pill">5 шт</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Если есть рекомендации для улучшения
                        if (response.recommendations && response.recommendations.length > 0) {
                            html += `
                                <div class="alert alert-info mt-3">
                                    <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i>Как увеличить шансы?</h5>
                                    <ul class="mb-0">
                                        ${response.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                                    </ul>
                                </div>
                            `;
                        }

                        $('#check-result').html(html);
                    } else {
                        let html = `
                            <div class="alert alert-danger">
                                <h4 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>${response.message || 'Вы пока не участвуете в розыгрыше'}</h4>
                        `;

                        if (response.missing && response.missing.length > 0) {
                            html += `
                                <hr>
                                <p class="mb-2"><strong>Для участия необходимо:</strong></p>
                                <ul class="mb-3">
                                    ${response.missing.map(item => `<li>${item}</li>`).join('')}
                                </ul>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar" style="width: ${response.progress}%"></div>
                                </div>
                                <p class="mb-0">Выполнено: ${response.completed_count} из 4 условий</p>
                            `;
                        }

                        html += `</div>`;

                        // Добавляем кнопку с подробностями, если есть
                        if (response.details_link) {
                            html += `
                                <div class="d-grid mt-2">
                                    <a href="${response.details_link}" class="btn btn-outline-danger">
                                        <i class="fas fa-info-circle me-2"></i>Подробнее о требованиях
                                    </a>
                                </div>
                            `;
                        }

                        $('#check-result').html(html);
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'Произошла ошибка при проверке промокода';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $('#check-result').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>${errorMessage}
                        </div>
                    `);
                }
            });
        });
    });
</script>

<?php include('includes/footer.php'); ?>