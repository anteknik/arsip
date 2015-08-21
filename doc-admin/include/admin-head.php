<?php

$encodeExplorer = new EncodeExplorer();
$setUp = new SetUp();
$updater = new Updater();

$timeconfig = $setUp->getConfig('default_timezone');
$timezone = (strlen($timeconfig) > 0) ? $timeconfig : "UTC";
date_default_timezone_set($timezone);

global $baselang;
$baselang = $_TRANSLATIONS;

$posteditlang = filter_input(
    INPUT_POST, "editlang", FILTER_SANITIZE_STRING
);
$postnewlang = filter_input(
    INPUT_POST, "newlang", FILTER_SANITIZE_STRING
);
$thelang = ($posteditlang ? $posteditlang : "en");
$thenewlang = ($postnewlang ? $postnewlang : null);
$editlang = ($thenewlang ? $thenewlang : $thelang);

global $_TRANSLATIONSEDIT;

if ($posteditlang) {
    include 'translations/'.$editlang.'.php';
    $_TRANSLATIONSEDIT = $_TRANSLATIONS;
} else {
    $_TRANSLATIONSEDIT = $baselang;
}
/**
* Get lang
*/
if ( isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $_GET['lang'];
}
if (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} else {
    $lang = $_CONFIG["lang"];
}
require 'translations/'.$lang.'.php';

global $translations;
$translations = $encodeExplorer->getLanguages();
?>

<?php
$activesec = "home";

