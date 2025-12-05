<?php

if (empty($_POST['group_id']) == false) {
    if ($db->query("DELETE FROM grouped_coach WHERE id='" . $_POST['group_id'] . "'")) {
        echo json_encode(['type' => true, 'data' => []], );
    } else {
        echo json_encode(['type' => false, 'msg' => $db->error], );
    }
} else {
    echo json_encode(['type' => false, 'msg' => 'В параметрах отсутствует ID группы...'], );
}