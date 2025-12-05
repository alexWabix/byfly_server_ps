<?php
if (empty($_POST['user_id']) == false && empty($_POST['company_name']) == false && empty($_POST['company_bin']) == false && empty($_POST['company_adress']) == false) {
    if ($db->query("INSERT INTO user_companies (`id`, `user_id`, `company_name`, `company_adress`, `date_create`, `bin_iin`) VALUES (NULL, '" . $_POST['user_id'] . "', '" . $_POST['company_name'] . "', '" . $_POST['company_adress'] . "', CURRENT_TIMESTAMP, '" . $_POST['company_bin'] . "');")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => 'Company add success!',
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
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty company info...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>