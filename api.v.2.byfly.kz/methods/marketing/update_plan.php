<?php
if ($db->query("UPDATE agents_content_plan SET " . $_POST['column'] . "='" . $_POST['data'] . "' WHERE id='" . $_POST['id'] . "' AND user_id='" . $_POST['user_id'] . "'")) {
    echo json_encode(
        array(
            "type" => true,
            "data" => 'IS UPDATED',
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