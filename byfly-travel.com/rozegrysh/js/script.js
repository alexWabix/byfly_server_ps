$(document).ready(function () {
    function getNextSundayAt20() {
        const now = new Date();
        const result = new Date(now);

        // Получаем день недели (0 - воскресенье, 6 - суббота)
        const dayOfWeek = now.getDay();
        const daysUntilSunday = (dayOfWeek === 0) ? 0 : 7 - dayOfWeek;

        result.setDate(now.getDate() + daysUntilSunday);
        result.setHours(20, 0, 0, 0); // Устанавливаем время на 20:00

        // Если уже позже 20:00 в это воскресенье, идём на следующее
        if (result.getTime() <= now.getTime()) {
            result.setDate(result.getDate() + 7);
        }

        return result.getTime();
    }

    const nextDrawDate = getNextSundayAt20();

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = nextDrawDate - now;

        if (distance < 0) {
            clearInterval(countdownTimer);
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);


        document.getElementById('countdown-days').textContent = days.toString().padStart(2, '0');
        document.getElementById('countdown-hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('countdown-minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('countdown-seconds').textContent = seconds.toString().padStart(2, '0');
    }

    updateCountdown();
    const countdownTimer = setInterval(updateCountdown, 1000);
    loadParticipants();

    // Обработка формы проверки промокода
    $('#promo-check-form').on('submit', function (e) {
        e.preventDefault();

        const promoCode = $('#promo-code').val().trim();

        if (promoCode) {
            $('#check-result').html('<div class="text-center"><div class="spinner-border text-primary"></div></div>');

            $.post('check_promo.php', { promo_code: promoCode }, function (response) {
                let html = '';

                if (response.status === 'success') {
                    const user = response.user;
                    const req = response.requirements;

                    html += `<div class="card">
                                <div class="card-header ${response.all_met ? 'bg-success' : 'bg-warning'} text-white">
                                    <h4>${user.name} ${user.famale}</h4>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">Статус участия</h5>
                                        <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item ${req.is_agent ? 'list-group-item-success' : 'list-group-item-danger'}">
                                        <i class="fas ${req.is_agent ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                                        Является агентом компании
                                        </li>
                                        <li class="list-group-item ${req.has_agent ? 'list-group-item-success' : 'list-group-item-danger'}">
                                        <i class="fas ${req.has_agent ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                                        Имеет агента в первой линии
                                        </li>
                                        <li class="list-group-item ${req.has_copilka ? 'list-group-item-success' : 'list-group-item-danger'}">
                                        <i class="fas ${req.has_copilka ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                                        Имеет активную копилку (мин. 2 месяца)
                                        </li>
                                        <li class="list-group-item ${req.has_tours ? 'list-group-item-success' : 'list-group-item-danger'}">
                                        <i class="fas ${req.has_tours ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                                        Продано туров: ${user.tours_sold}/2
                                        </li>
                                        </ul>`;

                    if (response.all_met) {
                        html += `<div class="alert alert-success">
                                <i class="fas fa-trophy me-2"></i> 
                                Поздравляем! Вы соответствуете всем требованиям и участвуете в розыгрыше!
                            </div>`;
                    } else {
                        html += `<div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i> 
                                Вы пока не соответствуете всем требованиям для участия в розыгрыше.
                            </div>
                            <div class="alert alert-info">
                                <h6>Что нужно сделать:</h6>
                                <ul>`;

                        if (!req.is_agent) {
                            html += `<li>Стать агентом компании (пройти обучение)</li>`;
                        }
                        if (!req.has_agent) {
                            html += `<li>Пригласить минимум одного агента в свою первую линию</li>`;
                        }
                        if (!req.has_copilka) {
                            html += `<li>Открыть накопительную ячейку и внести минимум 2 платежа, либо кто то из пользователей в первой линии откроет накопительную и внесет 2 платежа!</li>`;
                        }
                        if (!req.has_tours) {
                            html += `<li>Продать ${2 - user.tours_sold} тура(ов)</li>`;
                        }

                        html += `</ul></div>`;
                    }

                    html += `</div></div>`;
                } else {
                    html = `<div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            ${response.message}
                        </div>`;
                }

                $('#check-result').html(html);
            }, 'json').fail(function () {
                $('#check-result').html('<div class="alert alert-danger">Ошибка сервера. Попробуйте позже.</div>');
            });
        }
    });

});

// Функция загрузки списка участников
function loadParticipants() {
    $.get('includes/functions.php?action=get_participants', function (data) {
        alert(data);
        let html = '';

        if (data.length > 0) {
            data.forEach((participant, index) => {
                const avatar = participant.avatar || 'https://via.placeholder.com/40';
                const name = `${participant.name} ${participant.famale}`;
                const phone = participant.phone;
                const city = phone.startsWith('7') ? 'Алматы' : 'Нур-Султан'; // Пример определения города по номеру

                html += `<tr>
                        <td>${index + 1}</td>
                        <td>
                            <img src="${avatar}" alt="${name}" class="rounded-circle me-2" width="30">
                            ${name}
                        </td>
                        <td>${city}</td>
                        <td>
                            <span class="badge bg-success">Активен</span>
                        </td>
                    </tr>`;
            });
        } else {
            html = `<tr>
                    <td colspan="4" class="text-center">Нет участников, соответствующих требованиям</td>
                </tr>`;
        }

        $('#participants-list').html(html);
    }, 'json').fail(function () {
        $('#participants-list').html('<tr><td colspan="4" class="text-center">Ошибка загрузки данных</td></tr>');
    });
}