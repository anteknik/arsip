/* Modernizr 2.8.3 (Custom Build) | MIT & BSD
 * Build: http://modernizr.com/download/#-touch-teststyles-prefixes
 */
;window.Modernizr=function(a,b,c){function w(a){j.cssText=a}function x(a,b){return w(m.join(a+";")+(b||""))}function y(a,b){return typeof a===b}function z(a,b){return!!~(""+a).indexOf(b)}function A(a,b,d){for(var e in a){var f=b[a[e]];if(f!==c)return d===!1?a[e]:y(f,"function")?f.bind(d||b):f}return!1}var d="2.8.3",e={},f=!0,g=b.documentElement,h="modernizr",i=b.createElement(h),j=i.style,k,l={}.toString,m=" -webkit- -moz- -o- -ms- ".split(" "),n={},o={},p={},q=[],r=q.slice,s,t=function(a,c,d,e){var f,i,j,k,l=b.createElement("div"),m=b.body,n=m||b.createElement("body");if(parseInt(d,10))while(d--)j=b.createElement("div"),j.id=e?e[d]:h+(d+1),l.appendChild(j);return f=["&#173;",'<style id="s',h,'">',a,"</style>"].join(""),l.id=h,(m?l:n).innerHTML+=f,n.appendChild(l),m||(n.style.background="",n.style.overflow="hidden",k=g.style.overflow,g.style.overflow="hidden",g.appendChild(n)),i=c(l,a),m?l.parentNode.removeChild(l):(n.parentNode.removeChild(n),g.style.overflow=k),!!i},u={}.hasOwnProperty,v;!y(u,"undefined")&&!y(u.call,"undefined")?v=function(a,b){return u.call(a,b)}:v=function(a,b){return b in a&&y(a.constructor.prototype[b],"undefined")},Function.prototype.bind||(Function.prototype.bind=function(b){var c=this;if(typeof c!="function")throw new TypeError;var d=r.call(arguments,1),e=function(){if(this instanceof e){var a=function(){};a.prototype=c.prototype;var f=new a,g=c.apply(f,d.concat(r.call(arguments)));return Object(g)===g?g:f}return c.apply(b,d.concat(r.call(arguments)))};return e}),n.touch=function(){var c;return"ontouchstart"in a||a.DocumentTouch&&b instanceof DocumentTouch?c=!0:t(["@media (",m.join("touch-enabled),("),h,")","{#modernizr{top:9px;position:absolute}}"].join(""),function(a){c=a.offsetTop===9}),c};for(var B in n)v(n,B)&&(s=B.toLowerCase(),e[s]=n[B](),q.push((e[s]?"":"no-")+s));return e.addTest=function(a,b){if(typeof a=="object")for(var d in a)v(a,d)&&e.addTest(d,a[d]);else{a=a.toLowerCase();if(e[a]!==c)return e;b=typeof b=="function"?b():b,typeof f!="undefined"&&f&&(g.className+=" "+(b?"":"no-")+a),e[a]=b}return e},w(""),i=k=null,e._version=d,e._prefixes=m,e.testStyles=t,g.className=g.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(f?" js "+q.join(" "):""),e}(this,this.document);
/* 
*
* Close alert message
*
*/
$(document).on('click', '.closealert', function () {
    $(this).parent().fadeOut();
});

/**
*
* Call image preview 
*
*/
$(document).on('click', 'a.thumb', function(e) {
    e.preventDefault();
    $(".navigall").remove();
    var thislink = $(this).data('link');
    var thislinkencoded = $(this).data('linkencoded');
    var thisname = $(this).data('name');
    var thisID = $(this).parents('.rowa').attr('id');

    loadImg(thislink, thislinkencoded, thisname, thisID);
});


jQuery.fn.firstAfter = function(selector) {
    return this.nextAll(selector).first();
};
jQuery.fn.firstBefore = function(selector) {
    return this.prevAll(selector).first();
};

