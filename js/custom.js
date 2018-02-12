/*jslint browser: true*/
/*global $, jQuery, alert*/
var idleTime = 0;
var hasCookie = false;
$(document).ajaxComplete(function () {
    pageLoad();
});
$(document).ready(function () {
    pageLoad();
    var clipboard = new Clipboard('.clipboard');
    clipboard.on('success', function(e) {
        message('Clipboard',e.text,'bottom-right','#FFF','info','5000');
        e.clearSelection();
    });
	"use strict";
    var body = $("body");
    $(function () {
        //$("#preloader").fadeOut();
        var set = function () {
            var topOffset = 60,
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
});
function pageLoad(){
	"use strict";
    //Start Organizr
    $(function () {
        $('#side-menu').metisMenu();
        if($('#preloader:visible').length == 1){
            $("#preloader").fadeOut();
        }
        lazyload();
    });
    $(".colorpicker").asColorPicker({
        mode: 'complex',
        color: {
            format: false,
            alphaConvert: false
        }
    });
    $(function () {
        $(".metadata-actors").owlCarousel({
          autoplay: true,
          slideSpeed : 300,
          paginationSpeed : 400,
          margin:40,
          nav:false,
          autoplay:true,
          dots:false,
          responsive:{
              0:{
                  items:2
              },
              500:{
                  items:3
              },
              650:{
                  items:4
              },
              800:{
                  items:5
              },
              950:{
                  items:6
              },
              992:{
                  items:4
              },
              1250:{
                  items:5
              },
              1400:{
                  items:6
              },
              1550:{
                  items:7
              },
              1700:{
                  items:8
              },
              1850:{
                  items:9
              }
          },
          //singleItem:true

          // "singleItem:true" is a shortcut for:
           items : 1,
          // itemsDesktop : false,
          // itemsDesktopSmall : false,
          // itemsTablet: false,
          // itemsMobile : false

      });
        $('.recent-items').owlCarousel({
    	    margin:40,
    	    nav:false,
    		autoplay:false,
            dots:false,
    	    responsive:{
    	        0:{
    	            items:2
    	        },
    	        500:{
    	            items:3
    	        },
    	        650:{
    	            items:4
    	        },
    	        800:{
    	            items:5
    	        },
    	        950:{
    	            items:6
    	        },
    	        1100:{
    	            items:7
    	        },
    	        1250:{
    	            items:8
    	        },
    	        1400:{
    	            items:9
    	        },
    	        1550:{
    	            items:10
    	        },
    	        1700:{
    	            items:11
    	        },
    	        1850:{
    	            items:12
    	        },
    	        2000:{
    	            items:13
    	        },
    	        2150:{
    	            items:14
    	        },
    	        2300:{
    	            items:15
    	        },
    	        2450:{
    	            items:16
    	        }
    	    }
    	})
    });


    /* ===== Theme Settings ===== */





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

    /* ===== Tooltip Initialization ===== */

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
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

    /* ===== Task Initialization ===== */

    $(".list-task li label").on("click", function () {
        $(this).toggleClass("task-done");
    });
    $(".settings_box a").on("click", function () {
        $("ul.theme_color").toggleClass("theme_block");
    });

    /* ===== Collepsible Toggle ===== */

    $(".collapseble").on("click", function () {
        $(".collapseblebox").fadeToggle(350);
    });

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

    $(".navbar-toggle").on("click", function () {
        $(".navbar-toggle i").toggleClass("ti-menu").addClass("ti-close");
    });

    /* magnific stuff */
    $('.image-popup-vertical-fit').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        mainClass: 'mfp-img-mobile',
        image: {
            verticalFit: true
        }

    });

    $('.image-popup-fit-width').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        image: {
            verticalFit: false
        }
    });

    $('.image-popup-no-margins').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        closeBtnInside: false,
        fixedContentPos: true,
        mainClass: 'mfp-no-margins mfp-with-zoom', // class to remove default margin from left and right side
        image: {
            verticalFit: true
        },
        zoom: {
            enabled: true,
            duration: 300 // don't foget to change the duration also in CSS
        }
    });

    $('.popup-gallery').magnificPopup({
        delegate: 'a',
        type: 'image',
        tLoading: 'Loading image #%curr%...',
        mainClass: 'mfp-img-mobile',
        gallery: {
            enabled: true,
            navigateByImgClick: true,
            preload: [0,1] // Will preload 0 - before current, and 1 after the current image
        },
        image: {
            tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
            titleSrc: function(item) {
                return item.el.attr('title') + '<small>by Marsel Van Oosten</small>';
            }
        }
    });

    $('.zoom-gallery').magnificPopup({
        delegate: 'a',
        type: 'image',
        closeOnContentClick: false,
        closeBtnInside: false,
        mainClass: 'mfp-with-zoom mfp-img-mobile',
        image: {
            verticalFit: true,
            titleSrc: function(item) {
                return item.el.attr('title') + ' &middot; <a class="image-source-link" href="'+item.el.attr('data-source')+'" target="_blank">image source</a>';
            }
        },
        gallery: {
            enabled: true
        },
        zoom: {
            enabled: true,
            duration: 300, // don't foget to change the duration also in CSS
            opener: function(element) {
                return element.find('img');
            }
        }

    });

    $('#image-popups').magnificPopup({
          delegate: 'a',
          type: 'image',
          removalDelay: 500, //delay removal by X to allow out-animation
          callbacks: {
            beforeOpen: function() {
              // just a hack that adds mfp-anim class to markup
               this.st.image.markup = this.st.image.markup.replace('mfp-figure', 'mfp-figure mfp-with-anim');
               this.st.mainClass = this.st.el.attr('data-effect');
            }
          },
          closeOnContentClick: true,
          midClick: true // allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source.
        });

    $('.popup-youtube, .popup-vimeo, .popup-gmaps').magnificPopup({

        disableOn: 700,
        type: 'iframe',
        mainClass: 'mfp-fade',
        removalDelay: 160,
        preloader: false,

        fixedContentPos: false
    });
    $('.popup-with-form').magnificPopup({
        type: 'inline',
        preloader: true,
        removalDelay: 500,
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
            }
        }
    });
    // Inline popups
    $('.inline-popups').magnificPopup({
      removalDelay: 500, //delay removal by X to allow out-animation
      closeOnBgClick: true,
      //closeOnContentClick: true,
      callbacks: {
        beforeOpen: function() {
           this.st.mainClass = this.st.el.attr('data-effect');
        }
      },
      midClick: true // allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source.
    });
    $('.simple-ajax-popup-align-top').magnificPopup({
        type: 'ajax',
        alignTop: true,
        overflowY: 'scroll' // as we know that popup content is tall we set scroll overflow by default to avoid jump
    });

    $('.simple-ajax-popup').magnificPopup({
        type: 'ajax'
    });
}

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
$(document).on("click", ".login-button", function(e) {
    e.preventDefault;
    $('div.login-box').block({
        message: '<h5><img width="20" src="plugins/images/busy.gif" /> Just a moment...</h4>',
        css: {
            color: '#fff',
            border: '1px solid #2cabe3',
            backgroundColor: '#2cabe3'
        }
    });
    var post = $( '#loginform' ).serializeArray();
    organizrAPI('POST','api/?v1/login',post).success(function(data) {
        var html = JSON.parse(data);
        if(html.data == true){
            location.reload();
        }else if(html.data == 'mismatch'){
            $('div.login-box').unblock({});
            $.toast().reset('all');
            message('Login Error',' Wrong username/email/password combo','bottom-right','#FFF','warning','10000');
            console.error('Organizr Function: Login failed - wrong username/email/password');
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
            $.toast().reset('all');
            message('Registration Error',' Wrong Registration Password','bottom-right','#FFF','warning','10000');
            console.error('Organizr Function: Registration failed - Wrong Registration Password');
        }else if(html.data == 'username taken'){
            $.toast().reset('all');
            message('Registration Error',' Registration Error - Username/Email Taken','bottom-right','#FFF','warning','10000');
            console.error('Organizr Function: Registration Failed - Username/Email Taken');
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: Login Failed");
    });
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
        message('New Group Error',' Could not get Group ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.groupName == 'undefined' || post.groupName == '') {
        message('New Group Error',' Please set a Group Name','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.groupImage == 'undefined' || post.groupImage == '') {
        message('New Group Error',' Please set a Group Image','bottom-right','#FFF','warning','5000');
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
    //Create POST Array
    var post = {
        action:'deleteUserGroup',
        api:'api/?v1/settings/user/manage/groups',
        id:$(this).parent().parent().attr("data-id"),
        groupID:$(this).parent().parent().attr("data-group-id"),
        groupName:$(this).parent().parent().attr("data-group"),
        messageTitle:'',
        messageBody:'Deleted User Group '+$(this).parent().parent().attr("data-group"),
        error:'Organizr Function: User Group API Connection Failed'
    };
    var callbacks = $.Callbacks();
    callbacks.add( buildGroupManagement );
    settingsAPI(post,callbacks);
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
        message('New Group Error',' Could not get next Group ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.newGroupName == 'undefined' || post.newGroupName == '') {
        message('New Group Error',' Please set a Group Name','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.newGroupImage == 'undefined' || post.newGroupImage == '') {
        message('New Group Error',' Please set a Group Image','bottom-right','#FFF','warning','5000');
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
        message('New User Error',' Please set a Username','bottom-right','#FFF','error','5000');
    }
    if (typeof post.email == 'undefined' || post.email == '') {
        message('New User Error',' Please set an Email','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.password == 'undefined' || post.password == '') {
        message('New User Error',' Please set a Password','bottom-right','#FFF','warning','5000');
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
        message('Edit User Error',' Could not get User ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.username == 'undefined' || post.username == '') {
        message('Edit User Error',' Please set a Username','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.email == 'undefined' || post.email == '') {
        message('Edit User Error',' Please set a User Email','bottom-right','#FFF','warning','5000');
    }
    if (post.password !== '' && post.password !== $('#edit-user-form [name=password2]').val()){
        message('Edit User Error',' Passwords do not match!','bottom-right','#FFF','warning','5000');
    }
    console.log(post);
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
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: window.lang.translate('Yes'),
        cancelButtonText: window.lang.translate('No'),
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm){
        if (isConfirm) {
            //Create POST Array
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
        title: window.lang.translate('Delete ')+user.parent().parent().attr("data-name")+'?',
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: window.lang.translate('Yes'),
        cancelButtonText: window.lang.translate('No'),
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm){
        if (isConfirm) {
            //Create POST Array
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
//EDIT TAB GET ID
$(document).on("click", ".editTabButton", function () {
    $('#edit-tab-form [name=tabName]').val($(this).parent().parent().attr("data-name"));
    $('#edit-tab-form [name=tabURL]').val($(this).parent().parent().attr("data-url"));
    $('#edit-tab-form [name=tabImage]').val($(this).parent().parent().attr("data-image"));
    $('#edit-tab-form [name=id]').val($(this).parent().parent().attr("data-id"));
    if( $(this).parent().parent().attr("data-url").indexOf('/?v') > 0){
        $('#edit-tab-form [name=tabURL]').prop('disabled', 'true');
    }else{
        $('#edit-tab-form [name=tabURL]').prop('disabled', null);
    }
});
//EDIT TAB
$(document).on("click", ".editTab", function () {
    //Create POST Array
    var post = {
        action:'editTab',
        api:'api/?v1/settings/tab/editor/tabs',
        id:$('#edit-tab-form [name=id]').val(),
        tabName:$('#edit-tab-form [name=tabName]').val(),
        tabImage:$('#edit-tab-form [name=tabImage]').val(),
        tabURL:$('#edit-tab-form [name=tabURL]').val(),
        messageTitle:'',
        messageBody:'Edited Tab '+$('#edit-tab-form [name=tabName]').val(),
        error:'Organizr Function: Tab Editor API Connection Failed'
    };
    if (typeof post.id == 'undefined' || post.id == '') {
        message('Edit Tab Error',' Could not get Tab ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.tabName == 'undefined' || post.tabName == '') {
        message('Edit Tab Error',' Please set a Tab Name','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.tabImage == 'undefined' || post.tabImage == '') {
        message('Edit Tab Error',' Please set a Tab Image','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.tabURL == 'undefined' || post.tabURL == '') {
        message('Edit Tab Error',' Please set a Tab URL','bottom-right','#FFF','warning','5000');
    }
    if(post.id !== '' && post.tabName !== '' && post.tabImage !== '' && post.tabURL !== '' ){
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
        tabGroupID:1,
        tabEnabled:0,
        tabDefault:0,
        tabType:1,
        messageTitle:'',
        messageBody:'Created Tab '+$('#new-tab-form [name=tabName]').val(),
        error:'Organizr Function: Tab API Connection Failed'
    };
    if (typeof post.tabOrder == 'undefined' || post.tabOrder == '') {
        message('New Tab Error',' Could not get next Group ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.tabName == 'undefined' || post.tabName == '') {
        message('New Tab Error',' Please set a Tab Name','bottom-right','#FFF','error','5000');
    }
    if (typeof post.tabURL == 'undefined' || post.tabURL == '') {
        message('New Tab Error',' Please set a Tab URL','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.tabImage == 'undefined' || post.tabImage == '') {
        message('New Tab Error',' Please set a Tab Image','bottom-right','#FFF','warning','5000');
    }
    if(post.tabOrder !== '' && post.tabName !== '' && post.tabURL !== '' && post.tabImage !== '' ){
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
        message('New Category Error',' Could not get next Category ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.categoryName == 'undefined' || post.categoryName == '') {
        message('New Category Error',' Please set a Category Name','bottom-right','#FFF','error','5000');
    }
    if (typeof post.categoryOrder == 'undefined' || post.categoryOrder == '') {
        message('New Category Error',' Could not get Category Order','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.categoryImage == 'undefined' || post.categoryImage == '') {
        message('New Category Error',' Please set a Category Image','bottom-right','#FFF','warning','5000');
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
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: window.lang.translate('Yes'),
        cancelButtonText: window.lang.translate('No'),
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm){
        if (isConfirm) {
            //Create POST Array
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
    console.log(post)
    if (typeof post.id == 'undefined' || post.id == '') {
        message('Edit Tab Error',' Could not get Tab ID','bottom-right','#FFF','error','5000');
    }
    if (typeof post.name == 'undefined' || post.name == '') {
        message('Edit Tab Error',' Please set a Tab Name','bottom-right','#FFF','warning','5000');
    }
    if (typeof post.image == 'undefined' || post.image == '') {
        message('Edit Tab Error',' Please set a Tab Image','bottom-right','#FFF','warning','5000');
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
// CHANGE CUSTOMIZE Options
$(document).on('change asColorPicker::clear asColorPicker::close', '#customize-appearance-form :input', function(e) {
	$(this).attr('data-changed', true);
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val();
    }
	var post = {
        action:'editCustomizeAppearance',
        api:'api/?v1/settings/customize/appearance',
        name:$(this).attr("name"),
        value:value,
        messageTitle:'',
        messageBody:'Updated Value for '+$(this).parent().parent().find('label').text(),
        error:'Organizr Function: API Connection Failed'
    };
    console.log(post);
    $('#customize-appearance-reload').removeClass('hidden');
	var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
    settingsAPI(post,callbacks);
    if($(this).hasClass('themeChanger')){
        changeTheme(value);
    }
	if($(this).hasClass('styleChanger')){
        changeStyle(value);
    }

});
//DELETE IMAGE
$(document).on("click", ".deleteImage", function () {
    var image = $(this);
    swal({
        title: window.lang.translate('Delete ')+image.attr("data-image-name")+'?',
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: window.lang.translate('Yes'),
        cancelButtonText: window.lang.translate('No'),
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm){
        if (isConfirm) {
            //Create POST Array
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
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: window.lang.translate('Yes'),
        cancelButtonText: window.lang.translate('No'),
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm){
        if (isConfirm) {
            //Create POST Array
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
// SSO Option change
$(document).on('change asColorPicker::close', '#sso-form :input', function(e) {
    var input = $(this);
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val();
    }
	var post = {
        api:'api/?v1/update/config',
        name:$(this).attr("name"),
        type:$(this).attr("data-type"),
        value:value,
        messageTitle:'',
        messageBody:'Updated Value for '+$(this).parent().parent().find('label').text(),
        error:'Organizr Function: API Connection Failed'
    };
	var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
    settingsAPI(post,callbacks);
    //disable button then renable
    $('#sso-form :input').prop('disabled', 'true');
    setTimeout(
        function(){
            $('#sso-form :input').prop('disabled', null);
            input.emulateTab();
        },
        2000
    );
});
// MAIN SETTINGS PAGE
$(document).on('change asColorPicker::close', '#settings-main-form :input', function(e) {
    var input = $(this);
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val();
    }
	var post = {
        api:'api/?v1/update/config',
        name:$(this).attr("name"),
        type:$(this).attr("data-type"),
        value:value,
        messageTitle:'',
        messageBody:'Updated Value for '+$(this).parent().parent().find('label').text(),
        error:'Organizr Function: API Connection Failed'
    };
	var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
    settingsAPI(post,callbacks);
    //disable button then renable
    $('#settings-main-form :input').prop('disabled', 'true');
    setTimeout(
        function(){
            $('#settings-main-form :input').prop('disabled', null);
            input.emulateTab();
        },
        2000
    );
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
                            machines += '<option value="'+machine+'">'+name+'</option>';
                        }
                    })
                    var listing = `<select class="form-control" id="ssoPlexMachineSelector" data-type="select">`+machines+`</select>`;
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
                            machines += '<option value="'+machine+'">'+name+'</option>';
                        }
                    })
                    var listing = `<select class="form-control" id="authPlexMachineSelector" data-type="select">`+machines+`</select>`;
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
        message('Path Error',' Please enter a path for DB','bottom-right','#FFF','warning','10000');
    }else{
        organizrAPI('POST','api/?v1/wizard_path',{path:path}).success(function(data) {
            var html = JSON.parse(data);
            console.log(html)
            if(html.data == true){
                message('Path',' Path is good to go','bottom-right','#FFF','success','10000');
            }else{
                message('Path Error',' Path is not writable','bottom-right','#FFF','warning','10000');
            }
        }).fail(function(xhr) {
            console.error("Organizr Function: Connection Failed");
        });
    }
});
// Save Homepage Form
$(document).on('change asColorPicker::close', '.homepageForm :input', function(e) {
    var input = $(this);
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val();
    }
	var post = {
        api:'api/?v1/update/config',
        name:$(this).attr("name"),
        type:$(this).attr("data-type"),
        value:value,
        messageTitle:'',
        messageBody:'Updated Value for '+$(this).parent().parent().find('label').text(),
        error:'Organizr Function: API Connection Failed'
    };
	var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
    settingsAPI(post,callbacks);
    //disable button then renable
    $('.homepageForm :input').prop('disabled', 'true');
    setTimeout(
        function(){
            $('.homepageForm :input').prop('disabled', null);
            input.emulateTab();
        },
        2000
    );

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
    console.log(filter);
    if(filter == 'all'){
        $('.plex-recent').find('.recent-item').parent().removeClass('hidden');
    }else{
        $('.plex-recent').find('.recent-item').parent().removeClass('hidden');
        $('.plex-recent').find('.recent-item:not(.'+ filter + ')').parent().addClass('hidden');
    }

});
// refresh cache image
$(document).on("click", ".refreshImage", function(e) {
	message('',' Refreshing Image...','bottom-right','#FFF','success','1000');
	e.preventDefault;
	var orginalElement = $(this).parent().parent().parent().parent().find('.imageSource');
    console.log(orginalElement)
	var original = $(this).attr('data-image');
	orginalElement.attr('src', original);
	console.log('replaced image with : '+original);
	setTimeout(function(){
        message('Image Refreshed ',' Clear Cache Please','bottom-right','#FFF','success','3000');
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
});
// metadata start
$(document).on("click", ".metadata-get", function(e) {
    $('.metadata-info').html('');
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
        console.log(response);
		$('.'+uid+'-metadata-info').html(buildMetadata(response.data, source));
        $('.'+uid).trigger('click')
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	ajaxloader();

});
// sad play/resume
$(document).on("click", ".downloader", function(e) {
    var action = $(this).attr('data-action');
    var source = $(this).attr('data-source');
    var target = $(this).attr('data-target');
    console.log(action);
    console.log(source);
    console.log(target);
    ajaxloader(".content-wrap","in");
	organizrAPI('POST','api/?v1/downloader',{action:action, source:source, target:target}).success(function(data) {
		var response = JSON.parse(data);
        console.log(response);
		homepageDownloader(source);
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	ajaxloader();

});
// purge log
$(document).on("click", ".purgeLog", function () {
var log = $('.swapLog.active').attr('data-name');
alert('This action is not set yet - but this would have purged the '+log+' log');
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
