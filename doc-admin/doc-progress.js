
$("#upchunk").remove();
$("#upformsubmit").show();

var ie = ((document.all) ? true : false);

var progress = $('#progress-up');
var probar = $('.upbar');
var prop = $('.upbar p');
var locazio = location.pathname;
var queri = location.search;
queri = queri.replace('&response', '');
queri = queri.replace('?response', '');
queri = queri.replace('?del', '?nodel');
queri = queri.replace('&del', '&nodel');

if (queri == "") { queri = "?" } else { queri = queri + "&"; }

if (!ie) {
    $(document).on('click', '#fileToUpload', function() {
        $('.upload_file').trigger('click');
    });
    $(document).on('click', '#upformsubmit', function(e) {
        e.preventDefault();
        $('.upload_file').trigger('click');
    });

} else {
    $('#upload_file').css('display','table-cell');
    $('.ie_hidden').remove();
    $(document).on('click', '#upformsubmit', function(e) {
        $('#fileToUpload').val('Loading....');
    });
}

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

$(document).ready(function(){

    if($("#frameloader").length == 0){
        var frameloader = $("<iframe name=\"frameloader\" id=\"frameloader\"></iframe>");
        var upform = $("#upForm");
        upform.after(frameloader);
        upform.attr('target','frameloader');
    }
    var finish = false;

    $("#upForm").submit(function(e){

        if($("#fileToUpload").val().length != 0){

            $('#frameloader').load(function () {
                finish = true;
                progress.css('opacity', 0);
                setTimeout(function () {
                    location.href = locazio+queri+"response"
                }, 800);
            });
            setTimeout(function () {
                updateProgress($('#uid').val());
                progress.css('opacity', 1);
            },1000)
        }
    }); // submit

    function updateProgress(id) {
        var time = new Date().getTime();
        $.get( "./vfm-admin/vfm-progress.php", { uid: id, _: time } )
        .done(function( msg ) {
            if (progress < 100 || !finish) {
 
                finish = progress < 100;
                setTimeout(function() {
                    updateProgress(id);
                }, 1000);
            }
            probar.css('width', msg+'%');
            prop.html(parseInt(msg, 10) + "%");
        })
        .fail(function() {
            progress.html("error");
        }); // get
    }
});