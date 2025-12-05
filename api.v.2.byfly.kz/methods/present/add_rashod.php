<?php
if ($db->query("INSERT INTO present_event_rashod (`id`, `summ`, `title`, `file`, `date_create`, `present_id`) VALUES (NULL, '" . $_POST['summ'] . "', '" . $_POST['name'] . "', '" . $_POST['file'] . "', CURRENT_TIMESTAMP, '" . $_POST['id'] . "');")) {
    echo json_encode(
        array(
            "type" => true,
            "data" => $db->query("SELECT * FROM present_event_rashod WHERE id='" . $db->insert_id . "'")->fetch_assoc(),
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