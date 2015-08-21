<?php

/**
*
* Group Actions
*
*/
if ($gateKeeper->isAccessAllowed()) {

    $time = time();
    $hash = md5($_CONFIG['salt'].$time);
    $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $url .= $_SERVER['HTTP_HOST'];
    $url .= htmlspecialchars($_SERVER['PHP_SELF']);
    $pulito = dirname($url);
    $insert4 = $encodeExplorer->getString('insert_4_chars');
    ?>
    <script type="text/javascript">

    var dvar;

    $(document).on('change', '#use_pass', function() {
        $('.alert').alert('close');
        passwidget();
    });

    $(document).on('click', '#createlink', function () {

        $('.alert').alert('close');
        var alertmess = '<div class="alert alert-warning alert-dismissible" role="alert">'
                        + '<?php echo $insert4; ?></div>';
        var shortlink, passw
        // check if wants a password
        if ($('#use_pass').prop('checked')) {
            

            if (!$('.setpass').val()) {
                passw = randomstring();
            } else {
                if ($('.setpass').val().length < 4) {
                    $('.setpass').focus();
                    $('.seclink').after(alertmess);
                    return;
                } else {
                    passw = $('.setpass').val();
                }
            }  
        }

        $.ajax({
            cache: false,
            type: "POST",
            url: "doc-admin/shorten.php",
            data: {
                atts: divar.join(','),
                time: '<?php echo $time;?>',
                hash: '<?php echo $hash; ?>',
                pass: passw
            }
        })
        .done(function( msg ) {
            shortlink = '<?php echo $pulito; ?>/?dl=' + msg;
            $(".sharelink").val(shortlink);
            $(".sharebutt").attr('href', shortlink);
            $(".passlink").val(passw);
            $('.passlink').prop('readonly', true);
            
            $('.createlink-wrap').fadeOut('fast', function(){
                $('.shalink').fadeIn();
                $(".openmail").fadeIn();
            });
            // console.log(msg);
        })
        .fail(function() {
            console.log('ERROR generating shortlink');
        });
    });

    $(document).on('click', '.manda', function () {
        if($(".selecta:checked").size() > 0) {
            divar = [];

    <?php 
    if ($setUp->getConfig("show_pagination_num") == true 
        || $setUp->getConfig("show_pagination") == true 
        || $setUp->getConfig("show_search") == true
    ) { ?>

        var sData = $('.selecta', oTable.fnGetNodes()).serializeArray();
        var numfiles = sData.length;

        jQuery.each( sData, function( i, field ) {
            divar.push(field.value);
        });

    <?php

    } else { ?>
        
        var numfiles = $(".selecta:checked").size();
        $(".selecta:checked").each(function(){
            divar.push($(this).val());
        });

        <?php
    }           
    //
    // generate short url for file sharing
    //
    ?>
            // reset form
            $(".addest").val('');
            $(".bcc-address").remove();

            $('.seclink, .shalink, .mailresponse, #sendfiles, .openmail').hide();
            $('.sharelink, .passlink').val('');
            $(".sharebutt").attr('href', '#');
            $('.createlink-wrap').fadeIn();

            passwidget();

            // populate send inputs
            $(".attach").val(divar.join(','));
            $(".numfiles").html(numfiles);

            // open modal
            $("#sendfilesmodal").modal();

            $("#sendfiles").unbind('submit').submit(function(event) {
                event.preventDefault();
                $(".mailpreload").fadeIn();
                var now = $.now();

                $.ajax({
                    cache: false,
                    type: "POST",
                    url: "doc-admin/sendfiles.php?t="+now,
                    data: $("#sendfiles").serialize()
                })
                .done(function( msg ) {
                    $('.mailresponse').html('<p>' + msg + '</p>').addClass('bg-success').fadeIn();
                    $(".addest").val('');
                    $(".bcc-address").remove();
                    $(".mailpreload").fadeOut();
                })
                .fail(function() {
                    $(".mailpreload").fadeOut();
                    $('.mailresponse').html('<p>Error</p>').addClass('bg-danger');
                });
            });
        } else {
            alert("<?php print $encodeExplorer->getString("select_files"); ?>");
        }
    }); // end .manda click

    $(document).on('click', '.multid', function(e) {
        e.preventDefault();
        if($(".selecta:checked").size() > 0) {
            divar = [];

                    <?php 
    if ($setUp->getConfig("show_pagination_num") == true 
        || $setUp->getConfig("show_pagination") == true 
        || $setUp->getConfig("show_search") == true
    ) { ?>
                    var sData = $('.selecta', oTable.fnGetNodes()).serializeArray();
                    var numfiles = sData.length;

                    jQuery.each( sData, function( i, field ) {
                        divar.push(field.value);
                    });
    <?php
    } else {
        ?>
                    var numfiles = $(".selecta:checked").size();
                    $(".selecta:checked").each(function(){
                        divar.push($(this).val());
                    });
    <?php
    } ?>
                    if (numfiles >= <?php echo $setUp->getConfig('max_zip_files'); ?>) {
                        alert("<?php print $encodeExplorer->getString('too_many_files').$setUp->getConfig('max_zip_files'); ?>");
                    } else {
                        <?php
                        //
                        // generate short url for multiple downloads
                        //
                        ?>
                        var shortlink

                        $.ajax({
                            cache: false,
                            type: "POST",
                            url: "doc-admin/shorten.php",
                            data: {
                                atts: divar.join(','),
                                time: '<?php echo $time;?>',
                                hash: '<?php echo $hash; ?>'
                            }
                        })
                        .done(function( msg ) {
    <?php
    if ($setUp->getConfig('enable_prettylinks') == true) { ?>
                            shortlink = 'download/dl/' + msg;
    <?php
    } else {
        ?>
                            shortlink = 'doc-admin/doc-downloader.php?dl=' + msg;
    <?php
    } ?>
                            $(".sendlink").attr("href", shortlink);

                            $("#downloadmulti .numfiles").html(numfiles);
                            
                            // open modal
                            $('#downloadmulti').modal();
                            // console.log(msg);
                        })
                        .fail(function() {
                            console.log('ERROR generating shortlink');
                        });
                    }
                } else {
                    alert("<?php print $encodeExplorer->getString('select_files'); ?>");
                }
            }); // end .multid click
        </script>

        <div class="modal fade downloadmulti" id="downloadmulti" tabindex="-1">

            <div class="modal-dialog">
                <div class="modal-content">
               
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                        </button>
                        <p class="modal-title">
                            <?php print " " .$encodeExplorer->getString('selected_files'); ?>: 
                            <span class="numfiles badge badge-danger"></span>
                        </p>
                    </div>

                    <div class="text-center modal-body">
                        <a class="btn btn-primary btn-lg centertext bigd sendlink" href="#">
                            <i class="fa fa-cloud-download fa-5x"></i>
                        </a>
                    </div>
                </div>
             </div>
        </div>

    <?php 
    if ($gateKeeper->isAllowed('delete_enable')) { 
        $doit = ($time * 12);
        ?>
        <script type="text/javascript">
            <?php
            //
            // Delete single files
            //
            ?>
            $(document).on('click', 'td.del a', function() {
                var answer = confirm("<?php print $encodeExplorer->getString('delete_this_confirm'); ?> \"" + $(this).attr("data-name") + "\"");
                return answer;
            });
            <?php
            //
            // Delete multiple files
            //
            ?>
            $(document).on('click', '.removelink', function(e) {
                e.preventDefault();
                var answer = confirm("<?php print $encodeExplorer->getString('delete_confirm'); ?>");
                
                var deldata = $('#delform').serializeArray();

                if (answer == true) {
                    $.ajax({
                        type: "POST",
                        url: "doc-admin/doc-del.php",
                        data: deldata
                    })
                    .done(function( msg ) {
                        if (msg == "ok") {
                            window.location = window.location.href;
                        } else {
                            $(".delresp").html(msg);
                        }
                    })
                     .fail(function() {
                        alert( 'error' );
                    });
                 } else {
                    return answer;
                 }
            });


            <?php
            //
            // Setup multi delete button
            //
            ?>
            $(document).on('click', '.multic', function(e) {
                e.preventDefault();
                if($(".selecta:checked").size() > 0) {

                    <?php 
        if ($setUp->getConfig("show_pagination_num") == true 
            || $setUp->getConfig("show_pagination") == true 
            || $setUp->getConfig("show_search") == true
        ) { ?>
                    var sData = $('.selecta', oTable.fnGetNodes()).serializeArray();
                    var numfiles = sData.length;

                    jQuery.each(sData, function(i, field) {
                        $("#delform").append("<input type=\"hidden\" name=\"setdel[]\" value=\""+field.value+"\">");
                    });
        <?php
        } else {
            ?>
                    var numfiles = $(".selecta:checked").size();

                    $(".selecta:checked").each(function(){
                        $("#delform").append("<input type=\"hidden\" name=\"setdel[]\" value=\""+$(this).val()+"\">");
                    });
        <?php
        } ?>
                    $("#delform").append("<input type=\"hidden\" name=\"t\" value=\"<?php echo $time;?>\">");
                    $("#delform").append("<input type=\"hidden\" name=\"h\" value=\"<?php echo $hash;?>\">");
                    $("#delform").append("<input type=\"hidden\" name=\"doit\" value=\"<?php echo $doit;?>\">");

                    $("#deletemulti .numfiles").html(numfiles);
                    $('#deletemulti').modal();
                } else {
                    alert("<?php print $encodeExplorer->getString('select_files'); ?>");
                }
            }); 
        </script>

        <div class="modal fade deletemulti" id="deletemulti" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                        </button>
                        <p class="modal-title">
                            <?php print " " .$encodeExplorer->getString("selected_files"); ?>: 
                            <span class="numfiles badge badge-danger"></span>
                        </p>
                    </div>
                    <div class="text-center modal-body">
                        <form id="delform">
                            <a class="btn btn-primary btn-lg centertext bigd removelink" href="#">
                            <i class="fa fa-trash-o fa-5x"></i></a>
                            <p class="delresp"></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php  
    } 

    /**
    *
    * Send files window
    *
    */
    if ($setUp->getConfig('sendfiles_enable')) { ?>
            <div class="modal fade sendfiles" id="sendfilesmodal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">

                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">
                                <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                            </button>
                            <h5 class="modal-title">
                                <?php print " " .$encodeExplorer->getString("selected_files"); ?>: 
                                <span class="numfiles badge badge-danger"></span>
                            </h5>
                        </div>

                        <div class="modal-body">
                            <div class="form-group createlink-wrap">
                                <button id="createlink" class="btn btn-primary btn-block"><i class="fa fa-check"></i> <?php print $encodeExplorer->getString("generate_link"); ?></button>
                            </div>
        <?php
        if ($setUp->getConfig('secure_sharing')) { ?>
                            <div class="checkbox">
                                <label>
                                    <input id="use_pass" name="use_pass" type="checkbox"><i class="fa fa-key"></i> <?php print $encodeExplorer->getString("password_protection"); ?>
                                </label>
                            </div>
        <?php
        } ?>
                            <div class="form-group shalink">
                                <div class="input-group">
                                    <span class="input-group-btn">
                                        <a class="btn btn-primary sharebutt" href="#" target="_blank">
                                        <i class="fa fa-link fa-fw"></i>
                                        </a>
                                    </span>
                                    <input class="sharelink form-control" type="text" onclick="this.select()" readonly>
                                </div>
                            </div>
        <?php
        if ($setUp->getConfig('secure_sharing')) { ?>
                            <div class="form-group seclink">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-lock fa-fw"></i></span>
                                    <input class="form-control passlink setpass" type="text" onclick="this.select()" placeholder="<?php print $encodeExplorer->getString("random_password"); ?>">
                                </div>
                            </div>
        <?php
        } 

        $mailsystem = $setUp->getConfig('email_from');
        if (strlen($mailsystem) > 0) { ?>

                        <div class="openmail">
                            <span class="fa-stack fa-lg">
                              <i class="fa fa-circle-thin fa-stack-2x"></i>
                              <i class="fa fa-envelope fa-stack-1x"></i>
                            </span>
                        </div>

                            <form role="form" id="sendfiles">
                                <div class="mailresponse"></div>
                                
                                <input name="thislang" type="hidden" 
                                value="<?php print $encodeExplorer->lang; ?>">

                                    <label for="mitt">
                                       <?php print $encodeExplorer->getString("from"); ?>:
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
                                        <input name="mitt" type="email" 
                                        class="form-control" id="mitt" 
                                        placeholder="<?php print $encodeExplorer->getString("your_email"); ?>" 
                                        required >
                                    </div>

                                <div class="wrap-dest">
                                    <div class="form-group">
                                        <label for="dest">
                                            <?php print $encodeExplorer->getString("send_to"); ?>:
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-envelope fa-fw"></i></span>
                                            <input name="dest" type="email" 
                                            class="form-control addest" id="dest" placeholder="" required >
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group clear">
                                    <div class="btn btn-primary btn-xs shownext">
                                        <i class="fa fa-plus"></i> <i class="fa fa-user"></i>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <textarea class="form-control" name="message" id="mess" rows="3" 
                                    placeholder="<?php print $encodeExplorer->getString("message"); ?>"></textarea>
                                </div>

                                <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-envelope"></i>
                                </button>
                                </div>

                                <input name="passlink" class="form-control passlink" type="hidden">
                                <input name="attach" class="attach" type="hidden">
                                <input name="sharelink" class="sharelink" type="hidden">
                            </form>

                            <div class="mailpreload"></div>
        <?php
        } ?>
                        </div> <!-- modal-body -->
                    </div>
                </div>
            </div>
        <?php
    }
} 
        
