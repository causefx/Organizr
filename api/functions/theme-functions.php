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
	if ($GLOBALS['installedThemes'] !== '') {
		$installedThemes = explode('|', $GLOBALS['installedThemes']);
		foreach ($installedThemes as $k => $v) {
			$themes = explode(':', $v);
			$installedThemesList[$themes[0]] = $themes[1];
		}
		if (isset($installedThemesList[$name])) {
			$installedThemesList[$name] = $version;
			$installedThemesNew = '';
			foreach ($installedThemesList as $k => $v) {
				if ($installedThemesNew == '') {
					$installedThemesNew .= $k . ':' . $v;
				} else {
					$installedThemesNew .= '|' . $k . ':' . $v;
				}
			}
		} else {
			$installedThemesNew = $GLOBALS['installedThemes'] . '|' . $name . ':' . $version;
		}
	} else {
		$installedThemesNew = $name . ':' . $version;
	}
	updateConfig(array('installedThemes' => $installedThemesNew));
	return 'Success!@!' . $installedThemesNew;
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
	if ($GLOBALS['installedThemes'] !== '') {
		$installedThemes = explode('|', $GLOBALS['installedThemes']);
		foreach ($installedThemes as $k => $v) {
			$themes = explode(':', $v);
			$installedThemesList[$themes[0]] = $themes[1];
		}
		if (isset($installedThemesList[$name])) {
			$installedThemesNew = '';
			foreach ($installedThemesList as $k => $v) {
				if ($k !== $name) {
					if ($installedThemesNew == '') {
						$installedThemesNew .= $k . ':' . $v;
					} else {
						$installedThemesNew .= '|' . $k . ':' . $v;
					}
				}
			}
		}
	} else {
		$installedThemesNew = '';
	}
	updateConfig(array('installedThemes' => $installedThemesNew));
	return 'Success!@!' . $installedThemesNew;
}