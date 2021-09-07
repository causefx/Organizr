<?php
$GLOBALS['organizrPages'][] = 'settings_tab_editor_tabs';
function get_page_settings_tab_editor_tabs($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	if ((!$Organizr->hasDB())) {
		return false;
	}
	if (!$Organizr->qualifyRequest(1, true)) {
		return false;
	}
	$iconSelectors = '
		$(".tabIconIconList").select2({
			ajax: {
				url: \'api/v2/icon\',
				data: function (params) {
					var query = {
						search: params.term,
						page: params.page || 1
					}
					return query;
				},
				processResults: function (data, params) {
					params.page = params.page || 1;
					return {
						results: data.response.data.results,
						pagination: {
							more: (params.page * 20) < data.response.data.total
						}
					};
				},
				//cache: true
			},
			placeholder: \'Search for an icon\',
			templateResult: formatIcon,
			templateSelection: formatIcon
		});
		
		$(".tabIconImageList").select2({
			 ajax: {
				url: \'api/v2/image/select\',
				data: function (params) {
					var query = {
						search: params.term,
						page: params.page || 1
					}
					return query;
				},
				processResults: function (data, params) {
					params.page = params.page || 1;
					return {
						results: data.response.data.results,
						pagination: {
							more: (params.page * 20) < data.response.data.total
						}
					};
				},
				//cache: true
			},
			placeholder: \'Search for an image\',
			templateResult: formatImage,
			templateSelection: formatImage
		});
	';
	return '
	<script>
	buildTabEditor();
	//!function(a){function f(a,b){if(!(a.originalEvent.touches.length>1)){a.preventDefault();var c=a.originalEvent.changedTouches[0],d=document.createEvent("MouseEvents");d.initMouseEvent(b,!0,!0,window,1,c.screenX,c.screenY,c.clientX,c.clientY,!1,!1,!1,!1,0,null),a.target.dispatchEvent(d)}}if(a.support.touch="ontouchend"in document,a.support.touch){var e,b=a.ui.mouse.prototype,c=b._mouseInit,d=b._mouseDestroy;b._touchStart=function(a){var b=this;!e&&b._mouseCapture(a.originalEvent.changedTouches[0])&&(e=!0,b._touchMoved=!1,f(a,"mouseover"),f(a,"mousemove"),f(a,"mousedown"))},b._touchMove=function(a){e&&(this._touchMoved=!0,f(a,"mousemove"))},b._touchEnd=function(a){e&&(f(a,"mouseup"),f(a,"mouseout"),this._touchMoved||f(a,"click"),e=!1)},b._mouseInit=function(){var b=this;b.element.bind({touchstart:a.proxy(b,"_touchStart"),touchmove:a.proxy(b,"_touchMove"),touchend:a.proxy(b,"_touchEnd")}),c.call(b)},b._mouseDestroy=function(){var b=this;b.element.unbind({touchstart:a.proxy(b,"_touchStart"),touchmove:a.proxy(b,"_touchMove"),touchend:a.proxy(b,"_touchEnd")}),d.call(b)}}}(jQuery);
	/*$( \'#tabEditorTable\' ).sortable({
		handle: "td:first",
		stop: function () {
			$(\'input.order\').each(function(idx) {
				$(this).val(idx + 1);
			});
			var newTabs = $( "#submit-tabs-form" ).serializeToJSON();
			newTabsGlobal = newTabs;
			$(\'.saveTabOrderButton\').removeClass(\'hidden\');
			//submitTabOrder(newTabs);
		}
	});*/
	let el = document.getElementById(\'tabEditorTable\');
	let tabSorter = new Sortable(el, {
		handle: ".sort-tabs-handle",
		ghostClass: "sortable-ghost",
		multiDrag: true,
		selectedClass: "multi-selected",
		onUpdate: function (evt) {
			$(\'input.order\').each(function(idx) {
				$(this).val(idx + 1);
			});
			var newTabs = $( "#submit-tabs-form" ).serializeToJSON();
			newTabsGlobal = newTabs;
			$(\'.saveTabOrderButton\').removeClass(\'hidden\');
		},
	});
	//$( \'#tabEditorTable\' ).disableSelection();
	' . $iconSelectors . '
	</script>
	<div class="panel bg-org panel-info">
		<div class="panel-heading">
			<span lang="en">Tab Editor</span>
			<button type="button" data-toggle="tooltip" title="Add New Tab" data-placement="bottom" class="btn btn-info btn-circle pull-right popup-with-form m-r-5" href="#new-tab-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
			<button type="button" data-toggle="tooltip" title="Help" data-placement="bottom" class="btn btn-info btn-circle pull-right m-r-5 help-modal" data-modal="tabs"><i class="fa fa-question-circle"></i> </button>
			<button onclick="submitTabOrder(newTabsGlobal)" data-toggle="tooltip" title="Save tab Order" data-placement="bottom" class="btn btn-info btn-circle waves-effect waves-light pull-right animated loop-animation rubberBand m-r-5 saveTabOrderButton hidden" type="button"><i class="fa fa-save"></i></button>
		</div>
		<div class="table-responsive">
			<form id="submit-tabs-form" onsubmit="return false;">
				<table class="table table-hover manage-u-table">
					<thead>
						<tr>
							<th width="20" class="text-center"></th>
							<th width="70" class="text-center"></th>
							<th lang="en">NAME</th>
							<th lang="en">CATEGORY</th>
							<th lang="en">GROUP</th>
							<th lang="en">TYPE</th>
							<th lang="en" style="text-align:center">DEFAULT</th>
							<th lang="en" style="text-align:center">ACTIVE</th>
							<th lang="en" style="text-align:center">SPLASH</th>
							<th lang="en" style="text-align:center">PING</th>
							<th lang="en" style="text-align:center">PRELOAD</th>
							<th lang="en" style="text-align:center">EDIT</th>
							<th lang="en" style="text-align:center">DELETE</th>
						</tr>
					</thead>
					<tbody id="tabEditorTable">
						<td class="text-center" colspan="12"><i class="fa fa-spin fa-spinner"></i></td>
					</tbody>
				</table>
			</form>
		</div>
	</div>
	<form id="new-tab-form" class="mfp-hide white-popup-block mfp-with-anim">
		<h1 lang="en">Add New Tab</h1>
		<fieldset style="border:0;">
			<div class="alert alert-success alert-dismissable tabTestMessage hidden">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<span lang="en">Tab can be set as iFrame</span>
			</div>
			<div class="alert alert-danger alert-dismissable tabTestMessage hidden">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<span lang="en">Please set tab as [New Window] on next screen</span>
			</div>
			<div class="form-group">
				<label class="control-label" for="new-tab-form-inputNameNew" lang="en">Tab Name</label>
				<input type="text" class="form-control" id="new-tab-form-inputNameNew" name="name" required="" autofocus>
			</div>
			<div class="form-group">
				<label class="control-label" for="new-tab-form-inputURLNew" lang="en">Tab URL</label>
				<input type="text" class="form-control" id="new-tab-form-inputURLNew" name="url"  required="">
			</div>
			<div class="form-group">
				<label class="control-label" for="new-tab-form-inputURLLocalNew" lang="en">Tab Local URL</label>
				<input type="text" class="form-control" id="new-tab-form-inputURLLocalNew" name="url_local">
			</div>
			<div class="form-group">
				<label class="control-label" for="new-tab-form-inputPingURLNew" lang="en">Ping URL</label>
				<input type="text" class="form-control" id="new-tab-form-inputPingURLNew" name="ping_url"  placeholder="host/ip:port">
			</div>
			<div class="row">
				<div class="form-group col-lg-6">
					<label class="control-label" for="new-tab-form-inputTabActionTypeNew" lang="en">Tab Auto Action</label>
						<select class="form-control" id="new-tab-form-inputTabActionTypeNew" name="timeout">
							<option value="null">None</option>
							<option value="1">Auto Close</option>
							<option value="2">Auto Reload</option>
						</select>
				</div>
				<div class="form-group col-lg-6">
					<label class="control-label" for="new-tab-form-inputTabActionTimeNew" lang="en">Tab Auto Action Minutes</label>
						<input type="number" class="form-control" id="new-tab-form-inputTabActionTimeNew" name="timeout_ms"  placeholder="0">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-lg-4">
					<label class="control-label" for="new-tab-form-chooseImage" lang="en">Choose Image</label>
					<select class="form-control tabIconImageList" id="new-tab-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
				</div>
				<div class="form-group col-lg-4">
					<label class="control-label" for="new-tab-form-chooseIcon" lang="en">Choose Icon</label>
					<select class="form-control tabIconIconList" id="new-tab-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
				</div>
				<div class="form-group col-lg-4">
					<label class="control-label" for="new-tab-form-chooseBlackberry" lang="en">Choose Blackberry Theme Icon</label>
					<button id="new-tab-form-chooseBlackberry" class="btn btn-xs btn-primary waves-effect waves-light form-control" onclick="showBlackberryThemes(\'new-tab-form-inputImageNew\');" type="button">
						<i class="fa fa-search"></i>&nbsp; <span lang="en">Choose</span>
					</button>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label" for="new-tab-form-inputImageNew" lang="en">Tab Image</label>
				<input type="text" class="form-control" id="new-tab-form-inputImageNew" name="image"  required="">
			</div>
		</fieldset>
		<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light row b-none testTab" type="button"><span class="btn-label"><i class="fa fa-flask"></i></span><span lang="en">Test Tab</span></button>
		<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewTab" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Tab</span></button>
		<div class="clearfix"></div>
	</form>
	<form id="edit-tab-form" class="mfp-hide white-popup-block mfp-with-anim">
		<input type="hidden" name="id" value="x">
		<span class="hidden" id="originalTabName"></span>
		<h1 lang="en">Edit Tab</h1>
		<fieldset style="border:0;">
			<div class="alert alert-success alert-dismissable tabEditTestMessage hidden">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<span lang="en">Tab can be set as iFrame</span>
			</div>
			<div class="alert alert-danger alert-dismissable tabEditTestMessage hidden">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<span lang="en">Please set tab as [New Window] on next screen</span>
			</div>
			<div class="form-group">
				<label class="control-label" for="edit-tab-form-inputName" lang="en">Tab Name</label>
				<input type="text" class="form-control" id="edit-tab-form-inputName" name="name" required="" autofocus>
			</div>
			<div class="form-group">
				<label class="control-label" for="edit-tab-form-inputURL" lang="en">Tab URL</label>
				<input type="text" class="form-control" id="edit-tab-form-inputURL" name="url"  required="">
			</div>
			<div class="form-group">
				<label class="control-label" for="edit-tab-form-inputLocalURL" lang="en">Tab Local URL</label>
				<input type="text" class="form-control" id="edit-tab-form-inputLocalURL" name="url_local">
			</div>
			<div class="form-group">
				<label class="control-label" for="edit-tab-form-pingURL" lang="en">Ping URL</label>
				<input type="text" class="form-control" id="edit-tab-form-pingURL" name="ping_url" placeholder="host/ip:port">
			</div>
			<div class="row">
				<div class="form-group col-lg-6">
					<label class="control-label" for="edit-tab-form-inputTabActionTypeNew" lang="en">Tab Auto Action</label>
						<select class="form-control" id="edit-tab-form-inputTabActionTypeNew" name="timeout">
							<option value="null">None</option>
							<option value="1">Auto Close</option>
							<option value="2">Auto Reload</option>
						</select>
				</div>
				<div class="form-group col-lg-6">
					<label class="control-label" for="edit-tab-form-inputTabActionTimeNew" lang="en">Tab Auto Action Minutes</label>
						<input type="number" class="form-control" id="edit-tab-form-inputTabActionTimeNew" name="timeout_ms">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-lg-4">
					<label class="control-label" for="edit-tab-form-chooseImage" lang="en">Choose Image</label>
					<select class="form-control tabIconImageList" id="edit-tab-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
				</div>
				<div class="form-group col-lg-4">
					<label class="control-label" for="edit-tab-form-chooseIcon" lang="en">Choose Icon</label>
					<select class="form-control tabIconIconList" id="edit-tab-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
				</div>
				<div class="form-group col-lg-4">
					<label class="control-label" for="edit-tab-form-chooseBlackberry" lang="en">Choose Blackberry Theme Icon</label>
					<button id="edit-tab-form-chooseBlackberry" class="btn btn-xs btn-primary waves-effect waves-light form-control" onclick="showBlackberryThemes(\'edit-tab-form-inputImage\');" type="button">
						<i class="fa fa-search"></i>&nbsp; <span lang="en">Choose</span>
					</button>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label" for="edit-tab-form-inputImage" lang="en">Tab Image</label>
				<input type="text" class="form-control" id="edit-tab-form-inputImage" name="image"  required="">
			</div>
		</fieldset>
		<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light row b-none testEditTab" type="button"><span class="btn-label"><i class="fa fa-flask"></i></span><span lang="en">Test Tab</span></button>
		<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editTab" type="button"><span class="btn-label"><i class="fa fa-check"></i></span><span lang="en">Edit Tab</span></button>
		<div class="clearfix"></div>
	</form>
	';
}