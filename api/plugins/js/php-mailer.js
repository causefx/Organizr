/* PHP MAILER JS FILE */

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
    }else{
	    messageSingle('','Sending Message',activeInfo.settings.notifications.position,'#FFF','success','5000');
    }
    if(to !== '' && subject !== '' && body !== ''){
        var post = {
            bcc:to,
            subject:subject,
            body:body
        };
        ajaxloader(".content-wrap","in");
        organizrAPI2('POST','api/v2/plugins/php-mailer/email/send',post).success(function(data) {
            var response = data.response;
            if(response.result == 'success'){
                $.magnificPopup.close();
                messageSingle('',window.lang.translate('Email Sent Successful'),activeInfo.settings.notifications.position,'#FFF','success','5000');
            }else{
                messageSingle('',response.message,activeInfo.settings.notifications.position,'#FFF','error','5000');
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
    ajaxloader(".content-wrap","in");
    organizrAPI2('GET','api/v2/plugins/php-mailer/email/list').success(function(data) {
        var response = data.response;
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
$(document).on('click', '#PHPMAILER-settings-button', function() {
    ajaxloader(".content-wrap","in");
    organizrAPI2('GET','api/v2/plugins/php-mailer/settings').success(function(data) {
        var response = data.response;
        $('#PHPMAILER-settings-items').html(buildFormGroup(response.data));
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
// SEND TEST EMAIL
$(document).on('click', '.phpmSendTestEmail', function() {
    messageSingle('',window.lang.translate('Sending Test E-Mail'),activeInfo.settings.notifications.position,'#FFF','info','5000');
    ajaxloader(".content-wrap","in");
    organizrAPI2('GET','api/v2/plugins/php-mailer/email/test').success(function(data) {
        var response = data.response;
        if(response.message !== null && response.message.indexOf('|||DEBUG|||') == 0){
            messageSingle('',window.lang.translate('Press F11 to check Console for output'),activeInfo.settings.notifications.position,'#FFF','warning','5000');
	        console.warn(response.message);
        }else if(response.result == 'success') {
            messageSingle('',window.lang.translate('Email Test Successful'),activeInfo.settings.notifications.position,'#FFF','success','20000');
        }else{
            messageSingle('',response.message,activeInfo.settings.notifications.position,'#FFF','error','5000');
        }
    }).fail(function(xhr, data) {
    	console.log(data)
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
