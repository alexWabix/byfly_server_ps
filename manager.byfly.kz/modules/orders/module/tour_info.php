<?php
function tourInfo($tourInfo, $dopInfo, $data)
{
    if (empty($tourInfo)) {
        return '<p>Информация о туре отсутствует.</p>';
    }

    $html = '<div class="card mb-3">
    
                <div class="card-body">
                    <p><a target="_blank" href="?page=allOperators&id=' . $data['tourId'] . '" class="btn btn-success">Все предложения</a></p>
                    <p><b>Ссылка на оператора:</b> <a href="' . htmlspecialchars($tourInfo['operatorlink'] ?? '#') . '" target="_blank">' . htmlspecialchars($tourInfo['operatorlink'] ?? 'Не указано') . '</a></p>
                    <h6><b>Название отеля:</b> ' . htmlspecialchars($tourInfo['hotelname'] ?? 'Не указано') . '</h6>
                    <p><b>Рейтинг:</b> ' . htmlspecialchars($tourInfo['hotelstars'] ?? 'Не указано') . '</p>
                    <p><b>Регион:</b> ' . htmlspecialchars($tourInfo['hotelregionname'] ?? 'Не указано') . '</p>
                    <p><b>Страна:</b> ' . htmlspecialchars($tourInfo['countryname'] ?? 'Не указано') . '</p>
                    <p><b>Город вылета:</b> ' . htmlspecialchars($tourInfo['departurename'] ?? 'Не указано') . '</p>
                    <p><b>Дата вылета:</b> ' . htmlspecialchars($tourInfo['flydate'] ?? 'Не указано') . '</p>
                    <p><b>Количество ночей:</b> ' . htmlspecialchars($tourInfo['nights'] ?? 'Не указано') . '</p>
                    <p><b>Питание:</b> ' . htmlspecialchars($tourInfo['meal'] ?? 'Не указано') . '</p>
                    <p><b>Комната:</b> ' . htmlspecialchars($tourInfo['room'] ?? 'Не указано') . '</p>
                    <p><b>Размещение:</b> ' . htmlspecialchars($tourInfo['placement'] ?? 'Не указано') . '</p>
                    <p><b>Взрослых:</b> ' . htmlspecialchars($tourInfo['adults'] ?? 'Не указано') . '</p>
                    <p><b>Детей:</b> ' . htmlspecialchars($tourInfo['child'] ?? 'Не указано') . '</p>
                    <p><b>Цена:</b> ' . htmlspecialchars(number_format($tourInfo['price'] ?? 0, 0, ',', ' ')) . ' ' . htmlspecialchars($tourInfo['currency'] ?? 'Не указано') . '</p>
                    <p><b>Оператор:</b> ' . htmlspecialchars($tourInfo['operatorname'] ?? 'Не указано') . '</p>
                    <p><b>Описание отеля:</b> ' . htmlspecialchars($tourInfo['hoteldescription'] ?? 'Не указано') . '</p>
                    <p><b>Пожелания:</b> ' . htmlspecialchars($dopInfo['dop_pojelaniya'] ?? 'Не указано') . '</p>
                </div>
            </div>';

    return $html;
}
?>