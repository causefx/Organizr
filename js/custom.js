/*jslint browser: true*/
/*global $, jQuery, alert*/
var idleTime = 0;
var hasCookie = false;
var loginAttempts = 0;
$(document).ajaxComplete(function () {
    pageLoad();
    //new SimpleBar($('.internal-listing')[0]);
});
$(document).ready(function () {
    pageLoad();
    var clipboard = new Clipboard('.clipboard');
    var internalClipboard = new Clipboard('#internal-clipboard');
    clipboard.on('success', function(e) {
        message('Clipboard',e.text,activeInfo.settings.notifications.position,'#FFF','info','5000');
        e.clearSelection();
    });
    "use strict";
    var body = $("body");

    $(window).scroll(function(){
        if ($(this).scrollTop() > 100) {
            $('#scroll').fadeIn();
        } else {
            $('#scroll').fadeOut();
        }
    });
    $('#scroll').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 600);
        return false;
    });

    $(function () {
        //$("#preloader").fadeOut();
        var set = function () {
            var topOffset = 40,
                width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width,
                height = ((window.innerHeight > 0) ? window.innerHeight : this.screen.height) - 1;
            if (width < 768) {
                $('div.navbar-collapse').addClass('collapse');
                topOffset = 100; /* 2-row-menu */
            } else {
                $('div.navbar-collapse').removeClass('collapse');
            }

            /* ===== This is for resizing window ===== */

            if (width < 1170) {
                body.addClass('content-wrapper');
                $(".sidebar-nav, .slimScrollDiv").css("overflow-x", "visible").parent().css("overflow", "visible");
            } else {
                body.removeClass('content-wrapper');
            }

            height = height - topOffset;
            if (height < 1) {
                height = 1;
            }
            if (height > topOffset) {
                $("#page-wrapper").css("min-height", (height) + "px");
                //$("#page-wrapper").css("max-height", (height) + "px");

            }
        },
        url = window.location,
        element = $('ul.nav a').filter(function () {
            return this.href === url || url.href.indexOf(this.href) === 0;
        }).addClass('activez').parent().parent().addClass('ok').parent();
        if (element.is('li')) {
            element.addClass('activezo');
        }
        $(window).ready(set);
        $(window).bind("resize", set);
    });
    body.trigger("resize");
    //Increment the idle time counter every minute.
    var idleInterval = setInterval(timerIncrement, 60000); // 1 minute
    hasCookie = (getCookie('organizrToken')) ? true : false;
    //Zero the idle timer on mouse movement.
    $(this).mousemove(function (e) {
        idleTime = 0;
    });
    $(this).keypress(function (e) {
        idleTime = 0;
    });
    myLazyLoad = new LazyLoad({
        elements_selector: ".lazyload"
    });
    /* ===== Collapsible Panels JS ===== */
    (function ($, window, document) {
        var panelSelector = '[data-perform="panel-collapse"]',
            panelRemover = '[data-perform="panel-dismiss"]';
        $(panelSelector).each(function () {
            var collapseOpts = {
                    toggle: false
                },
                parent = $(this).closest('.panel'),
                wrapper = parent.find('.panel-wrapper'),
                child = $(this).children('i');
            if (!wrapper.length) {
                wrapper = parent.children('.panel-heading').nextAll().wrapAll('<div/>').parent().addClass('panel-wrapper');
                collapseOpts = {};
            }
            wrapper.collapse(collapseOpts).on('hide.bs.collapse', function () {
                child.removeClass('ti-minus').addClass('ti-plus');
            }).on('show.bs.collapse', function () {
                child.removeClass('ti-plus').addClass('ti-minus');
            });
        });

        /* ===== Collapse Panels ===== */

        $(document).on('click', panelSelector, function (e) {
            e.preventDefault();
            var parent = $(this).closest('.panel'),
                wrapper = parent.find('.panel-wrapper');
                $(this).children('i').toggleClass('ti-plus').toggleClass('ti-minus');
            wrapper.collapse('toggle');
        });

        /* ===== Remove Panels ===== */

        $(document).on('click', panelRemover, function (e) {
            e.preventDefault();
            var removeParent = $(this).closest('.panel');

            function removeElement() {
                var col = removeParent.parent();
                removeParent.remove();
                col.filter(function () {
                    return ($(this).is('[class*="col-"]') && $(this).children('*').length === 0);
                }).remove();
            }
            removeElement();
        });
    }(jQuery, window, document));
});
function pageLoad(){
    "use strict";
    //Start Organizr
    $(function () {
        if($('#preloader:visible').length == 1){
            $("#preloader").fadeOut();
        }
        myLazyLoad.update();
    });


    /* ===== Tooltip Initialization ===== */

    $(function () {
        if(bowser.mobile !== true) {
            $('[data-toggle="tooltip"]').tooltip();
        }
        /*$('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });*/
    });

    /* ===== Popover Initialization ===== */

    $(function () {
        $('[data-toggle="popover"]').popover();
    });

    $(function () {
        // Switchery
        var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
        $('.js-switch').each(function() {
            if ($(this).attr('data-switchery') !== 'true'){
                new Switchery($(this)[0], $(this).data());
            }
        });
    });

    /* ===== Collepsible Toggle ===== */

    $(".collapseble").on("click", function () {
        $(".collapseblebox").fadeToggle(350);
    });


    /* ===== Resize all elements ===== */



    /* ===== Visited ul li ===== */

    /*$('.visited li a').on("click", function (e) {
        $('.visited li').removeClass('active');
        var $parent = $(this).parent();
        if (!$parent.hasClass('active')) {
            $parent.addClass('active');
        }
        e.preventDefault();
    });*/

    /* =================================================================
        Update 1.5
        this is for close icon when navigation open in mobile view
    ================================================================= */


    /* magnific stuff */
    $('.popup-with-form').magnificPopup({
        type: 'inline',
        preloader: true,
        removalDelay: 500,
        showCloseBtn: false,
        // When elemened is focused, some mobile browsers in some cases zoom in
        // It looks not nice, so we disable it:
        callbacks: {
            beforeOpen: function() {
                if($(window).width() < 700) {
                    this.st.focus = false;
                } else {
                    this.st.focus = '#name';
                }
                this.st.mainClass = this.st.el.attr('data-effect');
            },
            beforeClose: function () {
                // Callback available since v0.9.0
                if($.magnificPopup.instance.currItem.inlineElement.find('.rubberBand').length !== 0){
                    if(!$.magnificPopup.instance.currItem.inlineElement.find('.rubberBand').hasClass('hidden')){
                        var magIndex = $.magnificPopup.instance.currItem.index;
                        message('You forgot to save','<a class="mouse" onclick="$(\'.popup-with-form\').magnificPopup(\'open\','+magIndex+')">Would you like to go back?</a>',activeInfo.settings.notifications.position,'#FFF','warning','5000');
                    }
                }
            },
        }
    });
    // Inline popups
    $('.inline-popups').magnificPopup({
      removalDelay: 500, //delay removal by X to allow out-animation
      closeOnBgClick: true,
        showCloseBtn: false,
      //closeOnContentClick: true,
      callbacks: {
        beforeOpen: function() {
           this.st.mainClass = this.st.el.attr('data-effect');
           this.st.focus = '.inline-focus';
       },
       close: function() {
          if(typeof player !== 'undefined'){
              console.log('STOP STOP STOP');
              player.destroy();
          }
        }
      },
      midClick: true // allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source.
    });

}
/* ===== Sidebar ===== */

