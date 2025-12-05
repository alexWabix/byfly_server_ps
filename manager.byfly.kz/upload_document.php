<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function transliterate($text)
{
    $translitTable = [
        'А' => 'A',
        'Б' => 'B',
        'В' => 'V',
        'Г' => 'G',
        'Д' => 'D',
        'Е' => 'E',
        'Ё' => 'E',
        'Ж' => 'Zh',
        'З' => 'Z',
        'И' => 'I',
        'Й' => 'Y',
        'К' => 'K',
        'Л' => 'L',
        'М' => 'M',
        'Н' => 'N',
        'О' => 'O',
        'П' => 'P',
        'Р' => 'R',
        'С' => 'S',
        'Т' => 'T',
        'У' => 'U',
        'Ф' => 'F',
        'Х' => 'Kh',
        'Ц' => 'Ts',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Shch',
        'Ы' => 'Y',
        'Э' => 'E',
        'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'kh',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ы' => 'y',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',
        'ь' => '',
        'ъ' => ''
    ];

    return strtr($text, $translitTable);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];

    if (empty($_POST['order_id']) || empty($_POST['title']) || empty($_FILES['file'])) {
        $response['message'] = 'Недостаточно данных для загрузки документа.';
        echo json_encode($response);
        exit;
    }

    $orderId = intval($_POST['order_id']);
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $showToClient = isset($_POST['show_to_client']) ? 1 : 0;

    $file = $_FILES['file'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        $response['message'] = 'Недопустимый формат файла. Разрешены только: ' . implode(', ', $allowedExtensions);
        echo json_encode($response);
        exit;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Ошибка загрузки файла: ' . $file['error'];
        echo json_encode($response);
        exit;
    }

    $uploadDir = '/var/www/www-root/data/www/manager.byfly.kz/loaded/';
    $originalFileName = pathinfo($file['name'], PATHINFO_FILENAME);
    $transliteratedFileName = transliterate($originalFileName);
    $safeFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $transliteratedFileName);
    $fileName = $safeFileName . '_' . uniqid('', true) . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $response['message'] = 'Не удалось сохранить файл.';
        echo json_encode($response);
        exit;
    }

    $docsLink = 'https://manager.byfly.kz/loaded/' . $fileName;
    $query = "INSERT INTO order_docs (`id`, `docs_link`, `date_create`, `order_id`, `title`, `description`, `show_to_client`) 
              VALUES (NULL, '$docsLink', CURRENT_TIMESTAMP, $orderId, '$title', '$description', $showToClient)";

    if ($db->query($query)) {

        $orderInfo = $db->query("SELECT * FROM order_tours WHERE id='" . $orderId . "'")->fetch_assoc();
        $userInfo = $db->query("SELECT * FROM users WHERE id='" . $orderInfo['user_id'] . "'")->fetch_assoc();

        if ($showToClient) {
            sendWhatsapp($userInfo['phone'], "\uD83D\uDCC4 По вашей заявке №" . $orderInfo['id'] . " загружен новый документ: *" . $title . "*. \uD83D\uDCC4\n\n\uD83D\uDD17 Вы можете скачать его, перейдя по ссылке: " . $docsLink . "\n\nhttps://byfly.kz\n\n❤️ С уважением, ваша команда ByFly Travel.");
        }

        $response['success'] = true;
        $response['title'] = $title;
        $response['description'] = $showToClient ? 'Показывается клиенту' : 'Скрыт от клиента';
        $response['show_to_client'] = $showToClient;
        $response['docs_link'] = $docsLink;
    } else {
        $response['message'] = 'Ошибка базы данных: ' . $db->error;
    }

    echo json_encode($response);
}
