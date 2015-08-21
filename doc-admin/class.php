<?php

class ImageServer
{
    /**
    * Checks if an image is requested and displays one if needed
    *
    * @return true/false
    */
    public static function showImage()
    {
        if (isset($_GET['thumb'])) {

            $inline = (isset($_GET['in']) ? true : false);

            if (strlen($_GET['thumb']) > 0
                && (SetUp::getConfig('thumbnails') == true
                || SetUp::getConfig('inline_thumbs') == true)
            ) {
                ImageServer::showThumbnail($_GET['thumb'], $inline);
            }
            return true;
        }
        return false;
    }

    /**
    * Checks if isEnabledPdf()
    *
    * @return true/false
    */
    public static function isEnabledPdf()
    {
        /**
        *  if you wish to try .pdf quick preview
        *  (you need Imagick on your server)
        *  uncomment the following if statement line
        */
        // if (class_exists("Imagick")) {
        //     return true;
        // }
        return false;
    }

    /**
    * Preapre PDF for thumbnail
    *
    * @param string $file the file to convert
    *
    * @return null/$im2
    */
    public static function openPdf($file)
    {
        if (!ImageServer::isEnabledPdf()) {
            return false;
        }
        $img = new Imagick($file.'[0]');
        $img->setImageFormat("png");
        $str = $img->getImageBlob();
        $im2 = imagecreatefromstring($str);
        return $im2;
    }

    /**
    * Creates and returns a thumbnail image object from an image file
    *
    * @param string  $file   file to convert
    * @param boolean $inline thumbs or zoom
    *
    * @return null/$new_image
    */
    public static function createThumbnail($file, $inline = false)
    {

        if ($inline == true) {

            $max_width = 100;
            $max_height = 200;

        } else {
            if (is_int(SetUp::getConfig('thumbnails_width'))) {
                $max_width = SetUp::getConfig('thumbnails_width');
            } else {
                $max_width = 200;
            }
            if (is_int(SetUp::getConfig('thumbnails_height'))) {
                $max_height = SetUp::getConfig('thumbnails_height');
            } else {
                $max_height = 200;
            }
        }

        if (File::isPdfFile($file)) {
            $image = ImageServer::openPdf($file);
        } else {
            $image = ImageServer::openImage($file);
        }

        if ($image == false) {
            return;
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        $new_width = $max_width;
        $new_height = $max_height;
        if (($width/$height) > ($new_width/$new_height)) {
            $new_height = $new_width * ($height / $width);
        } else {
            $new_width = $new_height * ($width / $height);
        }

        if ($new_width >= $width && $new_height >= $height) {
            $new_width = $width;
            $new_height = $height;
        }

        $new_image = ImageCreateTrueColor($new_width, $new_height);
        imagealphablending($new_image, true);
        imagesavealpha($new_image, true);
        $trans_colour = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
        imagefill($new_image, 0, 0, $trans_colour);

        imagecopyResampled(
            $new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height
        );
        return $new_image;
    }
    
    /**
    * convert M K G in bytes
    *
    * @param string $size_str original size
    *
    * @return converted size
    */
    public static function returnBytes ($size_str)
    {
        switch(substr($size_str, -1)) {
        case 'M': case 'm': 
            return (int)$size_str * 1048576;
        case 'K': case 'k': 
            return (int)$size_str * 1024;
        case 'G': case 'g': 
            return (int)$size_str * 1073741824;
        default: 
            return $size_str;
        }
    }

    /**
    * Function for displaying the thumbnail.
    * Includes attempts at cacheing it so that generation is minimised.
    *
    * @param string  $file   file to convert
    * @param boolean $inline thumbs or zoom
    *
    * @return $image
    */
    public static function showThumbnail($file, $inline = false)
    {

        $file = EncodeExplorer::extraChars($file);

        if (filemtime($file) < filemtime($_SERVER['SCRIPT_FILENAME'])) {
            $mtime = gmdate('r', filemtime($_SERVER['SCRIPT_FILENAME']));
        } else {
            $mtime = gmdate('r', filemtime($file));
        }
        $etag = md5($mtime.$file);

        if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $mtime)
            || (isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && str_replace(
                '"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])
            ) == $etag)
        ) {
            header('HTTP/1.1 304 Not Modified');
            return;
        } else {
            header('ETag: "'.$etag.'"');
            header('Last-Modified: '.$mtime);
            header('Content-Type: image/png');

            $image = ImageServer::createThumbnail($file, $inline);

            imagepng($image);
            imagedestroy($image);
        }
    }
    /**
    * A helping function for opening different types of image files
    *
    * @param string $file the file to convert
    *
    * @return $img
    */
    public static function openImage($file)
    {

        $imageInfo = getimagesize($file); 
        $memoryNeeded = (($imageInfo[0] * $imageInfo[1]) * $imageInfo['bits']);
        $memoryLimit = (strlen(ini_get('memory_limit')) > 0 ? ImageServer::returnBytes(ini_get('memory_limit')) : false);

        if ($memoryLimit && $memoryNeeded > $memoryLimit) {
            $img = imagecreatefromjpeg("doc-admin/images/placeholder.jpg");
        } else {
            switch ($imageInfo["mime"]) {
            case "image/jpeg":
                $img = imagecreatefromjpeg($file);
                break;
            case "image/gif":
                $img = imagecreatefromgif($file);
                break;
            case "image/png":
                $img = imagecreatefrompng($file);
                break;
            default:
                $img = null;
                break;
            }
        }
        return $img;
    }
}

