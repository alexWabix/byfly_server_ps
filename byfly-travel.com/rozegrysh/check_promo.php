<?php
include('includes/config.php');
include('includes/functions.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code'])) {
    $promoCode = trim($_POST['promo_code']);
    $result = checkPromoCode($promoCode);

    echo json_encode($result);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Неверный запрос'
    ]);
}
?>