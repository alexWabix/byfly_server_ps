<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


$settingsPrice = $db->query("SELECT * FROM app_settings WHERE id='1'")->fetch_assoc();
$listAgentsDB = $db->query("SELECT * FROM users WHERE date_couch_start IS NOT NULL");
while ($listAgents = $listAgentsDB->fetch_assoc()) {
    if ($listAgents['parent_user'] != $listAgents['id']) {
        $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $listAgents['grouped'] . "' ")->fetch_assoc();
        $line1 = $db->query("SELECT * FROM users WHERE id='" . $listAgents['parent_user'] . "'")->fetch_assoc();
        $line2 = $db->query("SELECT * FROM users WHERE id='" . $line1['parent_user'] . "'")->fetch_assoc();


        $summForLine1 = ceil(($listAgents['priced_coach'] / 100) * $groupInfo['cash_back']);
        $summForLine2 = ceil(($listAgents['priced_coach'] / 100) * $settingsPrice['percentage_line_2']);

        //echo $listAgents['name'] . ' ' . $listAgents['famale'] . ', Оплатил за обучение - ' . $listAgents['priced_coach'] . ', Тариф - ' . $listAgents['tarif'] . ', Дата оплаты - ' . $listAgents['date_payment_couch'] . ', Возврат - ' . $listAgents['status_vozvrat'] . ', Училмя в группе - ' . $groupInfo['name_grouped_ru'] . ', Кэшбэк - ' . $groupInfo['cash_back'] . '<br>';
        // echo '---- По первой линии получает: ' . $line1['name'] . ' ' . $line1['famale'] . ', Сумма к получению - ' . $summForLine1 . '<br>';
        // echo '---- По второй линии получает: ' . $line2['name'] . ' ' . $line2['famale'] . ', Сумма к получению - ' . $summForLine2 . '<br>';
    }

}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика агентов</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-box h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-item {
            background-color: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .agent-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .agent-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .agent-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .agent-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-item {
            margin-bottom: 5px;
        }

        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
        }

        .line-item {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .line-1 {
            border-left: 4px solid #3498db;
        }

        .line-2 {
            border-left: 4px solid #2ecc71;
        }

        .payment-amount {
            font-weight: bold;
            color: #27ae60;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php

        $settingsPrice = $db->query("SELECT * FROM app_settings WHERE id='1'")->fetch_assoc();
        $listAgentsDB = $db->query("SELECT * FROM users WHERE date_couch_start IS NOT NULL");

        // Статистические переменные
        $totalAgents = 0;
        $totalPayments = 0;
        $totalPayoutsLine1 = 0;
        $totalPayoutsLine2 = 0;
        $agentsData = [];

        while ($listAgents = $listAgentsDB->fetch_assoc()) {
            if ($listAgents['parent_user'] != $listAgents['id']) {
                $totalAgents++;
                $totalPayments += $listAgents['priced_coach'];

                $groupInfo = $db->query("SELECT * FROM grouped_coach WHERE id='" . $listAgents['grouped'] . "' ")->fetch_assoc();
                $line1 = $db->query("SELECT * FROM users WHERE id='" . $listAgents['parent_user'] . "'")->fetch_assoc();
                $line2 = $db->query("SELECT * FROM users WHERE id='" . $line1['parent_user'] . "'")->fetch_assoc();

                // Исправленный расчет для первой линии (используем cash_back из groupInfo)
                $summForLine1 = ceil(($listAgents['priced_coach'] / 100) * $groupInfo['cash_back']);
                $summForLine2 = ceil(($listAgents['priced_coach'] / 100) * $settingsPrice['percentage_line_2']);

                $totalPayoutsLine1 += $summForLine1;
                $totalPayoutsLine2 += $summForLine2;

                $agentsData[] = [
                    'agent' => $listAgents,
                    'groupInfo' => $groupInfo,
                    'line1' => $line1,
                    'line2' => $line2,
                    'summForLine1' => $summForLine1,
                    'summForLine2' => $summForLine2
                ];
            }
        }

        $totalPayouts = $totalPayoutsLine1 + $totalPayoutsLine2;
        ?>

        <div class="stats-box">
            <h2>Общая статистика</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $totalAgents; ?></div>
                    <div class="stat-label">Всего агентов</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value payment-amount"><?php echo number_format($totalPayments, 0, ',', ' '); ?> ₸
                    </div>
                    <div class="stat-label">Общая сумма оплат</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value payment-amount"><?php echo number_format($totalPayouts, 0, ',', ' '); ?> ₸
                    </div>
                    <div class="stat-label">Общая сумма к выплате</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($totalPayoutsLine1, 0, ',', ' '); ?> ₸ /
                        <?php echo number_format($totalPayoutsLine2, 0, ',', ' '); ?> ₸
                    </div>
                    <div class="stat-label">По 1-й линии / По 2-й линии</div>
                </div>
            </div>
        </div>

        <h2>Детализация по агентам</h2>

        <?php foreach ($agentsData as $agentItem): ?>
            <div class="agent-card">
                <div class="agent-header">
                    <div class="agent-name">
                        <?php echo htmlspecialchars($agentItem['agent']['famale'] . ' ' . $agentItem['agent']['name']); ?>
                    </div>
                </div>

                <div class="agent-details">
                    <div class="detail-item">
                        <span class="detail-label">Оплата за обучение:</span>
                        <span
                            class="payment-amount"><?php echo number_format($agentItem['agent']['priced_coach'], 0, ',', ' '); ?>
                            ₸</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Тариф:</span>
                        <span><?php echo htmlspecialchars($agentItem['agent']['tarif']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Дата оплаты:</span>
                        <span><?php echo htmlspecialchars($agentItem['agent']['date_payment_couch']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Статус возврата:</span>
                        <span><?php echo htmlspecialchars($agentItem['agent']['status_vozvrat']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Группа обучения:</span>
                        <span><?php echo htmlspecialchars($agentItem['groupInfo']['name_grouped_ru']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Кэшбэк:</span>
                        <span><?php echo htmlspecialchars($agentItem['groupInfo']['cash_back']); ?>%</span>
                    </div>
                </div>

                <div class="line-item line-1">
                    <div><strong>Первая линия:</strong>
                        <?php echo htmlspecialchars($agentItem['line1']['famale'] . ' ' . $agentItem['line1']['name']); ?>
                    </div>
                    <div class="payment-amount">Сумма к выплате:
                        <?php echo number_format($agentItem['summForLine1'], 0, ',', ' '); ?> ₸
                        (<?php echo $agentItem['groupInfo']['cash_back']; ?>%)
                    </div>
                </div>

                <div class="line-item line-2">
                    <div><strong>Вторая линия:</strong>
                        <?php echo htmlspecialchars($agentItem['line2']['famale'] . ' ' . $agentItem['line2']['name']); ?>
                    </div>
                    <div class="payment-amount">Сумма к выплате:
                        <?php echo number_format($agentItem['summForLine2'], 0, ',', ' '); ?> ₸
                        (<?php echo $settingsPrice['percentage_line_2']; ?>%)
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>