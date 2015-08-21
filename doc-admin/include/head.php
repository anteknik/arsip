<?php

require_once 'doc-admin/config.php';
require_once 'doc-admin/users.php';
require_once 'doc-admin/doc-icons.php';
require_once 'doc-admin/class.php';

if (!empty($_SERVER['HTTPS']) 
    && $_SERVER['HTTPS'] !== 'off'
    || $_SERVER['SERVER_PORT'] == 443
) {
    $http = "https://";
} else {
    $http = "http://";
}
$actual_link = $http.$_SERVER['HTTP_HOST'].strtok($_SERVER["REQUEST_URI"], '?');

$resetusr = false;

if (strlen($_CONFIG['session_name']) < 5
    || strlen($_CONFIG['salt']) < 5 
) {
    if (strlen($_CONFIG['session_name']) < 5) {
        $session = "doc_".strval(mt_rand());
        $_CONFIG['session_name'] = $session;
    }

    if (strlen($_CONFIG['salt']) < 5 
    ) {
        $_CONFIG['salt'] = md5(mt_rand());
    }
    $resetusr = true;
    $con = '$_CONFIG = ';
    file_put_contents(
        'doc-admin/config.php', "<?php\n\n $con".var_export($_CONFIG, true).";\n"
    );
}
if ((isset($_CONFIG['script_url']) && $_CONFIG['script_url'] !== $actual_link)
    || !isset($_CONFIG['script_url'])
) {
    $_CONFIG['script_url'] = $actual_link;
    $con = '$_CONFIG = ';
    file_put_contents(
        'doc-admin/config.php', "<?php\n\n $con".var_export($_CONFIG, true).";\n"
    );
}

if (strlen($_USERS[0]['pass']) < 1 || $resetusr == true) {
    $reset = crypt($_CONFIG['salt'].urlencode('password'), Utils::randomString());
    $_USERS[0]['pass'] = $reset;

    $usr = '$_USERS = ';
    file_put_contents(
        'doc-admin/users.php', "<?php\n\n $usr".var_export($_USERS, true).";\n"
    );
}

global $_ERROR;
global $_WARNING;
global $_SUCCESS;

global $_IMAGES;
global $_USERS;
global $_DLIST;

require_once 'doc-admin/remember.php';
global $_REMEMBER;
$cookies = new Cookies();

$encodeExplorer = new EncodeExplorer();
$encodeExplorer->init();

require_once 'doc-admin/translations/'.$encodeExplorer->lang.'.php';
global $_TRANSLATIONS;

$gateKeeper = new GateKeeper();
$gateKeeper->init();
$setUp = new SetUp();
$location = new Location();
$location->init();
$downloader = new Downloader();
$updater = new Updater();
$updater->init();
$template = new Template();

$timeconfig = $setUp->getConfig('default_timezone');
$timezone = (strlen($timeconfig) > 0) ? $timeconfig : "UTC";
date_default_timezone_set($timezone);

require_once 'doc-admin/token.php';
global $_TOKENS;
$resetter = new Resetter();
$resetter->init();

if ($gateKeeper->isAccessAllowed()) {
    $fileManager = new FileManager();
    $fileManager->run($location);
    $encodeExplorer->run($location);
};

unset($_SESSION['upcoda']);
$_SESSION['upcoda'] = array();

unset($_SESSION['uplist']);
$_SESSION['uplist'] = array();

if (!isset($_GET['response'])) {
    if (isset($_ERROR) && strlen($_ERROR) > 0 ) {
        $_SESSION['error'] = $_ERROR;
    }
    if (isset($_SUCCESS) && strlen($_SUCCESS) > 0 ) {
        $_SESSION['success'] = $_SUCCESS;
    }
    if (isset($_WARNING) && strlen($_WARNING) > 0 ) {
        $_SESSION['warning'] = $_WARNING;
    }
} 

if (isset($_SESSION['error'])) {
    $_ERROR = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $_SUCCESS = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['warning'])) {
    $_WARNING = $_SESSION['warning'];
    unset($_SESSION['warning']);
}

if (isset($_SESSION['doc_dlist'])) {
    $_DLIST = $_SESSION['doc_dlist'];
} else {
    $_DLIST = $setUp->getConfig('folderdeforder');
}

$uid = md5(uniqid(mt_rand()));

$getrp = filter_input(INPUT_GET, "rp", FILTER_SANITIZE_STRING);
$getusr = filter_input(INPUT_GET, "usr", FILTER_SANITIZE_STRING);
$getfilelist = filter_input(INPUT_GET, "dl", FILTER_SANITIZE_STRING);