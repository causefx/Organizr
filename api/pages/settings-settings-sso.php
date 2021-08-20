<?php
$GLOBALS['organizrPages'][] = 'settings_settings_sso';
function get_page_settings_settings_sso($Organizr)
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
	return '
<script>
	buildSSO();
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Single Sign-On</span>
		<button id="sso-form-save" onclick="submitSettingsForm(\'sso-form\')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="bg-org">
            <form id="sso-form" class="addFormTick" onsubmit="return false;"></form>
        </div>
    </div>
</div>
';
}