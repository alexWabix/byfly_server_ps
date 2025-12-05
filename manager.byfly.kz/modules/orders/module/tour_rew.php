<?php
function tourRew($id, $db)
{
    $rew = array();
    $tourMediaListDB = $db->query("SELECT * FROM order_media WHERE order_id='" . $id . "'");
    while ($tourMediaList = $tourMediaListDB->fetch_assoc()) {
        $rew[] = $tourMediaList;
    }

    if (empty($rew)) {
        return '<div class="alert alert-info">Нет отзывов для данного тура.</div>';
    }

    $html = '<div class="container mt-4">';
    $html .= '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">';

    foreach ($rew as $item) {
        $mediaType = $item['media_type'];
        $mediaLink = htmlspecialchars($item['link_media']);

        $html .= '<div class="col">';
        $html .= '<div class="card h-100" style="background: url(' . $mediaLink . ') center/cover no-repeat;">';

        if ($mediaType === 'video') {
            $html .= '<div class="card-body d-flex flex-column">';
            $html .= '<video controls style="width: 100%; height: auto;">';
            $html .= '<source src="' . $mediaLink . '" type="video/mp4">';
            $html .= 'Ваш браузер не поддерживает видео.';
            $html .= '</video>';
            $html .= '<a href="' . $mediaLink . '" target="_blank" class="btn btn-primary mt-2">Открыть в новой вкладке</a>';
            $html .= '</div>';
        } else {
            $html .= '<a href="' . $mediaLink . '" target="_blank" style="display: block; width: 100%; height: 100%;"></a>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
?>