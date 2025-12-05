<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$manager_id = $_POST['manager_id'] ?? '';
$code = $_POST['code'] ?? '';

if (empty($manager_id) || empty($code)) {
    echo json_encode([
        "type" => false,
        "msg" => "Не указаны обязательные параметры"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Проверяем код
    $stmt = $db->prepare("SELECT * FROM manager_two_factor_codes WHERE manager_id = ? AND code = ? AND expires_at > NOW() AND used_at IS NULL");
    $stmt->execute([$manager_id, $code]);
    $codeRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($codeRecord) {
        // Помечаем код как использованный
        $updateStmt = $db->prepare("UPDATE manager_two_factor_codes SET used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$codeRecord['id']]);

        // Получаем данные менеджера
        $managerStmt = $db->prepare("SELECT * FROM managers WHERE id = ?");
        $managerStmt->execute([$manager_id]);
        $manager = $managerStmt->fetch(PDO::FETCH_ASSOC);

        if ($manager) {
            // Обновляем время последнего визита
            $updateVisitStmt = $db->prepare("UPDATE managers SET last_visit = NOW() WHERE id = ?");
            $updateVisitStmt->execute([$manager_id]);

            echo json_encode([
                "type" => true,
                "msg" => "Авторизация успешна",
                "data" => [
                    "manager" => $manager
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "type" => false,
                "msg" => "Менеджер не найден"
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            "type" => false,
            "msg" => "Неверный или просроченный код"
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode([
        "type" => false,
        "msg" => "Ошибка при проверке кода: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>