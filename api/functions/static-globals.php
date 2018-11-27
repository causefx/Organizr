<?php
// ===================================
// Organizr Version
$GLOBALS['installedVersion'] = '2.0.0-beta.800';
// ===================================
// Quick php Version check
$GLOBALS['minimumPHP'] = '7.0.0';
if (!(version_compare(PHP_VERSION, $GLOBALS['minimumPHP']) >= 0)) {
	die('Organizr needs PHP Version: ' . $GLOBALS['minimumPHP'] . '<br/> You have PHP Version: ' . PHP_VERSION);
}
// Set GLOBALS from config file
$GLOBALS['userConfigPath'] = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
$GLOBALS['defaultConfigPath'] = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'default.php';
$GLOBALS['currentTime'] = gmdate("Y-m-d\TH:i:s\Z");
// Quick function for plugins
function pluginFiles($type)
{
	$files = '';
	switch ($type) {
		case 'js':
			foreach (glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . "*.js") as $filename) {
				$files .= '<script src="api/plugins/js/' . basename($filename) . '?v=' . $GLOBALS['installedVersion'] . '" defer="true"></script>';
			}
			break;
		case 'css':
			foreach (glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . "*.js") as $filename) {
				$files .= '<link href="api/plugins/css/' . basename($filename) . $GLOBALS['installedVersion'] . '" rel="stylesheet">';
			}
			break;
		default:
			break;
	}
	return $files;
}

function loadConfigOnce($path = null)
{
	$path = ($path) ? $path : $GLOBALS['userConfigPath'];
	if (!is_file($path)) {
		return null;
	} else {
		return (array)call_user_func(function () use ($path) {
			return include($path);
		});
	}
}

function favIcons()
{
	$favicon = '
	<link rel="apple-touch-icon" sizes="180x180" href="plugins/images/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="plugins/images/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="plugins/images/favicon/favicon-16x16.png">
	<link rel="manifest" href="plugins/images/favicon/site.webmanifest">
	<link rel="mask-icon" href="plugins/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="plugins/images/favicon/favicon.ico">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="msapplication-TileImage" content="plugins/images/favicon/mstile-144x144.png">
	<meta name="msapplication-config" content="plugins/images/favicon/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	';
	if (file_exists($GLOBALS['userConfigPath'])) {
		$config = loadConfigOnce($GLOBALS['userConfigPath']);
		if (isset($config['favIcon'])) {
			if ($config['favIcon'] !== '') {
				$favicon = $config['favIcon'];
			}
		}
	}
	return $favicon;
}

function languagePacks($encode = false)
{
	$files = array();
	foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'langpack' . DIRECTORY_SEPARATOR . "*.json") as $filename) {
		if (strpos(basename($filename), '[') !== false) {
			$explode = explode('[', basename($filename));
			$files[] = array(
				'filename' => basename($filename),
				'code' => $explode[0],
				'language' => matchBrackets(basename($filename))
			);
		}
	}
	usort($files, function ($a, $b) {
		return $a['language'] <=> $b['language'];
	});
	return ($encode) ? json_encode($files) : $files;
}

function matchBrackets($text, $brackets = 's')
{
	switch ($brackets) {
		case 's':
		case 'square':
			$pattern = '#\[(.*?)\]#';
			break;
		case 'c':
		case 'curly':
			$pattern = '#\((.*?)\)#';
			break;
		default:
			return null;
	}
	preg_match($pattern, $text, $match);
	return $match[1];
}