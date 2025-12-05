<?php
function getFlyInfo($flyInfo)
{
    if (empty($flyInfo['flights'])) {
        return '<p>Информация о перелёте отсутствует.</p>';
    }

    $tabs = '';
    $tabContent = '';

    // Информация о рейсе "туда"
    if (!empty($flyInfo['flights'][0]['forward'])) {
        $tabs .= '<li class="nav-item" role="presentation">
                    <button class="nav-link active" id="forward-tab" data-bs-toggle="tab" data-bs-target="#forward-content" type="button" role="tab">Рейс туда</button>
                  </li>';
        $tabContent .= '<div class="tab-pane fade show active" id="forward-content" role="tabpanel">
                            ' . renderFlightInfo($flyInfo['flights'][0]['forward']) . '
                        </div>';
    }

    // Информация о рейсе "обратно"
    if (!empty($flyInfo['flights'][0]['backward'])) {
        $tabs .= '<li class="nav-item" role="presentation">
                    <button class="nav-link ' . (empty($flyInfo['flights'][0]['forward']) ? 'active' : '') . '" id="backward-tab" data-bs-toggle="tab" data-bs-target="#backward-content" type="button" role="tab">Рейс обратно</button>
                  </li>';
        $tabContent .= '<div class="tab-pane fade ' . (empty($flyInfo['flights'][0]['forward']) ? 'show active' : '') . '" id="backward-content" role="tabpanel">
                            ' . renderFlightInfo($flyInfo['flights'][0]['backward']) . '
                        </div>';
    }

    // Объединение результатов
    return '<div>
                <ul class="nav nav-tabs" role="tablist">
                    ' . $tabs . '
                </ul>
                <div class="tab-content mt-3">
                    ' . $tabContent . '
                </div>
            </div>';
}

function renderFlightInfo($flightData)
{
    $html = '';
    foreach ($flightData as $flight) {
        $html .= '<div class="card mb-3">
                    <div class="card-body">
                        <h6><b>Номер рейса:</b> ' . htmlspecialchars($flight['number'] ?? 'Не указано') . '</h6>
                        <p><b>Авиакомпания:</b> ' . htmlspecialchars($flight['company']['name'] ?? 'Не указано') . '</p>
                        <p><b>Самолёт:</b> ' . htmlspecialchars($flight['plane'] ?? 'Не указано') . '</p>
                        <p><b>Класс:</b> ' . htmlspecialchars($flight['class'] ?? 'Не указано') . '</p>
                        <p><b>Вылет:</b> ' . htmlspecialchars($flight['departure']['date'] ?? 'Не указано') . ' ' . htmlspecialchars($flight['departure']['time'] ?? 'Не указано') . ' из ' . htmlspecialchars($flight['departure']['port']['name'] ?? 'Не указано') . '</p>
                        <p><b>Прилет:</b> ' . htmlspecialchars($flight['arrival']['date'] ?? 'Не указано') . ' ' . htmlspecialchars($flight['arrival']['time'] ?? 'Не указано') . ' в ' . htmlspecialchars($flight['arrival']['port']['name'] ?? 'Не указано') . '</p>
                        <p><b>Багаж:</b> ' . htmlspecialchars($flight['baggage'] ?? 'Нет') . ' кг</p>
                        <p><b>Ручная кладь:</b> ' . htmlspecialchars($flight['carryOn'] ?? 'Нет') . ' кг</p>
                    </div>
                </div>';
    }
    return $html;
}
?>