<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];

    // Получение и проверка ID операции
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id'])) {
        $response['message'] = 'Не передан ID операции.';
        echo json_encode($response);
        exit;
    }

    $operationId = intval($input['id']);

    // Получение информации о операции для удаления файла, если он есть
    $operationQuery = "SELECT document FROM order_tour_operators WHERE id = ?";
    $stmt = $db->prepare($operationQuery);

    if ($stmt) {
        $stmt->bind_param('i', $operationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $operation = $result->fetch_assoc();
        $stmt->close();

        if ($operation) {
            // Удаление записи из базы данных
            $deleteQuery = "DELETE FROM order_tour_operators WHERE id = ?";
            $stmt = $db->prepare($deleteQuery);

            if ($stmt) {
                $stmt->bind_param('i', $operationId);

                if ($stmt->execute()) {
                    // Удаление файла документа, если он существует
                    if (!empty($operation['document'])) {
                        $documentPath = str_replace('https://manager.byfly.kz/', '/var/www/www-root/data/www/manager.byfly.kz/', $operation['document']);
                        if (file_exists($documentPath)) {
                            unlink($documentPath);
                        }
                    }

                    $response['success'] = true;
                    $response['message'] = 'Операция успешно удалена.';
                } else {
                    $response['message'] = 'Ошибка при удалении операции: ' . $stmt->error;
                }

                $stmt->close();
            } else {
                $response['message'] = 'Ошибка подготовки запроса: ' . $db->error;
            }
        } else {
            $response['message'] = 'Операция с указанным ID не найдена.';
        }
    } else {
        $response['message'] = 'Ошибка подготовки запроса: ' . $db->error;
    }

    echo json_encode($response);
}

$db->close();
?>