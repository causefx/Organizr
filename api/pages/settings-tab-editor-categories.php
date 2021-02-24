<?php
$GLOBALS['organizrPages'][] = 'settings_tab_editor_categories';
function get_page_settings_tab_editor_categories($Organizr)
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
		$(".categoryIconIconList").select2({
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
		
		$(".categoryIconImageList").select2({
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
buildCategoryEditor();
$( \'#categoryEditorTable\' ).sortable({
	stop: function () {
		var inputs = $(\'input.order\');
		var nbElems = inputs.length;
		inputs.each(function(idx) {
			$(this).val(idx + 1);
		});
		submitCategoryOrder();
	}
});
' . $iconSelectors . '
</script>
<div class="panel bg-org panel-info">
	<div class="panel-heading">
		<span lang="en">Category Editor</span>
		<button type="button" class="btn btn-success btn-circle pull-right popup-with-form m-r-5" href="#new-category-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
	</div>
	<div class="table-responsive">
		<form id="submit-categories-form" onsubmit="return false;">
			<table class="table table-hover manage-u-table">
				<thead>
					<tr>
						<th width="70" class="text-center">#</th>
						<th lang="en">NAME</th>
						<th lang="en" style="text-align:center">TABS</th>
						<th lang="en" style="text-align:center">DEFAULT</th>
						<th lang="en" style="text-align:center">EDIT</th>
						<th lang="en" style="text-align:center">DELETE</th>
					</tr>
				</thead>
				<tbody id="categoryEditorTable"><td class="text-center" colspan="6"><i class="fa fa-spin fa-spinner"></i></td></tbody>
			</table>
		</form>
	</div>
</div>
<form id="new-category-form" class="mfp-hide white-popup-block mfp-with-anim">
	<h1 lang="en">Add New Category</h1>
	<fieldset style="border:0;">
		<div class="form-group">
			<label class="control-label" for="new-category-form-inputNameNew" lang="en">Category Name</label>
			<input type="text" class="form-control" id="new-category-form-inputNameNew" name="category" required="" autofocus>
		</div>
		<div class="row">
			<div class="form-group col-lg-6">
				<label class="control-label" for="new-category-form-chooseImage" lang="en">Choose Image</label>
				<select class="form-control categoryIconImageList" id="new-category-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
			</div>
			<div class="form-group col-lg-6">
				<label class="control-label" for="new-category-form-chooseIcon" lang="en">Choose Icon</label>
				<select class="form-control categoryIconIconList" id="new-category-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label" for="new-category-form-inputImageNew" lang="en">Category Image</label>
			<input type="text" class="form-control" id="new-category-form-inputImageNew" name="image"  required="">
		</div>
	</fieldset>
	<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewCategory" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Category</span></button>
	<div class="clearfix"></div>
</form>
<form id="edit-category-form" class="mfp-hide white-popup-block mfp-with-anim">
	<input type="hidden" name="id" value="">
	<h1 lang="en">Edit Category</h1>
	<fieldset style="border:0;">
		<div class="form-group">
			<label class="control-label" for="edit-category-form-inputName" lang="en">Category Name</label>
			<input type="text" class="form-control" id="edit-category-form-inputName" name="category" required="" autofocus>
		</div>
		<div class="row">
			<div class="form-group col-lg-6">
				<label class="control-label" for="edit-category-form-chooseImage" lang="en">Choose Image</label>
				<select class="form-control categoryIconImageList" id="edit-category-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
			</div>
			<div class="form-group col-lg-6">
				<label class="control-label" for="edit-category-form-chooseIcon" lang="en">Choose Icon</label>
				<select class="form-control categoryIconIconList" id="edit-category-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label" for="edit-category-form-inputImage" lang="en">Category Image</label>
			<input type="text" class="form-control" id="edit-category-form-inputImage" name="image"  required="">
		</div>
	</fieldset>
	<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editCategory" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit Category</span></button>
	<div class="clearfix"></div>
</form>
';
}