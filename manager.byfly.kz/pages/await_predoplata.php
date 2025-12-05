<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(to right, #ae011a, #4a000b);
            padding-left: 20px;
            padding-right: 20px;
        }

        .navbar-brand {
            margin-right: auto;
        }

        .nav-link,
        .dropdown-item {
            color: white;
            margin-right: 15px;
            text-decoration: none;
        }

        .nav-link.active {
            color: red !important;
        }

        .nav-link {
            color: black;
            border-radius: 5px;
        }

        .content {
            margin-top: 20px;
        }

        .badge {
            background-color: red;
        }
    </style>
</head>

<body>
    <?php
    $count1 = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code='0'")->fetch_assoc()['ct'];
    $count2 = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code='1'")->fetch_assoc()['ct'];
    $count3 = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code='3'")->fetch_assoc()['ct'];
    $count4 = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code='4'")->fetch_assoc()['ct'];
    $count5 = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code='5'")->fetch_assoc()['ct'];
    $count6 = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE manager_id ='" . $userInfo['id'] . "' AND status_code='6'")->fetch_assoc()['ct'];

    $statuses = [
        'home' => ['label' => 'Новые', 'badge' => $count1],
        'await_predoplata' => ['label' => 'Ожидают предоплату', 'badge' => $count2],
        'await_pay' => ['label' => 'Ожидают оплату', 'badge' => $count3],
        'await_fly' => ['label' => 'Ожидает вылета', 'badge' => $count4],
        'in_tours' => ['label' => 'На отдыхе', 'badge' => $count5],
        'cancle_tours' => ['label' => 'Заявка отменена', 'badge' => $count6],
    ];


    $currentPage = $_GET['page'] ?? 'await_predoplata';
    ?>

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
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <?php foreach ($statuses as $page => $info): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentPage === $page ? 'active' : '' ?>" href="?page=<?= $page ?>">
                        <?= $info['label'] ?> <span class="badge"><?= $info['badge'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content mt-4">
            <div class="tab-pane fade show active">
                <?php
                $allowedPages = array_keys($statuses);
                include("modules/orders/await_predoplata.php");
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>

</html>