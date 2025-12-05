<?php
function getUserInfo($userId, $db)
{
    $userInfoDB = $db->query("SELECT * FROM users WHERE id='" . $userId . "'");
    if ($userInfoDB->num_rows > 0) {
        $userInfo = $userInfoDB->fetch_assoc();

        // Определяем роль
        $roleText = $userInfo['user_status'] === 'agent' ? 'Агент компании' : 'Пользователь';

        // Форматируем баланс и бонусы
        $balanceFormatted = number_format((float) $userInfo['balance'], 0, '', ' ') . ' KZT';
        $bonusFormatted = number_format((float) $userInfo['bonus'], 0, '', ' ') . ' KZT';

        // Проверяем наличие аватара
        if (!empty($userInfo['avatar'])) {
            $avatarHtml = '<img src="' . htmlspecialchars($userInfo['avatar']) . '" class="img-fluid rounded-circle" alt="Аватар">';
        } else {
            // Генерируем инициалы
            $initials = strtoupper(mb_substr($userInfo['name'], 0, 1) . mb_substr($userInfo['famale'], 0, 1));
            $avatarHtml = '
                <div class="d-flex align-items-center justify-content-center bg-secondary rounded-circle text-white" 
                     style="width: 100px; height: 100px; font-size: 36px; font-weight: bold;">
                    ' . $initials . '
                </div>';
        }

        // Проверяем блокировку
        $isBlocked = !empty($userInfo['blocked_to_time']) && strtotime($userInfo['blocked_to_time']) > time();
        $blockInfo = '';
        if ($isBlocked) {
            $remainingTime = strtotime($userInfo['blocked_to_time']) - time();
            $daysRemaining = ceil($remainingTime / 86400);
            $blockInfo = '<div class="alert alert-danger mt-3">Пользователь заблокирован на ' . ($daysRemaining > 0 ? "$daysRemaining дней" : "всегда") . '.<br>Причина: ' . htmlspecialchars($userInfo['block_desc']) . '</div>';
        }

        $html = '
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            ' . $avatarHtml . '
                        </div>
                        <div class="col-md-8">
                            <h5>' . htmlspecialchars($userInfo['name'] . ' ' . $userInfo['famale'] . ' ' . $userInfo['surname']) . '</h5>
                            <p class="mb-1"><strong>Телефон:</strong> ' . htmlspecialchars($userInfo['phone']) . '</p>
                            <p class="mb-1"><strong>Роль:</strong> ' . $roleText . '</p>
                            <p class="mb-1"><strong>Баланс:</strong> ' . $balanceFormatted . '</p>
                            <p class="mb-1"><strong>Бонусы:</strong> ' . $bonusFormatted . '</p>
                            <a target="_blank" href="https://wa.me/' . htmlspecialchars($userInfo['phone']) . '" class="btn btn-success mt-3">
                                Написать в WhatsApp
                            </a>
                            ' . $blockInfo . '
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <ul class="nav nav-tabs" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="true">
                                Заказы
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="block-tab" data-bs-toggle="tab" data-bs-target="#block" type="button" role="tab" aria-controls="block" aria-selected="false">
                                Блокировка
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                            <ul class="list-group">';

        $listOrdersUserDB = $db->query("SELECT * FROM order_tours WHERE user_id='" . $userInfo['id'] . "'");
        if ($listOrdersUserDB->num_rows > 0) {
            while ($listOrdersUser = $listOrdersUserDB->fetch_assoc()) {
                $toursInfo = json_decode(preg_replace('/[\x00-\x1F\x7F]/u', '', trim($listOrdersUser['tours_info'])), true);
                if ($toursInfo != null) {
                    $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Заказ #' . htmlspecialchars($listOrdersUser['id']) . ':</strong><br>
                                        <ul>
                                            <li><strong>Название тура:</strong> ' . htmlspecialchars($toursInfo['tourname'] ?? 'N/A') . '</li>
                                            <li><strong>Отель:</strong> ' . htmlspecialchars($toursInfo['hotelname'] ?? 'N/A') . ' </li>
                                            <li><strong>Регион:</strong> ' . htmlspecialchars($toursInfo['countryname'] ?? 'N/A') . ' / ' . htmlspecialchars($toursInfo['hotelregionname'] ?? 'N/A') . '</li>
                                            <li><strong>Дата вылета:</strong> ' . htmlspecialchars($toursInfo['flydate'] ?? 'N/A') . '</li>
                                            <li><strong>Питание:</strong> ' . htmlspecialchars($toursInfo['meal'] ?? 'N/A') . '</li>
                                            <li><strong>Цена:</strong> ' . number_format((float) ($toursInfo['price'] ?? 0), 2, '.', ' ') . ' ' . htmlspecialchars($toursInfo['currency'] ?? 'N/A') . '</li>
                                            <li>
                                                <a target="_blank" href="https://manager.byfly.kz/index.php?page=search&query=' . htmlspecialchars($listOrdersUser['id']) . '" class="btn btn-danger btn-sm">
                                                    Открыть заказ
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>';
                } else {
                    $html .= '<li class="list-group-item">
                                    <strong>Заказ #' . htmlspecialchars($listOrdersUser['id']) . ':</strong> Неверный формат данных tours_info
                                </li>';
                }
            }
        } else {
            $html .= '<li class="list-group-item">Заказы отсутствуют</li>';
        }

        $html .= '</ul>
                        </div>
                        <div class="tab-pane fade" id="block" role="tabpanel" aria-labelledby="block-tab">
                            <h5>Блокировка пользователя</h5>
                            <form id="blockUserForm">
                                <input type="hidden" name="user_id" value="' . $userInfo['id'] . '">
                                <div class="mb-3">
                                    <label for="blockDays" class="form-label">Количество дней (0 для блокировки навсегда)</label>
                                    <input type="number" id="blockDays" name="days" class="form-control" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="blockReason" class="form-label">Причина блокировки</label>
                                    <textarea id="blockReason" name="reason" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="button" class="btn btn-danger" onclick="blockUser()">Заблокировать</button>
                                <div id="blockLoader" class="spinner-border text-danger ms-3 d-none" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return $html;
    } else {
        return '<div class="alert alert-danger">Пользователь не найден!</div>';
    }
}
?>

<script>
    function blockUser() {
        const loader = document.getElementById('blockLoader');
        const form = document.getElementById('blockUserForm');
        const formData = new FormData(form);

        loader.classList.remove('d-none');

        $.ajax({
            url: '/block_user.php',
            method: 'POST',
            data: {
                user_id: formData.get('user_id'),
                days: formData.get('days'),
                reason: formData.get('reason')
            },
            success: function (response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert('Пользователь успешно заблокирован!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                alert('Произошла ошибка: ' + error);
            },
            complete: function () {
                loader.classList.add('d-none');
            }
        });
    }
</script>