<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
$pageSettingsPlugins = '
<script>
	buildPlugins();
</script>
<div id="main-plugin-area"></div>
';
}
