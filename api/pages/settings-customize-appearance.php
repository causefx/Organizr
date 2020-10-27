<?php
$GLOBALS['organizrPages'][] = 'settings_customize_appearance';
function get_page_settings_customize_appearance($Organizr)
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
	buildCustomizeAppearance();
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Customize Appearance</span>
		<button type="button" id="customize-appearance-reload" class="btn btn-primary btn-circle pull-right reload hidden m-r-5"><i class="fa fa-spin fa-refresh"></i> </button>
		<button id="customize-appearance-form-save" onclick="submitSettingsForm(\'customize-appearance-form\')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-org">
            <form id="customize-appearance-form" class="addFormTick" onsubmit="return false;"></form>
        </div>
    </div>
</div>
';
}
