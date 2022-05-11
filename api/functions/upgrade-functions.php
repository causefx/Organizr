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
			// Upgrade check start for version below
			$versionCheck = '2.1.860';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = false;
				$oldVer = $versionCheck;
				$this->upgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.1.1500';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = false;
				$oldVer = $versionCheck;
				$this->upgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.1.1860';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$updateDB = false;
				$oldVer = $versionCheck;
				$this->upgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			// Upgrade check start for version below
			$versionCheck = '2.1.2000';
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
				$this->setLoggerChannel('Update')->notice('Updated config version to ' . $this->version);
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
			if ($this->config['driver'] === 'sqlite3') {
				$term = 'SELECT COUNT(*) AS has_column FROM pragma_table_info(?) WHERE name=?';
			} else {
				$term = 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . $this->config['dbName'] . '" AND TABLE_NAME=? AND COLUMN_NAME=?';
			}
			$tableInfo = [
				array(
					'function' => 'fetchSingle',
					'query' => array(
						$term,
						(string)$table,
						(string)$columnName
					)
				)
			];
			$query = $this->processQueries($tableInfo);
			if (!$query) {
				$columnAlter = [
					array(
						'function' => 'query',
						'query' => ['ALTER TABLE %n ADD %n ' . (string)$definition,
							(string)$table,
							(string)$columnName,
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

	public function createMysqliDatabase($database, $migration = false)
	{
		$query = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'DROP DATABASE IF EXISTS tempMigration'
				)
			),
			array(
				'function' => 'fetchAll',
				'query' => array(
					'CREATE DATABASE IF NOT EXISTS %n',
					$database
				)
			),
		];
		//$query = ['CREATE DB %n', $database];
		return $this->processQueries($query, $migration);
	}

	public function updateDB($oldVerNum = false)
	{
		$tempLock = $this->config['dbLocation'] . 'DBLOCK.txt';
		if (!file_exists($tempLock)) {
			touch($tempLock);
			$migrationDB = 'tempMigration.db';
			$pathDigest = pathinfo($this->config['dbLocation'] . $this->config['dbName']);
			// Delete old backup sqlite db if exists
			if (file_exists($this->config['dbLocation'] . $migrationDB)) {
				unlink($this->config['dbLocation'] . $migrationDB);
			}
			// Create Temp DB First
			$this->createNewDB('tempMigration', true);
			$this->connectOtherDB();
			if ($this->config['driver'] == 'sqlite3') {
				// Backup sqlite database
				$backupDB = $pathDigest['dirname'] . '/' . $pathDigest['filename'] . '[' . date('Y-m-d_H-i-s') . ']' . ($oldVerNum ? '[' . $oldVerNum . ']' : '') . '.bak.db';
				copy($this->config['dbLocation'] . $this->config['dbName'], $backupDB);
			}
			$success = $this->createDB($this->config['dbLocation'], true);
			if ($success) {
				switch ($this->config['driver']) {
					case 'sqlite3':
						$query = 'SELECT name FROM sqlite_master WHERE type="table"';
						break;
					case 'mysqli':
						$query = 'SELECT Table_name as name from information_schema.tables where table_schema = "tempMigration"';
						break;
				}
				$response = [
					array(
						'function' => 'fetchAll',
						'query' => array(
							$query
						)
					),
				];
				$tables = $this->processQueries($response);
				$defaultTables = $this->getDefaultTablesFormatted();
				foreach ($tables as $table) {
					if (in_array($table['name'], $defaultTables)) {
						$response = [
							array(
								'function' => 'fetchAll',
								'query' => array(
									'SELECT * FROM %n', $table['name']
								)
							),
						];
						$data = $this->processQueries($response);
						$this->setLoggerChannel('Migration')->info('Obtained Table data', ['table' => $table['name']]);
						foreach ($data as $row) {
							$response = [
								array(
									'function' => 'query',
									'query' => array(
										'INSERT into %n', $table['name'],
										$row
									)
								),
							];
							$this->processQueries($response, true);
						}
						$this->setLoggerChannel('Migration')->info('Wrote Table data', ['table' => $table['name']]);
					}
				}
				if ($this->config['driver'] == 'mysqli') {
					$response = [
						array(
							'function' => 'query',
							'query' => array(
								'DROP DATABASE IF EXISTS %n', $this->config['dbName']
							)
						),
					];
					$data = $this->processQueries($response);
					if ($data) {
						$create = $this->createNewDB($this->config['dbName']);
						if ($create) {
							$structure = $this->createDB($this->config['dbLocation']);
							if ($structure) {
								foreach ($tables as $table) {
									if (in_array($table['name'], $defaultTables)) {
										$response = [
											array(
												'function' => 'fetchAll',
												'query' => array(
													'SELECT * FROM %n', $table['name']
												)
											),
										];
										$data = $this->processQueries($response, true);
										$this->setLoggerChannel('Migration')->info('Obtained Table data', ['table' => $table['name']]);
										foreach ($data as $row) {
											$response = [
												array(
													'function' => 'query',
													'query' => array(
														'INSERT into %n', $table['name'],
														$row
													)
												),
											];
											$this->processQueries($response);
										}
										$this->setLoggerChannel('Migration')->info('Wrote Table data', ['table' => $table['name']]);
									}
								}
							} else {
								$this->setLoggerChannel('Migration')->warning('Could not recreate Database structure');
							}
						} else {
							$this->setLoggerChannel('Migration')->warning('Could not recreate Database');
						}
					} else {
						$this->setLoggerChannel('Migration')->warning('Could not drop old tempMigration Database');
					}
					$this->setLoggerChannel('Migration')->info('All Table data converted');
					@unlink($tempLock);
					return true;
				}
				//$this->db->disconnect();
				//$this->otherDb->disconnect();
				// Remove Current Database
				if ($this->config['driver'] == 'sqlite3') {
					$this->setLoggerChannel('Migration')->info('All Table data converted');
					$this->setLoggerChannel('Migration')->info('Starting Database movement for sqlite3');
					if (file_exists($this->config['dbLocation'] . $migrationDB)) {
						$oldFileSize = filesize($this->config['dbLocation'] . $this->config['dbName']);
						$newFileSize = filesize($this->config['dbLocation'] . $migrationDB);
						if ($newFileSize > 0) {
							$this->setLoggerChannel('Migration')->info('New Table size has been verified');
							@unlink($this->config['dbLocation'] . $this->config['dbName']);
							copy($this->config['dbLocation'] . $migrationDB, $this->config['dbLocation'] . $this->config['dbName']);
							@unlink($this->config['dbLocation'] . $migrationDB);
							$this->setLoggerChannel('Migration')->info('Migrated Old Info to new Database');
							@unlink($tempLock);
							return true;
						} else {
							$this->setLoggerChannel('Migration')->warning('Database filesize is zero');
						}
					} else {
						$this->setLoggerChannel('Migration')->warning('Migration Database does not exist');
					}
				}
				@unlink($tempLock);
				return false;
			} else {
				$this->setLoggerChannel('Migration')->warning('Could not create migration Database');
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
				break;
			case '2.1.400':
				$this->removeOldPluginDirectoriesAndFiles();
				break;
			case '2.1.525':
				$this->removeOldCustomHTML();
				break;
			case '2.1.860':
				$this->upgradeInstalledPluginsConfigItem();
				break;
			case '2.1.1500':
				$this->upgradeDataToFolder();
				break;
			case '2.1.1860':
				$this->upgradePluginsToDataFolder();
				break;
			case '2.1.2000':
				$this->addGroupIdMinToDatabase();
				$this->addAddToAdminToDatabase();
				break;
			default:
				$this->setAPIResponse('success', 'Ran update function for version: ' . $version, 200);
				return true;
		}
	}

	public function removeOldCacheFolder()
	{
		$folder = $this->root . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$this->setLoggerChannel('Migration');
		$this->logger->info('Running Old Cache folder migration');
		if (file_exists($folder)) {
			$this->rrmdir($folder);
			$this->logger->info('Old Cache folder found');
			$this->logger->info('Removed Old Cache folder');
		}
		return true;
	}

	public function upgradeDataToFolder()
	{
		if ($this->hasDB()) {
			// Make main data folder
			$rootFolderMade = $this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data');
			// Make config folder child
			$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);

			if ($rootFolderMade) {
				// Migrate over userTabs folder
				$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'userTabs');
				if ($this->rcopy($this->root . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'userTabs', $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'userTabs')) {
					// Convert tabs over
					$query = [
						[
							'function' => 'fetchAll',
							'query' => [
								'SELECT * FROM tabs WHERE image like "%userTabs%"'
							]
						],
					];
					$tabs = $this->processQueries($query);
					if (count($tabs) > 0) {
						foreach ($tabs as $tab) {
							$newImage = str_replace('plugins/images/userTabs', 'data/userTabs', $tab['image']);
							$updateQuery = [
								[
									'function' => 'query',
									'query' => [
										'UPDATE tabs SET',
										['image' => $newImage],
										'WHERE id = ?',
										$tab['id']
									]
								],
							];
							$this->processQueries($updateQuery);
						}
					}
					$this->setLoggerChannel('Migration');
					$this->logger->info('The folder "userTabs" was migrated to new data folder');
				}
				// Migrate over custom cert
				if (file_exists($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'custom.pem')) {
					// Make cert folder child
					$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR);
					if ($this->rcopy($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'custom.pem', $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'custom.pem')) {
						$this->setLoggerChannel('Migration');
						$this->logger->info('Moved over custom cert file');
					}
				}
				// Migrate over favIcon
				$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'favicon');
				if ($this->rcopy($this->root . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'faviconCustom', $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'favicon')) {
					if ($this->config['favIcon'] !== '') {
						$this->config['favIcon'] = str_replace('plugins/images/faviconCustom', 'data/favicon', $this->config['favIcon']);
						$this->updateConfig(array('favIcon' => $this->config['favIcon']));
					}
					$this->setLoggerChannel('Migration');
					$this->logger->info('Favicon was migrated over');
				}
				// Migrate over custom pages
				$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'pages');
				if (file_exists($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'custom')) {
					if ($this->rcopy($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'custom', $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'pages')) {
						$this->rrmdir($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'custom');
						$this->setLoggerChannel('Migration');
						$this->logger->info('Custom pages was migrated over');
					}
				}
				// Migrate over custom routes
				$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'routes');
				if (file_exists($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'v2' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'custom')) {
					if ($this->rcopy($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'v2' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'custom', $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'routes')) {
						$this->rrmdir($this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'v2' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'custom');
						$this->setLoggerChannel('Migration');
						$this->logger->info('Custom routes was migrated over');
					}
				}
				// Migrate over cache folder
				$this->removeOldCacheFolder();
			}
			return true;
		}
		return false;
	}

	public function upgradePluginsToDataFolder()
	{
		if ($this->hasDB()) {
			// Make main data folder
			$rootFolderMade = $this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data');
			if ($rootFolderMade) {
				// Migrate over plugins folder
				$this->makeDir($this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins');
				$plexLibraries = $this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'plexLibraries';
				if (file_exists($plexLibraries)) {
					if (rename($plexLibraries, $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'plexLibraries')) {
						$this->setLoggerChannel('Migration');
						$this->logger->info('The plugin folder "plexLibraries" was migrated to new data folder');
					}
				}
				$test = $this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'test';
				if (file_exists($test)) {
					if (rename($test, $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'test')) {
						$this->setLoggerChannel('Migration');
						$this->logger->info('The plugin folder "test" was migrated to new data folder');
					}
				}
			}
			return true;
		}
		return false;
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

	public function upgradeInstalledPluginsConfigItem()
	{
		$oldConfigItem = $this->config['installedPlugins'];
		if (gettype($oldConfigItem) == 'string') {
			if ((strpos($oldConfigItem, '|') !== false)) {
				$newPlugins = [];
				$plugins = explode('|', $oldConfigItem);
				foreach ($plugins as $plugin) {
					$info = explode(':', $plugin);
					$newPlugins[$info[0]] = [
						'name' => $info[0],
						'version' => $info[1],
						'repo' => 'organizr'
					];
				}
			} else {
				$newPlugins = [];
				if ($oldConfigItem !== '') {
					$info = explode(':', $oldConfigItem);
					$newPlugins[$info[0]] = [
						'name' => $info[0],
						'version' => $info[1],
						'repo' => 'https://github.com/Organizr/Organizr-Plugins'
					];
				}
			}
			$this->updateConfig(['installedPlugins' => $newPlugins]);
		} elseif (gettype($oldConfigItem) == 'array') {
			$this->updateConfig(['installedPlugins' => $oldConfigItem]);
		}
		return true;
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

	public function addGroupIdMinToDatabase()
	{
		$this->setLoggerChannel('Database Migration')->info('Starting database update');
		$addColumn = $this->addColumnToDatabase('tabs', 'group_id_min', 'INTEGER DEFAULT \'0\'');
		if ($addColumn) {
			$this->setLoggerChannel('Database Migration')->notice('Added group_id_min to database');
			return true;
		} else {
			$this->setLoggerChannel('Database Migration')->warning('Could not update database');
			return false;
		}
	}

	public function addAddToAdminToDatabase()
	{
		$this->setLoggerChannel('Database Migration')->info('Starting database update');
		$addColumn = $this->addColumnToDatabase('tabs', 'add_to_admin', 'INTEGER DEFAULT \'0\'');
		if ($addColumn) {
			$this->setLoggerChannel('Database Migration')->notice('Added add_to_admin to database');
			return true;
		} else {
			$this->setLoggerChannel('Database Migration')->warning('Could not update database');
			return false;
		}
	}
}