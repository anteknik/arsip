<?php

error_reporting(E_ALL ^ E_NOTICE);
 //error_reporting(E_ALL);
 //ini_set('display_errors', 1);

require_once 'config.php';
session_name($_CONFIG["session_name"]);
session_start();
if (!isset( $_SESSION['doc_admin_name']) 
    && !isset($_SESSION['doc_admin_pass'])
) {
    header('Location:login.php');
    exit();
}
require_once 'users.php';
require 'translations/en.php';
require_once 'class.php';
require_once 'include/admin-head.php';

// $maxfiles and $maxfilesize are to avoid timeouts 
// and server errors when creating .zip files
//
// max number of files 
// for batch download
$maxfiles = 100;

// max size of .zip for mass download (in MB)
// if the sum of the files added is higher
// the script stops the process and suggests
// to download single files 
$maxfilesize = 100;

// user available quota
$_QUOTA = array(
    "10",
    "20",
    "50",
    "100",
    "200",
    "500",
    ); 

// exipration for downloadable links
$share_lifetime = array(
    // "days" => "menu value"
    "1" => "24 h",
    "2" => "48 h",
    "3" => "72 h",
    "5" => "5 days",
    "7" => "7 days",
    "10" => "10 days",
    "30" => "30 days",
    "365" => "1 year",
    ); ?>

<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php print $encodeExplorer->getString('administration')." - ".$setUp->getConfig('appname'); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="<?php echo $lang; ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="shortcut icon" href="images/favicon.ico">
    <meta name="viewport" 
    content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="doc-style.css">
    <?php 
    if ($setUp->getConfig("txt_direction") == "RTL") { ?>
        <link rel="stylesheet" href="css/bootstrap-rtl.min.css">
    <?php 
    } ?>
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <script src="js/jquery-1.11.1.min.js"></script>
    <!--[if lt IE 9]>
    <script src="js/html5.js" type="text/javascript"></script>
    <script src="js/respond.min.js" type="text/javascript"></script>
    <![endif]-->
</head>

<body class="admin-body">

    <header class="adminhead">
    <div class="container">

    <div class="topbanner">
        <?php 
        print "<a class=\"pull-right btn btn-warning btn-sm\" 
        href=\"login.php?logout\">"
        .$encodeExplorer->getString("log_out")." <i class=\"fa fa-sign-out\"></i></a>";
        ?>

    <?php
    if ($setUp->showLangMenu()) { ?>

        <div class="btn-group pull-right">
        <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
            <i class="fa fa-flag"></i>
        </button>
    <?php
        print ($encodeExplorer->printLangMenu())."</div>";
    } ?>
        </div>
        <div class="lead adminlogo"><a href="./"><?php print $setUp->getConfig('appname'); ?>
        </a></div>
    </div>

    <div class="adminmenu">

    <nav class="navbar navbar-inverse" role="navigation">
        <div class="container">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#admin-menu">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="../"><i class="fa fa-home"></i></a>
            </div>

            <div class="collapse navbar-collapse" id="admin-menu">
                <ul class="nav navbar-nav">
                <?php print "<li";
                if ($activesec == 'home') {
                    print " class=\"active\"";
                }
                print "><a href=\"".$_SERVER['PHP_SELF']."\">
                 <i class=\"fa fa-cogs\"></i> " 
                .$encodeExplorer->getString("preferences").
                "</a></li>";

                print "<li";
                if ($activesec == 'users') {
                    print " class=\"active\"";
                }
                print "><a href=\"?users=go\">
                <i class=\"fa fa-users\"></i> " 
                 .$encodeExplorer->getString("users").
                "</a></li>";

                print "<li";
                if ($activesec == 'lang') {
                    print " class=\"active\"";
                }
                print "><a href=\"?languagemanager=go\">
                    <i class=\"fa fa-language\"></i> " 
                 .$encodeExplorer->getString("languages").
                "</a></li>";
                if ($setUp->getConfig('log_file') == true) { 

                    print "<li";
    if ($activesec == 'log') {
        print " class=\"active\"";
    }
                    print "><a href=\"?log=go\">
                        <i class=\"fa fa-bar-chart-o\"></i> " 
                     .$encodeExplorer->getString("statistics").
                    "</a></li>";
                } ?>
                </ul>
            </div><!-- /.navbar-collapse -->
        </div>
    </nav>

    </div>

    </header>


<?php 
        /**
        *
        * END HEADER 
        *
        */ 
?>
<div class="container">

<section class="intero">
<?php
if (!empty($success) && !empty($status)) {
    $icona = ( ($status == 'yep') ? 'fa-check' : 'fa-times' );
    echo "<div class=\"response "
    .$status."\"><i class=\"fa ".$icona."\"></i> ".$success."</div>";
}  ?>


<?php 
    /**
    * 
    * LANGUAGE MANAGER
    *
    */ 
?>

