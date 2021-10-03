/* This file is loaded when Organizr is loaded */
// Load once Organizr loads
$('body').arrive('#activeInfo', {onceOnly: true}, function() {
	plexLibrariesPluginLaunch();
});
// FUNCTIONS
function plexLibrariesPluginLaunch(){
	organizrAPI2('GET','api/v2/plugins/plexlibraries/launch').success(function(data) {
		try {
			var menuList = `<li><a href="javascript:void(0)" onclick="togglePlexLibrariesPlugin();"><i class="fa fa-tv fa-fw"></i> <span lang="en">Plex Libraries</span></a></li>`;
			$('.append-menu').after(menuList);
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});

}
function togglePlexLibrariesPlugin(){
	let div = `
		<div class="panel bg-org panel-info" id="plexLibraries-area">
			<div class="panel-heading">
				<span lang="en">Customise Plex Libraries</span>
			</div>
			<div class="panel-body">
				
				<div id="plexLibrariesTable">
					<div class="white-box m-b-0">
						<h2 class="text-center loadingPlexLibraries" lang="en"><i class="fa fa-spin fa-spinner"></i></h2>
						<div class="row">
							<div class="col-lg-12">
								<select class="form-control" name="plexUsers" id="plexUsers" style="display:none">
									<option value="">Choose a User</option>
								</select><br>
							</div>
						</div>
						<div class="table-responsive plexLibrariesTableList hidden" id="plexLibrariesTableList">
							<table class="table color-bordered-table purple-bordered-table text-left">
								<thead>
									<tr>
										<th width="20">Type</th>
										<th>Name</th>
										<th width="20">Action</th>
									</tr>
								</thead>
								<tbody id="plexLibraries"></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		`;
	swal({
		content: createElementFromHTML(div),
		button: false,
		className: 'orgAlertTransparent',
	});
	plexLibrariesPluginLoadShares();
}
function plexLibrariesPluginLoadShares(){
	organizrAPI2('GET','api/v2/plugins/plexlibraries/shares').success(function(data) {
		$('.loadingPlexLibraries').remove();
		try {
			if (data.response.data.plexAdmin == false) {
				$.each(data.response.data.libraryData, function(_, sharedServer) {
					$.each(sharedServer.libraries, function(_, obj) {
						var userId = sharedServer.id;
						plexLibrariesPluginLoadSharesItem(obj,"",userId);
						if($('.plexLibrariesTableList').hasClass('hidden')){
							$('.plexLibrariesTableList').removeClass('hidden');
						}
					});
				});
			} else {
				// Plex Admin response contains all users shares, mark all toggles as disabled whilst this is a work in progress.
				$.each(data.response.data.libraryData, function(_, sharedServer) {
					const thtml = $("#plexUsers ");
					var dropdown = document.getElementById('plexUsers');
					dropdown.style.display = "block";
					var username = sharedServer.username;
					var userId = sharedServer.id;
					thtml.append('<option value="'+username+'">'+username+'</option>');
					$.each(sharedServer.libraries, function(_, obj) {
						plexLibrariesPluginLoadSharesItem(obj,username,userId);
					});
				});
			}
			const thtml = $("#plexLibraries ");
			thtml.append('<script>plexLibrariesPluginOnToggle();</script>');
			thtml.append('<script>plexLibrariesPluginOnSelect();</script>');
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		$('.loadingPlexLibraries').remove();
		OrganizrApiError(xhr);
	});
}

function plexLibrariesPluginLoadSharesItem(obj,username,userId){
	const thtml = $("#plexLibraries ");
	var mediaType = obj.type
	var mediaShared = obj.shared
	var mediaIcon = "0"
	var checked = "";
	switch(mediaType) {
		case 'movie':
			var mediaIcon = "video-clapper"
			var mediaIconColour = "purple"
			break;
		case 'show':
			var mediaIcon = "video-camera"
			var mediaIconColour = "warning"
			break;
		case 'artist':
			var mediaIcon = "music-alt"
			var mediaIconColour = "info"
			break;
		case 'photo':
			var mediaIcon = "camera"
			var mediaIconColour = "danger"
			break;
	}
	if (mediaShared == 1) {
		var checked = "checked";
	}
	if (username === "") {
		var username = "none"
	}
	let libItem = `
		<tr class="plexUser ${username}">
			<td><p class="text-${mediaIconColour}"><i class="ti-${mediaIcon} fa-2x" style="vertical-align: text-top;"></i></p></td>
			<td>${obj.title}</td>
			<td><input type="checkbox" class="js-switch plexLibraries" data-size="small" data-color="#99d683" data-secondary-color="#f96262" data-user-id="${userId}" value="${obj.id}" ${checked}></td>
		</tr>
	`;
		thtml.append(libItem);
}

function plexLibrariesPluginOnToggle() {
    $('.plexLibraries').change(function () {
    	let userId = $(this).attr('data-user-id');
        if (this.checked) {
	        plexLibrariesPluginUpdateShare(userId,"share", this.value);
        } else {
	        plexLibrariesPluginUpdateShare(userId,"unshare", this.value);
        }
    });
}

function plexLibrariesPluginOnSelect() {
    $('#plexUsers').change(function () {
		Array.from(document.getElementsByClassName('plexUser')).forEach(
			function(element, index, array) {
				element.style.display = "none";
			}
		);
		Array.from(document.getElementsByClassName(this.value)).forEach(
			function(element, index, array) {
				element.style.display = "table-row";
			}
		);
		if($('.plexLibrariesTableList').hasClass('hidden')){
			$('.plexLibrariesTableList').removeClass('hidden');
		}
    });
}

function plexLibrariesPluginUpdateShare(userId, action, shareId) {

	$('#plexLibrariesTable').block({
		message: '<h4><img src="plugins/images/busy.gif" width="50px" /> Just a moment...</h4>',
		css: {
			border: '1px solid #000',
			color: '#fff',
			background: '#1b1b1b'
		}
	});
	organizrAPI2('POST','api/v2/plugins/plexlibraries/shares/' + userId + '/' + action + '/' + shareId, {}).success(function(data) {
		try {
			let response = data.response;
			$('#plexLibrariesTable').unblock();
			message('Plex Share',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		message('Plex Share',xhr.responseJSON.response.message,activeInfo.settings.notifications.position,"#FFF","error","5000");
		$('#plexLibrariesTable').unblock();
		OrganizrApiError(xhr);
	});
}
// EVENTS and LISTENERS
