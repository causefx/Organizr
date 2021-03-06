/* BOOKMARK JS FILE */
// FUNCTIONS
bookmarkLaunch();
$('body').arrive('#settings-main-tab-editor .nav-tabs', {onceOnly: true}, function() {
	bookmarkLaunch();
});
function bookmarkCheckForTab() {
	// Let check for tab with bookmark url
	organizrAPI2('GET', 'api/v2/plugins/bookmark/setup/tab').success(function (data) {
		try {
			let response = data.response;
			$('.bookmark-check-tab small').text('Bookmark Tab');
			$('.bookmark-check-tab .result').text(response.message);
		} catch (e) {
			organizrCatchError(e, data);
		}
	}).fail(function (xhr) {
		OrganizrApiError(xhr);
		$('.bookmark-check-tab .result').text('Error...');
	});
}
$('body').arrive('.bookmark-check-tab', {onceOnly: false}, function() {
	setTimeout(function(){
		bookmarkCheckForTab()
		bookmarkCheckForCategory();
	}, 500);

});
function bookmarkCheckForCategory(){
	// Let check for tab with bookmark url
	organizrAPI2('GET','api/v2/plugins/bookmark/setup/category').success(function(data) {
		try {
			let response = data.response;
			$('.bookmark-check-category small').text('Bookmark Categories');
			$('.bookmark-check-category .result').text(response.message);
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
		$('.bookmark-check-category .result').text('Error...');
	});
}
function bookmarkLaunch(){
	if(activeInfo.plugins["BOOKMARK-enabled"] == true){
		bookmarkTabsLaunch();
		bookmarkCategoriesLaunch();
		pageLoad();
	}
}

// TAB MANAGEMENT
function bookmarkTabsLaunch(){
	var menuList = `<li onclick="changeSettingsMenu('Settings::Tab Editor::Bookmark Tabs');loadSettingsPage2('api/v2/plugins/bookmark/settings_tab_editor_bookmark_tabs','#settings-tab-editor-tabs','Tab Editor');" role="presentation"><a id="settings-tab-editor-tabs-anchor" href="#settings-tab-editor-tabs" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-layout-tab-v"></i></span><span class="hidden-xs" lang="en">Bookmark Tabs</span></a></li>`;
	$('#settings-main-tab-editor .nav-tabs').append(menuList);
}

function buildBookmarkTabEditor(){
	organizrAPI2('GET','api/v2/plugins/bookmark/tabs').success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		$('#bookmarkTabEditorTable').html(buildBookmarkTabEditorItem(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}

function buildBookmarkTabEditorItem(array){
	var tabList = '';
	$.each(array.tabs, function(i,v) {
		tabList += `
		<tr class="bookmarkTabEditor" data-order="`+v.order+`" data-original-order="`+v.order+`" data-id="`+v.id+`" data-group-id="`+v.group_id+`" data-category-id="`+v.category_id+`" data-name="`+v.name+`" data-url="`+v.url+`" data-image="`+v.image+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].id" value="`+v.id+`">
			<input type="hidden" class="form-control order" name="tab[`+v.id+`].order" value="`+v.order+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].originalOrder" value="`+v.order+`">
			<td style="text-align:center" class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="bookmarkTabEditorIcon">`+iconPrefix(v.image)+`</div>
						<div class="el-overlay bg-org">
							<ul class="el-info">
								<i class="fa fa-bars"></i>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+v.url+`">`+v.name+`</span></td>
            `+buildBookmarkTabCategorySelect(array.categories,v.id, v.category_id)+`
			`+buildBookmarkTabGroupSelect(array.groups,v.id, v.group_id)+`
			<td style="text-align:center"><input type="checkbox" class="js-switch bookmarkEnabledSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[`+v.id+`].enabled" value="true" `+tof(v.enabled,'c')+`/><input type="hidden" class="form-control" name="tab[`+v.id+`].enabled" value="false"></td>
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editBookmarkTabButton popup-with-form" onclick="editBookmarkTabForm('`+v.id+`')" href="#edit-bookmark-tab-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 bookmarkDeleteTab"><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return tabList;
}

function buildBookmarkTabGroupSelect(array, tabID, groupID){
	var groupSelect = '';
	var selected = '';
	$.each(array, function(i,v) {
		selected = '';
		if(v.group_id == groupID){
			selected = 'selected';
		}
		groupSelect += '<option '+selected+' value="'+v.group_id+'">'+v.group+'</option>';
	});
	return '<td><select name="tab['+tabID+'].group_id" class="form-control bookmarkTabGroupSelect">'+groupSelect+'</select></td>';
}

function buildBookmarkTabCategorySelect(array,tabID, categoryID){
	var categorySelect = '';
	var selected = '';
	$.each(array, function(i,v) {
		selected = '';
		if(v.category_id == categoryID){
			selected = 'selected';
		}
		categorySelect += '<option '+selected+' value="'+v.category_id+'">'+v.category+'</option>';
	});
	return '<td><select name="tab['+tabID+'].category_id" class="form-control bookmarkTabCategorySelect">'+categorySelect+'</select></td>';
}

function editBookmarkTabForm(id){
	organizrAPI2('GET','api/v2/plugins/bookmark/tabs/' + id,true).success(function(data) {
		try {
			let response = data.response;
			console.log(response);
			$('#edit-bookmark-tab-form [name=name]').val(response.data.name);
			$('#originalBookmarkTabName').html(response.data.name);
			$('#edit-bookmark-tab-form [name=url]').val(response.data.url);
			$('#edit-bookmark-tab-form [name=image]').val(response.data.image);
			$('#edit-bookmark-tab-form [name=background_color]').val(response.data.background_color);
			$('#edit-bookmark-tab-form [name=text_color]').val(response.data.text_color);
			$('#edit-bookmark-tab-form [name=id]').val(response.data.id);
			if( response.data.url.indexOf('/?v') > 0){
				$('#edit-bookmark-tab-form [name=url]').prop('disabled', 'true');
			}else{
				$('#edit-bookmark-tab-form [name=url]').prop('disabled', null);
			}
			generatePreviewBookmarkEditTab();
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Error');
	});
}

function newBookmarkTabForm(){
	generatePreviewBookmarkNewTab();
}

// CHANGE ENABLED TAB
$(document).on("change", ".bookmarkEnabledSwitch", function () {
	var id = $(this).parent().parent().attr("data-id");
	var enabled = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/plugins/bookmark/tabs/' + id, {"enabled":enabled},true).success(function(data) {
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
// CHANGE TAB GROUP
$(document).on("change", ".bookmarkTabGroupSelect", function (event) {
	var id = $(this).parent().parent().attr("data-id");
	var groupID = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/plugins/bookmark/tabs/' + id, {"group_id":groupID},true).success(function(data) {
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
$(document).on("change", ".bookmarkTabCategorySelect", function () {
	var id = $(this).parent().parent().attr("data-id");
	var categoryID = $(this).find("option:selected").val();
	console.log("CategoryID: " + categoryID);
	var callbacks = $.Callbacks();
	organizrAPI2('PUT','api/v2/plugins/bookmark/tabs/' + id, {"category_id":categoryID},true).success(function(data) {
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
//DELETE TAB
$(document).on("click", ".bookmarkDeleteTab", function () {
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
			callbacks.add( buildBookmarkTabEditor );
			organizrAPI2('DELETE','api/v2/plugins/bookmark/tabs/' + id, null,true).success(function(data) {
				message('Tab Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
				if(callbacks){ callbacks.fire(); }
			}).fail(function(xhr) {
				OrganizrApiError(xhr, 'Tab Deleted Error');
			});
		}
	});
});
//EDIT TAB
$(document).on("click", ".editBookmarkTab", function () {
	var originalTabName = $('#originalBookmarkTabName').html();
	var tabInfo = $('#edit-bookmark-tab-form').serializeToJSON();
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
	if (typeof tabInfo.url == 'undefined' || tabInfo.url == '') {
		message('Edit Tab Error',' Please set a Tab URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.background_color == 'undefined' || tabInfo.background_color == '') {
		message('Edit Tab Error',' Please set a Background Color',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.text_color == 'undefined' || tabInfo.text_color == '') {
		message('Edit Tab Error',' Please set a Text Color',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(tabInfo.id !== '' && tabInfo.tabName !== '' && tabInfo.tabImage !== '' && tabInfo.background_color !== '' && tabInfo.text_color !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildBookmarkTabEditor );
		organizrAPI2('PUT','api/v2/plugins/bookmark/tabs/' + tabInfo.id,tabInfo,true).success(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Tab Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#edit-bookmark-tab-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Tab Error');
		});
	}
});
//ADD NEW TAB
$(document).on("click", ".addNewBookmarkTab", function () {
	var tabInfo = $('#new-bookmark-tab-form').serializeToJSON();
	var order = parseInt($('#bookmarkTabEditorTable').find('tr[data-order]').last().attr('data-order')) + 1;
	tabInfo['order'] = isNaN(order) ? 1 : order;

	if (typeof tabInfo.name == 'undefined' || tabInfo.name == '') {
		message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.image == 'undefined' || tabInfo.image == '') {
		message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if ((typeof tabInfo.url == 'undefined' || tabInfo.url == '')) {
		message('Edit Tab Error',' Please set a Tab URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.background_color == 'undefined' || tabInfo.background_color == '') {
		message('Edit Tab Error',' Please set a Background Color',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.text_color == 'undefined' || tabInfo.text_color == '') {
		message('Edit Tab Error',' Please set a Text Color',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(tabInfo.order !== '' && tabInfo.name !== '' && tabInfo.url !== '' && tabInfo.image !== '' && tabInfo.background_color !== '' && tabInfo.text_color !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildBookmarkTabEditor );
		organizrAPI2('POST','api/v2/plugins/bookmark/tabs',tabInfo,true).success(function(data) {
			try {
				var response = data.response;
				$('.bookmarkTabIconImageList').val(null).trigger('change');
				$('.bookmarkTabIconIconList').val(null).trigger('change');
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Tab Created',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#new-bookmark-tab-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Tab Error');
		});
	}
});
// CHANGE TAB ORDER
function submitBookmarkTabOrder(newTabs){
	var data = [];
	var process = false;
	$.each(newTabs.tab, function(i,v) {
		if(v.originalOrder == v.order){
			delete newTabs.tab[i];
		}else{
			let temp = {
				"order":v.order,
				"id":v.id
			}
			data.push(temp);
			process = true;
		}
	})
	if(!process){
		message('Tab Order Warning','Order was not changed - Submission not needed',activeInfo.settings.notifications.position,"#FFF","warning","5000");
		$('.saveBookmarkTabOrderButton').addClass('hidden');
		return false;
	}
	var callbacks = $.Callbacks();
	callbacks.add( buildBookmarkTabEditor );
	organizrAPI2('PUT','api/v2/plugins/bookmark/tabs',data,true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Order Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		$('.saveBookmarkTabOrderButton').addClass('hidden');
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Update Error');
	});
}

$(document).on('change', "#new-bookmark-tab-form-chooseImage", function (e) {
	var newIcon = $('#new-bookmark-tab-form-chooseImage').val();
	if(newIcon !== 'Select or type Icon'){
		$('#new-bookmark-tab-form-inputImage').val(newIcon).change();
	}
});
$(document).on('change', "#edit-bookmark-tab-form-chooseImage", function (e) {
	var newIcon = $('#edit-bookmark-tab-form-chooseImage').val();
	if(newIcon !== 'Select or type Icon'){
		$('#edit-bookmark-tab-form-inputImage').val(newIcon).change();
	}
});
$(document).on('change', "#new-bookmark-tab-form-chooseIcon", function (e) {
	var newIcon = $('#new-bookmark-tab-form-chooseIcon').val();
	if(newIcon !== 'Select or type Icon'){
		$('#new-bookmark-tab-form-inputImage').val(newIcon).change();
	}
});
$(document).on('change', "#edit-bookmark-tab-form-chooseIcon", function (e) {
	var newIcon = $('#edit-bookmark-tab-form-chooseIcon').val();
	if(newIcon !== 'Select or type Icon'){
		$('#edit-bookmark-tab-form-inputImage').val(newIcon).change();
	}
});

// TAB PREVIEWS
function adjustBrightness(hexCode, adjustPercent){
	hexCode = hexCode.replace('#','');
    if(hexCode.length != 6 && hexCode.length != 3) return;
    if(hexCode.length == 3)
   		hexCode = hexCode[0]+hexCode[0]+hexCode[1]+hexCode[1]+hexCode[2]+hexCode[2];
    var result = ['#'];
	for (var i = 0; i < 3; ++i) {
    	var color = parseInt(hexCode[2*i] + hexCode[2*i+1], 16);
        var adjustableLimit = adjustPercent < 0 ? color : 255 - color;
		var adjustAmount = Math.ceil(adjustableLimit * adjustPercent);
        var hex = (color + adjustAmount).toString(16).padStart(2, '0');
        result.push(hex);
    }
	return result.join('');
}

function generatePreview(preview, name, image, colorBg, colorText){
	var result = '<div class="BOOKMARK-category-content"> \
					<a href="#" target="_SELF"> \
						<div class="BOOKMARK-tab" style="border-color: ' + adjustBrightness(colorBg, 0.3) + '; background: linear-gradient(90deg, ' + adjustBrightness(colorBg, -0.3) + ' 0%, ' + colorBg + ' 70%, ' + adjustBrightness(colorBg, 0.1) + ' 100%);"> \
							<span class="BOOKMARK-tab-image">' + iconPrefix(image) + '</span> \
							<span class="BOOKMARK-tab-title" style="color: ' + colorText + ';">' + name + '</span> \
						</div> \
					</a> \
				</div>';

	preview.html(result);
	$(".BOOKMARK-tab-image>img, .BOOKMARK-tab-image>i").removeClass("fa-fw");
}

function generatePreviewBookmarkNewTab(){
	var preview = $('#new-bookmark-preview');
	var name = $('#new-bookmark-tab-form-inputName').val();
	var image = $('#new-bookmark-tab-form-inputImage').val();
	var colorBg = $('#new-bookmark-tab-form-inputBackgroundColor').val();
	var colorText = $('#new-bookmark-tab-form-inputTextColor').val();

	generatePreview(preview, name, image, colorBg, colorText);
}

function generatePreviewBookmarkEditTab(){
	var preview = $('#edit-bookmark-preview');
	var name = $('#edit-bookmark-tab-form-inputName').val();
	var image = $('#edit-bookmark-tab-form-inputImage').val();
	var colorBg = $('#edit-bookmark-tab-form-inputBackgroundColor').val();
	var colorText = $('#edit-bookmark-tab-form-inputTextColor').val();

	generatePreview(preview, name, image, colorBg, colorText);
}

$(document).on('input', "#new-bookmark-tab-form-inputName", generatePreviewBookmarkNewTab);
$(document).on('input change', "#new-bookmark-tab-form-inputImage", generatePreviewBookmarkNewTab);
$(document).on('input', "#new-bookmark-tab-form-inputBackgroundColor", generatePreviewBookmarkNewTab);
$(document).on('input', "#new-bookmark-tab-form-inputTextColor", generatePreviewBookmarkNewTab);

$(document).on('input', "#edit-bookmark-tab-form-inputName", generatePreviewBookmarkEditTab);
$(document).on('input change', "#edit-bookmark-tab-form-inputImage", generatePreviewBookmarkEditTab);
$(document).on('input', "#edit-bookmark-tab-form-inputBackgroundColor", generatePreviewBookmarkEditTab);
$(document).on('input', "#edit-bookmark-tab-form-inputTextColor", generatePreviewBookmarkEditTab);

// CATEGORY MANAGEMENT
function bookmarkCategoriesLaunch(){
	var menuList = `<li onclick="changeSettingsMenu('Settings::Tab Editor::Bookmark Categories');loadSettingsPage2('api/v2/plugins/bookmark/settings_tab_editor_bookmark_categories','#settings-tab-editor-tabs','Tab Editor');" role="presentation"><a id="settings-tab-editor-tabs-anchor" href="#settings-tab-editor-tabs" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-layout-tab-v"></i></span><span class="hidden-xs" lang="en">Bookmark Categories</span></a></li>`;
	$('#settings-main-tab-editor .nav-tabs').append(menuList);
}

function buildBookmarkCategoryEditor(){
	organizrAPI2('GET','api/v2/plugins/bookmark/tabs').success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		$('#bookmarkCategoryEditorTable').html(buildBookmarkCategoryEditorItem(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}

function buildBookmarkCategoryEditorItem(array){
	var categoryList = '';
	$.each(array.categories, function(i,v) {
		var tabCount = array.tabs.reduce(function (n, category) {
			return n + (category.category_id == v.category_id);
		}, 0);
		var disabledDefault = (v.default == 1) ? 'disabled' : '';
		var disabledDelete = (tabCount > 0) ? 'disabled' : '';
		var defaultIcon = (v.default == 1) ? 'icon-user-following' : 'icon-user-follow';
		var defaultColor = (v.default == 1) ? 'btn-info disabled' : 'btn-warning';
		categoryList += `
		<tr class="bookmarkCategoryEditor" data-id="`+v.id+`" data-order="`+v.order+`" data-category-id="`+v.category_id+`" data-name="`+v.category+`" data-default="`+tof(v.default)+`" data-tab-count="`+tabCount+`">
			<input type="hidden" class="form-control order" name="category[`+v.id+`].order" value="`+v.order+`">
			<input type="hidden" class="form-control" name="category[`+v.id+`].originalOrder" value="`+v.order+`">
			<input type="hidden" class="form-control" name="category[`+v.id+`].name" value="`+v.category+`">
			<input type="hidden" class="form-control" name="category[`+v.id+`].id" value="`+v.id+`">
			<td>`+v.category+`</td>
			<td style="text-align:center">`+tabCount+`</td>
			<td style="text-align:center"><button type="button" class="btn `+defaultColor+` btn-outline btn-circle btn-lg m-r-5 changeDefaultBookmarkCategory" `+disabledDefault+`><i class="`+defaultIcon+`"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editBookmarkCategoryButton popup-with-form" href="#edit-bookmark-category-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 deleteBookmarkCategory" `+disabledDelete+`><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return categoryList;
}

//ADD NEW CATEGORY
$(document).on("click", ".addNewBookmarkCategory", function () {
	var categoryInfo = $('#new-bookmark-category-form').serializeToJSON();
	var order = parseInt($('#bookmarkCategoryEditorTable').find('tr[data-order]').last().attr('data-order')) + 1;
	categoryInfo['order'] = isNaN(order) ? 1 : order;

	if (typeof categoryInfo.category == 'undefined' || categoryInfo.category == '') {
		message('Edit Tab Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(categoryInfo.category !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildBookmarkCategoryEditor );
		organizrAPI2('POST','api/v2/plugins/bookmark/categories',categoryInfo,true).success(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Category Added',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#new-bookmark-category-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Category Error');
		});
	}
});
//DELETE CATEGORY
$(document).on("click", ".deleteBookmarkCategory", function () {
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
			callbacks.add( buildBookmarkCategoryEditor );
			organizrAPI2('DELETE','api/v2/plugins/bookmark/categories/' + id, null,true).success(function(data) {
				message('Category Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
				if(callbacks){ callbacks.fire(); }
			}).fail(function(xhr) {
				OrganizrApiError(xhr, 'Category Deleted Error');
			});
		}
	});
});
//EDIT CATEGORY GET ID
$(document).on("click", ".editBookmarkCategoryButton", function () {
	$('#edit-bookmark-category-form [name=category]').val($(this).parent().parent().attr("data-name"));
	$('#edit-bookmark-category-form [name=id]').val($(this).parent().parent().attr("data-id"));
});
//EDIT CATEGORY
$(document).on("click", ".editBookmarkCategory", function () {
	var categoryInfo = $('#edit-bookmark-category-form').serializeToJSON();
	if (typeof categoryInfo.id == 'undefined' || categoryInfo.id == '') {
		message('Edit Tab Error',' Could not get Category ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (typeof categoryInfo.category == 'undefined' || categoryInfo.category == '') {
		message('Edit Tab Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(categoryInfo.id !== '' && categoryInfo.category !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildBookmarkCategoryEditor );
		organizrAPI2('PUT','api/v2/plugins/bookmark/categories/' + categoryInfo.id,categoryInfo,true).success(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#edit-bookmark-category-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Category Error');
		});
	}
});
//CHANGE DEFAULT CATEGORY
$(document).on("click", ".changeDefaultBookmarkCategory", function () {
	var id = $(this).parent().parent().attr("data-id");
	var callbacks = $.Callbacks();
	callbacks.add( buildBookmarkCategoryEditor );
	organizrAPI2('PUT','api/v2/plugins/bookmark/categories/' + id, {"default":1},true).success(function(data) {
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
// CHANGE CATEGORY ORDER
function submitBookmarkCategoryOrder(){
	var data = [];
	var categories = $( "#submit-bookmark-categories-form" ).serializeToJSON();
	var callbacks = $.Callbacks();
	callbacks.add( buildCategoryEditor );
	$.each(categories.category, function(i,v) {
		if(v.originalOrder == v.order){
			delete categories.category[i];
		}else{
			let temp = {
				"order":v.order,
				"id":v.id
			}
			data.push(temp);
		}
	})
	organizrAPI2('PUT','api/v2/plugins/bookmark/categories',data,true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Category Order Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		$('.saveTabOrderButton').addClass('hidden');
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Update Error');
	});
}
