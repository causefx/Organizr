<?php

trait UpdateFunctions
{
	public function dockerUpdate()
	{
		$dockerUpdate = null;
		chdir('/etc/cont-init.d/');
		if (file_exists('./30-install')) {
			$dockerUpdate = shell_exec('./30-install');
		} elseif (file_exists('./40-install')) {
			$dockerUpdate = shell_exec('./40-install');
		}
		if ($dockerUpdate) {
			$this->setAPIResponse('success', $dockerUpdate, 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'Update failed', 500);
			return false;
		}
	}
	
	public function windowsUpdate()
	{
		$branch = ($this->config['branch'] == 'v2-master') ? '-m' : '-d';
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		$logFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'log.txt';
		$windowsScript = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'windows-update.bat ' . $branch . ' > ' . $logFile . ' 2>&1';
		$windowsUpdate = shell_exec($windowsScript);
		if ($windowsUpdate) {
			$this->setAPIResponse('success', $windowsUpdate, 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'Update Complete - check log.txt for output', 500);
			return false;
		}
	}
	
	public function upgradeInstall($branch = 'v2-master', $stage = '1')
	{
		// may kill this function in place for php script to run elsewhere
		if ($this->docker) {
			$this->setAPIResponse('error', 'Cannot perform update action on docker install - use script', 500);
			return false;
		}
		if ($this->getOS() == 'win') {
			$this->setAPIResponse('error', 'Cannot perform update action on windows install - use script', 500);
			return false;
		}
		$notWritable = array_search(false, $this->pathsWritable($this->paths));
		if ($notWritable == false) {
			ini_set('max_execution_time', 0);
			set_time_limit(0);
			$url = 'https://github.com/causefx/Organizr/archive/' . $branch . '.zip';
			$file = "upgrade.zip";
			$source = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . 'Organizr-' . str_replace('v2', '2', $branch) . DIRECTORY_SEPARATOR;
			$cleanup = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "upgrade" . DIRECTORY_SEPARATOR;
			$destination = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
			switch ($stage) {
				case '1':
					$this->writeLog('success', 'Update Function -  Started Upgrade Process', $this->user['username']);
					if ($this->downloadFile($url, $file)) {
						$this->writeLog('success', 'Update Function -  Downloaded Update File for Branch: ' . $branch, $this->user['username']);
						$this->setAPIResponse('success', 'Downloaded file successfully', 200);
						return true;
					} else {
						$this->writeLog('error', 'Update Function -  Downloaded Update File Failed  for Branch: ' . $branch, $this->user['username']);
						$this->setAPIResponse('error', 'Download failed', 500);
						return false;
					}
				case '2':
					if ($this->unzipFile($file)) {
						$this->writeLog('success', 'Update Function -  Unzipped Update File for Branch: ' . $branch, $this->user['username']);
						$this->setAPIResponse('success', 'Unzipped file successfully', 200);
						return true;
					} else {
						$this->writeLog('error', 'Update Function -  Unzip Failed for Branch: ' . $branch, $this->user['username']);
						$this->setAPIResponse('error', 'Unzip failed', 500);
						return false;
					}
				case '3':
					if ($this->rcopy($source, $destination)) {
						$this->writeLog('success', 'Update Function -  Files overwritten using Updated Files from Branch: ' . $branch, $this->user['username']);
						$updateComplete = $this->config['dbLocation'] . 'completed.txt';
						if (!file_exists($updateComplete)) {
							touch($updateComplete);
						}
						$this->setAPIResponse('success', 'Files replaced successfully', 200);
						return true;
					} else {
						$this->writeLog('error', 'Update Function -  Overwrite Failed for Branch: ' . $branch, $this->user['username']);
						$this->setAPIResponse('error', 'File replacement failed', 500);
						return false;
					}
				case '4':
					if ($this->rrmdir($cleanup)) {
						$this->writeLog('success', 'Update Function -  Deleted Update Files from Branch: ' . $branch, $this->user['username']);
						$this->writeLog('success', 'Update Function -  Update Completed', $this->user['username']);
						$this->setAPIResponse('success', 'Removed update files successfully', 200);
						return true;
					} else {
						$this->writeLog('error', 'Update Function -  Removal of Update Files Failed for Branch: ' . $branch, $this->user['username']);
						$this->setAPIResponse('error', 'File removal failed', 500);
						return false;
					}
				default:
					$this->setAPIResponse('error', 'Action not setup', 500);
					return false;
			}
		} else {
			$this->setAPIResponse('error', 'File permissions not set correctly', 500);
			return false;
		}
		
	}
}