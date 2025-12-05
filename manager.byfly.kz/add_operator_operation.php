<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];

    // Проверка обязательных данных
    if (empty($_POST['order_id']) || empty($_POST['summ']) || !isset($_POST['is_from']) || empty($_POST['description'])) {
        $response['message'] = 'Недостаточно данных для добавления операции.';
        echo json_encode($response);
        exit;
    }

    $orderId = intval($_POST['order_id']);
    $summ = floatval($_POST['summ']);
    $isFrom = intval($_POST['is_from']);
    $description = htmlspecialchars(trim($_POST['description']));

    $documentPath = '';
    if (!empty($_FILES['document']['name'])) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $fileExtension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            $response['message'] = 'Недопустимый формат файла. Разрешены только: ' . implode(', ', $allowedExtensions);
            echo json_encode($response);
            exit;
        }

        if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Ошибка загрузки файла: ' . $_FILES['document']['error'];
            echo json_encode($response);
            exit;
        }

        $uploadDir = '/var/www/www-root/data/www/manager.byfly.kz/loaded/';
        $fileName = uniqid('', true) . '.' . $fileExtension;
        $documentPath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['document']['tmp_name'], $documentPath)) {
            $response['message'] = 'Не удалось сохранить файл.';
            echo json_encode($response);
            exit;
        }

        $documentPath = 'https://manager.byfly.kz/loaded/' . $fileName;
    }

    // Вставка данных в базу
    $query = "INSERT INTO order_tour_operators (order_id, summ, is_from, description, date_create, document) VALUES (?, ?, ?, ?, NOW(), ?)";
    $stmt = $db->prepare($query);

    if ($stmt) {
        $stmt->bind_param('idiss', $orderId, $summ, $isFrom, $description, $documentPath);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['id'] = $stmt->insert_id;
            $response['summ'] = number_format($summ, 2, '.', ' ');
            $response['is_from'] = $isFrom;
            $response['description'] = $description;
            $response['date_create'] = date('d.m.Y H:i');
            $response['document'] = $documentPath;
        } else {
            $response['message'] = 'Ошибка базы данных: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Ошибка подготовки запроса: ' . $db->error;
    }

    echo json_encode($response);
}

$db->close();
?>