$('.slimscrollright').slimScroll({
    height: '100%',
    position: 'right',
    size: "5px",
    color: '#dcdcdc'
});
$('.slimscrollsidebar').slimScroll({
    height: '100%',
    position: 'left',
    size: "6px",
    color: 'rgba(0,0,0,0.5)'
});
if(bowser.mobile !== true){
    $('.inbox-center').slimScroll({
        height: '100%',
        position: 'right',
        size: "5px",
        color: '#dcdcdc'
    });
}
$(".navbar-toggle").on("click", function () {
    $(".navbar-toggle i").toggleClass("ti-menu").addClass("ti-close");
});
/* ===== Login and Recover Password ===== */
$(document).on("click", "#to-recover", function(e) {
    $("#loginform").slideUp();
    $("#recoverform").fadeIn();
});
$(document).on("click", ".to-register", function(e) {
    $("#loginform").slideUp();
    $("#registerForm").removeClass('hidden');
    $("#registerform").fadeIn();
});
$(document).on("click", "#leave-recover", function(e) {
    $("#loginform").slideDown();
    $("#recoverform").fadeOut();
});
$(document).on("click", "#leave-registration", function(e) {
    $("#registerform").fadeOut();
    $("#registerForm").addClass('hidden');
    $("#loginform").slideDown();

});
$(document).on("click", ".updateNow", function(e) {
    updateNow();
});
$(document).on("click", ".show-login", function(e) {
    buildLogin();
});
$(document).on("click", ".depenency-item", function(e) {
    alert($(this).attr('data-name'));
});
function doneTypingMediaSearch () {
    var mediaSearchQuery = $('#mediaSearchQuery');
    var query = mediaSearchQuery.val();
    var server = mediaSearchQuery.attr('data-server');
    if(query == '' || query == ' '){
        return false;
    }
    switch (server) {
        case 'plex':
            var action = 'getPlexSearch';
            break;
        case 'emby':
            var action = 'getEmbySearch';
            break;
        default:
    }
    organizrAPI('POST','api/?v1/homepage/connect',{action:action, query:query}).success(function(data) {
        var response = JSON.parse(data);
        $('.mediaSearch-div').html(buildMediaResults(response.data,server,query));
        if(bowser.mobile !== true){
            $('.resultBox-inside').slimScroll({
                height: '100%',
                position: 'right',
                size: "5px",
                color: '#dcdcdc'
            });
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    })
}
$(document).on("click", ".login-button", function(e) {
    e.preventDefault;
    loginAttempts = loginAttempts + 1;
    $('#login-attempts').val(loginAttempts);
    var check = (local('g','loggingIn'));
    if(check == null) {
        local('s','loggingIn', true);
        $('div.login-box').block({
            message: '<h5><img width="20" src="plugins/images/busy.gif" /> Just a moment...</h4>',
            css: {
                color: '#fff',
                border: '1px solid #2cabe3',
                backgroundColor: '#2cabe3'
            }
        });
        var post = $('#loginform').serializeArray();
        organizrAPI('POST', 'api/?v1/login', post).success(function (data) {
            var html = JSON.parse(data);
            if (html.data == true) {
                local('set','message','Welcome|Login Successful|success');
                location.reload();
            } else if (html.data == 'mismatch') {
                $('div.login-box').unblock({});
                message('Login Error', ' Wrong username/email/password combo', activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
                console.error('Organizr Function: Login failed - wrong username/email/password');
            } else if (html.data == 'lockout') {
                $('div.login-box').block({
                    message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
                    css: {
                        color: '#fff',
                        border: '1px solid #e91e63',
                        backgroundColor: '#f44336'
                    }
                });
                message('Login Error', ' You have been Locked out', activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
                console.error('Organizr Function: Login failed - User has been locked out');
                setTimeout(function(){ local('r','loggingIn'); location.reload() }, 10000);
            } else if (html.data == '2FA') {
                $('div.login-box').unblock({});
                $('#tfa-div').removeClass('hidden');
                $('#loginform [name=tfaCode]').focus();
            } else if (html.data == '2FA-incorrect') {
                $('div.login-box').unblock({});
                $('#tfa-div').removeClass('hidden');
                $('#loginform [name=tfaCode]').focus();
                message('Login Error', html.data, activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
            } else {
                $('div.login-box').unblock({});
                message('Login Error', html.data, activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
                console.error('Organizr Function: Login failed');
            }
            local('r','loggingIn');
        }).fail(function (xhr) {
            $('div.login-box').unblock({});
            message('Login Error', 'API Connection Failed', activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
            console.error("Organizr Function: API Connection Failed");
            local('r','loggingIn');
        });
    }
});
$(document).on("click", ".unlockButton", function(e) {
    e.preventDefault;
    var post = {
        password:$('#unlockPassword').val()
    };
    organizrAPI('POST','api/?v1/unlock',post).success(function(data) {
        var html = JSON.parse(data);
        console.log(html);
        if(html.data == true){
            location.reload();
        }else if(html.data == 'Password Incorrect'){
            message('Login Error',' Wrong password',activeInfo.settings.notifications.position,'#FFF','warning','10000');
            console.error('Organizr Function: Login failed - wrong password');
        }else{
            message('Login Error',html.data,activeInfo.settings.notifications.position,'#FFF','warning','10000');
            console.error('Organizr Function: Login failed');
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: Login Failed");
    });
});
$(document).on("click", ".register-button", function(e) {
    e.preventDefault;
    var post = $( '#registerForm' ).serializeArray();
    organizrAPI('POST','api/?v1/register',post).success(function(data) {
        var html = JSON.parse(data);
        console.log(html);
        if(html.data == true){
            location.reload();
        }else if(html.data == 'mismatch'){
            message('Registration Error',' Wrong Registration Password',activeInfo.settings.notifications.position,'#FFF','warning','10000');
            console.error('Organizr Function: Registration failed - Wrong Registration Password');
        }else if(html.data == 'username taken'){
            message('Registration Error',' Registration Error - Username/Email Taken',activeInfo.settings.notifications.position,'#FFF','warning','10000');
            console.error('Organizr Function: Registration Failed - Username/Email Taken');
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: Login Failed");
    });
});
$(document).on("click", ".reset-button", function(e) {
    e.preventDefault;
    var email = $('#recover-input').val();
    if(email !== ''){
		var post = {
	        email:email
        };
        organizrAPI('POST','api/?v1/recover',post).success(function(data) {
            var html = JSON.parse(data);
            if(html.data == true){
                message('Recover Password',' Email Sent',activeInfo.settings.notifications.position,'#FFF','success','10000');
                $('#leave-recover').trigger('click');
            }else if(html.data == 'an error occured'){
                message('Recover Error',' User Error',activeInfo.settings.notifications.position,'#FFF','warning','10000');
                console.error('Organizr Function: Recover failed - Wrong Registration Password');
            }else if(html.data == 'username taken'){
                message('Recover Error',' Registration Error - Username/Email Taken',activeInfo.settings.notifications.position,'#FFF','warning','10000');
                console.error('Organizr Function: Recover Failed - Username/Email Taken');
            }
        }).fail(function(xhr) {
            console.error("Organizr Function: Login Failed");
        });
    }else{
        message('Recover Error','Enter Email',activeInfo.settings.notifications.position,'#FFF','warning','10000');
    }
});
$(document).on("click", ".open-close", function () {
    $("body").toggleClass("show-sidebar");
});
//EDIT GROUP GET ID
$(document).on("click", ".editGroupButton", function () {
    $('#edit-group-form [name=groupName]').val($(this).parent().parent().attr("data-group"));
    $('#edit-group-form [name=id]').val($(this).parent().parent().attr("data-id"));
    $('#edit-group-form [name=groupImage]').val($(this).parent().parent().attr("data-image"));
    $('#edit-group-form [name=oldGroupName]').val($(this).parent().parent().attr("data-group"));
});
//EDIT GROUP
$(document).on("click", ".editGroup", function () {
    //Create POST Array
    var post = {
        action:'editUserGroup',
        api:'api/?v1/settings/user/manage/groups',
        id:$('#edit-group-form [name=id]').val(),
        groupName:$('#edit-group-form [name=groupName]').val(),
        groupImage:$('#edit-group-form [name=groupImage]').val(),
        oldGroupName:$('#edit-group-form [name=oldGroupName]').val(),
        messageTitle:'',
        messageBody:'Edited User Group '+$('#edit-group-form [name=groupName]').val(),
        error:'Organizr Function: User Group API Connection Failed'
    };
    if (typeof post.id == 'undefined' || post.id == '') {
        message('New Group Error',' Could not get Group ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.groupName == 'undefined' || post.groupName == '') {
        message('New Group Error',' Please set a Group Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.groupImage == 'undefined' || post.groupImage == '') {
        message('New Group Error',' Please set a Group Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(post.id !== '' && post.groupName !== '' && post.groupImage !== '' ){
        var callbacks = $.Callbacks();
        callbacks.add( buildGroupManagement );
        settingsAPI(post,callbacks);
        clearForm('#edit-group-form');
        $.magnificPopup.close();
    }
});
//CHANGE DEFAULT GROUP
$(document).on("click", ".changeDefaultGroup", function () {
    //Create POST Array
    var post = {
        action:'changeDefaultGroup',
        api:'api/?v1/settings/user/manage/groups',
        id:$(this).parent().parent().attr("data-id"),
        oldGroupID:$('#manageGroupTable').find('tr[data-default=true]').attr("data-group-id"),
        oldGroupName:$('#manageGroupTable').find('tr[data-default=true]').attr("data-group"),
        newGroupID:$(this).parent().parent().attr("data-group-id"),
        newGroupName:$(this).parent().parent().attr("data-group"),
        messageTitle:'',
        messageBody:'Changed Default Group to '+$(this).parent().parent().attr("data-group"),
        error:'Organizr Function: User Group API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildGroupManagement );
    settingsAPI(post,callbacks);
});
//DELETE GROUP
$(document).on("click", ".deleteUserGroup", function () {
    var group = $(this);
    swal({
        title: window.lang.translate('Delete ')+group.parent().parent().attr("data-group")+'?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
            var post = {
                action:'deleteUserGroup',
                api:'api/?v1/settings/user/manage/groups',
                id:group.parent().parent().attr("data-id"),
                groupID:group.parent().parent().attr("data-group-id"),
                groupName:group.parent().parent().attr("data-group"),
                messageTitle:'',
                messageBody:'Deleted User Group '+group.parent().parent().attr("data-group"),
                error:'Organizr Function: User Group API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildGroupManagement );
            settingsAPI(post,callbacks);
        }
    });
});
//ADD GROUP
$(document).on("click", ".addNewGroup", function () {
    //Create POST Array
    var post = {
        action:'addUserGroup',
        api:'api/?v1/settings/user/manage/groups',
        newGroupID:parseInt($('#manageGroupTable').find('tr[data-group-id]:nth-last-child(2)').attr('data-group-id')) + 1,
        newGroupName:$('#new-group-form [name=groupName]').val(),
        newGroupImage:$('#new-group-form [name=groupImage]').val(),
        messageTitle:'',
        messageBody:'Created User Group '+$('#new-group-form [name=groupName]').val(),
        error:'Organizr Function: User Group API Connection Failed'
    };
    if (typeof post.newGroupID == 'undefined' || post.newGroupID == '') {
        message('New Group Error',' Could not get next Group ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.newGroupName == 'undefined' || post.newGroupName == '') {
        message('New Group Error',' Please set a Group Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.newGroupImage == 'undefined' || post.newGroupImage == '') {
        message('New Group Error',' Please set a Group Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(post.newGroupID !== '' && post.newGroupName !== '' && post.newGroupImage !== '' ){
        var callbacks = $.Callbacks();
        callbacks.add( buildGroupManagement );
        settingsAPI(post,callbacks);
        clearForm('#new-group-form');
        $.magnificPopup.close();
    }
});
// ADD USER
$(document).on("click", ".addNewUser", function () {
    //Create POST Array
    var post = {
        action:'addNewUser',
        api:'api/?v1/settings/user/manage/users',
        username:$('#new-user-form [name=username]').val(),
        email:$('#new-user-form [name=email]').val(),
        password:$('#new-user-form [name=password]').val(),
        messageTitle:'',
        messageBody:'Added New User: '+$('#new-user-form [name=username]').val(),
        error:'Organizr Function: User API Connection Failed'
    };
    if (typeof post.username == 'undefined' || post.username == '') {
        message('New User Error',' Please set a Username',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.email == 'undefined' || post.email == '') {
        message('New User Error',' Please set an Email',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.password == 'undefined' || post.password == '') {
        message('New User Error',' Please set a Password',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(post.username !== '' && post.email !== '' && post.password !== '' ){
        var callbacks = $.Callbacks();
        callbacks.add( buildUserManagement );
        settingsAPI(post,callbacks);
        clearForm('#new-user-form');
        $.magnificPopup.close();
    }
});
//EDIT GROUP GET ID
$(document).on("click", ".editUserButton", function () {
    $('#edit-user-form [name=username]').val($(this).parent().parent().attr("data-username"));
    $('#edit-user-form [name=id]').val($(this).parent().parent().attr("data-id"));
    $('#edit-user-form [name=email]').val($(this).parent().parent().attr("data-email"));
});
//EDIT GROUP
$(document).on("click", ".editUserAdmin", function () {
    //Create POST Array
    var post = {
        action:'editUser',
        api:'api/?v1/settings/user/manage/users',
        id:$('#edit-user-form [name=id]').val(),
        username:$('#edit-user-form [name=username]').val(),
        email:$('#edit-user-form [name=email]').val(),
        password:$('#edit-user-form [name=password]').val(),
        messageTitle:'',
        messageBody:'Edited User '+$('#edit-user-form [name=username]').val(),
        error:'Organizr Function: API Connection Failed'
    };
    if (typeof post.id == 'undefined' || post.id == '') {
        message('Edit User Error',' Could not get User ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.username == 'undefined' || post.username == '') {
        message('Edit User Error',' Please set a Username',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.email == 'undefined' || post.email == '') {
        message('Edit User Error',' Please set a User Email',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (post.password !== '' && post.password !== $('#edit-user-form [name=password2]').val()){
        message('Edit User Error',' Passwords do not match!',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(post.id !== '' && post.username !== '' && post.email !== '' ){
        var callbacks = $.Callbacks();
        callbacks.add( buildUserManagement );
        settingsAPI(post,callbacks);
        clearForm('#edit-user-form');
        $.magnificPopup.close();
    }
});
// CHANGE USER GROUP
$(document).on("change", ".userGroupSelect", function () {
    //Create POST Array
    var post = {
        action:'changeGroup',
        api:'api/?v1/settings/user/manage/users',
        id:$(this).parent().parent().attr("data-id"),
        username:$(this).parent().parent().attr("data-username"),
        oldGroup:$(this).parent().parent().attr("data-group"),
        newGroupID:$(this).find("option:selected").val(),
        newGroupName:$(this).find("option:selected").text(),
        messageTitle:'',
        messageBody:'User Info updated for '+$(this).parent().parent().attr("data-username"),
        error:'Organizr Function: User API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildUserManagement );
    settingsAPI(post,callbacks);
});
// DELETE USER
//DELETE GROUP
$(document).on("click", ".deleteUser", function () {
    var user = $(this);
    swal({
        title: window.lang.translate('Delete ')+user.parent().parent().attr("data-username")+'?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
            var post = {
                action:'deleteUser',
                api:'api/?v1/settings/user/manage/users',
                id:user.parent().parent().attr("data-id"),
                username:user.parent().parent().attr("data-username"),
                messageTitle:'',
                messageBody:window.lang.translate('Deleted User')+': '+user.parent().parent().attr("data-username"),
                error:'Organizr Function: User API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildUserManagement );
            settingsAPI(post,callbacks);
        }
    });
});
// CHANGE TAB GROUP
$(document).on("change", ".tabGroupSelect", function () {
    //Create POST Array
    var post = {
        action:'changeGroup',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        oldGroupID:$(this).parent().parent().attr("data-group-id"),
        newGroupID:$(this).find("option:selected").val(),
        newGroupName:$(this).find("option:selected").text(),
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE TAB CATEGORY
$(document).on("change", ".tabCategorySelect", function () {
    //Create POST Array
    var post = {
        action:'changeCategory',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        newCategoryID:$(this).find("option:selected").val(),
        newCategoryName:$(this).find("option:selected").text(),
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE TAB TYPE
$(document).on("change", ".tabTypeSelect", function () {
    //Create POST Array
    var post = {
        action:'changeType',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        newTypeID:$(this).find("option:selected").val(),
        newTypeName:$(this).find("option:selected").text(),
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE ENABLED TAB
$(document).on("change", ".enabledSwitch", function () {
    //Create POST Array
    var post = {
        action:'changeEnabled',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        tabEnabled:$(this).prop("checked") ? 1 : 0,
        tabEnabledWord:$(this).prop("checked") ? "On" : "Off",
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE SPLASH TAB
$(document).on("change", ".splashSwitch", function () {
    //Create POST Array
    var post = {
        action:'changeSplash',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        tabSplash:$(this).prop("checked") ? 1 : 0,
        tabSplashWord:$(this).prop("checked") ? "On" : "Off",
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE SPLASH TAB
$(document).on("change", ".pingSwitch", function () {
    //Create POST Array
    var post = {
        action:'changePing',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        tabPing:$(this).prop("checked") ? 1 : 0,
        tabPingWord:$(this).prop("checked") ? "On" : "Off",
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE PRELOAD TAB
$(document).on("change", ".preloadSwitch", function () {
    //Create POST Array
    var post = {
        action:'changePreload',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().attr("data-id"),
        tab:$(this).parent().parent().attr("data-name"),
        tabPreload:$(this).prop("checked") ? 1 : 0,
        tabPreloadWord:$(this).prop("checked") ? "On" : "Off",
        messageTitle:'',
        messageBody:'Tab Info updated for '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
// CHANGE DEFAULT TAB
$(document).on("change", ".defaultSwitch", function () {
    //Create POST Array
    var post = {
        action:'changeDefault',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$(this).parent().parent().parent().attr("data-id"),
        tab:$(this).parent().parent().parent().attr("data-name"),
        messageTitle:'',
        messageBody:'Changed Default Tab to: '+$(this).parent().parent().parent().attr("data-name"),
        error:'Organizr Function: Tab API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
    settingsAPI(post,callbacks);
});
//DELETE TAB
$(document).on("click", ".deleteTab", function () {
    var user = $(this);
    swal({
        title: window.lang.translate('Delete ') + user.parent().parent().attr("data-name") + '?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
            var post = {
                action:'deleteTab',
                api:'api/?v1/settings/tab/editor/tabs',
                id:user.parent().parent().attr("data-id"),
                tab:user.parent().parent().attr("data-name"),
                messageTitle:'',
                messageBody:window.lang.translate('Deleted Tab')+': '+user.parent().parent().attr("data-name"),
                error:'Organizr Function: Tab Editor API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildTabEditor );
            settingsAPI(post,callbacks);
        }
    });
});
function convertMsToMinutes(ms){
    if(ms === false || ms === 0 || ms === "0"){
        return 0;
    }else{
        return (ms / 1000) / 60;
    }
}
function convertMinutesToMs(minutes){
    if(minutes === false || minutes === 0 || minutes === "0"){
        return 0;
    }else{
        return (minutes * 1000) * 60;
    }
}
//EDIT TAB GET ID
$(document).on("click", ".editTabButton", function () {
    //tabActionTime
    //tabActionType
    $('#edit-tab-form [name=tabName]').val($(this).parent().parent().attr("data-name"));
    $('#originalTabName').html($(this).parent().parent().attr("data-name"));
    $('#edit-tab-form [name=tabURL]').val($(this).parent().parent().attr("data-url"));
    $('#edit-tab-form [name=tabLocalURL]').val($(this).parent().parent().attr("data-local-url"));
    $('#edit-tab-form [name=pingURL]').val($(this).parent().parent().attr("data-ping-url"));
    $('#edit-tab-form [name=tabImage]').val($(this).parent().parent().attr("data-image"));
    $('#edit-tab-form [name=id]').val($(this).parent().parent().attr("data-id"));
    $('#edit-tab-form [name=tabActionTime]').val(convertMsToMinutes($(this).parent().parent().attr("data-tab-action-time")));
    $('#edit-tab-form [name=tabActionType]').val($(this).parent().parent().attr("data-tab-action-type"));
    if( $(this).parent().parent().attr("data-url").indexOf('/?v') > 0){
        $('#edit-tab-form [name=tabURL]').prop('disabled', 'true');
    }else{
        $('#edit-tab-form [name=tabURL]').prop('disabled', null);
    }
});
//EDIT TAB
$(document).on("click", ".editTab", function () {
    var originalTabName = $('#originalTabName').html();
    //Create POST Array
    var post = {
        action:'editTab',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$('#edit-tab-form [name=id]').val(),
        tabName:$('#edit-tab-form [name=tabName]').val(),
        tabImage:$('#edit-tab-form [name=tabImage]').val(),
        tabURL:$('#edit-tab-form [name=tabURL]').val(),
        tabLocalURL:$('#edit-tab-form [name=tabLocalURL]').val(),
        pingURL:$('#edit-tab-form [name=pingURL]').val(),
        tabActionTime:convertMinutesToMs($('#edit-tab-form [name=tabActionTime]').val()),
        tabActionType:$('#edit-tab-form [name=tabActionType]').val(),
        messageTitle:'',
        messageBody:'Edited Tab '+$('#edit-tab-form [name=tabName]').val(),
        error:'Organizr Function: Tab Editor API Connection Failed'
    };
    if (typeof post.id == 'undefined' || post.id == '') {
        message('Edit Tab Error',' Could not get Tab ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.tabName == 'undefined' || post.tabName == '') {
        message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.tabImage == 'undefined' || post.tabImage == '') {
        message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if ((typeof post.tabURL == 'undefined' || post.tabURL == '') && (typeof post.tabLocalURL == 'undefined' || post.tabLocalURL == '')) {
        message('Edit Tab Error',' Please set a Tab URL or Local URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(checkIfTabNameExists(post.tabName) && originalTabName !== post.tabName){
        message('Edit Tab Error',' Tab name already used',activeInfo.settings.notifications.position,'#FFF','warning','5000');
        return false;
    }
    if(post.id !== '' && post.tabName !== '' && post.tabImage !== ''){
        var callbacks = $.Callbacks();
        callbacks.add( buildTabEditor );
        settingsAPI(post,callbacks);
        clearForm('#edit-tab-form');
        $.magnificPopup.close();
    }
});
//ADD NEW TAB
$(document).on("click", ".addNewTab", function () {
    //Create POST Array
    var post = {
        action:'addNewTab',
        api:'api/?v1/settings/tab/editor/tabs',
        tabOrder:parseInt($('#tabEditorTable').find('tr[data-order]').last().attr('data-order')) + 1,
        tabName:$('#new-tab-form [name=tabName]').val(),
        tabImage:$('#new-tab-form [name=tabImage]').val(),
        tabURL:$('#new-tab-form [name=tabURL]').val(),
        tabLocalURL:$('#new-tab-form [name=tabLocalURL]').val(),
        pingURL:$('#new-tab-form [name=pingURL]').val(),
        tabActionTime:convertMinutesToMs($('#new-tab-form [name=tabActionTime]').val()),
        tabActionType:$('#new-tab-form [name=tabActionType]').val(),
        tabGroupID:1,
        tabEnabled:0,
        tabDefault:0,
        tabType:1,
        messageTitle:'Created Tab '+$('#new-tab-form [name=tabName]').val(),
        messageBody:'Please <a href="javascript:void(0)" onclick="window.location.reload(false);">RELOAD</a> page to update',
        error:'Organizr Function: Tab API Connection Failed'
    };
    if (typeof post.tabOrder == 'undefined' || post.tabOrder == '') {
        message('New Tab Error',' Could not get next Group ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.tabName == 'undefined' || post.tabName == '') {
        message('New Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if ((typeof post.tabURL == 'undefined' || post.tabURL == '') && (typeof post.tabLocalURL == 'undefined' || post.tabLocalURL == '')) {
        message('New Tab Error',' Please set a Tab URL or Local URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.tabImage == 'undefined' || post.tabImage == '') {
        message('New Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(checkIfTabNameExists(post.tabName)){
        message('New Tab Error',' Tab name already used',activeInfo.settings.notifications.position,'#FFF','warning','5000');
        return false;
    }
    if(post.tabOrder !== '' && post.tabName !== '' && (post.tabURL !== '' || post.tabLocalURL !== '') && post.tabImage !== '' ){
        var callbacks = $.Callbacks();
        callbacks.add( buildTabEditor );
        settingsAPI(post,callbacks);
        clearForm('#new-tab-form');
        $.magnificPopup.close();
    }
});
//ADD NEW CATEGORY
$(document).on("click", ".addNewCategory", function () {
    //Create POST
    var nextID = [];
    $($('#categoryEditorTable').find('tr[data-category-id]')).each(function () {
        nextID.push($(this).attr('data-category-id'));
    });
    var post = {
        action:'addNewCategory',
        api:'api/?v1/settings/tab/editor/categories',
        categoryOrder:parseInt($('#categoryEditorTable').find('tr[data-order]').last().attr('data-order')) + 1,
        categoryName:$('#new-category-form [name=name]').val(),
        categoryImage:$('#new-category-form [name=image]').val(),
        categoryID:Math.max.apply( null, nextID ) + 1,
        categoryDefault:0,
        messageTitle:'',
        messageBody:'Created Category '+$('#new-category-form [name=name]').val(),
        error:'Organizr Function: API Connection Failed'
    };
    console.log(post);
    if (typeof post.categoryID == 'undefined' || post.categoryID == '') {
        message('New Category Error',' Could not get next Category ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.categoryName == 'undefined' || post.categoryName == '') {
        message('New Category Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.categoryOrder == 'undefined' || post.categoryOrder == '') {
        message('New Category Error',' Could not get Category Order',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.categoryImage == 'undefined' || post.categoryImage == '') {
        message('New Category Error',' Please set a Category Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(post.categoryID !== '' && post.categoryName !== '' && post.categoryOrder !== '' && post.categoryImage !== '' ){
        var callbacks = $.Callbacks();
        callbacks.add( buildCategoryEditor );
        settingsAPI(post,callbacks);
        clearForm('#new-category-form');
        $.magnificPopup.close();
    }
});
//DELETE CATEGORY
$(document).on("click", ".deleteCategory", function () {
    var category = $(this);
    swal({
        title: window.lang.translate('Delete ')+category.parent().parent().attr("data-name")+'?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
            var post = {
                action:'deleteCategory',
                api:'api/?v1/settings/tab/editor/categories',
                id:category.parent().parent().attr("data-id"),
                category:category.parent().parent().attr("data-name"),
                messageTitle:'',
                messageBody:window.lang.translate('Deleted Category')+': '+category.parent().parent().attr("data-name"),
                error:'Organizr Function: API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildCategoryEditor );
            settingsAPI(post,callbacks);
        }
    });
});
//EDIT CATEGORY GET ID
$(document).on("click", ".editCategoryButton", function () {
    $('#edit-category-form [name=name]').val($(this).parent().parent().attr("data-name"));
    $('#edit-category-form [name=image]').val($(this).parent().parent().attr("data-image"));
    $('#edit-category-form [name=id]').val($(this).parent().parent().attr("data-id"));
});
//EDIT CATEGORY
$(document).on("click", ".editCategory", function () {
    //Create POST Array
    var post = {
        action:'editCategory',
        api:'api/?v1/settings/tab/editor/categories',
        id:$('#edit-category-form [name=id]').val(),
        name:$('#edit-category-form [name=name]').val(),
        image:$('#edit-category-form [name=image]').val(),
        messageTitle:'',
        messageBody:'Edited Category '+$('#edit-category-form [name=name]').val(),
        error:'Organizr Function: API Connection Failed'
    };
    console.log(post);
    if (typeof post.id == 'undefined' || post.id == '') {
        message('Edit Tab Error',' Could not get Tab ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if (typeof post.name == 'undefined' || post.name == '') {
        message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if (typeof post.image == 'undefined' || post.image == '') {
        message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(post.id !== '' && post.name !== '' && post.image !== ''){
        var callbacks = $.Callbacks();
        callbacks.add( buildCategoryEditor );
        settingsAPI(post,callbacks);
        clearForm('#edit-category-form');
        $.magnificPopup.close();
    }
});
//CHANGE DEFAULT CATEGORY
$(document).on("click", ".changeDefaultCategory", function () {
    //Create POST Array
    var post = {
        action:'changeDefault',
        api:'api/?v1/settings/tab/editor/categories',
        id:$(this).parent().parent().attr("data-id"),
        oldCategoryName:$('#categoryEditorTable').find('tr[data-default=true]').attr("data-name"),
        newCategoryName:$(this).parent().parent().attr("data-name"),
        messageTitle:'',
        messageBody:'Changed Default Category to '+$(this).parent().parent().attr("data-name"),
        error:'Organizr Function: API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildCategoryEditor );
    settingsAPI(post,callbacks);
});
// CHANGE CUSTOMIZE Options and CSS Save
$(document).on("click", ".saveCss", function () {
    $('.cssTextarea').val(cssEditor.getValue()).trigger('change');
});
$(document).on("click", ".saveThemeCss", function () {
    $('.cssThemeTextarea').val(cssThemeEditor.getValue()).trigger('change');
});
$(document).on("click", ".saveJava", function () {
    $('.javaTextarea').val(javaEditor.getValue()).trigger('change');
});
$(document).on("click", ".saveThemeJava", function () {
    $('.javaThemeTextarea').val(javaThemeEditor.getValue()).trigger('change');
});
$(document).on("click", ".savecustomHTMLoneTextarea", function () {
    $('.customHTMLoneTextarea').val(customHTMLoneEditor.getValue()).trigger('change');
});
$(document).on("click", ".savecustomHTMLtwoTextarea", function () {
    $('.customHTMLtwoTextarea').val(customHTMLtwoEditor.getValue()).trigger('change');
});

$(document).on('focusout', 'input.pick-a-color', function(e) {
    var original = $(this).attr('data-original');
    var newValue = $(this).val();
    if((original !== newValue) && (newValue !== '#987654') && newValue !== ''){
        $(this).change();
        $(this).attr('data-original', newValue);
    }else if(newValue == ''){
        $(this).attr('style','');
    }
});
$(document).on('change keydown', '.addFormTick :input', function(e) {
    $(this).attr('data-changed', true);
    $(this).closest('.form-group').addClass('has-success');
    var formID = $(this).closest('form').attr('id');
    $('#'+formID+'-save').removeClass('hidden');
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val();
    }
    if($(this).hasClass('themeChanger')){
        changeTheme(value);
    }
    if($(this).hasClass('styleChanger')){
        changeStyle(value);
    }
    if($(this).hasClass('notifyChanger')){
        activeInfo.settings.notifications.backbone = value;
        defineNotification();
    }
    if($(this).hasClass('notifyPositionChanger')){
        activeInfo.settings.notifications.position = value;
    }
    if($(this).hasClass('authDebug')){
        activeInfo.settings.misc.authDebug = value;
    }
});
//DELETE IMAGE
$(document).on("click", ".deleteImage", function () {
    var image = $(this);
    swal({
        title: window.lang.translate('Delete ')+image.attr("data-image-name")+'?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
            var post = {
                action:'deleteImage',
                api:'api/?v1/settings/image/manager/view',
                imageName:image.attr("data-image-name"),
                imagePath:image.attr("data-image-path"),
                messageTitle:'',
                messageBody:window.lang.translate('Deleted Image')+': '+image.attr("data-image-name"),
                error:'Organizr Function: User API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildImageManagerView );
            settingsAPI(post,callbacks);
        }
    });
});
// RELOAD Page
$(document).on("click", ".reload", function () {
    location.reload();
});
// ENABLE PLUGIN
$(document).on('click', '.enablePlugin', function() {
    var post = {
        action:'enable',
        api:'api/?v1/settings/plugins/list',
        name:$(this).attr('data-plugin-name'),
        configName:$(this).attr('data-config-name'),
        messageTitle:'',
        messageBody:'Enabling '+$(this).attr('data-plugin-name'),
        error:'Organizr Function: API Connection Failed'
    };
    //$('#customize-appearance-reload').removeClass('hidden');
    var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
    settingsAPI(post,callbacks);
    ajaxloader(".content-wrap","in");
    setTimeout(function(){ buildPlugins();ajaxloader(); }, 3000);

});
// DISABLE PLUGIN
$(document).on('click', '.disablePlugin', function() {
    var plugin = $(this);
    swal({
        title: window.lang.translate('Disable')+' '+plugin.attr("data-plugin-name")+'?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
            var post = {
                action:'disable',
                api:'api/?v1/settings/plugins/list',
                name:plugin.attr('data-plugin-name'),
                configName:plugin.attr('data-config-name'),
                messageTitle:'',
                messageBody:'Disabling '+plugin.attr('data-plugin-name'),
                error:'Organizr Function: API Connection Failed'
            };
            //$('#customize-appearance-reload').removeClass('hidden');
            var callbacks = $.Callbacks();
            //callbacks.add( buildCustomizeAppearance );
            settingsAPI(post,callbacks);
            ajaxloader(".content-wrap","in");
            setTimeout(function(){ buildPlugins();ajaxloader(); }, 3000);
        }
    });
});
// AUTH BACKEND HIDE SHOW
$(document).on('change', '#authSelect, #authBackendSelect', function(e) {
    changeAuth();
});
$(document).on("click", ".getSSOPlexToken", function () {
    $('.ssoPlexTokenMessage').text("Grabbing Token");
    $('.ssoPlexTokenHeader').addClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
    var plex_username = $('#sso-plex-token-form [name=username]').val().trim();
    var plex_password = $('#sso-plex-token-form [name=password]').val().trim();
    if ((plex_password !== '') && (plex_password !== '')) {
        $.ajax({
            type: 'POST',
            headers: {
                'X-Plex-Product':'Organizr',
                'X-Plex-Version':'2.0',
                'X-Plex-Client-Identifier':'01010101-10101010'
            },
            url: 'https://plex.tv/users/sign_in.json',
            data: {
                'user[login]': plex_username,
                'user[password]': plex_password,
                force: true
            },
            cache: false,
            async: true,
            complete: function(xhr, status) {
                var result = $.parseJSON(xhr.responseText);
                if (xhr.status === 201) {
                    $('.ssoPlexTokenMessage').text(xhr.statusText);
                    $('.ssoPlexTokenHeader').addClass('panel-success').removeClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
                    $('#sso-form [name=plexToken]').val(result.user.authToken);
                    $('#sso-form [name=plexToken]').change();
                } else {
                    $('.ssoPlexTokenMessage').text(xhr.statusText);
                    $('.ssoPlexTokenHeader').addClass('panel-danger').removeClass('panel-info').removeClass('panel-warning');
                }
            }
        });
    } else {
        $('.ssoPlexTokenMessage').text("Enter Username and Password");
        $('.ssoPlexTokenHeader').addClass('panel-warning').removeClass('panel-info').removeClass('panel-danger');
    }
});
$(document).on("click", ".getPlexMachineSSO", function () {
    var plex_token = $('#sso-form [name=plexToken]').val().trim();
    if (plex_token !== '') {
        $('.ssoPlexMachineMessage').text("Grabbing List");
        $('.ssoPlexMachineHeader').addClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
        $.ajax({
            type: 'GET',
            headers: {
                'X-Plex-Product':'Organizr',
                'X-Plex-Version':'2.0',
                'X-Plex-Client-Identifier':'01010101-10101010',
                'X-Plex-Token':plex_token,
            },
            url: 'https://plex.tv/pms/servers.xml',
            cache: false,
            async: true,
            complete: function(xhr, status) {
                var result = $.parseXML(xhr.responseText);
                if (xhr.status === 200) {
                    $('.ssoPlexMachineMessage').text('Choose Plex Server');
                    $('.ssoPlexMachineHeader').addClass('panel-success').removeClass('panel-info').removeClass('panel-warning');
                    var machines = '<option lang="en">Choose Plex Machine</option>';
                    $('Server', result).each(function(){
                        if($(this).attr('owned') == 1){
                            var name = $(this).attr('name');
                            var machine = $(this).attr('machineIdentifier');
                            name = name + ' [' + machine + ']';
                            machines += '<option value="'+machine+'">'+name+'</option>';
                        }
                    });
                    var listing = '<select class="form-control" id="ssoPlexMachineSelector" data-type="select">'+machines+'</select>';
                    $('.ssoPlexMachineListing').html(listing);
                } else {
                    $('.ssoPlexTokenMessage').text(xhr.statusText);
                    $('.ssoPlexTokenHeader').addClass('panel-danger').removeClass('panel-info').removeClass('panel-warning');
                }
            }
        });
    } else {
        $('.ssoPlexMachineMessage').text("Plex Token Needed");
        $('.ssoPlexMachineHeader').addClass('panel-warning').removeClass('panel-info').removeClass('panel-danger');
    }
});
$(document).on('change', '#ssoPlexMachineSelector', function(e) {
    $('#sso-form [name=plexID]').val($(this).val());
    $('#sso-form [name=plexID]').change();
});
$(document).on("click", ".getauthPlexToken", function () {
    $('.authPlexTokenMessage').text("Grabbing Token");
    $('.authPlexTokenHeader').addClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
    var plex_username = $('#auth-plex-token-form [name=username]').val().trim();
    var plex_password = $('#auth-plex-token-form [name=password]').val().trim();
    if ((plex_password !== '') && (plex_password !== '')) {
        $.ajax({
            type: 'POST',
            headers: {
                'X-Plex-Product':'Organizr',
                'X-Plex-Version':'2.0',
                'X-Plex-Client-Identifier':'01010101-10101010'
            },
            url: 'https://plex.tv/users/sign_in.json',
            data: {
                'user[login]': plex_username,
                'user[password]': plex_password,
                force: true
            },
            cache: false,
            async: true,
            complete: function(xhr, status) {
                var result = $.parseJSON(xhr.responseText);
                if (xhr.status === 201) {
                    $('.authPlexTokenMessage').text(xhr.statusText);
                    $('.authPlexTokenHeader').addClass('panel-success').removeClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
                    $('#settings-main-form [name=plexToken]').val(result.user.authToken);
                    $('#settings-main-form [name=plexToken]').change();
                } else {
                    $('.authPlexTokenMessage').text(xhr.statusText);
                    $('.authPlexTokenHeader').addClass('panel-danger').removeClass('panel-info').removeClass('panel-warning');
                }
            }
        });
    } else {
        $('.authPlexTokenMessage').text("Enter Username and Password");
        $('.authPlexTokenHeader').addClass('panel-warning').removeClass('panel-info').removeClass('panel-danger');
    }
});
$(document).on("click", ".getPlexMachineAuth", function () {
    var plex_token = $('#settings-main-form [name=plexToken]').val().trim();
    if (plex_token !== '') {
        $('.authPlexMachineMessage').text("Grabbing List");
        $('.authPlexMachineHeader').addClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
        $.ajax({
            type: 'GET',
            headers: {
                'X-Plex-Product':'Organizr',
                'X-Plex-Version':'2.0',
                'X-Plex-Client-Identifier':'01010101-10101010',
                'X-Plex-Token':plex_token,
            },
            url: 'https://plex.tv/pms/servers.xml',
            cache: false,
            async: true,
            complete: function(xhr, status) {
                var result = $.parseXML(xhr.responseText);
                if (xhr.status === 200) {
                    $('.authPlexMachineMessage').text('Choose Plex Server');
                    $('.authPlexMachineHeader').addClass('panel-success').removeClass('panel-info').removeClass('panel-warning');
                    var machines = '<option lang="en">Choose Plex Machine</option>';
                    $('Server', result).each(function(){
                        if($(this).attr('owned') == 1){
                            var name = $(this).attr('name');
                            var machine = $(this).attr('machineIdentifier');
                            name = name + ' [' + machine + ']';
                            machines += '<option value="'+machine+'">'+name+'</option>';
                        }
                    });
                    var listing = '<select class="form-control" id="authPlexMachineSelector" data-type="select">'+machines+'</select>';
                    $('.authPlexMachineListing').html(listing);
                } else {
                    $('.authPlexTokenMessage').text(xhr.statusText);
                    $('.authPlexTokenHeader').addClass('panel-danger').removeClass('panel-info').removeClass('panel-warning');
                }
            }
        });
    } else {
        $('.authPlexMachineMessage').text("Plex Token Needed");
        $('.authPlexMachineHeader').addClass('panel-warning').removeClass('panel-info').removeClass('panel-danger');
    }
});
$(document).on('change', '#authPlexMachineSelector', function(e) {
    $('#settings-main-form [name=plexID]').val($(this).val());
    $('#settings-main-form [name=plexID]').change();
});
$(document).on("click", ".closeErrorPage", function () {
    $('.error-page').html('');
    $('.error-page').fadeOut();
});
// test Location
$(document).on("click", ".testPath", function () {
    var path = $("#form-location").val();
    if (typeof path == 'undefined' || path == '') {
        message('Path Error',' Please enter a path for DB',activeInfo.settings.notifications.position,'#FFF','warning','10000');
    }else{
        organizrAPI('POST','api/?v1/wizard_path',{path:path}).success(function(data) {
            var html = JSON.parse(data);
            console.log(html);
            if(html.data == true){
                message('Path',' Path is good to go',activeInfo.settings.notifications.position,'#FFF','success','10000');
            }else{
                message('Path Error',' Path is not writable',activeInfo.settings.notifications.position,'#FFF','warning','10000');
            }
        }).fail(function(xhr) {
            console.error("Organizr Function: Connection Failed");
        });
    }
});
$(document).on({
    mouseenter: function () {
        $(this).find('.progress').toggleClass('progress-lg');
        $(this).find('.progress').find('span').toggleClass('hidden');
        $(this).find('.white-box').toggleClass('nowPlayingHover');
    },
    mouseleave: function () {
        $(this).find('.progress').toggleClass('progress-lg');
        $(this).find('.progress').find('span').toggleClass('hidden');
        $(this).find('.white-box').toggleClass('nowPlayingHover');
    }
}, '.nowPlayingItem');
// recent filter
$(document).on("click", ".recent-filter li>a", function () {
    var filter = $(this).attr('data-filter');
    var type = $(this).attr('server-filter'); //plex or emby
    //console.log(filter);
    if(filter == 'all'){
        $('.'+type+'-recent').find('.recent-item').parent().removeClass('hidden');
    }else{
        $('.'+type+'-recent').find('.recent-item').parent().removeClass('hidden');
        $('.'+type+'-recent').find('.recent-item:not(.'+ filter + ')').parent().addClass('hidden');
    }
    var owl = $('.'+type+'-recent');
    owl.owlCarousel();
    owl.trigger('refresh.owl.carousel');
    owl.trigger('to.owl.carousel',0);
});
// request search filter
$(document).on("click", ".filter-request-result", function () {
    var filter = $(this).attr('data-filter');
    if(filter == 'request-result-all'){
        $('.request-result-item').removeClass('hidden');
    }else{
        $('.request-result-item').addClass('hidden');
        $('.'+filter).removeClass('hidden');
    }
});
//playlist filter
$(document).on("click", ".playlist-filter li>a", function () {
    var filter = $(this).attr('data-filter');
    var button = $(this).attr('data-filter')+'-playlist';
    var title = decodeURI($(this).attr('data-title'));
    var type = $(this).attr('server-filter'); //plex or emby
    $('.'+type+'-playlistTitle').html(title);
    $('.'+type+'-playlist').addClass('hidden');
    $('.'+filter+'-playlist').removeClass('hidden');
    $('.playlist-next').attr('onclick','owlChange(\''+button+'\',\'next\');');
    $('.playlist-previous').attr('onclick','owlChange(\''+button+'\',\'previous\');');

});
// refresh cache image
$(document).on("click", ".refreshImage", function(e) {
    message('',' Refreshing Image...',activeInfo.settings.notifications.position,'#FFF','success','1000');
    e.preventDefault;
    var original = $(this).attr('data-image');
    var type = $(this).attr('data-type');
    switch (type) {
        case 'nowPlaying':
            var orginalElement = $(this).parent().parent().parent().parent().find('.imageSource');
            orginalElement.attr('src', original);
            break;
        case 'recent-item':
            var orginalElementAlt = $(this).parent().parent().parent().find('.imageSourceAlt');
            var orginalElement = $(this).parent().parent().parent().parent().find('.imageSource');
            orginalElement.attr('style', 'background-image: url("'+original+'");');
            orginalElementAlt.attr('src', original);
            break;
        default:

    }
    setTimeout(function(){
        message('Image Refreshed ',' Clear Cache Please',activeInfo.settings.notifications.position,'#FFF','success','3000');
    }, 1000);
});
// open tab code
$(document).on("click", ".openTab", function(e) {
    if($(this).attr("data-open-tab") === "true") {
        var tabName = $(this).attr("data-tab-name");
        var container = $("#container-"+tabName);
        var activeFrame = container.children('iframe');
        if(activeFrame.length === 1){
            $('#menu-'+tabName+' a').trigger("click");
            activeFrame.attr("src", $(this).attr("data-url"));
        }else{
            container.attr("data-url", $(this).attr("data-url"));
            $('#menu-'+tabName+' a').trigger("click");
        }
    }else{
        var source = $(this).attr("data-url");
        window.open(source, '_blank');
    }
    $.magnificPopup.close();
});
//request click
$(document).on("click", ".request-item", function(e) {
    var target = $(this).attr('data-target');
    $('#link-'+target).trigger('click');
});
// metadata start
$(document).on("click", ".metadata-get", function(e) {
    if($(e.target).hasClass('mdi-refresh')) return;
    $("#preloader").fadeIn();
    var key = $(this).attr('data-key');
    var uid = $(this).attr('data-uid');
    var source = $(this).attr('data-source');
    switch (source) {
        case 'plex':
            var action = 'getPlexMetadata';
            break;
        case 'emby':
            var action = 'getEmbyMetadata';
            break;
        default:

    }
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/homepage/connect',{action:action, key:key}).success(function(data) {
        var response = JSON.parse(data);
        $('.'+uid+'-metadata-info').html('');
        $('.'+uid+'-metadata-info').html(buildMetadata(response.data, source));
        $('.'+uid).trigger('click');
        $(".metadata-actors").owlCarousel({
            autoplay: true,
            slideSpeed : 300,
            paginationSpeed : 400,
            nav:false,
			dots:false,
			margin:10,
			autoWidth:true,
			items:4
        });
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
    $("#preloader").fadeOut();

});
// sab play/resume
$(document).on("click", ".downloader", function(e) {
    var action = $(this).attr('data-action');
    var source = $(this).attr('data-source');
    var target = $(this).attr('data-target');
    //console.log(action);
    //console.log(source);
    //console.log(target);
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/downloader',{action:action, source:source, target:target}).success(function(data) {
        var response = JSON.parse(data);
        //console.log(response);
        homepageDownloader(source);
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();

});
// test tab
$(document).on("click", ".testTab", function () {
    var input = $('#new-tab-form-inputURLNew');
    if(input.val() == ''){
        message('','Please enter a URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(input.val() !== ''){
        var post = {
            url:input.val()
        };
        organizrAPI('POST','api/?v1/test/iframe',post).success(function(data) {
            var html = JSON.parse(data);
            if(html.data == true){
                $('.tabTestMessage.alert-success').removeClass('hidden');
                $('.tabTestMessage.alert-danger').addClass('hidden');
            }else{
                $('.tabTestMessage.alert-danger').removeClass('hidden');
                $('.tabTestMessage.alert-success').addClass('hidden');
            }

        }).fail(function(xhr) {
            console.error("Organizr Function: Check Failed");
        });
    }
});
$(document).on("click", ".testEditTab", function () {
    var input = $('#edit-tab-form-inputURL');
    if(input.val() == ''){
        message('','Please enter a URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(input.val() !== ''){
        var post = {
            url:input.val()
        };
        organizrAPI('POST','api/?v1/test/iframe',post).success(function(data) {
            var html = JSON.parse(data);
            if(html.data == true){
                $('.tabEditTestMessage.alert-success').removeClass('hidden');
                $('.tabEditTestMessage.alert-danger').addClass('hidden');
            }else{
                $('.tabEditTestMessage.alert-danger').removeClass('hidden');
                $('.tabEditTestMessage.alert-success').addClass('hidden');
            }

        }).fail(function(xhr) {
            console.error("Organizr Function: Check Failed");
        });
    }
});
// new api key
$(document).on("click", ".newAPIKey", function () {
    $('#settings-main-form [name=organizrAPI]').val(generateCode());
    $('#settings-main-form [name=organizrAPI]').change();
});
// purge logvcfdD\o8i 8
$(document).on("click", ".purgeLog", function () {
    var name = $('.swapLog.active').attr('data-name');
    var path = $('.swapLog.active').attr('data-path');
    if(name !== '' && path !== ''){
        removeFile(path,name);
        setTimeout(function(){ loadSettingsPage('api/?v1/settings/settings/logs','#settings-settings-logs','Log Viewer'); }, 1500);
    }

});
//Show Password
$(document).on("click", ".showPassword", function () {
    var toggle = $(this).parent().parent().find('.password-alt');
    if (toggle.attr('type') === "password") {
        toggle.attr('type', 'text');
    } else {
        toggle.attr('type', 'password');
    }
    $(this).find('.passwordToggle').toggleClass('fa-eye').toggleClass('fa-eye-slash');
});
$(document).on("click", ".emailUser", function () {
    var email = $(this).parent().parent().attr('data-email');
    if(activeInfo.plugins["PHPMAILER-enabled"] == true){
        $('.emailModal').click();
        $('#sendEmailToInput').val(email);
    }else{
        message('Email','Plugin not setup',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
});
// calendar popups
$(document).on('click', "a[class*=ID-]", function(){
    //$("#preloader").fadeIn();
    var details = $(this).attr('data-details');
    var target = $(this).attr('data-target')+'-metadata-info';
    var json = JSON.parse(details);
    $('.'+target).html(buildCalendarMetadata(json));
    //$("#preloader").fadeOut();
    myLazyLoad.update();
});
// request filter
$(document).on("change", ".filter-request-input", function () {
    $('.request-item').parent().removeClass('hidden');
    var filterArray = [];
    $('.filter-request-input').each(function () {
        var value = $(this).prop('checked');
        var filter = $(this).attr('data-filter');
        if(value == false){
            filterArray.push('.'+filter);
        }
    });
    $('.request-item').each(function () {
        var element = $(this);
        var string = filterArray.join(', ');
        if(element.is(string)){
            element.parent().addClass('hidden');
        }
    });
    var owl = $('.request-items');
    owl.owlCarousel();
    owl.trigger('refresh.owl.carousel');
    owl.trigger('to.owl.carousel',0);
});
//search ombi
var typingTimer;
//on keyup, start the countdown
$(document).on('keyup', '#request-input', function () {
  clearTimeout(typingTimer);
  typingTimer = setTimeout(doneTyping, 750);
});
$(document).on('keyup', '#mediaSearchQuery', function () {
  clearTimeout(typingTimer);
  typingTimer = setTimeout(doneTypingMediaSearch, 750);
});
//on keydown, clear the countdown
$(document).on('keydown', '#request-input', function () {
  clearTimeout(typingTimer);
});
$(document).on('keydown', '#mediaSearchQuery', function () {
  clearTimeout(typingTimer);
});
$(document).on('keydown', 'body', function () {
    blockDev();
});
/* ===== Open-Close Right Sidebar ===== */

$(document).on("click", ".right-side-toggle", function () {
    $(".right-sidebar").slideDown(50).toggleClass("shw-rside");
    $(".fxhdr").on("click", function () {
        $("body").toggleClass("fix-header"); /* Fix Header JS */
    });
    $(".fxsdr").on("click", function () {
        $("body").toggleClass("fix-sidebar"); /* Fix Sidebar JS */
    });

    /* ===== Service Panel JS ===== */

    var fxhdr = $('.fxhdr');
    if ($("body").hasClass("fix-header")) {
        fxhdr.attr('checked', true);
    } else {
        fxhdr.attr('checked', false);
    }
});
$(document).on('mousewheel', '.recent-items .owl-stage', function (e) {
    if (e.shiftKey) {
        if (e.deltaY>0) {
            $('.recent-items').trigger('next.owl');
        } else {
            $('.recent-items').trigger('prev.owl');
        }
        e.preventDefault();
    }
});
$(document).on('mousewheel', '.playlist-items .owl-stage', function (e) {
    if (e.shiftKey) {
        if (e.deltaY>0) {
            $('.playlist-items').trigger('next.owl');
        } else {
            $('.playlist-items').trigger('prev.owl');
        }
        e.preventDefault();
    }
});
$(document).on('mousewheel', '.request-items .owl-stage', function (e) {
    if (e.shiftKey) {
        if (e.deltaY>0) {
            $('.request-items').trigger('next.owl');
        } else {
            $('.request-items').trigger('prev.owl');
        }
    e.preventDefault();
    }
});
Mousetrap.bind('r r', function() { reloadCurrentTab() });
Mousetrap.bind("c c", function() { closeCurrentTab() });
Mousetrap.bind("s s", function() { openSettings() });
Mousetrap.bind("h h", function() { openHomepage() });
Mousetrap.bind("f f", function() { toggleFullScreen() });
Mousetrap.bind("d d", function() { toggleDebug() });
Mousetrap.bind("esc", function () {
    $('.splash-screen').removeClass('in').addClass('hidden')
});
Mousetrap.bind('ctrl+shift+up', function(e) {
    var getCurrentTab = $('.allTabsList a.active').parent();
    var previousTab = getCurrentTab.prev().children();
    previousTab.trigger("click");
    parent.focus();
    return false;
});
Mousetrap.bind('ctrl+shift+down', function(e) {
    var getCurrentTab = $('.allTabsList a.active').parent();
    var nextTab = getCurrentTab.next().children();
    nextTab.trigger("click");
    return false;
});
$(document).on('change', "#new-tab-form-chooseImage", function (e) {
    var newIcon = $('#new-tab-form-chooseImage').val();
    if(newIcon !== 'Select or type Icon'){
        $('#new-tab-form-inputImageNew').val(newIcon);
    }
});
$(document).on('change', "#edit-tab-form-chooseImage", function (e) {
    var newIcon = $('#edit-tab-form-chooseImage').val();
    if(newIcon !== 'Select or type Icon'){
        $('#edit-tab-form-inputImage').val(newIcon);
    }
});
$(document).on('change', "#new-tab-form-chooseIcon", function (e) {
    var newIcon = $('#new-tab-form-chooseIcon').val();
    if(newIcon !== 'Select or type Icon'){
        $('#new-tab-form-inputImageNew').val(newIcon);
    }
});
$(document).on('change', "#edit-tab-form-chooseIcon", function (e) {
    var newIcon = $('#edit-tab-form-chooseIcon').val();
    if(newIcon !== 'Select or type Icon'){
        $('#edit-tab-form-inputImage').val(newIcon);
    }
});
$(document).on('change', "#choose-calender-filter, #choose-calender-filter-status", function (e) {
    filter = $('#choose-calender-filter').val();
    filterDownload = $('#choose-calender-filter-status').val();
    $('#calendar-filter-modal').modal('hide');
    console.log("Calendar Filter: "+filter);
    console.log("Calendar Filter: "+filterDownload);
    $('#calendar').fullCalendar('rerenderEvents');
    new SimpleBar($('.fc-scroller')[0]);
});
$('#debug-input').keyup(function(e){
    if(e.keyCode == 13) {
        orgDebug();
    }
});
// settings menu open if not open
$(document).on('click', ".sticon", function(){
    var target = $(this).attr('href');
    var menu = $(target).find('.customtab2 > li');
    if(menu.length !== 0){
        var isActive = false;
        $(menu).each(function (index, value) {
            var hasClass = $(this).hasClass('active');
            if(hasClass){
                isActive = true;
            }
        });
        if(isActive == false){
            let el = $(menu).find('a').first();
            $(el).trigger('click');
        }
    }
});
// open help modal
$(document).on('click', ".help-modal", function(){
    var type = $(this).attr('data-modal');
    var title = '';
    var body = '';
    //clear modal first
    $('#help-modal-title').html('');
    $('#help-modal-body').html('');
    //alter info
    switch (type) {
        case 'tabs':
            title = 'Tab Help';
            var items = [
                {title:"Name", body:"The text that will be displayed for that certain tab"},
                {title:"Category", body:"Each Tab is assigned a Category, the default is unsorted.  You may create new categories on the Category settings tab"},
                {title:"Group", body:"The lowest Group that will have access to this tab"},
                {title:"Type", body:"Internal is for Organizr pages<br/>iFrame is for all others<br/>New Window is for items to open in a new window"},
                {title:"Default", body:"You can choose one tab to be the first opened tab on page load"},
                {title:"Active", body:"Either mark a tab as active or inactive"},
                {title:"Splash", body:"Toggle this to add the tab to the Splash Page on page load"},
                {title:"Ping", body:"Enable Organizr to ping the status of the local URL of this tab"},
                {title:"Preload", body:"Toggle this tab to loaded in the background on page load"},
            ];
            body = buildAccordion(items);
            break;
        default:
            return null;
        
    }
    $('#help-modal-title').html(title);
    $('#help-modal-body').html(body);
    $('.help-modal-lg').modal('show');
});
$(document).on('click', ".close-popup", function(){
    $.magnificPopup.close();
});
// open help modal
$(document).on('click', ".copyDebug", function(){
    copyDebug();
    $('#internal-clipboard').trigger('click');
});
// AccountDN change
$(document).on("keyup", "#authBackendHostPrefix-input, #authBackendHostSuffix-input", function () {
    var newDN = $('#authBackendHostPrefix-input').val() + 'TestAcct' + $('#authBackendHostSuffix-input').val();
    $('#accountDN').html(newDN);
});

// homepage healthchecks
$(document).on('click', ".good-health-checks", function(){
    homepageHealthChecks();
});
$(document).on('click', ".showMoreHealth", function(){
   var id = $(this).attr('data-id');
    $('.showMoreHealthDiv-'+id).toggleClass('d-none');
    $(this).find('.card-body').toggleClass('healthPosition');
});