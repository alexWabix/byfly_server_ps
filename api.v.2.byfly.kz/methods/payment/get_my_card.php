<?php
try {
    if (empty($_POST['user_id']) == false && empty($_POST['key']) == false) {
        if ($_POST['key'] == '1977a1e3b1fab8abc3c57592cbb1630797875da52365a81e3a69b91a2b5aeb8b') {
            $arrayListCard = array();
            $listDB = $db->query("SELECT * FROM user_cards WHERE user_id='" . $_POST['user_id'] . "'");
            while ($list = $listDB->fetch_assoc()) {
                array_push($arrayListCard, $list);
            }

            echo json_encode(
                array(
                    "type" => true,
                    "data" => $arrayListCard,
                ),
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => 'Error empty key data...',
                ),
                JSON_UNESCAPED_UNICODE
            );
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Error empty key data...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} catch (\Throwable $th) {
    echo json_encode(
        array(
            "type" => false,
            "msg" => $th->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>