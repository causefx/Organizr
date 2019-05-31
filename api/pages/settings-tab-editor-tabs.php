<?php
$pageSettingsTabEditorTabs = '
<script>
buildTabEditor();
$( \'#tabEditorTable\' ).sortable({
    stop: function () {
        $(\'input.order\').each(function(idx) {
            $(this).val(idx + 1);
        });
        var newTabs = $( "#submit-tabs-form" ).serializeToJSON();
        submitTabOrder(newTabs);
    }
});
$(".tabIconImageList").select2({
	templateResult: formatImage,
	templateSelection: formatImage,
});
allIcons().success(function(data) {
    $(".tabIconIconList").select2({
		data: data,
		templateResult: formatIcon,
		templateSelection: formatIcon,
	});
});

</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
        <span lang="en">Tab Editor</span>
        <button type="button" class="btn btn-info btn-circle pull-right popup-with-form m-r-5" href="#new-tab-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
        <button type="button" class="btn btn-info btn-circle pull-right m-r-5 help-modal" data-modal="tabs"><i class="fa fa-question-circle"></i> </button>
    </div>
    <div class="table-responsive">
        <form id="submit-tabs-form" onsubmit="return false;">
            <table class="table table-hover manage-u-table">
                <thead>
                    <tr>
                        <th width="70" class="text-center">#</th>
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
                <tbody id="tabEditorTable"></tbody>
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
            <input type="text" class="form-control" id="new-tab-form-inputNameNew" name="tabName" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="new-tab-form-inputURLNew" lang="en">Tab URL</label>
            <input type="text" class="form-control" id="new-tab-form-inputURLNew" name="tabURL"  required="">
        </div>
        <div class="form-group">
            <label class="control-label" for="new-tab-form-inputURLLocalNew" lang="en">Tab Local URL</label>
            <input type="text" class="form-control" id="new-tab-form-inputURLLocalNew" name="tabLocalURL">
        </div>
        <div class="form-group">
            <label class="control-label" for="new-tab-form-inputPingURLNew" lang="en">Ping URL</label>
            <input type="text" class="form-control" id="new-tab-form-inputPingURLNew" name="pingURL"  placeholder="host/ip:port">
        </div>
        <div class="row">
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="new-tab-form-inputTabActionTypeNew" lang="en">Tab Auto Action</label>
	                <select class="form-control" id="new-tab-form-inputTabActionTypeNew" name="tabActionType">
	                    <option value="null">None</option>
	                    <option value="1">Auto Close</option>
	                    <option value="2">Auto Reload</option>
					</select>
	        </div>
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="new-tab-form-inputTabActionTimeNew" lang="en">Tab Auto Action Minutes</label>
	                <input type="number" class="form-control" id="new-tab-form-inputTabActionTimeNew" name="tabActionTime"  placeholder="0">
	        </div>
	    </div>
        <div class="row">
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="new-tab-form-chooseImage" lang="en">Choose Image</label>
	            ' . imageSelect("new-tab-form") . '
	        </div>
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="new-tab-form-chooseIcon" lang="en">Choose Icon</label>
				<select class="form-control tabIconIconList" id="new-tab-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
	        </div>
	    </div>
        <div class="form-group">
            <label class="control-label" for="new-tab-form-inputImageNew" lang="en">Tab Image</label>
            <input type="text" class="form-control" id="new-tab-form-inputImageNew" name="tabImage"  required="">
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
            <input type="text" class="form-control" id="edit-tab-form-inputName" name="tabName" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-tab-form-inputURL" lang="en">Tab URL</label>
            <input type="text" class="form-control" id="edit-tab-form-inputURL" name="tabURL"  required="">
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-tab-form-inputLocalURL" lang="en">Tab Local URL</label>
            <input type="text" class="form-control" id="edit-tab-form-inputLocalURL" name="tabLocalURL">
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-tab-form-pingURL" lang="en">Ping URL</label>
            <input type="text" class="form-control" id="edit-tab-form-pingURL" name="pingURL" placeholder="host/ip:port">
        </div>
        <div class="row">
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="edit-tab-form-inputTabActionTypeNew" lang="en">Tab Auto Action</label>
	                <select class="form-control" id="edit-tab-form-inputTabActionTypeNew" name="tabActionType">
	                    <option value="null">None</option>
	                    <option value="1">Auto Close</option>
	                    <option value="2">Auto Reload</option>
					</select>
	        </div>
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="edit-tab-form-inputTabActionTimeNew" lang="en">Tab Auto Action Minutes</label>
	                <input type="number" class="form-control" id="edit-tab-form-inputTabActionTimeNew" name="tabActionTime">
	        </div>
	    </div>
        <div class="row">
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="edit-tab-form-chooseImage" lang="en">Choose Image</label>
	            ' . imageSelect("edit-tab-form") . '
	        </div>
	        <div class="form-group col-lg-6">
	            <label class="control-label" for="edit-tab-form-chooseIcon" lang="en">Choose Icon</label>
				<select class="form-control tabIconIconList" id="edit-tab-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
	        </div>
	    </div>
        <div class="form-group">
            <label class="control-label" for="edit-tab-form-inputImage" lang="en">Tab Image</label>
            <input type="text" class="form-control" id="edit-tab-form-inputImage" name="tabImage"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light row b-none testEditTab" type="button"><span class="btn-label"><i class="fa fa-flask"></i></span><span lang="en">Test Tab</span></button>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editTab" type="button"><span class="btn-label"><i class="fa fa-check"></i></span><span lang="en">Edit Tab</span></button>
    <div class="clearfix"></div>
</form>
';
