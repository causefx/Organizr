<?php

// ===================================
// Organizr Version
$GLOBALS['installedVersion'] = '2.0.0-alpha.800';
// ===================================
// Set GLOBALS from config file
$GLOBALS['userConfigPath'] = dirname(__DIR__,1).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
$GLOBALS['defaultConfigPath'] = dirname(__DIR__,1).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'default.php';
$GLOBALS['currentTime'] = gmdate("Y-m-d\TH:i:s\Z");
// Quick function for plugins
function pluginFiles($type){
	$files = '';
	switch ($type) {
		case 'js':
			foreach (glob(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'plugins' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . "*.js") as $filename){
				$files .= '<script src="api/plugins/js/'.basename($filename).'?v='.$GLOBALS['installedVersion'].'" defer="true"></script>';
			}
			break;
		case 'css':
			foreach (glob(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'plugins' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . "*.js") as $filename){
				$files .= '<link href="api/plugins/css/'.basename($filename).$GLOBALS['installedVersion'].'" rel="stylesheet">';
			}
			break;
		default:
			break;
	}
	return $files;
}
