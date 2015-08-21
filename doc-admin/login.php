<?php

error_reporting(E_ALL ^ E_NOTICE);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require 'config.php';
session_name($_CONFIG["session_name"]);
session_start();

if (isset($_GET['logout'])) {
    unset($_SESSION['doc_admin_name']);
    unset($_SESSION['doc_admin_pass']);
}
require 'users.php';
require 'class.php';

$encodeExplorer = new EncodeExplorer();
$setUp = new SetUp();
$timeconfig = $setUp->getConfig('default_timezone');
$timezone = (strlen($timeconfig) > 0) ? $timeconfig : "UTC";
date_default_timezone_set($timezone);
$template = new Template();
$gateKeeper = new GateKeeper();

$logged = false;
$error = null;
$captchaerror = null;

if (isset( $_SESSION['doc_admin_name'])) {
    $logged = true;
}
$postusername = filter_input(
    INPUT_POST, "doc_admin_name", FILTER_SANITIZE_STRING
);
$postuserpass = filter_input(
    INPUT_POST, "doc_admin_pass", FILTER_SANITIZE_STRING
);


if ($postusername && $postuserpass) {
    if (logIn($postusername, $postuserpass)) {
        $logged = true;
    } else {
        $logged = false;
    }
}
/**
* check log in
*
* @param string $postusername username
* @param string $postuserpass password
*
* @return true/false
*/
function logIn ($postusername, $postuserpass)
{
    global $error;
    global $setUp;

    if ($setUp->getConfig('show_captcha') == true ) {
        global $captchaerror;
        $postcaptcha = filter_input(
            INPUT_POST, "captcha", FILTER_SANITIZE_STRING
        );
        $postcaptcha = strtolower($postcaptcha);

        if ($postcaptcha !== $_SESSION['captcha']
        ) {
            $captchaerror = "Error";
            return false;
        }
    }

    global $_USERS;
    global $_CONFIG;
    foreach ($_USERS as $user) {
        if ($user['name'] == $postusername
            && crypt(
                $_CONFIG['salt'].urlencode($postuserpass),
                $user['pass']
            ) == $user['pass']
            && $user['role'] == 'superadmin'
        ) {
            $_SESSION['doc_admin_name'] = $postusername;
            return true;
        }
    }
    $error = "Error";
    return false;
}

if ($logged) {
    header('Location:index.php');
    exit();
} else { 
    /* *********** GET LANG ************* */
    if ( isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $_GET['lang'];
    }
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    } else {
        $lang = $_CONFIG["lang"];
    }
    include_once 'translations/'.$lang.'.php';
    ?>
    <!DOCTYPE HTML>
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="<?php echo $lang; ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>Login | <?php print $setUp->getConfig('appname'); ?></title>
        <link rel="stylesheet" href="css/bootstrap.min.css">
    <?php 
    if ($setUp->getConfig("txt_direction") == "RTL") { ?>
        <link rel="stylesheet" href="doc-admin/css/bootstrap-rtl.min.css">
    <?php 
    } ?>
        <link rel="stylesheet" href="doc-style.css">
        <link rel="stylesheet" href="skins/<?php print $setUp->getConfig('skin'); ?>">
        <link rel="stylesheet" href="css/font-awesome.min.css">
        <script src="js/jquery-1.11.1.min.js"></script>
        <!--[if lt IE 9]>
        <script src="js/html5.js" type="text/javascript"></script>
        <script src="js/respond.min.js" type="text/javascript"></script>
        <![endif]-->
    </head>
    <body>

    <?php
        $template->getPart('userpanel', '');
        $template->getPart('header', '');
    ?>

    <div class="container">
        <div id="error">
                <?php
    if ($error) {
            print "<div class=\"response nope\">"
            .$encodeExplorer->getString('wrong_pass')."</div>";
    }

    if ($captchaerror) {
            print "<div class=\"response nope\">"
            .$encodeExplorer->getString('wrong_captcha')."</div>";
    }

     ?>
        </div>
        <section class="docblock">
            <div class="login">
                <h2>
                    <i class="fa fa-cogs"></i> 
                    <?php print $encodeExplorer->getString('administration'); ?>
                </h2>
                <form enctype="multipart/form-data" 
                method="post" role="form" 
                action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label class="sr-only" for="doc_user_name">
                            <?php print $encodeExplorer->getString('username'); ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
                            <input type="text" name="doc_admin_name" 
                            value="" class="form-control ricevi1" 
                            placeholder="<?php echo $encodeExplorer->getString('username'); ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="doc_user_pass">
                            <?php print $encodeExplorer->getString('password'); ?>
                        </label>

                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-lock fa-fw"></i></span>
                            <input type="password" name="doc_admin_pass" 
                            class="form-control ricevi2" 
                            placeholder="<?php print $encodeExplorer->getString('password'); ?>" />
                        </div>
                    </div>
            <?php 
            /* ************************ CAPTCHA ************************* */
    if ($setUp->getConfig('show_captcha') == true ) { 
        $capath = "";
        include "include/captcha.php"; 
    }   ?>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block" />
                            <i class="fa fa-sign-in"></i> 
                            <?php print $encodeExplorer->getString('log_in'); ?>
                        </button>
                    </div>
                </form>
                <p><a href="../"><i class="fa fa-home"></i> 
                    <?php print $setUp->getConfig('appname'); ?></a>
                </p>
            </div>
            </section>
        </div>

        <?php $template->getPart('footer', ''); ?>
        
        <script type="text/javascript" src="js/bootstrap.min.js"></script>
    </body>
    </html>
    <?php
}
?>