/**
 * The class for logging user activity
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Logger
{
    /**
    * Print log file
    *
    * @param string $message the message to log
    * @param string $relpath relative path of log file
    *
    * @return $message
    */
    public static function log($message, $relpath = "doc-admin/")
    {
        if (SetUp::getConfig('log_file') == true) {
            $logjson = $relpath."log/".date("Y-m-d").".json";

            if (Location::isFileWritable($logjson)) {

                $message['time'] = date("H:i:s");

                if (file_exists($logjson)) {
                    $oldlog = json_decode(file_get_contents($logjson), true);
                } else {
                    $oldlog = array();
                }

                $daily = date("Y-m-d");
                $oldlog[$daily][] = $message;

                file_put_contents($logjson, json_encode($oldlog, JSON_FORCE_OBJECT));

            } else {
                Utils::setError("The script does not have permissions to write inside \"doc-admin/log\" folder. check CHMOD");
                return;
            }
        }
    }
    /**
    * log user login
    *
    * @return $message
    */
    public static function logAccess()
    {
        $message = "<td>"
        .GateKeeper::getUserInfo('name')."</td><td>
        <span class=\"label label-warning\">ACCESS</span></td><td>";
        $message .= "--";
        $message .= "</td><td class=\"wordbreak\">--</td>";
        Logger::log($message);
    }

    /**
    * log user creation of folders and files
    *
    * @param string $path  the path to set
    * @param string $isDir may be "dir" or "file"
    *
    * @return $message
    */
    public static function logCreation($path, $isDir)
    {
        $path = addslashes($path);

        $message = array(
            'user' => GateKeeper::getUserInfo('name'), 
            'action' => 'ADD', 
            'type' => $isDir ? 'folder':'file',
            'item' => $path
        );
        Logger::log($message);
        if (!$isDir && SetUp::getConfig("notify_upload")) {
            Logger::emailNotification($path, 'upload');
        }
        if ($isDir && SetUp::getConfig("notify_newfolder")) {
            Logger::emailNotification($path, 'newdir');
        }
    }

    /**
    * log user deletion of folders and files
    *
    * @param string  $path   the path to set
    * @param boolean $isDir  may be true or false
    * @param boolean $remote may be true or false
    *
    * @return $message
    */
    public static function logDeletion($path, $isDir, $remote = false)
    {
        $path = addslashes($path);
        $message = array(
            'user' => GateKeeper::getUserInfo('name'), 
            'action' => 'REMOVE', 
            'type' => $isDir ? 'folder':'file',
            'item' => $path
        );
        if ($remote == false) {
            Logger::log($message);
        } else {
            Logger::log($message, "");
        }
    }
    
    /**
    * log download of single files
    *
    * @param string $path the path to set
    *
    * @return $message
    */
    public static function logDownload($path)
    {
        $path = addslashes($path);
        $message = array(
            'user' =>  GateKeeper::getUserInfo('name') ? GateKeeper::getUserInfo('name') : '--', 
            'action' => 'DOWNLOAD', 
            'type' => 'file',
            'item' => $path
        );
        Logger::log($message, "");
        if (SetUp::getConfig("notify_download")) {
            Logger::emailNotification($path, 'download');
        }
    }

    /**
    * log play of single track
    *
    * @param string $path the path to set
    *
    * @return $message
    */
    public static function logPlay($path)
    {
        $path = addslashes($path);
        $message = array(
            'user' =>  GateKeeper::getUserInfo('name') ? GateKeeper::getUserInfo('name') : '--', 
            'action' => 'PLAY', 
            'type' => 'file',
            'item' => $path
        );
        Logger::log($message, "");
    }

    /**
    * send email notfications for uploading
    *
    * @param string $path   the path to set
    * @param string $action may be "download" | "upload" | "newdir" | "login"
    *
    * @return $message
    */
    public static function emailNotification($path, $action = false)
    {
        global $encodeExplorer;

        $path = Utils::normalizeStr($path);
        if (strlen(SetUp::getConfig('upload_email')) > 5) {

            $time = SetUp::formatModTime(time());
            $appname = SetUp::getConfig('appname');
            switch ($action) {
            case "download":
                $title = $encodeExplorer->getString("new_download");
                break;
            case "upload":
                $title = $encodeExplorer->getString("new_upload");
                break;
            case "newdir":
                $title = $encodeExplorer->getString("new_directory");
                break;
            case "login":
                $title = $encodeExplorer->getString("new_access");
                break;
            default:
                $title = $encodeExplorer->getString("new_activity");
                break;
            }
            $message = $time."\n\n";
            $message .= "IP   : ".$_SERVER['REMOTE_ADDR']."\n";
            $message .= $encodeExplorer->getString("user")." : ".GateKeeper::getUserInfo('name')."\n";
            $message .= $encodeExplorer->getString("path")." : ".$path."\n";

    // send to multiple recipients
            // $sendTo = SetUp::getConfig('upload_email').",cc1@example.com,cc2@example.com";
            $sendTo = SetUp::getConfig('upload_email');

            mail(
                $sendTo, $title, $message,
                "Content-type: text/plain; charset=UTF-8\r\n".
                "From: ".$appname." <noreply@{$_SERVER['SERVER_NAME']}>\r\n".
                "Reply-To: ".$appname." <noreply@{$_SERVER['SERVER_NAME']}>"
            );
        }
    }

}
/**
 * The class controls single user update panel
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Updater
{
    /**
    * call update user functions
    *
    * @return $message
    */
    public static function init()
    {
        global $updater;

        $posteditname = filter_input(
            INPUT_POST, "user_new_name", FILTER_SANITIZE_STRING
        );
        $postoldname = filter_input(
            INPUT_POST, "user_old_name", FILTER_SANITIZE_STRING
        );
        $posteditpass = filter_input(
            INPUT_POST, "user_new_pass", FILTER_SANITIZE_STRING
        );
        $posteditpasscheck = filter_input(
            INPUT_POST, "user_new_pass_confirm", FILTER_SANITIZE_STRING
        );
        $postoldpass = filter_input(
            INPUT_POST, "user_old_pass", FILTER_SANITIZE_STRING
        );
        $posteditmail = filter_input(
            INPUT_POST, "user_new_email", FILTER_VALIDATE_EMAIL
        );
        $postoldmail = filter_input(
            INPUT_POST, "user_old_email", FILTER_VALIDATE_EMAIL
        );

        if ($postoldpass && $posteditname) {
            $updater->updateUser(
                $posteditname, 
                $postoldname, 
                $posteditpass, 
                $posteditpasscheck, 
                $postoldpass, 
                $posteditmail, 
                $postoldmail
            );
        }
    }

    /**
    * Update username or password
    *
    * @param string $posteditname      new username
    * @param string $postoldname       current username
    * @param string $posteditpass      new password
    * @param string $posteditpasscheck check password
    * @param string $postoldpass       old password
    * @param string $posteditmail      new email
    * @param string $postoldmail       old email
    *
    * @return global $users updated
    */
    public function updateUser(
        $posteditname, 
        $postoldname, 
        $posteditpass, 
        $posteditpasscheck, 
        $postoldpass, 
        $posteditmail, 
        $postoldmail
    ) {
        global $encodeExplorer;
        global $updater;
        global $_USERS;
        global $users;
        $users = $_USERS;
        $passa = true;

        if (GateKeeper::isUser($postoldname, $postoldpass)) {

            if ($posteditname != $postoldname) {

                if ($updater->findUser($posteditname)) {
                        Utils::setError(
                            "<strong>".$posteditname."</strong> "
                            .$encodeExplorer->getString("file_exists")
                        );
                        $passa = false;
                        return;   
                }
                $updater->updateUserData($postoldname, 'name', $posteditname);
            }

            if ($posteditmail != $postoldmail) {
                if ($updater->findEmail($posteditmail)) {
                        Utils::setError(
                            "<strong>".$posteditmail."</strong> "
                            .$encodeExplorer->getString("file_exists")
                        );
                        $passa = false;
                        return;   
                }
                $updater->updateUserData($postoldname, 'email', $posteditmail);

            }
                
            if ($posteditpass) {
                if ($posteditpass === $posteditpasscheck ) {
                    $updater->updateUserPwd($postoldname, $posteditpass);

                } else {
                    $encodeExplorer->setErrorString("wrong_pass");
                    $passa = false;
                    return;
                }
            }

            if ($passa == true) {
                $updater->updateUserFile($posteditname);
            }

        } else {
            $encodeExplorer->setErrorString("wrong_pass");
        }

    }

    /**
    * Update user password
    *
    * @param string $checkname  username
    * @param string $changepass new pass
    *
    * @return global $users updated
    */
    public function updateUserPwd($checkname, $changepass)
    {
        global $_USERS;
        global $users;
        $utenti = $_USERS;

        foreach ($utenti as $key => $value) {
            if ($value['name'] === $checkname) {
                $salt = SetUp::getConfig('salt');
                $users[$key]['pass'] = crypt($salt.urlencode($changepass), Utils::randomString());
                break;
            }
        }
    }

    /**
    * Update user data
    *
    * @param string $checkname username to find
    * @param string $type      info to change
    * @param string $changeval new value
    *
    * @return global $users updated
    */
    public function updateUserData($checkname, $type, $changeval)
    {
        global $_USERS;
        global $users;
        $utenti = $_USERS;

        foreach ($utenti as $key => $value) {
            if ($value['name'] === $checkname) {
                if ($changeval) {
                    $users[$key][$type] = $changeval;
                } else {
                    unset($users[$key][$type]);
                }
                break;
            }
        }
    }

    /**
    * Delete user
    *
    * @param string $checkname username to find
    *
    * @return global $users updated
    */
    public function deleteUser($checkname)
    {
        global $_USERS;
        global $users;
        $utenti = $_USERS;

        foreach ($utenti as $key => $value) {
            if ($value['name'] === $checkname) {
                unset($users[$key]);
                break;
            }
        }
    }
    /**
    * Look if email exists
    *
    * @param string $userdata email to look for
    *
    * @return true/false
    */
    public function findEmail($userdata)
    {
        global $_USERS;
        $utenti = array();
        $utenti = $_USERS;
        
        if (is_array($utenti)) {
            foreach ($utenti as $value) {
                if (isset($value['email']) && $value['email'] === $userdata) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
    * Look if user exists
    *
    * @param string $userdata username to look for
    *
    * @return true/false
    */
    public function findUser($userdata)
    {
        global $_USERS;
        $utenti = array();
        $utenti = $_USERS;

        foreach ($utenti as $value) {
            if ($value['name'] === $userdata) {
                return true;         
            }
        }
        return false;
    }

    /**
    * Update users file
    *
    * @param string $posteditname new user name
    *
    * @return response
    */
    public function updateUserFile($posteditname = "")
    {
        global $encodeExplorer;
        global $users;
        $usrs = '$_USERS = ';

        if ( false == (file_put_contents(
            'doc-admin/users.php', "<?php\n\n $usrs".var_export($users, true).";\n"
        ))
        ) {
            Utils::setError("error");
        } else {
            if ($posteditname == "password") {
                Utils::setSuccess($encodeExplorer->getString("password_reset"));   
            } else {
                Utils::setSuccess(
                    "<strong>".$posteditname."</strong> "
                    .$encodeExplorer->getString("updated")
                );
            }
            $_SESSION['vfm_user_name'] = null;
            $_SESSION['vfm_logged_in'] = null;
            session_destroy();
        }
    }
}

/**
 * The class controls cookies for remember me
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Cookies
{
    /**
    * set remember me cookie
    *
    * @param string $postusername user name
    *
    * @return cookie and key set
    */
    public function setCookie($postusername = false)
    {
        global $_REMEMBER;

        $rewrite = false;
        $salt = SetUp::getConfig("salt");
        $rmsha = md5($salt.sha1($postusername.$salt));
        $rmshaved = md5($rmsha);

        setcookie("rm", $rmsha, time()+ (60*60*24*365));
        setcookie("vfm_user_name", $postusername, time()+ (60*60*24*365));

        if (array_key_exists($postusername, $_REMEMBER)
            && $_REMEMBER[$postusername] !== $rmshaved
        ) {
            $rewrite = true;
        }

        if (!array_key_exists($postusername, $_REMEMBER)
            || $rewrite == true
        ) {
            $_REMEMBER[$postusername] = $rmshaved;
            $rmb = '$_REMEMBER = ';
            if (false == (file_put_contents(
                'doc-admin/remember.php', "<?php\n\n $rmb".var_export($_REMEMBER, true).";\n"
            ))
            ) {
                Utils::setError("error setting your remember key");
                return false;
            }
        }

    }


    /**
    * check remember me key
    *
    * @param string $name user name
    * @param string $key  rememberme key
    *
    * @return login via cookie
    */
    public function checkKey($name, $key)
    {
        global $_REMEMBER;
        
        if (array_key_exists($name, $_REMEMBER)) {
            if ($_REMEMBER[$name] === md5($key)) {
                $_SESSION['vfm_user_name'] = $name;
                $_SESSION['vfm_logged_in'] = 1;
            }
        }
        return false;
    }

    /**
    * check rememberme cookie
    *
    * @return checkKey() | false
    */
    public function checkCookie()
    {
        global $cookies;

        if (isset($_COOKIE['rm']) && isset($_COOKIE['vfm_user_name'])) {
            $name = $_COOKIE['vfm_user_name'];
            $key = $_COOKIE['rm'];
            return $cookies->checkKey($name, $key);
        }
        return false;
    }


}

/**
 * The class controls logging in and authentication
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class GateKeeper
{
    /**
    * check user satus
    *
    * @return $message
    */
    public static function init()
    {
        global $encodeExplorer;
        global $gateKeeper;
        global $cookies;

        if (isset($_GET['logout'])) {
            setcookie("rm", "", time() -(60*60*24*365));
            $_SESSION['vfm_user_name'] = null;
            $_SESSION['vfm_logged_in'] = null;
            $_SESSION['vfm_user_used'] = null;
            $_SESSION['vfm_dlist'] = null;
            // session_destroy();
        } else {
            $cookies->checkCookie();
        }

        $postusername = filter_input(
            INPUT_POST, "user_name", FILTER_SANITIZE_STRING
        );
        $postuserpass = filter_input(
            INPUT_POST, "user_pass", FILTER_SANITIZE_STRING
        );
        $postcaptcha = filter_input(
            INPUT_POST, "captcha", FILTER_SANITIZE_STRING
        );
        $rememberme = filter_input(
            INPUT_POST, "vfm_remember", FILTER_SANITIZE_STRING
        );

        if ($postusername && $postuserpass) {

            if (Utils::checkCaptcha($postcaptcha) == true) {

                if (GateKeeper::isUser($postusername, $postuserpass)) {
                    if ($rememberme == "yes") {
                        $cookies->setCookie($postusername);
                    }
                    $_SESSION['vfm_user_name'] = $postusername;
                    $_SESSION['vfm_logged_in'] = 1;

                    if ($gateKeeper->getUserSpace() !== false) {
                        $userspace = $gateKeeper->getUserInfo('quota')*1024*1024;
                        $usedspace = $gateKeeper->getUserSpace();
                        $_SESSION['vfm_user_used'] = $usedspace;
                        $_SESSION['vfm_user_space'] = $userspace;
                    }
                    if (SetUp::getConfig("notify_login")) {
                        Logger::emailNotification('--', 'login');
                    }
                    header("location:?dir=");
                } else {
                    $encodeExplorer->setErrorString("wrong_pass");
                }
            } else {
                $encodeExplorer->setErrorString("wrong_captcha");
            }
        }            
    }

    /**
    * delete multifile
    *
    * @return updates total available space
    */
    public function getUserSpace()
    {
        global $gateKeeper;

        if ($gateKeeper->getUserInfo('dir') !== null
            && $gateKeeper->getUserInfo('quota') !== null
        ) {

            $userfolders = array();
            $totalsize = 0;

            $userfolders = json_decode($gateKeeper->getUserInfo('dir'));

            foreach ($userfolders as $myfolder) {
                $checkfolder = urldecode(SetUp::getConfig('starting_dir').$myfolder);
                if (file_exists($checkfolder)) {
                    $ritorno = FileManager::sumDir($checkfolder);
                    $totalsize += $ritorno['size'];
                }
            }
            return $totalsize;
        }
        return false;
    }


    /**
    * login validation
    *
    * @param string $userName user name
    * @param string $userPass password
    *
    * @return true/false
    */
    public static function isUser($userName, $userPass)
    {
        $salt = SetUp::getConfig("salt");
        foreach (SetUp::getUsers() as $user) {
            if ($user['name'] === $userName) {
                $passo = $salt.urlencode($userPass);
                if (crypt($passo, $user['pass']) == $user['pass']) {
                    return true;

                }
                break;
            }
        }
        return false;
    }

    /**
    * check if login is required to view lists
    *
    * @return true/false
    */
    public static function isLoginRequired()
    {
        if (SetUp::getConfig("require_login") == false) {
            return false;
        }
        return true;
    }

    /**
    * check if user is logged in
    *
    * @return true/false
    */
    public static function isUserLoggedIn()
    {
        if (isset($_SESSION['vfm_user_name']) 
            && isset($_SESSION['vfm_logged_in']) 
            && $_SESSION['vfm_logged_in'] == 1
        ) {
            return true;
        }
        return false;
    }

    /**
    * check if target action is allowed
    *
    * @param string $action action to check
    *
    * @return true/false
    */
    public static function isAllowed($action)
    {
        if (!GateKeeper::isLoginRequired() || GateKeeper::isUserLoggedIn()) {
            if ((SetUp::getConfig($action) == true
                && GateKeeper::getUserInfo('role') == "admin")
                || GateKeeper::getUserInfo('role') == "superadmin"
            ) {
                return true;
            }
        }
        return false;
    }

    /**
    * check if user can access
    *
    * @return true/false
    */
    public static function isAccessAllowed()
    {
        if (!GateKeeper::isLoginRequired() || GateKeeper::isUserLoggedIn()) {
            return true;
        }
        return false;
    }

    /**
    * get user info ('name', 'pass', 'role', 'dir', 'email')
    *
    * @param int $info index of corresponding user info
    *
    * @return info requested
    */
    public static function getUserInfo($info)
    {
        if (GateKeeper::isUserLoggedIn() == true
            && isset($_SESSION['vfm_user_name'])
            && strlen($_SESSION['vfm_user_name']) > 0
        ) {

            $username = $_SESSION['vfm_user_name'];
            $curruser = Utils::getCurrentUser($username);

            if (isset($curruser[$info]) && strlen($curruser[$info]) > 0) {
                return $curruser[$info];
            }
            return null;
        }
    }

    /**
    * check if user is SuperAdmin
    *
    * @return true/false
    */
    public static function isSuperAdmin()
    {
        if (GateKeeper::getUserInfo('role') == "superadmin") {
            return true;
        }
        return false;
    }

    /**
    * show login box
    *
    * @return true/false
    */
    public static function showLoginBox()
    {
        if (!GateKeeper::isUserLoggedIn()
            && count(SetUp::getUsers()) > 0
        ) {
            return true;
        }
        return false;
    }
}


