<?php
if (empty($_POST['user_id']) == false) {
    $lc = array();
    $listCompanyDB = $db->query("SELECT * FROM user_companies WHERE user_id='" . $_POST['user_id'] . "'");
    while ($listCompany = $listCompanyDB->fetch_assoc()) {
        array_push($lc, $listCompany);
    }
    echo json_encode(
        array(
            "type" => true,
            "data" => $lc,
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty user id...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>