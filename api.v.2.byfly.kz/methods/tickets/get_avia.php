<?php
$searchedID = $db->query("SELECT * FROM tickets_serarch WHERE id = '" . $_POST['id'] . "'");
if ($searchedID->num_rows > 0) {
    $searchInfo = $searchedID->fetch_assoc();

    if ($searchInfo['status'] == 0) {
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "proccess" => 0,
                    "tickets" => array(),
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else if ($searchInfo['status'] == 200) {
        $ticketsArr = array();
        $tickestsDB = $db->query("SELECT * FROM avia_tickets WHERE search_id='" . $_POST['id'] . "'");
        while ($tickests = $tickestsDB->fetch_assoc()) {
            $tickests['stopDetails'] = explode(',', $tickests['stopDetails']);
            $tickests['fareFeatures'] = explode(',', $tickests['fareFeatures']);

            array_push($ticketsArr, $tickests);
        }
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "proccess" => 100,
                    "tickets" => $ticketsArr,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    } else if ($searchInfo['status'] == 500) {
        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    "proccess" => 100,
                    "tickets" => array(),
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty tickets search from id...',
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

?>