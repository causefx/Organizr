<?php

trait UpgradeFunctions
{
	public function upgradeCheck()
	{
		if ($this->hasDB()) {
			$tempLock = $this->config['dbLocation'] . 'DBLOCK.txt';
			$updateComplete = $this->config['dbLocation'] . 'completed.txt';
			$cleanup = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR;
			if (file_exists($updateComplete)) {
				@unlink($updateComplete);
				@$this->rrmdir($cleanup);
			}
			if (file_exists($tempLock)) {
				die($this->showHTML('Upgrading', 'Please wait...'));
			}
			$updateDB = false;
			$updateSuccess = true;
			$compare = new Composer\Semver\Comparator;
			$oldVer = $this->config['configVersion'];
			// Upgrade check start for version below
			$versionCheck = '2.0.0-beta-200';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = true;
				$oldVer = $versionCheck;
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.0.0-beta-500';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = true;
				$oldVer = $versionCheck;
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.0.0-beta-800';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = true;
				$oldVer = $versionCheck;
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.1.0';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = false;
				$oldVer = $versionCheck;
				$this->upgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.1.400';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = false;
				$oldVer = $versionCheck;
				$this->upgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.1.525';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = false;
				$oldVer = $versionCheck;
				$this->upgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			if ($updateDB == true) {
				//return 'Upgraded Needed - Current Version '.$oldVer.' - New Version: '.$versionCheck;
				// Upgrade database to latest version
				$updateSuccess = $this->updateDB($oldVer);
			}
			// Update config.php version if different to the installed version
			if ($updateSuccess && $this->version !== $this->config['configVersion']) {
				$this->updateConfig(array('apply_CONFIG_VERSION' => $this->version));
				$this->setLoggerChannel('Update');
				$this->logger->debug('Updated config version to ' . $this->version);
			}
			if ($updateSuccess == false) {
				die($this->showHTML('Database update failed', 'Please manually check logs and fix - Then reload this page'));
			}
			return true;
		}
	}
	
	public function addColumnToDatabase($table = '', $columnName = '', $definition = 'TEXT')
	{
		if ($table == '' || $columnName == '' || $definition == '') {
			return false;
		}
		if ($this->hasDB()) {
			$tableInfo = [
				array(
					'function' => 'fetchSingle',
					'query' => array(
						'SELECT COUNT(*) AS has_column FROM pragma_table_info(?) WHERE name=?',
						$table,
						$columnName
					)
				)
			];
			$query = $this->processQueries($tableInfo);
			if (!$query) {
				$columnAlter = [
					array(
						'function' => 'query',
						'query' => ['ALTER TABLE ? ADD ? ' . $definition,
							$table,
							$columnName,
						]
					)
				];
				$AlterQuery = $this->processQueries($columnAlter);
				if ($AlterQuery) {
					$query = $this->processQueries($tableInfo);
					if ($query) {
						return true;
					}
				}
			} else {
				return true;
			}
		}
		return false;
	}
	
	public function updateDB($oldVerNum = false)
	{
		$tempLock = $this->config['dbLocation'] . 'DBLOCK.txt';
		if (!file_exists($tempLock)) {
			touch($tempLock);
			$migrationDB = 'tempMigration.db';
			$pathDigest = pathinfo($this->config['dbLocation'] . $this->config['dbName']);
			if (file_exists($this->config['dbLocation'] . $migrationDB)) {
				unlink($this->config['dbLocation'] . $migrationDB);
			}
			// Create Temp DB First
			$this->connectOtherDB();
			$backupDB = $pathDigest['dirname'] . '/' . $pathDigest['filename'] . '[' . date('Y-m-d_H-i-s') . ']' . ($oldVerNum ? '[' . $oldVerNum . ']' : '') . '.bak.db';
			copy($this->config['dbLocation'] . $this->config['dbName'], $backupDB);
			$success = $this->createDB($this->config['dbLocation'], true);
			if ($success) {
				$response = [
					array(
						'function' => 'fetchAll',
						'query' => array(
							'SELECT name FROM sqlite_master WHERE type="table"'
						)
					),
				];
				$tables = $this->processQueries($response);
				foreach ($tables as $table) {
					$response = [
						array(
							'function' => 'fetchAll',
							'query' => array(
								'SELECT * FROM ' . $table['name']
							)
						),
					];
					$data = $this->processQueries($response);
					$this->writeLog('success', 'Update Function -  Grabbed Table data for Table: ' . $table['name'], 'Database');
					foreach ($data as $row) {
						$response = [
							array(
								'function' => 'query',
								'query' => array(
									'INSERT into ' . $table['name'],
									$row
								)
							),
						];
						$this->processQueries($response, true);
					}
					$this->writeLog('success', 'Update Function -  Wrote Table data for Table: ' . $table['name'], 'Database');
				}
				$this->writeLog('success', 'Update Function -  All Table data converted - Starting Movement', 'Database');
				$this->db->disconnect();
				$this->otherDb->disconnect();
				// Remove Current Database
				if (file_exists($this->config['dbLocation'] . $migrationDB)) {
					$oldFileSize = filesize($this->config['dbLocation'] . $this->config['dbName']);
					$newFileSize = filesize($this->config['dbLocation'] . $migrationDB);
					if ($newFileSize > 0) {
						$this->writeLog('success', 'Update Function -  Table Size of new DB ok..', 'Database');
						@unlink($this->config['dbLocation'] . $this->config['dbName']);
						copy($this->config['dbLocation'] . $migrationDB, $this->config['dbLocation'] . $this->config['dbName']);
						@unlink($this->config['dbLocation'] . $migrationDB);
						$this->writeLog('success', 'Update Function -  Migrated Old Info to new Database', 'Database');
						@unlink($tempLock);
						return true;
					} else {
						$this->writeLog('error', 'Update Function -  Filesize is zero', 'Database');
					}
				} else {
					$this->writeLog('error', 'Update Function -  Migration DB does not exist', 'Database');
				}
				@unlink($tempLock);
				return false;
				
			} else {
				$this->writeLog('error', 'Update Function -  Could not create migration DB', 'Database');
			}
			@unlink($tempLock);
			return false;
		}
		return false;
	}
	