/**
*
* Rename files and folders
*
*/
if ($gateKeeper->isAllowed('rename_enable')) { ?>

    <div class="modal fade changename" id="modalchangename" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> <?php print $encodeExplorer->getString("rename"); ?></h4>
                </div>

                <div class="modal-body">
                    <form role="form" method="post">
                        <input readonly name="thisdir" type="hidden" 
                        class="form-control" id="dir">
                        <input readonly name="thisext" type="hidden"
                        class="form-control" id="ext">
                        <input readonly name="oldname" type="hidden" 
                        class="form-control" id="oldname">

                        <div class="input-group">
                            <label for="newname" class="sr-only">
                                <?php print $encodeExplorer->getString("rename"); ?>
                            </label>
                            <input name="newname" type="text" 
                            class="form-control" id="newname">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary">
                                    <?php print $encodeExplorer->getString("rename"); ?>
                                </button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
*
* Show Thumbnails
*
*/
if ($setUp->getConfig("thumbnails") == true ) { ?>
    <div class="modal fade zoomview" id="zoomview" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                    </button>
                    <div class="modal-title"><span class="downlink"><a class="doclink" href="#">
                        <i class="fa fa-download"></i></a></span> <span class="thumbtitle"></span>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="doc-zoom"></div>
                    <!--            
                     <div style="position:absolute; right:10px; bottom:10px;">Custom Watermark</div>
                    -->                
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
    /**
    *
    * Load image preview 
    *
    */
    function loadImg(thislink, thislinkencoded, thisname, thisID){

        $(".doc-zoom").html("<i class=\"fa fa-refresh fa-spin\"></i><img class=\"preimg\" src=\"doc-thumb.php?thumb="+ thislink +"\" \/>");

        // remove extension from filename    
        // fileExtension = '.'+thisname.replace(/^.*\./, '');
        // thisname = thisname.replace(fileExtension, '');
        $("#zoomview .thumbtitle").html(thisname);
        $("#zoomview").data('id', thisID);

        var firstImg = $('.preimg');
        firstImg.css('display','none');

        $("#zoomview").modal();

        firstImg.one('load', function() {
            $(".doc-zoom .fa-refresh").fadeOut();
            $(this).fadeIn();
            checkNextPrev(thisID);
            <?php 
    if ($setUp->getConfig('enable_prettylinks') == true) { ?>
                $(".doclink").attr("href", "download/"+thislinkencoded);
            <?php 
    } else { ?>
                $(".doclink").attr("href", "doc-admin/doc-downloader.php?q="+thislinkencoded);
            <?php 
    } ?>
            }).each(function() {
                if(this.complete) $(this).load();
        });   
    }
    </script>
    <?php
} ?>