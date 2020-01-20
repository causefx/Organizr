<?php
$pageSettingsSettingsSSO = '
<script>
	buildSSO();
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Single Sign-On</span>
		<button id="sso-form-save" onclick="submitSettingsForm(\'sso-form\')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-org">
            <form id="sso-form" class="addFormTick" onsbumit="return false;"></form>
        </div>
    </div>
</div>
<form id="sso-plex-token-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Get Plex Token</h1>
    <div class="panel ssoPlexTokenHeader">
        <div class="panel-heading ssoPlexTokenMessage" lang="en">Enter Plex Details</div>
    </div>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="sso-plex-token-form-username" lang="en">Plex Username</label>
            <input type="text" class="form-control" id="sso-plex-token-form-username" name="username" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="sso-plex-token-form-password" lang="en">Plex Password</label>
            <input type="password" class="form-control" id="sso-plex-token-form-password" name="password"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none getSSOPlexToken" type="button"><span class="btn-label"><i class="fa fa-ticket"></i></span><span lang="en">Grab It</span></button>
    <div class="clearfix"></div>
</form>
<form id="sso-plex-machine-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Get Plex Machine</h1>
    <div class="panel ssoPlexMachineHeader">
        <div class="panel-heading ssoPlexMachineMessage" lang="en"></div>
    </div>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="sso-plex-machine-form-machine" lang="en">Plex Machine</label>
            <div class="ssoPlexMachineListing"></div>
        </div>
    </fieldset>
    <div class="clearfix"></div>
</form>
';
