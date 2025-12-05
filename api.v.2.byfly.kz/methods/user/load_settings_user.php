<?php
if (empty($_POST['user_id']) == false) {
    $searchUserDB = $db->query("SELECT * FROM users WHERE id='" . $_POST['user_id'] . "'");
    if ($searchUserDB->num_rows > 0) {
        $waGroup = array();
        $userInfo = $searchUserDB->fetch_assoc();

        $waGroupsDB = $db->query("SELECT * FROM user_whatsapp_groups WHERE user_id='" . $_POST['user_id'] . "'");
        while ($wa = $waGroupsDB->fetch_assoc()) {
            $wa['city'] = $db->query("SELECT * FROM departure_citys WHERE id_visor='" . $wa['city_id'] . "'")->fetch_assoc();
            array_push($waGroup, $wa);
        }


        echo json_encode(
            array(
                "type" => true,
                "data" => array(
                    'list_wa_group' => $waGroup,
                    'user_info' => $userInfo,
                ),
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit();
    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'User not found',
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit();
    }
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