<?php if (isset($_GET['languagemanager'])) { ?>
    <div class="intero well">

        <h3 class="pull-left">
            <i class="fa fa-language"></i> 
            <?php print $encodeExplorer->getString("language_manager"); ?>
        </h3>

        <hr class="intero">

    <?php

    if (($_GET['languagemanager'] == 'editlang' 
        && $editlang )
        || ($_GET['languagemanager'] == 'newlang' 
        && $postnewlang && strlen($postnewlang) == 2
        && !in_array($postnewlang, $translations))
    ) { ?>

        <form role="form" method="post" 
        action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?languagemanager=update" 
        class="clear intero">


        <button type="submit" class="btn btn-info pull-right btn-lg">
            <i class=\"fa fa-refresh\"></i> 
            <?php print $encodeExplorer->getString("save_settings"); ?>
        </button>

        <div class="intero">
            <h3><?php print $encodeExplorer->getString("edit").": ".$editlang; ?></h3>
            
            <div class="btn-group pull-right">
    <?php
        if ($editlang != "en") { 
            print "<a href=\"?languagemanager=update&remove=".$editlang."\" 
            class=\"btn btn-danger delete\"><i class=\"fa fa-trash-o\"></i> "
            .$encodeExplorer->getString("remove_language")."</a>";
        }

        print "</div>";
        print "<input type=\"hidden\" 
        class=\"form-control\" name=\"thenewlang\" value=\""
        .$editlang."\">";
        $index = 0;
        foreach ($baselang as $key => $voce) {
            $ide = ( ($index % 2) ? 'pull-right' : 'pull-left clear' );
            $index++; ?>
            <div class="unmezzo <?php echo $ide; ?>">
            <label><?php echo $key; ?></label>
    <?php

            if (array_key_exists($key, $_TRANSLATIONSEDIT)) {
                $tempval = $_TRANSLATIONSEDIT[$key];
            } else {
                $tempval = "";
            }
             ?>
            <input type="text" class="form-control" name="<?php echo $key; ?>" 
            value="<?php echo stripslashes($tempval); ?>"
            placeholder="<?php echo stripslashes($baselang[$key]); ?>">
            </div>
    <?php
        } ?>
        </div>
        <hr class="intero">
        <button type="submit" class="btn btn-info btn-lg btn-block">
            <i class="fa fa-refresh"></i> 
            <?php print $encodeExplorer->getString("save_settings"); ?>
        </button>
        </form>

        <?php
    } else { ?>

        <form role="form" method="post" 
        action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?languagemanager=editlang" 
        class="unmezzo pull-left">

            <div class="form-group">
                <label><?php print $encodeExplorer->getString("edit_language"); ?> </label>

                <div class="input-group">
                    <select class="form-control input-lg" name="editlang">
    <?php
        $translations = $encodeExplorer->getLanguages();

        foreach ($translations as $lingua) { ?>
            <option <?php
            if ($lingua == $thelang) {
                echo "selected";
            } ?> ><?php echo $lingua; ?></option>
    <?php
        } ?>
                    </select>
                    <span class="input-group-btn btn-group-lg">
                        <button class="btn btn-info" type="submit"><i class="fa fa-pencil-square-o"></i> 
                            <?php print $encodeExplorer->getString("edit"); ?>
                        </button>
                    </span>
                </div>
            </div>
        </form>

        <form role="form" method="post" 
        action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?languagemanager=newlang" 
        class="unmezzo pull-right">
            <div class="intero">
                <div class="form-group">
                    <label><?php print $encodeExplorer->getString("new_language"); ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control input-lg" name="newlang">
                        <span class="input-group-btn btn-group-lg">
                            <button class="btn btn-success" type="submit">
                                <i class="fa fa-plus"></i> 
                                <?php print $encodeExplorer->getString("add"); ?>
                            </button>
                        </span>
                    </div>
                </div>

                <p><?php print $encodeExplorer->getString("2_letters_iso_code"); ?>
                    <a title="view full list" class="tooltipper" data-placement="left" target="_blank" href="http://www.loc.gov/standards/iso639-2/php/code_list.php">
                        <i class="fa fa-question-circle"></i>
                    </a>
                </p>
            </div>
        </form>

    <?php
    } ?>
    </div>
    <?php
} elseif (isset($_GET['users'])) { ?>

    <?php 
    /**
    * USERS SETTINGS
    *
    */

    // get available foders for users
    $availabelFolders = array_filter($setUp->getFolders());
    
    $utenti = $_USERS;

    // get MasterAdmin ($king) 
    // and remove it from list ($utenti)
    $king = array_shift($utenti);
    $kingmail = isset($king['email']) ? $king['email'] : "";
    /**
    *
    * ADD NEW USER
    *
    */
    ?>        
        <div class="intero well">
                <h3 class="pull-left"><i class="fa fa-users"></i> 
                    <?php print $encodeExplorer->getString("users"); ?>
                </h3>

                <hr class="intero">

        <div class="unmezzo pull-left" id="newuserpanel">

            <form role="form" method="post" autocomplete="off" 
            action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?users=new" 
            class="clear intero" enctype="multipart/form-data">

            <div class="panel panel-default">
                
                <div class="panel-body">

                    <div class="form-group pull-left intero">
                        <h4><i class="fa fa-user-plus"></i> 
                            <?php print $encodeExplorer->getString("add_user"); ?></h4>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user fa-fw"></i>
                                    </span>
                                    <input type="text" 
                                    class="form-control addme" 
                                    name="newusername" 
                                    placeholder="*<?php print $encodeExplorer->getString("username"); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 form-group cooldropgroup">
                                <label class="sr-only">
                                    <?php print $encodeExplorer->getString("role"); ?>
                                </label>
                                <div class="input-group btn-group cooldrop">
                                <span class="input-group-addon">
                                    <i class="fa fa-check fa-fw"></i>
                                </span>
                                <select name="newrole" class="form-control coolselect">
                                    <option value="user">user</option>
                                    <option value="admin">admin</option>
                                    <option value="superadmin">superadmin</option>
                                </select>
                                </div>
                            </div>
                        </div> <!-- row -->

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-lock fa-fw"></i>
                                    </span>
                                    <input type="password" 
                                    name="newuserpass" 
                                    class="form-control addme" 
                                    placeholder="*<?php print $encodeExplorer->getString("password"); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope fa-fw"></i>
                                    </span>
                                    <input type="email" 
                                    name="newusermail" 
                                    class="form-control newusermail addme" 
                                    placeholder="<?php print $encodeExplorer->getString("email"); ?>">
                                </div>
                            </div>
                        </div> <!-- row -->

                        <div class="row">
                            <div class="col-md-6 form-group cooldropgroup">
                                <label>
                                    <?php print $encodeExplorer->getString("user_folder"); ?>
                                </label>
                            <?php
                        
    if (empty($availabelFolders)) {
        print "<fieldset disabled>";
    } ?>
                            <div class="input-group btn-group cooldrop">
                                <span class="input-group-addon">
                                    <i class="fa fa-sitemap fa-fw"></i>
                                </span>
                            <select name="newuserfolders[]" 
                            class="form-control assignfolder" multiple="multiple">
                        <?php
    foreach ($setUp->getFolders() as $folder) {
        print "<option value=\"".$folder."\">".$folder."</option>";
    } ?>
                            </select>
                            </div>
                            <?php
    if (empty($availabelFolders)) {
        print "</fieldset>";
    } ?>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>
                            <?php print $encodeExplorer->getString("make_directory"); ?>
                        </label>

                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-folder fa-fw"></i>
                            </span>
                            <input type="text" name="newuserfolder" 
                            class="form-control addme usrfolder getfolder assignnew" 
                            placeholder="<?php print $encodeExplorer->getString("add_new"); ?>">

                        </div>
                    </div>

                </div> <!-- row -->

                <div class="row">
                    <div class="col-md-12 form-group userquota cooldropgroup">
                        <label><?php print $encodeExplorer->getString("available_space"); ?></label>

                        <div class="input-group btn-group cooldrop">
                            <span class="input-group-addon">
                                <i class="fa fa-tachometer fa-fw"></i>
                            </span>

                    <select class="form-control coolselect" name="quota" >
                        <option value=""><?php print $encodeExplorer->getString("unlimited"); ?></option>
    <?php
    foreach ($_QUOTA as $value) {
        print "<option value=\"".$value."\">".$value."MB</option>";
    } ?>
                    </select>
                        </div>
                    </div>
                </div> <!-- row -->

                <div class="row">
                    <div class="col-xs-6 form-group">
    <?php
    if (strlen($setUp->getConfig('email_from')) > 4) { ?>
                        <div class="checkbox usernotif">
                            <label>
                            <input type="checkbox" name="usernotif"> <i class="fa fa-envelope"></i> 
                            <?php print $encodeExplorer->getString("notify_user"); ?>
                            </label>
                        </div>
    <?php 
    } ?>
                    </div>

                    <div class="col-xs-6 form-group">
                        <button class="btn btn-success btn-block pull-right">
                            <i class="fa fa-plus"></i> 
                            <small>
                                <?php print $encodeExplorer->getString("new_user"); ?>
                            </small>
                        </button>
                    </div>
                </div> <!-- row -->

              </div> <!-- intero -->

            </div> <!-- panel body -->
        </div> <!-- panel -->
    </form>
    </div> <!-- unmezzo -->


    <?php
    /**
    *
    * DISPLAY MASTER-ADMIN
    *
    */
    ?>
    <form role="form" method="post" autocomplete="off" 
    action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?users=updatemaster" 
    enctype="multipart/form-data">
        <div class="unmezzo pull-right">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="form-group pull-left intero">
                        <h4><i class="vfmi vfmi-king"></i></a> Master Admin</h4>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user fa-fw"></i>
                                    </span>
                                    <input type="hidden" class="form-control" 
                                    name="masterusernameold" 
                                    value="<?php echo $king['name']; ?>">

                                    <input type="text" class="form-control" 
                                    name="masterusername" 
                                    value="<?php echo $king['name']; ?>">
                                </div>
                            </div>

                            <div class="col-md-6 form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-check fa-fw"></i>
                                    </span>
                                    <input type="text" class="form-control" readonly 
                                    value="<?php echo $king['role']; ?>">
                                </div>
                            </div>   
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <input type="hidden" class="form-control" 
                                name="masteruserpass" 
                                value="<?php echo $king['pass']; ?>">

                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-lock fa-fw"></i>
                                    </span>
                                    <input type="password" class="form-control" 
                                    name="masteruserpassnew" 
                                    placeholder="<?php print $encodeExplorer->getString("new_password"); ?>">
                                </div>
                            </div>

                            <div class="col-md-6 form-group">
                                <input type="hidden" class="form-control" 
                                name="masterusermailold" 
                                value="<?php echo $kingmail; ?>">

                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope fa-fw"></i>
                                    </span>
                                    <input type="email" class="form-control" 
                                    name="masterusermail" 
                                    value="<?php echo $kingmail; ?>" 
                                    placeholder="<?php print $encodeExplorer->getString("email"); ?>">
                                </div>
                            </div>
                            <div class="col-xs-6 form-group">
                            </div>
                            <div class="col-xs-6 form-group">
                                <button class="btn btn-info btn-block pull-right">
                                    <i class="fa fa-refresh"></i> 
                                    <small>
                                        <?php print $encodeExplorer->getString("update_profile"); ?>
                                    </small>
                                </button>
                            </div>
                        </div> <!-- row -->
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="intero clear">
    
    <?php
    /**
    *
    * LIST USERS
    *
    */
    foreach ($utenti as $key => $user) {
        $ide = ( ($key % 2) ? 'pull-right' : 'pull-left clear' );
        $usermail = isset($user['email']) ? $user['email'] : "";
        $userquota = isset($user['quota']) ? $user['quota'] : "";
        $userdirs = isset($user['dir']) ? json_decode($user['dir']) : false;
        ?>
        <div class="unmezzo <?php echo $ide; ?>">

            <div class="pull-left intero usrblock">
                        <button class="btn btn-block usrindex" data-toggle="modal" data-target="#modaluser">
                        <i class="fa fa-pencil-square-o" ></i> <strong><?php echo $user['name']; ?></strong> 
                        <em class="small"><?php echo "(".$user['role'].")";?></em></button>
                        <input type="hidden" value="<?php echo $user['name']; ?>" class="s-username">
                        <input type="hidden" value="<?php echo $user['pass']; ?>" class="s-userpass">
                        <input type="hidden" value="<?php echo $userquota; ?>" class="s-quota">
                        <input type="hidden" value="<?php echo $usermail; ?>" class="s-usermail">
                        <input type="hidden" value="<?php echo $user['role']; ?>" class="s-role">
        <?php
        if ($userdirs) {
            foreach ($userdirs as $dir) {
                echo " <input type=\"hidden\" value=\"".$dir."\" class=\"s-userfolders\">";
            }
        }
        ?>

            </div> <!-- usrblock -->
        </div> <!-- unmezzo -->
    <?php
       
    } // end foreach ?>


    </div> <!-- intero -->

    <?php
    /**
    *
    * MODAL USER PANEL
    *
    */
    ?>          
    <div class="modal fade" id="modaluser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-user"></i> 
                        <span class="modalusername"></span>
                    </h4>
                </div>
                <div class="modal-body">
                    <form role="form" method="post" autocomplete="off" 
                    action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?users=update" 
                    enctype="multipart/form-data" class="removegroup">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user fa-fw"></i>
                                </span>
                                <input type="hidden" class="form-control" 
                                name="usernameold" id="r-usernameold"
                                value="">

                                <input type="text" class="form-control deleteme" 
                                name="username" id="r-username"
                                value="">
                            </div>
                        </div>

                        <div class="col-md-6 form-group cooldropgroup">
                                <label class="sr-only">
                                    <?php print $encodeExplorer->getString("role"); ?>
                                </label>
                            <div class="input-group btn-group cooldrop">
                                <span class="input-group-addon">
                                    <i class="fa fa-check fa-fw"></i>
                                </span>
                                <select class="form-control coolselect" name="role" id="r-role">
                                    <option value="user">user</option>
                                    <option value="admin">admin</option>
                                    <option value="superadmin">superadmin</option>
                                </select>
                                </div>
                            </div>
                      
                        </div> <!-- row -->

                        <div class="row">

                            <div class="col-md-6 form-group">
                                <input type="hidden" class="form-control" 
                                name="userpass" id="r-userpass"
                                value="">

                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-lock fa-fw"></i>
                                    </span>
                                    <input type="password" class="form-control" 
                                    name="userpassnew" id="r-userpassnew"
                                    placeholder="<?php print $encodeExplorer->getString("new_password"); ?>">
                                </div>
                            </div>

                            <div class="col-md-6 form-group">
                                <input type="hidden" class="form-control" 
                                name="usermailold" id="r-usermailold"
                                value="">

                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope fa-fw"></i>
                                    </span>
                                    <input type="email" class="form-control" 
                                    name="usermail" id="r-usermail"
                                    value="" 
                                    placeholder="<?php print $encodeExplorer->getString("email"); ?>">
                                </div>
                            </div>

                        </div> <!-- row -->

                        <div class="row">
                            <div class="col-md-6 form-group cooldropgroup">
                                <label>
                                    <?php print $encodeExplorer->getString("user_folder"); ?>
                                </label>
    <?php
    if (empty($availabelFolders)) {
        print "<fieldset disabled>";
    } ?>
                                <div class="input-group btn-group cooldrop">
                                    <span class="input-group-addon">
                                        <i class="fa fa-sitemap fa-fw"></i>
                                    </span>
                                    <select name="userfolders[]" id="r-userfolders" 
                                    class="form-control assignfolder" multiple="multiple">
    <?php
    foreach ($setUp->getFolders() as $folder) {
        print "<option value=\"".$folder."\"";
        print ">".$folder."</option>";

    } ?>
                                    </select>
                                </div>
    <?php
    if (empty($availabelFolders)) {
        print "</fieldset>";
    } ?>
                            </div>
                            <div class="col-md-6">
                                <label><?php print $encodeExplorer->getString("make_directory"); ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-folder fa-fw"></i>
                                    </span>
                                    <input type="text" class="form-control getfolder assignnew" 
                                    name="userfolder" 
                                    placeholder="<?php print $encodeExplorer->getString("add_new"); ?>">
                                </div>
                            </div> <!-- col-md-6 form-group -->
                        </div> <!-- row -->

                        <div class="row" style="min-height:60px;">
                            <div class="col-md-6 userquota cooldropgroup">
                                <label><?php print $encodeExplorer->getString("available_space"); ?></label>

                                <div class="input-group btn-group cooldrop">
                                    <span class="input-group-addon">
                                        <i class="fa fa-tachometer fa-fw"></i>
                                    </span>

                                    <select class="form-control coolselect" name="quota" id="r-quota">
                                    <option value=""><?php print $encodeExplorer->getString("unlimited"); ?></option>
    <?php
    foreach ($_QUOTA as $value) {
        print "<option value=\"".$value."\">".$value."MB</option>";
    } ?>
                                    </select>
                                </div> <!-- input-group -->
                            </div> <!-- col-md-6 userquota -->
                        </div> <!-- row -->

                        <div class="row">
                            <div class="col-md-12 form-group">

                                <div class="btn-group pull-right">
                                    <button class="btn btn-info">
                                        <i class="fa fa-refresh"></i> 
                                        <small>
                                            <?php print $encodeExplorer->getString("update_profile"); ?>
                                        </small>
                                    </button>

                                    <button class="btn btn-danger remove">
                                        <i class="fa fa-trash-o"></i> 
                                        <small><?php print $encodeExplorer->getString("delete"); ?></small>
                                        <input type="hidden" name="delme" class="delme" value="">
                                    </button>
                                </div><!-- btn-group -->
                            </div><!-- col-md-12 form-group -->
                        </div><!-- row -->
                    </form>
                </div> <!-- intero -->

            </div><!-- modal -->
        </div>
    </div>
    </div> <!-- intero well -->

    <?php

} elseif (isset($_GET['log'])) { ?>

    <?php 
    /**
    * ANALYTICS
    *
    */
    $getday = false;
    $day = false;
    if (isset($_GET['range'])) {
        $range = $_GET['range'];
    } elseif (isset($_GET['day']) && strlen($_GET['day'] > 0)) {
        $day = $_GET['day'];
        $logs = array($_GET['day'].".json");
        $getday = true;
    } else {
        $range = 1;
    }

    $loglist = array_diff(scandir('log/'), array(".", "..", ".DS_Store", ".svn", ".htaccess"));
    $loglist = array_reverse(array_values(preg_grep('/^([^.])/', $loglist)));

    if ($getday == false) {
        $logs = array_slice($loglist, 0, $range);
    }
    $result = array();

    ?>

    <section class="intero well">
        <h3 class="pull-left">
        <i class="fa fa-bar-chart-o"></i>
        <?php print $encodeExplorer->getString("statistics"); ?>
        </h3>
    <hr class="intero">
    <div class="intero">
        <form class="form-inline selectdate" method="get">
            <input type="hidden" value="go" name="log">
            <div class="form-group">
                <div class="btn-group pull-left">
                    <a href="?log=go&range=1" class="btn btn-default <?php if ($range == 1) echo "active"; ?>">
                        <span class="fa-stack stackalendar">
                          <i class="fa fa-calendar-o fa-stack-2x"></i>1
                        </span>
                    </a>
                    <a href="?log=go&range=7" class="btn btn-default <?php if ($range == 7) echo "active"; ?>">
                        <span class="fa-stack stackalendar">
                            <i class="fa fa-calendar-o fa-stack-2x"></i>7
                        </span>
                    </a>
                    <a href="?log=go&range=30" class="btn btn-default <?php if ($range == 30) echo "active"; ?>">
                        <span class="fa-stack stackalendar">
                          <i class="fa fa-calendar-o fa-stack-2x"></i>30
                        </span>
                    </a>
                </div>
            </div>
            <div class="form-group">
                <?php 
                echo "<select name=\"day\" class=\"form-control\" onchange=\"this.form.submit()\"><option>"
                .$encodeExplorer->getString("select_date")."</option>";
    foreach ($loglist as $item) {
        
        $ext = substr($item, -4); 
        $name = substr($item, 0, -5);

        if ($ext == "json") { 
            print "<option ";
            if ($day == $name) {
                print "selected ";
            }
            print "value=\"".$name."\">".$name."</option>";
        }
    }
                echo "</select>";
                ?>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <h3><?php print $encodeExplorer->getString("main_activities"); ?></h3>

            <div class="col-sm-6 col-xs-12">
                <div class="canvas-holder">
                    <canvas class="chart" id="pie" height="400" width="400"></canvas>
                </div>
            </div>

            <div class="col-sm-6 col-xs-12">
                <p><?php print $encodeExplorer->getString("action"); ?></p>
                <div class="legend" id="mainLegend"></div>
            </div>

        </div>

        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12" id="chart-download">
            <div class="canvas-holder">
                <h3><i class="fa fa-cloud-download"></i> <?php print $encodeExplorer->getString("downloads"); ?> <span class="num-down"></span></h3>
                <canvas class="chart" id="polar-down" width="400" height="400"></canvas>
                <div class="showdata screen-down"></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12" id="chart-play">
            <div class="canvas-holder">
                <h3><i class="fa fa-play-circle-o"></i> <?php print $encodeExplorer->getString("play"); ?> <span class="num-play"></span></h3>
                <canvas class="chart" id="polar-play" width="400" height="400"></canvas>
                <div class="showdata screen-play"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table statistics table-hover table-condensed" id="sortanalytics" width="100%">
                  <thead>
                      <tr>
                          <th><span class="sorta"><?php print $encodeExplorer->getString("day"); ?></span></th>
                          <th><span class="sorta">hh:mm:ss</span></th>
                          <th><span class="sorta"><?php print $encodeExplorer->getString("user"); ?></span></th>
                          <th><span class="sorta"><?php print $encodeExplorer->getString("action"); ?></span></th>
                          <th><span class="sorta"><?php print $encodeExplorer->getString("type"); ?></span></th>
                          <th><span class="sorta"><?php print $encodeExplorer->getString("file_name"); ?></span></th>
                      </tr>
                   </thead>
                   <tbody>
    <?php
    foreach ($logs as $log) { 
        $ext = substr($log, -4); 
        if ($ext == "json") { 
            $resultnew = json_decode(file_get_contents('log/'.$log), true);
            $result = array_merge($result, $resultnew);
        }
    }
    $numup = 0;
    $numdel = 0;
    $numplay = 0;
    $numdown = 0;

    $polarplay = array();
    $polardown = array();

    $polardowncount = 0;
    $polarplaycount = 0;

    foreach ($result as $key => $value) {
        //echo "DAY: ".$key."<br>";
        foreach ($value as $kiave => $day) {

            $item = $day['item'];

            if ($day['action'] == 'ADD') {
                $numup++;
                $contextual = "success";
            } 
            if ($day['action'] == 'REMOVE') {
                $numdel++;
                $contextual = "danger";
            }
            if ($day['action'] == 'PLAY') {
                $numplay++;
                $polarplaycount++;
                if (isset($polarplay[$item])) {
                    $polarplay[$item] = $polarplay[$item] +1;
                } else {
                    $polarplay[$item] = 1;
                }
                $contextual = "warning";
            }
            if ($day['action'] == 'DOWNLOAD') {
                $numdown++;
                $polardowncount++;
                if (isset($polardown[$item])) {
                    $polardown[$item] = $polardown[$item] +1;
                } else {
                    $polardown[$item] = 1;
                }
                $contextual = "info";
            }
            echo "<tr class=\"".$contextual."\">";

            echo "<td>".$key."</td>";
            echo "<td>".$day['time']."</td>";
            echo "<td>".$day['user']."</td>";
            echo "<td>".$encodeExplorer->getString(strtolower($day['action']))."</td>";
            echo "<td>".$day['type']."</td>";
            echo "<td>".$day['item']."</td>";
        }
    }
    ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><span class="sorta"><?php print $encodeExplorer->getString("day"); ?></span></td>
                            <td><span class="sorta">hh:mm:ss</span></td>
                            <td><span class="sorta"><?php print $encodeExplorer->getString("user"); ?></span></td>
                            <td><span class="sorta"><?php print $encodeExplorer->getString("action"); ?></span></td>
                            <td><span class="sorta"><?php print $encodeExplorer->getString("type"); ?></span></td>
                            <td><span class="sorta"><?php print $encodeExplorer->getString("item"); ?></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    </section>
    <?php
    /**
    * Generate random rgb color
    *
    * @return color
    */ 
    function randomColor() 
    {
        $color = mt_rand(0, 255).",".mt_rand(0, 255).",".mt_rand(0, 255);
        return $color;
    }
    $colorplay = randomColor();
    $colordown = randomColor();
    ?>
    <script type="text/javascript" src="js/datatables.js"></script>
    <script type="text/javascript" src="js/chart.min.js"></script>

    <script>
        var pieData = [
                {
                    value: <?php echo $numup; ?>,
                    color:"#5cb85c",
                    highlight: "#32b836",
                    title: "<?php print $encodeExplorer->getString('add'); ?> ",
                    label: "<?php print $encodeExplorer->getString('add'); ?> "
                },
                {
                    value :  <?php echo $numdel; ?>,
                    color : "#d9534f",
                    highlight: "#d9211e",
                    title: "<?php print $encodeExplorer->getString('remove'); ?>",
                    label: "<?php print $encodeExplorer->getString('remove'); ?>"
                },
                {
                    value :  <?php echo $numplay; ?>,
                    color : "#f0ad4e",
                    highlight: "#f09927",
                    title: "<?php print $encodeExplorer->getString('play'); ?>",
                    label: "<?php print $encodeExplorer->getString('play'); ?>"
                },
                {
                    value :  <?php echo $numdown; ?>,
                    color : "#5bc0de",
                    highlight: "#16b5de",
                    title: "<?php print $encodeExplorer->getString('download'); ?>",
                    label: "<?php print $encodeExplorer->getString('download'); ?>"
                }
            ];

        var polarDataPlay = [
        <?php 

    arsort($polarplay);
    $highest = (!empty($polarplay) ? max($polarplay) : 1);

    foreach ($polarplay as $key => $value) {
        $gradient = $value/$highest;
        echo "{ value: ".$value.",";
        echo "label: '".$key."',";
        echo "title: '".$key."',";
        echo "color:\"rgba(".$colorplay.",".$gradient.")\",";
        echo "highlight:\"rgba(".$colorplay.",0.6)\"},";
    }
         ?>
        ];

        var polarDataDown = [
        <?php 

    arsort($polardown);
    $highest = (!empty($polardown) ? max($polardown) : 1);

    foreach ($polardown as $key => $value) {
        $gradient = $value/$highest;
        echo "{ value: ".$value.",";
        echo "label: '".$key."',";
        echo "title: '".$key."',";
        echo "color:\"rgba(".$colordown.",".$gradient.")\",";
        echo "highlight:\"rgba(".$colordown.",0.6)\"},";
    }
         ?>
        ];

        $(".num-play").html('(<?php echo $numplay; ?>)');
        $(".num-down").html('(<?php echo $numdown; ?>)');

        <?php
    if ($numdown < 1) {
        echo "$(\"#chart-download\").remove();";
    }
    if ($numplay < 1) {
        echo "$(\"#chart-play\").remove();";
    } ?>

    </script>
    <script type="text/javascript" src="js/statistics.js"></script>

    <?php

} else { 
    /**
    *
    * CONFIG SETTINGS
    *
    */ 
    /**
    * Timezones list with GMT offset
    *
    * @return array
    */
    function tzList() 
    {
        $zones_array = array();
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zones_array[$key]['zone'] = $zone;
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }
        return $zones_array;
    }
    ?>

        <form role="form" method="post" id="settings-form"
        action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" 
        class="clear intero" enctype="multipart/form-data">
            <div class="intero">
            <div class="intero well">

        <h3 class="pull-left"><i class="fa fa-cogs"></i> 
            <?php print $encodeExplorer->getString("general_settings"); ?></h3>

            <button type="submit" class="btn btn-info pull-right">
                <i class="fa fa-refresh"></i> 
                <?php print $encodeExplorer->getString("save_settings"); ?>
            </button>

        <hr class="intero">

            <input type="hidden" class="form-control" 
            value="<?php print $setUp->getConfig('logo'); ?>" name="logo">

            <div class="unmezzo clear pull-left">

                <div class="form-group">
                    <label><?php print $encodeExplorer->getString("app_name"); ?></label>
                    <input type="text" class="form-control" 
                    value="<?php print $setUp->getConfig('appname'); ?>" name="appname">
                </div>

                <div class="form-group">
                    <label><?php print $encodeExplorer->getString("description"); ?></label>
                    <textarea class="form-control" rows="2" name="description"><?php print $setUp->getConfig('description'); ?></textarea>
                </div>


                <div class="form-group">

                    <label><?php print $encodeExplorer->getString("skin"); ?></label>
                    <select class="form-control" name="skin">
    <?php
    $skins = array_diff(scandir('skins'), array(".", "..", ".DS_Store", ".svn"));
    $skins = preg_grep('/^([^.])/', $skins);

    foreach ($skins as $skin) { 
        $ext = substr($skin, -3); 
        if ($ext == "css") { ?>
            <option <?php
            if ($skin == $setUp->getConfig('skin')) {
                echo "selected";
            } ?> ><?php echo $skin; ?></option>
    <?php
        }
    } ?>
                    </select>
                </div>

        <hr>

                <div class="checkbox checkbox-big clear">
                    <label>
                        <input type="checkbox" name="require_login" <?php
    if ($setUp->getConfig('require_login')) {
                            echo "checked";
    } ?>><i class="fa fa-lock"></i> 
    <?php print $encodeExplorer->getString("require_login"); ?>
                    </label>
                </div>


                <div class="checkbox clear">
                    <label>
                        <input type="checkbox" name="show_captcha" <?php
    if ($setUp->getConfig('show_captcha')) {
        echo "checked";
    } ?>> <i class="fa fa-shield"></i> <i class="fa fa-sign-in"></i>  


    <?php print $encodeExplorer->getString("show_captcha"); ?>
                    </label>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="show_captcha_reset" <?php
    if ($setUp->getConfig('show_captcha_reset')) {
        echo "checked";
    } ?>> <i class="fa fa-shield"></i> <i class="fa fa-key fa-flip-horizontal"></i> 
    <?php print $encodeExplorer->getString("show_captcha_reset"); ?>
                    </label>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="show_usermenu" <?php
    if ($setUp->getConfig('show_usermenu')) {
        echo "checked";
    } ?>><i class="fa fa-user"></i> 
    <?php print $encodeExplorer->getString("show_usermenu"); ?>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="show_langmenu" <?php
    if ($setUp->getConfig('show_langmenu')) {
        echo "checked";
    } ?>><i class="fa fa-flag"></i> 
    <?php print $encodeExplorer->getString("show_langmenu"); ?>
                    </label>
                </div>

                <div class="checkbox">
                    <label>

                        <input type="checkbox" name="show_head" <?php
    if ($setUp->getConfig('show_head')) {
        echo "checked";
    } ?>><i class="fa fa-certificate"></i> 
    <?php print $encodeExplorer->getString("show_head"); ?>
                    </label>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="show_path" <?php
    if ($setUp->getConfig('show_path')) {
                            echo "checked";
    } ?>><i class="fa fa-ellipsis-h"></i> 
    <?php print $encodeExplorer->getString("display_breadcrumbs"); ?>
                    </label>
                </div>
                <hr>
                <div class="checkbox clear">
                    <label>
                        <input type="checkbox" name="upload_enable" <?php
    if ($setUp->getConfig('upload_enable')) {
        echo "checked";
    } ?>><i class="fa fa-upload"></i> 
    <?php print $encodeExplorer->getString("can_file"); ?>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="newdir_enable" <?php
    if ($setUp->getConfig('newdir_enable')) {
        echo "checked";
    } ?>><i class="fa fa-folder"></i> 
    <?php print $encodeExplorer->getString("can_folder"); ?>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="delete_enable" <?php
    if ($setUp->getConfig('delete_enable')) {
        echo "checked";
    } ?>> <i class="fa fa-trash-o"></i> 
    <?php print $encodeExplorer->getString("can_del"); ?>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="rename_enable" <?php
    if ($setUp->getConfig('rename_enable')) {
        echo "checked";
    } ?>><i class="fa fa-pencil-square-o"></i> 
    <?php print $encodeExplorer->getString("can_rename"); ?>
                    </label>
                </div>
                <hr>

            <div class="checkbox clear checkbox-bigger">
                <label>
                    <input type="checkbox" name="playmusic" <?php
    if ($setUp->getConfig('playmusic') == true) {
        echo "checked";
    } ?>><i class="fa fa-play-circle fa-fw"></i> 
    <?php print $encodeExplorer->getString("mp3_player"); ?>
                </label>
            </div>

        <div class="checkbox clear checkbox-bigger">
            <label>
                <input type="checkbox" name="inline_thumbs" <?php
    if ($setUp->getConfig('inline_thumbs')) {
        echo "checked";
    } ?>><i class="fa fa-picture-o fa-fw"></i>

    <?php print $encodeExplorer->getString("inline_thumbs"); ?>
            </label>
        </div>

            <div class="checkbox clear checkbox-bigger toggle">
                <label>
                    <input type="checkbox" name="thumbnails" <?php
    if ($setUp->getConfig('thumbnails')) {
        echo "checked";
    } ?>><i class="fa fa-desktop fa-fw"></i>

    <?php print $encodeExplorer->getString("can_thumb"); ?>
                </label>
            </div>
            <div class="row toggled">
                <div class="form-group col-xs-6">
                    <label><?php print $encodeExplorer->getString("thumb_w"); ?></label>
                    <input type="text" class="form-control" name="thumbnails_width" 
                    value="<?php print $setUp->getConfig('thumbnails_width'); ?>">
                </div>
                <div class="form-group col-xs-6">
                    <label><?php print $encodeExplorer->getString("thumb_h"); ?></label>
                    <input type="text" class="form-control" name="thumbnails_height" 
                    value="<?php print $setUp->getConfig('thumbnails_height'); ?>">
                </div>
            </div>
        <hr>

            <div class="checkbox clear toggle checkbox-bigger">
                <label>
                <input type="checkbox" name="sendfiles_enable" <?php
    if ($setUp->getConfig('sendfiles_enable') == true) {
        echo "checked";
    } ?>><i class="fa fa-paper-plane-o"></i> 
    <?php print $encodeExplorer->getString("share_files"); ?>
                </label>
            </div>

            <div class="form-group toggled">
                <label><?php print $encodeExplorer->getString("keep_links"); ?></label>
                <select class="form-control" name="lifetime">
    <?php 
    foreach ($share_lifetime as $key => $value) {
        echo "<option ";
        if ($setUp->getConfig('lifetime') == $key) {
            echo "selected ";
        }
        echo "value=\"".$key."\">".$value."</option>";
    } ?>
                </select>

                <div class="checkbox checkbox-big">
                    <label>
                    <input type="checkbox" name="secure_sharing" <?php
    if ($setUp->getConfig('secure_sharing') == true) {
        echo "checked";
    } ?>><i class="fa fa-key"></i> 
        <?php print $encodeExplorer->getString("password_protection"); ?>
                    </label>
                </div>
            </div>


            <hr>

            <div class="checkbox checkbox-bigger toggle clear">
                <label>
                    <input type="checkbox" name="enable_prettylinks" id="disable-prettylinks" <?php
    if ($setUp->getConfig('enable_prettylinks')) {
            echo "checked";
    } ?>>/ 
    <?php 
    print $encodeExplorer->getString("prettylinks"); ?> 
                </label>
            </div>
            <div class="row toggled">
                <div class="form-group col-xs-12">
                    <strong><?php print $encodeExplorer->getString("prettylink_old"); ?></strong>:<br><code>/doc-admin/doc-downloader.php?q=xxx</code><br>
                    <strong><?php print $encodeExplorer->getString("prettylink"); ?></strong>:<br><code>/download/xxx</code>
                </div>
            </div>

        </div> <!-- .unmezzo left -->

        <div class="unmezzo pull-right">
            <div class="form-group row">
            <div class="col-sm-12 col-md-4">

                <label>
                    <?php print $encodeExplorer->getString("default_lang"); ?>
                </label>
                <select class="form-control" name="lang">
    <?php

    foreach ($translations as $lingua) { ?>
        <option <?php
        if ($lingua == $setUp->getConfig('lang')) {
            echo "selected";
        } ?> ><?php echo $lingua; ?></option>
    <?php
    } ?>
                </select>
            </div>

            <div class="col-xs-6 col-sm-6 col-md-4">
                <label><?php print $encodeExplorer->getString("direction"); ?></label>
                <select class="form-control" name="txt_direction">
                    <option value="LTR" <?php
    if ($setUp->getConfig('txt_direction') == "LTR") {
                echo "selected";
    } ?> >Left to Right</option>
                    <option value="RTL" <?php
    if ($setUp->getConfig('txt_direction') == "RTL") {
                echo "selected";
    } ?> >Right to Left</option>
                </select>
            </div>

            <div class="col-xs-6 col-sm-6 col-md-4">
                <label><?php print $encodeExplorer->getString("time_format"); ?></label>
                <select class="form-control" name="time_format">
                    <option <?php
    if ($setUp->getConfig('time_format') == "d/m/Y - H:i") {
                echo "selected";
    } ?> >d/m/Y</option>
                    <option <?php
    if ($setUp->getConfig('time_format') == "m/d/Y - H:i") {
                echo "selected";
    } ?> >m/d/Y</option>
                    <option <?php
    if ($setUp->getConfig('time_format') == "Y/m/d - H:i") {
                echo "selected";
    } ?> >Y/m/d</option>
                </select>
            </div>

        </div>
            <div class="form-group">
    <?php 
    if (strlen($setUp->getConfig('default_timezone')) < 3 ) {
        $thistime = "UTC";
    } else {
        $thistime = $setUp->getConfig('default_timezone');
    } ?>
            <label><?php print $encodeExplorer->getString("default_timezone"); ?></label>
            <select class="form-control" name="default_timezone">
                    <?php 
    foreach (tzList() as $tim) { 
        print "<option value=\"".$tim['zone']."\" ";
        if ($tim['zone'] == $thistime) {
            print "selected";
        }
        print ">".$tim['diff_from_GMT'] . ' - ' . $tim['zone']."</option>";
    } ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fa fa-folder-open"></i> 
                <?php print $encodeExplorer->getString("uploads_dir"); ?>
            </label>
    <?php
        $cleandir = substr($setUp->getConfig('starting_dir'), 2);
        $cleandir = substr_replace($cleandir, "", -1); ?>
            <input type="text" class="form-control blockme" 
            name="starting_dir" value="<?php echo $cleandir; ?>">
        </div>

        <div class="form-group">
            <label><?php print $encodeExplorer->getString("rejected_ext"); ?></label>
            <?php $rejectlist = implode(",", $setUp->getConfig('upload_reject_extension')); ?>
            <input type="text" class="form-control" name="upload_reject_extension" 
            value="<?php echo $rejectlist; ?>">
        </div>

        <div class="form-group">
            <label>
                <i class="fa fa-envelope"></i> 
                <?php print $encodeExplorer->getString("email_notifications"); ?>
            </label>
            <input type="email" class="form-control" 
            name="upload_email" value="<?php print $setUp->getConfig('upload_email'); ?>">
            <span class="help-block"><?php print $encodeExplorer->getString("set_email_to_receive_notifications"); ?></span>
        </div>

        <p class="help-block"><?php print $encodeExplorer->getString("select_notification"); ?>:</p>
                    <input class="checkbox" id="notify_login" type="checkbox" name="notify_login" <?php
    if ($setUp->getConfig('notify_login')) {
            echo "checked";
    } ?>><label for="notify_login" data-toggle="tooltip" class="tooltipper" data-placement="top" 
    title="<?php print $encodeExplorer->getString("notify_login"); ?>">
    <i class="fa fa-sign-in"></i>
                </label>

                    <input class="checkbox" id="notify_upload" type="checkbox" name="notify_upload" <?php
    if ($setUp->getConfig('notify_upload')) {
            echo "checked";
    } ?>><label for="notify_upload" data-toggle="tooltip" class="tooltipper" data-placement="top" 
    title="<?php print $encodeExplorer->getString("notify_upload"); ?>">
    <i class="fa fa-upload"></i>
                </label>
    
                
                    <input class="checkbox" id="notify_download" type="checkbox" name="notify_download" <?php
    if ($setUp->getConfig('notify_download')) {
            echo "checked";
    } ?>><label for="notify_download" data-toggle="tooltip" class="tooltipper" data-placement="top" 
    title="<?php print $encodeExplorer->getString("notify_download"); ?>">
    <i class="fa fa-download"></i>
    
                </label>
                
                    <input class="checkbox" id="notify_newfolder" type="checkbox" name="notify_newfolder" <?php
    if ($setUp->getConfig('notify_newfolder')) {
            echo "checked";
    } ?>><label for="notify_newfolder" data-toggle="tooltip" class="tooltipper" data-placement="top" 
    title="<?php print $encodeExplorer->getString("notify_newfolder"); ?>">
    <i class="fa fa-folder-o"></i>
    
                </label>
        
        <hr>
        <div class="form-group intero clear">

        <label><?php print $encodeExplorer->getString("upload_progress"); ?></label>
        <div class="radio pro">
            <label>
                <input type="radio" name="progressColor" value="" 
    <?php
    if ($setUp->getConfig('progress_color') == "") {
                        echo "checked";
    } ?>>
                    <div class="progress progress-striped active">
                      <div class="progress-bar"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 45%">
                        <p class="pull-left propercent">45%</p>
                      </div>
                    </div>
                </label>
            </div>
            <div class="radio pro">
              <label>
                <input type="radio" name="progressColor" value="progress-bar-info"
    <?php
    if ($setUp->getConfig('progress_color') == "progress-bar-info") {
                        echo "checked";
    } ?>>
                    <div class="progress progress-striped active">
                      <div class="progress-bar progress-bar-info"  role="progressbar" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="width: 65%">
                        <p class="pull-left propercent">65%</p>
                      </div>
                    </div>
              </label>
            </div>
            <div class="radio pro">
              <label>
                <input type="radio" name="progressColor" value="progress-bar-success"
    <?php
    if ($setUp->getConfig('progress_color') == "progress-bar-success") {
                        echo "checked";
    } ?>>
                    <div class="progress progress-striped active">
                      <div class="progress-bar progress-bar-success"  role="progressbar" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100" style="width: 35%">
                        <p class="pull-left propercent">35%</p>
                      </div>
                    </div>
              </label>
            </div>
            <div class="radio pro">
              <label>
                <input type="radio" name="progressColor" value="progress-bar-warning"
    <?php
    if ($setUp->getConfig('progress_color') == "progress-bar-warning") {
                        echo "checked";
    } ?>>
                    <div class="progress progress-striped active">
                      <div class="progress-bar progress-bar-warning"  role="progressbar" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" style="width: 85%">
                        <p class="pull-left propercent">85%</p>
                      </div>
                    </div>
              </label>
            </div>
            <div class="radio pro">
              <label>
                <input type="radio" name="progressColor" value="progress-bar-danger"
    <?php
    if ($setUp->getConfig('progress_color') == "progress-bar-danger") {
                        echo "checked";
    } ?>>
                    <div class="progress progress-striped active">
                      <div class="progress-bar progress-bar-danger"  role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%">
                        <p class="pull-left propercent">75%</p>
                      </div>
                    </div>
              </label>
            </div>

            <div class="checkbox clear intero">
                <label>
                    <input type="checkbox" name="show_percentage" id="percent" <?php
    if ($setUp->getConfig('show_percentage')) {
            echo "checked";
    } ?>>
    <?php 
    print $encodeExplorer->getString("show_percentage"); ?> %
                </label>
            </div>

            <div class="checkbox clear intero">
                <label>
                    <input type="checkbox" name="single_progress" id="single-progress" <?php
    if ($setUp->getConfig('single_progress')) {
            echo "checked";
    } ?>>
                    <div class="progress progress-single">
                      <div class="progress-bar"  role="progressbar" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="width: 65%">
                        <p class="pull-left propercent"><?php print $encodeExplorer->getString("single_progress"); ?></p>
                      </div>
                    </div>

                </label>
            </div>

            <hr>

            <div class="checkbox clear intero checkbox-bigger">
                <label>
                    <input type="checkbox" name="show_search" <?php
    if ($setUp->getConfig('show_search')) {
            echo "checked";
    } ?>><i class="fa fa-search"></i> 
    <?php 
    print $encodeExplorer->getString("show_search"); ?>
                </label>
            </div>

            <div class="checkbox clear toggle checkbox-bigger">
                <label>
                    <input type="checkbox" name="show_pagination" <?php
    if ($setUp->getConfig('show_pagination')) {
            echo "checked";
    } ?>> <i class="fa fa-caret-left"></i> <i class="fa fa-list"></i> <i class="fa fa-caret-right"></i>
    <?php 
    print $encodeExplorer->getString("show_pagination"); ?>
                </label>
            </div>
            <div class="toggled intero">
                <div class="intero">

                <label class="radio-inline">
                  <input type="radio" name="filedefnum" <?php
    if ($setUp->getConfig('filedefnum') == 10) {
        echo "checked";
    } ?> value="10"> 10
                </label>
                <label class="radio-inline">
                  <input type="radio" name="filedefnum" <?php
    if ($setUp->getConfig('filedefnum') == 25) {
        echo "checked";
    } ?> value="25"> 25
                </label>
                <label class="radio-inline">
                  <input type="radio" name="filedefnum" <?php
    if ($setUp->getConfig('filedefnum') == 50) {
        echo "checked";
    } ?> value="50"> 50
                </label>
                <label class="radio-inline">
                  <input type="radio" name="filedefnum" <?php
    if ($setUp->getConfig('filedefnum') == 100) {
        echo "checked";
    } ?> value="100"> 100
                </label>
            </div>
            <div class="intero">
                <label class="radio-inline">
                  <input type="radio" name="filedeforder" <?php
    if ($setUp->getConfig('filedeforder') == "alpha") {
        echo "checked";
    } ?> value="alpha"> <i class="fa fa-sort-alpha-asc"></i>
                </label>
                <label class="radio-inline">
                  <input type="radio" name="filedeforder" <?php
    if ($setUp->getConfig('filedeforder') == "date") {
        echo "checked";
    } ?> value="date"> <i class="fa fa-calendar"></i>
                </label>
                <label class="radio-inline">
                  <input type="radio" name="filedeforder" <?php
    if ($setUp->getConfig('filedeforder') == "size") {
        echo "checked";
    } ?> value="size"> <i class="fa fa-tachometer"></i>
                </label>
            </div>

            <div class="checkbox clear checkbox-big">
                <label>
                    <input type="checkbox" name="show_pagination_num" <?php
    if ($setUp->getConfig('show_pagination_num')) {
            echo "checked";
    } ?>><i class="fa fa-caret-left"></i>..2..<i class="fa fa-caret-right"></i>
    <?php 
    print $encodeExplorer->getString("show_pagination_num"); ?>
                </label>
            </div>

        </div>
                <hr>

            <div class="checkbox clear toggle checkbox-bigger">
                <label>
                    <input type="checkbox" name="show_pagination_folders" <?php
    if ($setUp->getConfig('show_pagination_folders')) {
            echo "checked";
    } ?>><i class="fa fa-caret-left"></i> <i class="fa fa-folder"></i> <i class="fa fa-caret-right"></i>
    <?php 
    print $encodeExplorer->getString("show_pagination_folders"); ?>
                </label>
            </div>

            <div class="toggled intero">
                <div class="intero">
                    <label class="radio-inline">
                      <input type="radio" name="folderdefnum" <?php
    if ($setUp->getConfig('folderdefnum') == 10) {
            echo "checked";
    } ?> value="10"> 10
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="folderdefnum" <?php
    if ($setUp->getConfig('folderdefnum') == 25) {
            echo "checked";
    } ?> value="25"> 25
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="folderdefnum" <?php
    if ($setUp->getConfig('folderdefnum') == 50) {
            echo "checked";
    } ?> value="50"> 50
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="folderdefnum" <?php
    if ($setUp->getConfig('folderdefnum') == 100) {
            echo "checked";
    } ?> value="100"> 100
                    </label>
                </div>

                <div class="checkbox clear checkbox-big">
                    <label>
                        <input type="checkbox" name="show_pagination_num_folder" <?php
    if ($setUp->getConfig('show_pagination_num_folder')) {
                echo "checked";
    } ?>><i class="fa fa-caret-left"></i>..2..<i class="fa fa-caret-right"></i>
        <?php 
        print $encodeExplorer->getString("show_pagination_num"); ?>
                    </label>
                </div>

            </div>
            <div class="form-group">
                <label class="radio-inline">
                  <input type="radio" name="folderdeforder" <?php
    if ($setUp->getConfig('folderdeforder') == "alpha") {
        echo "checked";
    } ?> value="alpha"> <i class="fa fa-sort-alpha-asc"></i>
                </label>
                <label class="radio-inline">
                  <input type="radio" name="folderdeforder" <?php
    if ($setUp->getConfig('folderdeforder') == "date") {
        echo "checked";
    } ?> value="date"> <i class="fa fa-calendar"></i>
                </label>
            </div>


            <hr>
            
            <div class="checkbox clear checkbox-bigger">
                <label>
                    <input type="checkbox" name="log_file" <?php
    if ($setUp->getConfig('log_file') == true) {
        echo "checked";
    } ?>><i class="fa fa-bar-chart-o"></i> 
    <?php print $encodeExplorer->getString("statistics"); ?>
                </label>
            </div>

        </div>
    </div>

    <div class="intero well">
        <h3>            
            <i class="fa fa-envelope"></i> 
            <?php print $encodeExplorer->getString("email"); ?>
        </h3>
        <hr class="intero clear">

        <div class="form-group">
            <label>
                <i class="fa fa-envelope-o"></i> <?php print $encodeExplorer->getString("email_from"); ?>
            </label>
            <input type="email" class="form-control input-lg" 
            name="email_from" value="<?php print $setUp->getConfig('email_from'); ?>"
            placeholder="noreply@example.com">
        </div>


        <div class="checkbox checkbox-big toggle clear">
            <label>
                <input type="checkbox" name="smtp_enable" id="smtp_enable" <?php
    if ($setUp->getConfig('smtp_enable') == true) {
        echo "checked";
    } ?>>SMTP mail</label></div>
            <div class="intero" class="toggled">


            <div class="form-group unmezzo pull-left">
                <label>
                    <?php print $encodeExplorer->getString("smtp_server"); ?>
                </label>
                <input type="text" class="form-control" 
                name="smtp_server" value="<?php print $setUp->getConfig('smtp_server'); ?>"
                placeholder="mail.example.com">
            </div>

            <div class="form-group unmezzo pull-right">
                <div class="row">
                <div class="col-sm-6">
                    <label>
                        <?php print $encodeExplorer->getString("port"); ?>
                    </label>
                    <input type="text" class="form-control" 
                    name="port" value="<?php print $setUp->getConfig('port'); ?>"
                    placeholder="25">
                </div>
                

                <div class="col-sm-6">
                    <label><?php print $encodeExplorer->getString("secure_connection"); ?></label>

                <select class="form-control" name="secure_conn">
                  <option <?php
    if ($setUp->getConfig('secure_conn') == "") {
        echo "selected";
    } ?> value="none">none</option>
                <option <?php
    if ($setUp->getConfig('secure_conn') == "ssl") {
        echo "selected";
    } ?> value="ssl">SSL</option>
                <option <?php
    if ($setUp->getConfig('secure_conn') == "tls") {
        echo "selected";
    } ?> value="tls">TLS</option>
                </select>
                </div>
                </div>
            </div>

            <div class="checkbox clear toggle">
                <label>
                    <input type="checkbox" name="smtp_auth" <?php
    if ($setUp->getConfig('smtp_auth') == true) {
        echo "checked";
    } ?>>
    <?php print $encodeExplorer->getString("smtp_auth"); ?>
                </label>
            </div>
            <div class="intero" class="toggled">
                <div class="form-group unmezzo pull-left">
                    <label>
                        <?php print $encodeExplorer->getString("username"); ?>
                    </label>
                    <input type="text" class="form-control" 
                    name="email_login" value="<?php print $setUp->getConfig('email_login'); ?>"
                    placeholder="login@example.com">
                </div>

                <div class="form-group unmezzo pull-right">
                    <label>
                        <?php print $encodeExplorer->getString("password"); ?>
                    </label>
                    <input type="password" class="form-control" 
                    name="email_pass" value=""
                    placeholder="<?php print $encodeExplorer->getString("password"); ?>">
                </div>
            </div>
        </div>

    </div><!-- intero -->

            <button type="submit" class="btn btn-info btn-lg btn-block clear">
                <i class="fa fa-refresh"></i> 
                <?php print $encodeExplorer->getString("save_settings"); ?>
            </button>

        </div><!-- adminblock -->
        <input type="hidden" name="max_zip_files" value="<?php echo $maxfiles; ?>">
        <input type="hidden" name="max_zip_filesize" value="<?php echo $maxfilesize; ?>">
    </form>

    <?php 
    /**
    * 
    * CUSTOM HEADER 
    * 
    */ 
    ?>
    <div class="intero well">
        <h3>
            <i class="fa fa-certificate"></i> 
            <?php print $encodeExplorer->getString("custom_header"); ?>
        </h3>
        <hr class="intero">


        <div class="clear unmezzo pull-left">
            <form role="form" method="post" 
            action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" 
            enctype="multipart/form-data">

                <div class="row">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <span class="btn btn-default btn-file">
                                        <?php print $encodeExplorer->getString("browse"); ?>
                                        <input type="file" name="file" value="select">
                                    </span>
                                </span>
                                <input class="form-control" type="text" readonly 
                                name="fileToUpload" id="fileToUpload" onchange="fileSelected();">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <button class="upload_sumbit btn btn-primary btn-block pull-left" type="submit">
                                <?php print $encodeExplorer->getString("upload"); ?>
                            </button>
                        </div>
                    </div>
                </div>

            </form>

            <label><?php print $encodeExplorer->getString("alignment"); ?></label>

            <div class="form-group select-logo-alignment">
                <label class="radio-inline">
                  <input form="settings-form" type="radio" name="align_logo" <?php
    if ($setUp->getConfig('align_logo') == "left") {
        echo "checked";
    } ?> value="left"> <i class="fa fa-align-left"></i>
                </label>
                <label class="radio-inline">
                  <input form="settings-form" type="radio" name="align_logo" <?php
    if ($setUp->getConfig('align_logo') == "center") {
        echo "checked";
    } ?> value="center"> <i class="fa fa-align-center"></i>
                </label>
                <label class="radio-inline">
                  <input form="settings-form" type="radio" name="align_logo" <?php
    if ($setUp->getConfig('align_logo') == "right") {
        echo "checked";
    } ?> value="right"> <i class="fa fa-align-right"></i>
                </label>
            </div>

        </div>

    <?php
    $logoAlignment = $setUp->getConfig("align_logo");
    switch ($logoAlignment) {
    case "left":
        $placealign = "text-left";
        break;
    case "center":
        $placealign = "text-center";
        break;
    case "right":
        $placealign = "text-right";
        break;
    default:
        $placealign = "text-left";
    } ?>

        <div class="unmezzo pull-right placeheader <?php echo $placealign; ?>">
            <img src="images/<?php print $setUp->getConfig('logo'); ?>">
        </div>
    </div>

    <?php 
    /**
    *
    * END SWITCH PANELS
    *
    */ ?>

    <?php
} ?>
    </section><!-- intero -->
    <footer class="footer">
        <span class="pull-right"><a href="../">
            <?php print $setUp->getConfig('appname'); ?> </a> 
            &copy; <?php echo date('Y'); ?>
        </span>
    </footer>
