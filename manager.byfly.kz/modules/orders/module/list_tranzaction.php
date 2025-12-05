<?php
function getTransactionOrder($orderId, $db)
{
    $listTransactionDB = $db->query("SELECT * FROM order_pays WHERE order_id='" . $orderId . "'");
    if ($listTransactionDB->num_rows > 0) {
        $transactionsHtml = '
        <div class="container my-5">
            <h5>Список транзакций:</h5>
            <ul class="list-group">';

        while ($transaction = $listTransactionDB->fetch_assoc()) {
            // Получение данных для отображения
            $type = $transaction['type'];
            $iconHtml = '';
            $backgroundColor = '#ffffff';
            $textColor = '#000000';
            $text = '';

            switch ($type) {
                case 'bonus':
                    $text = 'Оплата бонусами';
                    $iconHtml = '<i class="fa fa-tenge"></i>';
                    $backgroundColor = '#007504';
                    $textColor = '#ffffff';
                    break;
                case 'balance':
                    $text = 'Оплата балансом';
                    $iconHtml = '<i class="fa fa-money"></i>';
                    $backgroundColor = '#222222';
                    $textColor = '#ffffff';
                    break;
                case 'kaspi':
                case 'kaspi_kredit':
                case 'kaspi_red':
                    $text = $type === 'kaspi' ? 'Оплата Kaspi' : ($type === 'kaspi_kredit' ? 'Оплата в рассрочку' : 'Оплата Kaspi Red');
                    $iconHtml = '<img src="https://api.v.2.byfly.kz/images/kaspi-icon.png" alt="Kaspi" style="width: 20px;">';
                    $backgroundColor = $type === 'kaspi' ? '#A90000' : '#750000';
                    $textColor = '#ffffff';
                    break;
                case 'order_in':
                    $text = 'Оплата картой';
                    $iconHtml = '<i class="fa fa-credit-card"></i>';
                    $backgroundColor = '#005C75';
                    $textColor = '#ffffff';
                    break;
                case 'nalichnie':
                    $text = 'Оплата наличными';
                    $iconHtml = '<i class="fa fa-map-marker"></i>';
                    $backgroundColor = '#1F1F1F';
                    $textColor = '#ffffff';
                    break;
                default:
                    $text = 'Неизвестный тип';
                    $iconHtml = '<i class="fa fa-question-circle"></i>';
                    $backgroundColor = '#ffffff';
                    $textColor = '#000000';
                    break;
            }

            $transactionsHtml .= '
            <li class="list-group-item d-flex justify-content-between align-items-center" 
                style="background-color: ' . $backgroundColor . '; margin-bottom: 5px; color: ' . $textColor . '; border: none;">
                <div>
                    ' . $iconHtml . ' 
                    <span>' . htmlspecialchars($text) . '</span>
                </div>
                <div>
                    <strong>Сумма:</strong> ' . htmlspecialchars(number_format((float) $transaction['summ'], 0, '', ' ')) . ' KZT
                    <br>
                    <span style="font-size: 12px;"><strong>Дата:</strong> ' . htmlspecialchars($transaction['date_create']) . '</span>
                </div>
                <div>
                    <strong>ID транзакции:</strong> ' . htmlspecialchars($transaction['tranzaction_id']) . '
                </div>
            </li>';
        }

        $transactionsHtml .= '</ul></div>';
        return $transactionsHtml;
    } else {
        return '<div class="alert alert-warning">Транзакции отсутствуют!</div>';
    }
}
?>