/* INVITES JS FILE */
$('body').arrive('#activeInfo', {onceOnly: true}, function() {
	inviteLaunch();
});
// FUNCTIONS
function inviteLaunch(){
	var menuList = '';
	var htmlDOM = `
	<div id="invite-area" class="white-popup mfp-with-anim mfp-hide">
		<div class="col-md-10 col-md-offset-1">
			<div class="invite-div"></div>
		</div>
	</div>
	`;
	if(activeInfo.plugins["INVITES-enabled"] == true){
		if (activeInfo.user.loggedin === true && activeInfo.user.groupID <= activeInfo.plugins.includes["INVITES-Auth-include"]) {
			menuList = `<li><a class="inline-popups inviteModal" href="#invite-area" data-effect="mfp-zoom-out"><i class="fa fa-ticket fa-fw"></i> <span lang="en">Manage Invites</span></a></li>`;
			htmlDOM += `
			<div id="new-invite-area" class="white-popup mfp-with-anim mfp-hide">
				<div class="col-md-10 col-md-offset-1">
					<div class="col-md-12">
						<div class="panel panel-info m-b-0">
							<div class="panel-heading" lang="en">New Invite</div>
							<div class="panel-wrapper collapse in" aria-expanded="true">
								<div class="panel-body">
									<form id="new-invite-form">
										<fieldset style="border:0;">
										<div class="form-group">
											<label class="control-label" for="new-invite-form-inputUsername" lang="en">Name or Username</label>
											<input type="text" class="form-control" id="new-invite-form-inputUsername" name="username" required="" autofocus="">
										</div>
										<div class="form-group">
											<label class="control-label" for="new-invite-form-inputEmail" lang="en">Email</label>
											<input type="text" class="form-control" id="new-invite-form-inputEmail" name="email" required="" autofocus="">
										</div>
										</fieldset>
										<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none" onclick="createNewInvite();" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Create/Send Invite</span></button>
										<div class="clearfix"></div>
									</form>
								</div>
							</div>
						</div>
					</div>
					<div class="clearfix"></div>
				</div>
			</div>`;
		}else if (activeInfo.user.loggedin === false){
			menuList = `<li><a class="inline-popups inviteModal" href="#invite-area" data-effect="mfp-zoom-out"><i class="fa fa-ticket fa-fw"></i> <span lang="en">Use Invite Code</span></a></li>`;
		}
		$('.append-menu').after(menuList);
		$('.organizr-area').after(htmlDOM);
		pageLoad();
		getInvite();
	}
}
function joinPlex(){
	var username = $('#invitePlexJoinUsername');
	var email = $('#invitePlexJoinEmail');
	var password = $('#invitePlexJoinPassword');
	if(username.val() == ''){
		username.focus();
		message('Invite Error',' Please Enter Username',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else if(email.val() == ''){
		email.focus();
		message('Invite Error',' Please Enter Email',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else if(password.val() == ''){
		password.focus();
		message('Invite Error',' Please Enter Password',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}
	if(email.val() !== '' && username.val() !== '' && password.val() !== ''){
		organizrAPI2('POST','api/v2/plex/register',{username:username.val(), email:email.val(), password:password.val()}).success(function(data) {
			var response = data.response;
			if(response.result === 'success'){
				$('.invite-step-3-plex-no').toggleClass('hidden');
				$('.invite-step-3-plex-yes').toggleClass('hidden');
				message('Invite Function',' User Created',activeInfo.settings.notifications.position,'#FFF','success','5000');
				$('#inviteUsernameInvite').val(username.val());
				hasPlexUsername();
			}else{
				message('Invite Error',' '+response.message,activeInfo.settings.notifications.position,'#FFF','warning','5000');
			}
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Plex Signup Error');
		});
	}
}

function joinEmby(){
	var username = $('#inviteEmbyJoinUsername');
	var email = $('#inviteEmbyJoinEmail');
	var password = $('#inviteEmbyJoinPassword');
	if(username.val() == ''){
		username.focus();
		message('Invite Error',' Please Enter Username',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else if(email.val() == ''){
		email.focus();
		message('Invite Error',' Please Enter Email',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else if(password.val() == ''){
		password.focus();
		message('Invite Error',' Please Enter Password',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}
	if(email.val() !== '' && username.val() !== '' && password.val() !== ''){
		organizrAPI2('POST','api/v2/emby/register',{username:username.val(), email:email.val(), password:password.val()}).success(function(data) {
			var response = data.response;
			if(response.result === 'success'){
				$('.invite-step-3-emby-no').toggleClass('hidden');
				$('.invite-step-3-emby-yes').toggleClass('hidden');
				message('Invite Function',' User Created',activeInfo.settings.notifications.position,'#FFF','success','5000');
				$('#inviteUsernameInviteEmby').val(username.val());
				hasEmbyUsername();
			}else{
				message('Invite Error',' '+response.message,activeInfo.settings.notifications.position,'#FFF','warning','5000');
			}
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Emby Signup Error');
		});
	}
}

function inviteHasAccount(type,value){
	switch (type) {
		case 'plex':
			if(value){
				$('.invite-step-2').toggleClass('hidden');
				$('.invite-step-3-plex-yes').toggleClass('hidden');
			}else{
				$('.invite-step-2').toggleClass('hidden');
				$('.invite-step-3-plex-no').toggleClass('hidden');
			}
			break;
		case 'emby' :
		  if(value){
			$('.invite-step-2').toggleClass('hidden');
			$('.invite-step-3-emby-yes').toggleClass('hidden');
		  }else{
			$('.invite-step-2').toggleClass('hidden');
			$('.invite-step-3-emby-no').toggleClass('hidden');
		  }
		  break;
		default:
		alert(type+' is not set up yet');
	}
}
function hasPlexUsername(){
	var code = $('#inviteCodeInput').val().toUpperCase();
	var username = $('#inviteUsernameInvite');
	if(username.val() == ''){
		username.focus();
		message('Invite Error',' Please Enter Username',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else{
		var post = {
			usedby:username.val()
		};
		ajaxloader(".content-wrap","in");
		organizrAPI2('POST','api/v2/plugins/invites/' + code,post).success(function(data) {
			var response = data.response;
			if(response.result === 'success'){
				$('.invite-step-3-plex-yes').toggleClass('hidden');
				$('.invite-step-4-plex-accept').toggleClass('hidden');
				if(local('get', 'invite')){
					local('remove', 'invite');
				}
			}else{
				message('Invite Error',response.message,activeInfo.settings.notifications.position,'#FFF','warning','5000');
			}
			ajaxloader();;
		}).fail(function(xhr) {
			OrganizrApiError(xhr);
			ajaxloader();
		});
	}
}
function hasEmbyUsername(){
	var code = $('#inviteCodeInput').val().toUpperCase();
	var username = $('#inviteUsernameInviteEmby');
	if(username.val() == ''){
		username.focus();
		message('Invite Error',' Please Enter Username',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else{
		var post = {
			usedby:username.val()
		};
		ajaxloader(".content-wrap","in");
		organizrAPI2('POST','api/v2/plugins/invites/' + code,post).success(function(data) {
			var response = data.response;
			if(response.result === 'success'){
				$('.invite-step-3-emby-yes').toggleClass('hidden');
				$('.invite-step-4-emby-accept').toggleClass('hidden');
				if(local('get', 'invite')){
					local('remove', 'invite');
				}
			}else{
				message('Invite Error',response.message,activeInfo.settings.notifications.position,'#FFF','warning','5000');
			}
			ajaxloader();;
		}).fail(function(xhr) {
			OrganizrApiError(xhr);
			ajaxloader();
		});
	}
}
function verifyInvite(){
	var code = $('#inviteCodeInput').val().toUpperCase();
	ajaxloader(".content-wrap","in");
	organizrAPI2('GET','api/v2/plugins/invites/'+code).success(function(data) {
		var response = data.response;
		if(response.result === 'success'){
			$('.invite-step-1').toggleClass('hidden');
			$('.invite-step-2').toggleClass('hidden');
		}else{
			message('Invite Error',response.message,activeInfo.settings.notifications.position,'#FFF','warning','5000');
		}
		if(local('get', 'invite')){
			local('remove', 'invite');
		}
		ajaxloader();;
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
		ajaxloader();
	});
}
function getInvite(invite=null){
	if(invite){
		local('set','invite',invite);
	}
	if($.urlParam('invite') !== null){
		local('set','invite',$.urlParam('invite'));
	}
	if(local('get', 'invite')){
		//show error page
		$('.inviteModal').trigger('click');
		$('#inviteCodeInput').val(local('get', 'invite'));
		window.history.pushState({}, document.title, "./" );
		local('remove', 'invite');
	}

}
function createNewInvite(){
	var username = $('#new-invite-form-inputUsername');
	var email = $('#new-invite-form-inputEmail');
	if(username.val() == ''){
		username.focus();
		message('Invite Error',' Please Enter Username',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}else if(email.val() == ''){
		email.focus();
		message('Invite Error',' Please Enter Email',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	}

	if(email.val() !== '' && username.val() !== ''){
		var post = {
			code:createRandomString(6).toUpperCase(),
			email:email.val(),
			username:username.val(),
		};
		ajaxloader(".content-wrap","in");
		organizrAPI2('POST','api/v2/plugins/invites',post).success(function(data) {
			var response = data.response;
			$.magnificPopup.close();
			ajaxloader();
			message('Invite',' Invite Created',activeInfo.settings.notifications.position,'#FFF','success','5000');
		}).fail(function(xhr) {
			OrganizrApiError(xhr);
			ajaxloader();
			message('Invite Error',' An Error Occured',activeInfo.settings.notifications.position,'#FFF','error','5000');
		});
	}

}
function deleteInvite(code, id){
	ajaxloader(".content-wrap","in");
	organizrAPI2('DELETE','api/v2/plugins/invites/' + code).success(function(data) {
		var response = data.response;
		$('#inviteItem-'+id).remove();
		//$.magnificPopup.close();
		ajaxloader();
		message('Invite',' Invite Deleted',activeInfo.settings.notifications.position,'#FFF','success','5000');
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
		ajaxloader();
		message('Invite Error',' An Error Occured',activeInfo.settings.notifications.position,'#FFF','error','5000');
	});

}
// EVENTS and LISTENERS
function buildInvites(array){
	if(array.length == 0){
		return '<h2 class="text-center" lang="en">No Invites</h2>';
	}
	var invites = '';
	$.each(array, function(i,v) {
		v.dateused = (v.dateused) ? v.dateused : '-';
		v.usedby = (v.usedby) ? v.usedby : '-';
		v.ip = (v.ip) ? v.ip : '-';
		invites += `
		<tr id="inviteItem-`+v.id+`">
			<td class="text-center">`+v.id+`</td>
			<td>`+v.username+`</td>
			<td>`+v.email+`</td>
			<td>`+v.code+`</td>
			<td>`+v.date+`</td>
			<td>`+v.dateused+`</td>
			<td>`+v.usedby+`</td>
			<td>`+v.ip+`</td>
			<td>`+v.invitedby+`</td>
			<td>`+v.valid+`</td>
			<td><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5" onclick="deleteInvite('`+v.code+`','`+v.id+`');"><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return invites;
}
$(document).on('click', '.inviteModal', function() {
	var htmlDOM = '';
	if (activeInfo.user.loggedin === true && activeInfo.user.groupID <= activeInfo.plugins.includes["INVITES-Auth-include"]) {
		ajaxloader(".content-wrap","in");
		organizrAPI2('GET','api/v2/plugins/invites').success(function(data) {
			var response = data.response;
			var htmlDOM = '';
			htmlDOM = `
			<div class="col-md-12">
				<div class="panel bg-org panel-info">
					<div class="panel-heading">
						<span lang="en">Manage Invites</span>
						<button type="button" class="btn btn-info btn-circle pull-right popup-with-form" href="#new-invite-area" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
					</div>
					<div class="table-responsive">
						<table class="table table-hover manage-u-table">
							<thead>
								<tr>
									<th width="70" class="text-center">#</th>
									<th lang="en">USERNAME</th>
									<th lang="en">EMAIL</th>
									<th lang="en">INVITE CODE</th>
									<th lang="en">DATE SENT</th>
									<th lang="en">DATE USED</th>
									<th lang="en">USED BY</th>
									<th lang="en">IP ADDRESS</th>
									<th lang="en">INVITED BY</th>
									<th lang="en">VALID</th>
									<th lang="en">DELETE</th>
								</tr>
							</thead>
							<tbody id="manageInviteTable">
								`+buildInvites(response.data)+`
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			`;
			$('.invite-div').html(htmlDOM);
		}).fail(function(xhr) {
			console.error("Organizr Function: API Connection Failed");
		});
		ajaxloader();
	}else if (activeInfo.user.loggedin === false){
		htmlDOM = `
		<div class="col-md-12">
			<div class="panel panel-info m-b-0">
				<div class="panel-heading" lang="en">Use Invite Code</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<div class="panel-body">
						<div class="form-group invite-step-1">
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-lock"></i></div>
								<input type="text" class="form-control text-uppercase" id="inviteCodeInput" placeholder="Code" autocomplete="off" autocorrect="off" autocapitalize="off" maxlength="6" spellcheck="false" autofocus="" required="">
							</div>
							<br />
							<button class="btn btn-block btn-info" onclick="verifyInvite();">Verify</button>
						</div>
						<div class="form-group invite-step-2 hidden">
							<div class="row">
								<h2 class="text-center" lang="en">Do you have a `+activeInfo.plugins.includes["INVITES-type-include"].toUpperCase()+` account?</h2>
								<div class="col-lg-6">
									<button class="btn btn-block btn-info m-b-10" onclick="inviteHasAccount('`+activeInfo.plugins.includes["INVITES-type-include"]+`',true);" lang="en">Yes</button>
								</div>
								<div class="col-lg-6">
									<button class="btn btn-block btn-primary m-b-10" onclick="inviteHasAccount('`+activeInfo.plugins.includes["INVITES-type-include"]+`',false);" lang="en">No</button>
								</div>
							</div>
						</div>
						<div class="form-group invite-step-3-plex-yes hidden">
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-user"></i></div>
								<input type="text" class="form-control" id="inviteUsernameInvite" placeholder="Plex Username or Email" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus="" required="">
							</div>
							<br />
							<button class="btn btn-block btn-info" onclick="hasPlexUsername();">Submit</button>
						</div>
						<div class="form-group invite-step-3-plex-no hidden">
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-user"></i></div>
								<input type="text" class="form-control" id="invitePlexJoinUsername" lang="en" placeholder="Username" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus="" required="">
							</div>
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-email"></i></div>
								<input type="text" class="form-control" id="invitePlexJoinEmail" lang="en" placeholder="E-Mail" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" required="">
							</div>
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-user"></i></div>
								<input type="password" class="form-control" id="invitePlexJoinPassword" lang="en" placeholder="Password" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"  required="">
							</div>
							<br />
							<button class="btn btn-block btn-info" onclick="joinPlex();">Submit</button>
						</div>
						<div class="form-group invite-step-4-plex-accept hidden">
							<h4 class="" lang="en">You have been invited.  Please check your email or goto <a href="https://plex.tv" target="_blank">PLEX.TV</a> and login to accept the invite.  Once you have done that, you may head back here and login with your credentials.</h4>
						</div>
						<!-- Begin Emby Invites -->
						<div class="form-group invite-step-3-emby-yes hidden">
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-user"></i></div>
								<input type="text" class="form-control" id="inviteUsernameInviteEmby" placeholder="Emby Username" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus="" required="">
							</div>
							<br />
							<button class="btn btn-block btn-info" onclick="hasEmbyUsername();">Submit</button>
						</div>
						<div class="form-group invite-step-3-emby-no hidden">
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-user"></i></div>
								<input type="text" class="form-control" id="inviteEmbyJoinUsername" lang="en" placeholder="Username" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus="" required="">
							</div>
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-email"></i></div>
								<input type="text" class="form-control" id="inviteEmbyJoinEmail" lang="en" placeholder="E-Mail" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" required="">
							</div>
							<div class="input-group" style="width: 100%;">
								<div class="input-group-addon hidden-xs"><i class="ti-user"></i></div>
								<input type="password" class="form-control" id="inviteEmbyJoinPassword" lang="en" placeholder="Password" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"  required="">
							</div>
							<br />
							<button class="btn btn-block btn-info" onclick="joinEmby();">Submit</button>
						</div>
						<div class="form-group invite-step-4-emby-accept hidden">
							<h4 class="" lang="en">You Have been added to emby!</h4>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clearfix"></div>
		`;
		$('.invite-div').html(htmlDOM);
	}
});