	public function upgradeToVersion($version = '2.1.0')
	{
		switch ($version) {
			case '2.1.0':
				$this->upgradeSettingsTabURL();
				$this->upgradeHomepageTabURL();
			case '2.1.400':
				$this->removeOldPluginDirectoriesAndFiles();
			case '2.1.525':
				$this->removeOldCustomHTML();
			default:
				$this->setAPIResponse('success', 'Ran update function for version: ' . $version, 200);
				return true;
		}
	}
	
	public function upgradeSettingsTabURL()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE tabs SET',
					['url' => 'api/v2/page/settings'],
					'WHERE url = ?',
					'api/?v1/settings/page'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function upgradeHomepageTabURL()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE tabs SET',
					['url' => 'api/v2/page/homepage'],
					'WHERE url = ?',
					'api/?v1/homepage/page'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function removeOldPluginDirectoriesAndFiles()
	{
		$folders = [
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'api',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'config',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'css',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'js',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'misc',
		];
		$files = [
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'bookmark.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'chat.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'healthChecks.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'invites.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'php-mailer.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'speedTest.php',
		];
		foreach ($files as $file) {
			if (file_exists($file)) {
				@unlink($file);
			}
		}
		foreach ($folders as $folder) {
			if (file_exists($folder)) {
				@$this->rrmdir($folder);
			}
		}
		return true;
	}
	
	public function checkForConfigKeyAddToArray($keys)
	{
		$updateItems = [];
		foreach ($keys as $new => $old) {
			if (isset($this->config[$old])) {
				if ($this->config[$old] !== '') {
					$updateItemsNew = [$new => $this->config[$old]];
					$updateItems = array_merge($updateItems, $updateItemsNew);
				}
			}
		}
		return $updateItems;
	}
	
	public function removeOldCustomHTML()
	{
		$backup = $this->backupOrganizr();
		if ($backup) {
			$keys = [
				'homepageCustomHTML01Enabled' => 'homepageCustomHTMLoneEnabled',
				'homepageCustomHTML01Auth' => 'homepageCustomHTMLoneAuth',
				'customHTML01' => 'customHTMLone',
				'homepageCustomHTML02Enabled' => 'homepageCustomHTMLtwoEnabled',
				'homepageCustomHTML02Auth' => 'homepageCustomHTMLtwoAuth',
				'customHTML02' => 'customHTMLtwo',
			];
			$updateItems = $this->checkForConfigKeyAddToArray($keys);
			$updateComplete = false;
			if (!empty($updateItems)) {
				$updateComplete = $this->updateConfig($updateItems);
			}
			if ($updateComplete) {
				$removeConfigItems = $this->removeConfigItem(['homepagCustomHTMLoneAuth', 'homepagCustomHTMLoneEnabled', 'homepagCustomHTMLtwoAuth', 'homepagCustomHTMLtwoEnabled', 'homepageOrdercustomhtml', 'homepageOrdercustomhtmlTwo', 'homepageCustomHTMLoneEnabled', 'homepageCustomHTMLoneAuth', 'customHTMLone', 'homepageCustomHTMLtwoEnabled', 'homepageCustomHTMLtwoAuth', 'customHTMLtwo']);
				if ($removeConfigItems) {
					return true;
				}
			}
		}
		return false;
	}
}