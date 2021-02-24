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
	
	public function deleteBackup($filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$path = $this->config['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
		$filename = $path . $filename;
		if ($ext == 'zip') {
			if (file_exists($filename)) {
				$this->writeLog('success', 'Backup Manager Function -  Deleted Backup [' . pathinfo($filename, PATHINFO_BASENAME) . ']', $this->user['username']);
				$this->setAPIResponse(null, pathinfo($filename, PATHINFO_BASENAME) . ' has been deleted', null);
				return (unlink($filename));
			} else {
				$this->setAPIResponse('error', 'File does not exist', 404);
				return false;
			}
		} else {
			$this->setAPIResponse('error', pathinfo($filename, PATHINFO_BASENAME) . ' is not approved to be deleted', 409);
			return false;
		}
	}
	
	public function downloadBackup($filename)
	{
		$path = $this->config['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
		$filename = $path . $filename;
		if (file_exists($filename)) {
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
			header('Content-Length: ' . filesize($filename));
			flush();
			readfile($filename);
			exit();
		} else {
			$this->setAPIResponse('error', 'File does not exist', 404);
			return false;
		}
	}
	
	public function backupOrganizr($type = 'config')
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
			$this->setAPIResponse('success', 'Backup has been created', 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'Backup creation failed', 409);
			return false;
		}
		
	}
	
	public function getBackups()
	{
		$path = $this->config['dbLocation'] . 'backups' . DIRECTORY_SEPARATOR;
		@mkdir($path, 0770, true);
		$files = array_diff(scandir($path), array('.', '..'));
		$fileList = [];
		$totalFiles = 0;
		$totalFileSize = 0;
		foreach ($files as $file) {
			if (file_exists($path . $file)) {
				$size = filesize($path . $file);
				$totalFileSize = $totalFileSize + $size;
				$totalFiles = $totalFiles + 1;
				try {
					$fileList['files'][] = [
						'name' => $file,
						'size' => $this->human_filesize($size, 0),
						'date' => gmdate("Y-m-d\TH:i:s\Z", (filemtime($path . $file)))
					];
				} catch (Exception $e) {
					$this->setAPIResponse('error', 'Backup list failed', 409, $e->getMessage());
					return false;
				}
			}
		}
		$fileList['total_files'] = $totalFiles;
		$fileList['total_size'] = $this->human_filesize($totalFileSize, 2);
		$this->setAPIResponse('success', null, 200, array_reverse($fileList));
		return array_reverse($fileList);
	}
	
}
