<?php

require 'config.php';
session_name($_CONFIG["session_name"]);
session_start();
header("Expires: Tue, 01 Jan 2013 00:00:00 GMT"); 
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$randomString = '';

for ($i = 0; $i < 5; $i++) {
    $randomString .= $chars[rand(0, strlen($chars)-1)];
}
$_SESSION['captcha'] = strtolower($randomString);
//$_SESSION['captcha'] = $randomString;
$img = @imagecreatefrompng("captcha/captcha_bg_sm.png"); 
imagettftext($img, 26, 0, 14, 28, imagecolorallocate($img, 0, 0, 0), 'captcha/broken15.ttf', $randomString);
header('Content-type: image/png');
imagepng($img, null, 0);
imagedestroy($img);