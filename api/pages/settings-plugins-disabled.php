<?php
$GLOBALS['organizrPages'][] = 'settings_plugins_disabled';
function get_page_settings_plugins_disabled($Organizr)
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
	buildPlugins("disabled");
</script>
<div id="disabled-plugin-area"></div>
';
}