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
				$this->setLoggerChannel('Backup')->info('Deleted Backup [' . pathinfo($filename, PATHINFO_BASENAME) . ']');
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
		$this->setLoggerChannel('Backup')->notice('Backing up Organizr');
		$zipname = $directory . 'backup[' . date('Y-m-d_H-i') . ' - ' . $this->random_ascii_string(2) . '][' . $this->version . '].zip';
		$zip = new ZipArchive;
		$zip->open($zipname, ZipArchive::CREATE);
		if ($this->config['driver'] == 'sqlite3') {
			$zip->addFile($this->config['dbLocation'] . $this->config['dbName'], basename($this->config['dbLocation'] . $this->config['dbName']));
		}
		$rootPath = $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

		foreach ($files as $name => $file) {
			// Skip directories (they would be added automatically)
			if (!$file->isDir()) {
				if (stripos($name, 'data' . DIRECTORY_SEPARATOR . 'cache') == false) {
					// Get real and relative path for current file
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($rootPath));
					// Add current file to archive
					$zip->addFile($filePath, $relativePath);
				}
			}
		}


		$zip->close();
		$this->setLoggerChannel('Backup')->notice('Backup process finished');
		$this->setAPIResponse('success', 'Backup has been created', 200);
		return true;
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
			$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if (file_exists($path . $file) && $ext == 'zip') {
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
		$fileList['files'] = $totalFiles > 0 ? array_reverse($fileList['files']) : null;
		$this->setAPIResponse('success', null, 200, array_reverse($fileList));
		return array_reverse($fileList);
	}

}