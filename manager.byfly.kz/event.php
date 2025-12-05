<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
ignore_user_abort(true);
set_time_limit(0);

$listOrganizators = array(
    "77780021666",
    "77777080808",
    "77014265987",
    "77771101777",
    "77085194866",
    "77084340334",
    "77052019563",
    "77021122545",
    "77021511372",
    "77079010041",
    "77025089335",
    "77773700772",
);

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'byfly' || $_SERVER['PHP_AUTH_PW'] !== '2024') {
    header('WWW-Authenticate: Basic realm="ByFly"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';
    $count = $_POST['count'] ?? null;
    $response = [];

    header('Content-Type: application/json');


    switch ($method) {
        case 'eventStarted':
            foreach ($listOrganizators as $listOrganizators) {
                sendWhatsapp(
                    $listOrganizators,
                    "Уважаемые партнеры, друзья и коллеги! ✨\n\n" .
                    "С радостью сообщаем, что наш долгожданный ГАЛА-ужин уже начался! 🎉\n\n" .
                    "🌟 На вас ждут незабываемые сюрпризы, а также множество потрясающих розыгрышей. " .
                    "Не упустите возможность стать частью этого грандиозного события!\n\n" .
                    "Присоединяйтесь к эфиру прямо сейчас: \nhttps://us06web.zoom.us/j/85199598406?pwd=ZU5QhQ9VOeEanKg353C0bVQ6WoS3Yb.1\n\n" .
                    "Будьте с нами, чтобы не пропустить ни одной важной детали! 🔥"
                );
                sleep(2);
            }
            break;

        case 'connectAll':
            foreach ($listOrganizators as $listOrganizators) {
                sendWhatsapp(
                    $listOrganizators,
                    "Уважаемые партнеры!\n\n" .
                    "Срочно подключайтесь к эфиру, где прямо сейчас транслируется важнейшая информация для всех участников! 📣\n\n" .
                    "Это ваша возможность узнать ключевые детали и принять участие в обсуждении. " .
                    "Не пропустите! Подключиться можно по ссылке: \nhttps://us06web.zoom.us/j/85199598406?pwd=ZU5QhQ9VOeEanKg353C0bVQ6WoS3Yb.1"
                );
                sleep(2);
            }
            break;

        case 'callAgents':
            $listAgentsDB = $db->query("SELECT * FROM new_year WHERE is_agent='1' AND is_pay='1'");
            while ($listAgents = $listAgentsDB->fetch_assoc()) {
                sendWhatsapp(
                    preg_replace('/\D/', '', $listAgents['phone']),
                    "Уважаемые агенты!\n\n" .
                    "Приглашаем вас к эфиру, где транслируются важные объявления и уникальные предложения для участников. " .
                    "Пожалуйста, подключайтесь по ссылке: \nhttps://us06web.zoom.us/j/85199598406?pwd=ZU5QhQ9VOeEanKg353C0bVQ6WoS3Yb.1\n\n" .
                    "Будьте внимательны, чтобы не упустить ни одной важной детали!"
                );
                sleep(2);
            }
            foreach ($listOrganizators as $listOrganizators) {
                sendWhatsapp(
                    $listOrganizators,
                    "Уважаемые организаторы!\n\n" .
                    "Все агенты приглашены к эфиру. Убедитесь, что они подключились, и следите за трансляцией для координации дальнейших действий."
                );
                sleep(2);
            }
            break;

        case 'raffleStarted':
            foreach ($listOrganizators as $listOrganizators) {
                sendWhatsapp(
                    $listOrganizators,
                    "Уважаемые партнеры!\n\n" .
                    "С радостью сообщаем, что розыгрыш официально начался! 🏆\n\n" .
                    "Следите за трансляцией, чтобы быть в курсе всех событий и узнать, кто станет победителем. " .
                    "Эфир доступен по ссылке: \nhttps://us06web.zoom.us/j/85199598406?pwd=ZU5QhQ9VOeEanKg353C0bVQ6WoS3Yb.1"
                );
                sleep(2);
            }
            break;

        case 'eveningEnded':
            foreach ($listOrganizators as $listOrganizators) {
                sendWhatsapp(
                    $listOrganizators,
                    "Уважаемые партнеры!\n\n" .
                    "Наш вечер подошел к концу. Спасибо каждому из вас за участие, поддержку и активность! 🙏\n\n" .
                    "Мы надеемся, что этот вечер был для вас таким же вдохновляющим и незабываемым, как и для нас. " .
                    "До новых встреч и новых успехов вместе с ByFly! 🚀"
                );
                sleep(2);
            }
            break;
        default:
            // Неизвестный метод
            break;
    }

    echo json_encode(['status' => 'success', 'message' => 'Рассылка началась']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кнопки действий</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <style>
        .button-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .button-grid button {
            border-radius: 5px;
            font-size: 1.2rem;
            padding: 15px;
            color: white;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .button-grid button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #6610f2);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #343a40);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
        }

        .btn-dark {
            background: linear-gradient(135deg, #343a40, #495057);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #bd2130);
        }

        .input-group {
            margin-bottom: 10px;
        }

        #preloader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>

<body>

    <div id="preloader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container py-5">
        <h1 class="text-center mb-4">Кнопки действий</h1>
        <?php
        $countAgents = $db->query("SELECT COUNT(*) as ct FROM new_year WHERE is_agent = '1' AND go = '1'")->fetch_assoc()['ct'];
        $countUsers = $db->query("SELECT COUNT(*) as ct FROM new_year WHERE go = '1'")->fetch_assoc()['ct'];
        ?>
        <h2 class="text-center mb-4">Учавствует агентов: <?= $countAgents ?></h2>
        <h2 class="text-center mb-4">Учавствует пользователей: <?= $countUsers ?></h2>
        <div class="button-grid">
            <button class="btn btn-primary" data-method="eventStarted">Мероприятие началось</button>
            <button class="btn btn-warning" data-method="connectAll">Подключить ВСЕХ</button>



            <button class="btn btn-danger" data-method="raffleStarted">Начался розыгрыш</button>
            <button class="btn btn-danger" data-method="eveningEnded">Завершение вечера</button>
            <button class="btn btn-dark" data-method="callAgents">Позвать всех агентов</button>


        </div>
        <div class="mt-5">
            <a href="egypt.php" target="_blank" class="btn btn-success">Разыграть Египет</a>
            <a href="tyland.php" target="_blank" class="btn btn-success">Разыграть Тайланд</a>
            <a href="maldives.php" target="_blank" class="btn btn-success">Разыграть Мальдивы</a>
            <a href="learning.php" target="_blank" class="btn btn-success">Разыграть обучение</a>
            <a href="random_user.php" target="_blank" class="btn btn-dark">Дать слово рандомному участнику</a>

            <div class="mt-3">
                <form action="selectedAlmaty.php" method="GET" target="_blank">
                    <div class="input-group">
                        <input name="count" type="number" class="form-control" placeholder="Кол-во участников" min="1"
                            value="10">
                        <button type="submit" class="btn btn-info text-light">
                            Выбрать участников Алматы
                        </button>
                    </div>
                </form>
            </div>

            <!-- Форма для выбора участников (Все города) -->
            <div>
                <form action="selected.php" method="GET" target="_blank">
                    <div class="input-group">
                        <input name="count" type="number" class="form-control" placeholder="Кол-во участников" min="1"
                            value="10">
                        <button type="submit" class="btn btn-info text-light">
                            Выбрать участников (Все города)
                        </button>
                    </div>
                </form>
            </div>

            <!-- Форма для выбора участников (Все города) -->
            <div>
                <form action="selectedAgents.php" method="GET" target="_blank">
                    <div class="input-group">
                        <input name="count" type="number" class="form-control" placeholder="Кол-во участников" min="1"
                            value="10">
                        <button type="submit" class="btn btn-info text-light">
                            Выбрать агентов (Все города)
                        </button>
                    </div>
                </form>
            </div>
            <div>
                <form action="selectedAgentsAlmaty.php" method="GET" target="_blank">
                    <div class="input-group">
                        <input name="count" type="number" class="form-control" placeholder="Кол-во участников" min="1"
                            value="10">
                        <button type="submit" class="btn btn-info text-light">
                            Выбрать агентов (Алматы)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $(".button-grid button:not(#randomParticipants)").on("click", function () {
                var method = $(this).data("method");

                $("#preloader").css("display", "flex");

                $.post("", { method: method })
                    .done(function (response) {
                        alert(response.message);
                    })
                    .fail(function (err) {
                        alert("Ошибка выполнения запроса." + JSON.stringify(err));
                    })
                    .always(function () {
                        $("#preloader").fadeOut();
                    });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>