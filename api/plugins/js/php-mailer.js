/* PHP MAILER JS FILE */
/*
$(document).on('click', '#PHPMAILER-settings-button', function() {
	var post = {
        plugin:'PHPMailer/settings/get', // used for switch case in your API call
        api:'api/?v1/plugin', // API Endpoint will always be this for custom plugin API calls
        name:$(this).attr('data-plugin-name'),
        configName:$(this).attr('data-config-name'),
        messageTitle:'', // Send succees message title (top line)
        messageBody:'Disabled '+$(this).attr('data-plugin-name'), // Send succees message body (bottom line)
        error:'Organizr Function: API Connection Failed' // conole error message
    };
	var callbacks = $.Callbacks(); // init callbacks var
    //callbacks.add(  ); // add function to callback to be fired after API call
    //settingsAPI(post,callbacks); // exec API call
    //ajaxloader(".content-wrap","in");
    //setTimeout(function(){ buildPlugins();ajaxloader(); }, 3000);
});
*/

// FUNCTIONS
phpmLaunch();
function phpmLaunch(){
    if(typeof activeInfo == 'undefined'){
        setTimeout(function () {
            phpmLaunch();
        }, 1000);
    }else{
        if(activeInfo.plugins["PHPMAILER-enabled"] == true){
            if (activeInfo.user.loggedin === true && activeInfo.user.groupID <= 1) {
                var menuList = `<li><a class="inline-popups emailModal" href="#email-area" data-effect="mfp-zoom-out"><i class="fa fa-envelope fa-fw"></i> <span lang="en">E-Mail Center</span></a></li>`;
                var htmlDOM = `
            	<div id="email-area" class="white-popup mfp-with-anim mfp-hide">
            		<div class="col-md-10 col-md-offset-1">
            			<div class="email-div"></div>
            		</div>
            	</div>
            	`;
                $('.organizr-area').after(htmlDOM);
                $('.append-menu').after(menuList);
                pageLoad();
            }
        }
    }
}
function sendMail(){
    var to = $('#sendEmailToInput').val();
    var subject = $('#sendEmailSubjectInput').val();
    var body = tinyMCE.get('sendEmail').getContent();
    if(to == ''){
        messageSingle('','Please Enter Email',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }else if(subject == ''){
        messageSingle('','Please Enter Subject',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }else if(body == ''){
        messageSingle('','Please Enter Body',activeInfo.settings.notifications.position,'#FFF','error','5000');
    }
    if(to !== '' && subject !== '' && body !== ''){
        var post = {
            plugin:'PHPMailer/send/email', // used for switch case in your API call
            bcc:to,
            subject:subject,
            body:body
        };
        ajaxloader(".content-wrap","in");
        organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
            var response = JSON.parse(data);
            if(response.data == true){
                $.magnificPopup.close();
                messageSingle('',window.lang.translate('Email Sent Successful'),activeInfo.settings.notifications.position,'#FFF','success','5000');
            }else{
                messageSingle('',response.data,activeInfo.settings.notifications.position,'#FFF','error','5000');
            }
        }).fail(function(xhr) {
            console.error("Organizr Function: API Connection Failed");
        });
        ajaxloader();
    }
}
function buildUserList(array){
    var users = '';
    var htmlDOM = '';
	$.each(array, function(i,v) {
        users += '<option value="'+v+'">'+i+'</option>';
    });
    htmlDOM = `
    <select multiple id="email-user-list" name="email-user-list[]">`+users+`</select>
    <div class="button-box m-t-20">
        <a id="select-all-users-list" class="btn btn-danger btn-outline" href="#">select all</a>
        <a id="deselect-all-users-list" class="btn btn-info btn-outline" href="#">deselect all</a>
        <a id="minimize-users-list" class="btn btn-primary btn-outline" href="#">minimize</a>
    </div>`;
    return htmlDOM;
}
function buildEmailModal(){
    var htmlDOM = `
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info m-0">
                <div class="panel-heading">
                    <span lang="en">Email Users</span>
                    <div class="btn-group pull-right">

						<button class="btn btn-info waves-effect waves-light loadUserList" type="button">
							<i class="fa fa-user"></i>
						</button>
                        <button class="btn btn-info waves-effect waves-light" type="button" onclick="$('.mce-i-template').trigger('click');">
							<i class="fa fa-files-o"></i>
						</button>
                        <button class="btn btn-info waves-effect waves-light unhide-user-list hidden" type="button">
							<i class="fa fa-eye"></i>
						</button>
						<button class="btn btn-info waves-effect waves-light" onclick="sendMail();"><i class="fa fa-paper-plane"></i></button>

	                </div>
                </div>
                <div class="panel-wrapper collapse in main-email-panel" aria-expanded="true">
                    <div class="panel-body">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" lang="en">To:</label>
                                        <input type="text" id="sendEmailToInput" class="form-control"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" lang="en">Subject</label>
                                        <input type="text" id="sendEmailSubjectInput" class="form-control"></div>
                                </div>
                                <div class="col-md-12" id="user-list-div">


                                </div>
                            </div>
                            <!--/row-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <textarea id="sendEmail" name="area"></textarea>
    `;
    $('.email-div').html(htmlDOM);
    if ($("#sendEmail").length > 0) {
        var templates = [];
        if(activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-One"] !== ''){
            templates.push(
                {
                    title: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-OneName"],
                    description: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-OneSubject"],
                    content: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-One"],
                }
            )
        }
        if(activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-Two"] !== ''){
            templates.push(
                {
                    title: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-TwoName"],
                    description: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-TwoSubject"],
                    content: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-Two"],
                }
            )
        }
        if(activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-Three"] !== ''){
            templates.push(
                {
                    title: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-ThreeName"],
                    description: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-ThreeSubject"],
                    content: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-Three"],
                }
            )
        }
        if(activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-Four"] !== ''){
            templates.push(
                {
                    title: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-FourName"],
                    description: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-FourSubject"],
                    content: activeInfo.plugins.includes["PHPMAILER-emailTemplateCustom-include-Four"],
                }
            )
        }
        tinymce.init({
            selector: "textarea#sendEmail",
            theme: "modern",
            height: 300,
            plugins: [
                "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker", "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking", "save table contextmenu directionality emoticons template paste textcolor"
            ],
            toolbar: "insertfile template undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor",
            templates: templates,
            init_instance_callback: function (editor) {
                editor.on('BeforeSetContent', function (e) {
                    //tinyMCE.get('sendEmail').execCommand('selectAll');
                    //tinyMCE.get('sendEmail').execCommand('delete');
                    $.each(e.target.settings.templates, function(i,v) {
                        if($.trim(v.content) == $.trim(e.content)){
                            $('#sendEmailSubjectInput').val(v.description);
                        }
                    });
                });
              }
        });
    }

}
// EVENTS and LISTENERS
$(document).on("change", "#email-user-list", function () {
    $('#sendEmailToInput').val($('#email-user-list').val());
});
$(document).on('click', '.loadUserList', function() {
    var post = {
        plugin:'PHPMailer/users/get', // used for switch case in your API call
    };
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
        var response = JSON.parse(data);
        $('#user-list-div').html(buildUserList(response.data));
        $('#email-user-list').multiSelect();
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
$(document).on("click", ".emailModal", function(e) {
    buildEmailModal();
});
$(document).on("click", ".show-login", function(e) {
    setTimeout(addForgotPassword, 1000);
});
$(document).on("click", "#select-all-users-list", function(e) {
    $('#email-user-list').multiSelect('select_all');
    return false;
});
$(document).on("click", "#deselect-all-users-list", function(e) {
    $('#email-user-list').multiSelect('deselect_all');
    return false;
});
$(document).on("click", "#minimize-users-list, .unhide-user-list", function(e) {
    $('.main-email-panel').toggleClass('hidden');
    $('.loadUserList').toggleClass('hidden');
    $('.unhide-user-list').toggleClass('hidden');
    return false;
});
function addForgotPassword(){
    var item = '';
    if(activeInfo.plugins["PHPMAILER-enabled"] == true){
        if (activeInfo.user.loggedin === false) {
            item = `<a href="javascript:void(0)" id="to-recover" class="text-dark pull-right"><i class="fa fa-lock m-r-5"></i> <span lang="en">Forgot pwd?</span></a>`;
            $('.remember-me').after(item);
        }
    }
}
// CHANGE CUSTOMIZE Options
$(document).on('change asColorPicker::close', '#PHPMAILER-settings-page1 :input', function(e) {
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
    $('#PHPMAILER-settings-page :input').prop('disabled', 'true');
    setTimeout(
        function(){
            $('#PHPMAILER-settings-page :input').prop('disabled', null);
            input.emulateTab();
        },
        2000
    );

});
$(document).on('click', '#PHPMAILER-settings-button', function() {
    var post = {
        plugin:'PHPMailer/settings/get', // used for switch case in your API call
    };
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
        var response = JSON.parse(data);
        $('#PHPMAILER-settings-items').html(buildFormGroup(response.data));
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
// SEND TEST EMAIL
$(document).on('click', '.phpmSendTestEmail', function() {
    messageSingle('',window.lang.translate('Sending Test E-Mail'),activeInfo.settings.notifications.position,'#FFF','info','5000');
    var post = {
        plugin:'PHPMailer/send/test', // used for switch case in your API call
    };
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
        var response = JSON.parse(data);
        if(response.data == true){
            messageSingle('',window.lang.translate('Email Test Successful'),activeInfo.settings.notifications.position,'#FFF','success','5000');
        }else{
            messageSingle('',response.data,activeInfo.settings.notifications.position,'#FFF','error','5000');
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
