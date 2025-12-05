<?php
function orderHotelInfo($byflyInfo, $tourvisorInfo)
{
    // Проверяем наличие данных
    $tabs = '';
    $tabContent = '';

    if (!empty($byflyInfo)) {
        $tabs .= '<li class="nav-item" role="presentation">
                    <button class="nav-link active" id="byfly-tab" data-bs-toggle="tab" data-bs-target="#byfly-content" type="button" role="tab">ByFly</button>
                  </li>';
        $tabContent .= '<div class="tab-pane fade show active" id="byfly-content" role="tabpanel">
                            ' . renderHotelInfo($byflyInfo) . '
                        </div>';
    }

    if (!empty($tourvisorInfo)) {
        $tabs .= '<li class="nav-item" role="presentation">
                    <button class="nav-link ' . (empty($byflyInfo) ? 'active' : '') . '" id="tourvisor-tab" data-bs-toggle="tab" data-bs-target="#tourvisor-content" type="button" role="tab">Стандартная</button>
                  </li>';
        $tabContent .= '<div class="tab-pane fade ' . (empty($byflyInfo) ? 'show active' : '') . '" id="tourvisor-content" role="tabpanel">
                            ' . renderHotelInfo($tourvisorInfo) . '
                        </div>';
    }

    // Если данные отсутствуют
    if (empty($tabs)) {
        return '<p>Информация об отеле отсутствует.</p>';
    }

    // Объединяем результат
    return '<div>
                <ul class="nav nav-tabs" role="tablist">
                    ' . $tabs . '
                </ul>
                <div class="tab-content mt-3">
                    ' . $tabContent . '
                </div>
            </div>';
}

function renderHotelInfo($hotelInfo)
{

    $html = '<h6>' . htmlspecialchars($hotelInfo['name'] ?? 'Название отсутствует') . '</h6>';
    // Отображение изображений
    if (!empty($hotelInfo['images']['image'])) {
        $html .= '<div class="d-flex flex-wrap gap-2 mt-3">';
        foreach ($hotelInfo['images']['image'] as $image) {
            $html .= '<a href="' . htmlspecialchars($image) . '" target="_blank">
                        <img src="' . htmlspecialchars($image) . '" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Изображение">
                      </a>';
        }
        $html .= '</div>';
    }
    $html .= '<p><b>Рейтинг:</b> ' . htmlspecialchars($hotelInfo['rating'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Звёзды:</b> ' . htmlspecialchars($hotelInfo['stars'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Регион:</b> ' . htmlspecialchars($hotelInfo['region'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Страна:</b> ' . htmlspecialchars($hotelInfo['country'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Расположение:</b> ' . htmlspecialchars($hotelInfo['placement'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Телефон:</b> ' . htmlspecialchars($hotelInfo['phone'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Сайт:</b> <a href="' . htmlspecialchars($hotelInfo['site'] ?? '#') . '" target="_blank">' . htmlspecialchars($hotelInfo['site'] ?? 'Не указано') . '</a></p>';
    $html .= '<p><b>Территория:</b> ' . ($hotelInfo['territory'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>В номере:</b> ' . ($hotelInfo['inroom'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Типы номеров:</b> ' . ($hotelInfo['roomtypes'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Услуги:</b> ' . ($hotelInfo['services'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Пляж:</b> ' . ($hotelInfo['beach'] ?? 'Не указано') . '</p>';
    $html .= '<p><b>Питание:</b> ' . ($hotelInfo['meallist'] ?? 'Не указано') . '</p>';



    return $html;
}
?>