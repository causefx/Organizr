<?php
$GLOBALS['organizrPages'][] = 'settings_template';
function get_page_settings_template($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	/*
	 * Take this out if you dont care if DB as been created
	 */
	if ((!$Organizr->hasDB())) {
		return false;
	}
	/*
	 * Take this out if you dont want to be for admin only
	 */
	if (!$Organizr->qualifyRequest(1, true)) {
		return false;
	}
	return '
<script>
	// Custom JS here
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
		<span lang="en">Template</span>
	</div>
    <div class="panel-wrapper collapse in" aria-expanded="true">
        <div class="panel-body bg-org">
        </div>
    </div>
</div>
';
}