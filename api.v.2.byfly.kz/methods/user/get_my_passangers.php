<?php
if (empty($_POST['delete_passangers']) == false) {
    $db->query("DELETE FROM passangers WHERE id='" . $_POST['delete_passangers'] . "'");
}

if (!empty($_POST['user_id'])) {
    $addQuery = '';
    $listResp = array();

    if (!empty($_POST['text_search']) && mb_strlen($_POST['text_search'], 'utf-8') > 2) {
        $text_search = $db->real_escape_string($_POST['text_search']);

        $addQuery .= " AND (LOWER(passanger_name) LIKE LOWER('%$text_search%') OR LOWER(passanger_famale) LIKE LOWER('%$text_search%'))";
    }

    $query = "SELECT * FROM passangers WHERE from_user_id = ? $addQuery";

    if ($stmt = $db->prepare($query)) {
        $stmt->bind_param("s", $_POST['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($listPassangers = $result->fetch_assoc()) {
            array_push($listResp, $listPassangers);
        }
        $stmt->close();
    } else {
        echo json_encode(array("type" => false, "msg" => "Query preparation failed"));
        exit();
    }

    echo json_encode(
        array(
            "type" => true,
            "data" => array(
                "passangers" => $listResp,
            ),
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit();
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty user id parameters...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit();
}
?>