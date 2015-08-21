<?php

/**
* UPLOAD AREA
*/
if ($gateKeeper->isAccessAllowed() && $location->editAllowed() 
    && ($gateKeeper->isAllowed('upload_enable') 
    || $gateKeeper->isAllowed('newdir_enable'))
) { 
    print "<section class=\"vfmblock uploadarea\">";
    /**
    *
    * Upload files
    *
    */
    if ($gateKeeper->isAllowed('upload_enable')) { 

        if ($gateKeeper->isAllowed('newdir_enable')) { 
            $class = "span-6";
        } else {
            $class = "intero";
        }

        print "<form enctype=\"multipart/form-data\" method=\"post\" 
                id=\"upForm\" action=\"".$actual_link."\">";

        if ($setUp->getConfig('preloader') == 'APC') {
                // ******************  APC UPLOAD MODE ******************* //
                print "<input type=\"hidden\" name=\"APC_UPLOAD_PROGRESS\" 
                id=\"uid\" value=\"".$uid."\"/>";
        } elseif ($setUp->getConfig('preloader') == 'UploadProgress') {
                // ************* PHP UPLOADPROGRESS MODE ************* //
                print "<input type=\"hidden\" name=\"UPLOAD_IDENTIFIER\" 
                id=\"uid\"  value=\"".$uid."\">";
        } ?>
                <?php
                print "<input type=\"hidden\" name=\"location\" value=\"".$location->getDir(true, false, false, 0)."\">";       
                print "<div id=\"upload_container\" class=\"input-group pull-left ".$class."\">";
                print "<span class=\"input-group-addon ie_hidden\"><i class=\"fa fa-files-o fa-fw\"></i></span>";
                print "<span class=\"input-group-btn\" id=\"upload_file\">
                <span class=\"upfile btn btn-default btn-file\"><i class=\"fa fa-plus fa-fw\"></i>
                <input name=\"userfile[]\" type=\"file\" multiple class=\"upload_file\" /></span></span>";
                print "<input class=\"form-control\" type=\"text\" readonly name=\"fileToUpload\" 
                id=\"fileToUpload\" onchange=\"fileSelected();\" placeholder=\""
                .$encodeExplorer->getString("browse")."\">
                <span class=\"input-group-btn\">
                <button class=\"upload_sumbit btn btn-primary\" type=\"submit\" id=\"upformsubmit\">
                <i class=\"fa fa-upload fa-fw\"></i></button>
                <a href=\"javascript:void(0)\" class=\"btn btn-primary\" id=\"upchunk\"><i class=\"fa fa-upload fa-fw\"></i></a>
                </span></div></form>";

        if ($setUp->getConfig('preloader') == 'APC' 
            || $setUp->getConfig('preloader') == 'UploadProgress' 
        ) { 
            print "<script type=\"text/javascript\" src=\"doc-admin/doc-progress.js\"></script>";
        } else {
            print "<script type=\"text/javascript\" src=\"doc-admin/js/uploaders.js\"></script>";

            $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
            if (stripos($useragent, 'android') !== false) {
                $android = 'yes';
            } else {
                $android = 'no';
            }
            ?>
            
            <script type="text/javascript">

            var ua = navigator.userAgent.toLowerCase();
            var android = '<?php echo $android; ?>';

            var r = new Resumable({
               target:"doc-admin/chunk.php?loc=<?php echo urlencode($location->getFullPath()); ?>&logloc=<?php echo urlencode($location->getDir(true, false, false, 0)); ?>",

              simultaneousUploads:2,
              prioritizeFirstAndLastChunk: true,
              //maxFiles: 1, // uncomment this to disable multiple uploading
              minFileSizeErrorCallback:function(file, errorCount) {
                    setTimeout(function() {
                        alert(file.fileName||file.name +' is not valid.');
                    }, 1000);
              }
                //testChunks:false
                //chunkSize: 1*1024*1024,
            });

            var percentVal = 0;
            var roundval = 0;
            var locazio = location.pathname;
            var queri = location.search;
            queri = queri.replace('&response', '');
            queri = queri.replace('?response', '');
            queri = queri.replace('?del', '?nodel');
            queri = queri.replace('&del', '&nodel');
            if (queri == "") { queri = "?" } else { queri = queri+"&"; }

            if (r.support && android == 'no') {

                var anyUserChecked;

                r.assignBrowse(document.getElementById('upchunk'));
                r.assignDrop(document.getElementById('uparea'));

                $("#fileToUpload").attr("placeholder","<?php echo $encodeExplorer->getString('drag_files'); ?>");

                r.on('uploadStart', function(){
                    $("#resumer").remove();
                    $("#upchunk").before("<a onclick=\"r.pause(); return(false);\" class=\"btn btn-primary\" id=\"resumer\"><i class=\"fa fa-pause\"></i></a>");
                    window.onbeforeunload = function() {
                        return 'Are you sure you want to leave?';
                    }
                });
                r.on('pause', function(){
                    $("#resumer").remove();
                    $("#upchunk").before("<a onclick=\"r.upload(); return(false);\" class=\"btn btn-primary\" id=\"resumer\"><i class=\"fa fa-play\"></i></a>")
                });

                r.on('progress', function(){
                    percentVal = r.progress()*100;
                    roundval = percentVal.toFixed(1);

                    $('.upbar p').html(roundval+'%');
                    $(".upbar").width(percentVal+'%');
                });
            
            <?php 
            // upload progress for individual files

            if ($setUp->getConfig('single_progress')) { ?>
                r.on('fileProgress', function(file){
                    percentVal = file.progress(true)*100;
                    $('.upbarfile p').html(file.fileName);
                    $(".upbarfile").width(percentVal+'%');
                });
            <?php 
            } ?>

                r.on('error', function(message, file){
                    console.log(message, file);
                });

                r.on('fileAdded', function(file, event){
                    anyUserChecked = $('#userslist :checkbox:checked').length > 0;
                    r.upload();
                });

                // add file path 
                // to notification message
                r.on('fileSuccess', function(file, event){
                    if (anyUserChecked == true) {
                        var newinput = '<input type="hidden" name="filename[]" value="'+file.fileName+'">';
                        $("#userslist").append(newinput);
                    }
                });
                
                r.on('complete', function(){
                    window.onbeforeunload = null;

                    // Send upload notification 
                    // to selected users
                    if (anyUserChecked == true) {
                        //  alert(anyUserChecked);
                        var now = $.now();

                        $.ajax({
                            cache: false,
                            type: "POST",
                            url: "doc-admin/template/sendupnotif.php?t="+now,
                            data: $("#userslist").serialize()

                        })
                        .done(function(msg) {
                        //  alert(msg);
                        });
                    }

                    setTimeout(function() {
                        location.href = locazio+queri+"response"
                    }, 800);
                });

                // Drag & Drop
                $('#uparea').on(
                    'dragstart dragenter dragover',
                    function(e) {
                        $(".overdrag").css('display','block');
                })
                $('.overdrag').on(
                    'drop dragleave dragend',
                    function(e) {
                        $(".overdrag").css('display','none');
                })

            } else {

                // Resumable.js is not supported, fall back on the form.js method
                $("#upchunk").remove();
                $("#upformsubmit").show();

                var ie = ((document.all) ? true : false);

                if (ie || ($.client.os === 'Windows' && $.client.browser === 'Safari' ) || android === 'yes') {
                    // form.js is not supported ( < IE 10 or Safari on Wondows), fall back on the old classic form method
                    $('#upload_file').css('display','table-cell');
                    $('.ie_hidden').remove();
                    $(document).on('click', '#upformsubmit', function(e) {
                        $('#fileToUpload').val('Loading....');
                    });

                } else {

                    $(document).on('click', '#fileToUpload', function() {
                        $('.upload_file').trigger('click');
                    });
                    $(document).on('click', '#upformsubmit', function(e) {

                        e.preventDefault();
                        $('.upload_file').trigger('click');
                    });
                }

                $(document).ready(function(){

                    var progress = $('#progress-up');
                    var probar = $('.upbar');
                    var prop = $('.upbar p');

                    $('#upForm').ajaxForm({
                        beforeSend: function() {            
                            progress.css('opacity', 1);
                        },
                        uploadProgress: function(event, position, total, percentComplete) {
                            var percentVal = percentComplete ;
                            var roundval = percentComplete.toFixed(1);
                            
                            probar.width(percentVal);
                            prop.html(roundval);
                        },
                        success: function() {
                            var percentVal = '100';
                            probar.width(percentVal+'%');
                            prop.html(percentVal+'%');
                        },
                        complete: function(xhr) {
                            setTimeout(function () {
                                location.href = locazio+queri+"response"
                            }, 800);
                        }
                    });
                });
            
                $('.btn-file :file').on('fileselect', function (event, numFiles, label) {
                    var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;
                    if (input.length) {
                        input.val(log);

                        if (!ie) {
                            $("#upForm").submit();
                        }
                    }
                });
            }
            </script>
        <?php
        }       
    } 

    /**
    *
    * Create directory
    *
    */
    if ($gateKeeper->isAllowed('newdir_enable')) { 
        if ($gateKeeper->isAllowed('upload_enable')) { 
            $class = "span-6";
        } else {
            $class = "intero";
        }
        print "<form enctype=\"multipart/form-data\" method=\"post\">
        <div id=\"newdir_container\" class=\"input-group pull-right ".$class."\">";
        print "<span class=\"input-group-addon\"><i class=\"fa fa-folder-open-o fa-fw\"></i></span>";
        print "<input name=\"userdir\" type=\"text\" class=\"upload_dirname form-control\" 
        placeholder=\"".$encodeExplorer->getString("make_directory")."\" />
        <span class=\"input-group-btn\">
        <button class=\"btn btn-primary upfolder\" type=\"submit\"><i class=\"fa fa-plus fa-fw\"></i>"
        ."</button></span></div></form>";
    }

    if ($gateKeeper->isAllowed('upload_enable')
        && strlen($setUp->getConfig('preloader')) > 0
    ) { 
            // upload progress bar
            print "<div class=\"intero";
        if ($setUp->getConfig('show_percentage')) {
            print " fullp";
        }
            print "\">";
            print "<div class=\"progress progress-striped active\" id=\"progress-up\">
            <div class=\"upbar progress-bar ".$setUp->getConfig('progress_color')."\" 
            role=\"progressbar\" aria-valuenow=\"0\" 
            aria-valuemin=\"0\" aria-valuemax=\"100\">
            <p class=\"pull-left propercent\"></p>
            </div></div>";
        
        // second progress bar for individual files
        if ($setUp->getConfig('single_progress')) {
            print "<div class=\"progress progress-single\" id=\"progress-up-single\">
            <div class=\"upbarfile progress-bar ".$setUp->getConfig('progress_color')."\" 
            role=\"progressbar\" aria-valuenow=\"0\" 
            aria-valuemin=\"0\" aria-valuemax=\"100\">
            <p class=\"pull-left propercent\"></p>
            </div></div></div>";
        }
    }
    print "</section>";
}