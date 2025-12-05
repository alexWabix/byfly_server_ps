<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');
$userInfoArr = array();

$monitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();

$obshPriceSended = 0;
$listToursInOtdihDB = $db->query("SELECT * FROM order_tours WHERE flyDate > '" . date('Y-m-d') . "' AND status_code = '3' AND includesPrice > 0 AND type='tour' AND send_money_agent='0'");
while ($listToursInOtdih = $listToursInOtdihDB->fetch_assoc()) {
    $listToursInOtdih['tours_info'] = json_decode($listToursInOtdih['tours_info'], true);

    $sendedPrice = ($listToursInOtdih['real_price'] / 100) * $listToursInOtdih['nakrutka'];
    $obshPriceSended = $obshPriceSended + $sendedPrice;

    $orderSendedMoney = 0;

    $userInfo = $db->query("SELECT * FROM users WHERE id='" . $listToursInOtdih['user_id'] . "'")->fetch_assoc();
    if ($userInfo['user_status'] == 'user') {
        if ($listToursInOtdih['sub_user'] > 0) {
            $orderSendedMoney = $orderSendedMoney + $sendedPrice;
            $sendedPrice = $sendedPrice / 2;
            $managerInfo = $db->query("SELECT * FROM users WHERE id='" . $listToursInOtdih['sub_user'] . "'")->fetch_assoc();
            nachislenieLine($userInfo, $sendedPrice, $listToursInOtdih['tours_info'], $listToursInOtdih['nakrutka'] . '%', 'Сегодня вылетает ваш клиент и вам начислена ваша накрутка в размере: ' . number_format($sendedPrice, 0, '', ' ') . ' тенге. Напоминаем накрутка разделена с менеджером 50/50.');
            nachislenieLine($managerInfo, $sendedPrice, $listToursInOtdih['tours_info'], $listToursInOtdih['nakrutka'] . '%', 'Сегодня вылетает ваш клиент и вам начислена ваша накрутка в размере: ' . number_format($sendedPrice, 0, '', ' ') . ' тенге. Напоминаем накрутка разделена с пользователем 50/50.');
        } else {
            $userInfo = $db->query("SELECT * FROM users WHERE id='" . $userInfo['parent_user'] . "'")->fetch_assoc();
            if ($userInfo['blocked_to_time'] == null) {
                $orderSendedMoney = $orderSendedMoney + $sendedPrice;
                nachislenieLine($userInfo, $sendedPrice, $listToursInOtdih['tours_info'], $listToursInOtdih['nakrutka'] . '%', 'Сегодня вылетает ваш клиент и вам начислена ваша накрутка в размере: ' . number_format($sendedPrice, 0, '', ' ') . ' тенге');
            }
        }
    } else {
        if ($userInfo['blocked_to_time'] == null) {
            $orderSendedMoney = $orderSendedMoney + $sendedPrice;
            nachislenieLine($userInfo, $sendedPrice, $listToursInOtdih['tours_info'], $listToursInOtdih['nakrutka'] . '%', 'Сегодня вылетает ваш клиент и вам начислена ваша накрутка в размере: ' . number_format($sendedPrice, 0, '', ' ') . ' тенге');
        }
    }


    $db->query("UPDATE users SET is_active ='1' WHERE id='" . $userInfo['id'] . "'");
    $db->query("UPDATE order_tours SET status_code='4' WHERE id='" . $listToursInOtdih['id'] . "'");


    $getUserRefer1 = $db->query("SELECT * FROM users WHERE id='" . $userInfo['parent_user'] . "'");


    if ($getUserRefer1->num_rows > 0) {
        $getUserRefer1 = $getUserRefer1->fetch_assoc();
        $onePercentage = ($listToursInOtdih['real_price'] / 100) * 1;
        $obshPriceSended = $obshPriceSended + $onePercentage;

        $orderSendedMoney = $orderSendedMoney + $onePercentage;

        nachislenieLine($getUserRefer1, $onePercentage, $listToursInOtdih, '1%', 'В вашей первой линии произошла продажа тура! Клиент сегодня вылетает на отдых и вам начислена заработная плата в размере 1% (' . number_format($onePercentage, 0, '', ' ') . ')');


        $getUserRefer2 = $db->query("SELECT * FROM users WHERE id='" . $getUserRefer1['parent_user'] . "'");
        if ($getUserRefer2->num_rows > 0) {
            $getUserRefer2 = $getUserRefer2->fetch_assoc();
            $twoPercentage = ($listToursInOtdih['real_price'] / 100) * ($getUserRefer2['user_status'] == 'agent' ? 0.2 : 0.3);
            $obshPriceSended = $obshPriceSended + $twoPercentage;


            $orderSendedMoney = $orderSendedMoney + $twoPercentage;

            nachislenieLine($getUserRefer2, $twoPercentage, $listToursInOtdih, ($getUserRefer2['user_status'] == 'agent' ? '0.2%' : '0.3%'), 'В вашей второй линии произошла продажа тура! Клиент сегодня вылетает на отдых и вам нвчислена заработная плата в размере ' . ($getUserRefer2['user_status'] == 'agent' ? '0.2%' : '0.3%') . '(' . number_format($twoPercentage, 0, '', ' ') . ')');

            $getUserRefer3 = $db->query("SELECT * FROM users WHERE id='" . $getUserRefer2['parent_user'] . "'");
            if ($getUserRefer3->num_rows > 0) {
                $getUserRefer3 = $getUserRefer3->fetch_assoc();
                if ($getUserRefer3['user_status'] == 'ambasador') {
                    $threePercentage = ($listToursInOtdih['real_price'] / 100) * 0.2;
                    $obshPriceSended = $obshPriceSended + $threePercentage;

                    $orderSendedMoney = $orderSendedMoney + $threePercentage;
                    nachislenieLine($getUserRefer3, $threePercentage, $listToursInOtdih, '0.2%', 'В вашей третьей линии произошла продажа тура! Клиент сегодня вылетает на отдых и вам нвчислена заработная плата в размере 0.2% (' . number_format($threePercentage, 0, '', ' ') . ')');
                }

                $getUserRefer4 = $db->query("SELECT * FROM users WHERE id='" . $getUserRefer3['parent_user'] . "'");
                if ($getUserRefer4->num_rows > 0) {
                    $getUserRefer4 = $getUserRefer4->fetch_assoc();
                    if ($getUserRefer4['user_status'] == 'couch') {
                        $fourPercentage = ($listToursInOtdih['real_price'] / 100) * 0.2;
                        $obshPriceSended = $obshPriceSended + $fourPercentage;

                        $orderSendedMoney = $orderSendedMoney + $threePercentage;
                        nachislenieLine($getUserRefer4, $fourPercentage, $listToursInOtdih, '0.2%', 'В вашей четвертой линии произошла продажа тура! Клиент сегодня вылетает на отдых и вам нвчислена заработная плата в размере 0.2% (' . number_format($fourPercentage, 0, '', ' ') . ')');
                    }


                    $getUserRefer5 = $db->query("SELECT * FROM users WHERE id='" . $getUserRefer4['parent_user'] . "'");
                    if ($getUserRefer5->num_rows > 0) {
                        $getUserRefer5 = $getUserRefer5->fetch_assoc();
                        if ($getUserRefer5['user_status'] == 'alpha') {
                            $fivePercentage = ($listToursInOtdih['real_price'] / 100) * ($getUserRefer4['user_status'] == 'alpha' ? 0.2 : 0.1);
                            $obshPriceSended = $obshPriceSended + $fivePercentage;

                            $obshPriceSended = $obshPriceSended + $fivePercentage;
                            nachislenieLine($getUserRefer5, $fivePercentage, $listToursInOtdih, ($getUserRefer5['user_status'] == 'alpha' ? '0.2%' : '0.1%'), 'В вашей четвертой линии произошла продажа тура! Клиент сегодня вылетает на отдых и вам нвчислена заработная плата в размере ' . ($getUserRefer4['user_status'] == 'alpha' ? '0.2%' : '0.1%') . ' (' . number_format($fivePercentage, 0, '', ' ') . ')');
                        }

                    }

                }
            }
        }
    }

    $db->query("UPDATE order_tours SET send_money_agent='1', summ_send_money='" . $obshPriceSended . "' WHERE id='" . $listToursInOtdih['id'] . "'");
}



