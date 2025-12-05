<?php
if (empty($_COOKIE['lang'])) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    if (empty($lang)) {
        $lang = 'ru';
    }

    $cookie_name = "lang";
    $cookie_value = $langs;
    $cookie_time = time() + (365 * 24 * 60 * 60);
    setcookie($cookie_name, $cookie_value, $cookie_time, "/");
} else {
    $lang = $_COOKIE['lang'];
}

if ($lang != 'ru' && $lang != 'kk' && $lang != 'en') {
    $lang = 'en';
}


if (empty($_GET['lang']) == false) {
    $cookie_name = "lang";
    $cookie_value = $_GET['lang'];
    $cookie_time = time() + (365 * 24 * 60 * 60);
    setcookie($cookie_name, $cookie_value, $cookie_time, "/");
    $lang = $_GET['lang'];
}




include($dir . 'config.php');
if (empty($_GET['agent'])) {
    $_GET['agent'] = 0;
} else {
    $searchAgent = $db->query("SELECT * FROM users WHERE id='" . $_GET['agent'] . "'");
    if ($searchAgent->num_rows > 0) {
        $searchAgent = $searchAgent->fetch_assoc();
        if ($searchAgent['blocked_to_time'] != null) {
            $_GET['agent'] = 0;
        } else {
            $_GET['agent'] = $searchAgent['id'];
        }
    } else {
        $_GET['agent'] = 0;
    }
}
?>