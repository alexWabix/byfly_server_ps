<?php
if (empty($_POST['id_present']) == false) {
    if ($db->query("UPDATE present_event SET adress='" . $_POST['new_adress'] . "', city='" . $_POST['new_city'] . "' WHERE id='" . $_POST['id_present'] . "'")) {
        echo json_encode(
            array(
                "type" => true,
                "data" => [],
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
            "msg" => 'Empty present id variable...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>