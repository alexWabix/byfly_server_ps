<?php
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type']) && $_POST['type'] === 'addPersonal') {
    $fio = $db->real_escape_string($_POST['name']);
    $birthDate = $db->real_escape_string($_POST['birth_date']);
    $branch = intval($_POST['branch']);
    $phone = $db->real_escape_string($_POST['phone']);
    $whatsapp = $db->real_escape_string($_POST['whatsapp']);
    $email = $db->real_escape_string($_POST['email']);
    $isAdmin = intval($_POST['is_admin']);
    $worksTours = intval($_POST['works_tours']);
    $specialOffers = intval($_POST['special_offers']);
    $commission = intval($_POST['commission']);
    $salary = intval($_POST['salary']);
    $employmentDate = $db->real_escape_string($_POST['employment_date']);

    // Генерация случайного пароля
    $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $linkDogovorReal = null;
    if (isset($_FILES['contract']) && $_FILES['contract']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'loaded/';
        $fileExtension = pathinfo($_FILES['contract']['name'], PATHINFO_EXTENSION);
        $newFileName = date('Y-m-d_H-i-s') . '.' . $fileExtension;
        $linkDogovor = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['contract']['tmp_name'], $linkDogovor)) {
            $linkDogovorReal = 'https://manager.byfly.kz/loaded/' . $newFileName;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка загрузки файла.']);
            exit;
        }
    }

    $query = "INSERT INTO managers (
        fio, franchaise, phone_call, phone_whatsapp, avatar, email, type, work_for_tours,
        show_spec, percentage_of_commisiion, oklad, date_start_work, berthday, linkDogovor, password
    ) VALUES (
        '$fio', $branch, '$phone', '$whatsapp', 'https://api.v.2.byfly.kz/images/no-ava.png', '$email', $isAdmin, $worksTours,
        $specialOffers, $commission, $salary, '$employmentDate', '$birthDate', '$linkDogovorReal', '$hashedPassword'
    )";

    if ($db->query($query)) {
        $message = "Добрый день, $fio!\n\nВаш аккаунт в системе ByFly успешно создан.\nВаш логин: $email\nВаш пароль: $password\n\nС уважением, команда ByFly.\n\nhttps://manager.byfly.kz/";
        sendWhatsapp($whatsapp, $message);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['method'] === 'personalUpdate') {
    $id = intval($_POST['id']);
    $fio = $db->real_escape_string($_POST['name']);
    $franchaise = intval($_POST['branch']);
    $phone_call = $db->real_escape_string($_POST['phone']);
    $phone_whatsapp = $db->real_escape_string($_POST['whatsapp']);
    $email = $db->real_escape_string($_POST['email']);
    $type = intval($_POST['is_admin']);
    $work_for_tours = intval($_POST['works_tours']);
    $show_spec = intval($_POST['special_offers']);
    $percentage_of_commisiion = intval($_POST['commission']);
    $oklad = intval($_POST['salary']);
    $date_start_work = $db->real_escape_string($_POST['employment_date']);
    $berthday = $db->real_escape_string($_POST['birth_date']);

    $linkDogovorReal = null;
    if (isset($_FILES['contract']) && $_FILES['contract']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'loaded/';

        $file_extension = pathinfo($_FILES['contract']['name'], PATHINFO_EXTENSION);

        $new_file_name = date('Y-m-d_H-i-s') . '.' . $file_extension;
        $linkDogovor = $upload_dir . $new_file_name;
        if (move_uploaded_file($_FILES['contract']['tmp_name'], $linkDogovor)) {
            $linkDogovorReal = 'https://manager.byfly.kz/loaded/' . $new_file_name;
        } else {
            die(json_encode(['status' => 'error', 'message' => 'Ошибка загрузки файла.']));
        }
    }

    $query = "UPDATE managers SET
              fio = '$fio',
              franchaise = $franchaise,
              phone_call = '$phone_call',
              phone_whatsapp = '$phone_whatsapp',
              email = '$email',
              type = $type,
              work_for_tours = $work_for_tours,
              show_spec = $show_spec,
              percentage_of_commisiion = $percentage_of_commisiion,
              oklad = $oklad,
              date_start_work = '$date_start_work',
              berthday = '$berthday'";

    if ($linkDogovorReal) {
        $query .= ", linkDogovor = '$linkDogovorReal'";
    }

    $query .= " WHERE id = $id";
    if ($db->query($query)) {
        $message = '<div class="alert alert-success">Данные успешно обновлены.</div>';
    } else {
        $message = '<div class="alert alert-danger">Ошибка обновления: ' . $db->error . '</div>';
    }
}
?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список сотрудников</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(to right, #ae011a, #4a000b);
            padding: 10px 20px;
        }

        .navbar-brand {
            color: white;
            font-weight: bold;
        }

        .nav-link {
            color: white;
        }

        .content {
            margin-top: 20px;
        }

        .employee-list {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #ae011a;
            border: none;
        }

        .btn-primary:hover {
            background-color: #8b0000;
        }

        .btn-danger:hover {
            background-color: #ff4b4b;
        }

        .modal-content {
            border-radius: 5px;
        }

        .nav-tabs .nav-link {
            color: #ae011a;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            margin-right: 5px;
        }

        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #ae011a;
            border-color: #ae011a;
        }

        .nav-tabs .nav-link:hover {
            background-color: #e7e7e7;
            border-color: #ddd;
        }

        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            text-align: center;
            padding-top: 25%;
        }

        .spinner-border {
            width: 4rem;
            height: 4rem;
        }


        .table tbody td,
        .table thead th {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div id="preloader">
        <div class="spinner-border text-light" role="status"></div>
    </div>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <?php include('modules/logo.php'); ?>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <?php include('modules/user_info.php'); ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php include('modules/header.php'); ?>

    <div class="container content">

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="ion-plus"></i> Добавить сотрудника
            </button>
        </div>

        <!-- Список сотрудников -->
        <div class="employee-list">
            <h4>Список сотрудников</h4>
            <!-- Навигация по вкладкам -->
            <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab"
                        data-bs-target="#active-employees" type="button" role="tab">
                        Действующие
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive-employees"
                        type="button" role="tab">
                        Уволенные
                    </button>
                </li>
            </ul>

            <style>
                table {
                    font-size: 12px;
                }
            </style>

            <!-- Содержимое вкладок -->
            <div class="tab-content" id="employeeTabsContent">
                <!-- Действующие сотрудники -->
                <div class="tab-pane fade show active" id="active-employees" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ФИО</th>
                                    <th>Франчайзи</th>
                                    <th>Телефон</th>
                                    <th>WhatsApp</th>
                                    <th>Email</th>
                                    <th>Заказы</th>
                                    <th>Спец-предл.</th>
                                    <th>Процент.</th>
                                    <th>Оклад</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="activeEmployeeTableBody">
                                <?php
                                $listManagerDB = $db->query("SELECT * FROM managers WHERE date_off_works IS NULL");
                                $counter = 1;
                                while ($listManager = $listManagerDB->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$counter}</td>
                                        <td>{$listManager['fio']}</td>
                                        <td>{$listManager['franchaise']}</td>
                                        <td>{$listManager['phone_call']}</td>
                                        <td>{$listManager['phone_whatsapp']}</td>
                                        <td>{$listManager['email']}</td>
                                        <td>" . ($listManager['work_for_tours'] ? 'Да' : 'Нет') . "</td>
                                        <td>" . ($listManager['show_spec'] ? 'Да' : 'Нет') . "</td>
                                        <td>{$listManager['percentage_of_commisiion']}%</td>
                                        <td>{$listManager['oklad']} KZT</td>
                                        <td>
                                            <button onclick='updateUser(" . json_encode($listManager, JSON_UNESCAPED_UNICODE) . ")' data-bs-toggle='modal' data-bs-target='#editEmployeeModal' class='btn btn-sm btn-primary edit-manager w-100 mb-1' data-id='{$listManager['id']}'>Изменить</button>
                                            <a href='https://manager.byfly.kz/index.php?page=zarplata&id={$listManager['id']}' target='_blank' class='btn btn-sm btn-warning salary-manager w-100 mb-1' data-id='{$listManager['id']}'>Зарплата</a>
                                            <button class='btn btn-danger btn-sm w-100 mb-1' onclick='fireEmployee(" . $listManager['id'] . ")'>Уволить</button>
                                            <button class='btn btn-info btn-sm w-100' onclick='sendPassword(" . $listManager["id"] . ")'>Отправить пароль</button>
                                        </td>
                                    </tr>";
                                    $counter++;
                                }
                                ?>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Уволенные сотрудники -->
                <div class="tab-pane fade" id="inactive-employees" role="tabpanel">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ФИО</th>
                                <th>Франчайзи</th>
                                <th>Телефон</th>
                                <th>WhatsApp</th>
                                <th>Email</th>
                                <th>Дата увольнения</th>
                            </tr>
                        </thead>
                        <tbody id="inactiveEmployeeTableBody">
                            <?php
                            $listManagerDB = $db->query("SELECT * FROM managers WHERE date_off_works IS NOT NULL");
                            $counter = 1;
                            while ($listManager = $listManagerDB->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$counter}</td>
                                        <td>{$listManager['fio']}</td>
                                        <td>{$listManager['franchaise']}</td>
                                        <td>{$listManager['phone_call']}</td>
                                        <td>{$listManager['phone_whatsapp']}</td>
                                        <td>{$listManager['email']}</td>
                                        <td>" . ($listManager['work_for_tours'] ? 'Да' : 'Нет') . "</td>
                                        <td>" . ($listManager['show_spec'] ? 'Да' : 'Нет') . "</td>
                                        <td>{$listManager['percentage_of_commisiion']}%</td>
                                        <td>{$listManager['oklad']} KZT</td>
                                        <td>
                                            <button onclick='updateUser(" . json_encode($listManager, JSON_UNESCAPED_UNICODE) . ")' data-bs-toggle='modal' data-bs-target='#editEmployeeModal' class='btn btn-sm btn-primary edit-manager w-100' data-id='{$listManager['id']}'>Реда-ть</button>
                                        </td>
                                        <td>
                                            <a href='https://manager.byfly.kz/index.php?page=zarplata&id={$listManager['id']}' target='_blank' class='btn btn-sm btn-warning salary-manager w-100' data-id='{$listManager['id']}'>Зар-та</a>
                                        </td>
                                        <td>
                                            <button class='btn btn-danger btn-sm' onclick='fireEmployee2(" . $listManager['id'] . ")'>Принять на работу</button>
                                        </td>
                                    </tr>";
                                $counter++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="index.php?page=personal" id="addEmployeeForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEmployeeModalLabel">Редактировать сотрудника</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="employeeId" name="id">
                        <input type="hidden" name="method" value="personalUpdate">
                        <div class="mb-3">
                            <label for="employeeName" class="form-label">ФИО</label>
                            <input type="text" class="form-control" id="employeeName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="birthDate" class="form-label">Дата рождения</label>
                            <input type="date" class="form-control" id="birthDate" name="birth_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="branch" class="form-label">Филиал</label>
                            <select class="form-select" id="branch" name="branch" required>
                                <option value="0" selected>Выбрать филиал</option>
                                <?php
                                $listFilialsDB = $db->query("SELECT * FROM franchaise");
                                while ($listFilials = $listFilialsDB->fetch_assoc()) {
                                    echo '<option value="' . $listFilials['id'] . '">' . $listFilials['adress'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон для звонков</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp" class="form-label">Телефон для WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="isAdmin" class="form-label">Администратор</label>
                            <select class="form-select" id="isAdmin" name="is_admin" required>
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="worksTours" class="form-label">Отрабатывает туры</label>
                            <select class="form-select" id="worksTours" name="works_tours" required>
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="specialOffers" class="form-label">Отрабатывает спецпредложения</label>
                            <select class="form-select" id="specialOffers" name="special_offers" required>
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="commission" class="form-label">Процент от комиссии</label>
                            <input type="number" class="form-control" id="commission" name="commission" required>
                        </div>
                        <div class="mb-3">
                            <label for="salary" class="form-label">Оклад</label>
                            <input type="number" class="form-control" id="salary" name="salary" required>
                        </div>
                        <div class="mb-3">
                            <label for="employmentDate" class="form-label">Дата приема на работу</label>
                            <input type="date" class="form-control" id="employmentDate" name="employment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="contract" class="form-label">Трудовой договор</label>
                            <input type="file" class="form-control" id="contract" name="contract">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Модальное окно для добавления сотрудника -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addEmployeeForm" method="POST" action="" enctype="multipart/form-data">
                    <!-- Скрытое поле для типа действия -->
                    <input type="hidden" name="type" value="addPersonal">

                    <div class="modal-header">
                        <h5 class="modal-title" id="addEmployeeModalLabel">Добавить сотрудника</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="employeeName" class="form-label">ФИО</label>
                            <input type="text" class="form-control" id="employeeName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="birthDate" class="form-label">Дата рождения</label>
                            <input type="date" class="form-control" id="birthDate" name="birth_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="branch" class="form-label">Филиал</label>
                            <select class="form-select" id="branch" name="branch" required>
                                <option value="0" selected>Выбрать филиал</option>
                                <?php
                                $listFilialsDB = $db->query("SELECT * FROM franchaise");
                                while ($listFilials = $listFilialsDB->fetch_assoc()) {
                                    echo '<option value="' . $listFilials['id'] . '">' . $listFilials['adress'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон для звонков</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp" class="form-label">Телефон для WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="isAdmin" class="form-label">Администратор</label>
                            <select class="form-select" id="isAdmin" name="is_admin" required>
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="worksTours" class="form-label">Отрабатывает туры</label>
                            <select class="form-select" id="worksTours" name="works_tours" required>
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="specialOffers" class="form-label">Отрабатывает спецпредложения</label>
                            <select class="form-select" id="specialOffers" name="special_offers" required>
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="commission" class="form-label">Процент от комиссии</label>
                            <input type="number" class="form-control" id="commission" name="commission" required>
                        </div>
                        <div class="mb-3">
                            <label for="salary" class="form-label">Оклад</label>
                            <input type="number" class="form-control" id="salary" name="salary" required>
                        </div>
                        <div class="mb-3">
                            <label for="employmentDate" class="form-label">Дата приема на работу</label>
                            <input type="date" class="form-control" id="employmentDate" name="employment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="contract" class="form-label">Трудовой договор</label>
                            <input type="file" class="form-control" id="contract" name="contract">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>





    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Удаление backdrop вручную, если он не исчезает
            document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (closeButton) {
                closeButton.addEventListener('click', function () {
                    const modalBackdrop = document.querySelector('.modal-backdrop');
                    if (modalBackdrop) {
                        modalBackdrop.remove();
                    }
                    document.body.classList.remove('modal-open'); // Убираем класс modal-open
                    document.body.style = ''; // Убираем стили, которые остаются
                });
            });
        });


        var lastId = null;
        function updateUser(data) {
            $("#preloader").show();
            document.getElementById('employeeId').value = data.id;
            document.getElementById('employeeName').value = data.fio;

            if (data.berthday) {
                let birthDate = data.berthday.split(' ')[0];
                document.getElementById('birthDate').value = birthDate;
            } else {
                document.getElementById('birthDate').value = "";
            }

            document.getElementById('branch').value = data.franchaise;
            document.getElementById('phone').value = data.phone_call;
            document.getElementById('whatsapp').value = data.phone_whatsapp;
            document.getElementById('email').value = data.email;
            document.getElementById('isAdmin').value = data.type;
            document.getElementById('worksTours').value = data.work_for_tours;
            document.getElementById('specialOffers').value = data.show_spec;
            document.getElementById('commission').value = data.percentage_of_commisiion;
            document.getElementById('salary').value = data.oklad;

            if (data.date_start_work) {
                let employmentDate = data.date_start_work.split(' ')[0];
                document.getElementById('employmentDate').value = employmentDate;
            } else {
                document.getElementById('employmentDate').value = "";
            }

            $("#preloader").hide();
            const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
            editModal.show();
        }


        function fireEmployee(employeeId) {
            if (!confirm('Вы уверены, что хотите уволить этого сотрудника?')) return;
            $("#preloader").show();
            $.ajax({
                url: 'index.php?page=personal',
                method: 'POST',
                data: {
                    method: 'fireEmployee',
                    id: employeeId
                },
                dataType: 'json',
                success: function (response) {
                    $("#preloader").hide();
                    if (response.success) {
                        alert(response.message);
                        $('#activeEmployeeTableBody').load(location.href + ' #activeEmployeeTableBody>*', '');
                        $('#inactiveEmployeeTableBody').load(location.href + ' #inactiveEmployeeTableBody>*', '');
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }

                },
                error: function () {
                    alert('Ошибка при выполнении запроса.');
                    $("#preloader").hide();
                }
            });
        }

        function fireEmployee2(employeeId) {
            if (!confirm('Вы уверены, что хотите вернуть этого сотрудника?')) return;
            $("#preloader").show();
            $.ajax({
                url: 'index.php?page=personal',
                method: 'POST',
                data: {
                    method: 'fireEmployee2',
                    id: employeeId
                },
                dataType: 'json',
                success: function (response) {
                    $("#preloader").hide();
                    if (response.success) {
                        alert(response.message);
                        $('#activeEmployeeTableBody').load(location.href + ' #activeEmployeeTableBody>*', '');
                        $('#inactiveEmployeeTableBody').load(location.href + ' #inactiveEmployeeTableBody>*', '');
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }

                },
                error: function () {
                    alert('Ошибка при выполнении запроса.');
                    $("#preloader").hide();
                }
            });
        }

        function sendPassword(employeeId) {
            if (!confirm('Вы уверены, что хотите отправить новый пароль этому сотруднику?')) return;
            $("#preloader").show();

            $.ajax({
                url: 'index.php?page=personal',
                method: 'POST',
                data: {
                    operation: 'sendPassword',
                    id: employeeId
                },
                dataType: 'json',
                success: function (response) {
                    $("#preloader").hide();
                    if (response.success) { // Проверка на success
                        alert('Пароль успешно отправлен сотруднику.');
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    $("#preloader").hide();
                    alert('Ошибка при выполнении запроса: ' + error);
                }
            });
        }
    </script>
</body>

</html>