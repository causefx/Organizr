<?php
function installTheme($theme)
{
	$name = $theme['data']['theme']['name'];
	$version = $theme['data']['theme']['version'];
	foreach ($theme['data']['theme']['downloadList'] as $k => $v) {
		$file = array(
			'from' => $v['githubPath'],
			'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'] . $v['fileName']),
			'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'])
		);
		if (!downloadFileToPath($file['from'], $file['to'], $file['path'])) {
			writeLog('error', 'Theme Function -  Downloaded File Failed  for: ' . $v['githubPath'], $GLOBALS['organizrUser']['username']);
			return false;
		}
	}
	updateConfig(
		array(
			'themeInstalled' => $name,
			'themeVersion' => $version,
		)
	);
	return true;
}

function removeTheme($theme)
{
	$name = $theme['data']['theme']['name'];
	$version = $theme['data']['theme']['version'];
	foreach ($theme['data']['theme']['downloadList'] as $k => $v) {
		$file = array(
			'from' => $v['githubPath'],
			'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'] . $v['fileName']),
			'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'])
		);
		if (!rrmdir($file['to'])) {
			writeLog('error', 'Theme Function -  Remove File Failed  for: ' . $v['githubPath'], $GLOBALS['organizrUser']['username']);
			return false;
		}
	}
	if ($GLOBALS['themeInstalled'] !== '') {
		$installedTheme = $GLOBALS['themeInstalled'];
		if ($installedTheme == $name) {
			updateConfig(
				array(
					'themeInstalled' => '',
					'themeVersion' => '',
				)
			);
		}
	}
	return true;
}