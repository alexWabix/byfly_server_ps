<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отели Умра</title>
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

        .task-list {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .task-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .task-item.completed {
            background-color: #d4edda;
            text-decoration: line-through;
            color: #6c757d;
        }

        .task-item .form-check-input {
            width: 25px;
            height: 25px;
        }

        .task-item-text {
            flex-grow: 1;
            margin-left: 15px;
        }

        .task-item-actions {
            display: flex;
            gap: 10px;
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

        /* Прелоадер */
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
    </style>
</head>

<body>
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
        <table class="table">
            <thead>
                <th>ID</th>
                <th>Название отеля</th>
                <th style="text-align: center;">Фотографии</th>
                <th style="text-align: center;">Типы номеров</th>
                <th style="text-align: center;">Описание</th>
                <th></th>
            </thead>
            <tbody>
                <?php
                $listHotelsDB = $db->query("SELECT * FROM mekka_list_hotels");
                while ($listHotels = $listHotelsDB->fetch_assoc()) {
                    $listHotels['count_image'] = $db->query("SELECT COUNT(*) as ct FROM mekka_hotel_image WHERE hotel_id='" . $listHotels['id'] . "'")->fetch_assoc()['ct'];
                    $listHotels['count_rooms'] = $db->query("SELECT COUNT(*) as ct FROM mekka_hotel_rooms WHERE hotel_id='" . $listHotels['id'] . "'")->fetch_assoc()['ct'];
                    $listHotels['description'] = mb_strlen($listHotels['description'], 'utf-8') > 0 ? 1 : 0;
                    echo '<tr>
                            <td>' . $listHotels['id'] . '</td>
                            <td>' . $listHotels['name_hotel'] . '</td>
                            <td style="text-align: center;">' . $listHotels['count_image'] . '</td>
                            <td style="text-align: center;">' . $listHotels['count_rooms'] . '</td>
                            <td style="text-align: center;">' . $listHotels['description'] . '</td>
                            <td style="text-align: center;">
                                <a href="index.php?page=update_hotel&id=' . $listHotels['id'] . '" target="_blank" class="btn btn-success w-100 btn-sm">Редактировать</a>
                            </td>
                        </tr>';
                }
                ?>
                <tr></tr>
            </tbody>
        </table>
    </div>


    <!-- Прелоадер -->
    <div id="preloader">
        <div class="spinner-border text-light" role="status"></div>
    </div>

    <!-- Модальное окно для добавления задачи -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addTaskForm" method="POST">
                    <input hidden name="operation" value="addCrm">
                    <input hidden name="myId" value="<?= $userInfo['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTaskModalLabel">Добавить задачу</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <?php
                    if ($userInfo['type'] == 0) {
                        echo '<input hidden name="assigned_to" value="manager:' . $userInfo['id'] . '">';
                    }
                    ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Описание задачи</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3"
                                required></textarea>
                        </div>
                        <div <?= $userInfo['type'] == 1 ? '' : 'style="display: none;"' ?> class="mb-3">
                            <label for="taskAssignedTo" class="form-label">Кому назначить</label>
                            <select class="form-select" id="taskAssignedTo" name="assigned_to" required>
                                <?php
                                $groups = [
                                    'Менеджеры' => ['table' => 'managers', 'column' => 'fio', 'type' => 'manager'],
                                    'Преподаватели' => ['table' => 'coach', 'column' => 'name_famale', 'type' => 'coach'],
                                    'Финансисты' => ['table' => 'money_user', 'column' => 'name_famale', 'type' => 'money_man'],
                                ];

                                foreach ($groups as $groupName => $groupInfo) {
                                    echo "<optgroup label=\"$groupName\">";

                                    $query = "SELECT id, {$groupInfo['column']} AS name FROM {$groupInfo['table']} ORDER BY name";
                                    $result = $db->query($query);

                                    if ($result) {
                                        while ($user = $result->fetch_assoc()) {
                                            if ($groupName == 'Менеджеры') {
                                                if ($user['id'] == $userInfo['id']) {
                                                    echo "<option selected value='" . $groupInfo['type'] . ':' . $user['id'] . "'>Задача для себя</option>";
                                                } else {
                                                    echo "<option value='" . $groupInfo['type'] . ':' . $user['id'] . "'>{$user['name']}</option>";
                                                }
                                            } else {
                                                echo "<option value='" . $groupInfo['type'] . ':' . $user['id'] . "'>{$user['name']}</option>";
                                            }

                                        }
                                    }

                                    echo "</optgroup>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="taskDueDate" class="form-label">Крайний срок</label>
                            <input type="datetime-local" class="form-control" id="taskDueDate" name="due_date" required>
                        </div>
                        <input type="hidden" name="created_by" value="<?= $userInfo['id']; ?>">
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
        function showPreloader() {
            $('#preloader').fadeIn();
        }

        function hidePreloader() {
            $('#preloader').fadeOut();
        }

        $(document).on('submit', '#addTaskForm', function (e) {
            e.preventDefault();
            showPreloader();
            $.post('index.php', $(this).serialize(), function (response) {
                hidePreloader();
                location.reload();
            }).fail(function () {
                hidePreloader();
                alert('Ошибка при добавлении задачи.');
            });
        });

        $(document).on('change', '.toggle-completed', function () {
            const taskId = $(this).data('id');
            const isCompleted = $(this).is(':checked');
            showPreloader();
            $.post('index.php', { id: taskId, operation: 'toggleTask', success: isCompleted ? 1 : 0 }, function () {
                hidePreloader();
                location.reload();
            }).fail(function (err) {
                hidePreloader();
                alert('Ошибка при изменении состояния задачи.');
            });
        });

        $(document).on('click', '.delete-task', function () {
            const taskId = $(this).data('id');
            if (confirm('Вы уверены, что хотите удалить задачу?')) {
                showPreloader();
                $.post('index.php', { id: taskId, operation: 'deleteCrm' }, function () {
                    hidePreloader();
                    location.reload();
                }).fail(function () {
                    hidePreloader();
                    alert('Ошибка при удалении задачи.');
                });
            }
        });
    </script>
</body>

</html>