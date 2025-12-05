<?php
if (empty($_POST['id']) == false) {
    if ($db->query("DELETE FROM present_event_rashod WHERE id='" . $_POST['id'] . "'")) {
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
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty id tranzaction variable...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>