<?php
function fileArray($files)
{
	foreach ($files as $file) {
		if (file_exists($file)) {
			$list[] = $file;
		}
	}
	if (!empty($list)) {
		return $list;
	}
}

function backupDB($type = 'config')
{
	$directory = $GLOBALS['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
	@mkdir($directory, 0770, true);
	switch ($type) {
		case 'config':
			break;
		case 'full':
			break;
		default:
		
	}
	$orgFiles = array(
		'orgLog' => $GLOBALS['organizrLog'],
		'loginLog' => $GLOBALS['organizrLoginLog'],
		'config' => $GLOBALS['userConfigPath'],
		'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName']
	);
	$files = fileArray($orgFiles);
	if (!empty($files)) {
		writeLog('success', 'BACKUP: backup process started', 'SYSTEM');
		$zipname = $directory . 'backup[' . date('Y-m-d_H-i') . '][' . $GLOBALS['installedVersion'] . '].zip';
		$zip = new ZipArchive;
		$zip->open($zipname, ZipArchive::CREATE);
		foreach ($files as $file) {
			$zip->addFile($file);
		}
		$zip->close();
		writeLog('success', 'BACKUP: backup process finished', 'SYSTEM');
		return true;
	} else {
		return false;
	}
	
}

function getBackups()
{
	$path = $GLOBALS['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
	@mkdir($path, 0770, true);
	$files = array_diff(scandir($path), array('.', '..'));
	return array_reverse($files);
}
