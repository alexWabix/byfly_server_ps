<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if ($_POST['type'] == 'getCode') {
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    $fio = $_POST['fio'];
    $searchPhoneDB = $db->query("SELECT * FROM user_test_start_coach WHERE phone='" . $phone . "' OR iin='" . $_POST['iin'] . "'");
    if ($searchPhoneDB->num_rows > 0) {
        $searchPhone = $searchPhoneDB->fetch_assoc();
        if ($searchPhone['atestation_bal'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Пользователь уже совершал попытку прохождения собеседования.']);
            exit();
        } else {
            $code = random_int(100000, 999999);
            $wa = sendWhatsapp($phone, "$code Вы проходите собеседование на участие в агентской сети ByFly Travel. 🌟 Вы должны понимать, что в данном случае происходит работа с клиентом, и сервис превыше всего. 📋 Данный тест разработан для того, чтобы в первую очередь вы могли оценить себя и понять, получится ли у вас работать в нашей компании.");
            echo json_encode(['success' => true, 'code' => $code, 'test' => $wa, 'id' => $searchPhone['id']]);
            exit();
        }
    } else {
        $db->query("INSERT INTO `user_test_start_coach` (`id`, `date_create`, `iin`, `phone`, `fio`, `atestation_bal`) VALUES (NULL, CURRENT_TIMESTAMP, '" . $_POST['iin'] . "', '" . $phone . "', '" . $fio . "', '0');");
        $code = random_int(100000, 999999);
        $wa = sendWhatsapp($phone, "$code Вы проходите собеседование на участие в агентской сети ByFly Travel. 🌟 Вы должны понимать, что в данном случае происходит работа с клиентом, и сервис превыше всего. 📋 Данный тест разработан для того, чтобы в первую очередь вы могли оценить себя и понять, получится ли у вас работать в нашей компании.");
        echo json_encode(['success' => true, 'code' => $code, 'test' => $wa, 'id' => $db->insert_id]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Собеседование на участие в агентской программе ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-mask-plugin/dist/jquery.mask.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 400px;
            margin: 100px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="text-center mb-4">Введите данные для проверки</h2>
        <form id="phoneForm">
            <div class="mb-3">
                <label for="fio" class="form-label">ФИО</label>
                <input type="text" class="form-control" id="fio" placeholder="Введите ваше ФИО" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Телефон</label>
                <input type="text" class="form-control" id="phone" placeholder="+7 777 777 77 77" required>
            </div>
            <div class="mb-3">
                <label for="iin" class="form-label">ИИН</label>
                <input type="text" class="form-control" id="iin" maxlength="12" placeholder="Введите ваш ИИН" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Получить код</button>
        </form>

        <form id="codeForm" class="hidden">
            <div class="mb-3">
                <label for="code" class="form-label">Введите код</label>
                <input type="text" class="form-control" id="code" maxlength="6" placeholder="Код из SMS" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Подтвердить</button>
        </form>

        <div id="errorMessage" class="alert alert-danger hidden mt-3" role="alert">
            Неверный код. Попробуйте снова.
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Маска для ввода телефона
            $('#phone').mask('+7 000 000 00 00');

            let verificationCode = null; // Хранение кода
            let id = null;

            // Обработка отправки формы с данными
            $('#phoneForm').on('submit', function (e) {
                e.preventDefault();
                const fio = $('#fio').val();
                const phone = $('#phone').val();
                const iin = $('#iin').val();

                $.ajax({
                    url: 'https://manager.byfly.kz/start_test.php',
                    type: 'POST',
                    data: { fio: fio, phone: phone, iin: iin, type: 'getCode' },
                    success: function (response) {
                        var resp = JSON.parse(response);
                        if (resp.success) {
                            verificationCode = resp.code;
                            id = resp.id;
                            $('#phoneForm').hide();
                            $('#codeForm').removeClass('hidden');
                        } else {
                            alert(resp.message);
                        }
                    },
                    error: function () {
                        alert('Ошибка при отправке кода. Попробуйте снова.');
                    }
                });
            });

            // Обработка отправки формы с кодом
            $('#codeForm').on('submit', function (e) {
                e.preventDefault();
                const enteredCode = $('#code').val();

                if (parseInt(enteredCode) === parseInt(verificationCode)) {
                    window.location.href = 'https://manager.byfly.kz/agent.php?id=' + id;
                } else {
                    $('#errorMessage').removeClass('hidden');
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>