<?php
if (
    $db->query("INSERT INTO passangers (`id`, `passanger_famale`, `passanger_name`, `grazhdanstvo`, `date_berthday`, `date_apend`, `passangers_phone`, `from_user_id`, `pasport_link`, `number_pasport`, `iin`, `pasport_srok`, `isChildren`) VALUES (NULL, '" . $_POST['famale'] . "', '" . $_POST['userName'] . "', '" . $_POST['grazhdanstvo'] . "', '" . $_POST['berthday'] . "', CURRENT_TIMESTAMP, '" . $_POST['phone'] . "', '" . $_POST['user_id'] . "', '" . $_POST['pasport1'] . "', '" . $_POST['number_passport'] . "', '" . $_POST['iin'] . "', '" . $_POST['passpordSrokGodnosti'] . "', '" . $_POST['children'] . "');")
) {
    echo json_encode(
        array(
            "type" => true,
            "data" => array(),
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Error add passanger... ' . $db->error,
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

?>