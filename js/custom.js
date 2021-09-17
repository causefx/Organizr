/*jslint browser: true*/
/*global $, jQuery, alert*/
var idleTime = 0;
var hasCookie = false;
var loginAttempts = 0;
$(document).ajaxComplete(function () {
    pageLoad();
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

            if (width < 768) {
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
	$('#page-wrapper').overlayScrollbars({ scrollbars : { autoHide: "move"}});
	$('.default-scroller').overlayScrollbars({ scrollbars : { autoHide: "scroll"}});
	$('.nav-bar-rtl').overlayScrollbars({ scrollbars : { autoHide: "leave"}});
	$('.inbox-center').overlayScrollbars({ scrollbars : { autoHide: "leave"}});
	$('.mailbox').overlayScrollbars({ scrollbars : { autoHide: "leave"}});
	$('.fc-scroller').overlayScrollbars({ scrollbars : { autoHide: "leave"}});
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
        $('[data-toggle="popover"]').popover({trigger: "hover",});
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
    organizrAPI2('GET','api/v2/homepage/'+server+'/search/' + query).success(function(data) {
	    try {
		    let response = data.response;
		    $('.mediaSearch-div').html(buildMediaResults(response.data,server,query));
		    if(bowser.mobile !== true){
			    $('.resultBox-inside').slimScroll({
				    height: '100%',
				    position: 'right',
				    size: "5px",
				    color: '#dcdcdc'
			    });
			    //$('.resultBox-inside').overlayScrollbars({ scrollbars : { autoHide: "leave"}});
		    }
	    }catch(e) {
		    organizrCatchError(e,data);
	    }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    })
}
$(document).on("click", ".login-button", function(e) {
    e.preventDefault;
    var oAuthEntered = $('#oAuth-Input').val();
    var usernameEntered = $('#login-username-Input').val();
    if(oAuthEntered == '' && usernameEntered == ''){
        message('Login Error', ' You need to enter a Username', activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
        $('#login-username-Input').focus();
        return false;
    }
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
        var post = $('#loginform').serializeToJSON();
        organizrAPI2('POST', 'api/v2/login', post).success(function (data) {
            local('set','message','Welcome|Login Successful|success');
	        local('r','loggingIn');
	        location.reload();
        }).fail(function (xhr) {
            $('div.login-box').unblock({});
            switch (xhr.status){
	            case 401:
					if(xhr.responseJSON.response.message == '2FA Code incorrect'){
						$('div.login-box').unblock({});
						$('#tfa-div').removeClass('hidden');
						$('#loginform [name=tfaCode]').focus();
					}
	            	break;
	            case 403:
		            $('div.login-box').block({
			            message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
			            css: {
				            color: '#fff',
				            border: '1px solid #e91e63',
				            backgroundColor: '#f44336'
			            }
		            });
		            setTimeout(function(){ local('r','loggingIn'); location.reload() }, 10000);
	            	break;
	            case 422:
		            $('div.login-box').unblock({});
		            $('#tfa-div').removeClass('hidden');
		            $('#loginform [name=tfaCode]').focus();
	            	break;
	            default:
		            message('Login Error', 'API Connection Failed', activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
		            console.error("Organizr Function: API Connection Failed");
            }
	        message('Login Error', xhr.responseJSON.response.message, activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
	        console.error("Organizr Function: " + xhr.responseJSON.response.message);
            local('r','loggingIn');
        });
    }
});
$(document).on("click", ".unlockButton", function(e) {
    e.preventDefault;
    var post = {
        password:$('#unlockPassword').val()
    };
    if(post == ''){
	    message('Password cannot be blank', '', activeInfo.settings.notifications.position, '#FFF', 'error', '5000');
    	return false;
    }
    organizrAPI2('POST','api/v2/users/unlock',post).success(function(data) {
        let html = data.response;
        location.reload();
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    });
});
$(document).on("click", ".register-button", function(e) {
    e.preventDefault;
    var post = $( '#registerForm' ).serializeToJSON();
    console.log(post)
    organizrAPI2('POST','api/v2/users/register',post).success(function(data) {
        let html = data.response;
		location.reload();
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    });
});
$(document).on("click", ".reset-button", function(e) {
    e.preventDefault;
    var email = $('#recover-input').val();
    if(email !== ''){
		var post = {
	        email:email
        };
	    message('Submitting request...','',activeInfo.settings.notifications.position,'#FFF','info','10000');
        organizrAPI2('POST','api/v2/users/recover',post).success(function(data) {
            var html = data.response;
            message('Recover Password',html.message,activeInfo.settings.notifications.position,'#FFF','success','10000');
            $('#leave-recover').trigger('click');
        }).fail(function(xhr) {
	        OrganizrApiError(xhr, 'API Error');
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
    $('#edit-group-form [name=group]').val($(this).parent().parent().attr("data-group"));
    $('#edit-group-form [name=id]').val($(this).parent().parent().attr("data-id"));
    $('#edit-group-form [name=image]').val($(this).parent().parent().attr("data-image"));
});
//EDIT GROUP
$(document).on("click", ".editGroup", function () {
	var info = $('#edit-group-form').serializeToJSON();
	var callbacks = $.Callbacks();
	if (typeof info.id == 'undefined' || info.id == '') {
		message('Edit Tab Error',' Could not get ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (typeof info.group == 'undefined' || info.group == '') {
		message('Edit Tab Error',' Please set a Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof info.image == 'undefined' || info.image == '') {
		message('Edit Tab Error',' Please set an Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	callbacks.add( buildGroupManagement );
	organizrAPI2('PUT','api/v2/groups/' + info.id,info,true).success(function(data) {
		try {
			var response = data.response;
			$('.groupIconImageList').val(null).trigger('change');
			$('.groupIconIconList').val(null).trigger('change');
		}catch(e) {
			organizrCatchError(e,data);
		}
		message(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		clearForm('#edit-group-form');
		$.magnificPopup.close();
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
});
//CHANGE DEFAULT GROUP
$(document).on("click", ".changeDefaultGroup", function () {
	var id = $(this).parent().parent().attr("data-id");
	var callbacks = $.Callbacks();
	callbacks.add( buildGroupManagement );
	organizrAPI2('PUT','api/v2/groups/' + id, {"default":1},true).success(function(data) {
		try {
			var response = data.response;
			message(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
});
//DELETE GROUP
$(document).on("click", ".deleteUserGroup", function () {
	var el = $(this);
    swal({
        title: window.lang.translate('Delete ')+el.parent().parent().attr("data-group")+'?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
	        var id = el.parent().parent().attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildGroupManagement );
	        organizrAPI2('DELETE','api/v2/groups/' + id, null,true).success(function(data) {
		        try {
			        message('Group Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
			        if(callbacks){ callbacks.fire(); }
		        }catch(e) {
			        organizrCatchError(e,data);
		        }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'API Error');
	        });
        }
    });
});
//ADD GROUP
$(document).on("click", ".addNewGroup", function () {

	var info = $('#new-group-form').serializeToJSON();
	console.log(info);
	if (typeof info.group == 'undefined' || info.group == '') {
		message('New Group Error',' Please set a Group Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof info.image == 'undefined' || info.image == '') {
		message('New Group Error',' Please set a Group Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	var callbacks = $.Callbacks();
	callbacks.add( buildGroupManagement );
	organizrAPI2('POST','api/v2/groups',info,true).success(function(data) {
		try {
			var response = data.response;
			$('.groupIconImageList').val(null).trigger('change');
			$('.groupIconIconList').val(null).trigger('change');
			message(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#new-group-form');
			$.magnificPopup.close();
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
});
// ADD USER
$(document).on("click", ".addNewUser", function () {
	var userInfo = $('#new-user-form').serializeToJSON();
	$.each(userInfo, function(i,v) {
		if(v == ''){
			delete userInfo[i];
		}
	})
	console.log(userInfo)
	var callbacks = $.Callbacks();
	callbacks.add( buildUserManagement );
	organizrAPI2('POST','api/v2/users', userInfo,true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('User Created',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		clearForm('#new-user-form');
		$('#jsGrid-Users').jsGrid('render');
		$.magnificPopup.close();
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
});
//EDIT GROUP GET ID
$(document).on("click", ".editUserButton", function () {
    $('#edit-user-form [name=username]').val($(this).parent().parent().attr("data-username"));
    $('#edit-user-form [name=id]').val($(this).parent().parent().attr("data-id"));
    $('#edit-user-form [name=email]').val($(this).parent().parent().attr("data-email"));
});
//EDIT GROUP
$(document).on("click", ".editUserAdmin", function () {
	var userInfo = $('#edit-user-form').serializeToJSON();
	$.each(userInfo, function(i,v) {
		if(v == ''){
			delete userInfo[i];
		}
	})
	if (typeof userInfo.id == 'undefined' || userInfo.id == '') {
		message('Edit User Error',' Could not get User ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (userInfo.password !== '' && userInfo.password !== userInfo.password2){
		message('Edit User Error',' Passwords do not match!',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	var callbacks = $.Callbacks();
	callbacks.add( buildUserManagement );
	organizrAPI2('PUT','api/v2/users/' + userInfo.id, userInfo,true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('User Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		clearForm('#edit-user-form');
		$.magnificPopup.close();
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
});
// CHANGE USER GROUP
$(document).on("change", ".userGroupSelect", function () {

	var id = $(this).parent().parent().attr("data-id");
	var groupId = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	callbacks.add( buildUserManagement );
	organizrAPI2('PUT','api/v2/users/' + id, {"group_id":groupId},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('User Group Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
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
	        var id = user.parent().parent().attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildUserManagement );
	        organizrAPI2('DELETE','api/v2/users/' + id, null,true).success(function(data) {
		        message('User Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'User Delete Error');
	        });
        }
    });
});
// CHANGE TAB GROUP
$(document).on("change", ".tabGroupSelect", function (event) {
	var id = $(this).parent().parent().attr("data-id");
	var groupID = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"group_id":groupID},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Group Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Group Error');
	});
});
// CHANGE TAB CATEGORY
$(document).on("change", ".tabCategorySelect", function () {
	var id = $(this).parent().parent().attr("data-id");
	var categoryID = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"category_id":categoryID},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Category Error');
	});
});
// CHANGE TAB TYPE
$(document).on("change", ".tabTypeSelect", function () {
	var id = $(this).parent().parent().attr("data-id");
	var type = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"type":type},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Type Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Type Error');
	});
});
// CHANGE ENABLED TAB
$(document).on("change", ".enabledSwitch", function () {
	var id = $(this).parent().parent().attr("data-id");
	var enabled = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"enabled":enabled},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Enable Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Enable Error');
	});
});
// CHANGE SPLASH TAB
$(document).on("change", ".splashSwitch", function () {
	var id = $(this).parent().parent().attr("data-id");
	var splash = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"splash":splash},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Splash Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Splash Error');
	});
});
// CHANGE SPLASH TAB
$(document).on("change", ".pingSwitch", function () {
	var id = $(this).parent().parent().attr("data-id");
	var ping = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"ping":ping},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Ping Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Ping Error');
	});
});
// CHANGE PRELOAD TAB
$(document).on("change", ".preloadSwitch", function () {
	var id = $(this).parent().parent().attr("data-id");
	var preload = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"preload":preload},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Preload Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Preload Error');
	});
});
// CHANGE DEFAULT TAB
$(document).on("change", ".defaultSwitch", function () {
	var id = $(this).parent().parent().parent().attr("data-id");
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/tabs/' + id, {"default":1},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Default Tab Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Default Tab Error');
	});
});
//DELETE TAB
$(document).on("click", ".deleteTab", function () {
    var tab = $(this);
    swal({
        title: window.lang.translate('Delete ') + tab.parent().parent().attr("data-name") + '?',
        icon: "warning",
        buttons: {
            cancel: window.lang.translate('No'),
            confirm: window.lang.translate('Yes'),
        },
        dangerMode: true,
        confirmButtonColor: "#DD6B55"
    }).then(function(willDelete) {
        if (willDelete) {
	        var id = tab.parent().parent().attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildTabEditor );
	        organizrAPI2('DELETE','api/v2/tabs/' + id, null,true).success(function(data) {
		        message('Tab Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'Tab Deleted Error');
	        });
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
//EDIT TAB
$(document).on("click", ".editTab", function () {
    var originalTabName = $('#originalTabName').html();
    var tabInfo = $('#edit-tab-form').serializeToJSON();
    if (typeof tabInfo.id == 'undefined' || tabInfo.id == '') {
        message('Edit Tab Error',' Could not get Tab ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
	    return false;
    }
    if (typeof tabInfo.name == 'undefined' || tabInfo.name == '') {
        message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	    return false;
    }
    if (typeof tabInfo.image == 'undefined' || tabInfo.image == '') {
        message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	    return false;
    }
    if ((typeof tabInfo.url == 'undefined' || tabInfo.url == '') && (typeof tabInfo.url_local == 'undefined' || tabInfo.url_local == '')) {
        message('Edit Tab Error',' Please set a Tab URL or Local URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	    return false;
    }
    if(checkIfTabNameExists(tabInfo.name) && originalTabName !== tabInfo.name){
        message('Edit Tab Error',' Tab name already used',activeInfo.settings.notifications.position,'#FFF','warning','5000');
        return false;
    }
    if(tabInfo.timeout_ms !== '' || typeof tabInfo.timeout_ms !== 'undefined'){
    	tabInfo.timeout_ms = convertMinutesToMs(tabInfo.timeout_ms);
    }
    if(tabInfo.id !== '' && tabInfo.tabName !== '' && tabInfo.tabImage !== ''){
	    var callbacks = $.Callbacks();
	    callbacks.add( buildTabEditor );
	    organizrAPI2('PUT','api/v2/tabs/' + tabInfo.id,tabInfo,true).success(function(data) {
		    try {
			    var response = data.response;
		    }catch(e) {
			    organizrCatchError(e,data);
		    }
		    message('Tab Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		    if(callbacks){ callbacks.fire(); }
		    clearForm('#edit-tab-form');
		    $.magnificPopup.close();
	    }).fail(function(xhr) {
		    OrganizrApiError(xhr, 'Tab Error');
	    });
    }
});
//ADD NEW TAB
$(document).on("click", ".addNewTab", function () {
	var tabInfo = $('#new-tab-form').serializeToJSON();
	tabInfo['order'] = parseInt($('#tabEditorTable').find('tr[data-order]').last().attr('data-order')) + 1;

	if (typeof tabInfo.name == 'undefined' || tabInfo.name == '') {
		message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.image == 'undefined' || tabInfo.image == '') {
		message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if ((typeof tabInfo.url == 'undefined' || tabInfo.url == '') && (typeof tabInfo.url_local == 'undefined' || tabInfo.url_local == '')) {
		message('Edit Tab Error',' Please set a Tab URL or Local URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(checkIfTabNameExists(tabInfo.name)){
		message('Edit Tab Error',' Tab name already used',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(tabInfo.timeout_ms !== '' || typeof tabInfo.timeout_ms !== 'undefined'){
		tabInfo.timeout_ms = convertMinutesToMs(tabInfo.timeout_ms);
	}
    if(tabInfo.order !== '' && tabInfo.name !== '' && (tabInfo.url !== '' || tabInfo.url_local !== '') && tabInfo.image !== '' ){
	    var callbacks = $.Callbacks();
	    callbacks.add( buildTabEditor );
	    organizrAPI2('POST','api/v2/tabs',tabInfo,true).success(function(data) {
		    try {
			    var response = data.response;
			    $('.tabIconImageList').val(null).trigger('change');
			    $('.tabIconIconList').val(null).trigger('change');
		    }catch(e) {
			    organizrCatchError(e,data);
		    }
		    message('Tab Created',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		    if(callbacks){ callbacks.fire(); }
		    clearForm('#new-tab-form');
		    $.magnificPopup.close();
	    }).fail(function(xhr) {
		    OrganizrApiError(xhr, 'Tab Error');
	    });
    }
});
//ADD NEW CATEGORY
$(document).on("click", ".addNewCategory", function () {
    var categoryInfo = $('#new-category-form').serializeToJSON();
	categoryInfo['order'] = parseInt($('#categoryEditorTable').find('tr[data-order]').last().attr('data-order')) + 1;

	if (typeof categoryInfo.category == 'undefined' || categoryInfo.category == '') {
		message('Edit Tab Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof categoryInfo.image == 'undefined' || categoryInfo.image == '') {
		message('Edit Tab Error',' Please set a Category Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(categoryInfo.category !== '' && categoryInfo.image !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildCategoryEditor );
		organizrAPI2('POST','api/v2/categories',categoryInfo,true).success(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Category Added',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#new-category-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Category Error');
		});
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
	        var id = category.parent().parent().attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildCategoryEditor );
	        organizrAPI2('DELETE','api/v2/categories/' + id, null,true).success(function(data) {
		        message('Category Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'Category Deleted Error');
	        });
        }
    });
});
//EDIT CATEGORY GET ID
$(document).on("click", ".editCategoryButton", function () {
    $('#edit-category-form [name=category]').val($(this).parent().parent().attr("data-name"));
    $('#edit-category-form [name=image]').val($(this).parent().parent().attr("data-image"));
    $('#edit-category-form [name=id]').val($(this).parent().parent().attr("data-id"));
});
//EDIT CATEGORY
$(document).on("click", ".editCategory", function () {
	var categoryInfo = $('#edit-category-form').serializeToJSON();
	if (typeof categoryInfo.id == 'undefined' || categoryInfo.id == '') {
		message('Edit Tab Error',' Could not get Category ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (typeof categoryInfo.category == 'undefined' || categoryInfo.category == '') {
		message('Edit Tab Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof categoryInfo.image == 'undefined' || categoryInfo.image == '') {
		message('Edit Tab Error',' Please set a Category Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(categoryInfo.id !== '' && categoryInfo.category !== '' && categoryInfo.image !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildCategoryEditor );
		organizrAPI2('PUT','api/v2/categories/' + categoryInfo.id,categoryInfo,true).success(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#edit-category-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Category Error');
		});
	}
});
//CHANGE DEFAULT CATEGORY
$(document).on("click", ".changeDefaultCategory", function () {
	var id = $(this).parent().parent().attr("data-id");
	var callbacks = $.Callbacks();
	callbacks.add( buildCategoryEditor );
	organizrAPI2('PUT','api/v2/categories/' + id, {"default":1},true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Default Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Default Cateogry Error');
	});
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

$(document).on('focusout', 'input.pick-a-color-custom-options', function(e) {
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
                api:'api/v2/image/' + image.attr("data-image-name-ext"),
                messageTitle:'',
                messageBody:window.lang.translate('Deleted Image')+': '+image.attr("data-image-name"),
                error:'Organizr Function: User API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildImageManagerView );
	        organizrAPI2('DELETE',post.api,'',true).success(function(data) {
		        try {
			        var response = data.response;
		        }catch(e) {
			        organizrCatchError(e,data);
		        }
		        message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'Image Error');
	        });
        }
    });
});
// RELOAD Page
$(document).on("click", ".reload", function () {
    location.reload();
});
// ENABLE PLUGIN
$(document).on('click', '.enablePlugin', function() {
	ajaxloader(".content-wrap","in");
	let pluginConfigValue = $(this).attr('data-config-name');
	let callbacks = $.Callbacks();
	callbacks.add( buildPlugins );
	callbacks.add( ajaxloader );
	let data = {};
	data[pluginConfigValue] = 'true';
	organizrAPI2('PUT','api/v2/config', data,true).success(function(data) {
		try {
			message('Plugin Enabled','',activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Plugin Error');
		ajaxloader();
	});
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
	        ajaxloader(".content-wrap","in");
			let pluginConfigValue = plugin.attr('data-config-name');
	        var callbacks = $.Callbacks();
	        callbacks.add( buildPlugins );
	        callbacks.add( ajaxloader );
	        var data = {};
	        data[pluginConfigValue] = 'false';
	        organizrAPI2('PUT','api/v2/config', data,true).success(function(data) {
		        try {
			        message('Plugin Disabled','',activeInfo.settings.notifications.position,"#FFF","success","5000");
			        if(callbacks){ callbacks.fire(); }
		        }catch(e) {
			        organizrCatchError(e,data);
		        }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'Plugin Error');
		        ajaxloader();
	        });
        }
    });
});
// AUTH BACKEND HIDE SHOW
$(document).on('change', '#authSelect, #authBackendSelect', function(e) {
    changeAuth();
});
$(document).on('change', '#plexMachineSelector', function(e) {
	let selector = $(this).attr('data-selector');
	$(selector).val($(this).val());
	$(selector).change();
	messageSingle('Machine ID selected','Please save...',activeInfo.settings.notifications.position,'#FFF','success','5000');
});
$(document).on("click", ".closeErrorPage", function () {
    $('.error-page').html('');
    $('.error-page').fadeOut();
});
// test Location
$(document).on("click", ".testPath", function () {
    var path = $("#form-dbPath").val();
    if (typeof path == 'undefined' || path == '') {
        message('Path Error',' Please enter a path for DB',activeInfo.settings.notifications.position,'#FFF','warning','10000');
    }else{
        organizrAPI2('POST','api/v2/test/path',{path:path}).success(function(data) {
            var html = data.response;
            message('Path',' Path is good to go',activeInfo.settings.notifications.position,'#FFF','success','10000');
        }).fail(function(xhr) {
	        OrganizrApiError(xhr, 'API Error');
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
        case 'jellyfin':
            var action = 'getEmbyMetadata';
            break;
        default:

    }
    ajaxloader(".content-wrap","in");
    organizrAPI2('POST','api/v2/homepage/'+source+'/metadata',{key:key}).success(function(data) {
        let response = data.response;
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
	    ajaxloader();
	    $("#preloader").fadeOut();
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
	    ajaxloader();
	    $("#preloader").fadeOut();
    });


});
// sab play/resume
$(document).on("click", ".downloader", function(e) {
	$(this).find('i').attr('class', 'fa fa-spin fa-circle-o-notch');
	let action = $(this).attr('data-action');
	let source = $(this).attr('data-source');
	let target = $(this).attr('data-target');
	let api = null;
	switch (source){
		case 'sabnzbd':
			api = 'api/v2/homepage/sabnzbd/queue/' + action;
			break;
		default:
			return false;
	}
	messageSingle('Sending command to downloader', '', activeInfo.settings.notifications.position, '#FFF', 'info', '2500');
    organizrAPI2('POST',api,{target:target}).success(function(data) {
        homepageDownloader(source);
	    messageSingle('Successful', '', activeInfo.settings.notifications.position, '#FFF', 'success', '2500');
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    });
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
        organizrAPI2('POST','api/v2/test/iframe',post).success(function(data) {
            let html = data.response;
            $('.tabTestMessage.alert-success').removeClass('hidden');
            $('.tabTestMessage.alert-danger').addClass('hidden');
	        setTimeout(function(){
		        $('.tabTestMessage.alert-success').addClass('hidden');
	        	}, 5000);
        }).fail(function(xhr) {
	        OrganizrApiError(xhr, 'API Error');
	        $('.tabTestMessage.alert-danger').removeClass('hidden');
	        $('.tabTestMessage.alert-success').addClass('hidden');
	        setTimeout(function(){

		        $('.tabTestMessage.alert-danger').addClass('hidden');
	        }, 5000);
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
	    message('Checking URL now...','',activeInfo.settings.notifications.position,'#FFF','info','5000');
        organizrAPI2('POST','api/v2/test/iframe',post).success(function(data) {
            let html = data.response;
            $('.tabEditTestMessage.alert-success').removeClass('hidden');
            $('.tabEditTestMessage.alert-danger').addClass('hidden');
	        setTimeout(function(){
		        $('.tabEditTestMessage.alert-success').addClass('hidden');
	        }, 5000);
        }).fail(function(xhr) {
	        OrganizrApiError(xhr, 'API Error');
	        $('.tabEditTestMessage.alert-danger').removeClass('hidden');
	        $('.tabEditTestMessage.alert-success').addClass('hidden');
	        setTimeout(function(){
		        $('.tabEditTestMessage.alert-danger').addClass('hidden');
	        }, 5000);
        });
    }
});
// new api key
$(document).on("click", ".newAPIKey", function () {
	let newCode = generateCode();
    $('#settings-main-form [name=organizrAPI]').val(newCode).change().parent().find('.clipboard').attr('data-clipboard-text',newCode);
});
// purge log
$(document).on("click", ".purgeLog", function () {
    var name = $('.swapLog.active').attr('data-name');
    if(name !== ''){
	    var post = {
		    api:'api/v2/log/' + name,
		    messageTitle:'',
		    messageBody:window.lang.translate('Deleted Log')+': '+name,
		    error:'Organizr Function: User API Connection Failed'
	    };
	    organizrAPI2('DELETE',post.api,'',true).success(function(data) {
		    loadSettingsPage2('api/v2/page/settings_settings_logs','#settings-settings-logs','Log Viewer');
		    try {
			    var response = data.response;
		    }catch(e) {
			    organizrCatchError(e,data);
		    }
		    message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		    var callbacks = $.Callbacks();
		    switch ($(this).attr('data-name')){
			    case 'loginLog':
				    loginLogTable.ajax.reload(null, false);
				    break;
			    case 'orgLog':
				    organizrLogTable.ajax.reload(null, false);
				    break;
			    default:
		    }
		    if(callbacks){ callbacks.fire(); }
	    }).fail(function(xhr) {
		    OrganizrApiError(xhr, 'API Error');
	    });
    }

});
$(document).on("click", ".delete-backup", function () {
	$('#settings-settings-backup').block({
		message: '<p style="margin:0;padding:8px;font-size:24px;" lang="en">Deleting Backup...</p>',
		css: {
			color: '#fff',
			border: '1px solid #5761a9',
			backgroundColor: '#707cd2'
		}
	});
	let filename = $(this).attr('data-file');
	if(filename !== ''){
		let post = {
			api:'api/v2/backup/' + filename,
			messageTitle:'',
			messageBody:window.lang.translate('Deleted Backup')+': '+filename,
			error:'Organizr Function: Backup API Connection Failed'
		};
		organizrAPI2('DELETE',post.api,'',true).success(function(data) {
			message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
			getOrganizrBackups();
			$('#settings-settings-backup').unblock();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'API Error');
			$('#settings-settings-backup').unblock();
		});
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
Mousetrap.bind("c c", function() { closeCurrentTab(event) });
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
$(document).on('change', "#choose-calender-filter, #choose-calender-filter-status", function (e) {
    filter = $('#choose-calender-filter').val();
    filterDownload = $('#choose-calender-filter-status').val();
    $('#calendar').fullCalendar('rerenderEvents');
	$('.fc-scroller').overlayScrollbars({ scrollbars : { autoHide: "leave"}});
});
$(document).on('keyup', "#debug-input", function(e  ){
	console.log(this);
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
$(document).on('click', ".showMoreHealth", function(){
   var id = $(this).attr('data-id');
    $('.showMoreHealthDiv-'+id).toggleClass('d-none');
    $(this).find('.card-body').toggleClass('healthPosition');
});
//IP INFO
$(document).on('click', ".ipInfo", function(){
	organizrAPI2('GET','api/v2/ip/'+$(this).text()).success(function(data) {
		try {
			let response = data.response.data;
			var region = (typeof response.region == 'undefined') ? ' N/A' : response.region;
			var ip = (typeof response.ip == 'undefined') ? ' N/A' : response.ip;
			var hostname = (typeof response.hostname == 'undefined') ? ' N/A' : response.hostname;
			var loc = (typeof response.loc == 'undefined') ? ' N/A' : response.loc;
			var org = (typeof response.org == 'undefined') ? ' N/A' : response.org;
			var city = (typeof response.city == 'undefined') ? ' N/A' : response.city;
			var country = (typeof response.country == 'undefined') ? ' N/A' : response.country;
			var phone = (typeof response.phone == 'undefined') ? ' N/A' : response.phone;
			var div = '<div class="row">' +
				'<div class="col-lg-12">' +
				'<div class="white-box">' +
				'<h3 class="box-title">'+ip+'</h3>' +
				'<div class="table-responsive inbox-center">' +
				'<table class="table">' +
				'<tbody>' +
				'<tr><td class="text-left">Hostname</td><td class="txt-oflo text-right">'+hostname+'</td></tr>' +
				'<tr><td class="text-left">Location</td><td class="txt-oflo text-right">'+loc+'</td></tr>' +
				'<tr><td class="text-left">Org</td><td class="txt-oflo text-right">'+org+'</td></tr>' +
				'<tr><td class="text-left">City</td><td class="txt-oflo text-right">'+city+'</td></tr>' +
				'<tr><td class="text-left">Country</td><td class="txt-oflo text-right">'+country+'</td></tr>' +
				'<tr><td class="text-left">Phone</td><td class="txt-oflo text-right">'+phone+'</td></tr>' +
				'<tr><td class="text-left">Region</td><td class="txt-oflo text-right">'+region+'</td></tr>' +
				'</tbody>' +
				'</table>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';
			swal({
				content: createElementFromHTML(div),
				buttons: false,
				className: 'bg-org'
			});
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'API Error');
	});
});
// set active for group list
$(document).on('click', '.allGroupsList', function() {
    //$(this).toggleClass('active');
});
// Control init of custom netdata JSON editor
$(document).on('click', 'li a[aria-controls="Custom data"]', function() {
    var resizeEditor = function(jsonEditor) {
        const aceEditor = jsonEditor;
        const newHeight = aceEditor.getSession().getScreenLength() * (aceEditor.renderer.lineHeight + aceEditor.renderer.scrollBar.getWidth());
        aceEditor.container.style.height = newHeight + 'px';
        aceEditor.resize();
    }

    jsonEditor = ace.edit("netdataCustomTextAce");
    var JsonMode = ace.require("ace/mode/javascript").Mode;
    jsonEditor.session.setMode(new JsonMode());
    jsonEditor.setTheme("ace/theme/idle_fingers");
    jsonEditor.setShowPrintMargin(false);
    jsonEditor.session.on('change', function(delta) {
        $('#netdataCustomText').val(jsonEditor.getValue());
        $('#customize-appearance-form-save').removeClass('hidden');
    });
});
$(document).on('click', '.imageManagerItem', function() {
	createImageSwal($(this));
});

$(document).on('click', '.close-editHomepageItemDiv',function () {
	//$('body').removeAttr('style');
	//$('html').removeAttr('style');
	Custombox.modal.closeAll()
})

// Trakt image fix
$(document).on('click', '.get-tmdb-image', function() {
	let target = $(this).attr('data-target');
	let type = $(this).hasClass('tmdb-tv') ? 'tv' : 'movie';
	let classList = $(this).attr('class');
	checkMetadataDiv(target,type,classList);
});

function checkMetadataDiv(target,type,classList){
	let classArray = classList.split(/\s+/);
	$(classArray).each(function (i,v) {
		if(v.includes('--')){
			let getId = v.split('--');
			getTmdbImages(getId[1], type).success(function(data) {
				try {
					let response = data;
					let bg = 'https://image.tmdb.org/t/p/w1280';
					if(typeof response.backdrops !== 'undefined'){
						bg = bg + response.backdrops[0]['file_path'];
						$('.' + target + '-metadata-info .user-bg').css('background-image' , '');
						setTimeout(function(){
							$('.' + target + '-metadata-info .user-bg').css('background-image' , 'url('+bg+')');
						}, 25);
					}
				}catch(e) {
					console.log('tmdb Error');
				}
			}).fail(function(xhr) {
				console.log('tmdb Error');
			});
		}
	});
}

// Plugins settings bind
$(document).on('click', '[id$=-settings-button]', function() {
	let el = $(this)[0];
	let bind = $(el).attr('data-bind');
	let api = $(el).attr('data-api');
	let prefix = $(el).attr('data-config-prefix');
	if(bind == 'true' && api !== 'false' && prefix !== 'false'){
		ajaxloader(".content-wrap","in");
		organizrAPI2('GET',api).success(function(data) {
			var response = data.response;
			$('#'+prefix+'-settings-items').html(buildFormGroup(response.data));
		}).fail(function(xhr) {
			OrganizrApiError(xhr);
		});
		ajaxloader();
	}
});
$(document).on('change', '[id*=-form-chooseI]', function (e) {
	let el = $(this)[0];
	let id = $(el).attr('id');
	let newForm = (id.includes('new')) ? 'New' : '';
	let pasteId = id.match(/(?:[a-z]*-){1,5}/) + 'inputImage' + newForm;
	let newValue = $('#'+id).val();
	if(newValue !== 'Select or type Icon'){
		$('#'+pasteId).val(newValue);
	}
});
// SETTINGS DROPDOWN CHANGE
$(document).on("change", ".settings-dropdown-box", function () {
	let id = $(this).val();
	$(id).click();
});
$(document).on('click', '.nav-non-mobile li a', function() {
	let id = $(this).attr('id');
	let menu = $(this).parent().parent().attr('data-dropdown');
	$('.' + menu).val('#' + id);

});

// TOGGLE OVERSEERR ALL SEASONS
$(document).on("change", ".select-all-overseerr-seasons", function () {
	var enabled = $(this).prop("checked") ? 1 : 0;
	$.each($('.overseerr-season'), function(i,v) {
		let seasonEnabled = $(v).prop("checked") ? 1 : 0;
		if(enabled !== seasonEnabled){
			$(v).trigger('click');
		}
	});
});

$(document).on("change", ".overseerr-season", function () {
	let enableButtonDisabled = true;
	let requestedSeasons = [];
	$.each($('.overseerr-season'), function(i,v) {
		let seasonEnabled = $(v).prop("checked") ? 1 : 0;
		if(seasonEnabled){
			let seasonNumber = $(v).attr('data-seasonNumber');
			requestedSeasons.push(seasonNumber);
			enableButtonDisabled = false;
		}
	});
	$('.submit-overseerr-seasons').attr('disabled', enableButtonDisabled);
	$('.submit-overseerr-seasons').attr('data-seasons', requestedSeasons);
});