<?php
$pageSettingsSettingsMain = '
<script>
	buildSettingsMain();
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Organizr Settings</span>
		<button id="settings-main-form-save" onclick="submitSettingsForm(\'settings-main-form\')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-org">
            <form id="settings-main-form" class="addFormTick" onsbumit="return false;"></form>
        </div>
    </div>
</div>
<form id="auth-plex-token-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Get Plex Token</h1>
    <div class="panel authPlexTokenHeader">
        <div class="panel-heading authPlexTokenMessage" lang="en">Enter Plex Details</div>
    </div>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="auth-plex-token-form-username" lang="en">Plex Username</label>
            <input type="text" class="form-control" id="auth-plex-token-form-username" name="username" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="auth-plex-token-form-password" lang="en">Plex Password</label>
            <input type="password" class="form-control" id="auth-plex-token-form-password" name="password"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none getauthPlexToken" type="button"><span class="btn-label"><i class="fa fa-ticket"></i></span><span lang="en">Grab It</span></button>
    <div class="clearfix"></div>
</form>
<form id="auth-plex-machine-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Get Plex Machine</h1>
    <div class="panel authPlexMachineHeader">
        <div class="panel-heading authPlexMachineMessage" lang="en"></div>
    </div>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="auth-plex-machine-form-machine" lang="en">Plex Machine</label>
            <div class="authPlexMachineListing"></div>
        </div>
    </fieldset>
    <div class="clearfix"></div>
</form>
';