/**
*
* Quick image preview gallery navigation
*
*/
function checkNextPrev(currentID){

    var current = $('#'+currentID);
    var nextgall = current.firstAfter('.gallindex').find('.vfm-gall');
    var prevgall = current.firstBefore('.gallindex').find('.vfm-gall');

    if (nextgall.length > 0){

        var nextlink = nextgall.data('link');  
        var nextlinkencoded = nextgall.data('linkencoded');
        var nextname = nextgall.data('name');
        var nextID = current.firstAfter('.gallindex').attr('id');

        if ($('.nextgall').length < 1) {
            $(".vfm-zoom").append('<a class="nextgall navigall"><i class="fa fa-angle-right"></i></a>');
        }
        $(".nextgall").data('link', nextlink)
        $(".nextgall").data('linkencoded', nextlinkencoded)
        $(".nextgall").data('name', nextname)
        $(".nextgall").data('id', nextID)
    } else {
        $(".nextgall").remove();
    }

    if (prevgall.length > 0){

        var prevlink = prevgall.data('link');  
        var prevlinkencoded = prevgall.data('linkencoded');
        var prevname = prevgall.data('name');
        var prevID = current.firstBefore('.gallindex').attr('id');

        if ($('.prevgall').length < 1) {
            $(".vfm-zoom").append('<a class="prevgall navigall"><i class="fa fa-angle-left"></i></a>');
        }
        $(".prevgall").data('link', prevlink)
        $(".prevgall").data('linkencoded', prevlinkencoded)
        $(".prevgall").data('name', prevname)
        $(".prevgall").data('id', prevID)
    } else {
        $(".prevgall").remove();
    }
}
/**
*
* navigate through image preview gallery
*
*/
$(document).on('click', 'a.navigall', function(e) {
    var thislink = $(this).data('link');
    var thislinkencoded = $(this).data('linkencoded');
    var thisname = $(this).data('name');
    var thisID = $(this).data('id');
    $(".navigall").remove();

    loadImg(thislink, thislinkencoded, thisname, thisID);
});

$('body').keydown(function(e) {

    if(e.keyCode == 39 && $('.nextgall').length > 0) { // right
        var thislink = $('.nextgall').data('link');
        var thislinkencoded = $('.nextgall').data('linkencoded');
        var thisname = $('.nextgall').data('name');
        var thisID = $('.nextgall').data('id');
        $(".navigall").remove();

        loadImg(thislink, thislinkencoded, thisname, thisID);
    }

    if(e.keyCode == 37 && $('.prevgall').length > 0) { // left
        var thislink = $('.prevgall').data('link');
        var thislinkencoded = $('.prevgall').data('linkencoded');
        var thisname = $('.prevgall').data('name');
        var thisID = $('.prevgall').data('id');
        $(".navigall").remove();

        loadImg(thislink, thislinkencoded, thisname, thisID);
    }
});

$('#zoomview').on('hidden.bs.modal', function () {
   $(".navigall").remove();
})
/**
*
* Rename file and folder 
*
*/
$(document).on('click', '.rename a', function () {

    var thisname = $(this).data('thisname');
    var thisdir = $(this).data('thisdir');
    var thisext = $(this).data('thisext');

    $("#newname").val(thisname);
    $("#oldname").val(thisname);

    $("#dir").val(thisdir);
    $("#ext").val(thisext);
    $("#modalchangename").modal();
});


/** 
* 
* User panel 
*
*/
$(document).on('click', '.edituser', function () {

    var thisname = $(this).data('thisname');
    var thisdir = $(this).data('thisdir');
    var thisext = $(this).data('thisext');

    $("#newname").val(thisname);
    $("#oldname").val(thisname);

    $("#dir").val(thisdir);
    $("#ext").val(thisext);
});

