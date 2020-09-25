<?php
$GLOBALS['organizrPages'][] = 'settings_tab_editor_homepage';
function get_page_settings_tab_editor_homepage($Organizr)
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
	buildHomepage();
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Homepage Items</span>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-org" >
        	<div class="row el-element-overlay m-b-40" id="settings-homepage-list">
        		<div class="text-center"><i class="fa fa-spin fa-spinner fa-3x"></i></div>
			</div>
        </div>
    </div>
</div>

';
}
