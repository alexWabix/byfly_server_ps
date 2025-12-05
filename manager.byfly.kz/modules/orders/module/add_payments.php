<?php
function getPayments($id, $db, $userId)
{
    // Получение списка платежей
    $paymentsDB = $db->query("SELECT * FROM order_pays WHERE order_id='" . $id . "' AND type='nalichnie'");

    // Убрана внешняя обертка `container`, добавлены стили для устранения отступов
    $html = '<div class="list-group" id="paymentsGroup-' . $id . '">';

    if ($paymentsDB->num_rows == 0) {
        $html .= '<div class="alert alert-info">Нет платежей для данного тура.</div>';
    } else {
        while ($payment = $paymentsDB->fetch_assoc()) {
            $formattedSum = number_format($payment['summ'], 2, '.', ' ');
            $sumInWords = numberToRussian($payment['summ']);

            $html .= '<div class="list-group-item d-flex justify-content-between align-items-center" id="payment-' . $payment['id'] . '">';
            $html .= '<div>
                        <h6 class="mb-1">Тип: Наличные</h6>
                        <small>Сумма: ' . $formattedSum . ' KZT (' . $sumInWords . ') | Дата: ' . $payment['date_create'] . '</small>
                      </div>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';

    // Форма для нового платежа
    $html .= <<<HTML
<div class="accordion mt-3" id="paymentAccordion-{$id}">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingPayment-{$id}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePayment-{$id}" aria-expanded="false" aria-controls="collapsePayment-{$id}">
                Добавить наличный платеж
            </button>
        </h2>
        <div id="collapsePayment-{$id}" class="accordion-collapse collapse" aria-labelledby="headingPayment-{$id}" data-bs-parent="#paymentAccordion-{$id}">
            <div class="accordion-body">
                <form id="addPaymentForm-{$id}" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="order_id" value="{$id}">
                    <input type="hidden" name="operation" value="add_cash_payment">
                    <input type="hidden" name="user_id" value="{$userId}">
                    <div class="mb-3">
                        <label for="paymentSumm-{$id}" class="form-label">Сумма</label>
                        <input type="text" class="form-control" id="paymentSumm-{$id}" name="summ" required oninput="formatInput(this)">
                    </div>
                    <button type="button" class="btn btn-success" onclick="addPayment({$id})">Добавить</button>
                    <div id="paymentLoader-{$id}" class="spinner-border text-success ms-3 d-none" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function formatInput(input) {
    let value = input.value.replace(/\s+/g, '');
    if (!isNaN(value)) {
        input.value = new Intl.NumberFormat('ru-RU').format(value);
    }
}

function addPayment(orderId) {
    const loader = document.getElementById(`paymentLoader-` + orderId);
    const form = document.getElementById(`addPaymentForm-` + orderId);
    const paymentsGroup = document.getElementById(`paymentsGroup-` + orderId);

    const formData = new FormData(form);
    const rawSumm = formData.get('summ').replace(/\s+/g, '');
    formData.set('summ', rawSumm);

    loader.classList.remove('d-none');

    fetch('https://manager.byfly.kz/index.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const formattedSum = data.summ;
                const sumInWords = data.summInWords;

                const newPayment = '<div class="list-group-item d-flex justify-content-between align-items-center" id="payment-'+data . id+'">'+
                        '<div>'+
                            '<h6 class="mb-1">Тип: '+data . type+'</h6>'+
                            '<small>Сумма: '+formattedSum+' KZT ('+sumInWords+') | Дата: '+data . date_create+'</small>'+
                        '</div>'+
                    '</div>';
                paymentsGroup.insertAdjacentHTML('beforeend', newPayment);
                const includesPriceElement = document.getElementById('includesPrice-' + orderId);
                if (includesPriceElement) {
                    const currentIncludes = parseFloat(includesPriceElement.textContent.replace(/\s+/g, '')) || 0;
                    includesPriceElement.textContent = new Intl.NumberFormat('ru-RU').format(currentIncludes + parseFloat(data.summ)) + ' KZT';
                }
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            alert('Произошла ошибка при добавлении. ' + JSON.stringify(error));
        })
        .finally(() => {
            loader.classList.add('d-none');
        });
}
</script>
HTML;

    $html .= '</div>';

    return $html;
}

function numberToRussian($number)
{
    $formatter = new NumberFormatter('ru_RU', NumberFormatter::SPELLOUT);
    return ucfirst($formatter->format($number));
}
?>