/**
 * The class for any kind of file managing (new folder, upload, etc).
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class FileManager
{
    /**
    * The main function, checks if the user wants to perform any supported operations
    *
    * @param string $location current location
    *
    * @return checks if any action is required
    */
    public function run($location)
    {
        $postuserdir = filter_input(
            INPUT_POST, "userdir", FILTER_SANITIZE_STRING
        );
        $postnewname = filter_input(
            INPUT_POST, "newname", FILTER_SANITIZE_STRING
        );

        if ($postuserdir) {
            // add new folder
            Actions::newFolder($location, $postuserdir);

        } elseif (isset($_FILES['userfile']['name'])) {
            // upload files
             $this->uploadMulti($_FILES['userfile']);

        } elseif ($postnewname) {
            // rename files or folders
            $postoldname = filter_input(
                INPUT_POST, "oldname", FILTER_SANITIZE_STRING
            );

            $postnewname = Utils::normalizeStr($postnewname);
            $this->setRename($postoldname, $postnewname);

        } else {
            // no post action
            $getdel = filter_input(
                INPUT_GET, "del", FILTER_SANITIZE_STRING
            );
            // delete files or folders
            if ($getdel
                && GateKeeper::isUserLoggedIn()
                && GateKeeper::isAllowed('delete_enable')
            ) {
                $getdel = str_replace(' ', '+', $getdel);
                $getdel = urldecode(base64_decode($getdel));
                
                $getdel = EncodeExplorer::extraChars($getdel);

                $this->setDel($getdel);
            }
        }
    }


    /**
    * A recursive function for calculating the total used space
    *
    * @param string $path start dir
    *
    * @return total used space
    */
    public static function sumDir($path)
    {
        $totalsize = 0;
        if ($handle = @opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                $nextpath = $path . '/' . $file;
                if ($file != '.' && $file != '..' && !is_link($nextpath)) {
                    if (is_dir($nextpath)) {
                        $result = self::sumDir($nextpath);
                        $totalsize += $result['size'];
                    } elseif (is_file($nextpath)) {
                        $totalsize += File::getFileSize($nextpath);
                    }
                }
            }
            closedir($handle);
        }
        $total['size'] = $totalsize;
        return $total;
    }

    /**
    * setup file to delete
    *
    * @param string $getdel path to delete
    *
    * @return call deleteFile()
    */
    public function setDel($getdel)
    {
        global $gateKeeper;

        if (Utils::checkDel($getdel) == false) {
            Utils::setError("<i class=\"fa fa-ban\"></i> Permission denied");
            return;
        }

        $dir = pathinfo($getdel, PATHINFO_DIRNAME);
        $info = pathinfo($getdel);
        $file_name = basename($getdel, '.'.$info['extension']);

        if (is_dir($getdel)) {

            $thumbdir = 'doc-admin/thumbs/'.$dir.'/'.$file_name;

            if ($gateKeeper->getUserSpace() !== false) {
                $ritorno = FileManager::sumDir("./".$getdel);
                $totalsize = $ritorno['size'];

                if ($totalsize > 0) {
                    Actions::updateUserSpaceDeep($totalsize);
                }
            }
            Actions::deleteDir($getdel);
            Actions::deleteDir($thumbdir);

            Utils::setWarning("<i class=\"fa fa-trash-o\"></i> ".substr($getdel, strrpos($getdel, '/') + 1));
            // Directory successfully deleted, sending log notification
            Logger::logDeletion("./".$getdel, true);

        } elseif (is_file($getdel)) {
            $thumb = 'doc-admin/thumbs/'.$dir.'/'.$file_name.'_thumb.png';

            Actions::deleteFile($getdel);
            Actions::deleteFile($thumb);
        }
    }


    /**
    * setup file renaming
    *
    * @param string $postoldname original file or directory name
    * @param string $postnewname new file or directory name
    *
    * @return call renameFile();
    */
    public function setRename($postoldname, $postnewname)
    {
        if (GateKeeper::isAccessAllowed()
            && GateKeeper::isAllowed('rename_enable')
        ) {
            $postthisext = filter_input(
                INPUT_POST, "thisext", FILTER_SANITIZE_STRING
            );
            $postthisdir = filter_input(
                INPUT_POST, "thisdir", FILTER_SANITIZE_STRING
            );

            if ($postoldname && $postnewname) {
                if ($postthisext) {
                    $oldname = $postthisdir.$postoldname.".".$postthisext;
                    $newname = $postthisdir
                    .Utils::normalizeStr($postnewname).".".$postthisext;
                } else {
                    $oldname = $postthisdir.$postoldname;
                    $newname = $postthisdir
                    .Utils::normalizeStr($postnewname);
                }

                Actions::renameFile($oldname, $newname, $postnewname);
            }
        }
    }

    /**
    * prepare multiple files for upload
    *
    * @param array $coda $_FLES['userfile']
    *
    * @return call uploadFIle()
    */
    public function uploadMulti($coda)
    {
        global $location;

        if ($location->editAllowed()
            && GateKeeper::isUserLoggedIn()
            && GateKeeper::isAccessAllowed()
            && GateKeeper::isAllowed('upload_enable')
        ) {
            // Number of files to uploaded
            $num_files = count($coda['tmp_name']);
            $totnames = array();
            /** loop through the array of files ***/
            for ($i=0; $i < $num_files;$i++) {
                
                $filepathinfo = Utils::mbPathinfo($coda['name'][$i]);

                $filename = $filepathinfo['filename'];
                $filex = $filepathinfo['extension'];
                $thename = $filepathinfo['basename'];
                $tempname = $coda['tmp_name'][$i];
                $tipo = $coda['type'][$i];
                $filerror = $coda['error'][$i];

                if (in_array($thename, $totnames)) {
                    $thename = $filename.$i.".".$filex;
                }

                if (Utils::notList(
                    $thename, 
                    array('.htaccess','.htpasswd','.ftpquota')
                ) == true) {

                    array_push($totnames, $thename);

                    if ($thename) {
                        Actions::uploadFile($location, $thename, $tempname, $tipo);
                        // check uplad errors
                        FileManager::upLog($filerror);
                    }
                }
            }
        }
    }

    /**
    * add log uploading errors
    *
    * @param num $filerr array value of $_FILES['userfile']['error'][$i]
    *
    * @return error response
    */
    public static function upLog($filerr) 
    {

        $error_types = array(
        0=>'OK',
        1=>'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        2=>'The uploaded file exceeds the MAX_FILE_SIZE specified in the HTML form.',
        3=>'The uploaded file was only partially uploaded.',
        4=>'No file was uploaded.',
        6=>'Missing a temporary folder.',
        7=>'Failed to write file to disk.',
        8=>'A PHP extension stopped the file upload.',
        'post_max_size' => 'The uploaded file exceeds the post_max_size directive in php.ini',
        'max_file_size' => 'File is too big',
        'min_file_size' => 'File is too small',
        'accept_file_types' => 'Filetype not allowed',
        'max_number_of_files' => 'Maximum number of files exceeded',
        'max_width' => 'Image exceeds maximum width',
        'min_width' => 'Image requires a minimum width',
        'max_height' => 'Image exceeds maximum height',
        'min_height' => 'Image requires a minimum height',
        'abort' => 'File upload aborted',
        'image_resize' => 'Failed to resize image'
        ); 

        $error_message = $error_types[$filerr]; 
        if ($filerr > 0) {
            Utils::setError(" :: ".$error_message);
        }
    }

    /**
    * append .txt to extension
    *
    * @param string $name      name to modify
    * @param string $extension extension to check
    *
    * @return string $name filename with .txt appended
    */
    public static function safeExtension($name, $extension)
    {
        $evil = array(
            "php","php3","php4","php5","htm","html","phtm","phtml",
            "shtm","shtml","asp","pl","py","jsp","sh","cgi"
            );
        if (in_array($extension, $evil)) {
            $name = $name.".txt";
        }
        return $name;
    }
}


