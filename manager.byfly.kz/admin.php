<?php
$validUsername = 'byfly';
$validPassword = '20242025';

// Проверяем, переданы ли данные авторизации
if (
    !isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $validUsername || $_SERVER['PHP_AUTH_PW'] !== $validPassword
) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Доступ запрещён: неверный логин или пароль.';
    exit;
}



include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


/*$listUserDB = $db->query("SELECT * FROM new_year");
while ($listUser = $listUserDB->fetch_assoc()) {
    if ($listUser['is_pay'] != '1' || $listUser['is_pay'] != 1) {
        $db->query("DELETE FROM new_year WHERE id='" . $listUser['id'] . "'");
    } else {
        $db->query("UPDATE new_year SET `go`='1' WHERE id='" . $listUser['id'] . "'");
        $checkISAgentDB = $db->query("SELECT * FROM users WHERE phone='" . preg_replace('/\D/', '', $listUser['phone']) . "' AND `date_couch_start` IS NOT NULL");
        if ($checkISAgentDB->num_rows > 0) {
            $db->query("UPDATE new_year SET `is_agent`='1' WHERE id='" . $listUser['id'] . "'");
        }
    }
}*/

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'updateStatus') {
            $id = $_POST['id'];
            $is_pay = $_POST['is_pay'];
            $query = "UPDATE `byfly.2.0`.`new_year` SET `is_pay` = '$is_pay' WHERE `id` = $id";
            $db->query($query);
        } elseif ($_POST['action'] === 'addParticipant') {
            $fio = $_POST['fio'];
            $phone = $_POST['phone'];
            $message = $_POST['message'];
            $city = $_POST['city'];
            $query = "INSERT INTO `byfly.2.0`.`new_year` (`id`, `fio`, `phone`, `message`, `city`, `date_create`, `is_pay`, `is_agent`, `go`) VALUES (NULL, '$fio', '$phone', '$message', '$city', CURRENT_TIMESTAMP, '0', '0', '0');";
            $db->query($query);
        } elseif ($_POST['action'] === 'deleteParticipant') {
            $id = $_POST['id'];
            $query = "DELETE FROM `byfly.2.0`.`new_year` WHERE `id` = $id";
            $db->query($query);
        } elseif ($_POST['action'] === 'checked') {
            $id = $_POST['id'];
            $is_pay = $_POST['checked'];
            $query = "UPDATE `byfly.2.0`.`new_year` SET `go` = '$is_pay' WHERE `id` = $id";
            $db->query($query);
        } elseif ($_POST['action'] === 'updatePhone') {
            $id = $_POST['id'];
            $phone = $_POST['phone'];
            $query = "UPDATE `byfly.2.0`.`new_year` SET `phone` = '$phone' WHERE `id` = $id";
            $db->query($query);
        }



    }
}

// Получение данных с фильтрами
$filters = [];
if (!empty($_GET['city'])) {
    $city = $db->real_escape_string($_GET['city']);
    $filters[] = "`city` = '$city'";
}
if (isset($_GET['is_pay'])) {
    $is_pay = $db->real_escape_string($_GET['is_pay']);
    $filters[] = "`is_pay` = '$is_pay'";
}

$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';
$query = "SELECT * FROM `byfly.2.0`.`new_year` $where ORDER BY `date_create` DESC";
$result = $db->query($query);
$participants = $result->fetch_all(MYSQLI_ASSOC);

// Общая статистика по городам только оплаченных
$queryTotal = "SELECT city, SUM(CASE WHEN is_pay = '1' THEN CASE WHEN city = 'Алматы' THEN 20000 ELSE 5000 END ELSE 0 END) as amount, SUM(CASE WHEN is_pay = '1' THEN 1 ELSE 0 END) as count FROM `byfly.2.0`.`new_year` GROUP BY city";
$resultTotal = $db->query($queryTotal);
$cities = [];
while ($row = $resultTotal->fetch_assoc()) {
    $cities[$row['city']] = [
        'amount' => $row['amount'],
        'count' => $row['count']
    ];
}

