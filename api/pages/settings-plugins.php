<?php
$GLOBALS['organizrPages'][] = 'settings_plugins';
function get_page_settings_plugins($Organizr)
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
	buildPlugins();
</script>
<div id="main-plugin-area"></div>
<form id="about-plugin-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h2 id="about-plugin-title">Loading...</h2>
    <div class="clearfix"></div>
    <div id="about-plugin-body" class=""></div>
</form>
';
}
