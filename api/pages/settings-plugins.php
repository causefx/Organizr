<?php
if (file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
	$pageSettingsPlugins = '
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
