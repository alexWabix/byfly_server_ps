<?php
function viewListPassangers($listPassangers)
{
    $passangersList = '';
    foreach ($listPassangers as $passenger) {
        $passangersList .= '<div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">
                    ' . htmlspecialchars($passenger['passanger_famale'] ?? 'Не указано') . ' ' . htmlspecialchars($passenger['passanger_name'] ?? 'Не указано') . '
                </h6>
                <p><b>Гражданство:</b> ' . htmlspecialchars($passenger['grazhdanstvo'] ?? 'Не указано') . '</p>
                <p><b>Дата рождения:</b> ' . htmlspecialchars($passenger['date_berthday'] ?? 'Не указано') . '</p>
                <p><b>Номер телефона:</b> ' . htmlspecialchars($passenger['passangers_phone'] ?? 'Не указано') . '</p>
                <p><b>ИИН:</b> ' . htmlspecialchars($passenger['iin'] ?? 'Не указано') . '</p>
                <p><b>Номер удостоверения:</b> ' . htmlspecialchars($passenger['number_udv'] ?? 'Не указано') . '</p>
                <p><b>Срок действия удостоверения:</b> ' . htmlspecialchars($passenger['udv_srok'] ?? 'Не указано') . '</p>
                <p><b>Номер паспорта:</b> ' . htmlspecialchars($passenger['number_pasport'] ?? 'Не указано') . '</p>
                <p><b>Срок действия паспорта:</b> ' . htmlspecialchars($passenger['pasport_srok'] ?? 'Не указано') . '</p>
                <p><b>Детский:</b> ' . (($passenger['isChildren'] ?? 0) ? 'Да' : 'Нет') . '</p>
                <div>
                    <b>Документы:</b>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <a href="' . htmlspecialchars($passenger['link_from_documents1'] ?? '#') . '" target="_blank">
                            <img src="' . htmlspecialchars($passenger['link_from_documents1'] ?? '#') . '" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Документ 1">
                        </a>
                        <a href="' . htmlspecialchars($passenger['link_from_documents2'] ?? '#') . '" target="_blank">
                            <img src="' . htmlspecialchars($passenger['link_from_documents2'] ?? '#') . '" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Документ 2">
                        </a>
                        <a href="' . htmlspecialchars($passenger['pasport_link'] ?? '#') . '" target="_blank">
                            <img src="' . htmlspecialchars($passenger['pasport_link'] ?? '#') . '" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Паспорт">
                        </a>
                    </div>
                </div>
            </div>
        </div>';
    }

    return $passangersList ?: '<p>Данные о пассажирах отсутствуют.</p>';
}
?>