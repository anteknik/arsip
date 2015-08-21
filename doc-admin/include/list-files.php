<?php

/**
* List Files
*/
if ($gateKeeper->isAccessAllowed() 
    && $location->editAllowed()
) {
    ?>    
    <section class="docblock tableblock">
        <div class="action-group">
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle groupact" 
                data-toggle="dropdown">
                    <i class="fa fa-cog"></i> 
                    <?php print $encodeExplorer->getString("group_actions"); ?> 
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a class="multid" href="#">
                        <i class="fa fa-cloud-download"></i> 
                        <?php print $encodeExplorer->getString("download"); ?></a></li>
            <?php 
    if ($gateKeeper->isAllowed('delete_enable')) { 
                print "<li><a class=\"multic\" href=\"#\"><i class=\"fa fa-trash-o\"></i> "
                .$encodeExplorer->getString("delete")."</a></li>";
    } ?>
                </ul>
            </div> <!-- .btn-group -->

        <?php
    if ($setUp->getConfig('sendfiles_enable')) { ?>
                <button class="btn btn-default manda">
                    <i class="fa fa-paper-plane"></i> 
                    <?php print $encodeExplorer->getString("share"); ?>
                </button>
        <?php
    }  ?>
        </div> <!-- .action-group -->

        <form id="tableform">
            <table class="table table-striped" width="100%" id="sort">
                <thead>
                    <tr class="rowa one">
                        <td class="text-center">
                            <a href="#" id="selectall"><i class="fa fa-check fa-lg"></i></a>
                        </td>
                        <td class="icon"></td>
                        <td class="mini">
                            <span class="sorta nowrap">
                                <?php print $encodeExplorer->getString("file_name"); ?>
                            </span>
                        </td>
                        <td class="hidden"></td>
                        <td class="taglia reduce mini">
                            <span class="hidden-xs centertext sorta nowrap">
                                <?php print $encodeExplorer->getString("size"); ?>
                            </span>
                        </td>
                        <td class="hidden"></td>
                        <td class="reduce mini">
                            <span class="hidden-xs centertext sorta nowrap">
                                <?php print $encodeExplorer->getString("last_changed"); ?>
                            </span>
                        </td>

        <?php
    if ($gateKeeper->isAllowed('rename_enable') && $location->editAllowed()) {
            print "<td class=\"mini centertext\">";
            print "<i class=\"fa fa-pencil\"></i></td>";
    }           

    if ($gateKeeper->isAllowed('delete_enable') && $location->editAllowed()) { 
            print "<td class=\"mini centertext\"><i class=\"fa fa-trash-o\"></i></td>";
    }           
        print "</tr></thead><tbody>";
        
        // Display the files
    if ($encodeExplorer->files) {

            $alt = $setUp->getConfig('salt');
            $altone = $setUp->getConfig('session_name');
            
        foreach ($encodeExplorer->files as $key => $file) {
            $thislink = $encodeExplorer->location->getDir(
                false, true, false, 0
            ).$file->getNameEncoded();

            $thisdir = urldecode(
                $encodeExplorer->location->getDir(false, true, false, 0)
            );
            $thisfile = $file->getName();
            $thisname = $file->getNameHtml();

            $dash = md5($alt.base64_encode($thislink).$altone.$alt);

            $ext = pathinfo($thisfile, PATHINFO_EXTENSION);
            $withoutExt = preg_replace("/\\.[^.\\s]{2,4}$/", "", $thisfile);
            $del = $location->getDir(false, true, false, 0).$file->getName();

            $thisdel = $encodeExplorer->makeLink(
                false, $del, $location->getDir(
                    false, true, false, 0
                )
            );

            if ($setUp->getConfig('enable_prettylinks') == true) {
                $downlink = "<a href=\"download/".base64_encode($thislink)."/h/".$dash."\"";
                $imgdata = "data-name=\"".$thisname."\" data-link=\"".$thislink
                ."\" data-linkencoded=\"".base64_encode($thislink)."/h/".$dash."\"";
            } else {
                $downlink = "<a href=\"doc-admin/doc-downloader.php?q=".base64_encode($thislink)."&h=".$dash."\"";
                $imgdata = "data-name=\"".$thisname."\" data-link=\"".$thislink
                ."\" data-linkencoded=\"".base64_encode($thislink)."&h=".$dash."\"";
            }

            if (array_key_exists($file->getType(), $_IMAGES)) {
                $thisicon = $_IMAGES[$file->getType()];
            } else {
                $thisicon = "fa-file-o";
            }
            print "<tr class=\"rowa ";
            if ($file->isValidForThumb()) {
                print "gallindex\" id=\"gall-".$key;
            }
            print "\"><td class=\"checkb centertext\">
            <input type=\"checkbox\" name=\"selecta\" class=\"selecta\" value=\""
            .base64_encode($thislink)."\"></td>";

            if (($ext == "mp3" || $ext == "wav")
                && $setUp->getConfig('playmusic') == true
            ) {
                print "<td class=\"icon centertext playme\">";


                if ($setUp->getConfig('enable_prettylinks') == true) {
                    print "<a type=\"audio/mp3\" class=\"sm2_button\" href=\"download/"
                    .base64_encode($thislink)."/h/".$dash."&audio=play\">";
                } else {
                    print "<a type=\"audio/mp3\" class=\"sm2_button\" href=\"doc-admin/doc-downloader.php?q="
                    .base64_encode($thislink)."&h=".$dash."&audio=play\">";
                }

                if ($setUp->getConfig('inline_thumbs') == true) {
                    print "<span class=\"icon-placeholder\">";
                }
                print "<i class=\"trackload fa fa-refresh fa-spin\"></i>"
                ."<i class=\"trackpause fa fa-play-circle-o\"></i>"
                ."<i class=\"trackplay fa fa-circle-o-notch fa-spin\"></i>"
                ."<i class=\"trackstop fa fa-play-circle\"></i>";
                if ($setUp->getConfig('inline_thumbs') == true) {
                    print "</span>";
                }
                print "</a>";

            } else {

                print "<td class=\"icon centertext\">";
                print $downlink;

                if ($file->isValidForThumb()) {
                    print $imgdata;
                }

                if ($ext == "pdf" || $ext == "PDF") {
                    print " target=\"_blank\"";
                }
                    print " class=\"item file";

                if ($file->isValidForThumb() 
                    && $setUp->getConfig('thumbnails') == true
                ) {
                    print " thumb doc-gall";
                }
                    print "\">";

                // INLINE THUMBNAILs
                if ($setUp->getConfig('inline_thumbs') == true) {
                    if ($file->isValidForThumb()) {
                        print "<img style=\"width:60px;\" src=\"doc-thumb.php?thumb=".$thislink."&in=y\">";
                    } else {
                        print "<span class=\"icon-placeholder\"><i class=\"fa "
                        .$thisicon."\"></i></span></a>";
                    }
                } else {
                    print "<i class=\"fa "
                    .$thisicon."\"></i>";
                }
                print "</a>";           
                   
            }

            print "</td><td class=\"name\">";
            print $downlink;
                
            if ($file->isValidForThumb()) {
                print $imgdata;
            }
            if ($ext == "pdf" || $ext == "PDF") {
                print " target=\"_blank\"";
            }
                print " class=\"item file";
            if ($file->isValidForThumb()
                && $setUp->getConfig('thumbnails') == true
            ) {
                print " thumb";
            }
                print "\">".$thisname."</a>";

            if ($file->isValidForThumb()
                && $setUp->getConfig('thumbnails') == true
            ) {
                print "<span class=\"hover\"><i class=\"fa fa-eye fa-fw\"></i></span>";
            } elseif ($ext == "pdf" || $ext == "PDF") {
                print "<span class=\"hover\"><i class=\"fa fa-arrow-right fa-fw\"></i></span>";
            } else {
                print "<span class=\"hover\"><i class=\"fa fa-download fa-fw\"></i></span>";
            }

            print "</td>\n";
            print "<td class=\"hidden\">".$setUp->fullSize($file->getSize())."</td>
            <td class=\"mini reduce nowrap\"><span class=\"hidden-xs centertext\">"
            .$setUp->formatSize($file->getSize())."</span></td>\n";

            print "<td class=\"hidden\">".$file->getModTime()."</td>
            <td class=\"mini reduce\"><span class=\"hidden-xs centertext\">"
            .$setUp->formatModTime($file->getModTime())."</span></td>\n";
            
            if ($gateKeeper->isAllowed('rename_enable') 
                && $location->editAllowed()
            ) {
                print "<td class=\"icon rename centertext\">"
                ."<a href=\"javascript:void(0)\" data-thisdir=\""
                .$thisdir."\" data-thisext=\"".$ext."\" data-thisname=\""
                .$withoutExt."\"><i class=\"fa fa-pencil-square-o\"></i></td>\n";
            }
            if ($gateKeeper->isAllowed('delete_enable') 
                && $location->editAllowed()
            ) {

                $delquery = base64_encode($del);
                $cash = md5($delquery.$setUp->getConfig('salt').$setUp->getConfig('session_name'));

                print "<td class=\"del centertext\">
                    <a data-name=\""
                    .$thisfile
                    ."\" href=\"".$thisdel."&h=".$cash."\">
                    <i class=\"fa fa-times\"></i>
                    </a>
                </td>";

            }
                print "</tr>\n";
        }
    }
                      ?>
                </tbody>
            </table>
        </form>
    </section>

    <?php
    /**
    *
    * init soundmanager
    *
    */
    if ($setUp->getConfig("playmusic") == true) { 
        print "<a type=\"audio/mp3\" class=\"sm2_button hidden\" href=\"#\"></a>";
        ?>
        <script src="doc-admin/js/soundmanager2.min.js"></script>
        <script>
            var basicMP3Player = null;

            soundManager.setup({
                url: 'doc-admin/swf/',
                debugMode: true,
                preferFlash: false,
                onready: function() {
                     basicMP3Player = new BasicMP3Player();
                }
            });
        </script>
    <?php 
    } ?>

    <?php 

    if ($setUp->getConfig("show_pagination_num") == true 
        || $setUp->getConfig("show_pagination") == true 
        || $setUp->getConfig("show_search") == true
    ) { 
            ?>
        <script type="text/javascript">
            $('#sort').addClass('ghost');

            var oTable;
            $.extend($.fn.dataTableExt.oStdClasses, {
                "sSortAsc": "header headerSortDown",
                "sSortDesc": "header headerSortUp",
                "sSortable": "header"
            }); 

            $(document).ready(function() {
                oTable = $('#sort').dataTable({
        <?php
        if ($setUp->getConfig("show_pagination_num") == true) { ?>
                    "sPaginationType": "full_numbers",
        <?php
        } else { ?>
                    "sPaginationType": "simple",
        <?php
        }
        if ($setUp->getConfig("show_pagination") == false) { ?>
                    "bPaginate": false,
        <?php
        }
        if ($setUp->getConfig("show_search") == false) { 
            ?>            
                    "bFilter": false,
        <?php
        }   ?>
                    "iDisplayLength": <?php print $setUp->getConfig('filedefnum'); ?>,
                    
                    // hide pagination if we have only one page
                    "fnDrawCallback": function() { 
                        var paginateRow = $(this).parent().find('.dataTables_paginate');                        
                        if (this.fnSettings().fnRecordsDisplay() <= this.fnSettings()._iDisplayLength) {
                            paginateRow.css('display', 'none');
                        } else {
                            paginateRow.css('display', 'block');
                        }
                    },

                    "aoColumnDefs": [
                        { "iDataSort": 3, "aTargets": [ 4 ] },
                        { "iDataSort": 5, "aTargets": [ 6 ] },
                        { "bSearchable": false, "aTargets": [ 0 ] },
                        { "bSearchable": false, "aTargets": [ 1 ] },
                        { "bSearchable": false, "aTargets": [ 3 ] },
                        { "bSearchable": false, "aTargets": [ 4 ] },
                        { "bSearchable": false, "aTargets": [ 5 ] },
                        { "bSearchable": false, "aTargets": [ 6 ] },
                        { "bSortable": false, "aTargets": [ 0 ] },
                        { "bSortable": false, "aTargets": [ 1 ] }
                    ],
                    "oLanguage": {
                        "sEmptyTable":      "--",
                        "sInfo":            "_START_ / _END_ - _TOTAL_ ",
                        "sInfoEmpty":       "",
                        "sInfoFiltered":    "",
                        "sInfoPostFix":     "",
                        "sLengthMenu":      "<i class='fa fa-list-ol'></i> _MENU_",
                        "sLoadingRecords":  "<i class='fa fa-refresh fa-spin'></i>",
                        "sProcessing":      "<i class='fa fa-refresh fa-spin'></i>",
                        "sSearch":          "<span class='input-group-addon'><i class='fa fa-search'></i></span> ",
                        "sZeroRecords":     "--",
                        "oPaginate": {
                            "sFirst": "<i class='fa fa-angle-double-left'></i>",
                            "sLast": "<i class='fa fa-angle-double-right'></i>",
                            "sPrevious": "<i class='fa fa-angle-left'></i>",
                            "sNext": "<i class='fa fa-angle-right'></i>"
                        }
                    }
                });


    <?php // list by name
        if ($setUp->getConfig('filedeforder') == "alpha") { ?>
            oTable.fnSort( [ [2,'asc'] ] );
    <?php // list by size
        } elseif ($setUp->getConfig('filedeforder') == "size") { ?>
            oTable.fnSort( [ [4,'asc'] ] );
    <?php // list by creation date
        } else { ?>
            oTable.fnSort( [ [6,'desc'] ] );
    <?php 

        } ?>
                /**
                *
                * Uncomment the following code
                * to disable instant search input,
                * enable enter key and
                * enable search button
                */
                
                // $('.dataTables_filter input').unbind('keyup').bind('keyup', function(e){
                //     if (e.which == 13){
                //         oTable.fnFilter($(this).val(), null, false, true);
                //     }
                // });
         
                // $('.dataTables_filter .input-group-addon').on('click',function(e){
                //     oTable.fnFilter($('.dataTables_filter input').val(), null, false, true);
                // });
                $('#sort').removeClass('ghost');
            });
        </script>
    <?php
    } 
} ?>