</div><!-- .wrapper -->
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/admin.js"></script>

    <script type="text/javascript">
    $(document).ready(function () {

        $('.cooldrop').click(function(){
            $('.cooldropgroup').css('z-index', '99');
            $(this).closest('.cooldropgroup').css('z-index', '100');
        });
        $('.assignfolder').click(function(){
            $('.cooldropgroup').css('z-index', '99');
            $(this).closest('.cooldropgroup').css('z-index', '100');
        });

        $('.assignfolder').multiselect({
            buttonWidth: '100%',
            selectAllText: ' <?php print $encodeExplorer->getString("select_all"); ?>',
            maxHeight: 300,
            enableFiltering: true,
            enableFilteringIfMoreThan: 10,
            filterPlaceholder: '',
            includeSelectAllOption: true,
            includeSelectAllIfMoreThan: 10,
            numberDisplayed: 1,
            nSelectedText: '<?php print $encodeExplorer->getString("selected_files"); ?>',
            buttonContainer: '<div class="btn-group btn-block" />',
            nonSelectedText : '<?php print $encodeExplorer->getString("available_folders"); ?>',
            templates: {
            filter: '<div class="input-group"><span class="input-group-addon input-sm"><i class="glyphicon glyphicon-search"></i></span><input class="form-control multiselect-search input-sm" type="text"></div>'
            }
        });
        $('.coolselect').multiselect({
            buttonWidth: '100%',
            buttonContainer: '<div class="btn-group btn-block" />'
        });
    });
    </script>
</body>
</html>