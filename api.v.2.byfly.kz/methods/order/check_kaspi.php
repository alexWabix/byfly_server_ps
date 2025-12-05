<?php
file_get_contents('http://api.v.2.byfly.kz/cron/check_emails.php');
echo json_encode(
    array(
        "type" => true,
        "data" => '',
    ),
    JSON_UNESCAPED_UNICODE
);
?>