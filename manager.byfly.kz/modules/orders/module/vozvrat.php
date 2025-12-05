<?php
function getVozvrat($id, $db)
{
    // Получение данных о возвратах из базы данных
    $listVozvratDB = $db->query("SELECT * FROM order_vozvrat WHERE order_id='" . $id . "'");
    $html = '<div class="vozvrat-widget">';

    // Кнопка для открытия/закрытия аккордеона
    $html .= '
        <button id="toggle-form-btn" style="margin-bottom: 20px;" class="btn btn-danger" onclick="toggleForm()">Добавить возврат</button>
        <div id="vozvrat-form-wrapper" style="display: none; margin-top: 20px;">
            <form id="add-vozvrat-form">
                <div class="form-group">
                    <label for="vozvrat-summ">Сумма</label>
                    <input type="number" id="vozvrat-summ" name="summ" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vozvrat-description">Комментарий</label>
                    <textarea id="vozvrat-description" name="description" class="form-control" required></textarea>
                </div>
                <input type="hidden" id="order-id" name="order_id" value="' . htmlspecialchars($id) . '">
                <button style="margin-top: 20px;" type="button" id="add-vozvrat-btn" class="btn btn-primary" onclick="submitVozvrat()">Добавить</button>
            </form>
            <div id="form-preloader" style="display: none;">Загрузка...</div>
        </div>
    ';

    // Таблица возвратов
    $html .= '
        <div id="vozvrat-list">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата и время</th>
                        <th>Сумма</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody id="tableVozvrat">';
    while ($listVozvrat = $listVozvratDB->fetch_assoc()) {
        $html .= '
            <tr data-id="' . $listVozvrat['id'] . '">
                <td>' . htmlspecialchars($listVozvrat['id']) . '</td>
                <td>' . htmlspecialchars($listVozvrat['date']) . '</td>
                <td>' . htmlspecialchars($listVozvrat['summ']) . '</td>
                <td>' . htmlspecialchars($listVozvrat['description']) . '</td>
            </tr>';
    }
    $html .= '</tbody></table></div>';

    $html .= '</div>';

    // JavaScript внутри PHP
    $html .= "
    <script>
        // Функция для открытия/закрытия аккордеона
        function toggleForm() {
            const formWrapper = document.getElementById('vozvrat-form-wrapper');
            const isHidden = formWrapper.style.display === 'none';
            formWrapper.style.display = isHidden ? 'block' : 'none';
        }

        // Функция для отправки данных
        function submitVozvrat() {
            // Показываем прелоадер
            $('#form-preloader').show();

            // Собираем данные формы
            const formData = {
                summ: $('#vozvrat-summ').val(),
                description: $('#vozvrat-description').val(),
                order_id: $('#order-id').val()
            };

            // Отправка данных через AJAX
            $.ajax({
                url: 'add_vozvrat.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    $('#form-preloader').hide();
                    if (response.success) {
                        $('#tableVozvrat').append(
                            '<tr data-id=\"' + response.data.id + '\">' +
                            '<td>' + response.data.id + '</td>' +
                            '<td>' + response.data.date + '</td>' +
                            '<td>' + response.data.summ + '</td>' +
                            '<td>' + response.data.description + '</td>' +
                            '</tr>'
                        );

                        // Очищаем поля формы
                        $('#vozvrat-summ').val('');
                        $('#vozvrat-description').val('');

                        // Скрываем форму
                        toggleForm();
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    $('#form-preloader').hide();
                    alert('Ошибка при добавлении возврата');
                }
            });
        }
    </script>
    ";

    return $html;
}
?>