<?php
require '/var/www/www-root/data/www/appoffice.kz/vendor/autoload.php';
use SpeechKit\Response\HypothesesList;
use SpeechKit\Response\Hypothesis;
use SpeechKit\Speech\SpeechContent;
use SpeechKit\SpeechKit;
$key = 'AQVNy68-e6DZciebtyBjsAC7yfHk7af3_B-fI7Vu';


$speechKit = new SpeechKit($key);

$source = fopen($outputFile, 'r');

$speech = new SpeechContent($source);

/** @var HypothesesList $result */
$result = $speechKit->recognize($speech);

$text = array();
/** @var Hypothesis $hyphotesis */
foreach ($result as $hyphotesis) {
    $resp = trim(strip_tags($hyphotesis->getContent()));
    $capitalizedString = mb_ucfirst($resp, 'UTF-8');
    $text[] = $capitalizedString;
}

echo json_encode(array('succ' => true, 'text' => $text), JSON_UNESCAPED_UNICODE);
?>