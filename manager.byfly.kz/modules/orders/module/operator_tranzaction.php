<?php
function operatorsOperation($id, $db)
{
    $list = array();
    $listOperationsDB = $db->query("SELECT * FROM order_tour_operators WHERE order_id='" . $id . "'");
    while ($listOperations = $listOperationsDB->fetch_assoc()) {
        $list[] = $listOperations;
    }

    $tableId = 'operators-table-' . $id; // Уникальный ID таблицы
    $html = '<div class="container mt-4">';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-striped table-sm" id="' . $tableId . '">';
    $html .= '<thead class="table-light">';
    $html .= '<tr>';
    $html .= '<th>Сумма</th>';
    $html .= '<th>Тип</th>';
    $html .= '<th>Описание</th>';
    $html .= '<th>Дата</th>';
    $html .= '<th>Документ</th>';
    $html .= '<th>Действия</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    if (empty($list)) {
        $html .= '<tr><td colspan="6" class="text-center">Нет операций для данного тура.</td></tr>';
    } else {
        foreach ($list as $operation) {
            $html .= '<tr id="operator-operation-' . $operation['id'] . '">';
            $html .= '<td>' . number_format($operation['summ'], 2, '.', ' ') . ' KZT</td>';
            $html .= '<td>' . ($operation['is_from'] ? 'Списание' : 'Поступление') . '</td>';
            $html .= '<td>' . htmlspecialchars($operation['description']) . '</td>';
            $html .= '<td>' . date('d.m.Y H:i', strtotime($operation['date_create'])) . '</td>';
            $html .= '<td>' . (!empty($operation['document']) ? '<a href="' . htmlspecialchars($operation['document']) . '" target="_blank" class="btn btn-primary btn-sm">Скачать</a>' : 'Нет') . '</td>';
            $html .= '<td>';
            $html .= '<button class="btn btn-danger btn-sm" onclick="deleteOperatorOperation(' . $operation['id'] . ', \'' . $tableId . '\')">Удалить</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';

    // Форма добавления новой операции
    $html .= <<<HTML
<div class="accordion mt-4" id="addOperatorOperationAccordion-{$id}">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingAddOperatorOperation-{$id}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddOperatorOperation-{$id}" aria-expanded="false" aria-controls="collapseAddOperatorOperation-{$id}">
                Добавить новую операцию
            </button>
        </h2>
        <div id="collapseAddOperatorOperation-{$id}" class="accordion-collapse collapse" aria-labelledby="headingAddOperatorOperation-{$id}">
            <div class="accordion-body">
                <form id="addOperatorOperationForm-{$id}" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="{$id}">
                    <div class="mb-3">
                        <label for="operationSumm-{$id}" class="form-label">Сумма</label>
                        <input type="number" step="0.01" class="form-control" id="operationSumm-{$id}" name="summ" required>
                    </div>
                    <div class="mb-3">
                        <label for="operationType-{$id}" class="form-label">Тип операции</label>
                        <select class="form-select" id="operationType-{$id}" name="is_from">
                            <option value="0">Возврат</option>
                            <option value="1">Оплата</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="operationDescription-{$id}" class="form-label">Описание</label>
                        <textarea class="form-control" id="operationDescription-{$id}" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="operationDocument-{$id}" class="form-label">Документ</label>
                        <input class="form-control" type="file" id="operationDocument-{$id}" name="document">
                    </div>
                    <button type="button" class="btn btn-success" onclick="addOperatorOperation('{$tableId}')">Добавить</button>
                    <div id="operationLoader-{$id}" class="spinner-border text-success ms-3 d-none" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
HTML;

    $html .= <<<SCRIPT
<script>
function addOperatorOperation(tableId) {
    const loader = document.getElementById('operationLoader-' + tableId.split('-')[2]);
    const form = document.getElementById('addOperatorOperationForm-' + tableId.split('-')[2]);
    const formData = new FormData(form);

    loader.classList.remove('d-none');

    fetch('https://manager.byfly.kz/add_operator_operation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const newOperation = '<tr id="operator-operation-' + data.id + '">' +
                '<td>' + parseFloat(data.summ).toFixed(2) + ' KZT</td>' +
                '<td>' + (data.is_from ? 'Списание' : 'Поступление') + '</td>' +
                '<td>' + data.description + '</td>' +
                '<td>' + data.date_create + '</td>' +
                '<td>' + (data.document ? '<a href="' + data.document + '" target="_blank" class="btn btn-primary btn-sm">Скачать</a>' : 'Нет') + '</td>' +
                '<td><button class="btn btn-danger btn-sm" onclick="deleteOperatorOperation(' + data.id + ', \'' + tableId + '\')">Удалить</button></td>' +
                '</tr>';

            document.getElementById(tableId).querySelector('tbody').insertAdjacentHTML('beforeend', newOperation);
            form.reset();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Произошла ошибка: ' + error);
    })
    .finally(() => {
        loader.classList.add('d-none');
    });
}

function deleteOperatorOperation(id, tableId) {
    if (!confirm('Вы уверены, что хотите удалить эту операцию?')) {
        return;
    }

    fetch('https://manager.byfly.kz/delete_operator_operation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('operator-operation-' + id).remove();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Произошла ошибка: ' + error);
    });
}
</script>
SCRIPT;

    $html .= '</div>';

    return $html;
}
?>