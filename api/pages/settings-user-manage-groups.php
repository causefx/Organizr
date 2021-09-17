<?php
$GLOBALS['organizrPages'][] = 'settings_user_manage_groups';
function get_page_settings_user_manage_groups($Organizr)
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
		$(".groupIconIconList").select2({
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
		
		$(".groupIconImageList").select2({
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
	buildGroupManagement();
	' . $iconSelectors . '
</script>
<div class="panel bg-org panel-info">
	<div class="panel-heading">
		<span lang="en">MANAGE GROUPS</span>
		<button type="button" class="btn btn-info btn-circle pull-right popup-with-form" href="#new-group-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
	</div>
	<div class="table-responsive">
		<table class="table table-hover manage-u-table">
			<thead>
				<tr>
					<th width="70" class="text-center">#</th>
					<th lang="en">GROUP NAME</th>
					<th lang="en">USERS</th>
					<th lang="en">DEFAULT</th>
					<th lang="en">EDIT</th>
					<th lang="en">DELETE</th>
				</tr>
			</thead>
			<tbody id="manageGroupTable"></tbody>
		</table>
	</div>
</div>
<form id="new-group-form" class="mfp-hide white-popup-block mfp-with-anim">
	<h1 lang="en">Add New Group</h1>
	<fieldset style="border:0;">
		<div class="form-group">
			<label class="control-label" for="new-group-form-inputName" lang="en">Group Name</label>
			<input type="text" class="form-control" id="new-group-form-inputName" name="group" required="" autofocus> </div>
			<div class="row">
				<div class="form-group col-lg-4">
					<label class="control-label" for="new-group-form-chooseImage" lang="en">Choose Image</label>
					<select class="form-control groupIconImageList" id="new-group-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
				</div>
				<div class="form-group col-lg-4">
					<label class="control-label" for="new-group-form-chooseIcon" lang="en">Choose Icon</label>
					<select class="form-control groupIconIconList" id="new-group-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
				</div>
				<div class="form-group col-lg-4">
					<label class="control-label" for="new-group-form-chooseBlackberry" lang="en">Choose Blackberry Theme Icon</label>
					<button id="new-group-form-chooseBlackberry" class="btn btn-xs btn-primary waves-effect waves-light form-control" onclick="showBlackberryThemes(\'new-group-form-inputImage\');" type="button">
						<i class="fa fa-search"></i>&nbsp; <span lang="en">Choose</span>
					</button>
				</div>
			</div>
		<div class="form-group">
			<label class="control-label" for="new-group-form-inputImage" lang="en">Group Image</label>
			<input type="text" class="form-control" id="new-group-form-inputImageNew" name="image" required=""> </div>
	</fieldset>
	<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewGroup" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Group</span></button>
	<div class="clearfix"></div>
</form>
<form id="edit-group-form" class="mfp-hide white-popup-block mfp-with-anim">
	<input type="hidden" name="id" value="x">
	<h1 lang="en">Edit Group</h1>
	<fieldset style="border:0;">
		<div class="form-group">
			<label class="control-label" for="edit-group-form-inputEditGroupName" lang="en">Group Name</label>
			<input type="text" class="form-control" id="edit-group-form-inputEditGroupName" name="group" required="" autofocus>
		</div>
		<div class="row">
			<div class="form-group col-lg-4">
				<label class="control-label" for="edit-group-form-chooseImage" lang="en">Choose Image</label>
				<select class="form-control groupIconImageList" id="edit-group-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
			</div>
			<div class="form-group col-lg-4">
				<label class="control-label" for="edit-group-form-chooseIcon" lang="en">Choose Icon</label>
				<select class="form-control groupIconIconList" id="edit-group-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
			</div>
			<div class="form-group col-lg-4">
				<label class="control-label" for="edit-group-form-chooseBlackberry" lang="en">Choose Blackberry Theme Icon</label>
				<button id="edit-group-form-chooseBlackberry" class="btn btn-xs btn-primary waves-effect waves-light form-control" onclick="showBlackberryThemes(\'edit-group-form-inputImage\');" type="button">
					<i class="fa fa-search"></i>&nbsp; <span lang="en">Choose</span>
				</button>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label" for="edit-group-form-inputImage" lang="en">Group Image</label>
			<input type="text" class="form-control" id="edit-group-form-inputImage" name="image"  required="">
		</div>
	</fieldset>
	<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editGroup" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit Group</span></button>
	<div class="clearfix"></div>
</form>
';
}