/**
* update LANG
*/
if (isset($_GET['languagemanager'])) {

    $activesec = "lang";

    if ($_GET['languagemanager'] == 'update') {
        $postnewlang = filter_input(INPUT_POST, "thenewlang", FILTER_SANITIZE_STRING);
        $getremove = filter_input(INPUT_GET, "remove", FILTER_SANITIZE_STRING);

        if ( $postnewlang && strlen($postnewlang) == 2 || $getremove ) {

            if ($getremove) {

                $thelang = $getremove;
                $langtoremove = "translations/".$thelang.".php";

                if (!unlink($langtoremove)) {
                    $success = 'language "'.$thelang.'" does not exists';
                    $status = 'nope';
                } else {
                    $success = $encodeExplorer->getString("language_removed");
                    $status = 'yep';
                }

            } else {

                $thelang = $postnewlang;

                if (in_array($thelang, $translations)) {
                    foreach ($baselang as $key => $value) {

                        $postkey = filter_input(
                            INPUT_POST, $key, FILTER_SANITIZE_STRING
                        );
            
                        $_TRANSLATIONSEDIT[$key] = $postkey;
                    }
                    $success = $encodeExplorer->getString("language_updated");
                    $status = 'yep';
                } else {
                    $newlang = array();
                    foreach ($baselang as $key => $value) {

                        $postkey = filter_input(
                            INPUT_POST, $key, FILTER_SANITIZE_STRING
                        );
                        $newlang[$key] = $postkey;
                    }
                    $_TRANSLATIONSEDIT = array_merge($_TRANSLATIONSEDIT, $newlang);
                    $success = $encodeExplorer->getString("language_added");
                    $status = 'yep';
                }


                $trans = '$_TRANSLATIONS = ';
                if ( false == (file_put_contents(
                    'translations/'.$thelang.'.php',
                    "<?php\n\n $trans".var_export($_TRANSLATIONSEDIT, true).";\n"
                ))
                ) {
                    $success = 'error';
                    $status = 'nope';
                }
            }
        }
    }

} elseif (isset($_GET['users'])) {

    $activesec = "users";

    /**
    * Update USERS
    */
    global $_USERS;
    global $users;
    $users = $_USERS;

    /**
    * update users file
    *
    * @return file updated
    *
    */
    function updateUsers()
    {
        global $success;
        global $status;
        global $_USERS;
        global $users;
        global $encodeExplorer;

        $usrs = '$_USERS = ';

        if ( false == (file_put_contents(
            'users.php', "<?php\n\n $usrs".var_export($users, true).";\n"
        ))
        ) {
            $success = 'error';
            $status = 'nope';
        } else {
            $_USERS = $users;
            $success = $encodeExplorer->getString("users_updated");
            $status = 'yep';
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if ($_GET['users'] == "new") {
            $postnewusername = filter_input(
                INPUT_POST, "newusername", FILTER_SANITIZE_STRING
            );
            $postnewuserpass = filter_input(
                INPUT_POST, "newuserpass", FILTER_SANITIZE_STRING
            );
            
            $postnewuserfolder = filter_input(
                INPUT_POST, "newuserfolder", FILTER_SANITIZE_STRING
            );
            
            $newquota = filter_input(
                INPUT_POST, "quota", FILTER_SANITIZE_STRING
            );


            $newuserfolders = false;
            
            if (isset($_POST['newuserfolders'])
                || $postnewuserfolder
            ) {

                $newuserfolders = array();

                if (isset($_POST['newuserfolders'])) {
                    $newuserfolders = $_POST['newuserfolders'];
                }
            }

            $postnewusermail = filter_input(
                INPUT_POST, "newusermail", FILTER_VALIDATE_EMAIL
            );

            if ($postnewusername  || $postnewuserpass) {
                if (!$postnewusername || !$postnewuserpass ) {
                    $success = $encodeExplorer->getString("indicate_username_and_password_for_new_user");
                    $status = 'nope'; 
                } else {

                    $users = $_USERS;
                    
                    if (!$updater->findUser($postnewusername)
                        && !$updater->findEmail($postnewusermail)
                    ) {
                        
                        $newuser = array();
                        $salt = $setUp->getConfig('salt');
                        $newuserpass = crypt($salt.urlencode($postnewuserpass), Utils::randomString());
                     
                        $newuser['name'] = $postnewusername;
                        $newuser['pass'] = $newuserpass;
                        $newuser['role'] = $_POST['newrole'];

                        if ($postnewuserfolder) {

                            if (!file_exists(
                                ".".$setUp->getConfig('starting_dir').$postnewuserfolder
                            )) {
                                mkdir(".".$setUp->getConfig('starting_dir').$postnewuserfolder);
                            }
                            if (!in_array($postnewuserfolder, $newuserfolders)) {
                                array_push($newuserfolders, $postnewuserfolder);
                            }
                        }

                        if ($newuserfolders) {
                            $newuser['dir'] = json_encode($newuserfolders);
                        }

                        if ($newquota) {
                            $newuser['quota'] = $newquota;
                        }

                        if ($postnewusermail) {
                            $newuser['email'] = $postnewusermail;

                            //
                            // send new user nofication
                            //
                            $mailsystem = $setUp->getConfig('email_from');

                            if (isset($_POST['usernotif']) && strlen($mailsystem) > 4) {
                                include_once 'mail/PHPMailerAutoload.php';

                                $setfrom = $mailsystem;

                                $mail = new PHPMailer();
                                $mail->CharSet = 'UTF-8';

                                if ($setUp->getConfig('smtp_enable') == true) {
                                    
                                    $timeconfig = $setUp->getConfig('default_timezone');
                                    $timezone = (strlen($timeconfig) > 0) ? $timeconfig : "UTC";
                                    date_default_timezone_set($timezone);

                                    $mail->isSMTP();
                                    $mail->SMTPDebug = 0;

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

                                $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
                                $url .= $_SERVER['HTTP_HOST'];
                                $url .= htmlspecialchars($_SERVER['PHP_SELF']);
                                $pulito = dirname($url);
                                $pulito = str_replace('/doc-admin', '', $pulito);

                                $mail->setFrom($setfrom, $setUp->getConfig('appname'));
                                $mail->addAddress($newuser['email'], '<'.$newuser['email'].'>');

                                $mail->Subject = $setUp->getConfig('appname').": ".$encodeExplorer->getString('new_user');

                                // alt message
                                $altmessage = $pulito."\r\n"
                                ."A new user has been created\r\n"
                                ."username: ".$newuser['name']."\r\n"
                                .$encodeExplorer->getString('password').":".$postnewuserpass;

                                $mail->AddEmbeddedImage('mail/mail-logo.png', 'logoimg', 'mail/mail-logo.png');

                                // Retrieve the email template required
                                $message = file_get_contents('mail/template-new-user.html');

                                // Replace the % with the actual information
                                $message = str_replace('%app_name%', $setUp->getConfig('appname'), $message);
                                $message = str_replace('%app_url%', $pulito, $message);

                                $message = str_replace(
                                    '%translate_new_user_has_been_created%', 
                                    $encodeExplorer->getString('new_user_has_been_created'), $message
                                );

                                $message = str_replace('%translate_username%', $encodeExplorer->getString('username'), $message);
                                $message = str_replace('%username%', $newuser['name'], $message);

                                $message = str_replace('%translate_password%', $encodeExplorer->getString('password'), $message);
                                $message = str_replace('%password%', $postnewuserpass, $message);

                                $mail->msgHTML($message);

                                $mail->AltBody = $altmessage;

                                if (!$mail->send()) {
                                    $success = '<strong>Mailer Error:</strong> '.$mail->ErrorInfo;
                                    $status = 'nope';
                                }
                            }
                        }

                        array_push($users, $newuser);
                        updateUsers();

                    } else {
                        if ($updater->findUser($postnewusername)) {
                            $colpevole = $postnewusername;
                        }
                        if ($updater->findEmail($postnewusermail)) {
                            $colpevole = $postnewusermail;
                        }

                        $success = '<strong>'.$colpevole.'</strong> '.$encodeExplorer->getString("file_exists");
                        $status = 'nope';
                    }
                }

            } 

        }
        if ($_GET['users'] == "updatemaster") {

            $blockup = false; 
            $blockupmail = false; 

            $postusernameold = filter_input(
                INPUT_POST, "masterusernameold", FILTER_SANITIZE_STRING
            );
            $postusername = filter_input(
                INPUT_POST, "masterusername", FILTER_SANITIZE_STRING
            );
            $postuserpassnew = filter_input(
                INPUT_POST, "masteruserpassnew", FILTER_SANITIZE_STRING
            );
            $postuserpass = filter_input(
                INPUT_POST, "masteruserpass", FILTER_SANITIZE_STRING
            );

            $postusermailold = filter_input(
                INPUT_POST, "masterusermailold", FILTER_VALIDATE_EMAIL
            );
            $postusermail = filter_input(
                INPUT_POST, "masterusermail", FILTER_VALIDATE_EMAIL
            );

            if ($postusername) {

                if ($postuserpassnew) {
                    $updater->updateUserPwd($postusernameold, $postuserpassnew);
                } 

                if ($postusername !== $postusernameold) {
                    if ($updater->findUser($postusername)) {
                        $blockup = true;
                    } else {
                        $updater->updateUserData($postusernameold, 'name', $postusername);
                    }
                }

                if ($postusermail !== $postusermailold) {
                    if ($updater->findEmail($postusermail)) {
                        $blockupmail = true; 
                    } else {
                        $updater->updateUserData($postusernameold, 'email', $postusermail);
                    }
                }

                if ($blockup == true || $blockupmail == true) {
                    if ($blockup == true) {
                        $success = $encodeExplorer->getString("file_exists");
                    }
                    if ($blockupmail == true) {
                        $success = $encodeExplorer->getString("email_in_use");
                    }
                    $status = 'nope';
                } else {
                    updateUsers();
                }
            }
        
        }


        if ($_GET['users'] == "update") {

            $blockup = false; 
            $blockupmail = false; 

            $postusernameold = filter_input(
                INPUT_POST, "usernameold", FILTER_SANITIZE_STRING
            );
            $postusername = filter_input(
                INPUT_POST, "username", FILTER_SANITIZE_STRING
            );
            $postuserpassnew = filter_input(
                INPUT_POST, "userpassnew", FILTER_SANITIZE_STRING
            );
            $postuserpass = filter_input(
                INPUT_POST, "userpass", FILTER_SANITIZE_STRING
            );
            $postuserfolder = filter_input(
                INPUT_POST, "userfolder", FILTER_SANITIZE_STRING
            );
            $quota = filter_input(
                INPUT_POST, "quota", FILTER_SANITIZE_STRING
            );
            $role = filter_input(
                INPUT_POST, "role", FILTER_SANITIZE_STRING
            );
            $delme = filter_input(
                INPUT_POST, "delme", FILTER_SANITIZE_STRING
            );

            if ($delme == $postusernameold) {
                $updater->deleteUser($postusernameold);
                updateUsers();
                $success = '<strong>'.$postusernameold.'</strong> deleted';
                $status = 'nope'; 
            } else {

                $userfolders = false;

                if (isset($_POST['userfolders'])
                    || $postuserfolder
                ) {
                    $userfolders = array();

                    if (isset($_POST['userfolders'])) {
                        $userfolders = $_POST['userfolders'];
                    }
                }

                $postusermailold = filter_input(
                    INPUT_POST, "usermailold", FILTER_VALIDATE_EMAIL
                );
                $postusermail = filter_input(
                    INPUT_POST, "usermail", FILTER_VALIDATE_EMAIL
                );

                if ($postusername) {

                    if ($postuserpassnew) {
                        $updater->updateUserPwd($postusernameold, $postuserpassnew);
                    } 

                    if ($postusername !== $postusernameold) {
                        if ($updater->findUser($postusername)) {
                            $blockup = true;
                        } else {
                            $updater->updateUserData($postusernameold, 'name', $postusername);
                        }
                    }

                    if ($postusermail !== $postusermailold) {
                        if ($updater->findEmail($postusermail)) {
                            $blockupmail = true; 
                        } else {
                            $updater->updateUserData($postusernameold, 'email', $postusermail);
                        }
                    }

                    if ($postuserfolder) {

                        if (!file_exists(
                            ".".$setUp->getConfig('starting_dir').$postuserfolder
                        )) {
                            mkdir(".".$setUp->getConfig('starting_dir').$postuserfolder);
                        }
                        if (!in_array($postuserfolder, $userfolders)) {
                            array_push($userfolders, $postuserfolder);
                        }
                    }

                    $userfolders = $userfolders ? json_encode($userfolders) : $userfolders;

                    $updater->updateUserData($postusernameold, 'quota', $quota);
                    $updater->updateUserData($postusernameold, 'dir', $userfolders);

                    if ($blockup == true || $blockupmail == true) {
                        if ($blockup == true) {
                            $success = $encodeExplorer->getString("file_exists");
                        }
                        if ($blockupmail == true) {
                            $success = $encodeExplorer->getString("email_in_use");
                        }
                        $status = 'nope';
                    } else {
                        $updater->updateUserData($postusernameold, 'role', $role);
                        updateUsers();
                    }
                }
            }
        }

        
    } 

} elseif (isset($_GET['log'])) {

    $activesec = "log";

} else {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $con = '$_CONFIG = ';

        /**
        * Upload LOGO
        */
        if (isset($_FILES['file']['name'])) {
            if (get_magic_quotes_gpc()) {
                $name = stripslashes($_FILES['file']['name']);
            } else {
                $name = $_FILES['file']['name']; 
            }
            $allowedExts = array("gif", "jpeg", "jpg", "png");
            $temp = explode(".", $name);
            $extension = end($temp);

            if ((($_FILES["file"]["type"] == "image/gif")
                || ($_FILES["file"]["type"] == "image/jpeg")
                || ($_FILES["file"]["type"] == "image/jpg")
                || ($_FILES["file"]["type"] == "image/pjpeg")
                || ($_FILES["file"]["type"] == "image/x-png")
                || ($_FILES["file"]["type"] == "image/png"))
                && in_array($extension, $allowedExts)
            ) {
                if ($_FILES["file"]["error"] > 0) {
                    $success = 'Invalid file';
                    $status = 'nope';
                } else {
                    move_uploaded_file(
                        $_FILES["file"]["tmp_name"], "images/" . $name
                    );
                    $success = 'image uploaded';
                    $status = 'yep';
                    $_CONFIG['logo'] = $name;
                }
            } else {
                $success = 'Invalid file';
                $status = 'nope';
            }

            if ( false == (@file_put_contents(
                'config.php', "<?php\n\n $con".var_export($_CONFIG, true).";\n"
            ))
            ) {
                $success = 'error';
                $status = 'nope';
            }

        } else {

            /**
            * General Settings
            */
            $_CONFIG['log_file'] = (isset(
                $_POST['log_file']) ? true : false
            );

            $_CONFIG['enable_prettylinks'] = (isset(
                $_POST['enable_prettylinks']) ? true : false
            );

            $postappname = filter_input(
                INPUT_POST, "appname", FILTER_SANITIZE_STRING
            );

            $postdescription = filter_input(
                INPUT_POST, "description", FILTER_SANITIZE_STRING
            );

            $postlogo = filter_input(
                INPUT_POST, "logo", FILTER_SANITIZE_STRING
            );
            $postupload_reject_extension = filter_input(
                INPUT_POST, "upload_reject_extension", FILTER_SANITIZE_STRING
            );

            $postthumbw = filter_input(
                INPUT_POST, "thumbnails_width", FILTER_VALIDATE_INT
            );
            $postthumbh = filter_input(
                INPUT_POST, "thumbnails_height", FILTER_VALIDATE_INT
            );
            
            $postuploademail = filter_input(
                INPUT_POST, "upload_email", FILTER_VALIDATE_EMAIL
            );

            $txtdir = filter_input(
                INPUT_POST, "txt_direction", FILTER_SANITIZE_STRING
            );
            
            $poststartingdir = filter_input(
                INPUT_POST, "starting_dir", FILTER_SANITIZE_STRING
            );

            $progressColor = filter_input(
                INPUT_POST, "progressColor", FILTER_SANITIZE_STRING
            );

            $timezone = filter_input(
                INPUT_POST, "default_timezone", FILTER_SANITIZE_STRING
            );

            $_CONFIG['default_timezone'] = ($timezone ? $timezone : "UTC");

            $_CONFIG['progress_color'] = ($progressColor ? $progressColor : "");

            $_CONFIG['appname'] = $postappname;

            $_CONFIG['description'] = $postdescription;

            $_CONFIG['logo'] = $postlogo;

            $_CONFIG['align_logo'] = (isset(
                $_POST['align_logo']) ? $_POST['align_logo'] : "left"
            );

            $_CONFIG['show_head'] = (isset(
                $_POST['show_head']) ? true : false
            );
            $_CONFIG['upload_reject_extension'] = explode(
                ",", $postupload_reject_extension
            );
            $_CONFIG['preloader'] = "XMLHttpRequest";

            $_CONFIG['skin'] = (isset(
                $_POST['skin']) ? $_POST['skin'] : $_CONFIG['skin']
            );
            $_CONFIG['lang'] = $_POST['lang'];

            $_CONFIG['time_format'] = $_POST['time_format']." - H:i";

            $_CONFIG['require_login'] = (isset(
                $_POST['require_login']) ? true : false
            );
            $_CONFIG['show_path'] = (isset(
                $_POST['show_path']) ? true : false
            );
            $_CONFIG['show_langmenu'] = (isset(
                $_POST['show_langmenu']) ? true : false
            );

            $_CONFIG['show_captcha'] = (isset(
                $_POST['show_captcha']) ? true : false
            );
            $_CONFIG['show_captcha_reset'] = (isset(
                $_POST['show_captcha_reset']) ? true : false
            );
            
            $_CONFIG['show_usermenu'] = (isset(
                $_POST['show_usermenu']) ? true : false
            );
            $_CONFIG['playmusic'] = (isset(
                $_POST['playmusic']) ? true : false
            );

            $_CONFIG['thumbnails'] = (isset(
                $_POST['thumbnails']) ? true : false
            );
            $_CONFIG['inline_thumbs'] = (isset(
                $_POST['inline_thumbs']) ? true : false
            );
            $_CONFIG['thumbnails_width'] = (int) $postthumbw;
            $_CONFIG['thumbnails_height'] = (int) $postthumbh;
            $_CONFIG['upload_enable'] = (
                isset($_POST['upload_enable']) ? true : false
            );
            $_CONFIG['newdir_enable'] = (
                isset($_POST['newdir_enable']) ? true : false
            );
            $_CONFIG['delete_enable'] = (
                isset($_POST['delete_enable']) ? true : false
            );
            $_CONFIG['rename_enable'] = (
                isset($_POST['rename_enable']) ? true : false
            );
            $_CONFIG['upload_email'] = $postuploademail;

            $_CONFIG['txt_direction'] = $txtdir;

            $_CONFIG['show_pagination'] = (isset(
                $_POST['show_pagination']) ? true : false
            );

            $filedefnum = filter_input(
                INPUT_POST, "filedefnum", FILTER_VALIDATE_INT
            );
            $_CONFIG['filedefnum'] = ($filedefnum ? $filedefnum : 10);

            $_CONFIG['filedeforder'] = (isset(
                $_POST['filedeforder']) ? $_POST['filedeforder'] : "date"
            );
            
            $folderdefnum = filter_input(
                INPUT_POST, "folderdefnum", FILTER_VALIDATE_INT
            );
            $_CONFIG['folderdefnum'] = ($folderdefnum ? $folderdefnum : 10);

            $_CONFIG['folderdeforder'] = (isset(
                $_POST['folderdeforder']) ? $_POST['folderdeforder'] : "date"
            );

            $_CONFIG['show_pagination_num'] = (isset(
                $_POST['show_pagination_num']) ? true : false
            );
            $_CONFIG['show_pagination_num_folder'] = (isset(
                $_POST['show_pagination_num_folder']) ? true : false
            );
            $_CONFIG['show_pagination_folders'] = (isset(
                $_POST['show_pagination_folders']) ? true : false
            );
            $_CONFIG['show_search'] = (isset(
                $_POST['show_search']) ? true : false
            );
            $_CONFIG['sendfiles_enable'] = (isset(
                $_POST['sendfiles_enable']) ? true : false
            );
            $_CONFIG['lifetime'] = (isset(
                $_POST['lifetime']) ?  (int) $_POST['lifetime'] : 1
            );
            $_CONFIG['secure_sharing'] = (isset(
                $_POST['secure_sharing']) ? true : false
            );
            
            if ($_CONFIG['starting_dir'] != "./".$poststartingdir."/") {
                if (strlen($poststartingdir) == 0) {
                         $_CONFIG['starting_dir'] = "./";
                } else {

                    if (file_exists(".".$_CONFIG['starting_dir']) 
                        && !file_exists("../".$poststartingdir."/")
                    ) {
                        if (!rename(
                            ".".$_CONFIG['starting_dir'], "../".$poststartingdir."/"
                        )) {
                            $success = 'error renaming uploads directory';
                            $status = 'nope';
                        } else {
                            $_CONFIG['starting_dir'] = "./".$poststartingdir."/";
                        }
                    } else {
                         $_CONFIG['starting_dir'] = "./".$poststartingdir."/";
                    }
                }
            }
            $_CONFIG['max_zip_files'] = (int) $_POST['max_zip_files'];
            
            $_CONFIG['max_zip_filesize'] = (int) $_POST['max_zip_filesize'];

            $_CONFIG['show_percentage'] = (isset(
                $_POST['show_percentage']) ? true : false
            );
            $_CONFIG['single_progress'] = (isset(
                $_POST['single_progress']) ? true : false
            );
            $_CONFIG['notify_login'] = (isset(
                $_POST['notify_login']) ? true : false
            );
            $_CONFIG['notify_upload'] = (isset(
                $_POST['notify_upload']) ? true : false
            );
            $_CONFIG['notify_download'] = (isset(
                $_POST['notify_download']) ? true : false
            );
            $_CONFIG['notify_newfolder'] = (isset(
                $_POST['notify_newfolder']) ? true : false
            );

            /**
            * Mail setup
            */
            $_CONFIG['smtp_enable'] = (isset(
                $_POST['smtp_enable']) ? true : false
            );
            $_CONFIG['smtp_auth'] = (isset(
                $_POST['smtp_auth']) ? true : false
            );

            $email_from = filter_input(
                INPUT_POST, "email_from", FILTER_VALIDATE_EMAIL
            );
            $_CONFIG['email_from'] = ($email_from ? $email_from : '');

            $smtp_server = filter_input(
                INPUT_POST, "smtp_server", FILTER_SANITIZE_STRING
            );
            $_CONFIG['smtp_server'] = ($smtp_server ? $smtp_server : '');

            $port = filter_input(
                INPUT_POST, "port", FILTER_VALIDATE_INT
            );
            $_CONFIG['port'] = ($port ? $port : '');

            $_CONFIG['secure_conn'] = $_POST['secure_conn'];

            $email_login = filter_input(
                INPUT_POST, "email_login", FILTER_SANITIZE_STRING
            );
            $_CONFIG['email_login'] = $email_login;

            $email_pass = filter_input(
                INPUT_POST, "email_pass", FILTER_SANITIZE_STRING
            );

            if ($_CONFIG['smtp_enable'] == true && $_CONFIG['smtp_auth'] == true) {
                if (array_key_exists('email_pass', $_CONFIG)) {
                    $_CONFIG['email_pass'] = ($email_pass ? $email_pass : $setUp->getConfig('email_pass'));
                } else {
                    $_CONFIG['email_pass'] = ($email_pass ? $email_pass : '');
                }
            } else {
                $_CONFIG['email_pass'] = '';
            }

            if ( false == (file_put_contents(
                'config.php', "<?php\n\n $con".var_export($_CONFIG, true).";\n"
            ))
            ) {
                $success = 'Error';
                $status = 'nope';
            } else {
                $success = $encodeExplorer->getString("settings_updated");
                $status = 'yep';
            }
        }
    }
} ?>