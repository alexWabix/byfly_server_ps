<?php
if (
    $db->query("UPDATE passangers SET 
`passanger_famale` = '" . $_POST['passanger_famale'] . "', 
`passanger_name` = '" . $_POST['passanger_name'] . "', 
`passangers_phone` = '" . $_POST['passangers_phone'] . "', 
`link_from_documents1` = '" . $_POST['link_from_documents1'] . "', 
`link_from_documents2` = '" . $_POST['link_from_documents2'] . "', 
`pasport_link` = '" . $_POST['pasport_link'] . "', 
`number_pasport` = '" . $_POST['number_pasport'] . "', 
`number_udv` = '" . $_POST['number_udv'] . "', 
`iin` = '" . $_POST['iin'] . "', 
`date_berthday` = '" . $_POST['date_berthday'] . "',
`pasport_srok` = '" . $_POST['pasport_srok'] . "', 
`udv_srok` = '" . $_POST['udv_srok'] . "' WHERE id = '" . $_POST['id'] . "'")
) {
    echo json_encode(
        array(
            "type" => true,
            "data" => 'success',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error update passanger information...' . $db->error,
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
?>