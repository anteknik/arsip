<?php

/**
* Downloader
*/
$expired = false;
global $getfilelist;

if ($getfilelist && file_exists('doc-admin/shorten/'.$getfilelist.'.json')) {

    $datarray = json_decode(file_get_contents('doc-admin/shorten/'.$getfilelist.'.json'), true);
    $passa = true;
    
    
    if ($setUp->getConfig('enable_prettylinks') == true) {
        $zip_url = "download/dl/".$getfilelist;
    } else {
        $zip_url = "doc-admin/doc-downloader.php?dl=".$getfilelist;
    }
    
    $pass = (isset($datarray['pass']) ? $datarray['pass'] : false);

    if ($pass) { 
        $passa = false;
        $postpass = filter_input(INPUT_POST, "dwnldpwd", FILTER_SANITIZE_STRING);
        if (md5($postpass) === $pass) {
            $passa = true;
            $zip_url .= "&pw=".urlencode($postpass);
        }
    }
    $hash = $datarray['hash'];
    $time = $datarray['time'];
        
    if ($passa === true) {    

        if ($downloader->checkTime($time) == true) {
            print "<div class=\"intero centertext bigzip\">
            <a class=\"btn btn-primary btn-lg centertext\" href=\"".$zip_url."\"><i class=\"fa fa-cloud-download fa-5x\"></i><br>"
            .".zip</a></div>";
            
            print "<div class=\"intero\"><ul class=\"multilink\">";

            $pieces = explode(",", $datarray['attachments']);

            $totalsize = 0;
            $salt = $setUp->getConfig('salt');

            foreach ($pieces as $pezzo) {
                $myfile = urldecode(base64_decode($pezzo));
                $filepathinfo = Utils::mbPathinfo($myfile);
                $filename = $filepathinfo['basename'];
                $extension = strtolower($filepathinfo['extension']);
                $supah = md5($hash.$salt.$pezzo);
                $filesize = File::getFileSize($myfile);
                $totalsize += $filesize;
                if (array_key_exists($extension, $_IMAGES)) {
                    $thisicon = $_IMAGES[$extension];
                } else {
                    $thisicon = "fa-file-o";
                }
                
                if ($setUp->getConfig('enable_prettylinks') == true) {
                    $downlink = "download/".$pezzo."/h/".$hash."/sh/".$supah;
                } else {
                    $downlink = "doc-admin/doc-downloader.php?q=".$pezzo."&h=".$hash."&sh=".$supah;
                }
                print "<li><a class=\"btn btn-primary\" href=\"".$downlink."\">"
                ."<span class=\"pull-left small\"><i class=\"fa ".$thisicon." fa-2x\"></i> ".$filename."</span>"
                ."<span class=\"pull-right small\">".$setUp->formatsize($filesize)." <i class=\"fa fa-download fa-2x\"></i></span></a></li>";
            }
            print "</ul></div>";
            
            // check number of files and total size 
            // if under the limits, show the zip button
            $max_zip_filesize = $setUp->getConfig('max_zip_filesize');
            $max_zip_files = $setUp->getConfig('max_zip_files');
            $totalsize = $totalsize/1024/1024;
            $totalfiles = count($pieces);

            if ($totalsize <= $max_zip_filesize && $totalfiles <= $max_zip_files) { ?>
                <script type="text/javascript">
                $(document).ready(function(){
                    $('.bigzip').fadeIn();
                });
                </script>
            <?php
            }
        } else {
            unlink('doc-admin/shorten/'.$getfilelist.'.json');
            $expired = true;
        }

    } // END if $passa == true


    if (strlen($pass) > 0 && $passa != true) { 
        if ($postpass) { ?>
            <script type="text/javascript">
                var $error = $('<div class="response nope"><i class="fa fa-times closealert"></i>'
                    +' <?php echo $encodeExplorer->getString("wrong_pass"); ?></div>');
                $('#error').append($error);
            </script>
        <?php
        }
        ?>
        <div class="row" id="dwnldpwd">
            <div class="col-sm-4">
            </div>
            <div class="col-sm-4">
                <form method="post">
                  <div class="form-group">
                    <label for="exampleInputPassword1"><?php echo $encodeExplorer->getString("password"); ?></label>
                    <input type="password" name="dwnldpwd" class="form-control" placeholder="<?php echo $encodeExplorer->getString("password"); ?>">
                  </div>
                    <div class="form-group">
                      <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-check"></i>
                      </button>
                    </div>
                </form>
            </div>
            <div class="col-sm-4">
            </div>
        </div>
    <?php
    }

} else {
    $expired = true;
}

if ($expired === true) {
    print "<div class=\"intero centertext\">";
    print "<a class=\"btn btn-default btn-lg centertext whitewrap\" href=\"./\">"
    .$encodeExplorer->getString("link_expired")."</a>";
    print "</div>";
}
?>