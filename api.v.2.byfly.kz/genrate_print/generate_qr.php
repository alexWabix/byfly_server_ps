<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;

$size = empty($_GET['size']) ? 300 : $_GET['size'];
$padding = empty($_GET['padding']) ? 5 : $_GET['padding'];
$color = empty($_GET['color']) ? 'dark' : $_GET['color'];

$background = array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0); // Прозрачный фон
$foreground = array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0); // Белые пиксели по умолчанию

if (empty($_GET['group']) == false) {
    $_GET['data'] = $_GET['group'];
} else {
    $_GET['data'] = 'https://byfly.kz/?type=qr&agent=' . $_GET['data'];
}

if (empty($_GET['data']) == false) {
    $qrCode = new QrCode();
    $qrCode
        ->setText($_GET['data'])
        ->setSize($size)
        ->setPadding($padding)
        ->setErrorCorrection('high')
        ->setForegroundColor($foreground)  // Цвет пикселей
        ->setBackgroundColor($background)
        ->setImageType(QrCode::IMAGE_TYPE_PNG);

    header('Content-Type: ' . $qrCode->getContentType());
    $qrCode->render();
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => "Отсутствуют данные!"
        )
    );
}
