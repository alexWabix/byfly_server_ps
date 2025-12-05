<?php
// Параметры пагинации
$currentPage = isset($_GET['current_page']) ? max(1, intval($_GET['current_page'])) : 1;
$limit = 10;
$offset = ($currentPage - 1) * $limit;

// Подсчет общего количества заявок со статусом 0
$totalQuery = "SELECT COUNT(*) as total FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code = 1";
$totalResult = $db->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'];

$totalPages = ceil($total / $limit);

// Получение данных с учетом пагинации
$listOrdersNewDB = $db->query("
    SELECT * FROM order_tours 
    WHERE manager_id ='" . $userInfo['id'] . "' 
    AND status_code = 1
    ORDER BY id DESC 
    LIMIT $limit OFFSET $offset
");

$tours = [];
while ($listOrdersNew = $listOrdersNewDB->fetch_assoc()) {
    $listOrdersNew['tours_info'] = json_decode($listOrdersNew['tours_info'], true);
    $listOrdersNew['listPassangers'] = json_decode($listOrdersNew['listPassangers'], true);
    $listOrdersNew['visor_hotel_info'] = json_decode(preg_replace('/[\x00-\x1F\x7F]/u', '', trim($listOrdersNew['visor_hotel_info'])), true);
    $listOrdersNew['byly_hotel_info'] = json_decode(preg_replace('/[\x00-\x1F\x7F]/u', '', trim($listOrdersNew['byly_hotel_info'])), true);
    $tours[] = $listOrdersNew;
}

$statuses = [
    '0' => 'Новая в обработке',
    '1' => 'Подтверждена - Требуется предоплата',
    '2' => 'Подтверждена - Требуется полная оплата',
    '3' => 'Полностью оплачена ожидает вылета',
    '4' => 'Турист на отдыхе',
    '5' => 'Отменена'
];

// Функция для сохранения всех параметров из $_GET
function buildQuery($additionalParams = [])
{
    $queryParams = array_merge($_GET, $additionalParams);
    return http_build_query($queryParams);
}
?>

<div class="container mt-4">
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th style="font-size: 10px;">ID</th>
                    <th style="font-size: 10px;">Туристы</th>
                    <th style="font-size: 10px;">Название</th>
                    <th style="font-size: 10px;">Дата вылета</th>
                    <th style="font-size: 10px;">Цена</th>
                    <th style="font-size: 10px;">Предоплата</th>
                    <th style="font-size: 10px;">Внесено</th>
                    <th style="font-size: 10px;">Накрутка</th>
                    <th style="font-size: 10px;">Статус</th>
                    <th style="font-size: 10px;">Оплатить до</th>
                    <th style="font-size: 10px;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tours as $tour): ?>
                    <tr>
                        <td style="font-size: 10px;"><?= $tour['id']; ?></td>
                        <td style="font-size: 10px;">
                            <i class="icon ion-person"></i> <?= htmlspecialchars($tour['tours_info']['adults']); ?>
                            <i class="icon ion-person-stalker"></i> <?= htmlspecialchars($tour['tours_info']['child']); ?>
                        </td>
                        <td style="font-size: 10px;">
                            <?= htmlspecialchars($tour['tours_info']['hotelname']); ?>
                        </td>
                        <td style="font-size: 10px;">
                            <?= date('d.m.y H:i', strtotime($tour['tours_info']['flydate'])); ?>
                        </td>
                        <td style="font-size: 10px;">
                            <input type="number" id="price<?= $tour['id']; ?>"
                                value="<?= htmlspecialchars($tour['price']); ?>" class="form-control form-control-sm">
                        </td>
                        <td style="font-size: 10px;">
                            <input type="number" id="client_price<?= $tour['id']; ?>"
                                value="<?= htmlspecialchars($tour['predoplata']); ?>" class="form-control form-control-sm">
                        </td>
                        <td width="7%" style="font-size: 10px;">
                            <?= number_format((float) $tour['includesPrice'], 0, '.', ' ') . ' KZT'; ?>
                        </td>
                        <td style="font-size: 14px; text-align: center;">
                            <?= htmlspecialchars($tour['nakrutka']); ?>%
                        </td>
                        <td style="font-size: 10px;">
                            <select name="status[<?= $tour['id']; ?>]" class="form-select form-select-sm">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= $key; ?>" <?= isset($tour['status_code']) && $tour['status_code'] == $key ? 'selected' : ($key == '0' ? 'selected' : ''); ?>>
                                        <?= $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="font-size: 10px;">
                            <input type="datetime-local" name="pay_until[<?= $tour['id']; ?>]"
                                value="<?= date('Y-m-d\TH:i', strtotime($tour['dateOffPay'] ?? '+1 hour')); ?>"
                                class="form-control form-control-sm">
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button"
                                    id="dropdownMenuButton<?= $tour['id']; ?>" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Действия
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end"
                                    aria-labelledby="dropdownMenuButton<?= $tour['id']; ?>">
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#tourModal<?= $tour['id']; ?>"><i class="icon ion-ios-eye"></i>
                                            Подробнее</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Pagination">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?= buildQuery(['current_page' => $i]) ?>" style="
                        color: <?= $i == $currentPage ? '#fff' : '#000'; ?>; 
                        background-color: <?= $i == $currentPage ? '#dc3545' : 'transparent'; ?>; 
                        border-color: #dc3545;">
                            <?= $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>