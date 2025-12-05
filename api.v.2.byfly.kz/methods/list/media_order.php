<?php
$orderMedia = array();
$orderMediaDB = $db->query('SELECT * FROM order_media WHERE order_id="' . $_POST['order_id'] . '" ORDER BY id DESC');
while ($media = $orderMediaDB->fetch_assoc()) {
    $media['userInfo'] = $db->query("SELECT * FROM  users WHERE id='" . $media['user_id'] . "'")->fetch_assoc();
    array_push($orderMedia, $media);
}

echo json_encode(
    array(
        "type" => true,
        "data" => $orderMedia,
    ),
    JSON_UNESCAPED_UNICODE
);
?>