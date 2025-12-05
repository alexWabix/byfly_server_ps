<?php
if ($db->query("UPDATE users SET defoult_nakrutka='" . $_POST['defoult_nakrutka'] . "', search_nakrutka='" . $_POST['search_nakrutka'] . "', show_my_data='" . $_POST['show_my_data'] . "', latter_my_contacts='" . $_POST['latter_my_contacts'] . "', latter_is_me='" . $_POST['latter_is_me'] . "', show_clear_nakrutka='" . $_POST['show_clear_nakrutka'] . "' WHERE id='" . $_POST['id'] . "'")) {
    echo json_encode(
        array(
            "type" => true,
            "data" => getUserInfoFromID($_POST['id']),
        ),
        JSON_UNESCAPED_UNICODE
    );
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Не удалось обновить данные',
        ),
        JSON_UNESCAPED_UNICODE
    );
}
?>