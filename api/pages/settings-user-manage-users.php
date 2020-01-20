<?php

$pageSettingsUserManageUsers = '
<script>
    buildUserManagement();
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
        <span lang="en">MANAGE USERS</span>
        <button type="button" class="btn btn-info btn-circle pull-right popup-with-form" href="#new-user-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover manage-u-table">
            <thead>
                <tr>
                    <th width="70" class="text-center">#</th>
                    <th lang="en">NAME & EMAIL</th>
                    <th lang="en">ADDED</th>
                    <th lang="en">GROUP</th>
                    <th lang="en">EDIT</th>
                    <th lang="en">EMAIL</th>
                    <th lang="en">DELETE</th>
                </tr>
            </thead>
            <tbody id="manageUserTable"></tbody>
        </table>
    </div>
</div>
<form id="new-user-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Add New User</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="new-user-form-inputUsername" lang="en">Username</label>
            <input type="text" class="form-control" id="new-user-form-inputUsername" name="username" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="new-user-form-inputEmail" lang="en">Email</label>
            <input type="email" class="form-control" id="new-user-form-inputEmail" name="email"  required="">
        </div>
        <div class="form-group">
            <label class="control-label" for="new-user-form-inputPassword" lang="en">Password</label>
            <input type="password" class="form-control" id="new-user-form-inputPassword" name="password"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewUser" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add User</span></button>
    <div class="clearfix"></div>
</form>
<form id="edit-user-form" class="mfp-hide white-popup-block mfp-with-anim">
    <input type="hidden" name="id" value="">
    <h1 lang="en">Edit User</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputUsername" lang="en">Username</label>
            <input type="text" class="form-control" id="edit-user-form-inputUsername" name="username" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputEmail" lang="en">Email</label>
            <input type="text" class="form-control" id="edit-user-form-inputEmail" name="email" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputPassword" lang="en">Password</label>
            <input type="password" class="form-control" id="edit-user-form-inputPassword" name="password"  required="">
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputPassword2" lang="en">Password Again</label>
            <input type="password" class="form-control" id="edit-user-form-inputPassword2" name="password2"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editUserAdmin" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit User</span></button>
    <div class="clearfix"></div>
</form>
';