/**
*
* password confirm
*
*/
$("#usrForm").submit(function(e){

    if($("#oldp").val().length < 1) {
        $("#oldp").focus();
        e.preventDefault();
    }

    if($("#newp").val() != $("#checknewp").val()) {
        $("#checknewp").focus();
        e.preventDefault();
    }
});

/**
*
* password reset 
*
*/
$("#rpForm").submit(function(e){

    if ($("#rep").val().length < 1) {
        $("#rep").focus();
        e.preventDefault();
    }

    if ($("#rep").val() != $("#repconf").val()) {
        $("#repconf").focus();
        e.preventDefault();
    }
});

/**
*
* add mail recipients (file sharing) 
*
*/
$(document).on('click', '.shownext', function () {
    var $lastinput = $(this).parent().prev().find('.form-group:last-child .addest');

    if ($lastinput.val().length < 5) {
        $lastinput.focus();
    } else {
        var $newdest, $inputgroup, $addon, $input;
        
        $input = $('<input name="send_cc[]" type="email" class="form-control addest">');
        $addon = $('<span class="input-group-addon"><i class="fa fa-envelope fa-fw"></i></span>');
        $inputgroup = $('<div class="input-group"></div>').append($addon).append($input);
        $newdest = $('<div class="form-group bcc-address"></div>').append($inputgroup);

        $(".wrap-dest").append($newdest);
    }
});

/**
*
* slide fade mail form
*
*/
$.fn.slideFadeToggle = function(speed, easing, callback) {
    return this.animate({
        opacity: 'toggle',
        height: 'toggle'
    }, speed, easing, callback);
};

$(document).on('click', '.openmail', function () {
    $('#sendfiles').slideFadeToggle();
});


/**
*
* create a random string
*
*/
function randomstring() 
{
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (var i=0; i < 8; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}

/**
*
* file sharing password widget
*
*/
function passwidget()
{
    if ($('#use_pass').prop('checked')) {
        $('.seclink').show();
    } else {
        $('.seclink').hide();
    } 
    $('.sharelink, .passlink').val('');
    $('.shalink, #sendfiles, .openmail').hide();
    $('.passlink').prop('readonly', false);
    $('.createlink-wrap').fadeIn();
}
/**
*
* change input value on select files
*
*/
$(document).on('change', '.btn-file :file', function () {
    var input = $(this),
    numFiles = input.get(0).files ? input.get(0).files.length : 1,
    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', [numFiles, label]);
});

/**
*
* Check - Uncheck all
*
*/
$(document).on('click', '#selectall', function (e) {
    e.preventDefault();
    $('.selecta').prop('checked',!$('.selecta').prop('checked'));
    checkSelecta();
});

/**
*
* Disable/Enable group action buttons & new directory submit
*
*/
$('.groupact, .manda, .upfolder').attr("disabled", true);

function checkSelecta(){
    if ($('.selecta:checked').length > 0) {
        $('.groupact, .manda').attr("disabled", false);
    } else {
        $('.groupact, .manda').attr("disabled", true);
    } 
}

$('.selecta').change(function() {
    checkSelecta();
});

$(document).ready(function(){
    checkSelecta();
});


$('.upload_dirname').keyup(function() {
    if($(this).val().length>0){
        $('.upfolder').attr("disabled", false);
    } else {
        $('.upfolder').attr("disabled", true);
   }
});

/**
*
* Change notify users icon
*
*/
function checkNotiflist(){

    var anyUserChecked = $('#userslist :checkbox:checked').length > 0;

    if (anyUserChecked == true) {
        $('.check-notif').removeClass('fa-circle-o').addClass('fa-check-circle');
    } else {
        $('.check-notif').removeClass('fa-check-circle').addClass('fa-circle-o');
    }
}
$('#userslist :checkbox').change(function() {
    checkNotiflist();
});

/**
*
* Fade In filemanager tables on load
*
*/
$(window).load(function(){
    $(".tableblock").animate({
        opacity: 1
        }, 500, function() {
    });

});