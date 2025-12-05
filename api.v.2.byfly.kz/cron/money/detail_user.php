<?php
// Подключение к базе данных
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

// Проверка соединения
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Обработка AJAX запроса
if (isset($_GET['user_id'])) {
    $userId = $db->real_escape_string($_GET['user_id']);

    // Получаем данные пользователя
    $userQuery = $db->query("SELECT * FROM users WHERE id = '$userId'");
    if ($userQuery->num_rows > 0) {
        $user = $userQuery->fetch_assoc();

        // Получаем сообщения пользователя
        $messagesQuery = $db->query("
            SELECT * FROM send_message_whatsapp 
            WHERE user_id = '$userId' 
            ORDER BY date_create DESC
        ");

        $messages = [];
        while ($row = $messagesQuery->fetch_assoc()) {
            // Извлекаем сумму из сообщения (если есть)
            preg_match('/Сумма:\s*([\d\s]+)\s*₸/u', $row['message'], $matches);
            $amount = isset($matches[1]) ? (int) str_replace(' ', '', $matches[1]) : 0;

            $messages[] = [
                'id' => $row['id'],
                'message' => $row['message'], // Здесь оставляем оригинальные переносы
                'date_create' => $row['date_create'],
                'phone' => $row['phone'],
                'is_send' => $row['is_send'],
                'category' => $row['category'],
                'user_id' => $row['user_id'],
                'amount' => $amount
            ];
        }

        // Формируем ответ
        $response = [
            'user' => [
                'id' => $user['id'],
                'name' => trim($user['famale'] . ' ' . $user['name'] . ' ' . $user['surname']),
                'phone' => $user['phone'],
                'email' => $user['email'] ?? '',
                'balance' => $user['balance'] ?? 0,
                'bonus' => $user['bonus'] ?? 0,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] . '+' . $user['famale']) . '&background=random'
            ],
            'messages' => $messages
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'User not found']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История начислений пользователя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .user-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .message-card {
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .category-bonusevent {
            border-left: 5px solid #28a745;
        }

        .category-nakrutka {
            border-left: 5px solid #007bff;
        }

        .category-tourslines {
            border-left: 5px solid #6610f2;
        }

        .category-education_lines {
            border-left: 5px solid #fd7e14;
        }

        .category-cashback {
            border-left: 5px solid #20c997;
        }

        .chat-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f1f3f5;
            border-radius: 15px;
        }

        .category-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 50px;
        }

        .search-box {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .amount-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 50px;
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #dee2e6;
        }

        .message-text {
            white-space: pre-line;
            /* Это свойство сохраняет переносы строк */
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-box mb-4">
                    <h2 class="text-center mb-4">История начислений</h2>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="userIdInput" placeholder="Введите ID пользователя">
                        <button class="btn btn-primary" id="searchBtn">
                            <i class="bi bi-search"></i> Поиск
                        </button>
                    </div>
                </div>

                <!-- Информация о пользователе -->
                <div class="card user-card mb-4 d-none" id="userInfoCard">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <img id="userAvatar" src="" class="rounded-circle img-thumbnail" alt="User Avatar">
                            </div>
                            <div class="col-md-10">
                                <h3 id="userName"></h3>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><i class="bi bi-telephone"></i> <span id="userPhone"></span></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><i class="bi bi-envelope"></i> <span id="userEmail"></span></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><i class="bi bi-wallet2"></i> Баланс: <span id="userBalance"
                                                class="fw-bold"></span></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><i class="bi bi-wallet2"></i> Бонусы: <span id="userBonus"
                                                class="fw-bold"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Фильтры -->
                <div class="mb-4 d-none" id="filtersSection">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-success filter-btn active" data-category="all">
                            <i class="bi bi-list-ul"></i> Все
                        </button>
                        <button class="btn btn-sm btn-outline-success filter-btn" data-category="bonusevent">
                            <i class="bi bi-gift"></i> Бонусы за мероприятия
                        </button>
                        <button class="btn btn-sm btn-outline-primary filter-btn" data-category="nakrutka">
                            <i class="bi bi-arrow-up-circle"></i> Накрутка
                        </button>
                        <button class="btn btn-sm btn-outline-primary filter-btn" data-category="tourslines">
                            <i class="bi bi-airplane"></i> Линии туров
                        </button>
                        <button class="btn btn-sm btn-outline-warning filter-btn" data-category="education_lines">
                            <i class="bi bi-book"></i> Линии обучения
                        </button>
                        <button class="btn btn-sm btn-outline-info filter-btn" data-category="cashback">
                            <i class="bi bi-arrow-left-right"></i> Кэшбэк
                        </button>
                    </div>
                </div>

                <!-- Чат с сообщениями -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">История операций</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="chat-container" id="messagesContainer">
                            <div class="text-center py-5 text-muted" id="emptyState">
                                <i class="bi bi-chat-square-text" style="font-size: 3rem;"></i>
                                <p class="mt-3">Введите ID пользователя для просмотра истории начислений</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Обработка поиска
            $('#searchBtn').click(function () {
                const userId = $('#userIdInput').val().trim();
                if (userId) {
                    loadUserData(userId);
                } else {
                    alert('Пожалуйста, введите ID пользователя');
                }
            });

            // Обработка нажатия Enter в поле ввода
            $('#userIdInput').keypress(function (e) {
                if (e.which === 13) {
                    $('#searchBtn').click();
                }
            });

            // Обработка фильтров
            $('.filter-btn').click(function () {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                const category = $(this).data('category');
                filterMessages(category);
            });

            // Функция загрузки данных пользователя
            function loadUserData(userId) {
                // Показываем загрузку
                $('#messagesContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                // AJAX запрос к серверу
                $.ajax({
                    url: window.location.href,
                    type: 'GET',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.error) {
                            $('#messagesContainer').html('<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i><p class="mt-3">' + response.error + '</p></div>');
                            return;
                        }

                        // Заполняем информацию о пользователе
                        const user = response.user;
                        $('#userName').text(user.name);
                        $('#userPhone').text(user.phone);
                        $('#userEmail').text(user.email);
                        $('#userBalance').text(user.balance.toLocaleString('ru-RU') + ' ₸');
                        $('#userBonus').text(user.bonus.toLocaleString('ru-RU') + ' ₸');
                        $('#userAvatar').attr('src', user.avatar);
                        $('#userInfoCard').removeClass('d-none');
                        $('#filtersSection').removeClass('d-none');

                        // Отображаем сообщения
                        displayMessages(response.messages);
                    },
                    error: function (xhr, status, error) {
                        $('#messagesContainer').html('<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i><p class="mt-3">Ошибка при загрузке данных</p></div>');
                        console.error(error);
                    }
                });
            }

            // Функция отображения сообщений
            function displayMessages(messages) {
                if (messages.length === 0) {
                    $('#messagesContainer').html('<div class="text-center py-5 text-muted"><i class="bi bi-chat-square-text" style="font-size: 3rem;"></i><p class="mt-3">Нет данных о начислениях для этого пользователя</p></div>');
                    return;
                }

                let html = '';
                messages.forEach(msg => {
                    const date = new Date(msg.date_create);
                    const formattedDate = date.toLocaleString('ru-RU');

                    // Определяем иконку и цвет в зависимости от категории
                    let icon, badgeClass;
                    switch (msg.category) {
                        case 'bonusevent':
                            icon = 'bi-gift';
                            badgeClass = 'bg-success';
                            break;
                        case 'nakrutka':
                            icon = 'bi-arrow-up-circle';
                            badgeClass = 'bg-primary';
                            break;
                        case 'tourslines':
                            icon = 'bi-airplane';
                            badgeClass = 'bg-primary';
                            break;
                        case 'education_lines':
                            icon = 'bi-book';
                            badgeClass = 'bg-warning';
                            break;
                        case 'cashback':
                            icon = 'bi-arrow-left-right';
                            badgeClass = 'bg-info';
                            break;
                        default:
                            icon = 'bi-info-circle';
                            badgeClass = 'bg-secondary';
                    }

                    // Форматируем сумму
                    const amountFormatted = msg.amount ? msg.amount.toLocaleString('ru-RU') + ' ₸' : '0 ₸';

                    // Преобразуем переносы строк в HTML
                    const messageWithBreaks = msg.message.replace(/\n/g, '<br>');

                    html += `
                        <div class="card message-card mb-3 category-${msg.category}" data-category="${msg.category}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge ${badgeClass} category-badge">
                                        <i class="bi ${icon}"></i> ${getCategoryName(msg.category)}
                                    </span>
                                    <small class="text-muted">${formattedDate}</small>
                                </div>
                                <p class="mb-1 message-text">${messageWithBreaks}</p>
                            </div>
                        </div>
                    `;
                });

                $('#messagesContainer').html(html);
            }

            // Функция фильтрации сообщений
            function filterMessages(category) {
                if (category === 'all') {
                    $('.message-card').show();
                } else {
                    $('.message-card').hide();
                    $(`.message-card[data-category="${category}"]`).show();
                }
            }

            // Функция получения названия категории
            function getCategoryName(category) {
                switch (category) {
                    case 'bonusevent': return 'Бонусы за мероприятие';
                    case 'nakrutka': return 'Накрутка';
                    case 'tourslines': return 'Линии туров';
                    case 'education_lines': return 'Линии обучения';
                    case 'cashback': return 'Кэшбэк';
                    default: return category;
                }
            }
        });
    </script>
</body>

</html>