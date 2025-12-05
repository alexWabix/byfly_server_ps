<?php
if (empty($_POST['user_id']) == false) {
    $ceils = array();
    $listCeilsDB = $db->query("SELECT * FROM copilka_ceils WHERE user_id='" . $_POST['user_id'] . "' ORDER BY id DESC");
    while ($listCeils = $listCeilsDB->fetch_assoc()) {
        array_push($ceils, $listCeils);
    }
    echo json_encode(
        array(
            "type" => true,
            "data" => $ceils,
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty user_id...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>