// Расчет общей суммы в кассе
$totalAmount = array_sum(array_column($cities, 'amount'));
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление участниками</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table tbody tr.unpaid {
            background-color: #f8d7da;
        }

        .table td a {
            font-size: 0.9rem;
            color: #007bff;
            text-decoration: none;
        }

        .btn-group {
            display: flex;
            justify-content: center;
        }

        .btn-group button {
            margin: 0 2px;
        }

        .table {
            font-size: 0.9rem;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"
        integrity="sha512-d4KkQohk+HswGs6A1d6Gak6Bb9rMWtxjOa0IiY49Q3TeFd5xAzjWXDCBW9RS7m86FQ4RzM2BdHmdJnnKRYknxw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</head>

<body>
    <div class="container my-5">
        <h1 class="text-start mb-4">Управление участниками</h1>

        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <label for="city" class="form-label">Город</label>
                    <select id="city" name="city" class="form-select">
                        <option value="">Все</option>
                        <option value="Алматы" <?= (isset($_GET['city']) && $_GET['city'] === 'Алматы') ? 'selected' : '' ?>>Алматы</option>
                        <option value="Уральск" <?= (isset($_GET['city']) && $_GET['city'] === 'Уральск') ? 'selected' : '' ?>>Уральск</option>
                        <option value="Шымкент" <?= (isset($_GET['city']) && $_GET['city'] === 'Шымкент') ? 'selected' : '' ?>>Шымкент</option>
                        <option value="Усть-Каменогорск" <?= (isset($_GET['city']) && $_GET['city'] === 'Усть-Каменогорск') ? 'selected' : '' ?>>Усть-Каменогорск</option>
                        <option value="Астана" <?= (isset($_GET['city']) && $_GET['city'] === 'Астана') ? 'selected' : '' ?>>Астана</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="is_pay" class="form-label">Статус оплаты</label>
                    <select id="is_pay" name="is_pay" class="form-select">
                        <option value="">Все</option>
                        <option value="1" <?= (isset($_GET['is_pay']) && $_GET['is_pay'] === '1') ? 'selected' : '' ?>>
                            Оплачено</option>
                        <option value="0" <?= (isset($_GET['is_pay']) && $_GET['is_pay'] === '0') ? 'selected' : '' ?>>Не
                            оплачено</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Фильтровать</button>
                </div>
            </div>
        </form>

        <div class="text-start mb-3">
            <h4>Итоговая сумма в кассе: <span class="text-success"><?= number_format($totalAmount, 0, '.', ' ') ?>
                    KZT</span></h4>
        </div>

        <div class="text-start mb-3">
            <h5>Касса по городам:</h5>
            <ul>
                <?php foreach ($cities as $city => $data): ?>
                    <li><strong><?= htmlspecialchars($city) ?>:</strong> <?= number_format($data['amount'], 0, '.', ' ') ?>
                        KZT (<?= $data['count'] ?> участников)</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <section class="table-container">
            <h2 class="mb-3">Список участников</h2>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Сообщение</th>
                        <th>Город</th>
                        <th>Дата регистрации</th>
                        <th>Оплата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <form action="admin.php" method="POST">
                            <input type="hidden" name="action" value="addParticipant">
                            <td></td>
                            <td></td>
                            <td>
                                <input type="text" name="fio" class="form-control form-control-sm"
                                    placeholder="Введите ФИО" required>
                            </td>
                            <td style="width: 18%;">
                                <input type="text" name="phone" id="phone" class="form-control form-control-sm"
                                    placeholder="Введите телефон" required>
                            </td>
                            <td></td>
                            <td>
                                <select name="city" class="form-select form-select-sm" required>
                                    <option value="">Выберите город</option>
                                    <option value="Алматы">Алматы</option>
                                    <option value="Уральск">Уральск</option>
                                    <option value="Шымкент">Шымкент</option>
                                    <option value="Усть-Каменогорск">Усть-Каменогорск</option>
                                    <option value="Астана">Астана</option>
                                </select>
                            </td>
                            <td>Автоматически</td>
                            <td>Не оплачено</td>
                            <td>
                                <button type="submit" class="btn btn-sm btn-primary">Добавить</button>
                            </td>
                        </form>
                    </tr>
                    <?php foreach ($participants as $participant): ?>
                        <tr class="<?= $participant['is_pay'] == '0' ? 'unpaid' : '' ?>">
                            <td>
                                <form id="formCheck<?php echo $participant['id']; ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="checked">
                                    <input type="hidden" name="id" value="<?= $participant['id'] ?>">
                                    <input name="checked" <?= $participant['go'] == '0' ? '' : 'checked' ?>
                                        onchange="$('#formCheck<?php echo $participant['id']; ?>').submit()" type="checkbox"
                                        value="1">
                                </form>
                            </td>
                            <td><?= $participant['id'] ?></td>
                            <td><?= htmlspecialchars($participant['fio']) ?></td>
                            <td style="width: 18%;">
                                <form method="POST" class="d-inline ">
                                    <input type="hidden" name="action" value="updatePhone">
                                    <input type="hidden" name="id" value="<?= $participant['id'] ?>">
                                    <div class="input-group">
                                        <input style="width: 100px;" type="text" class="form-control form-control-sm"
                                            value="<?= htmlspecialchars($participant['phone']) ?>" name="phone"
                                            placeholder="Введите номер телефона">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            ОК
                                        </button>
                                    </div>

                                </form>
                            </td>
                            <td><?= htmlspecialchars($participant['message']) ?></td>
                            <td><?= htmlspecialchars($participant['city']) ?></td>
                            <td><?= $participant['date_create'] ?></td>
                            <td><?= $participant['is_pay'] == '1' ? 'Оплачено' : 'Не оплачено' ?></td>
                            <td>
                                <div class="btn-group">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="updateStatus">
                                        <input type="hidden" name="id" value="<?= $participant['id'] ?>">
                                        <input type="hidden" name="is_pay"
                                            value="<?= $participant['is_pay'] == '1' ? '0' : '1' ?>">
                                        <button type="submit"
                                            class="btn btn-sm <?= $participant['is_pay'] == '1' ? 'btn-danger' : 'btn-success' ?>">
                                            <?= $participant['is_pay'] == '1' ? 'Отменить оплату' : 'Подтвердить оплату' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="deleteParticipant">
                                        <input type="hidden" name="id" value="<?= $participant['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
    <script>
        window.onload = function () {
            $("#phone").mask("+7 (999) 999 99 99");
        }
    </script>
</body>

</html>