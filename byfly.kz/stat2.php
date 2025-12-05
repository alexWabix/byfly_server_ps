<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$countUser = $db->query("SELECT COUNT(*) as ct FROM users")->fetch_assoc()['ct'];
$countAgents = $db->query("SELECT COUNT(*) as ct FROM users WHERE user_status != 'user' AND orient != 'test'")->fetch_assoc()['ct'];

$countTours = $db->query("SELECT COUNT(*) as ct FROM order_tours WHERE status_code > 0")->fetch_assoc()['ct'];
$countFranchaise = $db->query("SELECT COUNT(*) as ct FROM franchaise")->fetch_assoc()['ct'];


function getTop10Leaders($db)
{
    function getSubordinates($db, $userId, $maxLevel)
    {
        $teamCount = 0;
        $queue = [[$userId, 1]];
        $visited = [];

        while (!empty($queue)) {
            list($currentUserId, $currentLevel) = array_shift($queue);

            if ($currentLevel <= $maxLevel) {
                $query = "SELECT id FROM users WHERE parent_user = " . intval($currentUserId) . " AND id NOT IN (2, 22)";
                $result = $db->query($query);

                if ($result !== false) {
                    while ($row = $result->fetch_assoc()) {
                        if (!in_array($row['id'], $visited)) {
                            $visited[] = $row['id'];
                            $teamCount++;
                            $queue[] = [$row['id'], $currentLevel + 1];
                        }
                    }
                }
            }
        }

        return $teamCount;
    }

    $query = "SELECT id, name, famale, user_status, avatar, promo_code FROM users WHERE user_status IN ('agent', 'couch', 'alpha') AND id NOT IN (22)";
    $result = $db->query($query);

    if ($result === false) {
        echo "Error: " . $db->error;
        return [];
    }

    $leaders = [];
    while ($row = $result->fetch_assoc()) {
        $maxLevel = 2; // Для статуса agent
        if ($row['user_status'] === 'couch') {
            $maxLevel = 4;
        } elseif ($row['user_status'] === 'alpha') {
            $maxLevel = 5;
        }

        $teamSize = getSubordinates($db, $row['id'], $maxLevel);
        $leaders[] = [
            'id' => $row['id'],
            'name' => $row['name'] . ' ' . mb_substr($row['famale'], 0, 1) . '. (' . $row['promo_code'] . ')',
            'status' => $row['user_status'],
            'team_size' => $teamSize,
            'ava' => $row['avatar'],
        ];
    }
    usort($leaders, function ($a, $b) {
        return $b['team_size'] - $a['team_size'];
    });

    return array_slice($leaders, 0, 10);
}