/**
 * Main actions
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Actions
{
    /**
    * rename files
    *
    * @param string $oldname    original file path
    * @param string $newname    new file path
    * @param string $thenewname new name
    *
    * @return rename file
    */
    public static function renameFile($oldname, $newname, $thenewname)
    {
        global $encodeExplorer;

        $oldname = EncodeExplorer::extraChars($oldname);
        $newname = EncodeExplorer::extraChars($newname);

        if (!file_exists($newname)) {
            if (file_exists($oldname) && !rename($oldname, $newname)) {
                Utils::setError(
                    "ERROR editing: <strong>" .$thenewname. "</strong>"
                );
            } else {
                Utils::setSuccess(
                    "<strong>".$thenewname. "</strong> "
                    .$encodeExplorer->getString("updated")
                );
            }
        } else {
            Utils::setError(
                "<strong>" .$thenewname. "</strong> "
                .$encodeExplorer->getString("file_exists")
            );
        }
    }

    /**
    * create new folder
    *
    * @param string $location where to create new folder
    * @param string $dirname  new dir name
    *
    * @return adds new folder
    */
    public static function newFolder($location, $dirname)
    {
        global $encodeExplorer;

        if (GateKeeper::isAllowed('newdir_enable')) {
            if (strlen($dirname) > 0) {
                $dirname = Utils::normalizeStr($dirname);

                if (!$location->editAllowed()) {
                    // The system configuration does not allow uploading here
                    $encodeExplorer->setErrorString("upload_not_allowed");
                } elseif (!$location->isWritable()) {
                    // The target directory is not writable
                    $encodeExplorer->setErrorString("upload_dir_not_writable");
                } elseif (file_exists(
                    $location->getDir(true, false, false, 0).$dirname
                )) {
                    Utils::setError(
                        "<i class=\"fa fa-folder\"></i>  <strong>".$dirname."</strong> "
                        .$encodeExplorer->getString("file_exists")
                    );
                } elseif (!mkdir(
                    $location->getDir(true, false, false, 0).$dirname, 0755
                )) {
                    // Error creating a new directory
                    $encodeExplorer->setErrorString("new_dir_failed");
                } elseif (!chmod(
                    $location->getDir(true, false, false, 0).$dirname, 0755
                )) {
                    // Error applying chmod 755
                    $encodeExplorer->setErrorString("chmod_dir_failed");
                } else {
                    Utils::setSuccess(
                        "<i class=\"fa fa-folder\"></i> <strong>".$dirname."</strong> "
                        .$encodeExplorer->getString("created")
                    );
                    // Directory successfully created, sending e-mail notification
                    Logger::logCreation(
                        $location->getDir(true, false, false, 0).$dirname, true
                    );
                }
            }
        }
    }

    /**
    * upload file
    *
    * @param string $location where to upload
    * @param string $thename  file name
    * @param string $tempname temp name
    * @param string $tipo     file type
    *
    * @return uploads file
    */
    public static function uploadFile($location, $thename, $tempname, $tipo)
    {
        global $encodeExplorer;

        $extension = File::getFileExtension($thename);

        $filepathinfo = Utils::mbPathinfo($thename);
        $name = Utils::normalizeStr($filepathinfo['filename']).".".$extension;

        $upload_dir = $location->getFullPath();

        $upload_file = $upload_dir.$name;

        if (file_exists($upload_file)) {
            Utils::setWarning(
                "<span><i class=\"fa fa-info-circle\"></i> ".$name." "
                .$encodeExplorer->getString("file_exists")."</span> "
            );
        } else {

            $mime_type = $tipo;
            $clean_file = $upload_dir.FileManager::safeExtension($name, $extension);

            if (!$location->editAllowed() || !$location->isWritable() ) {
                Utils::setError(
                    "<span><i class=\"fa fa-exclamation-triangle\"></i> "
                    .$encodeExplorer->getString("upload_not_allowed")."</span> "
                );

            } elseif (Utils::notList(
                $mime_type, SetUp::getConfig("upload_allow_type")
            ) == true
                || Utils::inList(
                    $extension, SetUp::getConfig("upload_reject_extension")
                ) == true
            ) {
                Utils::setError(
                    "<span><i class=\"fa fa-exclamation-triangle\"></i> "
                    .$name."<strong>.".$extension."</strong> "
                    .$encodeExplorer->getString("upload_type_not_allowed")."</span> "
                );

            } elseif (!is_uploaded_file($tempname)) {
                $encodeExplorer->setErrorString("failed_upload");

            } elseif (!move_uploaded_file($tempname, $clean_file)) {
                $encodeExplorer->setErrorString("failed_move");

            } elseif (Actions::checkUserUp($clean_file) == false) {

                $encodeExplorer->setErrorString("upload_exceeded");
                unlink($clean_file);

            } else {
                
                chmod($clean_file, 0755);
                //Actions::updateUserSpace($clean_file, true);

                Utils::setSuccess(
                    "<span><i class=\"fa fa-check-circle\"></i> "
                    .$name."</span> "
                );

                // file successfully uploaded, sending log notification
                Logger::logCreation(
                    $location->getDir(true, false, false, 0).$name, false
                );
            }
        }
    }

    /**
    * delete directory
    *
    * @param string $dir directory to delete
    *
    * @return deletes directory
    */
    public static function deleteDir($dir)
    {
        if (is_dir($dir)) {

            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {
                        Actions::deleteDir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    /**
    * delete file
    *
    * @param string $file file to delete
    *
    * @return deletes file
    */
    public static function deleteFile($file)
    {
        if (is_file($file)) {

            Actions::updateUserSpace($file, false);
            
            unlink($file);

            // file successfully deleted, sending log notification
            Utils::setWarning("<i class=\"fa fa-trash-o\"></i> ".substr($file, strrpos($file, '/') + 1));
            Logger::logDeletion("./".$file, false);
        }
    }
    /**
    * delete multifile
    *
    * @param string $file file to delete
    *
    * @return deletes file
    */

    public static function deleteMulti($file)
    {
        if (is_file($file)) {

            Actions::updateUserSpace($file, false);

            unlink($file);
            
            // files successfully deleted, sending log notification
            Logger::logDeletion(substr($file, 1), false, true);
        }
    }

    /**
    * check if user has space to upload
    *
    * @param string $file     file to check
    * @param string $thissize size to check
    *
    * @return true/false
    */
    public static function checkUserUp($file, $thissize = false)
    {
        if (isset($_SESSION['vfm_user_used']) 
            && isset($_SESSION['vfm_user_space'])
        ) {

            if (!$thissize) {
                $thissize = File::getFileSize($file);
            }

            $oldused = $_SESSION['vfm_user_used'];
            $newused = $oldused + $thissize;
            $freespace = $_SESSION['vfm_user_space'];
            
            if ($newused > $freespace) {
                return false;
            } else {
                $_SESSION['vfm_user_used'] = $newused;
                return true;
            }
        }
        return true;
    }

    /**
    * Update user used space by file (add or subtract)
    *
    * @param string  $file file to add/subtract
    * @param boolean $add  true/false add or subtract
    *
    * @return updates total used space
    */
    public static function updateUserSpace($file, $add)
    {

        if (isset($_SESSION['vfm_user_used'])) {

            $thissize = File::getFileSize($file);
            $usedspace = $_SESSION['vfm_user_used'];

            if ($add == true) {
                $usedspace = $usedspace + $thissize;
            } else {
                $usedspace = $usedspace - $thissize;
            }
            $_SESSION['vfm_user_used'] = $usedspace;
        }
    }

    /**
    * Update user used space by size (subtract)
    *
    * @param string $size size to add/subtract
    *
    * @return updates total used space
    */
    public static function updateUserSpaceDeep($size)
    {

        if (isset($_SESSION['vfm_user_used'])) {

            $thissize = $size;
            $usedspace = $_SESSION['vfm_user_used'];
            $usedspace = $usedspace - $thissize;

            $_SESSION['vfm_user_used'] = $usedspace;
        }
    }

}


/**
 * Dir class holds the information about one directory in the list
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Dir
{
    public $name;
    public $location;

    /**
    * Constructor
    *
    * @param string $name     path name
    * @param string $location current location
    *
    * @return directory name and location
    */
    public function __construct($name, $location)
    {
        $this->name = $name;
        $this->location = $location;
    }

    /**
    * get directory location
    *
    * @return directory location
    */
    public function getLocation()
    {
        return $this->location->getDir(true, false, false, 0);
    }
    
    /**
    * get directory name
    *
    * @return directory name
    */
    public function getName()
    {
        return $this->name;
    }

    /**
    * get directory HTML name
    *
    * @return directory name
    */
    public function getNameHtml()
    {
        return htmlspecialchars($this->name);
    }

    /**
    * get directory name urlencoded
    *
    * @return directory name
    */
    public function getNameEncoded()
    {
        return rawurlencode($this->name);
    }

    /**
    * Debugging output
    *
    * @return debug
    */
    public function debug()
    {
        print("Dir name (htmlspecialchars): ".$this->getName()."\n");
        print("Dir location: ".$this->location->getDir(true, false, false, 0)."\n");
    }
}

/**
 * File class that holds the information about one file in the list
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class File
{
    public $name;
    public $location;
    public $size;
    public $type;
    public $modTime;

    /**
    * Constructor
    *
    * @param string $name     path name
    * @param string $location current location
    *
    * @return all file data
    */
    public function __construct($name, $location)
    {
        $this->name = $name;
        $this->location = $location;

        $this->type = File::getFileType(
            $this->location->getDir(true, false, false, 0).$this->getName()
        );
        $this->size = File::getFileSize(
            $this->location->getDir(true, false, false, 0).$this->getName()
        );
        $this->modTime = filemtime(
            $this->location->getDir(true, false, false, 0).$this->getName()
        );
    }

    /**
    * get name
    *
    * @return name
    */
    public function getName()
    {
        return $this->name;
    }

    /**
    * get name encoded
    *
    * @return name urlencoded
    */
    public function getNameEncoded()
    {
        return rawurlencode($this->name);
    }

    /**
    * get name html formatted
    *
    * @return HTML name
    */
    public function getNameHtml()
    {
        return htmlspecialchars($this->name);
    }

    /**
    * get file size
    *
    * @return size
    */
    public function getSize()
    {
        return $this->size;
    }

    /**
    * get type
    *
    * @return file type
    */
    public function getType()
    {
        return $this->type;
    }

    /**
    * get time
    *
    * @return mod time
    */
    public function getModTime()
    {
        return $this->modTime;
    }

    /**
    * Determine the size of a file
    *
    * @param string $file file to calculate
    *
    * @return sizeInBytes
    */
    public static function getFileSize($file)
    {
        $sizeInBytes = filesize($file);
        /**
        * If filesize() fails (with larger files),
        * try to get the size with fseek
        */
        if (!$sizeInBytes || $sizeInBytes < 0) {
            $fho = fopen($file, "r");
            $size = "0";
            $char = "";
            fseek($fho, 0, SEEK_SET);
            $count = 0;
            while (true) {
                //jump 1 MB forward in file
                fseek($fho, 1048576, SEEK_CUR);
                //check if we actually left the file
                if (($char = fgetc($fho)) !== false) {
                    $count ++;
                } else {
                    //else jump back where we were before leaving and exit loop
                    fseek($fho, -1048576, SEEK_CUR);
                    break;
                }
            }
            $size = bcmul("1048577", $count);
            $fine = 0;
            while (false !== ($char = fgetc($fho))) {
                $fine ++;
            }
            //and add them
            $sizeInBytes = bcadd($size, $fine);
            fclose($fho);
        }
        return $sizeInBytes;
    }

    /**
    * Determine the type of a file
    *
    * @param string $filepath file to calculate
    *
    * @return call getFileExtension
    */
    public static function getFileType($filepath)
    {
        return File::getFileExtension($filepath);
    }

    /**
    * Determine the MIME info of a file
    *
    * @param string $filepath file to calculate
    *
    * @return mime_type
    */
    public static function getFileMime($filepath)
    {
        $fhandle = finfo_open(FILEINFO_MIME);
        $mime_type = finfo_file($fhandle, $filepath);
        $mime_type_chunks = preg_split('/\s+/', $mime_type);
        $mime_type = $mime_type_chunks[0];
        $mime_type_chunks = explode(";", $mime_type);
        $mime_type = $mime_type_chunks[0];
        finfo_close($fhandle);
        return $mime_type;
    }

    /**
    * Determine extension of a file
    *
    * @param string $filepath file to calculate
    *
    * @return ext
    */
    public static function getFileExtension($filepath)
    {
        return strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    }

    /**
    * Debugging output
    *
    * @return debug info
    */
    public function debug()
    {
        print("File name: ".$this->getName()."\n");
        print("File location: ".$this->location->getDir(true, false, false, 0)."\n");
        print("File size: ".$this->size."\n");
        print("File modTime: ".$this->modTime."\n");
    }

    /**
    * check if file is image
    *
    * @return true/false
    */
    public function isImage()
    {
        $type = strtolower($this->getType());
        if ($type == "png" || $type == "jpg" || $type == "gif" || $type == "jpeg") {
            return true;
        }
        return false;
    }

    /**
    * check if file is a pdf
    *
    * @return true/false
    */
    public function isPdf()
    {
        if (strtolower($this->getType()) == "pdf") {
            return true;
        }
        return false;
    }

    /**
    * check if target file is a pdf
    *
    * @param string $file file to calculate
    *
    * @return true/false
    */
    public static function isPdfFile($file)
    {
        if (File::getFileType($file) == "pdf") {
            return true;
        }
        return false;
    }

    /**
    * check if file is valid for create thumbnail
    *
    * @return true/false
    */
    public function isValidForThumb()
    {
        if ($this->isImage() || ($this->isPdf() 
            && ImageServer::isEnabledPdf())
        ) {
            return true;
        }
        return false;
    }
}