$obshAgent = 0;

$agentsPayDB = $db->query("SELECT * FROM users WHERE date_couch_start > '" . $monitor['last_proschet'] . "' AND blocked_to_time IS NULL AND price_coach_online < 1500000");
while ($agentsPay = $agentsPayDB->fetch_assoc()) {
    $summPay = 1500000 - $agentsPay['price_coach_online'];
    $date_registration = new DateTime($agentsPay['date_couch_start']);
    $target_date = new DateTime('2025-02-10');
    $percent = 0;
    if ($date_registration > $target_date) {
        $percent = 25;
    } else {
        if ($summPay >= 400000) {
            if ($summPay == 400000) {
                $percent = 5;
            } else if ($summPay == 800000) {
                $percent = 10;
            }
        } else if ($summPay == 300000) {
            $percent = 3;
        }
    }
    $db->query("UPDATE users SET is_active ='1' WHERE id='" . $agentsPay['id'] . "'");


    $parentUser = $db->query("SELECT * FROM users WHERE id='" . $agentsPay['parent_user'] . "'");
    if ($parentUser->num_rows > 0) {
        $summSendPay = ($summPay / 100) * $percent;
        $obshAgent = $obshAgent + $summSendPay;
        $parentUser = $parentUser->fetch_assoc();

        sendNachislenieForCouch($parentUser, $summSendPay, $percent . '%', 1, 'Привлеченный вами агент начал проходить обучение и вам начислен реферальный бонус в размере ' . $percent . '% (' . number_format($summSendPay, 0, '', ' ') . ').');


        $line2User = $db->query("SELECT * FROM users WHERE id='" . $parentUser['parent_user'] . "'");
        if ($line2User->num_rows > 0) {
            $line2User = $line2User->fetch_assoc();
            $line2summ = ($summPay / 100) * 0.2;

            $obshAgent = $obshAgent + $line2summ;

            sendNachislenieForCouch($line2User, $line2summ, '0.2%', 2, 'В вашей второй линии зарегистрирован новый агент. Вам начислен реферальный бонус по второй линии в размере 0.2% (' . number_format($line2summ, 0, '', ' ') . ').');

            $line3User = $db->query("SELECT * FROM users WHERE id='" . $line2User['parent_user'] . "'");
            if ($line3User->num_rows > 0) {
                $line3User = $line3User->fetch_assoc();
                if ($line3User['user_status'] == 'ambasador') {
                    $line3summ = ($summPay / 100) * ($line3User['user_status'] == 'ambasador' ? 0.3 : 0.2);
                    $obshAgent = $obshAgent + $line3summ;


                    sendNachislenieForCouch($line3User, $line3summ, ($line3User['user_status'] == 'ambasador' ? 0.3 : 0.2) . '%', 3, 'В вашей третьей линии зарегистрирован новый агент. Вам начислен реферальный бонус по третьей линии в размере ' . ($line3User['user_status'] == 'ambasador' ? 0.3 : 0.2) . '% (' . number_format($line3summ, 0, '', ' ') . ').');
                }


                $line4User = $db->query("SELECT * FROM users WHERE id='" . $line3User['parent_user'] . "'");

                if ($line4User->num_rows > 0) {
                    $line4User = $line4User->fetch_assoc();


                    if ($line4User['user_status'] == 'couch') {
                        $line4summ = ($summPay / 100) * ($line3User['user_status'] == 'couch' ? 0.1 : 0.2);
                        $obshAgent = $obshAgent + $line4summ;
                        sendNachislenieForCouch($line4User, $line4summ, ($line4User['user_status'] == 'couch' ? 0.1 : 0.2) . '%', 4, 'В вашей четвертой линии зарегистрирован новый агент. Вам начислен реферальный бонус по четвертой линии в размере ' . ($line4User['user_status'] == 'couch' ? 0.1 : 0.2) . '% (' . number_format($line4summ, 0, '', ' ') . ').');
                    }

                    $line5User = $db->query("SELECT * FROM users WHERE id='" . $line4User['parent_user'] . "'");
                    if ($line5User->num_rows > 0) {
                        $line5User = $line5User->fetch_assoc();

                        if ($line5User['user_status'] == 'alpha') {
                            $line5summ = ($summPay / 100) * 0.2;
                            $obshAgent = $obshAgent + $line5summ;

                            sendNachislenieForCouch($line5User, $line5summ, '0.2%', 5, 'В вашей пятой линии зарегистрирован новый агент. Вам начислен реферальный бонус по пятой линии в размере ' . ($line5User['user_status'] == 'couch' ? 0.1 : 0.2) . '% (' . number_format($line5summ, 0, '', ' ') . ').');

                        }

                    }

                }

            }

        }
    }
}