$topLeaders = getTop10Leaders($db);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Показатель ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://code.ionicframework.com/ionicons/1.5.2/css/ionicons.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="15;url=https://byfly.kz/stat3.php">
    <style>
        .container-fluid {
            height: 100vh;
            background-color: black;
        }

        body {
            background-color: black;
            overflow: hidden;
        }

        .cell-1 {
            background: linear-gradient(135deg, #b30000, #660000);
        }

        .cell-2 {
            background: linear-gradient(135deg, #a10000, #4d0000);
        }

        .cell-3 {
            background: linear-gradient(135deg, #8b0000, #400000);
        }

        .cell-4 {
            background: linear-gradient(135deg, #990033, #660033);
        }

        .cell-5 {
            background: linear-gradient(135deg, #b3003b, #730029);
        }

        .cell-6 {
            background: linear-gradient(135deg, #cc0000, #7a0000);
        }

        .cell-7 {
            background: linear-gradient(135deg, #b32424, #661414);
        }

        .cell-8 {
            background: linear-gradient(135deg, #FFD700, #DAA520, #B8860B, #FFD700);
        }

        .col-md-6,
        .col-md-12 {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding: 0px;
            text-align: center;
            margin: 10px;
            border-radius: 5px;
        }

        .row {
            height: 100%;
        }

        i {
            color: white;
            font-size: 90px;
        }

        span {
            font-size: 70px;
            line-height: 1;
        }

        .avatar {
            width: 200px;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: white;
            border-radius: 100%;
        }

        .avatar2 {
            width: 110px;
            height: 110px;
            background-size: cover;
            background-position: center;
            background-color: white;
            border-radius: 100%;
        }

        .avatar-overlay {
            width: 215px;
            border-radius: 100%;
            margin: auto;
            margin-top: 30px;
            margin-bottom: 30px;
            padding: 5px;
            border: dotted 2px white;
        }

        .avatar-overlay2 {
            border-radius: 100%;
            width: 120px;
            height: 120px;
            padding: 3px;
            border: dotted 2px white;
        }

        .userTopName {
            font-size: 40px;
            border-bottom: dotted 2px white;
            padding-bottom: 20px;
            line-height: 2.1;
        }

        p {
            font-size: 20px;
            line-height: 0;
        }

        .countUser {
            font-size: 40px;
            color: #39b900;
        }

        .loader {
            width: 900px;
            height: 900px;
            border-radius: 50%;
            position: relative;
            animation: rotate 1s linear infinite
        }

        .loader::before,
        .loader::after {
            content: "";
            box-sizing: border-box;
            position: absolute;
            inset: 0px;
            border-radius: 50%;
            border: 25px solid #FFF;
            animation: prixClipFix 2s linear infinite;
        }

        .loader::after {
            inset: 28px;
            transform: rotate3d(90, 90, 0, 180deg);
            border-color: #FF3D00;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg)
            }

            100% {
                transform: rotate(360deg)
            }
        }

        @keyframes prixClipFix {
            0% {
                clip-path: polygon(50% 50%, 0 0, 0 0, 0 0, 0 0, 0 0)
            }

            50% {
                clip-path: polygon(50% 50%, 0 0, 100% 0, 100% 0, 100% 0, 100% 0)
            }

            75%,
            100% {
                clip-path: polygon(50% 50%, 0 0, 100% 0, 100% 100%, 100% 100%, 100% 100%)
            }
        }
    </style>
</head>

<body style="text-align: center;">
    <div id="spinner" style="width: 100%; height: 100%; padding-top: 500px;">
        <span id="spinner" class="loader"></span>
    </div>
    <div id="loading" style="display:none;" class="container-fluid">
        <div class="row h-100">
            <div class="col-md-3 d-flex flex-column justify-content-between">
                <div class="col-md-12 cell-2">
                    <div style="width: 100%;">
                        <div class="row" style="width: 100%;">
                            <div class="col-lg-auto" style="text-align: right; padding-left: 40px;">
                                <i class="ion-speakerphone"></i><br>
                            </div>
                            <div class="col-lg" style="text-align: left;">
                                <span style="margin-top: 30px; font-size: 40px;">Пользователей</span><br>
                                <span style="font-size: 130px;"><?= $countUser ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 cell-2">
                    <div style="width: 100%;">
                        <div class="row" style="width: 100%;">
                            <div class="col-lg-auto" style="text-align: right; padding-left: 40px;">
                                <i class="ion-checkmark-round"></i>
                            </div>
                            <div class="col-lg" style="text-align: left;">
                                <span style="margin-top: 30px; font-size: 40px;">Агентов</span><br>
                                <span style="font-size: 130px;"><?= $countAgents ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 cell-2">
                    <div style="width: 100%;">
                        <div class="row" style="width: 100%;">
                            <div class="col-lg-auto" style="text-align: right; padding-left: 40px;">
                                <i class="ion-plane"></i>
                            </div>
                            <div class="col-lg" style="text-align: left;">
                                <span style="margin-top: 30px; font-size: 40px;">Продано туров</span><br>
                                <span style="font-size: 130px;"><?= $countTours ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 cell-2">
                    <div style="width: 100%;">
                        <div class="row" style="width: 100%;">
                            <div class="col-lg-auto" style="text-align: right; padding-left: 40px;">
                                <i class="ion-location"></i>
                            </div>
                            <div class="col-lg" style="text-align: left;">
                                <span style="margin-top: 30px; font-size: 40px;">Франшизы</span><br>
                                <span style="font-size: 130px;"><?= $countFranchaise ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9 d-flex flex-column justify-content-between">
                <div class="col-md-12 cell-8">
                    <div style="text-align: center; width: 100%;">
                        <div class="row" style="width: 100%;">
                            <div class="col-lg-3" style="text-align: right;">
                                <div class="avatar-overlay">
                                    <div style="background-image: url('<?= mb_strlen($topLeaders[0]['ava'], 'utf-8') > 0 ? $topLeaders[0]['ava'] : 'https://api.v.2.byfly.kz/images/no-ava.png' ?>')"
                                        class="avatar">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9" style="text-align: left; font-size: 45px; padding-top: 30px;">
                                <span style="font-size: 75px;"><?= $topLeaders[0]['name'] ?></span><br>
                                <span style="font-size: 55px; font-style: italic; color: #444444;">Статус -
                                    <?= $topLeaders[0]['status'] ?></span><br>
                                <span style="font-size: 55px;">Команда <?= $topLeaders[0]['team_size'] ?>
                                    чел.</span><br>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 cell-9">
                    <div class="row">
                        <?php
                        $count = 0;
                        foreach ($topLeaders as $user) {
                            if ($count > 0 && $count < 7) {
                                echo '<div class="col-lg-6">
                                    <div style="width: 100%; margin: 30px;">
                                        <div style="margin-top: 30px; width: 100%;" class="row">
                                            <div style="text-align: left;" class="col">
                                                <span class="userTopName">#' . $count . ' - ' . $user['name'] . '</span><br><br>
                                                <span class="countUser">Команда ' . $user['team_size'] . ' чел.</span>
                                            </div>
                                        </div>
                                    </div> 
                                </div>';
                            }
                            $count++;

                        }
                        ?>



                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script type="text/javaScript">
            window.onload = function(){
                $("#loading").show();
                $("#spinner").hide();
            }
        </script>
</body>

</html>