/**
 * Location class holds the information about path location
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Location
{
    public $path;

    /**
    * Set the current directory
    *
    * @return current directory
    */
    public function init()
    {
        $getdir = filter_input(INPUT_GET, "dir", FILTER_SANITIZE_STRING);
        $dlist = filter_input(INPUT_GET, "dlist", FILTER_SANITIZE_STRING);

        if ($dlist == "date" || $dlist == "alpha") {
            $_SESSION['vfm_dlist'] = $dlist;
        }

        if (!$getdir) {
            $this->path = $this->splitPath(SetUp::getConfig('starting_dir'));
        } else {
            $this->path = $this->splitPath($getdir);
        }
    }

    /**
    * Split a file path into array elements
    *
    * @param string $dir directory to split
    *
    * @return $path2
    */
    public static function splitPath($dir)
    {
        $dir = stripslashes($dir);
        $path1 = preg_split("/[\\\\\/]+/", $dir);
        $path2 = array();
        for ($i = 0; $i < count($path1); $i++) {
            if ($path1[$i] == ".." || $path1[$i] == "." || $path1[$i] == "") {
                continue;
            }
            $path2[] = $path1[$i];
        }
        return $path2;

    }

    /**
    * Get the current directory.
    *
    * @param boolean $prefix  Include the prefix ("./")
    * @param boolean $encoded URL-encode the string
    * @param boolean $html    HTML-encode the string
    * @param int     $upper   return directory n-levels up
    *
    * @return $dir
    */
    public function getDir($prefix, $encoded, $html, $upper)
    {
        $dir = "";
        if ($prefix == true) {
            $dir .= "./";
        }
        for ($i = 0; $i < ((count($this->path) >= $upper
            && $upper > 0)?count($this->path)-$upper:count($this->path)); $i++
        ) {
            $temp = $this->path[$i];
            if ($encoded) {
                $temp = rawurlencode($temp);
            }
            if ($html) {
                $temp = htmlspecialchars($temp);
            }
            $dir .= $temp."/";
        }

        $dir = EncodeExplorer::extraChars($dir);

        return $dir;
    }

    /**
    * Get directory link for breadcrumbs
    *
    * @param int     $level breadcrumb level
    * @param boolean $html  HTML-encode the name
    *
    * @return path name
    */
    public function getPathLink($level, $html)
    {
        if ($html) {
            return htmlspecialchars($this->path[$level]);
        } else {
            return $this->path[$level];
        }
    }

    /**
    * Get full directory path
    *
    * @return path name
    */
    public function getFullPath()
    {
        $fullpath = (strlen(
            SetUp::getConfig('basedir')
        ) > 0 ? SetUp::getConfig('basedir'):
        str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])))
        ."/".$this->getDir(false, false, false, 0);

        $fullpath = EncodeExplorer::extraChars($fullpath);
        return $fullpath;
    }

    /**
    * Debugging output
    *
    * @return debug
    */
    public function debug()
    {
        print_r($this->path);
        print("Dir with prefix: "
            .$this->getDir(true, false, false, 0)."\n");
        print("Dir without prefix: "
            .$this->getDir(false, false, false, 0)."\n");
        print("Upper dir with prefix: "
            .$this->getDir(true, false, false, 1)."\n");
        print("Upper dir without prefix: "
            .$this->getDir(false, false, false, 1)."\n");
    }

    /**
    * Checks if the current directory is below the input path
    *
    * @param string $checkPath path to check
    *
    * @return true/false
    */
    public function isSubDir($checkPath)
    {
        for ($i = 0; $i < count($this->path); $i++) {
            if (strcmp($this->getDir(true, false, false, $i), $checkPath) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
    * Check if editing is allowed into the current directory,
    * based on configuration settings
    *
    * @return true/false
    */
    public function editAllowed()
    {

        global $encodeExplorer;
        global $location;

        if (GateKeeper::getUserInfo('dir') == null
            || $encodeExplorer->checkUserDir($location) == true
        ) {
            return true;
        }
        return false;
    }

    /**
    * Check if current directory is writeable
    *
    * @return true/false
    */
    public function isWritable()
    {
        return is_writable($this->getDir(true, false, false, 0));
    }

    /**
    * Check if target directory is writeable
    *
    * @param string $dir path to check
    *
    * @return true/false
    */
    public static function isDirWritable($dir)
    {
        return is_writable($dir);
    }

    /**
    * Check if target file is writeable
    *
    * @param string $file path to check
    *
    * @return true/false
    */
    public static function isFileWritable($file)
    {
        if (file_exists($file)) {
            if (is_writable($file)) {
                return true;
            } else {
                return false;
            }
        } elseif (Location::isDirWritable(dirname($file))) {
            return true;
        } else {
            return false;
        }
    }
}


/**
 * Main engine based on EncodeExplorer
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class EncodeExplorer
{
    public $location;
    public $dirs;
    public $files;
    public $logging;
    public $spaceUsed;
    public $lang;
    /**
    * calculate space, log actions, set language
    *
    * @return set lang session
    */
    public function init()
    {
        if (strlen(SetUp::getConfig("session_name")) > 0) {
            session_name(SetUp::getConfig("session_name"));
        }
        if (count(SetUp::getUsers()) > 0) {
            session_start();
        } else {
            return;
        }

        if (isset($_GET['lang']) 
            && file_exists('doc-admin/translations/'.$_GET['lang'].'.php')
        ) {
            $this->lang = $_GET['lang'];
            $_SESSION['lang'] = $_GET['lang'];
        }
        if (isset($_SESSION['lang'])) {
            $this->lang = $_SESSION['lang'];
        } else {
            $this->lang = SetUp::getConfig("lang");
        }

        $this->logging = false;
        if (SetUp::getConfig("log_file") != null
            && strlen(SetUp::getConfig("log_file")) > 0
        ) {
            $this->logging = true;
        }
    }

    /**
    * Print languages list
    *
    * @param string $dir realtive path to translations
    *
    * @return Languages list
    */
    public function printLangMenu($dir = '')
    {
        $directory = "translations";
        $files = array_diff(
            scandir($dir.$directory), 
            array(".", "..", ".DS_Store", ".svn")
        );
        $files = preg_grep('/^([^.])/', $files);
        $menu = "<ul class=\"dropdown-menu\">";

        foreach ($files as $item) {
            $langvar = substr($item, 0, -4);
            $menu .= "<li><a  href=?lang="
            .$langvar.">".$langvar."</a></li>";
        }
        $menu .= "</ul>";
        return $menu;
    }

    /**
    * Return languages list as array
    *
    * @param string $dir realtive path to translations
    *
    * @return $languages
    */
    public function getLanguages($dir = '')
    {
        $directory = "translations";
        $files = array_diff(
            scandir($dir.$directory), 
            array(".", "..", ".DS_Store", ".svn")
        );
        $files = preg_grep('/^([^.])/', $files);
        
        $languages = array();

        foreach ($files as $item) {
            $langvar = substr($item, 0, -4);
            array_push($languages, $langvar);
        }
        return $languages;
    }

    /**
    * Read the file list from the directory
    *
    * @return Reading the data of files and directories
    */
    public function readDir()
    {
        global $encodeExplorer;

        $fullpath = $this->location->getFullPath();

        if ($open_dir = @opendir($fullpath)) {
            $this->dirs = array();
            $this->files = array();
            while ($object = readdir($open_dir)) {
                if ($object != "." && $object != "..") {
                    if (is_dir(
                        $this->location->getDir(true, false, false, 0)."/".$object
                    )
                        && !in_array($object, SetUp::getConfig('hidden_dirs'))
                    ) {
                        $this->dirs[] = new Dir($object, $this->location);
                    } elseif (!in_array(
                        $object, SetUp::getConfig('hidden_files')
                    ) && $object != "doc-admin"
                    ) {
                        $this->files[] = new File($object, $this->location);
                    }
                }
            }
            closedir($open_dir);
        } else {
            $encodeExplorer->setErrorString("unable_to_read_dir");
        }
    }

    /**
    * Read the assigned folder list from the root directory
    *
    * @return directiories listing
    */
    public function readFolders()
    {
        global $encodeExplorer;
        $fullpath = $this->location->getFullPath();

        if ($open_dir = @opendir($fullpath)) {
            $this->dirs = array();
            $this->files = array();
            while ($object = readdir($open_dir)) {
                if ($object != "." && $object != "..") {
                    if (is_dir(
                        $this->location->getDir(true, false, false, 0)."/".$object
                    )
                        && !in_array($object, SetUp::getConfig('hidden_dirs'))
                        && in_array($object, json_decode(GateKeeper::getUserInfo('dir')))
                    ) {
                        $this->dirs[] = new Dir($object, $this->location);
                    } 
                }
            }
            closedir($open_dir);
        } else {
            $encodeExplorer->setErrorString("unable_to_read_dir");
        }
    }

    /**
    * create links to logout, delete and open directory
    *
    * @param boolean $logout set logout
    * @param string  $delete path to delete
    * @param string  $dir    path to link
    *
    * @return link
    */
    public function makeLink($logout, $delete, $dir)
    {
        $link = "?";

        if ($logout == true) {
            $link .= "logout";

            return $link;
        }

        $link .= "dir=".$dir;
        if ($delete != null) {
            $link .= "&amp;del=".base64_encode($delete);
        }

        return $link;
    }

    /**
    * Get string in current language
    *
    * @param string $stringName string to translate
    *
    * @return translated string
    */
    public function getString($stringName)
    {
        return SetUp::getLangString($stringName, $this->lang);
    }

    /**
    * set success with translated string
    *
    * @param string $stringName translation string
    *
    * @return outputs error message
    */
    public function setSuccessString($stringName)
    {
        Utils::setSuccess($this->getString($stringName));
    }

    /**
    * set error with translated string
    *
    * @param string $stringName translation string
    *
    * @return outputs error message
    */
    public function setErrorString($stringName)
    {
        Utils::setError($this->getString($stringName));
    }

    /**
    * check if directory is available for user
    *
    * @param string $location to check
    *
    * @return true/false
    */
    public function checkUserDir($location)
    {
        $this->location = $location;
        $startdir = SetUp::getConfig('starting_dir');

        if (GateKeeper::getUserInfo('dir') == null) {
            return true;
        }

        $userpatharray = array();
        $userpatharray = json_decode(GateKeeper::getUserInfo('dir'));

        foreach ($userpatharray as $value) {

            $userpath = substr($startdir.$value, 2); 

            $pos = strpos($this->location->getDir(true, false, false, 0), $userpath);

            if ($pos !== false) {
                return true;
            }
        }
        return false;
    }

    /**
    * replace some chars from string
    *
    * @param string $str string to clean
    *
    * @return $str
    */
    public static function extraChars($str)
    {
        $apici = array("&#34;", "&#39;");
        $realapici = array("\"", "'");
        $str = str_replace($apici, $realapici, $str);
        return $str;
    }

    /**
    * Main function, check what to see
    *
    * @param string $location current location
    *
    * @return genral output
    */
    public function run($location)
    {
        global $encodeExplorer;

        $this->location = $location;

        if ($encodeExplorer->checkUserDir($location) == true) {
            $this->readDir();
        } else {
            $this->readFolders();
        }
    }
}


/**
 * Utilities
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Utils
{
    /**
    * generate random string
    *
    * @param string $length string lenght
    *
    * @return $randomString random string
    */
    public static function randomString($length = 9) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return "$1$".$randomString;
    }
    /**
    * check captcha code
    *
    * @param string $postcaptcha code to check
    *
    * @return true / false
    */
    public static function checkCaptchaReset($postcaptcha)
    {
        
        if (SetUp::getConfig("show_captcha_reset") !== true) {
            return true;
        }
        if ($postcaptcha) {
            $postcaptcha = strtolower($postcaptcha);

            if (isset($_SESSION['captcha'])
                && $postcaptcha === $_SESSION['captcha']
            ) {
                return true;
            }
        }
        return false;
    }

    /**
    * check captcha code
    *
    * @param string $postcaptcha code to check
    *
    * @return true / false
    */
    public static function checkCaptcha($postcaptcha)
    {
        if (SetUp::getConfig("show_captcha") !== true) {
            return true;
        }
        if ($postcaptcha) {
            $postcaptcha = strtolower($postcaptcha);

            if (isset($_SESSION['captcha'])
                && $postcaptcha === $_SESSION['captcha']
            ) {
                return true;
            }
        }
        return false;

    }
    /**
    * get pathinfo in UTF-8
    *
    * @param string $filepath to search
    *
    * @return array $ret
    */
    public static function mbPathinfo($filepath) 
    {
        preg_match(
            '%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', 
            $filepath, $node
        );

        if (isset($node[1])) {
            $ret['dirname'] = $node[1];
        } else {
            $ret['dirname'] = "";
        }

        if (isset($node[2])) {
            $ret['basename'] = $node[2];
        } else {
            $ret['basename'] = "";
        }

        if (isset($node[3])) {
            $ret['filename'] = $node[3];
        } else {
            $ret['filename'] = "";
        } 

        if (isset($node[5])) {
            $ret['extension'] = $node[5];
        } else {
            $ret['extension'] ="";
        }
        return $ret;
    }

    /**
    * check path to delete
    *
    * @param string $path to search
    *
    * @return true/false
    */
    public static function checkDel($path)
    {
        $startdir = SetUp::getConfig("starting_dir");

        $cash = filter_input(INPUT_GET, "h", FILTER_SANITIZE_STRING);
        $del = filter_input(INPUT_GET, "del", FILTER_SANITIZE_STRING);

        $del = str_replace(' ', '+', $del);

        if (md5($del.SetUp::getConfig('salt').SetUp::getConfig('session_name')) === $cash) {

            if (GateKeeper::getUserInfo('dir') != null) {
                $userdirs = json_decode(GateKeeper::getUserInfo('dir'));

                foreach ($userdirs as $value) {
                    $userpath = $startdir.$value;
                    $pos = strpos("./".$path, $userpath);

                    if ($pos !== false) {
                        return true;
                    }
                }
                return false;
            }

            $pos = strpos("./".$path, $startdir);
            $startdirinfo = Utils::mbPathinfo($startdir);
            $basedir = $startdirinfo['basename'];
            $filepathinfo = Utils::mbPathinfo($path);
            $basepath = $filepathinfo['basename'];

            $evil = array("", "/", "\\", ".");
            $avoid = array(".htaccess", "index.php", "vfm-thumb.php", "doc-admin", $basedir);

            if (in_array($path, $evil) 
                || $pos === false
                || in_array($basepath, $avoid)
            ) {
                return false;
            }
            return true;
        }
    
        return false;
    }

    /**
    * get user data by username
    *
    * @param int $search username to search
    *
    * @return user array requested
    */
    public static function getCurrentUser($search)
    {
        $currentuser = array();
        foreach (SetUp::getUsers() as $user) {
            if ($user['name'] == $search) {
                $currentuser = $user;
            }
        }
        return $currentuser;
    }

    /**
    * remove some chars from string
    *
    * @param string $str string to clean
    *
    * @return $str
    */
    public static function normalizeStr($str)
    {
        $invalid = array(
        '&#34;' => '' , '&#39;' => '' , ' ' => '-', ':' => '', ',' => '', ';' => '', '!' => '',
            '`' => '', '´' => '', '„' => '', '`' => '', '’' => '', '"' => '', '”' => '',
            '{' => '-', '}' => '-', '[' => '-', ']' => '-', '<' => '-', '>' => '-', '.' => '_',
            // '(' => '-', ')' => '-',
            '~' => '', '&' => '-', '?' => '', '#' => '', '*' => 'x', '+' => '-', 
            '$' => 'S', '¢' => 'cent', '£' => 'lb', '€' => 'eur' ,'™' => 'TM', '®' => 'R', 
            '\\' => '' , '\'' => '', '/' => '', '@' => '-at-',
            '§' => 's', '°' => '', '|' => '', '=' => '-', '^' => '',
            'Š'=>'S', 'š'=>'s', 'ş'=>'s', 'Đ'=>'Dj', 'Ț'=>'T',
            'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c',
            'Ć'=>'C', 'ć'=>'c', 'À'=>'A', 'Á'=>'A', 'Â'=>'A',
            'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I',
            'Ğ'=>'G', 'İ'=>'I', 'Ş'=>'S', 'ğ'=>'g', 'ı'=>'i',
            'ă'=>'a', 'Ă'=>'A', 'ș'=>'s', 'Ș'=>'S', 'ț'=>'t', 
            'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O',
            'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O',
            'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y',
            'Þ'=>'B', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a',
            'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ß'=>'ss', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i',
            'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n',
            'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y',
            'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
        );
        $cleanstring = strtr($str, $invalid);

        // cut name if has more than 31 chars;
        // if (strlen($cleanstring) > 31) {
        //     $cleanstring = substr($cleanstring, 0, 31);
        // }
        return $cleanstring;
    }

    /**
    * debugger
    *
    * @return debug
    */
    public function debug()
    {
        print("location: "
            .$this->location->getDir(true, false, false, 0)."\n");
        for ($i = 0; $i < count($this->dirs); $i++) {
            $this->dirs[$i]->output();
        }
        for ($i = 0; $i < count($this->files); $i++) {
            $this->files[$i]->output();
        }
    }

    /**
    * output errors
    *
    * @param string $message error message
    *
    * @return output error
    */
    public static function setError($message)
    {
        global $_ERROR;
        $_ERROR .= " ".$message;
    }

    /**
    * output success
    *
    * @param string $message success message
    *
    * @return output success
    */
    public static function setSuccess($message)
    {
        global $_SUCCESS;
        $_SUCCESS .= " ".$message;
    }


    /**
    * output warning
    *
    * @param string $message warning message
    *
    * @return output warning
    */
    public static function setWarning($message)
    {
        global $_WARNING;
        $_WARNING .= " ".$message;
    }

    /**
    * check Magic quotes
    *
    * @param string $name string to check
    *
    * @return $name
    */
    public static function checkMagicQuotes($name)
    {
        if (get_magic_quotes_gpc()) {
            $name = stripslashes($name);
        } else {
            $name = $name;
        }
        return $name;
    }

    /**
    * check file_info
    *
    * @return true/false
    */
    public static function checkFinfo()
    {
        if (function_exists("finfo_open")
            && function_exists("finfo_file")
        ) {
            return true;
        }
        return false;
    }

    /**
    * check if item is in list
    *
    * @param string $item item to check
    * @param string $list list where to look
    *
    * @return true/false
    */
    public static function inList($item, $list)
    {
        if (is_array($list)
            && count($list) > 0
            && in_array($item, $list)
        ) {
            return true;
        }
        return false;
    }

    /**
    * check if item is not in list
    *
    * @param string $item item to check
    * @param string $list list where to look
    *
    * @return true/false
    */
    public static function notList($item, $list)
    {
        if (is_array($list)
            && count($list) > 0
            && !in_array($item, $list)
        ) {
            return true;
        }
        return false;
    }

}