function sendNachislenieForCouch($userInfo, $summ, $percent, $line, $description)
{
    global $userInfoArr;
    global $db;

    if ($userInfo['blocked_to_time'] == null) {
        $userKey = $userInfo['id'];

        if (empty($userInfoArr[$userKey])) {
            $userInfoArr[$userKey] = [
                "bonus" => 0,
                "balance" => 0,
                "name" => $userInfo['famale'] . ' ' . $userInfo['name'],
                "phone" => '+7' . $userInfo['phone'],
                "tranzaction" => array(),
                "user_info" => $userInfo,
            ];
        }
        if ($summ > 0) {
            if ($userInfo['user_status'] == 'user') {
                $userInfoArr[$userKey]['bonus'] += $summ;
                array_push($userInfoArr[$userKey]['tranzaction'], array(
                    "desc" => $description,
                    "summ" => $summ,
                    "type" => 'bonus',
                ));
            } else {
                $userInfoArr[$userKey]['balance'] += $summ;
                array_push($userInfoArr[$userKey]['tranzaction'], array(
                    "desc" => $description,
                    "summ" => $summ,
                    "type" => 'balance',
                ));
            }
        }
    }
}

function nachislenieLine($userInfo, $summ, $orderInfo, $percentage, $description)
{
    global $db;
    global $userInfoArr;

    if ($userInfo['blocked_to_time'] == null) {
        $userKey = $userInfo['id'];
        if (empty($userInfoArr[$userKey])) {
            $userInfoArr[$userKey] = [
                "bonus" => 0,
                "balance" => 0,
                "name" => $userInfo['famale'] . ' ' . $userInfo['name'],
                "phone" => '+7' . $userInfo['phone'],
                "tranzaction" => array(),
                "user_info" => $userInfo,
            ];
        }

        if ($summ > 0) {
            if ($userInfo['user_status'] == 'user') {
                $userInfoArr[$userKey]['bonus'] += $summ;
                array_push($userInfoArr[$userKey]['tranzaction'], array(
                    "desc" => $description,
                    "summ" => $summ,
                    "type" => 'bonus',
                ));
            } else {
                $userInfoArr[$userKey]['balance'] += $summ;
                array_push($userInfoArr[$userKey]['tranzaction'], array(
                    "desc" => $description,
                    "summ" => $summ,
                    "type" => 'balance',
                ));
            }
        }

    }
}


