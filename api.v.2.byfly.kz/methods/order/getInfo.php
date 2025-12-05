<?php

function getProcessingTimeInfo($orderCreateTime, $orderType = 'tour')
{
    // Устанавливаем временную зону Алматы для всех расчетов
    $timezone = new DateTimeZone('Asia/Almaty');

    $createDateTime = new DateTime($orderCreateTime, $timezone);
    $now = new DateTime('now', $timezone);

    // Определяем лимит времени в зависимости от типа заявки
    if ($orderType === 'spec') {
        $timeLimit = 6; // час
        $expectedTime = clone $createDateTime;
        $expectedTime->modify('+' . $timeLimit . ' hours');
        $limitText = '1 час';
    } else {
        $timeLimit = 15; // часы
        $expectedTime = clone $createDateTime;
        $expectedTime->modify('+' . $timeLimit . ' hours');
        $limitText = '12 часов';
    }

    $isExpired = $now > $expectedTime;

    if ($isExpired) {
        // Время обработки истекло
        $interval = $expectedTime->diff($now);
        return [
            'is_expired' => true,
            'expected_time' => $expectedTime->format('Y-m-d H:i:s'),
            'message' => 'Время обработки истекло',
            'overdue_time' => formatInterval($interval),
            'limit_text' => $limitText
        ];
    } else {
        // Время обработки еще не истекло
        $interval = $now->diff($expectedTime);
        return [
            'is_expired' => false,
            'expected_time' => $expectedTime->format('Y-m-d H:i:s'),
            'message' => 'Заявка в процессе обработки',
            'time_remaining' => formatInterval($interval),
            'limit_text' => $limitText
        ];
    }
}

function formatInterval($interval)
{
    $parts = [];

    if ($interval->d > 0) {
        $parts[] = $interval->d . ' дн.';
    }
    if ($interval->h > 0) {
        $parts[] = $interval->h . ' ч.';
    }
    if ($interval->i > 0 || (empty($parts) && $interval->s > 0)) {
        $parts[] = $interval->i . ' мин.';
    }

    return empty($parts) ? '0 мин.' : implode(' ', $parts);
}

function calculateTimeProgress($startTime, $endTime)
{
    // Устанавливаем временную зону Алматы для всех расчетов
    $timezone = new DateTimeZone('Asia/Almaty');

    $start = new DateTime($startTime, $timezone);
    $end = new DateTime($endTime, $timezone);
    $now = new DateTime('now', $timezone);

    // Общее время от создания до крайнего срока
    $totalSeconds = $end->getTimestamp() - $start->getTimestamp();

    // Прошедшее время от создания до сейчас
    $elapsedSeconds = $now->getTimestamp() - $start->getTimestamp();

    // Вычисляем процент
    $percentage = $totalSeconds > 0 ? min(100, max(0, ($elapsedSeconds / $totalSeconds) * 100)) : 100;

    // Оставшееся время
    if ($now < $end) {
        $remaining = $now->diff($end);
        $difference = formatInterval($remaining);
    } else {
        $difference = 'Время истекло';
        $percentage = 100;
    }

    return [
        'percentage' => round($percentage, 2),
        'difference' => $difference
    ];
}

// Функция для расчета времени до вылета (аналог Flutter функции)
function calculateTimeLeftToFlight($dateCreate, $dateFly, $dateOffPay = null)
{
    $timezone = new DateTimeZone('Asia/Almaty');

    $createDateTime = new DateTime($dateCreate, $timezone);
    $flyDateTime = new DateTime($dateFly, $timezone);
    $now = new DateTime('now', $timezone);

    // Общее время от создания до вылета
    $totalSeconds = $flyDateTime->getTimestamp() - $createDateTime->getTimestamp();

    // Прошедшее время от создания до сейчас
    $elapsedSeconds = $now->getTimestamp() - $createDateTime->getTimestamp();

    // Процент прошедшего времени до вылета
    $flightPercentage = $totalSeconds > 0 ? min(100, max(0, ($elapsedSeconds / $totalSeconds) * 100)) : 100;

    // Оставшееся время до вылета
    if ($now < $flyDateTime) {
        $remaining = $now->diff($flyDateTime);
        $leftTime = formatInterval($remaining);
    } else {
        $leftTime = 'Время вылета прошло';
        $flightPercentage = 100;
    }

    $result = [
        'percentage' => $flightPercentage / 100, // Для Flutter (от 0 до 1)
        'left_time' => $leftTime
    ];

    // Если есть дата оплаты, рассчитываем прогресс оплаты
    if ($dateOffPay && $dateOffPay !== '0000-00-00 00:00:00') {
        $payDateTime = new DateTime($dateOffPay, $timezone);

        // Время от создания до крайнего срока оплаты
        $payTotalSeconds = $payDateTime->getTimestamp() - $createDateTime->getTimestamp();

        // Процент времени до оплаты
        $paymentPercentage = $payTotalSeconds > 0 ? min(100, max(0, ($elapsedSeconds / $payTotalSeconds) * 100)) : 100;

        // Оставшееся время до оплаты
        if ($now < $payDateTime) {
            $payRemaining = $now->diff($payDateTime);
            $payLeftTime = formatInterval($payRemaining);
        } else {
            $payLeftTime = 'Время оплаты истекло';
            $paymentPercentage = 100;
        }

        $result['payment_percentage'] = $paymentPercentage / 100; // Для Flutter (от 0 до 1)
        $result['payment_left_time'] = $payLeftTime;
    }

    return $result;
}

