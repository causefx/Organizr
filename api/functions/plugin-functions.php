<?php
function installPlugin($plugin)
{
	//$array['data']['action']
	/*
	 *
	 * if (downloadFileToPath($from, $to, $path)) {
                writeLog('success', 'Update Function -  Downloaded Update File for Branch: '.$branch, $GLOBALS['organizrUser']['username']);
                return true;
            } else {
                writeLog('error', 'Update Function -  Downloaded Update File Failed  for Branch: '.$branch, $GLOBALS['organizrUser']['username']);
                return false;
            }
	 */
	foreach ($plugin['data']['plugin']['downloadList'] as $k => $v) {
		$file = array(
			'from' => $v['githubPath'],
			'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'] . $v['fileName']),
			'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $GLOBALS['root'] . $v['path'])
		);
		if (!downloadFileToPath($file['from'], $file['to'], $file['path'])) {
			writeLog('error', 'Update Function -  Downloaded File Failed  for: ' . $v['githubPath'], $GLOBALS['organizrUser']['username']);
			return false;
		}
	}
	return true;
}