/**
 * SetUp main configuration
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class SetUp
{
    /**
    * Return folders available inside given directory
    *
    * @param string $dir realtive path
    *
    * @return $folders array
    */
    public function getFolders($dir = '')
    {

        $directory = ".".SetUp::getConfig("starting_dir");
        $files = array_diff(
            scandir($dir.$directory), 
            array(".", "..", ".DS_Store", ".svn", "doc-admin")
        );
        $files = preg_grep('/^([^.])/', $files);

        $folders = array();

        foreach ($files as $item) {
            if (is_dir($directory . '/' . $item)) {
                array_push($folders, $item);
            }
        }
        return $folders;
    }

    /**
    * The function for getting a translated string.
    * Falls back to english if the correct language is missing something.
    *
    * @param string $stringName string to translate
    *
    * @return translation
    */
    public static function getLangString($stringName)
    {
        global $_TRANSLATIONS;
        if (isset($_TRANSLATIONS)
            && is_array($_TRANSLATIONS)
            && isset($_TRANSLATIONS[$stringName])
            && strlen($_TRANSLATIONS[$stringName]) > 0
        ) {
            return stripslashes($_TRANSLATIONS[$stringName]);
        } else {
            return "&gt;".$stringName."&lt;";
        }
    }

    /**
    * show language menu
    *
    * @return true/false
    */
    public static function showLangMenu()
    {
        if (SetUp::getConfig("show_langmenu") == true) {
            return true;
        }
        return false;
    }

    /**
    * The function for getting configuration values
    *
    * @param string $name config option name
    *
    * @return config value
    */
    public static function getConfig($name)
    {
        global $_CONFIG;
        if (isset($_CONFIG) && isset($_CONFIG[$name])) {
            return $_CONFIG[$name];
        }
        return null;
    }

    /**
    * switch language
    *
    * @param string $lang language to link
    *
    * @return language link
    */
    public function switchLang($lang)
    {
        $link = "?lang=".$lang;
        return $link;
    }

    /**
    * format modification date time
    *
    * @param string $time new format
    *
    * @return formatted date
    */
    public static function formatModTime($time)
    {
        $timeformat = "d.m.y H:i:s";
        if (SetUp::getConfig("time_format") != null
            && strlen(SetUp::getConfig("time_format")) > 0
        ) {
            $timeformat = SetUp::getConfig("time_format");
        }
        return date($timeformat, $time);
    }

    /**
    * format file size
    *
    * @param string $size new format
    *
    * @return formatted size
    */
    public function formatSize($size)
    {
        $sizes = Array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
        $syz = $sizes[0];
        for ($i = 1; (($i < count($sizes)) && ($size >= 1024)); $i++) {
            $size = $size / 1024;
            $syz  = $sizes[$i];
        }
        return round($size, 2)." ".$syz;
    }

    /**
    * get file size in kb
    *
    * @param string $size new format
    *
    * @return formatted size
    */
    public function fullSize($size)
    {
        $size = $size / 1024;
        return round($size);
    }

    /**
    * get all users from users.php
    *
    * @return users array
    */
    public static function getUsers()
    {
        global $_USERS;
        if (isset($_USERS)) {
            return $_USERS;
        }
        return null;
    }

}