$obshSumm = 0;
$ct = 0;
foreach ($userInfoArr as $key => $user) {
    if ($user['balance'] > 0 or $user['bonus'] > 0) {
        $user['user_info']['balance'] += $user['balance'];
        $user['user_info']['bonus'] += $user['bonus'];

        $tranzactionsMessage = '';
        foreach ($user['tranzaction'] as $tranzaction) {
            $db->query("INSERT INTO user_tranzactions (`id`, `date_create`, `summ`, `type_operations`, `user_id`, `pay_info`) VALUES (NULL, CURRENT_TIMESTAMP, '" . $tranzaction['summ'] . "', '1', '" . $user['user_info']['id'] . "', '" . $tranzaction['desc'] . "');");
            $tranzactionsMessage .= "💰 Сумма: " . number_format($tranzaction['summ'], 0, '', ' ') . " KZT\n📌 Описание: " . $tranzaction['desc'] . "\n\n";
        }

        $message = '';

        echo $user['user_info']['famale'] . ' ' . $user['user_info']['name'] . ' | Баланс: ' . $user['user_info']['balance'] . ' | Бонусы: ' . $user['user_info']['bonus'] . '<br>';

        if ($db->query("UPDATE users SET balance='" . $user['user_info']['balance'] . "', bonus='" . $user['user_info']['bonus'] . "' WHERE id='" . $user['user_info']['id'] . "'")) {
            if ($user['balance'] > 0) {
                $message = "🎉 Поздравляем, {$user['user_info']['famale']} {$user['user_info']['name']}!\n\n💸 Сегодня ваш доход составил *" . number_format($user['balance'], 0, '', ' ') . " KZT*!\n📅 Вывод доступен по средам (мин. 50 000 KZT). 💳\n\n🚀 Развивайтесь вместе с *ByFly Travel*!\n\n🔍 Детали начислений:\n" . $tranzactionsMessage;
            } else {
                $message = "🎉 Отличные новости, {$user['user_info']['famale']} {$user['user_info']['name']}!\n\n🎁 Вам начислено *" . number_format($user['bonus'], 0, '', ' ') . " KZT* в бонусах!\n🛍️ Используйте их для покупок в приложении и наслаждайтесь привилегиями!\n\n🚀 Вперёд к новым возможностям с *ByFly Travel*!\n\n🔍 Детали начислений:\n" . $tranzactionsMessage;
            }
        }

        sendWhatsapp($user['user_info']['phone'], $message);
        sleep(5);


        $obshSumm += $user['balance'];
    }
}

$db->query("UPDATE monitor SET last_proschet='" . date('Y-m-d H:i:s') . "' WHERE id='1'");



?>