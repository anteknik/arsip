<?php

error_reporting(E_ALL ^ E_NOTICE);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'doc-admin/include/head.php';
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php print $setUp->getConfig("appname"); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <meta http-equiv="Content-Language" content="<?php print $encodeExplorer->lang; ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="file manager">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <link rel="stylesheet" href="doc-admin/css/bootstrap.min.css">
    <?php 
    if ($setUp->getConfig("txt_direction") == "RTL") { ?>
        <link rel="stylesheet" href="doc-admin/css/bootstrap-rtl.min.css">
    <?php 
    } ?>
    <link rel="stylesheet" href="doc-admin/css/font-awesome.min.css">
    <link rel="stylesheet" href="doc-admin/doc-style.css">

    <link rel="stylesheet" href="doc-admin/skins/<?php print $setUp->getConfig('skin') ?>">
    <script src="doc-admin/js/jquery-1.11.1.min.js"></script>
    <!--[if lt IE 9]>
    <script src="doc-admin/js/html5.js" type="text/javascript"></script>
    <script src="doc-admin/js/respond.min.js" type="text/javascript"></script>
    <![endif]-->
    <script type="text/javascript" src="doc-admin/js/bootstrap.min.js"></script>

</head>
    <body id="uparea">
        <div class="overdrag"></div>

            <?php 
            /**
            * ************************************************
            * ******************** HEADER ********************
            * ************************************************
            */ 
            $template->getPart('userpanel');
            $template->getPart('header');
            ?>
        <div class="container">   

            <?php
            /**
            * ************************************************
            * **************** Response messages *************
            * ************************************************
            */
            ?>
            <div id="error">
                <noscript>
                    <div class="response boh">
                        <span><i class="fa fa-exclamation-triangle"></i> 
                            Please activate JavaScript</span>
                    </div>
                </noscript>
                <?php
                if (isset($_ERROR) && strlen($_ERROR) > 0) {
                    print "<div class=\"response nope\">"
                    .$_ERROR."<i class=\"fa fa-times closealert\"></i></div>";
                }
                if (isset($_WARNING) && strlen($_WARNING) > 0) {
                    print "<div class=\"response boh\">"
                    .$_WARNING."<i class=\"fa fa-times closealert\"></i></div>";
                } 
                if (isset($_SUCCESS) && strlen($_SUCCESS) > 0) {
                    print "<div class=\"response yep\">"
                    .$_SUCCESS."<i class=\"fa fa-times closealert\"></i></div>";
                }
                ?>
            </div>

            <?php 
            if ($getfilelist) :
                /**
                * ************************************************
                * ********* SHOW FILE SHARING DOWNLOADER *********
                * ************************************************
                */
                $template->getPart('downloader');


            elseif ($getrp) :
                /**
                * ************************************************
                * **************** PASSWORD RESET ****************
                * ************************************************
                */
                $template->getPart('reset');

            else :

                /**
                * to do:
                * send upload email notifications
                * to other users
                */
                // $template->getPart('listusers');

                /**
                * ************************************************
                * **************** SHOW FILEMANAGER **************
                * ************************************************
                */
                $template->getPart('user-redirect');
                $template->getPart('uploadarea');
                $template->getPart('breadcrumbs');
                $template->getPart('list-folders');
                $template->getPart('list-files');
                $template->getPart('disk-space');
                $template->getPart('login');
                $template->getPart('modals');

            endif; ?>
        </div> <!-- .vfmwrapper -->
        <?php
                /**
                * ************************************************
                * ******************** FOOTER ********************
                * ************************************************
                */
                $template->getPart('footer');
            ?>

        <script type="text/javascript" src="doc-admin/js/home.js"></script>
    </body>
</html>