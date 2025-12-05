<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


function sendFileByUrl($chatId, $fileUrl, $fileName, $caption)
{
    $url = "https://7103.api.greenapi.com/waInstance7103957708/sendFileByUrl/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";

    $payload = [
        "chatId" => $chatId,
        "urlFile" => $fileUrl,
        "fileName" => $fileName,
        "caption" => $caption
    ];

    $headers = [
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Если HTTP-код 200, значит отправка успешна
    if ($httpCode == 200) {
        return json_encode(["type" => true]);
    } else {
        return json_encode(["type" => false, "error" => $response]);
    }
}

$listPlanDB = $db->query("SELECT * FROM agents_content_plan WHERE link_video IS NOT NULL AND date_create >= '" . date('Y-m-d') . " 00:00:00' AND date_create < '" . date('Y-m-d') . " 23:59:00' AND whatsapp_public ='0'");
while ($listPlan = $listPlanDB->fetch_assoc()) {
    $listChatUserDB = $db->query("SELECT * FROM user_whatsapp_groups WHERE user_id='" . $listPlan['user_id'] . "'");
    while ($listChatUser = $listChatUserDB->fetch_assoc()) {
        $send = sendFileByUrl($listChatUser['group_id'], $listPlan['link_video'], date('YmdHis') . '.mp4', $listPlan['title']);
        sleep(2);
    }

    $db->query("UPDATE agents_content_plan SET whatsapp_public='1' WHERE id='" . $listPlan['id'] . "'");

}
?>