/**
 * Manage download files
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Downloader
{
    /**
    * Checks if file is under user folder
    *
    * @param string $checkPath path to check
    *
    * @return true/false
    */
    public function subDir($checkPath)
    {
        global $gateKeeper;

        if ($gateKeeper->getUserInfo('dir') == null) {
            return true;
        } else {
            $userdirs = json_decode($gateKeeper->getUserInfo('dir'));
            foreach ($userdirs as $value) {
                $pos = strpos($checkPath, $value);
                if ($pos !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
    * the safe way
    *
    * @param string $checkfile file to check
    *
    * @return true/false
    */
    public function checkFile($checkfile)
    {
        global $setUp;

        $fileclean = base64_decode($checkfile);
        $file = "../".urldecode($fileclean);

        $filepathinfo = Utils::mbPathinfo($fileclean);

        $filename = $filepathinfo['basename']; 
        $safedir = $filepathinfo['dirname'];

        $safedir = str_replace(array('/', '.'), '', $safedir);
        $realfile = realpath($file);
        $realsetup = realpath(".".$setUp->getConfig('starting_dir'));

        if (strpos($realfile, $realsetup) !== false
            && $safedir != "doc-admin"
            && $safedir != "etc" 
            && $filename != "index.php"
            && $filename != "vfm-thumb.php"
            && $filename != ".htaccess"
            && $filename != ".htpasswd"
            && file_exists($file)
        ) {
            return true;
        }
        return false;
    }

    /**
    * check download lifetime
    *
    * @param string $time time to check
    *
    * @return true/false
    */
    public function checkTime($time)
    {
        global $setUp;

        $lifedays = (int)$setUp->getConfig('lifetime');
        $lifetime = 86400 * $lifedays;
        if (time() <= $time + $lifetime) {
            return true;
        }
        return false;
    }

    /**
    * Get file info before processing download
    *
    * @param string $getfile file to download
    * @param string $playmp3 check audio
    *
    * @return $headers array
    */
    public function getHeaders($getfile, $playmp3 = false)
    {
        global $utils;

        $headers = array();

        $audiofiles = array('mp3', 'MP3', 'wav', 'WAV');
        $trackfile = "./".urldecode(base64_decode($getfile));
        $file = ".".$trackfile;

        $filepathinfo = $utils->mbPathinfo($file);
        $filename = $filepathinfo['basename'];
        $dirname = $filepathinfo['dirname']."/";
        $ext = $filepathinfo['extension'];
        $file_size = File::getFileSize($file);
        $disposition = "inline";

        if ($ext == "pdf" || $ext == "PDF") {
            $content_type = "application/pdf";
        } elseif ($ext == "zip" || $ext == "ZIP") {
            $content_type = "application/zip";
            $disposition = "attachment";
        } elseif (in_array($ext, $audiofiles)
            && $playmp3 == "play"
        ) {
            $content_type = "audio/mp3";
        } else {
            $content_type = "application/force-download";
        }

        $headers['file'] = $file;
        $headers['filename'] = $filename;
        $headers['file_size'] = $file_size;
        $headers['content_type'] = $content_type;
        $headers['disposition'] = $disposition;
        $headers['trackfile'] = $trackfile;
        $headers['dirname'] = $dirname;

        return $headers;
    }

    /**
    * Android download
    *
    * @param string $file      path to download
    * @param string $filename  file name
    * @param string $file_size file size
    *
    * @return file served
    */
    public function androidDownload(
        $file, 
        $filename, 
        $file_size 
    ) {
        set_time_limit(0);
        session_write_close();
        header("Content-Length: ".$file_size);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".$filename."\"");

        @ob_flush();
        flush();
        readfile($file);
        return true;
    }
    
    /**
    * Resumable download
    *
    * @param string $file         path to download
    * @param string $filename     file name
    * @param string $file_size    file size
    * @param string $content_type header content type
    * @param string $disposition  header disposition
    *
    * @return file served
    */
    public function resumableDownload(
        $file, 
        $filename, 
        $file_size, 
        $content_type, 
        $disposition = 'inline'
    ) {
        set_time_limit(0);
        session_write_close();
        // turn off compression on the server
        ini_set('zlib.output_compression', 'Off');

        $time = filemtime($file);
        $etag = md5($file.$time);

        $fileopen = fopen($file, "rb");
        // Download speed in KB/s
        $chunk = 8*1024;
        // Initialize the range of bytes to be transferred
        $start = 0;
        $end = $file_size-1;
        // Check HTTP_RANGE variable
        if (isset($_SERVER['HTTP_RANGE']) 
            && preg_match('/^bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $arr)
        ) {
            $start = $arr[1];
            if ($arr[2]) {
                $end = $arr[2];
            }
        }
        // Check if starting and ending byte is valid
        if ($start > $end || $start > $file_size-1) {
            header("HTTP/1.1 416 Requested Range Not Satisfiable");
            header("Content-Length: 0");
            return false;
        } else {
            // For the first time download
            if ($start == 0 && $end == $file_size) {
                // Send HTTP OK header
                header("HTTP/1.1 200 OK");
            } else {
                // For resume download
                header("HTTP/1.1 206 Partial Content");
                header("Content-Range: bytes ".$start."-".$end."/".$file_size);
            }
            // Bytes left
            $left = $end-$start+1;

            //set last-modified header
            header('Last-Modified: '.date('D, d M Y H:i:s \G\M\T', $time));
            //set etag-header
            header('ETag: "'.$etag.'"');

            // Send the other headers
            header("Accept-Ranges: bytes");
            header("Content-Length: ".$left);
            header("Content-Type: $content_type");
            header("Content-Disposition: $disposition; filename=\"".$filename."\"");
            header("Content-Transfer-Encoding: binary");
            header("Expires: -1");
            // Read file from the given starting bytes
            fseek($fileopen, $start);

            while (!feof($fileopen)) {
                echo fread($fileopen, $chunk);
                @ob_flush();
                flush();
                $left-=$chunk;
                // // Delay for 10 microsecond
                // usleep(10);
            }
        }
        fclose($fileopen);
        return true;
    }

}

/**
 * Class to control password reset
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Resetter
{
    /**
    * call update user functions
    *
    * @return $message
    */
    public static function init()
    {
        global $updater;
        global $resetter;
        global $_USERS;
        global $users;
        $users = $_USERS;

        $resetpwd = filter_input(
            INPUT_POST, "reset_pwd", FILTER_SANITIZE_STRING
        );
        $resetconf = filter_input(
            INPUT_POST, "reset_conf", FILTER_SANITIZE_STRING
        );
        $userh = filter_input(
            INPUT_POST, "userh", FILTER_SANITIZE_STRING
        );
        $getrp = filter_input(
            INPUT_POST, "getrp", FILTER_SANITIZE_STRING
        );

        if ($resetpwd && $resetconf 
            && ($resetpwd == $resetconf) 
            && $userh
            && $resetter->checkTok($getrp, $userh) == true
        ) {
            $username = $resetter->getUserFromSha($userh);
            $updater->updateUserPwd($username, $resetpwd);
            $updater->updateUserFile('password');
            $resetter->resetToken($resetter->getMailFromSha($userh));
        }
    }

    /**
    * get user name from encrypted email
    *
    * @param string $usermailsha user email in SHA1
    *
    * @return username
    */
    public function getUserFromSha($usermailsha)
    {
        global $_USERS;
        $utenti = $_USERS;

        foreach ($utenti as $value) {
            if (isset($value['email']) && sha1($value['email']) === $usermailsha) {
                return $value['name'];
            }
        }
    }

    /**
    * get user mail from encrypted email
    *
    * @param string $usermailsha user email in SHA1
    *
    * @return username
    */
    public function getMailFromSha($usermailsha)
    {
        global $_USERS;
        $utenti = $_USERS;

        foreach ($utenti as $value) {
            if (isset($value['email']) && sha1($value['email']) === $usermailsha) {
                return $value['email'];
            }
        }
    }

    /**
    * get user name from email
    *
    * @param string $usermail user email
    *
    * @return username
    */
    public function getUserFromMail($usermail)
    {
        global $_USERS;
        $utenti = $_USERS;

        foreach ($utenti as $value) {
            if (isset($value['email'])) {
                if ($value['email'] === $usermail) {
                    return $value['name'];
                }
            }
        }
    }

    /**
    * reset token
    *
    * @param string $usermail user email
    *
    * @return mail to user
    */
    public function resetToken($usermail)
    {
        global $_TOKENS;
        global $tokens;
        $tokens = $_TOKENS;
        unset($tokens[$usermail]);

        $tkns = '$_TOKENS = ';

        if (false == (file_put_contents(
            'doc-admin/token.php', "<?php\n\n $tkns".var_export($tokens, true).";\n"
        ))
        ) {
            Utils::setError("error, no token reset");
            return false;
        }
    }

    /**
    * set token for password recovering
    *
    * @param string $usermail user email
    *
    * @return mail to user
    */
    public function setToken($usermail)
    {
        global $resetter;
        global $_TOKENS;
        global $tokens;
        $tokens = $_TOKENS;

        $birth = time();
        $salt = SetUp::getConfig('salt');
        $token = sha1($salt.$usermail.$birth);

        $tokens[$usermail]['token'] = $token;
        $tokens[$usermail]['birth'] = $birth;
        $tkns = '$_TOKENS = ';

        if ( false == (file_put_contents(
            'token.php', "<?php\n\n $tkns".var_export($tokens, true).";\n"
        ))
        ) {
            return false;
        } else {
            $message = array();
            $message['name'] = $resetter->getUserFromMail($usermail);
            $message['tok'] = "?rp=".$token."&usr=".sha1($usermail);
            return $message;
        }
        return false;
    }

    /**
    * check token validity and lifetime
    *
    * @param string $getrp  time to check
    * @param string $getusr getusr to check
    *
    * @return true/false
    */
    public function checkTok($getrp, $getusr)
    {
        global $_TOKENS;
        global $tokens;
        $tokens = $_TOKENS;
        $now = time();

        foreach ($tokens as $key => $value) {
            if (sha1($key) === $getusr) {
                if ($value['token'] === $getrp) {
                    if ($now < $value['birth'] + 3600 ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
} 

/**
 * The utilities for the chunk upload
 * managed by chunk.php
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Chunk
{
    /**
    * set response message
    *
    * @param string $message error message
    *
    * @return update session error
    */
    public function setError($message)
    {
        if (isset($_SESSION['error']) && $_SESSION['error'] !== $message) {
            $_SESSION['error'] .= $message;
        } else {
            $_SESSION['error'] = $message;   
        }
    }

    /**
    * set response message
    *
    * @param string $message warning message
    *
    * @return update session warning
    */
    public function setWarning($message)
    {
        if (isset($_SESSION['warning']) && $_SESSION['warning'] !== $message) {
            $_SESSION['warning'] .= $message;
        } else {
            $_SESSION['warning'] = $message;   
        }
    }

    /**
    * set response message
    *
    * @param string $message success message
    *
    * @return update session success
    */
    public function setSuccess($message)
    {
        if (isset($_SESSION['success']) && $_SESSION['success'] !== $message) {
            $_SESSION['success'] .= $message;
        } else {
            $_SESSION['success'] = $message;   
        }
    }

    /**
    * check if user has space to upload
    *
    * @param string $thissize size to check
    *
    * @return true/false
    */
    public function checkUserUp($thissize)
    {
        if (isset($_SESSION['vfm_user_used'])) {

            $oldused = $_SESSION['vfm_user_used'];
            $newused = $oldused + $thissize;
            $freespace = $_SESSION['vfm_user_space'];
            
            if ($newused > $freespace) {
                return false;
            } else {
                return true;
            }
        }
        return true;
    }

    /**
    * check if user has space to upload
    *
    * @param string $thissize size to check
    *
    * @return updated user space
    */
    public function setUserUp($thissize)
    {
        if (isset($_SESSION['vfm_user_used'])) {
            $oldused = $_SESSION['vfm_user_used'];
            $newused = $oldused + $thissize;
            $_SESSION['vfm_user_used'] = $newused;        
        }
    }

    /**
    * setup filename to upload
    *
    * @param string $resumableFilename filename to convert
    * @param string $rid               file ID
    *
    * @return resumableFilename updated
    */
    public function setupFilename($resumableFilename, $rid)
    {
        $extension = File::getFileExtension($resumableFilename);
        $filepathinfo = Utils::mbPathinfo($resumableFilename);
        $basename = Utils::normalizeStr(Utils::checkMagicQuotes($filepathinfo['filename']));

    //  change $resumableFilename to prepend date-time before file name
        $resumableFilename = $basename.".".$extension;
        // $resumableFilename = $basename."_".date('Y-m-d_G-i-s').".".$extension;

        array_push($_SESSION['upcoda'], $rid);
        array_push($_SESSION['uplist'], $resumableFilename);

        $upcoda = array_unique($_SESSION['upcoda']);
        $uplist = array_unique($_SESSION['uplist']);

        if (count($upcoda) > count($uplist)) {
            $count = count($upcoda);
            $basename = $basename.$count;
            $resumableFilename = $basename.".".$extension;
        }

        $_SESSION['upcoda'] = $upcoda;
        $_SESSION['uplist'] = $uplist;

        $resumabledata = array();

        $resumabledata['extension'] = $extension;
        $resumabledata['basename'] = $basename;
        $resumabledata['filename'] = $resumableFilename;

        return $resumabledata;
    }

    /**
     * Check if all the parts exist, and 
     * gather all the parts of the file together
     *
     * @param string $location  - the final location
     * @param string $temp_dir  - the temporary directory holding all the parts of the file
     * @param string $fileName  - the original file name
     * @param string $chunkSize - each chunk size (in bytes)
     * @param string $totalSize - original file size (in bytes)
     * @param string $logloc    - relative location for log file
     *
     * @return uploaded file
     */
    public function createFileFromChunks($location, $temp_dir, $fileName, $chunkSize, $totalSize, $logloc) 
    {
        global $chunk;
        $upload_dir = str_replace('\\', '', $location);
        $extension = File::getFileExtension($fileName);

        // count all the parts of this file
        $total_files = 0;
        foreach (scandir($temp_dir) as $file) {
            if (stripos($file, $fileName) !== false) {
                $total_files++;
            }
        }

        $finalfile = FileManager::safeExtension($fileName, $extension);

        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {

            // create the final file 
            if (($openfile = fopen($upload_dir.$finalfile, 'w')) !== false) {
                for ($i=1; $i<=$total_files; $i++) {
                    fwrite($openfile, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
                }
                fclose($openfile);

                // rename the temporary directory (to avoid access from other 
                // concurrent chunks uploads) and than delete it
                if (rename($temp_dir, $temp_dir.'_UNUSED')) {
                    Actions::deleteDir($temp_dir.'_UNUSED');
                } else {
                    Actions::deleteDir($temp_dir);
                }
                $chunk->setSuccess(" <span><i class=\"fa fa-check-circle\"></i> ".$finalfile." </span> ", "yep");
                $chunk->setUserUp($totalSize);

                $message = array(
                    'user' => GateKeeper::getUserInfo('name'), 
                    'action' => 'ADD', 
                    'type' => 'file',
                    'item' => $logloc.$finalfile
                );
                Logger::log($message, "");
                if (SetUp::getConfig("notify_upload")) {
                    Logger::emailNotification($logloc.$finalfile, 'upload');
                }


            } else {
                setError(" <span><i class=\"fa fa-exclamation-triangle\"></i> cannot create the destination file", "nope");
                return false;
            }
        }
    }

}
/**
 * The class controls the templating system
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Standard License http://codecanyon.net/licenses/standard
 * @version  Release: 1.6.8
 * @link     http://filemanager.veno.it/
*/
class Template
{
    /**
    * Check if all the parts exist 
    *
    * @param string $file     - the template part to search
    * @param string $relative - the relative path
    *
    * @return include file
    */
    function getPart($file, $relative = "doc-admin/")
    {
        global 
        $_CONFIG, 
        $_DLIST, 
        $_IMAGES, 
        $_USERS,
        $actual_link, 
        $downloader, 
        $encodeExplorer, 
        $gateKeeper, 
        $getcloud, 
        $getrp,
        $getusr, 
        $hash, 
        $location, 
        $logoclass, 
        $resetter, 
        $setUp, 
        $time; 
        
        if (file_exists($relative.'template/'.$file.'.php')) {
            $thefile = $relative.'template/'.$file.'.php';
        } else {
            $thefile =  $relative.'include/'.$file.'.php';
        }
        include $thefile;

    }
}
?>