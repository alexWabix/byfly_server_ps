<?php
$dir = '/var/www/www-root/data/www/api.v.2.byfly.kz/';
$dirSuite = '/var/www/www-root/data/www/byfly.kz/';
include($dirSuite . 'includes/start.php');
$userAgent = $_SERVER['HTTP_USER_AGENT'];
function isMobile($userAgent)
{
	$mobileAgents = [
		'iPhone',
		'Android',
		'webOS',
		'BlackBerry',
		'iPod',
		'Opera Mini',
		'IEMobile'
	];

	foreach ($mobileAgents as $agent) {
		if (stripos($userAgent, $agent) !== false) {
			return true;
		}
	}
	return false;
}

if (isMobile($userAgent)) {
    $queryParams = $_SERVER['QUERY_STRING'];
    $redirectUrl = 'https://app2.0.byfly-travel.com';
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . $queryParams;
    }
    header('Location: ' . $redirectUrl);
    exit;
} else {
    include($dirSuite . 'web.php');
}
?>