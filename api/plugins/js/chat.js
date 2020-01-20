// FUNCTIONS FOR CHAT
chatLaunch()
function chatLaunch(){
    if(typeof activeInfo == 'undefined'){
        setTimeout(function () {
            chatLaunch();
        }, 1000);
    }else{
        if(activeInfo.plugins["CHAT-enabled"] == true && activeInfo.plugins.includes["CHAT-authKey-include"] !== '' && activeInfo.plugins.includes["CHAT-appID-include"] !== '' && activeInfo.plugins.includes["CHAT-cluster-include"] !== ''){
            if (activeInfo.user.groupID <= activeInfo.plugins.includes["CHAT-Auth-include"]) {
                var menuList = `<li><a class=""  href="javascript:void(0)" onclick="tabActions(event,'chat','plugin');chatEntry();"><i class="fa fa-comments-o fa-fw"></i> <span lang="en">Chat</span><small class="chat-counter label label-rouded label-info pull-right hidden">0</small></a></li>`;
				var htmlDOM = `
                <div id="container-plugin-chat" class="plugin-container hidden">
                    <div class="chat-main-box bg-org">
                        <!-- .chat-left-panel -->
                        <div class="chat-left-aside">
                            <div class="open-panel"><i class="ti-angle-right"></i></div>
                            <div class="chat-left-inner bg-org"><ul class="chatonline style-none "></ul></div>
                        </div>
                        <!-- .chat-left-panel -->
                        <!-- .chat-right-panel -->
                        <div class="chat-right-aside">
                            <div class="chat-box">
                                <ul class="chat-list p-t-30"></ul>
                                <div class="row send-chat-box">
                                    <div class="col-sm-12">
                                        <textarea class="form-control chat-input-send" placeholder="Type your message"></textarea>
                                        <div class="custom-send">
                                            <button type="button" class="btn btn-info btn-lg custom-send-button"><i class="fa fa-paper-plane fa-2x"></i> </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- .chat-right-panel -->
                    </div>
                </div>
		    	`;
				$('.append-menu').after(menuList);
	            $('.plugin-listing').append(htmlDOM);
	            pageLoad();
                // Enable pusher logging - don't include this in production
                //Pusher.logToConsole = true;
                // Add API Key & cluster here to make the connection
                var pusher = new Pusher(activeInfo.plugins.includes["CHAT-authKey-include"], {
                    cluster: activeInfo.plugins.includes["CHAT-cluster-include"],
                    encrypted: true
                });
                // Enter a unique channel you wish your users to be subscribed in.
                var channel = pusher.subscribe('org_channel');
                // bind the server event to get the response data and append it to the message div
                channel.bind('my-event',
                    function(data) {
                        formatMessage(data);
                        $('.chat-list').append(formatMessage(data));
                        $('.custom-send').html('<button type="button" class="btn btn-info btn-lg custom-send-button"><i class="fa fa-paper-plane fa-2x"></i> </button>');
                        $(".chat-list").scrollTop($(".chat-list")[0].scrollHeight);
                        if($('#container-plugin-chat').hasClass('hidden')){
                            var chatSound =  new Audio(activeInfo.plugins.includes["CHAT-newMessageSound-include"]);
                            chatSound.play();
                            message(data.username,data.message,activeInfo.settings.notifications.position,"#FFF","success","20000");
                            $('.profile-image').addClass('animated loop-animation rubberBand');
                            $('.chat-counter').removeClass('hidden').html(parseInt($('.chat-counter').text()) + 1);
                        }
                    });
                // check if the user is subscribed to the above channel
                channel.bind('pusher:subscription_succeeded', function(members) {
                    console.log('Chat Websocket Connected!');
                    console.log('Connecting to Organizr Chat DB');
                    getMessagesAndUsers(activeInfo.settings.homepage.refresh["CHAT-userRefreshTimeout"], true);
                });
                /*jslint browser: true*/
                /*global $, jQuery, alert*/
                $(document).ready(function () {
                    "use strict";
                    $('.chat-left-inner > .chatonline').slimScroll({
                        height: '100%',
                        position: 'right',
                        size: "0px",
                        color: '#dcdcdc'

                    });
                    $('.chat-list').slimScroll({
                        height: '100%',
                        position: 'right',
                        size: "0px",
                        color: '#dcdcdc',
                        start: 'bottom',
                    });
                    $(".open-panel").on("click", function () {
                        $(".chat-left-aside").toggleClass("open-pnl");
                        $(".open-panel i").toggleClass("ti-angle-left");
                    });
                });
			}
        }
    }
}
$(document).on('click', '#CHAT-settings-button', function() {
    var post = {
        plugin:'chat/settings/get', // used for switch case in your API call
    };
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
        var response = JSON.parse(data);
        $('#CHAT-settings-items').html(buildFormGroup(response.data));
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
//Chat functions!
$(document).on('keypress', '.chat-input-send', function(ev) {
    var keycode = (ev.keyCode ? ev.keyCode : ev.which);
    if (keycode == '13') {
        ev.preventDefault();
        $('.custom-send-button').click();
    }
});
// Send the Message enter by User
$('body').on('click', '.custom-send-button', function(e) {
    e.preventDefault();
    var message = $('.chat-input-send').val();
    // Validate Name field
    if (message !== '') {
        var post = {
            plugin:'chat/message',
            message:message
        };
        organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
            // Nada yet
        }).fail(function(xhr) {
            console.error("Organizr Function: API Connection Failed");
        });
        // Clear the message input field
        $('.chat-input-send').val('');
        // Show a loading image while sending
        $('.custom-send').html('<button type="button" class="btn btn-info btn-lg custom-send-button" disabled><i class="fa fa-spinner fa-pulse fa-2x"></i> </button>');
    }
});
function formatMessage(msg){
    var className = 'odd';
    if(msg.username == activeInfo.user.username){
        if(activeInfo.user.username == 'Guest' && activeInfo.user.uid !== msg.uid){
            className = '';
        }
    }else{
        className = '';
    }
    return `
        <li class="`+className+`">
            <div class="chat-image"> <img alt="male" src="`+msg.gravatar+`"> </div>
            <div class="chat-body">
                <div class="chat-text">
                    <h4>`+msg.username+`</h4>
                    <p> `+msg.message+` </p> <b>`+moment.utc(msg.date, "YYYY-MM-DD hh:mm").local().format('LLL')+`</b> </div>
            </div>
        </li>
    `;
}
function formatUsers(array){
    var users = {};
    var userList = '';
    array.reverse();
    $.each(array, function (i, v){
        if(!users.hasOwnProperty(v.username)){
            users[v.username] = {
                'last':v.date,
                'gravatar':v.gravatar
            }
        }
    });
    $.each(users, function (i, v) {
        userList += `
            <li>
                <a href="javascript:void(0)"><img src="`+v.gravatar+`" alt="user-img" class="img-circle"> <span>`+i+`<small class="text-success">`+moment.utc(v.last, "YYYY-MM-DD hh:mm[Z]").local().fromNow()+`</small></span></a>
            </li>
        `;
    });
    userList += '<li class="p-20"></li>';
    return userList;
}
function chatEntry(){
    $(".chat-list").scrollTop($(".chat-list")[0].scrollHeight);
    $('.chat-input-send').focus();
    $('.chat-counter').addClass('hidden').html('0');
}
function getMessagesAndUsers(timeout, initial = false){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh["CHAT-userRefreshTimeout"];
    organizrAPI('GET','api/?v1/plugin&plugin=chat&cmd=chat/message').success(function(data) {
        var response = JSON.parse(data);
        if(initial == true){
            $.each(response.data, function (i, v){
                $('.chat-list').append(formatMessage(v));
            });
        }
        $('.chatonline').html(formatUsers(response.data));
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    var timeoutTitle = 'ChatUserList';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ getMessagesAndUsers(timeout, false); }, timeout);
}
$(document).on('click', '.profile-pic', function(e) {
    $('.profile-image').removeClass('animated loop-animation rubberBand');
});
