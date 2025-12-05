<?php
function dopPays($id, $db)
{
    $dopPaysDB = $db->query("SELECT * FROM order_dop_pays WHERE order_id='" . $id . "'");
    $html = '<div>';
    $html .= '<div class="list-group" id="dopPaysGroup-' . $id . '">';

    if ($dopPaysDB->num_rows == 0) {
        $html .= '<div class="alert alert-info">Нет дополнительных оплат для данного тура.</div>';
    } else {
        while ($dopPay = $dopPaysDB->fetch_assoc()) {
            $html .= '<div class="list-group-item d-flex justify-content-between align-items-center" id="dopPay-' . $dopPay['id'] . '">';
            $html .= '<div>
                        <h6 class="mb-1">' . htmlspecialchars($dopPay['desc_pay']) . '</h6>
                        <small>Сумма: ' . number_format($dopPay['summ'], 2, '.', ' ') . ' KZT | Процент: ' . $dopPay['percentage'] . '%</small>
                      </div>';
            $html .= '<button class="btn btn-danger btn-sm" onclick="deleteDopPay' . $dopPay['id'] . '(' . $dopPay['id'] . ', ' . $id . ')">Удалить</button>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';

    // Добавление формы для новой оплаты
    $html .= <<<HTML
<div class="accordion mt-3" id="dopPayAccordion-{$id}">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingDopPay-{$id}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDopPay-{$id}" aria-expanded="false" aria-controls="collapseDopPay-{$id}">
                Добавить дополнительную оплату
            </button>
        </h2>
        <div id="collapseDopPay-{$id}" class="accordion-collapse collapse" aria-labelledby="headingDopPay-{$id}" data-bs-parent="#dopPayAccordion-{$id}">
            <div class="accordion-body">
                <form id="addDopPayForm-{$id}" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="{$id}">
                    <input type="hidden" name="operation" value="add_dop_pay">
                    <div class="mb-3">
                        <label for="dopPayDesc-{$id}" class="form-label">Описание оплаты</label>
                        <input type="text" class="form-control" id="dopPayDesc-{$id}" name="desc_pay" required>
                    </div>
                    <div class="mb-3">
                        <label for="dopPaySumm-{$id}" class="form-label">Сумма</label>
                        <input type="number" class="form-control" id="dopPaySumm-{$id}" name="summ" required>
                    </div>
                    <div class="mb-3">
                        <label for="dopPayPercentage-{$id}" class="form-label">Процент</label>
                        <input type="number" class="form-control" value="0" id="dopPayPercentage-{$id}" name="percentage" required>
                    </div>
                    <button type="button" class="btn btn-success" onclick="addDopPay{$id}({$id})">Добавить</button>
                    <div id="dopPayLoader-{$id}" class="spinner-border text-success ms-3 d-none" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
function addDopPay{$id}(orderId) {
    const form = $(`#addDopPayForm-`+orderId);
    const loader = $(`#dopPayLoader-`+orderId);
    loader.removeClass('d-none');

    $.ajax({
        url: '',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function (response) {
            loader.addClass('d-none');

            if (response.success) {
                const newItem = $(
                    `<div class="list-group-item d-flex justify-content-between align-items-center" id="dopPay-"+response.data.id>
                        <div>
                            <h6 class="mb-1">`+response.data.desc_pay+`</h6>
                            <small>Сумма: `+parseFloat(response.data.summ).toFixed(2)+` KZT | Процент: `+response.data.percentage+`%</small>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="deleteDopPay(`+response.data.id+`, `+orderId+`)">Удалить</button>
                    </div>`
                );
                $(`#dopPaysGroup-`+orderId).append(newItem);
                form[0].reset();
                alert('Дополнительная оплата успешно добавлена.');
            } else {
                alert(`Ошибка:`+response.message);
            }
        },
        error: function () {
            loader.addClass('d-none');
            alert('Произошла ошибка при добавлении оплаты.');
        }
    });
}

function deleteDopPay{$id}(payId, orderId) {
    if (!confirm('Вы уверены, что хотите удалить эту оплату?')) return;

    $.ajax({
        url: '',
        type: 'POST',
        data: {
            operation: 'delete_dop_pay',
            id: payId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                $(`#dopPay-`+payId).remove();
                alert(response.message);
            } else {
                alert(`Ошибка:`+response.message);
            }
        },
        error: function () {
            alert('Произошла ошибка при удалении оплаты.');
        }
    });
}
</script>


HTML;

    $html .= '</div>';

    return $html;
}
?>