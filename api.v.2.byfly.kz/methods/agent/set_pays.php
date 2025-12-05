<?php
$price_coach = 400000;
$price_coach_tour = 800000;
$price_coach_online = 1500000;
$price_start = 200000;

$price_coach = $price_coach - $_POST['summ'];
$price_coach_tour = $price_coach_tour - $_POST['summ'];
$price_coach_online = $price_coach_online - $_POST['summ'];
$price_start = $price_start - $_POST['summ'];

if ($db->query("UPDATE users SET price_coach='" . $price_coach . "', price_coach_tour='" . $price_coach_tour . "', price_coach_online='" . $price_coach_online . "', price_start='" . $price_start . "' WHERE id='" . $_POST['agentId'] . "'")) {
    echo json_encode(
        array(
            "type" => true,
            "data" => array(),
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $db->error,
        ),
        JSON_UNESCAPED_UNICODE
    );
}

?>