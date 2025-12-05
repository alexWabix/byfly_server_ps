<?php
include('../config.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class KaspiPaymentProcessor
{
    private $db;
    private $log_messages = [];

    public function __construct($database)
    {
        $this->db = $database;
    }

    private function log($message, $data = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";
        if ($data) {
            $log_entry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $this->log_messages[] = $log_entry;
        error_log($log_entry);
    }

    // Получение свободного терминала
    private function getFreeTerminal($amount)
    {
        $query = "SELECT * FROM kaspi_terminals 
                  WHERE status = 'free' 
                  AND is_enabled = 1 
                  AND maintenance_mode = 0
                  AND min_amount <= ? 
                  AND max_amount >= ?
                  AND error_count < auto_disable_errors
                  ORDER BY priority ASC, queue_weight DESC, operations_count_today ASC
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $amount, $amount);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    // Обновление статуса терминала
    private function updateTerminalStatus($terminal_id, $status, $process_id = null, $error_message = null)
    {
        $updates = ['status = ?', 'last_check_date = NOW()'];
        $params = [$status];
        $types = 's';

        if ($process_id) {
            $updates[] = 'last_operation_id = ?';
            $params[] = $process_id;
            $types .= 's';
        }

        if ($error_message) {
            $updates[] = 'last_error_message = ?';
            $updates[] = 'error_count = error_count + 1';
            $params[] = $error_message;
            $types .= 's';
        } else {
            $updates[] = 'error_count = 0';
            $updates[] = 'last_error_message = NULL';
        }

        if ($status === 'free') {
            $updates[] = 'last_success_date = NOW()';
            $updates[] = 'operations_count_today = operations_count_today + 1';
            $updates[] = 'operations_count_total = operations_count_total + 1';
        }

        $query = "UPDATE kaspi_terminals SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $terminal_id;
        $types .= 'i';

        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    // HTTP запрос к терминалу
    private function makeTerminalRequest($ip, $port, $endpoint, $params = [])
    {
        $url = "https://{$ip}:{$port}/v2/{$endpoint}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $start_time = microtime(true);
        $response = file_get_contents($url, false, $context);
        $response_time = (microtime(true) - $start_time) * 1000; // в миллисекундах

        if ($response === false) {
            throw new Exception("Не удалось подключиться к терминалу {$ip}:{$port}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Некорректный JSON ответ от терминала");
        }

        // Обновляем время отклика терминала
        $this->updateTerminalResponseTime($ip, $response_time);

        return $data;
    }

    // Обновление времени отклика терминала
    private function updateTerminalResponseTime($ip, $response_time)
    {
        $query = "UPDATE kaspi_terminals 
                  SET response_time_avg = CASE 
                      WHEN response_time_avg = 0 THEN ?
                      ELSE (response_time_avg + ?) / 2
                  END
                  WHERE ip_address = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('iis', $response_time, $response_time, $ip);
        $stmt->execute();
    }

    // Создание платежа на терминале
    private function createPaymentOnTerminal($terminal, $payment)
    {
        try {
            $this->log("Создание платежа на терминале", [
                'terminal_id' => $terminal['id'],
                'payment_id' => $payment['id'],
                'amount' => $payment['amount_total']
            ]);

            // Обновляем статус терминала на занят
            $this->updateTerminalStatus($terminal['id'], 'busy');

            // Создаем платеж на терминале
            $response = $this->makeTerminalRequest(
                $terminal['ip_address'],
                $terminal['port'],
                'payment',
                [
                    'amount' => $payment['amount_total'],
                    'owncheque' => 'true'
                ]
            );

            if ($response['statusCode'] === 0 && isset($response['data']['processId'])) {
                $process_id = $response['data']['processId'];

                // Обновляем запись в очереди
                $update_query = "UPDATE kaspi_payment_queue SET 
                                status = 'waiting_payment',
                                terminal_id = ?,
                                process_id = ?,
                                date_payment_started = NOW(),
                                expires_at = DATE_ADD(NOW(), INTERVAL 3 MINUTE)
                                WHERE id = ?";

                $stmt = $this->db->prepare($update_query);
                $stmt->bind_param('isi', $terminal['id'], $process_id, $payment['id']);
                $stmt->execute();

                // Обновляем терминал с process_id
                $this->updateTerminalStatus($terminal['id'], 'busy', $process_id);

                $this->log("Платеж создан успешно", [
                    'process_id' => $process_id,
                    'terminal_id' => $terminal['id']
                ]);

                return true;

            } else {
                throw new Exception($response['errorText'] ?? 'Неизвестная ошибка создания платежа');
            }

        } catch (Exception $e) {
            $this->log("Ошибка создания платежа", [
                'terminal_id' => $terminal['id'],
                'error' => $e->getMessage()
            ]);

            // Возвращаем терминал в свободное состояние
            $this->updateTerminalStatus($terminal['id'], 'free', null, $e->getMessage());

            // Увеличиваем счетчик попыток платежа
            $retry_query = "UPDATE kaspi_payment_queue SET 
                           retry_count = retry_count + 1,
                           error_message = ?
                           WHERE id = ?";
            $stmt = $this->db->prepare($retry_query);
            $stmt->bind_param('si', $e->getMessage(), $payment['id']);
            $stmt->execute();

            return false;
        }
    }

    // Проверка статуса платежа
    private function checkPaymentStatus($payment)
    {
        try {
            $terminal_query = "SELECT * FROM kaspi_terminals WHERE id = ?";
            $stmt = $this->db->prepare($terminal_query);
            $stmt->bind_param('i', $payment['terminal_id']);
            $stmt->execute();
            $terminal = $stmt->get_result()->fetch_assoc();

            if (!$terminal) {
                throw new Exception("Терминал не найден");
            }

            $response = $this->makeTerminalRequest(
                $terminal['ip_address'],
                $terminal['port'],
                'status',
                ['processId' => $payment['process_id']]
            );

            if ($response['statusCode'] === 0 && isset($response['data'])) {
                $status = $response['data']['status'];

                $this->log("Статус платежа получен", [
                    'payment_id' => $payment['id'],
                    'process_id' => $payment['process_id'],
                    'status' => $status
                ]);

                if ($status === 'success') {
                    // Платеж успешен
                    $this->handleSuccessfulPayment($payment, $response['data']);
                    return 'completed';

                } elseif ($status === 'fail') {
                    // Платеж отменен
                    $this->handleFailedPayment($payment, $response['data']);
                    return 'failed';

                } elseif ($status === 'wait') {
                    // Проверяем не истекло ли время
                    if (strtotime($payment['expires_at']) < time()) {
                        $this->handleExpiredPayment($payment);
                        return 'expired';
                    }
                    return 'waiting';

                } else {
                    // Неизвестный статус - актуализируем
                    $this->actualizePayment($payment);
                    return 'unknown';
                }
            } else {
                throw new Exception($response['errorText'] ?? 'Ошибка получения статуса');
            }

        } catch (Exception $e) {
            $this->log("Ошибка проверки статуса", [
                'payment_id' => $payment['id'],
                'error' => $e->getMessage()
            ]);

            return 'error';
        }
    }

    // Обработка успешного платежа
    private function handleSuccessfulPayment($payment, $response_data)
    {
        try {
            $cheque_info = $response_data['chequeInfo'] ?? [];

            // Рассчитываем реально полученную сумму с учетом комиссии
            $amount_received = $payment['amount_total'];

            // Определяем комиссию по методу оплаты
            if (isset($cheque_info['method'])) {
                switch ($cheque_info['method']) {
                    case 'qr':
                        // Проверяем тип продукта для определения комиссии
                        $product_type = $response_data['addInfo']['ProductType'] ?? '';
                        if (strpos($product_type, 'Рассрочка') !== false) {
                            $commission = 0.125; // 12.5%
                        } elseif (strpos($product_type, 'Red') !== false) {
                            $commission = 0.05; // 5%
                        } elseif (strpos($product_type, 'Кредит') !== false) {
                            $commission = 0.05; // 5%
                        } else {
                            $commission = 0.0095; // 0.95% для Kaspi Gold
                        }
                        break;
                    default:
                        $commission = 0.0095; // По умолчанию 0.95%
                }

                $amount_received = $payment['amount_total'] - ($payment['amount_total'] * $commission);
            }

            // Рассчитываем длительность платежа
            $duration = null;
            if ($payment['date_payment_started']) {
                $start_time = strtotime($payment['date_payment_started']);
                $duration = time() - $start_time;
            }

            // Обновляем запись платежа
            $update_query = "UPDATE kaspi_payment_queue SET 
                            status = 'paid',
                            amount_received = ?,
                            kaspi_order_number = ?,
                            kaspi_rrn = ?,
                            kaspi_transaction_id = ?,
                            date_paid = NOW(),
                            date_completed = NOW(),
                            payment_duration_seconds = ?,
                            error_message = NULL
                            WHERE id = ?";

            $stmt = $this->db->prepare($update_query);
            $stmt->bind_param(
                'isssii',
                $amount_received,
                $cheque_info['orderNumber'] ?? null,
                $cheque_info['rrn'] ?? null,
                $response_data['transactionId'] ?? null,
                $duration,
                $payment['id']
            );
            $stmt->execute();

            // Освобождаем терминал
            $this->updateTerminalStatus($payment['terminal_id'], 'free');

            $this->log("Платеж завершен успешно", [
                'payment_id' => $payment['id'],
                'amount_received' => $amount_received,
                'duration' => $duration
            ]);

        } catch (Exception $e) {
            $this->log("Ошибка обработки успешного платежа", [
                'payment_id' => $payment['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    // Обработка неудачного платежа
    private function handleFailedPayment($payment, $response_data)
    {
        $error_message = $response_data['message'] ?? 'Платеж отменен';

        $update_query = "UPDATE kaspi_payment_queue SET 
                        status = 'cancelled',
                        date_completed = NOW(),
                        error_message = ?
                        WHERE id = ?";

        $stmt = $this->db->prepare($update_query);
        $stmt->bind_param('si', $error_message, $payment['id']);
        $stmt->execute();

        // Освобождаем терминал
        $this->updateTerminalStatus($payment['terminal_id'], 'free');

        $this->log("Платеж отменен", [
            'payment_id' => $payment['id'],
            'reason' => $error_message
        ]);
    }

    // Обработка истекшего платежа
    private function handleExpiredPayment($payment)
    {
        try {
            // Пытаемся отменить платеж на терминале
            $terminal_query = "SELECT * FROM kaspi_terminals WHERE id = ?";
            $stmt = $this->db->prepare($terminal_query);
            $stmt->bind_param('i', $payment['terminal_id']);
            $stmt->execute();
            $terminal = $stmt->get_result()->fetch_assoc();

            if ($terminal && $payment['process_id']) {
                $this->makeTerminalRequest(
                    $terminal['ip_address'],
                    $terminal['port'],
                    'cancel',
                    ['processId' => $payment['process_id']]
                );
            }
        } catch (Exception $e) {
            $this->log("Ошибка отмены истекшего платежа на терминале", [
                'payment_id' => $payment['id'],
                'error' => $e->getMessage()
            ]);
        }

        $update_query = "UPDATE kaspi_payment_queue SET 
                        status = 'expired',
                        date_completed = NOW(),
                        error_message = 'Время ожидания оплаты истекло'
                        WHERE id = ?";

        $stmt = $this->db->prepare($update_query);
        $stmt->bind_param('i', $payment['id']);
        $stmt->execute();

        // Освобождаем терминал
        if ($payment['terminal_id']) {
            $this->updateTerminalStatus($payment['terminal_id'], 'free');
        }

        $this->log("Платеж истек", ['payment_id' => $payment['id']]);
    }

    // Актуализация платежа
    private function actualizePayment($payment)
    {
        try {
            $terminal_query = "SELECT * FROM kaspi_terminals WHERE id = ?";
            $stmt = $this->db->prepare($terminal_query);
            $stmt->bind_param('i', $payment['terminal_id']);
            $stmt->execute();
            $terminal = $stmt->get_result()->fetch_assoc();

            if (!$terminal) {
                throw new Exception("Терминал не найден");
            }

            $response = $this->makeTerminalRequest(
                $terminal['ip_address'],
                $terminal['port'],
                'actualize',
                ['processId' => $payment['process_id']]
            );

            $this->log("Актуализация выполнена", [
                'payment_id' => $payment['id'],
                'response' => $response
            ]);

        } catch (Exception $e) {
            $this->log("Ошибка актуализации", [
                'payment_id' => $payment['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    // Основной метод обработки
    public function processPayments()
    {
        $this->log("Запуск обработки платежей");

        try {
            // 1. Обрабатываем новые платежи в очереди
            $queue_query = "SELECT * FROM kaspi_payment_queue 
                           WHERE status = 'queue' 
                           AND retry_count < max_retries
                           ORDER BY priority ASC, date_created ASC
                           LIMIT 10";

            $result = $this->db->query($queue_query);

            while ($payment = $result->fetch_assoc()) {
                $terminal = $this->getFreeTerminal($payment['amount_total']);

                if ($terminal) {
                    $this->createPaymentOnTerminal($terminal, $payment);
                } else {
                    $this->log("Нет свободных терминалов для платежа", [
                        'payment_id' => $payment['id'],
                        'amount' => $payment['amount_total']
                    ]);
                }
            }

            // 2. Проверяем статус активных платежей
            $active_query = "SELECT * FROM kaspi_payment_queue 
                            WHERE status = 'waiting_payment' 
                            AND process_id IS NOT NULL";

            $active_result = $this->db->query($active_query);

            while ($payment = $active_result->fetch_assoc()) {
                $this->checkPaymentStatus($payment);
            }

            // 3. Очищаем истекшие платежи
            $expired_query = "UPDATE kaspi_payment_queue SET 
                             status = 'expired',
                             date_completed = NOW(),
                             error_message = 'Время ожидания истекло'
                             WHERE status IN ('queue', 'waiting_payment') 
                             AND expires_at < NOW()";

            $this->db->query($expired_query);

            // 4. Освобождаем терминалы от истекших операций
            $free_terminals_query = "UPDATE kaspi_terminals kt
                                    SET status = 'free', last_operation_id = NULL
                                    WHERE status = 'busy' 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM kaspi_payment_queue kpq 
                                        WHERE kpq.terminal_id = kt.id 
                                        AND kpq.status = 'waiting_payment'
                                    )";

            $this->db->query($free_terminals_query);

            $this->log("Обработка платежей завершена");

            return [
                'type' => true,
                'msg' => 'Обработка завершена',
                'logs' => $this->log_messages
            ];

        } catch (Exception $e) {
            $this->log("Критическая ошибка обработки", ['error' => $e->getMessage()]);

            return [
                'type' => false,
                'msg' => 'Ошибка обработки: ' . $e->getMessage(),
                'logs' => $this->log_messages
            ];
        }
    }
}

// Запуск обработчика
try {
    $processor = new KaspiPaymentProcessor($db);
    $result = $processor->processPayments();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $response = [
        'type' => false,
        'msg' => 'Ошибка инициализации процессора: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>