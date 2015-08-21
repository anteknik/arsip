<?php

require 'mail/PHPMailerAutoload.php';
require 'config.php';
require 'users.php';
require 'token.php';
require 'class.php';
session_name($_CONFIG["session_name"]);
session_start();
$lang = filter_input(INPUT_POST, 'thislang', FILTER_SANITIZE_STRING);
require 'translations/'.$lang.'.php';
$setUp = new SetUp();
$utils = new Utils();
$updater = new Updater();
$resetter = new Resetter();
$encodeExplorer = new EncodeExplorer();
$dest = filter_input(INPUT_POST, "user_email", FILTER_VALIDATE_EMAIL);
$pulito = filter_input(INPUT_POST, 'cleanurl', FILTER_SANITIZE_STRING);
$postcaptcha = filter_input(INPUT_POST, "captcha", FILTER_SANITIZE_STRING);

global $_USERS;
global $_TOKENS;

if (!$dest || ($setUp->getConfig("show_captcha_reset") == true && !$postcaptcha)) {
    print "<div class=\"alert alert-warning\">".$encodeExplorer->getString("fill_all_fields")."</div>";
    exit();
}
if (Utils::checkCaptchaReset($postcaptcha) !== true) {
    print "<div class=\"alert alert-danger\">".$encodeExplorer->getString("wrong_captcha")."</div>";
    exit();
}

if (!$updater->findEmail($dest)) {
    print "<div class=\"alert alert-danger\">".$encodeExplorer->getString("email_not_exist")."</div>";
    exit();
}
if (!$resetter->setToken($dest)) {
    print "<div class=\"alert alert-danger\">Error: token not set</div>";
    exit();

}
$token = $resetter->setToken($dest);

$setfrom = $setUp->getConfig('email_from');
$mail = new PHPMailer();
$mail->CharSet = 'UTF-8';

if ($setUp->getConfig('smtp_enable') == true) {
    
    $timeconfig = $setUp->getConfig('default_timezone');
    $timezone = (strlen($timeconfig) > 0) ? $timeconfig : "UTC";
    date_default_timezone_set($timezone);

    $mail->isSMTP();
    $mail->SMTPDebug = 0;   // enables SMTP debug information (for testing)
                            // 1 = errors and messages
                            // 2 = messages only

    $smtp_auth = $setUp->getConfig('smtp_auth');

    $mail->Host = $setUp->getConfig('smtp_server');
    $mail->Port = (int)$setUp->getConfig('port');

    if ($setUp->getConfig('secure_conn') !== "none") {
        $mail->SMTPSecure = $setUp->getConfig('secure_conn');
    }
    
    $mail->SMTPAuth = $smtp_auth;

    if ($smtp_auth == true) {
        $mail->Username = $setUp->getConfig('email_login');
        $mail->Password = $setUp->getConfig('email_pass');
    }
}
$mail->setFrom($setfrom, $setUp->getConfig('appname'));
$mail->addAddress($dest, '<'.$dest.'>');

$mail->Subject = $setUp->getConfig('appname').": ".$encodeExplorer->getString('reset_password');

$altmessage = $encodeExplorer->getString('someone_requested_pwd_reset_1').": ".$token['name']."/n"
    .$encodeExplorer->getString('someone_requested_pwd_reset_2')."\n"
    .$encodeExplorer->getString('someone_requested_pwd_reset_3')."\n"
    .$pulito.$token['tok'];

$mail->AddEmbeddedImage('mail/mail-logo.png', 'logoimg', 'mail/mail-logo.png');

// Retrieve the email template required
$message = file_get_contents('mail/template-reset-password.html');

// Replace the % with the actual information
$message = str_replace('%app_url%', $pulito, $message);
$message = str_replace('%app_name%', $setUp->getConfig('appname'), $message);

$message = str_replace(
    '%translate_someone_requested_pwd_reset_1%', 
    $encodeExplorer->getString('someone_requested_pwd_reset_1'), $message
);
$message = str_replace(
    '%translate_someone_requested_pwd_reset_2%', 
    $encodeExplorer->getString('someone_requested_pwd_reset_2'), $message
);
$message = str_replace(
    '%translate_someone_requested_pwd_reset_3%', 
    $encodeExplorer->getString('someone_requested_pwd_reset_3'), $message
);

$message = str_replace('%translate_username%', $encodeExplorer->getString('username'), $message);
$message = str_replace('%username%', $token['name'], $message);
$message = str_replace('%translate_reset_password%', $encodeExplorer->getString('reset_password'), $message);
$message = str_replace('%tok%', $pulito.$token['tok'], $message);

$mail->msgHTML($message);

$mail->AltBody = $altmessage;

if (!$mail->send()) {
    echo "<div class=\"alert alert-danger\">Mailer Error: " . $mail->ErrorInfo."</div>";
} else {
    print "<div class=\"alert alert-success\">".$encodeExplorer->getString('message_sent').": " .$dest."</div>";
}