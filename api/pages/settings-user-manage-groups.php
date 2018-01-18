<?php

$pageSettingsUserManageGroups = '
<script>
    buildGroupManagement();
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
    <input type="hidden" id="newGroupID" name="groupID" value="0">
    <input type="hidden" name="groupDefault" value="0" required="">
    <h1 lang="en">Add New Group</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="new-group-form-inputName" lang="en">Group Name</label>
            <input type="text" class="form-control" id="new-group-form-inputName" name="groupName" required="" autofocus> </div>
        <div class="form-group">
            <label class="control-label" for="new-group-form-inputImage" lang="en">Group Image</label>
            <input type="text" class="form-control" id="new-group-form-inputImage" name="groupImage" required=""> </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewGroup" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Group</span></button>
    <div class="clearfix"></div>
</form>
<form id="edit-group-form" class="mfp-hide white-popup-block mfp-with-anim">
    <input type="hidden" name="id" value="x">
    <input type="hidden" name="oldGroupName" value="x">
    <h1 lang="en">Edit Group</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="edit-group-form-inputEditGroupName" lang="en">Group Name</label>
            <input type="text" class="form-control" id="edit-group-form-inputEditGroupName" name="groupName" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-group-form-inputEditGroupImage" lang="en">Group Image</label>
            <input type="text" class="form-control" id="edit-group-form-inputEditGroupImage" name="groupImage"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editGroup" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit Group</span></button>
    <div class="clearfix"></div>
</form>
';
