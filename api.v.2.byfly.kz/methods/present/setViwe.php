<?php
if (empty($_POST['present_id']) == false) {
    if ($db->query("UPDATE present_event SET showToClient='" . $_POST['show'] . "' WHERE id='" . $_POST['present_id'] . "'")) {
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
            "msg" => "Не указан ID презентации",
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>