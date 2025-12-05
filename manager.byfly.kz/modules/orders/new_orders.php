<?php
$page = isset($_GET['pagination']) ? max(1, intval($_GET['pagination'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;


function formatFIO($fio)
{
    $parts = explode(' ', $fio);
    $lastName = $parts[0] ?? ''; // Фамилия
    $firstName = isset($parts[1]) ? mb_substr($parts[1], 0, 1) . '.' : ''; // Первая буква имени
    $middleName = isset($parts[2]) ? mb_substr($parts[2], 0, 1) . '.' : ''; // Первая буква отчества

    return trim("$lastName $firstName$middleName");
}

if (empty($search_text)) {
    if ($userInfo['type'] == 1) {
        $totalQuery = "SELECT COUNT(*) as total FROM order_tours WHERE status_code = " . $status . " AND type != 'test'";
    } else {
        $totalQuery = "SELECT COUNT(*) as total FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code = " . $status . " AND type != 'test'";
    }
} else {
    if ($userInfo['type'] == 1) {
        $totalQuery = "SELECT COUNT(*) as total FROM order_tours WHERE id = '" . $search_text . "'";
    } else {
        $totalQuery = "SELECT COUNT(*) as total FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND id = '" . $search_text . "'" . " AND type != 'test'";
    }

}


$totalResult = $db->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'];


$totalPages = ceil($total / $limit);

if (empty($search_text)) {
    if ($userInfo['type'] == 1 || $userInfo['type'] == '1') {
        $listOrdersNewDB = $db->query("SELECT * FROM order_tours WHERE status_code = " . $status . " AND type != 'test' ORDER BY flyDate ASC LIMIT $limit OFFSET $offset");
    } else {
        $listOrdersNewDB = $db->query("SELECT * FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code = " . $status . " AND type != 'test' ORDER BY flyDate ASC LIMIT $limit OFFSET $offset");
    }

} else {
    if ($userInfo['type'] == 1 || $userInfo['type'] == '1') {
        $listOrdersNewDB = $db->query("SELECT * FROM order_tours WHERE id = '" . $search_text . "' AND type != 'test' ORDER BY flyDate ASC LIMIT $limit OFFSET $offset");
    } else {
        $listOrdersNewDB = $db->query("SELECT * FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND id = '" . $search_text . "' AND type != 'test' ORDER BY flyDate ASC LIMIT $limit OFFSET $offset");
    }
}


$tours = [];
$listManagers = [];

$listManagersDB = $db->query("SELECT * FROM managers WHERE work_for_tours='1'");
while ($listManagersed = $listManagersDB->fetch_assoc()) {
    $listManagers[] = $listManagersed;
}

while ($listOrdersNew = $listOrdersNewDB->fetch_assoc()) {
    $listOrdersNew['tours_info'] = json_decode($listOrdersNew['tours_info'], true);
    $listOrdersNew['listPassangers'] = json_decode($listOrdersNew['listPassangers'], true);
    $listOrdersNew['visor_hotel_info'] = json_decode(preg_replace('/[\x00-\x1F\x7F]/u', '', trim($listOrdersNew['visor_hotel_info'])), true);
    $listOrdersNew['byly_hotel_info'] = json_decode(preg_replace('/[\x00-\x1F\x7F]/u', '', trim($listOrdersNew['byly_hotel_info'])), true);
    $listOrdersNew['manager_info'] = $db->query("SELECT * FROM managers WHERE id='" . $listOrdersNew['manager_id'] . "'")->fetch_assoc();
    $tours[] = $listOrdersNew;
}

// Статусы заявок
$statuses = [
    '0' => 'Новая в обработке',
    '1' => 'Подтверждена - Требуется предоплата',
    '2' => 'Подтверждена - Требуется полная оплата',
    '3' => 'Полностью оплачена ожидает вылета',
    '4' => 'Турист на отдыхе',
    '5' => 'Отменена'
];

function getManagerPercentage($price)
{
    global $userInfo;
    $percentageValue = ceil(((($price / 100) * 7) / 100) * $userInfo['percentage_of_commisiion']);

    // Форматируем с разделением на разряды и добавляем KZT
    return '+ ' . number_format($percentageValue, 0, '.', ' ') . ' KZT';
}
?>

<style>
    .table td,
    .table th {
        vertical-align: middle !important;
    }

    .form-control-sm {
        font-size: 10px;
    }

    .form-select-sm {
        font-size: 10px;
    }
</style>



<div class="container mt-4">
    <?php
    include('modules/orders/search_orders.php');
    ?>
    <div class="table-responsive" style="overflow: visible;">
        <table class=" table table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th style="font-size: 10px;">ID</th>
                    <th style="font-size: 10px;">Менеджер</th>
                    <th style="font-size: 10px;">Туристы</th>
                    <th style="font-size: 10px;">Название</th>
                    <th style="font-size: 10px;">Дата вылета</th>
                    <th style="font-size: 10px;">Цена</th>
                    <th style="font-size: 10px;">Реальная</th>
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
                        <tr <?= $tour['type'] === 'spec' ? 'class="table-danger"' : '' ?>>
                            <td style="font-size: 10px;"><?= $tour['id']; ?></td>
                            <td style="font-size: 10px;">
                                <select <?= $userInfo['type'] === 0 ? 'style="display: none;"' : '' ?>
                                    name="manager[<?= $tour['id']; ?>]" class="form-select form-select-sm">
                                    <?php foreach ($listManagers as $mng): ?>
                                            <option value="<?= $mng['id']; ?>" <?= $mng['id'] == $tour['manager_id'] ? 'selected' : ''; ?>>
                                                <?= formatFIO($mng['fio']); ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="badge badge-success w-100 mt-2">
                                    <?= getManagerPercentage($tour['tours_info']['price']); ?>
                                </span>
                            </td>
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
                                <input type="number" id="realprice<?= $tour['id']; ?>"
                                    value="<?= htmlspecialchars($tour['real_price']); ?>" class="form-control form-control-sm">
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
                                <div class="btn-group">
                                    <div class="dropdown">
                                        <button style="border-radius: 0px;" class="btn btn-secondary btn-sm dropdown-toggle"
                                            type="button" id="dropdownMenuButton<?= $tour['id']; ?>" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            Действия
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="dropdownMenuButton<?= $tour['id']; ?>">
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#tourModal<?= $tour['id']; ?>"><i
                                                        class="icon ion-ios-eye"></i>
                                                    Подробнее</a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#extraModal<?= $tour['id']; ?>"><i
                                                        class="icon ion-ios-list"></i> Дополнительно</a></li>
                                            <li><a class="dropdown-item" onclick="updateOrderWithLoader(<?= $tour['id']; ?>);"
                                                    href="#"><i class="icon ion-checkmark"></i> Сохранить изменения</a></li>
                                            <li><a class="dropdown-item" href="<?= $tour['tours_info']['operatorlink']; ?>"
                                                    target="_blank"><i class="icon ion-link"></i> Открыть на сайте оператора</a>
                                            </li>
                                            <?php
                                            if ($userInfo['type'] == 1) {
                                                echo '<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settlementsModal' . $tour['id'] . '"><i class="icon ion-cash"></i> Взаиморасчеты с туроператором</a></li>';
                                            }
                                            ?>
                                            <li><a class="dropdown-item text-danger" onclick="deleteOrder(<?= $tour['id']; ?>);"
                                                    href="#"><i class="icon ion-trash-a"></i> Удалить</a></li>
                                        </ul>
                                    </div>
                                    <button style="border-radius: 0px;" onclick="updateOrderWithLoader(<?= $tour['id']; ?>);"
                                        class="btn btn-success btn-sm" type="button">
                                        <i class="ion-ios-refresh"></i>
                                    </button>
                                    <button style="border-radius: 0px; display: none;" id="loader-<?= $tour['id'] ?>"
                                        class="btn btn-success btn-sm" type="button">
                                        <div style="color: white;" class="spinner-border spinner-border-sm text-white"
                                            role="status"></div>
                                    </button>
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
                            <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?= $_GET['page'] ?>&pagination=<?= $i; ?>"
                                    style="color: <?= $i == $page ? '#fff' : '#dc3545'; ?>; background-color: <?= $i == $page ? '#dc3545' : '#fff'; ?>; border-color: #dc3545;">
                                    <?= $i; ?>
                                </a>
                            </li>
                    <?php endfor; ?>
                </ul>
            </nav>
    <?php endif; ?>
</div>

<script type="text/javascript">
    function updateOrderWithLoader(id) {
        const price = $(`#price${id}`).val();
        const realprice = $(`#realprice${id}`).val();

        const clientPrice = $(`#client_price${id}`).val();
        const status = $(`select[name="status[${id}]"]`).val();
        const payUntil = $(`input[name="pay_until[${id}]"]`).val();
        const manager = $(`select[name="manager[${id}]"]`).val();

        const formattedDate = new Date(payUntil + "Z").toISOString().slice(0, 19).replace('T', ' ');

        const dropdown = $(`#dropdownMenuButton${id}`).parent();
        $("#loader-" + id).show();

        const formData = new FormData();
        formData.append('operation', 'order_update');
        formData.append('id', id);
        formData.append('price', price);
        formData.append('clientPrice', clientPrice);
        formData.append('status', status);
        formData.append('payUntil', payUntil);
        formData.append('manager', manager);
        formData.append('realprice', realprice);


        $.ajax({
            url: 'https://manager.byfly.kz/index.php',
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                const resp = JSON.parse(response);
                if (!resp.success) {
                    alert("Не удалось обновить данные!");
                } else {
                    location.reload();
                }
            },
            error: function (xhr, status, error) {
                alert("Произошла ошибка: " + error);
            },
            complete: function () {
                setTimeout(1000, function () {
                    $("#loader" + id).hide();
                });
            }
        });
    }

    function deleteOrder(id) {
        if (!confirm("Вы уверены, что хотите удалить этот тур?")) {
            return;
        }

        const dropdown = $(`#dropdownMenuButton${id}`).parent();
        $("#loader-" + id).show();

        $.ajax({
            url: window.location.href,
            type: "POST",
            data: {
                operation: "order_delete",
                id: id
            },
            success: function (response) {
                const resp = JSON.parse(response);
                if (resp.success) {
                    location.reload();
                } else {
                    alert("Не удалось удалить тур!");
                }
            },
            error: function (xhr, status, error) {
                alert("Произошла ошибка: " + error);
            },
            complete: function () {
                setTimeout(1000, function () {
                    $("#loader" + id).hide();
                });
            }
        });
    }
</script>


<style>
    .tab-pane {
        display: none;
        /* Скрыть вкладки полностью */
    }

    .tab-pane.active {
        display: block;
        /* Показывать только активную вкладку */
    }

    .accordion {
        margin-top: 0;
        /* Убираем лишний отступ */
    }
</style>

<?php foreach ($tours as $tour): ?>
        <div class="modal fade" id="extraModal<?= $tour['id']; ?>" tabindex="-1"
            aria-labelledby="extraModalLabel<?= $tour['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="extraModalLabel<?= $tour['id']; ?>">Дополнительно для заявки
                            #<?= $tour['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="extraTab<?= $tour['id']; ?>" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="reviews-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#reviews<?= $tour['id']; ?>" type="button" role="tab" aria-selected="true">
                                    Отзывы
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documents-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#documents<?= $tour['id']; ?>" type="button" role="tab"
                                    aria-selected="false">
                                    Документы
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="extraPayments-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#extraPayments<?= $tour['id']; ?>" type="button" role="tab"
                                    aria-selected="false">
                                    Доп. оплаты
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="printContract-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#printContract<?= $tour['id']; ?>" type="button" role="tab"
                                    aria-selected="false">
                                    Принять наличные
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="vozvrat-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#vozvrat<?= $tour['id']; ?>" type="button" role="tab">Возвраты</button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="reviews<?= $tour['id']; ?>" role="tabpanel"
                                aria-labelledby="reviews-tab<?= $tour['id']; ?>">
                                <?php echo tourRew($tour['id'], $db); ?>
                            </div>
                            <div class="tab-pane fade" id="documents<?= $tour['id']; ?>" role="tabpanel"
                                aria-labelledby="documents-tab<?= $tour['id']; ?>">
                                <?php echo docsTour($tour['id'], $db); ?>
                            </div>
                            <div class="tab-pane fade" id="extraPayments<?= $tour['id']; ?>" role="tabpanel"
                                aria-labelledby="extraPayments-tab<?= $tour['id']; ?>">
                                <?php echo dopPays($tour['id'], $db); ?>
                            </div>
                            <div class="tab-pane fade" id="printContract<?= $tour['id']; ?>" role="tabpanel"
                                aria-labelledby="printContract-tab<?= $tour['id']; ?>">
                                <?php echo getPayments($tour['id'], $db, $tour['user_id']); ?>
                            </div>
                            <div class="tab-pane fade" style="padding: 20px;" id="vozvrat<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo getVozvrat($tour['id'], $db);
                                ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Закрыть</button>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
<?php endforeach; ?>

<style>
    .modal-header {
        position: relative;
    }

    .btn-close {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    .tab-content {
        display: block;
        /* Исправляем отображение содержимого табов */
    }

    .tab-pane {
        padding: 10px;
        /* Дополнительный отступ внутри табов */
    }
</style>



<!-- Модальные окна -->
<?php foreach ($tours as $tour): ?>
        <div class="modal fade" id="tourModal<?= $tour['id']; ?>" tabindex="-1"
            aria-labelledby="tourModalLabel<?= $tour['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tourModalLabel<?= $tour['id']; ?>">Детальная информация о туре</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Табы -->
                        <ul class="nav nav-tabs" id="tab<?= $tour['id']; ?>" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tourinfo-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#tourinfo<?= $tour['id']; ?>" type="button" role="tab">Турпакет</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="passengers-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#passengers<?= $tour['id']; ?>" type="button" role="tab">Пассажиры</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="hotel-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#hotel<?= $tour['id']; ?>" type="button" role="tab">Отель</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="flight-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#flight<?= $tour['id']; ?>" type="button" role="tab">Перелет</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="userinfo-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#userinfo<?= $tour['id']; ?>" type="button" role="tab">Клиент</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tranzaction-tab<?= $tour['id']; ?>" data-bs-toggle="tab"
                                    data-bs-target="#tranzaction<?= $tour['id']; ?>" type="button" role="tab">Операции</button>
                            </li>

                        </ul>
                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="tourinfo<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo tourInfo($tour['tours_info'], $dopInfo, $tour);
                                ?>
                            </div>
                            <!-- Пассажиры -->
                            <div class="tab-pane fade " id="passengers<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo viewListPassangers($tour['listPassangers']);
                                ?>
                            </div>
                            <!-- Отель -->
                            <div class="tab-pane fade" id="hotel<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo orderHotelInfo($tour['byly_hotel_info'], $tour['visor_hotel_info']);
                                ?>
                            </div>
                            <!-- Перелет -->
                            <div class="tab-pane fade" id="flight<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo getFlyInfo($tour['tours_info']['fly_info']);
                                ?>
                            </div>
                            <div class="tab-pane fade" id="userinfo<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo getUserInfo($tour['user_id'], $db);
                                ?>
                            </div>
                            <div class="tab-pane fade" id="tranzaction<?= $tour['id']; ?>" role="tabpanel">
                                <?php
                                echo getTransactionOrder($tour['id'], $db);
                                ?>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach; ?>


<!-- Модальные окна для взаиморасчетов -->
<?php foreach ($tours as $tour): ?>
        <div class="modal fade" id="settlementsModal<?= $tour['id']; ?>" tabindex="-1"
            aria-labelledby="settlementsModalLabel<?= $tour['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settlementsModalLabel<?= $tour['id']; ?>">Взаиморасчеты с туроператором для
                            заявки #<?= $tour['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        echo operatorsOperation($tour['id'], $db);
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach; ?>