<?php

require_once 'config.php';

require_once 'class.php';
$utils = new Utils;
$downloader = new Downloader();
$setUp = new SetUp();

$attachments = filter_input(INPUT_POST, "atts", FILTER_SANITIZE_STRING);
$time = filter_input(INPUT_POST, "time", FILTER_SANITIZE_STRING);
$hash = filter_input(INPUT_POST, "hash", FILTER_SANITIZE_STRING);
$pass = filter_input(INPUT_POST, "pass", FILTER_SANITIZE_STRING);

if (strlen($pass) > 0) {
    $hpass = md5($pass);
} else {
    $hpass = false;
}

$saveData = array();

$saveData['pass'] = $hpass;

$saveData['time'] = $time;
$saveData['hash'] = $hash;
$saveData['attachments'] = $attachments;

$attachash = md5($time.$attachments.$pass);

// create the temporary directory
if (!is_dir('shorten')) {
    mkdir('shorten', 0755, true);
}
// save dowloadable link if it does not already exists
if (!file_exists('shorten/'.$attachash.'.json') || $pass!==false) {
    $fp = fopen('shorten/'.$attachash.'.json', 'w');
    fwrite($fp, json_encode($saveData));
    fclose($fp);
}


// remove old files
$shortens = glob("shorten/*.json");

foreach ($shortens as $shorten) {
    if (is_file($shorten)) {
        $filetime = filemtime($shorten);

        if ($downloader->checkTime($filetime) == false) {
            unlink($shorten);
        }
    }
}

echo $attachash;