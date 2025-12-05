<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$getToken = file_get_contents('https://graph.facebook.com/v19.0/oauth/access_token?grant_type=fb_exchange_token&client_id=2785618054954170&client_secret=e9a25134f11465bddd5bf77d194c8f07&fb_exchange_token=EAAnlgT6lPLoBO955FZBCpC90LF27ZCoPwntKTZBGevw1EI0JvZCcnZBoBvkyO1jnOPx7s7gOpiFmLPN5ePoyqKZBrVZATZCZBrposYTKyhjMGecbQNp4yogGdJ9iK2PvsrioJKtep1K36QWZCH39WM7dgw978aYmGoWEHz4leLKkMKDZBU5DTSIi7x7JJyCNbVPZCQAB2dzGFx5LCzNDTsMS18PENErXkwZDZD');
if (empty($getToken) == false) {
    $getToken = json_decode($getToken, true);
    if (empty($getToken) == false) {
        $db->query("UPDATE settings SET insta_dev_token='" . $getToken['access_token'] . "' WHERE id='1'");
    }
}

?>