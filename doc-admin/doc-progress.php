<?php

require 'config.php';
session_name($_CONFIG["session_name"]);
session_start();
if (isset($_GET['uid'])) {
    if ($_CONFIG['preloader'] == 'APC') {
        // APC //
        $status = apc_fetch('upload_'.$_GET['uid']);
        echo $status['current']/$status['total']*100;
    } else {
        // UPLOAD PROGRESS //
        $status = uploadprogress_get_info($_GET['uid']);
        if ($status) {
            echo $status['bytes_uploaded']/$status['bytes_total']*100;
        } else {
            echo 100;
        }
    }
    exit;
} ?>