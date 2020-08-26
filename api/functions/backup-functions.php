<?php

trait BackupFunctions
{
	
	public function fileArray($files)
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
	
	public function backupDB($type = 'config')
	{
		$directory = $this->config['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
		@mkdir($directory, 0770, true);
		switch ($type) {
			case 'config':
				break;
			case 'full':
				break;
			default:
			
		}
		$orgFiles = array(
			'orgLog' => $this->organizrLog,
			'loginLog' => $this->organizrLoginLog,
			'config' => $this->userConfigPath,
			'database' => $this->config['dbLocation'] . $this->config['dbName']
		);
		$files = $this->fileArray($orgFiles);
		if (!empty($files)) {
			$this->writeLog('success', 'BACKUP: backup process started', 'SYSTEM');
			$zipname = $directory . 'backup[' . date('Y-m-d_H-i') . '][' . $this->version . '].zip';
			$zip = new ZipArchive;
			$zip->open($zipname, ZipArchive::CREATE);
			foreach ($files as $file) {
				$zip->addFile($file);
			}
			$zip->close();
			$this->writeLog('success', 'BACKUP: backup process finished', 'SYSTEM');
			return true;
		} else {
			return false;
		}
		
	}
	
	public function getBackups()
	{
		$path = $this->config['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
		@mkdir($path, 0770, true);
		$files = array_diff(scandir($path), array('.', '..'));
		return array_reverse($files);
	}
	
}