try {
    if (empty($_POST['orderId']) == false) {
        $searchOrderDB = $db->query("SELECT * FROM order_tours WHERE id='" . $_POST['orderId'] . "'");
        if ($searchOrderDB->num_rows > 0) {
            $searchOrder = $searchOrderDB->fetch_assoc();
            $searchOrder['passangers'] = array();
            $searchOrder['dop_pays'] = array();
            $searchOrder['dop_pays_summ'] = 0;
            $searchOrder['list_tranzactions'] = array();

            // Получаем транзакции
            $listTrDB = $db->query("SELECT * FROM order_pays WHERE order_id='" . $_POST['orderId'] . "'");
            while ($listTr = $listTrDB->fetch_assoc()) {
                array_push($searchOrder['list_tranzactions'], $listTr);
            }

            // Парсим JSON данные
            $searchOrder['tours_info'] = json_decode($searchOrder['tours_info'], true);

            $json_clean = str_replace(["\r", "\n"], "", $searchOrder['visor_hotel_info']);
            $data = json_decode($json_clean, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $searchOrder['visor_hotel_info'] = $data;
            } else {
                $searchOrder['visor_hotel_info'] = json_last_error_msg();
            }

            $searchOrder['byly_hotel_info'] = json_decode($searchOrder['byly_hotel_info'], true);
            $searchOrder['listPassangers'] = json_decode($searchOrder['listPassangers'], true);

            // Получаем пассажиров
            $listPassangersDB = $db->query("SELECT * FROM order_passangers WHERE order_id='" . $searchOrder['id'] . "'");
            while ($listPassangers = $listPassangersDB->fetch_assoc()) {
                array_push($searchOrder['passangers'], $listPassangers);
            }

            // Получаем дополнительные платежи
            $listDopPaysDB = $db->query("SELECT * FROM order_dop_pays WHERE order_id='" . $searchOrder['id'] . "'");
            while ($listDopPays = $listDopPaysDB->fetch_assoc()) {
                if ($listDopPays['percentage'] > 0) {
                    $listDopPays['summ'] = $listDopPays['summ'] + (($listDopPays['summ'] / 100) * $listDopPays['percentage']);
                }
                $searchOrder['dop_pays_summ'] = $searchOrder['dop_pays_summ'] + $listDopPays['summ'];
                array_push($searchOrder['dop_pays'], $listDopPays);
            }

            // ЛОГИКА ПРОВЕРКИ ВРЕМЕНИ ОБРАБОТКИ
            if ($searchOrder['status_code'] == 0) {
                // Заявка еще не обработана - определяем тип и проверяем время
                $orderType = ($searchOrder['type'] === 'spec') ? 'spec' : 'tour';
                $processingInfo = getProcessingTimeInfo($searchOrder['date_create'], $orderType);

                $searchOrder['processing_info'] = $processingInfo;
                $searchOrder['isWork'] = !$processingInfo['is_expired']; // true если время еще не истекло
                $searchOrder['time_to_work'] = $processingInfo['expected_time'];

                // Для Flutter приложения - добавляем прогресс только если время НЕ истекло
                if (!$processingInfo['is_expired']) {
                    // Время еще не истекло - показываем прогресс
                    $progressData = calculateTimeProgress($searchOrder['date_create'], $processingInfo['expected_time']);
                    $searchOrder['progress'] = $progressData;

                    $searchOrder['processing_status'] = 'pending';
                    $searchOrder['processing_message'] = 'Заявка в обработке. Осталось времени: ' . $processingInfo['time_remaining'];
                } else {
                    // Время истекло - НЕ добавляем прогресс, чтобы Flutter показал ошибку
                    $searchOrder['progress'] = null;

                    $searchOrder['processing_status'] = 'expired';
                    $searchOrder['processing_message'] = 'Заявка не обработана - время обработки вышло (' . $processingInfo['limit_text'] . ')';
                }

            } elseif (in_array($searchOrder['status_code'], [1, 2, 3])) {
                // Заявка подтверждена - рассчитываем время до вылета и оплаты
                $timeLeftData = calculateTimeLeftToFlight(
                    $searchOrder['date_create'],
                    $searchOrder['tours_info']['flydate'] ?? date('Y-m-d'),
                    $searchOrder['dateOffPay'] ?? null
                );

                $searchOrder['time_left_data'] = $timeLeftData;
                $searchOrder['isWork'] = true;
                $searchOrder['progress'] = null;

                if ($searchOrder['status_code'] == 1) {
                    $searchOrder['processing_status'] = 'confirmed';
                    $searchOrder['processing_message'] = 'Заявка подтверждена, ожидает предоплату';
                } elseif ($searchOrder['status_code'] == 2) {
                    $searchOrder['processing_status'] = 'awaiting_payment';
                    $searchOrder['processing_message'] = 'Заявка подтверждена, ожидает полную оплату';
                } else {
                    $searchOrder['processing_status'] = 'paid';
                    $searchOrder['processing_message'] = 'Заявка полностью оплачена, ожидает вылета';
                }

            } elseif ($searchOrder['status_code'] == 4) {
                // Турист на отдыхе
                $searchOrder['processing_info'] = [
                    'status' => 'traveling',
                    'message' => 'Турист на отдыхе'
                ];
                $searchOrder['isWork'] = true;
                $searchOrder['processing_status'] = 'traveling';
                $searchOrder['processing_message'] = 'Турист на отдыхе';
                $searchOrder['time_to_work'] = null;
                $searchOrder['progress'] = null;

            } elseif ($searchOrder['status_code'] == 5) {
                // Заявка отменена
                $searchOrder['processing_info'] = [
                    'status' => 'cancelled',
                    'message' => 'Заявка отменена'
                ];
                $searchOrder['isWork'] = false;
                $searchOrder['processing_status'] = 'cancelled';
                $searchOrder['processing_message'] = 'Заявка отменена: ' . ($searchOrder['cancle_description'] ?? 'Причина не указана');
                $searchOrder['time_to_work'] = null;
                $searchOrder['progress'] = null;

            } elseif ($searchOrder['status_code'] == 7) {
                // Тур завершен
                $searchOrder['processing_info'] = [
                    'status' => 'completed',
                    'message' => 'Тур завершен'
                ];
                $searchOrder['isWork'] = true;
                $searchOrder['processing_status'] = 'completed';
                $searchOrder['processing_message'] = 'Тур завершен! Поделитесь впечатлениями и получите бонусы!';
                $searchOrder['time_to_work'] = null;
                $searchOrder['progress'] = null;

            } else {
                // Неизвестный статус
                $searchOrder['processing_info'] = [
                    'status' => 'unknown',
                    'message' => 'Неизвестный статус заявки'
                ];
                $searchOrder['isWork'] = null;
                $searchOrder['processing_status'] = 'unknown';
                $searchOrder['processing_message'] = 'Неизвестный статус заявки';
                $searchOrder['time_to_work'] = null;
                $searchOrder['progress'] = null;
            }

            // Получаем информацию о менеджере
            if ($searchOrder['manager_id'] !== 0) {
                $managerResult = $db->query("SELECT * FROM managers WHERE id='" . $searchOrder['manager_id'] . "'");
                $searchOrder['manager_info'] = $managerResult ? $managerResult->fetch_assoc() : null;
            } else {
                $searchOrder['manager_info'] = null;
            }

            // Получаем медиа файлы
            $searchOrder['gallery'] = array();
            $searchOrder['gallery_image'] = array();
            $searchOrder['gallery_video'] = array();

            $searchImagesDB = $db->query("SELECT * FROM order_media WHERE order_id='" . $_POST['orderId'] . "'");
            while ($searchImages = $searchImagesDB->fetch_assoc()) {
                array_push($searchOrder['gallery'], $searchImages);
                if ($searchImages['media_type'] == 'image') {
                    array_push($searchOrder['gallery_image'], $searchImages['link_media']);
                } else {
                    array_push($searchOrder['gallery_video'], $searchImages['link_media']);
                }
            }

            // Получаем документы
            $searchOrder['docs'] = array();
            $listDocsDB = $db->query("SELECT * FROM order_docs WHERE order_id='" . $searchOrder['id'] . "'");
            while ($listDocs = $listDocsDB->fetch_assoc()) {
                array_push($searchOrder['docs'], $listDocs);
            }

            echo json_encode(
                array(
                    "type" => true,
                    "data" => $searchOrder,
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Заявка №' . $_POST['orderId'] . ' не найдена! Возможно она была отменена или удалена.',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Не указан ID заявки',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} catch (\Throwable $th) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>