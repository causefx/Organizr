<?php
function installPlugin($plugin)
{
	$name = $plugin['data']['plugin']['name'];
	$version = $plugin['data']['plugin']['version'];
	foreach ($plugin['data']['plugin']['downloadList'] as $k => $v) {
		$file = array(
			'from' => $v['githubPath'],
			'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'] . $v['fileName']),
			'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'])
		);
		if (!downloadFileToPath($file['from'], $file['to'], $file['path'])) {
			writeLog('error', 'Plugin Function -  Downloaded File Failed  for: ' . $v['githubPath'], $GLOBALS['organizrUser']['username']);
			return false;
		}
	}
	if ($GLOBALS['installedPlugins'] !== '') {
		$installedPlugins = explode('|', $GLOBALS['installedPlugins']);
		foreach ($installedPlugins as $k => $v) {
			$plugins = explode(':', $v);
			$installedPluginsList[$plugins[0]] = $plugins[1];
		}
		if (isset($installedPluginsList[$name])) {
			$installedPluginsList[$name] = $version;
			$installedPluginsNew = '';
			foreach ($installedPluginsList as $k => $v) {
				if ($installedPluginsNew == '') {
					$installedPluginsNew .= $k . ':' . $v;
				} else {
					$installedPluginsNew .= '|' . $k . ':' . $v;
				}
			}
		} else {
			$installedPluginsNew = $GLOBALS['installedPlugins'] . '|' . $name . ':' . $version;
		}
	} else {
		$installedPluginsNew = $name . ':' . $version;
	}
	updateConfig(array('installedPlugins' => $installedPluginsNew));
	return 'Success!@!' . $installedPluginsNew;
}

function removePlugin($plugin)
{
	$name = $plugin['data']['plugin']['name'];
	$version = $plugin['data']['plugin']['version'];
	foreach ($plugin['data']['plugin']['downloadList'] as $k => $v) {
		$file = array(
			'from' => $v['githubPath'],
			'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'] . $v['fileName']),
			'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'])
		);
		if (!rrmdir($file['to'])) {
			writeLog('error', 'Plugin Function -  Remove File Failed  for: ' . $v['githubPath'], $GLOBALS['organizrUser']['username']);
			return false;
		}
	}
	if ($GLOBALS['installedPlugins'] !== '') {
		$installedPlugins = explode('|', $GLOBALS['installedPlugins']);
		foreach ($installedPlugins as $k => $v) {
			$plugins = explode(':', $v);
			$installedPluginsList[$plugins[0]] = $plugins[1];
		}
		if (isset($installedPluginsList[$name])) {
			$installedPluginsNew = '';
			foreach ($installedPluginsList as $k => $v) {
				if ($k !== $name) {
					if ($installedPluginsNew == '') {
						$installedPluginsNew .= $k . ':' . $v;
					} else {
						$installedPluginsNew .= '|' . $k . ':' . $v;
					}
				}
			}
		}
	} else {
		$installedPluginsNew = '';
	}
	updateConfig(array('installedPlugins' => $installedPluginsNew));
	return 'Success!@!' . $installedPluginsNew;
}