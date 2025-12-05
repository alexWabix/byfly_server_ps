<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if ($_POST['type'] == 'result') {
    $info = $db->query("SELECT * FROM user_test_start_coach WHERE id='" . $_POST['id'] . "'")->fetch_assoc();
    if ($_POST['ball'] >= 70) {
        $wa = sendWhatsapp("77773700772", "Пользователь прошел собеседование для обучения агента: \n\n Телефон: " . $info['phone'] . "\nИИН: " . $info['iin'] . "\nФИО: " . $info['fio'] . "\nНабранный бал: " . $_POST['ball']);
    }

    $db->query("UPDATE user_test_start_coach SET atestation_bal='" . $_POST['ball'] . "' WHERE id='" . $_POST['id'] . "'");
    echo json_encode(["success" => true]);
    exit();
}



$errorMessage = "";

// Проверка наличия параметра id
if (empty($_GET['id'])) {
    $errorMessage = "<div class='alert alert-danger text-center'>Нет доступа к тестированию: отсутствует идентификатор теста.</div>";
} else {
    // Получение информации о тесте
    $id = $db->real_escape_string($_GET['id']);
    $infoTestDB = $db->query("SELECT * FROM user_test_start_coach WHERE id='$id'");

    if ($infoTestDB->num_rows === 0) {
        $errorMessage = "<div class='alert alert-danger text-center'>Нет доступа к тестированию: тест с указанным идентификатором не найден.</div>";
    } else {
        $infoTest = $infoTestDB->fetch_assoc();

        // Проверка, сдавал ли пользователь тест
        if ($infoTest['atestation_bal'] > 0) {
            if ($infoTest['atestation_bal'] > 70) {
                $errorMessage = "<div class='alert alert-success text-center'>Вы уже сдавали тест и набрали <strong>{$infoTest['atestation_bal']}</strong> баллов.<br>Вы можете пройти обучение и подходите на роль агента, ваши данные отправленны в обучающий центр ByFly Coach Center.</div>";
            } else {
                $errorMessage = "<div class='alert alert-danger text-center'>Вы уже сдавали тест и набрали <strong>{$infoTest['atestation_bal']}</strong> баллов.<br>Вы не можете работать агентом так как ваша квалификация не соответствует.</div>";
            }

        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Допуск к обучению ByFly Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }

        #loader.active {
            display: flex;
        }

        #loader .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        #testForm {
            display: block;
        }

        #testForm.hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div id="loader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
    </div>

    <div class="container">
        <h1 class="text-center mb-4">Допуск к обучению ByFly Travel</h1>

        <?php
        // Вывод сообщения об ошибке
        if (!empty($errorMessage)) {
            echo $errorMessage;
        } else {
            // Форма теста, если ошибок нет
            echo "<form id='testForm'>";

            $listQueryDB = $db->query("
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Гибкость' ORDER BY RAND() LIMIT 1
                ) AS category1
                UNION ALL
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Мотивация к обучению' ORDER BY RAND() LIMIT 1
                ) AS category3
                UNION ALL
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Навыки обучения' ORDER BY RAND() LIMIT 1
                ) AS category4
                UNION ALL
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Психологическая устойчивость' ORDER BY RAND() LIMIT 5
                ) AS category5
                UNION ALL
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Использование приложений' ORDER BY RAND() LIMIT 1
                ) AS category2
                UNION ALL
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Реакция на проблемы' ORDER BY RAND() LIMIT 4
                ) AS category6
                UNION ALL
                SELECT * FROM (
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Стрессоустойчивость' ORDER BY RAND() LIMIT 4
                ) AS category7
                UNION ALL
                SELECT * FROM ( 
                    SELECT * FROM `byfly.2.0`.`to_agents_start_test_quest` WHERE category = 'Техническая грамотность' ORDER BY RAND() LIMIT 3
                ) AS category8
                LIMIT 20
            ");

            $questions = $listQueryDB->fetch_all(MYSQLI_ASSOC);
            $questionNumber = 1;

            foreach ($questions as $question) {
                echo "<div class='mb-4'>";
                echo "<h5>{$questionNumber}. {$question['query']}</h5>";
                echo "<div class='form-check'>";
                echo "<input class='form-check-input' type='radio' name='q{$questionNumber}' value='{$question['answer1_bal']}' required>";
                echo "<label class='form-check-label'>{$question['answer1']}</label>";
                echo "</div>";
                echo "<div class='form-check'>";
                echo "<input class='form-check-input' type='radio' name='q{$questionNumber}' value='{$question['answer2_bal']}' required>";
                echo "<label class='form-check-label'>{$question['answer2']}</label>";
                echo "</div>";
                echo "<div class='form-check'>";
                echo "<input class='form-check-input' type='radio' name='q{$questionNumber}' value='{$question['answer3_bal']}' required>";
                echo "<label class='form-check-label'>{$question['answer3']}</label>";
                echo "</div>";
                echo "</div>";
                $questionNumber++;
            }

            echo "<button type='submit' class='btn btn-danger w-100'>Получить результат</button>";
            echo "</form>";
        }
        ?>

        <div id="result" class="mt-4" style="display: none;">
            <h3 class="text-center">Ваш результат:</h3>
            <p class="text-center" id="scoreText"></p>
            <p class="text-center fw-bold" id="passFail"></p>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#testForm').on('submit', function (event) {
                event.preventDefault();

                let totalScore = 0;
                $(this).serializeArray().forEach(field => {
                    totalScore += parseInt(field.value);
                });

                $('#loader').addClass('active');
                $('#testForm').addClass('hidden');

                $.ajax({
                    url: 'https://manager.byfly.kz/agent.php',
                    type: 'POST',
                    data: {
                        id: "<?= $_GET['id']; ?>",
                        type: 'result',
                        ball: totalScore
                    },
                    success: function (response) {
                        $('#loader').removeClass('active');
                        const data = JSON.parse(response);

                        if (data.success) {
                            $('#scoreText').text(`Вы набрали: ${totalScore} баллов.`);
                            if (totalScore >= 70) {
                                $('#passFail').text('Вы можете пройти обучение на агента компании ByFly Travel.').addClass('text-success').removeClass('text-danger');
                            } else {
                                $('#passFail').text('Сожалеем! Вы не подходите на роль агента в системе ByFly Travel.').addClass('text-danger').removeClass('text-success');
                            }
                            $('#result').show(300);
                        } else {
                            $('#testForm').removeClass('hidden');
                            alert(data.message);
                        }
                    },
                    error: function () {
                        $('#loader').removeClass('active');
                        $('#testForm').removeClass('hidden');
                        alert('Ошибка при отправке данных. Попробуйте снова.');
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>