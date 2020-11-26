<?php

use Dibi\Connection;

class Organizr
{
	// Use Custom Functions From Traits;
	use TwoFAFunctions;
	use ApiFunctions;
	use AuthFunctions;
	use BackupFunctions;
	use ConfigFunctions;
	use HomepageConnectFunctions;
	use HomepageFunctions;
	use LogFunctions;
	use NetDataFunctions;
	use NormalFunctions;
	use OptionsFunction;
	use OrganizrFunctions;
	use PluginFunctions;
	use StaticFunctions;
	use SSOFunctions;
	use TokenFunctions;
	use UpdateFunctions;
	use UpgradeFunctions;
	
	// Use homepage item functions
	use CalendarHomepageItem;
	use CouchPotatoHomepageItem;
	use DelugeHomepageItem;
	use EmbyHomepageItem;
	use HealthChecksHomepageItem;
	use HTMLHomepageItem;
	use ICalHomepageItem;
	use JackettHomepageItem;
	use JDownloaderHomepageItem;
	use JellyfinHomepageItem;
	use LidarrHomepageItem;
	use MiscHomepageItem;
	use MonitorrHomepageItem;
	use NetDataHomepageItem;
	use NZBGetHomepageItem;
	use OctoPrintHomepageItem;
	use OmbiHomepageItem;
	use PiHoleHomepageItem;
	use PlexHomepageItem;
	use QBitTorrentHomepageItem;
	use RadarrHomepageItem;
	use RTorrentHomepageItem;
	use SabNZBdHomepageItem;
	use SickRageHomepageItem;
	use SonarrHomepageItem;
	use SpeedTestHomepageItem;
	use TautulliHomepageItem;
	use TransmissionHomepageItem;
	use UnifiHomepageItem;
	use WeatherHomepageItem;
	
	// ===================================
	// Organizr Version
	public $version = '2.1.83';
	// ===================================
	// Quick php Version check
	public $minimumPHP = '7.2';
	// ===================================
	protected $db;
	protected $otherDb;
	public $config;
	public $user;
	public $userConfigPath;
	public $defaultConfigPath;
	public $currentTime;
	public $docker;
	public $dev;
	public $demo;
	public $commit;
	public $fileHash;
	public $cookieName;
	public $organizrLog;
	public $organizrLoginLog;
	public $timeExecution;
	public $root;
	public $paths;
	public $updating;
	public $groupOptions;
	
	public function __construct($updating = false)
	{
		// First Check PHP Version
		$this->checkPHP();
		// Constructed from Updater?
		$this->updating = $updating;
		// Set Project Root directory
		$this->root = dirname(__DIR__, 2);
		// Set Start Execution Time
		$this->timeExecution = $this->timeExecution();
		// Set location path to user config path
		$this->userConfigPath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
		// Set location path to default config path
		$this->defaultConfigPath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'default.php';
		// Set current time
		$this->currentTime = gmdate("Y-m-d\TH:i:s\Z");
		// Set variable if install is for official docker
		$this->docker = (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Docker.txt'));
		// Set variable if install is for develop
		$this->dev = (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Dev.txt'));
		// Set variable if install is for demo
		$this->demo = (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Demo.txt'));
		// Set variable if install has commit hash
		$this->commit = ($this->docker && !$this->dev) ? file_get_contents(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Github.txt') : null;
		// Set variable to be used as hash for files
		$this->fileHash = ($this->commit) ?? $this->version;
		// Load Config file
		$this->config = $this->config();
		// Set organizr Log file location
		$this->organizrLog = ($this->hasDB()) ? $this->config['dbLocation'] . 'organizrLog.json' : false;
		// Set organizr Login Log file location
		$this->organizrLoginLog = ($this->hasDB()) ? $this->config['dbLocation'] . 'organizrLoginLog.json' : false;
		// Set Paths
		$this->paths = array(
			'Root Folder' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
			'Cache Folder' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR,
			'Tab Folder' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'userTabs' . DIRECTORY_SEPARATOR,
			'API Folder' => dirname(__DIR__, 1) . DIRECTORY_SEPARATOR,
			'DB Folder' => ($this->hasDB()) ? $this->config['dbLocation'] : false
		);
		// Connect to DB
		$this->connectDB();
		// Check DB Writable
		$this->checkWritableDB();
		// Set cookie name for Organizr Instance
		$this->cookieName = ($this->hasDB()) ? $this->config['uuid'] !== '' ? 'organizr_token_' . $this->config['uuid'] : 'organizr_token_temp' : 'organizr_token_temp';
		// Get token form cookie and validate
		$this->user = $this->hasCookie() ? $this->validateToken($_COOKIE[$this->cookieName]) ?? $this->guestUser() : $this->guestUser();
		// might just run this at index
		$this->upgradeCheck();
		// Is Page load Organizr OAuth?
		$this->checkForOrganizrOAuth();
	}
	
	protected function connectDB()
	{
		if ($this->hasDB()) {
			try {
				$this->db = new Connection([
					'driver' => 'sqlite3',
					'database' => $this->config['dbLocation'] . $this->config['dbName'],
				]);
			} catch (Dibi\Exception $e) {
				$this->db = null;
			}
		} else {
			$this->db = null;
		}
	}
	
	public function connectOtherDB($file = null)
	{
		$file = $file ?? $this->config['dbLocation'] . 'tempMigration.db';
		try {
			$this->otherDb = new Connection([
				'driver' => 'sqlite3',
				'database' => $file,
			]);
		} catch (Dibi\Exception $e) {
			$this->otherDb = null;
		}
	}
	
	public function checkForOrganizrOAuth()
	{
		// Oauth?
		if ($this->config['authProxyEnabled'] && $this->config['authProxyHeaderName'] !== '' && $this->config['authProxyWhitelist'] !== '') {
			if (isset(getallheaders()[$this->config['authProxyHeaderName']])) {
				$this->coookieSeconds('set', 'organizrOAuth', 'true', 20000, false);
			}
		}
	}
	
	public function auth()
	{
		if ($this->hasDB()) {
			$whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
			$blacklist = isset($_GET['blacklist']) ? $_GET['blacklist'] : false;
			$group = 0;
			$groupParam = ($_GET['group']) ?? 0;
			$redirect = false;
			if (isset($groupParam)) {
				if (is_numeric($groupParam)) {
					$group = (int)$groupParam;
				} else {
					$group = $this->getTabGroupByTabName($groupParam);
				}
			}
			$currentIP = $this->userIP();
			$unlocked = ($this->user['locked'] == '1') ? false : true;
			if (isset($this->user)) {
				$currentUser = $this->user['username'];
				$currentGroup = $this->user['groupID'];
				$currentEmail = $this->user['email'];
			} else {
				$currentUser = 'Guest';
				$currentGroup = $this->getUserLevel();
				$currentEmail = 'guest@guest.com';
			}
			$userInfo = "User: $currentUser | Group: $currentGroup | IP: $currentIP | Requesting Access to Group $group | Result: ";
			if ($whitelist) {
				if (in_array($currentIP, $this->arrayIP($whitelist))) {
					$this->setAPIResponse('success', 'User is whitelisted', 200);
				}
			}
			if ($blacklist) {
				if (in_array($currentIP, $this->arrayIP($blacklist))) {
					$this->setAPIResponse('error', $userInfo . ' User is blacklisted', 401);
				}
			}
			if ($group !== null) {
				if ((isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'traefik') || $this->config['traefikAuthEnable']) {
					$redirect = 'Location: ' . $this->getServerPath();
				}
				if ($this->qualifyRequest($group) && $unlocked) {
					header("X-Organizr-User: $currentUser");
					header("X-Organizr-Email: $currentEmail");
					header("X-Organizr-Group: $currentGroup");
					$this->setAPIResponse('success', $userInfo . ' User is Authorized', 200);
				} else {
					if (!$redirect) {
						$this->setAPIResponse('error', $userInfo . ' User is not Authorized or User is locked', 401);
					} else {
						exit(http_response_code(401) . header($redirect));
					}
				}
			} else {
				$this->setAPIResponse('error', 'Missing info', 401);
			}
		}
		return true;
	}
	
	public function setAPIResponse($result = null, $message = null, $responseCode = null, $data = null)
	{
		if ($result) {
			$GLOBALS['api']['response']['result'] = $result;
		}
		if ($message) {
			$GLOBALS['api']['response']['message'] = $message;
		}
		if ($responseCode) {
			$GLOBALS['responseCode'] = $responseCode;
		}
		if ($data) {
			$GLOBALS['api']['response']['data'] = $data;
		}
	}
	
	public function checkRoute($request)
	{
		$route = '/api/v2/' . explode('api/v2/', $request->getUri()->getPath())[1];
		$method = $request->getMethod();
		$data = $this->apiData($request);
		if (!in_array($route, $GLOBALS['bypass'])) {
			if ($this->isApprovedRequest($method, $data) === false) {
				$this->setAPIResponse('error', 'Not authorized for current Route: ' . $route, 401);
				$this->writeLog('success', 'Killed Attack From [' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'No Referer') . ']', $this->user['username']);
				return false;
			}
		}
		return true;
	}
	
	public function apiData($request)
	{
		switch ($request->getMethod()) {
			case 'POST':
				if ($request->getHeaderLine('Content-Type') == 'application/json') {
					return json_decode(file_get_contents('php://input', 'r'), true);
				} else {
					return $request->getParsedBody();
				}
			default:
				if ($request->getHeaderLine('Content-Type') == 'application/json') {
					return json_decode(file_get_contents('php://input', 'r'), true);
				} else {
					return null;
				}
		}
	}
	
	public function getPlugins()
	{
		if ($this->hasDB()) {
			$pluginList = [];
			foreach ($GLOBALS['plugins'] as $plugin) {
				foreach ($plugin as $key => $value) {
					if (strpos($value['license'], $this->config['license']) !== false) {
						$plugin[$key]['enabled'] = $this->config[$value['configPrefix'] . '-enabled'];
						$pluginList[$key] = $plugin[$key];
					}
				}
			}
			return $pluginList;
		}
		return false;
	}
	
	public function refreshCookieName()
	{
		$this->cookieName = $this->config['uuid'] !== '' ? 'organizr_token_' . $this->config['uuid'] : 'organizr_token_temp';
	}
	
	public function favIcons()
	{
		$favicon = '
	<link rel="apple-touch-icon" sizes="180x180" href="plugins/images/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="plugins/images/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="plugins/images/favicon/favicon-16x16.png">
	<link rel="manifest" href="plugins/images/favicon/site.webmanifest">
	<link rel="mask-icon" href="plugins/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="plugins/images/favicon/favicon.ico">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="msapplication-TileImage" content="plugins/images/favicon/mstile-144x144.png">
	<meta name="msapplication-config" content="plugins/images/favicon/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	';
		return ($this->config['favIcon'] == '') ? $favicon : $this->config['favIcon'];
	}
	
	public function pluginGlobalList()
	{
		$pluginSearch = '-enabled';
		$pluginInclude = '-include';
		$plugins = array_filter($this->config, function ($k) use ($pluginSearch) {
			return stripos($k, $pluginSearch) !== false;
		}, ARRAY_FILTER_USE_KEY);
		$plugins['includes'] = array_filter($this->config, function ($k) use ($pluginInclude) {
			return stripos($k, $pluginInclude) !== false;
		}, ARRAY_FILTER_USE_KEY);
		return $plugins;
	}
	
	public function googleTracking()
	{
		if ($this->config['gaTrackingID'] !== '') {
			return '
				<script async src="https://www.googletagmanager.com/gtag/js?id=' . $this->config['gaTrackingID'] . '"></script>
    			<script>
				    window.dataLayer = window.dataLayer || [];
				    function gtag(){dataLayer.push(arguments);}
				    gtag("js", new Date());
				    gtag("config","' . $this->config['gaTrackingID'] . '");
    			</script>
			';
		}
		return null;
	}
	
	public function matchBrackets($text, $brackets = 's')
	{
		switch ($brackets) {
			case 's':
			case 'square':
				$pattern = '#\[(.*?)\]#';
				break;
			case 'c':
			case 'curly':
				$pattern = '#\((.*?)\)#';
				break;
			default:
				return null;
		}
		preg_match($pattern, $text, $match);
		return $match[1];
	}
	
	public function languagePacks($encode = false)
	{
		$files = array();
		foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'langpack' . DIRECTORY_SEPARATOR . "*.json") as $filename) {
			if (strpos(basename($filename), '[') !== false) {
				$explode = explode('[', basename($filename));
				$files[] = array(
					'filename' => basename($filename),
					'code' => $explode[0],
					'language' => $this->matchBrackets(basename($filename))
				);
			}
		}
		usort($files, function ($a, $b) {
			return $a['language'] <=> $b['language'];
		});
		return ($encode) ? json_encode($files) : $files;
	}
	
	public function pluginFiles($type)
	{
		$files = '';
		switch ($type) {
			case 'js':
				foreach (glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . "*.js") as $filename) {
					$files .= '<script src="api/plugins/js/' . basename($filename) . '?v=' . $this->fileHash . '" defer="true"></script>';
				}
				break;
			case 'css':
				foreach (glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . "*.css") as $filename) {
					$files .= '<link href="api/plugins/css/' . basename($filename) . '?v=' . $this->fileHash . '" rel="stylesheet">';
				}
				break;
			default:
				break;
		}
		return $files;
	}
	
	public function formKey($script = true)
	{
		if (isset($this->config['organizrHash'])) {
			if ($this->config['organizrHash'] !== '') {
				$hash = password_hash(substr($this->config['organizrHash'], 2, 10), PASSWORD_BCRYPT);
				return ($script) ? '<script>local("s","formKey","' . $hash . '");</script>' : $hash;
			}
		}
	}
	
	private function checkPHP()
	{
		if (!(version_compare(PHP_VERSION, $this->minimumPHP) >= 0)) {
			die('Organizr needs PHP Version: ' . $this->minimumPHP . '<br/> You have PHP Version: ' . PHP_VERSION);
		}
	}
	
	private function checkWritableDB()
	{
		if ($this->hasDB()) {
			$db = is_writable($this->config['dbLocation'] . $this->config['dbName']);
			if (!$db) {
				die('Organizr DB is not writable!!!  Please fix...');
			}
		}
	}
	
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
				die('upgrading');
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
			if ($updateDB == true) {
				//return 'Upgraded Needed - Current Version '.$oldVer.' - New Version: '.$versionCheck;
				// Upgrade database to latest version
				$updateSuccess = $this->updateDB($oldVer);
			}
			// Update config.php version if different to the installed version
			if ($updateSuccess && $this->version !== $this->config['configVersion']) {
				$this->updateConfig(array('apply_CONFIG_VERSION' => $this->version));
			}
			if ($updateSuccess == false) {
				die('Database update failed - Please manually check logs and fix - Then reload this page');
			}
			return true;
		}
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
	
	// Create config file in the return syntax
	public function createConfig($array, $path = null, $nest = 0)
	{
		$path = ($path) ? $path : $this->userConfigPath;
		// Define Initial Value
		$output = array();
		// Sort Items
		ksort($array);
		// Update the current config version
		if (!$nest) {
			// Inject Current Version
			$output[] = "\t'configVersion' => '" . (isset($array['apply_CONFIG_VERSION']) ? $array['apply_CONFIG_VERSION'] : $this->version) . "'";
		}
		unset($array['configVersion']);
		unset($array['apply_CONFIG_VERSION']);
		// Process Settings
		foreach ($array as $k => $v) {
			$allowCommit = true;
			$item = '';
			switch (gettype($v)) {
				case 'boolean':
					$item = ($v ? 'true' : 'false');
					break;
				case 'integer':
				case 'double':
				case 'NULL':
					$item = $v;
					break;
				case 'string':
					$item = "'" . str_replace(array('\\', "'"), array('\\\\', "\'"), $v) . "'";
					break;
				case 'array':
					$item = $this->createConfig($v, false, $nest + 1);
					break;
				default:
					$allowCommit = false;
			}
			if ($allowCommit) {
				$output[] = str_repeat("\t", $nest + 1) . "'$k' => $item";
			}
		}
		// Build output
		$output = (!$nest ? "<?php\nreturn " : '') . "array(\n" . implode(",\n", $output) . "\n" . str_repeat("\t", $nest) . ')' . (!$nest ? ';' : '');
		if (!$nest && $path) {
			$pathDigest = pathinfo($path);
			@mkdir($pathDigest['dirname'], 0770, true);
			if (file_exists($path)) {
				rename($path, $pathDigest['dirname'] . '/' . $pathDigest['filename'] . '.bak.php');
			}
			$file = fopen($path, 'w');
			fwrite($file, $output);
			fclose($file);
			if (file_exists($path)) {
				return true;
			}
			return false;
		} else {
			return $output;
		}
	}
	
	// Commit new values to the configuration
	public function updateConfig($new, $current = false)
	{
		// Get config if not supplied
		if ($current === false) {
			//$current = $this->loadConfig();
			$current = $this->config;
		} elseif (is_string($current) && is_file($current)) {
			$current = $this->loadConfig($current);
		}
		// Inject Parts
		foreach ($new as $k => $v) {
			$current[$k] = $v;
		}
		// Return Create
		return $this->createConfig($current);
	}
	
	public function loadConfig($path = null)
	{
		$path = ($path) ? $path : $this->userConfigPath;
		if (!is_file($path)) {
			return null;
		} else {
			return (array)call_user_func(function () use ($path) {
				return include($path);
			});
		}
	}
	
	public function fillDefaultConfig($array)
	{
		$path = $this->defaultConfigPath;
		if (is_string($path)) {
			$loadedDefaults = $this->loadConfig($path);
		} else {
			$loadedDefaults = $path;
		}
		// Include all plugin config files
		foreach (glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
			$loadedDefaults = array_merge($loadedDefaults, $this->loadConfig($filename));
		}
		return (is_array($loadedDefaults) ? $this->fillDefaultConfig_recurse($array, $loadedDefaults) : false);
	}
	
	public function fillDefaultConfig_recurse($current, $defaults)
	{
		foreach ($defaults as $k => $v) {
			if (!isset($current[$k])) {
				$current[$k] = $v;
			} elseif (is_array($current[$k]) && is_array($v)) {
				$current[$k] = $this->fillDefaultConfig_recurse($current[$k], $v);
			}
		}
		return $current;
	}
	
	public function config()
	{
		// Load config or default
		if (file_exists($this->userConfigPath)) {
			$config = $this->fillDefaultConfig($this->loadConfig($this->userConfigPath));
		} else {
			$config = $this->fillDefaultConfig($this->loadConfig($this->defaultConfigPath));
		}
		return $config;
	}
	
	public function combineConfig($array)
	{
		$this->config = array_merge($this->config, $array);
		return $this->config;
	}
	
	public function status()
	{
		$status = array();
		$dependenciesActive = array();
		$dependenciesInactive = array();
		$extensions = array("PDO_SQLITE", "PDO", "SQLITE3", "zip", "cURL", "openssl", "simplexml", "json", "session", "filter");
		$functions = array("hash", "fopen", "fsockopen", "fwrite", "fclose", "readfile");
		foreach ($extensions as $check) {
			if (extension_loaded($check)) {
				array_push($dependenciesActive, $check);
			} else {
				array_push($dependenciesInactive, $check);
			}
		}
		foreach ($functions as $check) {
			if (function_exists($check)) {
				array_push($dependenciesActive, $check);
			} else {
				array_push($dependenciesInactive, $check);
			}
		}
		if (!file_exists($this->userConfigPath)) {
			$status['status'] = "wizard";//wizard - ok for test
		}
		if (count($dependenciesInactive) > 0 || !is_writable(dirname(__DIR__, 2)) || !(version_compare(PHP_VERSION, $this->minimumPHP) >= 0)) {
			$status['status'] = "dependencies";
		}
		$status['status'] = ($status['status']) ?? "ok";
		$status['writable'] = is_writable(dirname(__DIR__, 2)) ? 'yes' : 'no';
		$status['minVersion'] = (version_compare(PHP_VERSION, $this->minimumPHP) >= 0) ? 'yes' : 'no';
		$status['dependenciesActive'] = $dependenciesActive;
		$status['dependenciesInactive'] = $dependenciesInactive;
		$status['version'] = $this->version;
		$status['os'] = $this->getOS();
		$status['php'] = phpversion();
		$status['userConfigPath'] = $this->userConfigPath;
		return $status;
	}
	
	public function hasDB()
	{
		return (file_exists($this->userConfigPath)) ?? false;
	}
	
	public function hasCookie()
	{
		return ($_COOKIE[$this->cookieName]) ?? false;
	}
	
	public function getGuest()
	{
		$guest = array(
			'group' => 'Guest',
			'group_id' => 999,
			'image' => 'plugins/images/groups/guest.png'
		);
		$response = [
			array(
				'function' => 'fetch',
				'query' => 'SELECT * FROM groups WHERE `group_id` = 999'
			),
		];
		return $this->hasDB() ? $this->processQueries($response) : $guest;
		
	}
	
	public function getSchema()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT name, sql FROM sqlite_master WHERE type=\'table\' ORDER BY name'
			),
		];
		return $this->hasDB() ? $this->processQueries($response) : 'Database not setup yet';
	}
	
	public function guestUser()
	{
		if ($this->hasDB()) {
			if ($this->getUserLevel() !== 999) {
				$guest = array(
					"token" => null,
					"tokenDate" => null,
					"tokenExpire" => null,
					"username" => "Organizr API",
					"uid" => $this->guestHash(0, 5),
					"group" => 'Admin',
					"groupID" => 0,
					"email" => null,
					//"groupImage"=>getGuest()['image'],
					"image" => $this->getGuest()['image'],
					"userID" => null,
					"loggedin" => false,
					"locked" => false,
					"tokenList" => null,
					"authService" => null
				);
			}
		}
		$guest = $guest ?? array(
				"token" => null,
				"tokenDate" => null,
				"tokenExpire" => null,
				"username" => "Guest",
				"uid" => $this->guestHash(0, 5),
				"group" => $this->getGuest()['group'],
				"groupID" => $this->getGuest()['group_id'],
				"email" => null,
				//"groupImage"=>getGuest()['image'],
				"image" => $this->getGuest()['image'],
				"userID" => null,
				"loggedin" => false,
				"locked" => false,
				"tokenList" => null,
				"authService" => null
			);
		return $guest;
	}
	
	public function getAllUserTokens($id)
	{
		
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM `tokens` WHERE user_id = ? AND expires > ?',
					[$id],
					[$this->currentTime]
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getUserById($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM users WHERE id = ?',
					$id
				)
			)
		];
		return $this->processQueries($response);
	}
	
	public function getUserByEmail($email)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM users WHERE email = ? COLLATE NOCASE',
					$email
				)
			)
		];
		return $this->processQueries($response);
	}
	
	protected function invalidToken()
	{
		$this->coookie('delete', $this->cookieName);
		$this->user = null;
	}
	
	public function validateToken($token)
	{
		// Validate script
		$userInfo = $this->jwtParse($token);
		$validated = $userInfo ? true : false;
		if ($validated == true) {
			$allTokens = $this->getAllUserTokens($userInfo['userID']);
			$user = $this->getUserById($userInfo['userID']);
			$tokenCheck = ($this->searchArray($allTokens, 'token', $token) !== false);
			if (!$tokenCheck) {
				$this->invalidToken();
				return false;
			} else {
				return array(
					"token" => $token,
					"tokenDate" => $userInfo['tokenDate'],
					"tokenExpire" => $userInfo['tokenExpire'],
					"username" => $user['username'],
					"uid" => $this->guestHash(0, 5),
					"group" => $user['group'],
					"groupID" => $user['group_id'],
					"email" => $user['email'],
					"image" => $user['image'],
					"userID" => $user['id'],
					"loggedin" => true,
					"locked" => $user['locked'],
					"tokenList" => $allTokens,
					"authService" => explode('::', $user['auth_service'])[0]
				);
			}
		} else {
			$this->invalidToken();
		}
		return false;
	}
	
	public function defaultUserGroup()
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => 'SELECT * FROM groups WHERE `default` = 1'
			)
		];
		return $this->processQueries($response);
	}
	
	public function getAllTabs()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM tabs ORDER BY `order` ASC',
				'key' => 'tabs'
			),
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM categories ORDER BY `order` ASC',
				'key' => 'categories'
			),
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM groups ORDER BY `group_id` ASC',
				'key' => 'groups'
			),
		];
		return $this->processQueries($response);
	}
	
	public function getUsers()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM users'
			),
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM groups ORDER BY group_id ASC'
			),
		];
		return $this->processQueries($response);
	}
	
	public function usernameTaken($username, $email, $id = null)
	{
		if ($id) {
			$response = [
				array(
					'function' => 'fetch',
					'query' => array(
						'SELECT * FROM users WHERE `id` != ? AND (username = ? COLLATE NOCASE or email = ? COLLATE NOCASE)',
						$id,
						$username,
						$email
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'fetch',
					'query' => array(
						'SELECT * FROM users WHERE username = ? COLLATE NOCASE or email = ? COLLATE NOCASE',
						[$username],
						[$email]
					)
				),
			];
		}
		return $this->processQueries($response);
	}
	
	public function cleanPageName($page)
	{
		return ($page) ? strtolower(str_replace(array('%20', ' ', '-', '_'), '_', $page)) : '';
	}
	
	public function cleanClassName($name)
	{
		return ($name) ? (str_replace(array('%20', ' ', '-', '_'), '-', $name)) : '';
	}
	
	public function reverseCleanClassName($name)
	{
		return ($name) ? (str_replace(array('%20', '-', '_'), ' ', strtolower($name))) : '';
	}
	
	public function getPageList()
	{
		return $GLOBALS['organizrPages'];
	}
	
	public function getPage($page)
	{
		if (!$page) {
			$this->setAPIResponse('error', 'Page not setup', 409);
			return null;
		}
		$pageFunction = 'get_page_' . $this->cleanPageName($page);
		if (function_exists($pageFunction)) {
			return $pageFunction($this);
		} else {
			$this->setAPIResponse('error', 'Page not setup', 409);
			return null;
		}
	}
	
	public function getUserLevel()
	{
		// Grab token
		$requesterToken = isset($this->getallheaders()['Token']) ? $this->getallheaders()['Token'] : (isset($_GET['apikey']) ? $_GET['apikey'] : false);
		$apiKey = ($this->config['organizrAPI']) ?? null;
		// Check token or API key
		// If API key, return 0 for admin
		if (strlen($requesterToken) == 20 && $requesterToken == $apiKey) {
			//DO API CHECK
			return 0;
		} elseif (isset($this->user)) {
			return $this->user['groupID'];
		}
		// All else fails?  return guest id
		return 999;
	}
	
	public function qualifyRequest($accessLevelNeeded, $api = false)
	{
		if ($this->getUserLevel() <= $accessLevelNeeded && $this->getUserLevel() !== null) {
			return true;
		} else {
			if ($api) {
				$this->setAPIResponse('error', 'Not Authorized', 401);
			}
			return false;
		}
	}
	
	public function getImages()
	{
		$allIconsPrep = array();
		$allIcons = array();
		$ignore = array(".", "..", "._.DS_Store", ".DS_Store", ".pydio_id", "index.html");
		$dirname = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'tabs' . DIRECTORY_SEPARATOR;
		$path = 'plugins/images/tabs/';
		$images = scandir($dirname);
		foreach ($images as $image) {
			if (!in_array($image, $ignore)) {
				$allIconsPrep[$image] = array(
					'path' => $path,
					'name' => $image
				);
			}
		}
		$dirname = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'userTabs' . DIRECTORY_SEPARATOR;
		$path = 'plugins/images/userTabs/';
		$images = scandir($dirname);
		foreach ($images as $image) {
			if (!in_array($image, $ignore)) {
				$allIconsPrep[$image] = array(
					'path' => $path,
					'name' => $image
				);
			}
		}
		uksort($allIconsPrep, 'strcasecmp');
		foreach ($allIconsPrep as $item) {
			$allIcons[] = $item['path'] . $item['name'];
		}
		return $allIcons;
	}
	
	public function getImagesSelect()
	{
		$term = $_GET['search'] ?? null;
		$page = $_GET['page'] ?? 1;
		$limit = $_GET['limit'] ?? 20;
		$offset = ($page * $limit) - $limit;
		$goodIcons['results'] = [];
		$goodIcons['limit'] = $limit;
		$goodIcons['page'] = $page;
		$goodIcons['term'] = $term;
		$imageListing = $this->getImages();
		$newImageListing = [];
		foreach ($imageListing as $image) {
			$newImageListing[] = [
				'id' => $image,
				'text' => basename($image)
			];
		}
		foreach ($newImageListing as $k => $v) {
			if (stripos($v['text'], $term) !== false || !$term) {
				$goodIcons['results'][] = $v;
			}
		}
		$total = count($goodIcons['results']);
		$goodIcons['total'] = $total;
		$goodIcons['results'] = array_slice($goodIcons['results'], $offset, $limit);
		$goodIcons['pagination']['more'] = $page < (ceil($total / $limit));
		return $goodIcons;
	}
	
	public function removeImage($image = null)
	{
		if (!$image) {
			$this->setAPIResponse('error', 'No image supplied', 422);
			return false;
		}
		$approvedPath = 'plugins/images/userTabs/';
		$removeImage = $approvedPath . pathinfo($image, PATHINFO_BASENAME);
		if ($this->approvedFileExtension($removeImage)) {
			if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $removeImage)) {
				$this->writeLog('success', 'Image Manager Function -  Deleted Image [' . pathinfo($image, PATHINFO_BASENAME) . ']', $this->user['username']);
				$this->setAPIResponse(null, pathinfo($image, PATHINFO_BASENAME) . ' has been deleted', null);
				return (unlink(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $removeImage));
			} else {
				$this->setAPIResponse('error', $removeImage . ' does not exist', 404);
				return false;
			}
		} else {
			$this->setAPIResponse('error', $removeImage . ' is not approved to be deleted', 409);
			return false;
		}
	}
	
	public function uploadImage()
	{
		$filesCheck = array_filter($_FILES);
		if (!empty($filesCheck) && $this->approvedFileExtension($_FILES['file']['name']) && strpos($_FILES['file']['type'], 'image/') !== false) {
			ini_set('upload_max_filesize', '10M');
			ini_set('post_max_size', '10M');
			$tempFile = $_FILES['file']['tmp_name'];
			$targetPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'userTabs' . DIRECTORY_SEPARATOR;
			$targetFile = $targetPath . $_FILES['file']['name'];
			$this->setAPIResponse(null, pathinfo($_FILES['file']['name'], PATHINFO_BASENAME) . ' has been uploaded', null);
			return move_uploaded_file($tempFile, $targetFile);
		}
	}
	
	public function ping($pings)
	{
		if ($this->qualifyRequest($this->config['pingAuth'], true)) {
			if (!$pings['list']) {
				$this->setAPIResponse('error', 'No ping hostname/IP\'s entered', 409);
				return null;
			}
			$pings = $pings['list'];
			$type = gettype($pings);
			$ping = new Ping("");
			$ping->setTtl(128);
			$ping->setTimeout(2);
			switch ($type) {
				case "array":
					$results = [];
					foreach ($pings as $k => $v) {
						if (strpos($v, ':') !== false) {
							$domain = explode(':', $v)[0];
							$port = explode(':', $v)[1];
							$ping->setHost($domain);
							$ping->setPort($port);
							$latency = $ping->ping('fsockopen');
						} else {
							$ping->setHost($v);
							$latency = $ping->ping();
						}
						if ($latency || $latency === 0) {
							$results[$v] = $latency;
						} else {
							$results[$v] = false;
						}
					}
					break;
				case "string":
					if (strpos($pings, ':') !== false) {
						$domain = explode(':', $pings)[0];
						$port = explode(':', $pings)[1];
						$ping->setHost($domain);
						$ping->setPort($port);
						$latency = $ping->ping('fsockopen');
					} else {
						$ping->setHost($pings);
						$latency = $ping->ping();
					}
					if ($latency || $latency === 0) {
						$results = $latency;
					} else {
						$results = null;
					}
					break;
			}
			return ($results) ?? null;
		}
		return null;
	}
	
	public function getCustomizeAppearance()
	{
		return array(
			'Top Bar' => array(
				array(
					'type' => 'input',
					'name' => 'logo',
					'label' => 'Logo',
					'value' => $this->config['logo']
				),
				array(
					'type' => 'input',
					'name' => 'title',
					'label' => 'Title',
					'value' => $this->config['title']
				),
				array(
					'type' => 'switch',
					'name' => 'useLogo',
					'label' => 'Use Logo instead of Title',
					'value' => $this->config['useLogo'],
					'help' => 'Also sets the title of your site'
				),
				array(
					'type' => 'input',
					'name' => 'description',
					'label' => 'Meta Description',
					'value' => $this->config['description'],
					'help' => 'Used to set the description for SEO meta tags'
				),
			),
			'Login Page' => array(
				array(
					'type' => 'input',
					'name' => 'loginLogo',
					'label' => 'Login Logo',
					'value' => $this->config['loginLogo'],
				),
				array(
					'type' => 'input',
					'name' => 'loginWallpaper',
					'label' => 'Login Wallpaper',
					'value' => $this->config['loginWallpaper'],
					'help' => 'You may enter multiple URL\'s using the CSV format.  i.e. link#1,link#2,link#3'
				),
				array(
					'type' => 'switch',
					'name' => 'useLogoLogin',
					'label' => 'Use Logo instead of Title on Login Page',
					'value' => $this->config['useLogoLogin']
				),
				array(
					'type' => 'switch',
					'name' => 'minimalLoginScreen',
					'label' => 'Minimal Login Screen',
					'value' => $this->config['minimalLoginScreen']
				)
			),
			'Options' => array(
				array(
					'type' => 'switch',
					'name' => 'alternateHomepageHeaders',
					'label' => 'Alternate Homepage Titles',
					'value' => $this->config['alternateHomepageHeaders']
				),
				array(
					'type' => 'switch',
					'name' => 'debugErrors',
					'label' => 'Show Debug Errors',
					'value' => $this->config['debugErrors']
				),
				array(
					'type' => 'switch',
					'name' => 'githubMenuLink',
					'label' => 'Show GitHub Repo Link',
					'value' => $this->config['githubMenuLink']
				),
				array(
					'type' => 'switch',
					'name' => 'organizrSupportMenuLink',
					'label' => 'Show Organizr Support Link',
					'value' => $this->config['organizrSupportMenuLink']
				),
				array(
					'type' => 'switch',
					'name' => 'organizrDocsMenuLink',
					'label' => 'Show Organizr Docs Link',
					'value' => $this->config['organizrDocsMenuLink']
				),
				array(
					'type' => 'switch',
					'name' => 'organizrSignoutMenuLink',
					'label' => 'Show Organizr Sign out & in Button on Sidebar',
					'value' => $this->config['organizrSignoutMenuLink']
				),
				array(
					'type' => 'select',
					'name' => 'unsortedTabs',
					'label' => 'Unsorted Tab Placement',
					'value' => $this->config['unsortedTabs'],
					'options' => array(
						array(
							'name' => 'Top',
							'value' => 'top'
						),
						array(
							'name' => 'Bottom',
							'value' => 'bottom'
						)
					)
				),
				array(
					'type' => 'input',
					'name' => 'gaTrackingID',
					'label' => 'Google Analytics Tracking ID',
					'placeholder' => 'e.g. UA-XXXXXXXXX-X',
					'value' => $this->config['gaTrackingID']
				)
			),
			'Colors & Themes' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom CSS [Can replace colors from above]',
					'html' => '
					<div class="row">
					    <div class="col-lg-12">
					        <div class="panel panel-info">
					            <div class="panel-heading">
					                <span lang="en">Notice</span>
					            </div>
					            <div class="panel-wrapper collapse in" aria-expanded="true">
					                <div class="panel-body">
					                    <span lang="en">The value of #987654 is just a placeholder, you can change to any value you like.</span>
					                    <span lang="en">To revert back to default, save with no value defined in the relevant field.</span>
					                </div>
					            </div>
					        </div>
					    </div>
					</div>
					',
				),
				array(
					'type' => 'blank',
					'label' => ''
				),
				array(
					'type' => 'input',
					'name' => 'headerColor',
					'label' => 'Nav Bar Color',
					'value' => $this->config['headerColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['headerColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'headerTextColor',
					'label' => 'Nav Bar Text Color',
					'value' => $this->config['headerTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['headerTextColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'sidebarColor',
					'label' => 'Side Bar Color',
					'value' => $this->config['sidebarColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['sidebarColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'sidebarTextColor',
					'label' => 'Side Bar Text Color',
					'value' => $this->config['sidebarTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['sidebarTextColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'accentColor',
					'label' => 'Accent Color',
					'value' => $this->config['accentColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['accentColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'accentTextColor',
					'label' => 'Accent Text Color',
					'value' => $this->config['accentTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['accentTextColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'buttonColor',
					'label' => 'Button Color',
					'value' => $this->config['buttonColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['buttonColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'buttonTextColor',
					'label' => 'Button Text Color',
					'value' => $this->config['buttonTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $this->config['buttonTextColor'] . '"'
				),
				array(
					'type' => 'select',
					'name' => 'theme',
					'label' => 'Theme',
					'class' => 'themeChanger',
					'value' => $this->config['theme'],
					'options' => $this->getThemes()
				),
				array(
					'type' => 'select',
					'name' => 'style',
					'label' => 'Style',
					'class' => 'styleChanger',
					'value' => $this->config['style'],
					'options' => array(
						array(
							'name' => 'Light',
							'value' => 'light'
						),
						array(
							'name' => 'Dark',
							'value' => 'dark'
						),
						array(
							'name' => 'Horizontal',
							'value' => 'horizontal'
						)
					)
				)
			),
			'Notifications' => array(
				array(
					'type' => 'select',
					'name' => 'notificationBackbone',
					'class' => 'notifyChanger',
					'label' => 'Type',
					'value' => $this->config['notificationBackbone'],
					'options' => $this->notificationTypesOptions()
				),
				array(
					'type' => 'select',
					'name' => 'notificationPosition',
					'class' => 'notifyPositionChanger',
					'label' => 'Position',
					'value' => $this->config['notificationPosition'],
					'options' => $this->notificationPositionsOptions()
				),
				array(
					'type' => 'html',
					'label' => 'Test Message',
					'html' => '
					<div class="btn-group m-r-10 dropup">
						<button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-outline dropdown-toggle waves-effect waves-light" type="button">
							<i class="fa fa-comment m-r-5"></i>
							<span>Test </span>
						</button>
						<ul role="menu" class="dropdown-menu">
							<li><a onclick="message(\'Test Message\',\'This is a success Message\',activeInfo.settings.notifications.position,\'#FFF\',\'success\',\'5000\');">Success</a></li>
							<li><a onclick="message(\'Test Message\',\'This is a info Message\',activeInfo.settings.notifications.position,\'#FFF\',\'info\',\'5000\');">Info</a></li>
							<li><a onclick="message(\'Test Message\',\'This is a warning Message\',activeInfo.settings.notifications.position,\'#FFF\',\'warning\',\'5000\');">Warning</a></li>
							<li><a onclick="message(\'Test Message\',\'This is a error Message\',activeInfo.settings.notifications.position,\'#FFF\',\'error\',\'5000\');">Error</a></li>
						</ul>
					</div>
					'
				)
			),
			'FavIcon' => array(
				array(
					'type' => 'textbox',
					'name' => 'favIcon',
					'class' => '',
					'label' => 'Fav Icon Code',
					'value' => $this->config['favIcon'],
					'placeholder' => 'Paste Contents from https://realfavicongenerator.net/',
					'attr' => 'rows="10"',
				),
				array(
					'type' => 'html',
					'label' => 'Instructions',
					'html' => '
					<div class="panel panel-default">
						<div class="panel-heading">
							<a href="https://realfavicongenerator.net/" target="_blank"><span class="label label-info m-l-5">Visit FavIcon Site</span></a>
						</div>
						<div class="panel-wrapper collapse in">
							<div class="panel-body">
								<ul class="list-icons">
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click [Select your Favicon picture]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Choose your image to use</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Edit settings to your liking</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> At bottom of page on [Favicon Generator Options] under [Path] choose [I cannot or I do not want to place favicon files at the root of my web site.]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Enter this path <code>plugins/images/faviconCustom</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click [Generate your Favicons and HTML code]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Download and unzip file and place in <code>plugins/images/faviconCustom</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Copy code and paste inside left box</li>
								</ul>
							</div>
						</div>
					</div>
					'
				),
			),
			'Custom CSS' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom CSS [Can replace colors from above]',
					'html' => '<button type="button" class="hidden saveCss btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customCSSEditor" style="height:300px">' . htmlentities($this->config['customCss']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customCss',
					'class' => 'hidden cssTextarea',
					'label' => '',
					'value' => $this->config['customCss'],
					'placeholder' => 'No &lt;style&gt; tags needed',
					'attr' => 'rows="10"',
				),
			),
			'Theme CSS' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Theme CSS [Can replace colors from above]',
					'html' => '<button type="button" class="hidden saveCssTheme btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customThemeCSSEditor" style="height:300px">' . htmlentities($this->config['customThemeCss']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customThemeCss',
					'class' => 'hidden cssThemeTextarea',
					'label' => '',
					'value' => $this->config['customThemeCss'],
					'placeholder' => 'No &lt;style&gt; tags needed',
					'attr' => 'rows="10"',
				),
			),
			'Custom Javascript' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom Javascript',
					'html' => '<button type="button" class="hidden saveJava btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customJavaEditor" style="height:300px">' . htmlentities($this->config['customJava']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customJava',
					'class' => 'hidden javaTextarea',
					'label' => '',
					'value' => $this->config['customJava'],
					'placeholder' => 'No &lt;script&gt; tags needed',
					'attr' => 'rows="10"',
				),
			),
			'Theme Javascript' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Theme Javascript',
					'html' => '<button type="button" class="hidden saveJavaTheme btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customThemeJavaEditor" style="height:300px">' . htmlentities($this->config['customThemeJava']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customThemeJava',
					'class' => 'hidden javaThemeTextarea',
					'label' => '',
					'value' => $this->config['customThemeJava'],
					'placeholder' => 'No &lt;script&gt; tags needed',
					'attr' => 'rows="10"',
				),
			),
		);
	}
	
	public function loadAppearance()
	{
		$appearance['logo'] = $this->config['logo'];
		$appearance['title'] = $this->config['title'];
		$appearance['useLogo'] = $this->config['useLogo'];
		$appearance['useLogoLogin'] = $this->config['useLogoLogin'];
		$appearance['headerColor'] = $this->config['headerColor'];
		$appearance['headerTextColor'] = $this->config['headerTextColor'];
		$appearance['sidebarColor'] = $this->config['sidebarColor'];
		$appearance['headerTextColor'] = $this->config['headerTextColor'];
		$appearance['sidebarTextColor'] = $this->config['sidebarTextColor'];
		$appearance['accentColor'] = $this->config['accentColor'];
		$appearance['accentTextColor'] = $this->config['accentTextColor'];
		$appearance['buttonColor'] = $this->config['buttonColor'];
		$appearance['buttonTextColor'] = $this->config['buttonTextColor'];
		$appearance['buttonTextHoverColor'] = $this->config['buttonTextHoverColor'];
		$appearance['buttonHoverColor'] = $this->config['buttonHoverColor'];
		$appearance['loginWallpaper'] = $this->config['loginWallpaper'];
		$appearance['loginLogo'] = $this->config['loginLogo'];
		$appearance['customCss'] = $this->config['customCss'];
		$appearance['customThemeCss'] = $this->config['customThemeCss'];
		$appearance['customJava'] = $this->config['customJava'];
		$appearance['customThemeJava'] = $this->config['customThemeJava'];
		return $appearance;
	}
	
	public function getSettingsMain()
	{
		return array(
			'Github' => array(
				array(
					'type' => 'select',
					'name' => 'branch',
					'label' => 'Branch',
					'value' => $this->config['branch'],
					'options' => $this->getBranches(),
					'disabled' => $this->docker,
					'help' => ($this->docker) ? 'Since you are using the Official Docker image, Change the image to change the branch' : 'Choose which branch to download from'
				),
				array(
					'type' => 'button',
					'name' => 'force-install-branch',
					'label' => 'Force Install Branch',
					'class' => 'updateNow',
					'icon' => 'fa fa-download',
					'text' => 'Retrieve',
					'attr' => ($this->docker) ? 'title="You can just restart your docker to update"' : '',
					'help' => ($this->docker) ? 'Since you are using the official Docker image, you can just restart your Docker container to update Organizr' : 'This will re-download all of the source files for Organizr'
				)
			),
			'API' => array(
				array(
					'type' => 'password-alt',
					'name' => 'organizrAPI',
					'label' => 'Organizr API',
					'value' => $this->config['organizrAPI']
				),
				array(
					'type' => 'button',
					'label' => 'Generate New API Key',
					'class' => 'newAPIKey',
					'icon' => 'fa fa-refresh',
					'text' => 'Generate'
				)
			),
			'Authentication' => array(
				array(
					'type' => 'select',
					'name' => 'authType',
					'id' => 'authSelect',
					'label' => 'Authentication Type',
					'value' => $this->config['authType'],
					'options' => $this->getAuthTypes()
				),
				array(
					'type' => 'select',
					'name' => 'authBackend',
					'id' => 'authBackendSelect',
					'label' => 'Authentication Backend',
					'class' => 'backendAuth switchAuth',
					'value' => $this->config['authBackend'],
					'options' => $this->getAuthBackends()
				),
				array(
					'type' => 'password-alt',
					'name' => 'plexToken',
					'class' => 'plexAuth switchAuth',
					'label' => 'Plex Token',
					'value' => $this->config['plexToken'],
					'placeholder' => 'Use Get Token Button'
				),
				array(
					'type' => 'button',
					'label' => 'Get Plex Token',
					'class' => 'getPlexTokenAuth plexAuth switchAuth',
					'icon' => 'fa fa-ticket',
					'text' => 'Retrieve',
					'attr' => 'onclick="showPlexTokenForm(\'#settings-main-form [name=plexToken]\')"'
				),
				array(
					'type' => 'password-alt',
					'name' => 'plexID',
					'class' => 'plexAuth switchAuth',
					'label' => 'Plex Machine',
					'value' => $this->config['plexID'],
					'placeholder' => 'Use Get Plex Machine Button'
				),
				array(
					'type' => 'button',
					'label' => 'Get Plex Machine',
					'class' => 'getPlexMachineAuth plexAuth switchAuth',
					'icon' => 'fa fa-id-badge',
					'text' => 'Retrieve',
					'attr' => 'onclick="showPlexMachineForm(\'#settings-main-form [name=plexID]\')"'
				),
				array(
					'type' => 'input',
					'name' => 'plexAdmin',
					'label' => 'Plex Admin Username',
					'class' => 'plexAuth switchAuth',
					'value' => $this->config['plexAdmin'],
					'placeholder' => 'Admin username for Plex'
				),
				array(
					'type' => 'switch',
					'name' => 'plexoAuth',
					'label' => 'Enable Plex oAuth',
					'class' => 'plexAuth switchAuth',
					'value' => $this->config['plexoAuth']
				),
				array(
					'type' => 'switch',
					'name' => 'plexStrictFriends',
					'label' => 'Strict Plex Friends ',
					'class' => 'plexAuth switchAuth',
					'value' => $this->config['plexStrictFriends'],
					'help' => 'Enabling this will only allow Friends that have shares to the Machine ID entered above to login, Having this disabled will allow all Friends on your Friends list to login'
				),
				array(
					'type' => 'switch',
					'name' => 'ignoreTFALocal',
					'label' => 'Ignore External 2FA on Local Subnet',
					'value' => $this->config['ignoreTFALocal'],
					'help' => 'Enabling this will bypass external 2FA security if user is on local Subnet'
				),
				array(
					'type' => 'input',
					'name' => 'authBackendHost',
					'class' => 'ldapAuth ftpAuth switchAuth',
					'label' => 'Host Address',
					'value' => $this->config['authBackendHost'],
					'placeholder' => 'http{s) | ftp(s) | ldap(s)://hostname:port'
				),
				array(
					'type' => 'input',
					'name' => 'authBaseDN',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Host Base DN',
					'value' => $this->config['authBaseDN'],
					'placeholder' => 'cn=%s,dc=sub,dc=domain,dc=com'
				),
				array(
					'type' => 'select',
					'name' => 'ldapType',
					'id' => 'ldapType',
					'label' => 'LDAP Backend Type',
					'class' => 'ldapAuth switchAuth',
					'value' => $this->config['ldapType'],
					'options' => $this->getLDAPOptions()
				),
				array(
					'type' => 'input',
					'name' => 'authBackendHostPrefix',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Account Prefix',
					'id' => 'authBackendHostPrefix-input',
					'value' => $this->config['authBackendHostPrefix'],
					'placeholder' => 'Account prefix - i.e. Controller\ from Controller\Username for AD - uid= for OpenLDAP'
				),
				array(
					'type' => 'input',
					'name' => 'authBackendHostSuffix',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Account Suffix',
					'id' => 'authBackendHostSuffix-input',
					'value' => $this->config['authBackendHostSuffix'],
					'placeholder' => 'Account suffix - start with comma - ,ou=people,dc=domain,dc=tld'
				),
				array(
					'type' => 'input',
					'name' => 'ldapBindUsername',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Bind Username',
					'value' => $this->config['ldapBindUsername'],
					'placeholder' => ''
				),
				array(
					'type' => 'password',
					'name' => 'ldapBindPassword',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Password',
					'value' => $this->config['ldapBindPassword']
				),
				array(
					'type' => 'html',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Account DN',
					'html' => '<span id="accountDN" class="ldapAuth switchAuth">' . $this->config['authBackendHostPrefix'] . 'TestAcct' . $this->config['authBackendHostSuffix'] . '</span>'
				),
				array(
					'type' => 'switch',
					'name' => 'ldapSSL',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Enable LDAP SSL',
					'value' => $this->config['ldapSSL'],
					'help' => 'This will enable the use of SSL for LDAP connections'
				),
				array(
					'type' => 'switch',
					'name' => 'ldapSSL',
					'class' => 'ldapAuth switchAuth',
					'label' => 'Enable LDAP TLS',
					'value' => $this->config['ldapTLS'],
					'help' => 'This will enable the use of TLS for LDAP connections'
				),
				array(
					'type' => 'button',
					'name' => 'test-button-ldap',
					'label' => 'Test Connection',
					'icon' => 'fa fa-flask',
					'class' => 'ldapAuth switchAuth',
					'text' => 'Test Connection',
					'attr' => 'onclick="testAPIConnection(\'ldap\')"',
					'help' => 'Remember! Please save before using the test button!'
				),
				array(
					'type' => 'button',
					'name' => 'test-button-ldap-login',
					'label' => 'Test Login',
					'icon' => 'fa fa-flask',
					'class' => 'ldapAuth switchAuth',
					'text' => 'Test Login',
					'attr' => 'onclick="showLDAPLoginTest()"'
				),
				array(
					'type' => 'input',
					'name' => 'embyURL',
					'class' => 'embyAuth switchAuth',
					'label' => 'Emby URL',
					'value' => $this->config['embyURL'],
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port'
				),
				array(
					'type' => 'password-alt',
					'name' => 'embyToken',
					'class' => 'embyAuth switchAuth',
					'label' => 'Emby Token',
					'value' => $this->config['embyToken'],
					'placeholder' => ''
				),
				array(
					'type' => 'input',
					'name' => 'jellyfinURL',
					'class' => 'jellyfinAuth switchAuth',
					'label' => 'Jellyfin URL',
					'value' => $this->config['jellyfinURL'],
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port'
				),
				array(
					'type' => 'password-alt',
					'name' => 'jellyfinToken',
					'class' => 'jellyfinAuth switchAuth',
					'label' => 'Jellyfin Token',
					'value' => $this->config['jellyfinToken'],
					'placeholder' => ''
				),
			),
			'Security' => array(
				array(
					'type' => 'number',
					'name' => 'loginAttempts',
					'label' => 'Max Login Attempts',
					'value' => $this->config['loginAttempts'],
					'placeholder' => ''
				),
				array(
					'type' => 'select',
					'name' => 'loginLockout',
					'label' => 'Login Lockout Seconds',
					'value' => $this->config['loginLockout'],
					'options' => $this->timeOptions()
				),
				array(
					'type' => 'number',
					'name' => 'lockoutTimeout',
					'label' => 'Inactivity Timer [Minutes]',
					'value' => $this->config['lockoutTimeout'],
					'placeholder' => ''
				),
				array(
					'type' => 'select',
					'name' => 'lockoutMinAuth',
					'label' => 'Lockout Groups From',
					'value' => $this->config['lockoutMinAuth'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'select',
					'name' => 'lockoutMaxAuth',
					'label' => 'Lockout Groups To',
					'value' => $this->config['lockoutMaxAuth'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'switch',
					'name' => 'lockoutSystem',
					'label' => 'Inactivity Lock',
					'value' => $this->config['lockoutSystem']
				),
				array(
					'type' => 'select',
					'name' => 'debugAreaAuth',
					'label' => 'Minimum Authentication for Debug Area',
					'value' => $this->config['debugAreaAuth'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'switch',
					'name' => 'authDebug',
					'label' => 'Nginx Auth Debug',
					'help' => 'Important! Do not keep this enabled for too long as this opens up Authentication while testing.',
					'value' => $this->config['authDebug'],
					'class' => 'authDebug'
				),
				array(
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => 'sandbox-select',
					'name' => 'sandbox',
					'label' => 'iFrame Sandbox',
					'value' => $this->config['sandbox'],
					'help' => 'WARNING! This can potentially mess up your iFrames',
					'options' => array(
						array(
							'name' => 'Allow Presentation',
							'value' => 'allow-presentation'
						),
						array(
							'name' => 'Allow Forms',
							'value' => 'allow-forms'
						),
						array(
							'name' => 'Allow Same Origin',
							'value' => 'allow-same-origin'
						),
						array(
							'name' => 'Allow Pointer Lock',
							'value' => 'allow-pointer-lock'
						),
						array(
							'name' => 'Allow Scripts',
							'value' => 'allow-scripts'
						), array(
							'name' => 'Allow Popups',
							'value' => 'allow-popups'
						),
						array(
							'name' => 'Allow Modals',
							'value' => 'allow-modals'
						),
						array(
							'name' => 'Allow Top Navigation',
							'value' => 'allow-top-navigation'
						),
						array(
							'name' => 'Allow Downloads',
							'value' => 'allow-downloads'
						),
					)
				),
				array(
					'type' => 'switch',
					'name' => 'traefikAuthEnable',
					'label' => 'Enable Traefik Auth Redirect',
					'help' => 'This will enable the webserver to forward errors so traefik will accept them',
					'value' => $this->config['traefikAuthEnable']
				),
			),
			'Performance' => array(
				array(
					'type' => 'switch',
					'name' => 'performanceDisableIconDropdown',
					'label' => 'Disable Icon Dropdown',
					'help' => 'Disable select dropdown boxes on new and edit tab forms',
					'value' => $this->config['performanceDisableIconDropdown'],
				),
				array(
					'type' => 'switch',
					'name' => 'performanceDisableImageDropdown',
					'label' => 'Disable Image Dropdown',
					'help' => 'Disable select dropdown boxes on new and edit tab forms',
					'value' => $this->config['performanceDisableImageDropdown'],
				),
			),
			'Login' => array(
				array(
					'type' => 'password-alt',
					'name' => 'registrationPassword',
					'label' => 'Registration Password',
					'help' => 'Sets the password for the Registration form on the login screen',
					'value' => $this->config['registrationPassword'],
				),
				array(
					'type' => 'switch',
					'name' => 'hideRegistration',
					'label' => 'Hide Registration',
					'help' => 'Enable this to hide the Registration button on the login screen',
					'value' => $this->config['hideRegistration'],
				),
				array(
					'type' => 'number',
					'name' => 'rememberMeDays',
					'label' => 'Remember Me Length',
					'help' => 'Number of days cookies and tokens will be valid for',
					'value' => $this->config['rememberMeDays'],
					'placeholder' => '',
					'attr' => 'min="1"'
				),
				array(
					'type' => 'switch',
					'name' => 'rememberMe',
					'label' => 'Remember Me',
					'help' => 'Default status of Remember Me button on login screen',
					'value' => $this->config['rememberMe'],
				),
				array(
					'type' => 'input',
					'name' => 'localIPFrom',
					'label' => 'Override Local IP From',
					'value' => $this->config['localIPFrom'],
					'placeholder' => 'i.e. 123.123.123.123',
					'help' => 'IPv4 only at the moment - This will set your login as local if your IP falls within the From and To'
				),
				array(
					'type' => 'input',
					'name' => 'localIPTo',
					'label' => 'Override Local IP To',
					'value' => $this->config['localIPTo'],
					'placeholder' => 'i.e. 123.123.123.123',
					'help' => 'IPv4 only at the moment - This will set your login as local if your IP falls within the From and To'
				),
				array(
					'type' => 'input',
					'name' => 'wanDomain',
					'label' => 'WAN Domain',
					'value' => $this->config['wanDomain'],
					'placeholder' => 'only domain and tld - i.e. domain.com',
					'help' => 'Enter domain if you wish to be forwarded to a local address - Local Address filled out on next item'
				),
				array(
					'type' => 'input',
					'name' => 'localAddress',
					'label' => 'Local Address',
					'value' => $this->config['localAddress'],
					'placeholder' => 'http://home.local',
					'help' => 'Full local address of organizr install - i.e. http://home.local or http://192.168.0.100'
				),
				array(
					'type' => 'switch',
					'name' => 'enableLocalAddressForward',
					'label' => 'Enable Local Address Forward',
					'help' => 'Enables the local address forward if on local address and accessed from WAN Domain',
					'value' => $this->config['enableLocalAddressForward'],
				),
			),
			'Auth Proxy' => array(
				array(
					'type' => 'switch',
					'name' => 'authProxyEnabled',
					'label' => 'Auth Proxy',
					'help' => 'Enable option to set Auth Proxy Header Login',
					'value' => $this->config['authProxyEnabled'],
				),
				array(
					'type' => 'input',
					'name' => 'authProxyHeaderName',
					'label' => 'Auth Proxy Header Name',
					'value' => $this->config['authProxyHeaderName'],
					'placeholder' => 'i.e. X-Forwarded-User',
					'help' => 'Please choose a unique value for added security'
				),
				array(
					'type' => 'input',
					'name' => 'authProxyWhitelist',
					'label' => 'Auth Proxy Whitelist',
					'value' => $this->config['authProxyWhitelist'],
					'placeholder' => 'i.e. 10.0.0.0/24 or 10.0.0.20',
					'help' => 'IPv4 only at the moment - This must be set to work, will accept subnet or IP address'
				),
			),
			'Ping' => array(
				array(
					'type' => 'select',
					'name' => 'pingAuth',
					'label' => 'Minimum Authentication',
					'value' => $this->config['pingAuth'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'select',
					'name' => 'pingAuthMessage',
					'label' => 'Minimum Authentication for Message and Sound',
					'value' => $this->config['pingAuthMessage'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'select',
					'name' => 'pingOnlineSound',
					'label' => 'Online Sound',
					'value' => $this->config['pingOnlineSound'],
					'options' => $this->getSounds()
				),
				array(
					'type' => 'select',
					'name' => 'pingOfflineSound',
					'label' => 'Offline Sound',
					'value' => $this->config['pingOfflineSound'],
					'options' => $this->getSounds()
				),
				array(
					'type' => 'switch',
					'name' => 'pingMs',
					'label' => 'Show Ping Time',
					'value' => $this->config['pingMs']
				),
				array(
					'type' => 'switch',
					'name' => 'statusSounds',
					'label' => 'Enable Notify Sounds',
					'value' => $this->config['statusSounds'],
					'help' => 'Will play a sound if the server goes down and will play sound if comes back up.',
				),
				array(
					'type' => 'select',
					'name' => 'pingAuthMs',
					'label' => 'Minimum Authentication for Time Display',
					'value' => $this->config['pingAuthMs'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'select',
					'name' => 'adminPingRefresh',
					'label' => 'Admin Refresh Seconds',
					'value' => $this->config['adminPingRefresh'],
					'options' => $this->timeOptions()
				),
				array(
					'type' => 'select',
					'name' => 'otherPingRefresh',
					'label' => 'Everyone Refresh Seconds',
					'value' => $this->config['otherPingRefresh'],
					'options' => $this->timeOptions()
				),
			)
		);
	}
	
	public function getSettingsSSO()
	{
		return array(
			'FYI' => array(
				array(
					'type' => 'html',
					'label' => 'Important Information',
					'override' => 12,
					'html' => '
				<div class="row">
						    <div class="col-lg-12">
						        <div class="panel panel-info">
						            <div class="panel-heading">
						                <span lang="en">Notice</span>
						            </div>
						            <div class="panel-wrapper collapse in" aria-expanded="true">
						                <div class="panel-body">
						                    <span lang="en">This is not the same as database authentication - i.e. Plex Authentication | Emby Authentication | FTP Authentication<br/>Click Main on the sub-menu above.</span>
						                </div>
						            </div>
						        </div>
						    </div>
						</div>
				'
				)
			),
			'Plex' => array(
				array(
					'type' => 'password-alt',
					'name' => 'plexToken',
					'label' => 'Plex Token',
					'value' => $this->config['plexToken'],
					'placeholder' => 'Use Get Token Button'
				),
				array(
					'type' => 'button',
					'label' => 'Get Plex Token',
					'icon' => 'fa fa-ticket',
					'text' => 'Retrieve',
					'attr' => 'onclick="showPlexTokenForm(\'#sso-form [name=plexToken]\')"'
				),
				array(
					'type' => 'password-alt',
					'name' => 'plexID',
					'label' => 'Plex Machine',
					'value' => $this->config['plexID'],
					'placeholder' => 'Use Get Plex Machine Button'
				),
				array(
					'type' => 'button',
					'label' => 'Get Plex Machine',
					'icon' => 'fa fa-id-badge',
					'text' => 'Retrieve',
					'attr' => 'onclick="showPlexMachineForm(\'#sso-form [name=plexID]\')"'
				),
				array(
					'type' => 'input',
					'name' => 'plexAdmin',
					'label' => 'Admin Username',
					'value' => $this->config['plexAdmin'],
					'placeholder' => 'Admin username for Plex'
				),
				array(
					'type' => 'blank',
					'label' => ''
				),
				array(
					'type' => 'html',
					'label' => 'Plex Note',
					'html' => '<span lang="en">Please make sure both Token and Machine are filled in</span>'
				),
				array(
					'type' => 'switch',
					'name' => 'ssoPlex',
					'label' => 'Enable',
					'value' => $this->config['ssoPlex']
				)
			),
			'Tautulli' => array(
				array(
					'type' => 'input',
					'name' => 'tautulliURL',
					'label' => 'Tautulli URL',
					'value' => $this->config['tautulliURL'],
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port'
				),
				array(
					'type' => 'switch',
					'name' => 'ssoTautulli',
					'label' => 'Enable',
					'value' => $this->config['ssoTautulli']
				)
			),
			'Ombi' => array(
				array(
					'type' => 'input',
					'name' => 'ombiURL',
					'label' => 'Ombi URL',
					'value' => $this->config['ombiURL'],
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port'
				),
				array(
					'type' => 'password-alt',
					'name' => 'ombiToken',
					'label' => 'Token',
					'value' => $this->config['ombiToken']
				),
				array(
					'type' => 'input',
					'name' => 'ombiFallbackUser',
					'label' => 'Ombi Fallback User',
					'value' => $this->config['ombiFallbackUser'],
					'help' => 'Organizr will request an Ombi User Token based off of this user credentials'
				),
				array(
					'type' => 'password-alt',
					'name' => 'ombiFallbackPassword',
					'label' => 'Ombi Fallback Password',
					'value' => $this->config['ombiFallbackPassword']
				),
				array(
					'type' => 'switch',
					'name' => 'ssoOmbi',
					'label' => 'Enable',
					'value' => $this->config['ssoOmbi']
				)
			),
			'Jellyfin' => array(
				array(
					'type' => 'input',
					'name' => 'jellyfinURL',
					'label' => 'Jellyfin URL',
					'value' => $this->config['jellyfinURL'],
					'help' => 'Please make sure to use the same (sub)domain to access Jellyfin as Organizr\'s',
					'placeholder' => 'http(s)://hostname:port'
				),
				array(
					'type' => 'switch',
					'name' => 'ssoJellyfin',
					'label' => 'Enable',
					'value' => $this->config['ssoJellyfin']
				)
			)
		);
	}
	
	public function updateConfigMultiple($array)
	{
		return ($this->updateConfig($array)) ? true : false;
	}
	
	public function updateConfigItems($array)
	{
		if (!count($array)) {
			$this->setAPIResponse('error', 'No data submitted', 409);
			return false;
		}
		$newItem = array();
		foreach ($array as $k => $v) {
			$v = $v ?? '';
			switch ($v) {
				case 'true':
					$v = (bool)true;
					break;
				case 'false':
					$v = (bool)false;
					break;
			}
			// Hash
			if ((stripos($k, 'password') !== false)) {
				if (!$this->isEncrypted($v)) {
					if ($v !== '') {
						$v = $this->encrypt($v);
					}
				}
			}
			if (strtolower($k) !== 'formkey') {
				$newItem[$k] = $v;
			}
		}
		$this->setAPIResponse('success', 'Config items updated', 200);
		return ($this->updateConfig($newItem)) ? true : false;
	}
	
	public function updateConfigItem($array)
	{
		$array['value'] = $array['value'] ?? '';
		switch ($array['value']) {
			case 'true':
				$array['value'] = (bool)true;
				break;
			case 'false':
				$array['value'] = (bool)false;
				break;
		}
		// Hash
		if ($array['type'] == 'password') {
			$array['value'] = $this->encrypt($array['value']);
		}
		$newItem = array(
			$array['name'] => $array['value']
		);
		return ($this->updateConfig($newItem)) ? true : false;
	}
	
	public function testWizardPath($array)
	{
		if ($this->hasDB()) {
			$this->setAPIResponse('error', 'Endpoint disabled as database already exists', 401);
			return false;
		}
		$path = $array['path'] ?? null;
		if (file_exists($path)) {
			if (is_writable($path)) {
				$this->setAPIResponse('success', 'Path exists and is writable', 200);
				return true;
			}
		} else {
			if (is_writable(dirname($path, 1))) {
				if (mkdir($path, 0760, true)) {
					$this->setAPIResponse('success', 'Path is writable - Creating now', 200);
					return true;
				}
			}
		}
		$this->setAPIResponse('error', 'Path is not writable', 401);
		return false;
	}
	
	public function wizardConfig($array)
	{
		$dbName = $array['dbName'] ?? null;
		$path = $array['dbPath'] ?? null;
		$license = $array['license'] ?? null;
		$hashKey = $array['hashKey'] ?? null;
		$api = $array['api'] ?? null;
		$registrationPassword = $array['registrationPassword'] ?? null;
		$username = $array['username'] ?? null;
		$password = $array['password'] ?? null;
		$email = $array['email'] ?? null;
		$validation = array(
			'dbName' => $dbName,
			'dbPath' => $path,
			'license' => $license,
			'hashKey' => $hashKey,
			'api' => $api,
			'registrationPassword' => $registrationPassword,
			'username' => $username,
			'password' => $password,
			'email' => $email,
		);
		foreach ($validation as $k => $v) {
			if ($v == null) {
				$this->setAPIResponse('error', '[' . $k . '] cannot be empty', 422);
				return false;
			}
		}
		$path = $this->cleanDirectory($path);
		if (file_exists($path)) {
			if (!is_writable($path)) {
				$this->setAPIResponse('error', '[' . $path . ']  is not writable', 422);
				return false;
			}
		} else {
			if (is_writable(dirname($path, 1))) {
				if (!mkdir($path, 0760, true)) {
					$this->setAPIResponse('error', '[' . $path . ']  is not writable', 422);
					return false;
				}
			} else {
				$this->setAPIResponse('error', '[' . $path . ']  is not writable', 422);
				return false;
			}
		}
		$dbName = $this->dbExtension($dbName);
		$configVersion = $this->version;
		$configArray = array(
			'dbName' => $dbName,
			'dbLocation' => $path,
			'license' => $license,
			'organizrHash' => $hashKey,
			'organizrAPI' => $api,
			'registrationPassword' => $registrationPassword,
			'uuid' => $this->gen_uuid()
		);
		// Create Config
		if ($this->createConfig($configArray)) {
			$this->config = $this->config();
			$this->refreshCookieName();
			$this->connectDB();
			// Call DB Create
			if ($this->createDB($path)) {
				// Add in first user
				if ($this->createFirstAdmin($username, $password, $email)) {
					if ($this->createToken($username, $email, 1)) {
						return true;
					} else {
						$this->setAPIResponse('error', 'error creating token', 500);
					}
				} else {
					$this->setAPIResponse('error', 'error creating admin', 500);
				}
			} else {
				$this->setAPIResponse('error', 'error creating database', 500);
			}
			
		} else {
			$this->setAPIResponse('error', 'error creating config', 500);
		}
		return false;
	}
	
	public function createDB($path, $migration = false)
	{
		
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		$response = [
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `users` (
				`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
				`username`	TEXT UNIQUE,
				`password`	TEXT,
				`email`	TEXT,
				`plex_token`	TEXT,
				`group`	TEXT,
				`group_id`	INTEGER,
				`locked`	INTEGER,
				`image`	TEXT,
				`register_date`	DATE,
				`auth_service`	TEXT DEFAULT \'internal\'
				);'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `chatroom` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `username`	TEXT,
			        `gravatar`	TEXT,
			        `uid`	TEXT,
			        `date` DATE,
			        `ip` TEXT,
			        `message` TEXT
			    );'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `tokens` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `token`	TEXT UNIQUE,
			        `user_id`	INTEGER,
			        `browser`	TEXT,
			        `ip`	TEXT,
			        `created` DATE,
			        `expires` DATE
			    );'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `groups` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `group`	TEXT UNIQUE,
			        `group_id`	INTEGER,
			        `image`	TEXT,
			        `default` INTEGER
			    );'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `categories` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `order`	INTEGER,
			        `category`	TEXT UNIQUE,
			        `category_id`	INTEGER,
			        `image`	TEXT,
			        `default` INTEGER
			    );'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `tabs` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `order`	INTEGER,
			        `category_id`	INTEGER,
			        `name`	TEXT,
			        `url`	TEXT,
			        `url_local`	TEXT,
			        `default`	INTEGER,
			        `enabled`	INTEGER,
			        `group_id`	INTEGER,
			        `image`	TEXT,
			        `type`	INTEGER,
			        `splash`	INTEGER,
			        `ping`		INTEGER,
			        `ping_url`	TEXT,
			        `timeout`	INTEGER,
			        `timeout_ms`	INTEGER,
			        `preload`	INTEGER
			    );'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `options` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `name`	TEXT UNIQUE,
			        `value`	TEXT
			    );'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `invites` (
			        `id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			        `code`	TEXT UNIQUE,
			        `date`	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			        `email`	TEXT,
			        `username`	TEXT,
			        `dateused`	TIMESTAMP,
			        `usedby`	TEXT,
			        `ip`	TEXT,
			        `valid`	TEXT,
			        `type` TEXT
			    );'
			),
		];
		return $this->processQueries($response, $migration);
	}
	
	public function createFirstAdmin($username, $password, $email)
	{
		
		$userInfo = [
			'username' => $username,
			'password' => password_hash($password, PASSWORD_BCRYPT),
			'email' => $email,
			'group' => 'Admin',
			'group_id' => 0,
			'image' => $this->gravatar($email),
			'register_date' => $this->currentTime,
		];
		$groupInfo0 = [
			'group' => 'Admin',
			'group_id' => 0,
			'default' => false,
			'image' => 'plugins/images/groups/admin.png',
		];
		$groupInfo1 = [
			'group' => 'Co-Admin',
			'group_id' => 1,
			'default' => false,
			'image' => 'plugins/images/groups/coadmin.png',
		];
		$groupInfo2 = [
			'group' => 'Super User',
			'group_id' => 2,
			'default' => false,
			'image' => 'plugins/images/groups/superuser.png',
		];
		$groupInfo3 = [
			'group' => 'Power User',
			'group_id' => 3,
			'default' => false,
			'image' => 'plugins/images/groups/poweruser.png',
		];
		$groupInfo4 = [
			'group' => 'User',
			'group_id' => 4,
			'default' => true,
			'image' => 'plugins/images/groups/user.png',
		];
		$groupInfoGuest = [
			'group' => 'Guest',
			'group_id' => 999,
			'default' => false,
			'image' => 'plugins/images/groups/guest.png',
		];
		$settingsInfo = [
			'order' => 1,
			'category_id' => 0,
			'name' => 'Settings',
			'url' => 'api/v2/page/settings',
			'default' => false,
			'enabled' => true,
			'group_id' => 1,
			'image' => 'fontawesome::cog',
			'type' => 0
		];
		$homepageInfo = [
			'order' => 2,
			'category_id' => 0,
			'name' => 'Homepage',
			'url' => 'api/v2/page/homepage',
			'default' => false,
			'enabled' => false,
			'group_id' => 4,
			'image' => 'fontawesome::home',
			'type' => 0
		];
		$unsortedInfo = [
			'order' => 1,
			'category' => 'Unsorted',
			'category_id' => 0,
			'image' => 'plugins/images/categories/unsorted.png',
			'default' => true
		];
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [users]',
					$userInfo
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$groupInfo0
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$groupInfo1
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$groupInfo2
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$groupInfo3
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$groupInfo4
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$groupInfoGuest
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [tabs]',
					$settingsInfo
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [tabs]',
					$homepageInfo
				)
			),
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [categories]',
					$unsortedInfo
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getUserByUsernameAndEmail($username, $email)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE',
					[$username],
					[$email]
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function createToken($username, $email, $days = 1)
	{
		$days = ($days > 365) ? 365 : $days;
		//Quick get user ID
		$result = $this->getUserByUsernameAndEmail($username, $email);
		// Create JWT
		// Set key
		// SHA256 Encryption
		$signer = new Lcobucci\JWT\Signer\Hmac\Sha256();
		// Start Builder
		$jwttoken = (new Lcobucci\JWT\Builder())->issuedBy('Organizr')// Configures the issuer (iss claim)
		->permittedFor('Organizr')// Configures the audience (aud claim)
		->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
		->issuedAt(time())// Configures the time that the token was issue (iat claim)
		->expiresAt(time() + (86400 * $days))// Configures the expiration time of the token (exp claim)
		->withClaim('username', $result['username'])// Configures a new claim, called "username"
		->withClaim('group', $result['group'])// Configures a new claim, called "group"
		->withClaim('groupID', $result['group_id'])// Configures a new claim, called "groupID"
		->withClaim('email', $result['email'])// Configures a new claim, called "email"
		->withClaim('image', $result['image'])// Configures a new claim, called "image"
		->withClaim('userID', $result['id'])// Configures a new claim, called "image"
		->sign($signer, $this->config['organizrHash'])// creates a signature using "testing" as key
		->getToken(); // Retrieves the generated token
		$jwttoken->getHeaders(); // Retrieves the token headers
		$jwttoken->getClaims(); // Retrieves the token claims
		$this->coookie('set', $this->cookieName, $jwttoken, $days);
		// Add token to DB
		$addToken = [
			'token' => (string)$jwttoken,
			'user_id' => $result['id'],
			'created' => $this->currentTime,
			'browser' => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : null,
			'ip' => $this->userIP(),
			'expires' => gmdate("Y-m-d\TH:i:s\Z", time() + (86400 * $days))
		];
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [tokens]',
					$addToken
				)
			),
		];
		$token = $this->processQueries($response);
		return $jwttoken;
		
	}
	
	public function login($array)
	{
		// Grab username, Password & other optional items from api call
		$username = $array['username'] ?? null;
		$password = $array['password'] ?? null;
		$oAuth = $array['oAuth'] ?? null;
		$oAuthType = $array['oAuthType'] ?? null;
		$remember = $array['remember'] ?? null;
		$tfaCode = $array['tfaCode'] ?? null;
		$loginAttempts = $array['loginAttempts'] ?? null;
		$output = $array['output'] ?? null;
		$username = (strpos($this->config['authBackend'], 'emby') !== false) ? $username : strtolower($username);
		$days = (isset($remember)) ? $this->config['rememberMeDays'] : 1;
		// Set  other variables
		$function = 'plugin_auth_' . $this->config['authBackend'];
		$authSuccess = false;
		$authProxy = false;
		// Check Login attempts and kill if over limit
		if ($loginAttempts > $this->config['loginAttempts'] || isset($_COOKIE['lockout'])) {
			$this->coookieSeconds('set', 'lockout', $this->config['loginLockout'], $this->config['loginLockout']);
			$this->setAPIResponse('error', 'User is locked out', 403);
			return false;
		}
		// Check if Auth Proxy is enabled
		if ($this->config['authProxyEnabled'] && $this->config['authProxyHeaderName'] !== '' && $this->config['authProxyWhitelist'] !== '') {
			if (isset($this->getallheaders()[$this->config['authProxyHeaderName']])) {
				$usernameHeader = isset($this->getallheaders()[$this->config['authProxyHeaderName']]) ? $this->getallheaders()[$this->config['authProxyHeaderName']] : $username;
				$this->writeLog('success', 'Auth Proxy Function - Starting Verification for IP: ' . $this->userIP() . ' for request on: ' . $_SERVER['REMOTE_ADDR'] . ' against IP/Subnet: ' . $this->config['authProxyWhitelist'], $usernameHeader);
				$whitelistRange = $this->analyzeIP($this->config['authProxyWhitelist']);
				$authProxy = $this->authProxyRangeCheck($whitelistRange['from'], $whitelistRange['to']);
				$username = ($authProxy) ? $usernameHeader : $username;
				if ($authProxy) {
					$this->writeLog('success', 'Auth Proxy Function - IP: ' . $this->userIP() . ' has been verified', $usernameHeader);
				} else {
					$this->writeLog('error', 'Auth Proxy Function - IP: ' . $this->userIP() . ' has failed verification', $usernameHeader);
				}
			}
		}
		// Check if Login method was an oAuth login
		if (!$oAuth) {
			$result = $this->getUserByUsernameAndEmail($username, $username);
			$result['password'] = $result['password'] ?? '';
			// Switch AuthType - internal - external - both
			switch ($this->config['authType']) {
				case 'external':
					if (method_exists($this, $function)) {
						$authSuccess = $this->$function($username, $password);
					}
					break;
				/** @noinspection PhpMissingBreakStatementInspection */
				case 'both':
					if (method_exists($this, $function)) {
						$authSuccess = $this->$function($username, $password);
					}
				// no break
				default: // Internal
					if (!$authSuccess) {
						// perform the internal authentication step
						if (password_verify($password, $result['password'])) {
							$authSuccess = true;
						}
					}
			}
			$authSuccess = ($authProxy) ? true : $authSuccess;
		} else {
			// Has oAuth Token!
			switch ($oAuthType) {
				case 'plex':
					if ($this->config['plexoAuth']) {
						$tokenInfo = $this->checkPlexToken($oAuth);
						if ($tokenInfo) {
							$authSuccess = array(
								'username' => $tokenInfo['user']['username'],
								'email' => $tokenInfo['user']['email'],
								'image' => $tokenInfo['user']['thumb'],
								'token' => $tokenInfo['user']['authToken']
							);
							$this->coookie('set', 'oAuth', 'true', $this->config['rememberMeDays']);
							$authSuccess = ((!empty($this->config['plexAdmin']) && strtolower($this->config['plexAdmin']) == strtolower($tokenInfo['user']['username'])) || (!empty($this->config['plexAdmin']) && strtolower($this->config['plexAdmin']) == strtolower($tokenInfo['user']['email'])) || $this->checkPlexUser($tokenInfo['user']['username'])) ? $authSuccess : false;
						}
					} else {
						$this->setAPIResponse('error', 'Plex oAuth is not setup', 422);
						return false;
					}
					break;
				default:
					return ($output) ? 'No oAuthType defined' : 'error';
			}
			$result = ($authSuccess) ? $this->getUserByUsernameAndEmail($authSuccess['username'], $authSuccess['email']) : '';
		}
		if ($authSuccess) {
			// Make sure user exists in database
			$userExists = false;
			$passwordMatches = $oAuth || $authProxy;
			$token = (is_array($authSuccess) && isset($authSuccess['token']) ? $authSuccess['token'] : '');
			if (isset($result['username'])) {
				$userExists = true;
				$username = $result['username'];
				if ($passwordMatches == false) {
					$passwordMatches = password_verify($password, $result['password']);
				}
			}
			if ($userExists) {
				//does org password need to be updated
				if (!$passwordMatches) {
					$this->updateUserPassword($password, $result['id']);
					$this->writeLog('success', 'Login Function - User Password updated from backend', $username);
				}
				if ($token !== '') {
					if ($token !== $result['plex_token']) {
						$this->updateUserPlexToken($token, $result['id']);
						$this->writeLog('success', 'Login Function - User Plex Token updated from backend', $username);
					}
				}
				// 2FA might go here
				if ($result['auth_service'] !== 'internal' && strpos($result['auth_service'], '::') !== false) {
					$tfaProceed = true;
					// Add check for local or not
					if ($this->config['ignoreTFALocal'] !== false) {
						$tfaProceed = ($this->isLocal()) ? false : true;
					}
					if ($tfaProceed) {
						$TFA = explode('::', $result['auth_service']);
						// Is code with login info?
						if ($tfaCode == '') {
							$this->setAPIResponse('warning', '2FA Code Needed', 422);
							return false;
						} else {
							if (!$this->verify2FA($TFA[1], $tfaCode, $TFA[0])) {
								$this->writeLoginLog($username, 'error');
								$this->writeLog('error', 'Login Function - Wrong 2FA', $username);
								$this->setAPIResponse('error', 'Wrong 2FA', 422);
								return false;
							}
						}
					}
				}
				// End 2FA
				// authentication passed - 1) mark active and update token
				$createToken = $this->createToken($result['username'], $result['email'], $days);
				if ($createToken) {
					$this->writeLoginLog($username, 'success');
					$this->writeLog('success', 'Login Function - A User has logged in', $username);
					$ssoUser = ((empty($result['email'])) ? $result['username'] : (strpos($result['email'], 'placeholder') !== false)) ? $result['username'] : $result['email'];
					$this->ssoCheck($ssoUser, $password, $token); //need to work on this
					return ($output) ? array('name' => $this->cookieName, 'token' => (string)$createToken) : true;
				} else {
					$this->setAPIResponse('error', 'Token creation error', 500);
					return false;
				}
			} else {
				// Create User
				return $this->authRegister((is_array($authSuccess) && isset($authSuccess['username']) ? $authSuccess['username'] : $username), $password, (is_array($authSuccess) && isset($authSuccess['email']) ? $authSuccess['email'] : ''), $token);
			}
		} else {
			// authentication failed
			$this->writeLoginLog($username, 'error');
			$this->writeLog('error', 'Login Function - Wrong Password', $username);
			if ($loginAttempts >= $this->config['loginAttempts']) {
				$this->coookieSeconds('set', 'lockout', $this->config['loginLockout'], $this->config['loginLockout']);
				$this->setAPIResponse('error', 'User is locked out', 403);
				return false;
			} else {
				$this->setAPIResponse('error', 'User credentials incorrect', 401);
				return false;
			}
		}
	}
	
	public function logout()
	{
		$this->coookie('delete', $this->cookieName);
		$this->coookie('delete', 'mpt');
		$this->coookie('delete', 'Auth');
		$this->coookie('delete', 'oAuth');
		$this->clearTautulliTokens();
		$this->revokeTokenCurrentUser($this->user['token']);
		$this->user = null;
		return true;
	}
	
	public function recover($array)
	{
		$email = $array['email'] ?? null;
		if (!$email) {
			$this->setAPIResponse('error', 'Email was not supplied', 422);
			return false;
		}
		$newPassword = $this->randString(10);
		$isUser = $this->getUserByEmail($email);
		if ($isUser) {
			$this->updateUserPassword($newPassword, $isUser['id']);
			$this->setAPIResponse('success', 'User password has been reset', 200);
			$this->writeLog('success', 'User Management Function - User: ' . $isUser['username'] . '\'s password was reset', $isUser['username']);
			if ($this->config['PHPMAILER-enabled']) {
				$PhpMailer = new PhpMailer();
				$emailTemplate = array(
					'type' => 'reset',
					'body' => $this->config['PHPMAILER-emailTemplateReset'],
					'subject' => $this->config['PHPMAILER-emailTemplateResetSubject'],
					'user' => $isUser['username'],
					'password' => $newPassword,
					'inviteCode' => null,
				);
				$emailTemplate = $PhpMailer->_phpMailerPluginEmailTemplate($emailTemplate);
				$sendEmail = array(
					'to' => $email,
					'user' => $isUser['username'],
					'subject' => $emailTemplate['subject'],
					'body' => $PhpMailer->_phpMailerPluginBuildEmail($emailTemplate),
				);
				$PhpMailer->_phpMailerPluginSendEmail($sendEmail);
				$this->setAPIResponse('success', 'User password has been reset and email has been sent', 200);
			}
			return true;
		} else {
			$this->setAPIResponse('error', 'User not found', 404);
			return false;
		}
	}
	
	public function register($array)
	{
		$email = $array['email'] ?? null;
		$username = $array['username'] ?? null;
		$password = $array['password'] ?? null;
		$registrationPassword = $array['registrationPassword'] ?? null;
		if (!$email) {
			$this->setAPIResponse('error', 'Email was not supplied', 422);
			return false;
		}
		if (!$username) {
			$this->setAPIResponse('error', 'Username was not supplied', 422);
			return false;
		}
		if (!$password) {
			$this->setAPIResponse('error', 'Password was not supplied', 422);
			return false;
		}
		if (!$registrationPassword) {
			$this->setAPIResponse('error', 'Registration Password was not supplied', 422);
			return false;
		}
		if ($registrationPassword == $this->decrypt($this->config['registrationPassword'])) {
			$this->writeLog('success', 'Registration Function - Registration Password Verified', $username);
			if ($this->createUser($username, $password, $email)) {
				$this->writeLog('success', 'Registration Function - A User has registered', $username);
				if ($this->createToken($username, $email, $this->config['rememberMeDays'])) {
					$this->writeLoginLog($username, 'success');
					$this->writeLog('success', 'Login Function - A User has logged in', $username);
					return true;
				}
			} else {
				return false;
			}
		} else {
			$this->writeLog('warning', 'Registration Function - Wrong Password', $username);
			$this->setAPIResponse('error', 'Registration Password was incorrect', 401);
			return false;
		}
	}
	
	public function authRegister($username, $password, $email, $token = null)
	{
		if ($this->config['authBackend'] !== '') {
			$this->ombiImport($this->config['authBackend']);
		}
		$this->ssoCheck($username, $password, $token);
		if ($token && (!$password || $password == '')) {
			$password = $this->random_ascii_string(10);
		}
		if ($this->createUser($username, $password, $email)) {
			$this->writeLog('success', 'Registration Function - A User has registered', $username);
			if ($this->config['PHPMAILER-enabled'] && $email !== '') {
				$PhpMailer = new PhpMailer();
				$emailTemplate = array(
					'type' => 'registration',
					'body' => $this->config['PHPMAILER-emailTemplateRegisterUser'],
					'subject' => $this->config['PHPMAILER-emailTemplateRegisterUserSubject'],
					'user' => $username,
					'password' => null,
					'inviteCode' => null,
				);
				$emailTemplate = $PhpMailer->_phpMailerPluginEmailTemplate($emailTemplate);
				$sendEmail = array(
					'to' => $email,
					'user' => $username,
					'subject' => $emailTemplate['subject'],
					'body' => $PhpMailer->_phpMailerPluginBuildEmail($emailTemplate),
				);
				$PhpMailer->_phpMailerPluginSendEmail($sendEmail);
			}
			if ($this->createToken($username, $email, $this->gravatar($email), $this->config['rememberMeDays'])) {
				$this->writeLoginLog($username, 'success');
				$this->writeLog('success', 'Login Function - A User has logged in', $username);
				return true;
			} else {
				return false;
			}
		} else {
			$this->writeLog('error', 'Registration Function - An error occurred', $username);
			return false;
		}
	}
	
	public function revokeTokenCurrentUser($token = null)
	{
		if ($token) {
			$response = [
				array(
					'function' => 'query',
					'query' => array(
						'DELETE FROM tokens WHERE token = ?',
						[$token]
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'query',
					'query' => array(
						'DELETE FROM tokens WHERE user_id = ?',
						[$this->user['userID']]
					)
				),
			];
		}
		return $this->processQueries($response);
	}
	
	public function revokeToken($token = null)
	{
		if (!$token) {
			$this->setAPIResponse('error', 'Token was not supplied', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM tokens WHERE token = ?',
					[$token]
				)
			),
		];
		$this->setAPIResponse('success', 'Token revoked', 204);
		return $this->processQueries($response);
	}
	
	public function revokeTokenByIdCurrentUser($id = null)
	{
		if (!$id) {
			$this->setAPIResponse('error', 'Id was not supplied', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM tokens WHERE id = ? AND user_id = ?',
					$id,
					$this->user['userID']
				)
			),
		];
		$this->setAPIResponse('success', 'Token revoked', 204);
		return $this->processQueries($response);
	}
	
	public function updateUserPassword($password, $id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['password' => password_hash($password, PASSWORD_BCRYPT)],
					'WHERE id = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function updateUserPlexToken($token, $id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['plex_token' => $token],
					'WHERE id = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getUserTabsAndCategories($type = null)
	{
		if (!$this->hasDB()) {
			return false;
		}
		$sort = ($this->config['unsortedTabs'] == 'top') ? 'DESC' : 'ASC';
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM tabs WHERE `group_id` >= ? AND `enabled` = 1 ORDER BY `order` ' . $sort,
					$this->user['groupID']
				),
				'key' => 'tabs'
			),
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM categories ORDER BY `order` ASC',
				),
				'key' => 'categories'
			),
		];
		$queries = $this->processQueries($response);
		$all['tabs'] = $queries['tabs'];
		foreach ($queries['tabs'] as $k => $v) {
			$v['access_url'] = (!empty($v['url_local']) && ($v['url_local'] !== null) && ($v['url_local'] !== 'null') && $this->isLocal() && $v['type'] !== 0) ? $v['url_local'] : $v['url'];
		}
		$count = array_map(function ($element) {
			return $element['category_id'];
		}, $queries['tabs']);
		$count = (array_count_values($count));
		foreach ($queries['categories'] as $k => $v) {
			$v['count'] = isset($count[$v['category_id']]) ? $count[$v['category_id']] : 0;
		}
		$all['categories'] = $queries['categories'];
		switch ($type) {
			case 'categories':
				return $all['categories'];
			case 'tabs':
				return $all['tabs'];
			default:
				return $all;
		}
	}
	
	public function refreshList()
	{
		$searchTerm = "Refresh";
		return array_filter($this->config, function ($k) use ($searchTerm) {
			return stripos($k, $searchTerm) !== false;
		}, ARRAY_FILTER_USE_KEY);
	}
	
	public function homepageOrderList()
	{
		$searchTerm = "homepageOrder";
		$order = array_filter($this->config, function ($k) use ($searchTerm) {
			return stripos($k, $searchTerm) !== false;
		}, ARRAY_FILTER_USE_KEY);
		asort($order);
		return $order;
	}
	
	public function tautulliList()
	{
		$searchTerm = "tautulli_token";
		return array_filter($this->config, function ($k) use ($searchTerm) {
			return stripos($k, $searchTerm) !== false;
		}, ARRAY_FILTER_USE_KEY);
	}
	
	public function checkPlexAdminFilled()
	{
		if ($this->config['plexAdmin'] == '') {
			return false;
		} else {
			if ((strpos($this->config['plexAdmin'], '@') !== false)) {
				return 'email';
			} else {
				return 'username';
			}
		}
	}
	
	public function organizrSpecialSettings()
	{
		return array(
			'homepage' => array(
				'refresh' => $this->refreshList(),
				'order' => $this->homepageOrderList(),
				'search' => array(
					'enabled' => $this->qualifyRequest($this->config['mediaSearchAuth']) && $this->config['mediaSearch'] == true && $this->config['plexToken'],
					'type' => $this->config['mediaSearchType'],
				),
				'ombi' => array(
					'enabled' => $this->qualifyRequest($this->config['homepageOmbiAuth']) && $this->qualifyRequest($this->config['homepageOmbiRequestAuth']) && $this->config['homepageOmbiEnabled'] == true && $this->config['ssoOmbi'] && isset($_COOKIE['Auth']),
					'authView' => $this->qualifyRequest($this->config['homepageOmbiAuth']),
					'authRequest' => $this->qualifyRequest($this->config['homepageOmbiRequestAuth']),
					'sso' => ($this->config['ssoOmbi']) ? true : false,
					'cookie' => isset($_COOKIE['Auth']),
					'alias' => ($this->config['ombiAlias']) ? true : false,
					'ombiDefaultFilterAvailable' => $this->config['ombiDefaultFilterAvailable'] ? true : false,
					'ombiDefaultFilterUnavailable' => $this->config['ombiDefaultFilterUnavailable'] ? true : false,
					'ombiDefaultFilterApproved' => $this->config['ombiDefaultFilterApproved'] ? true : false,
					'ombiDefaultFilterUnapproved' => $this->config['ombiDefaultFilterUnapproved'] ? true : false,
					'ombiDefaultFilterDenied' => $this->config['ombiDefaultFilterDenied'] ? true : false
				),
				'options' => array(
					'alternateHomepageHeaders' => $this->config['alternateHomepageHeaders'],
					'healthChecksTags' => $this->config['healthChecksTags'],
					'titles' => array(
						'tautulli' => $this->config['tautulliHeader']
					)
				),
				'media' => array(
					'jellyfin' => $this->config['homepageJellyfinInstead']
				)
			),
			'sso' => array(
				'misc' => array(
					'oAuthLogin' => isset($_COOKIE['oAuth']),
					'rememberMe' => $this->config['rememberMe'],
					'rememberMeDays' => $this->config['rememberMeDays']
				),
				'plex' => array(
					'enabled' => ($this->config['ssoPlex']) ? true : false,
					'cookie' => isset($_COOKIE['mpt']),
					'machineID' => strlen($this->config['plexID']) == 40,
					'token' => $this->config['plexToken'] !== '',
					'plexAdmin' => $this->checkPlexAdminFilled(),
					'strict' => ($this->config['plexStrictFriends']) ? true : false,
					'oAuthEnabled' => ($this->config['plexoAuth']) ? true : false,
					'backend' => $this->config['authBackend'] == 'plex',
				),
				'ombi' => array(
					'enabled' => ($this->config['ssoOmbi']) ? true : false,
					'cookie' => isset($_COOKIE['Auth']),
					'url' => ($this->config['ombiURL'] !== '') ? $this->config['ombiURL'] : false,
					'api' => $this->config['ombiToken'] !== '',
				),
				'tautulli' => array(
					'enabled' => ($this->config['ssoTautulli']) ? true : false,
					'cookie' => !empty($this->tautulliList()),
					'url' => ($this->config['tautulliURL'] !== '') ? $this->config['tautulliURL'] : false,
				),
			),
			'ping' => array(
				'onlineSound' => $this->config['pingOnlineSound'],
				'offlineSound' => $this->config['pingOfflineSound'],
				'statusSounds' => $this->config['statusSounds'],
				'auth' => $this->config['pingAuth'],
				'authMessage' => $this->config['pingAuthMessage'],
				'authMs' => $this->config['pingAuthMs'],
				'ms' => $this->config['pingMs'],
				'adminRefresh' => $this->config['adminPingRefresh'],
				'everyoneRefresh' => $this->config['otherPingRefresh'],
			),
			'notifications' => array(
				'backbone' => $this->config['notificationBackbone'],
				'position' => $this->config['notificationPosition']
			),
			'lockout' => array(
				'enabled' => $this->config['lockoutSystem'],
				'timer' => $this->config['lockoutTimeout'],
				'minGroup' => $this->config['lockoutMinAuth'],
				'maxGroup' => $this->config['lockoutMaxAuth']
			),
			'user' => array(
				'agent' => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : null,
				'oAuthLogin' => isset($_COOKIE['oAuth']),
				'local' => $this->isLocal(),
				'ip' => $this->userIP()
			),
			'login' => array(
				'rememberMe' => $this->config['rememberMe'],
				'rememberMeDays' => $this->config['rememberMeDays'],
				'wanDomain' => $this->config['wanDomain'],
				'localAddress' => $this->config['localAddress'],
				'enableLocalAddressForward' => $this->config['enableLocalAddressForward'],
			),
			'misc' => array(
				'installedPlugins' => $this->qualifyRequest(1) ? $this->config['installedPlugins'] : '',
				'installedThemes' => $this->qualifyRequest(1) ? $this->config['installedThemes'] : '',
				'return' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false,
				'authDebug' => $this->config['authDebug'],
				'minimalLoginScreen' => $this->config['minimalLoginScreen'],
				'unsortedTabs' => $this->config['unsortedTabs'],
				'authType' => $this->config['authType'],
				'authBackend' => $this->config['authBackend'],
				'newMessageSound' => (isset($this->config['CHAT-newMessageSound-include'])) ? $this->config['CHAT-newMessageSound-include'] : '',
				'uuid' => ($this->config['uuid']) ?? null,
				'docker' => $this->qualifyRequest(1) ? $this->docker : '',
				'githubCommit' => $this->qualifyRequest(1) ? $this->commit : '',
				'schema' => $this->qualifyRequest(1) ? $this->getSchema() : '',
				'debugArea' => $this->qualifyRequest($this->config['debugAreaAuth']),
				'debugErrors' => $this->config['debugErrors'],
				'sandbox' => $this->config['sandbox'],
			),
			'menuLink' => array(
				'githubMenuLink' => $this->config['githubMenuLink'],
				'organizrSupportMenuLink' => $this->config['organizrSupportMenuLink'],
				'organizrDocsMenuLink' => $this->config['organizrDocsMenuLink'],
				'organizrSignoutMenuLink' => $this->config['organizrSignoutMenuLink']
			)
		);
	}
	
	public function getLog($log, $reverse = true)
	{
		switch ($log) {
			case 'login':
			case 'loginLog':
			case 'loginlog':
				$file = $this->organizrLoginLog;
				$parent = 'auth';
				break;
			case 'org':
			case 'organizr':
			case 'organizrLog':
			case 'orglog':
				$file = $this->organizrLog;
				$parent = 'log_items';
				break;
			default:
				$this->setAPIResponse('error', 'Log not defined', 404);
				return null;
		}
		if (!file_exists($file)) {
			$this->setAPIResponse('error', 'Log does not exist', 404);
			return null;
		}
		$getLog = str_replace("\r\ndate", "date", file_get_contents($file));
		$gotLog = json_decode($getLog, true);
		return ($reverse) ? array_reverse($gotLog[$parent]) : $gotLog[$parent];
	}
	
	public function purgeLog($log)
	{
		
		switch ($log) {
			case 'login':
			case 'loginLog':
			case 'loginlog':
				$file = $this->organizrLoginLog;
				break;
			case 'org':
			case 'organizr':
			case 'organizrLog':
			case 'orgLog':
			case 'orglog':
				$file = $this->organizrLog;
				break;
			default:
				$this->setAPIResponse('error', 'Log not defined', 404);
				return null;
		}
		if (file_exists($file)) {
			if (unlink($file)) {
				$this->writeLog('success', 'Log Management Function - Log: ' . $log . ' has been purged/deleted', 'SYSTEM');
				$this->setAPIResponse(null, 'Log purged');
				return true;
			} else {
				$this->writeLog('error', 'Log Management Function - Log: ' . $log . ' - Error Occurred', 'SYSTEM');
				$this->setAPIResponse('error', 'Log could not be purged', 500);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Log does not exist', 404);
			return false;
		}
		
	}
	
	public function checkLog($path)
	{
		if (file_exists($path)) {
			if (filesize($path) > 500000) {
				rename($path, $path . '[' . date('Y-m-d') . '].json');
				return false;
			}
			return true;
		} else {
			return false;
		}
	}
	
	public function writeLoginLog($username, $authType)
	{
		$username = htmlspecialchars($username, ENT_QUOTES);
		if ($this->checkLog($this->organizrLoginLog)) {
			$getLog = str_replace("\r\ndate", "date", file_get_contents($this->organizrLoginLog));
			$gotLog = json_decode($getLog, true);
		}
		$logEntryFirst = array('logType' => 'login_log', 'auth' => array(array('date' => date("Y-m-d H:i:s"), 'utc_date' => $this->currentTime, 'username' => $username, 'ip' => $this->userIP(), 'auth_type' => $authType)));
		$logEntry = array('date' => date("Y-m-d H:i:s"), 'utc_date' => $this->currentTime, 'username' => $username, 'ip' => $this->userIP(), 'auth_type' => $authType);
		if (isset($gotLog)) {
			array_push($gotLog["auth"], $logEntry);
			$writeFailLog = str_replace("date", "\r\ndate", json_encode($gotLog));
		} else {
			$writeFailLog = str_replace("date", "\r\ndate", json_encode($logEntryFirst));
		}
		file_put_contents($this->organizrLoginLog, $writeFailLog);
	}
	
	public function writeLog($type = 'error', $message, $username = null)
	{
		$this->timeExecution = $this->timeExecution($this->timeExecution);
		$message = $message . ' [Execution Time: ' . $this->formatSeconds($this->timeExecution) . ']';
		$username = ($username) ? htmlspecialchars($username, ENT_QUOTES) : $this->user['username'];
		if ($this->checkLog($this->organizrLog)) {
			$getLog = str_replace("\r\ndate", "date", file_get_contents($this->organizrLog));
			$gotLog = json_decode($getLog, true);
		}
		$logEntryFirst = array('logType' => 'organizr_log', 'log_items' => array(array('date' => date("Y-m-d H:i:s"), 'utc_date' => $this->currentTime, 'type' => $type, 'username' => $username, 'ip' => $this->userIP(), 'message' => $message)));
		$logEntry = array('date' => date("Y-m-d H:i:s"), 'utc_date' => $this->currentTime, 'type' => $type, 'username' => $username, 'ip' => $this->userIP(), 'message' => $message);
		if (isset($gotLog)) {
			array_push($gotLog["log_items"], $logEntry);
			$writeFailLog = str_replace("date", "\r\ndate", json_encode($gotLog));
		} else {
			$writeFailLog = str_replace("date", "\r\ndate", json_encode($logEntryFirst));
		}
		file_put_contents($this->organizrLog, $writeFailLog);
	}
	
	public function isApprovedRequest($method, $data)
	{
		$requesterToken = isset($this->getallheaders()['Token']) ? $this->getallheaders()['Token'] : (isset($_GET['apikey']) ? $_GET['apikey'] : false);
		$apiKey = ($this->config['organizrAPI']) ?? null;
		if (isset($data['formKey'])) {
			$formKey = $data['formKey'];
		} elseif (isset($this->getallheaders()['Formkey'])) {
			$formKey = $this->getallheaders()['Formkey'];
		} elseif (isset($this->getallheaders()['formkey'])) {
			$formKey = $this->getallheaders()['formkey'];
		} elseif (isset($this->getallheaders()['formKey'])) {
			$formKey = $this->getallheaders()['formKey'];
		} elseif (isset($this->getallheaders()['FormKey'])) {
			$formKey = $this->getallheaders()['FormKey'];
		} else {
			$formKey = false;
		}
		// Check token or API key
		// If API key, return 0 for admin
		if (strlen($requesterToken) == 20 && $requesterToken == $apiKey) {
			//DO API CHECK
			return true;
		} elseif ($method == 'POST') {
			if ($this->checkFormKey($formKey)) {
				return true;
			} else {
				$this->writeLog('error', 'API ERROR: Unable to authenticate Form Key: ' . $formKey, $this->user['username']);
				return false;
			}
		} else {
			return true;
		}
		return false;
	}
	
	public function checkFormKey($formKey = '')
	{
		return password_verify(substr($this->config['organizrHash'], 2, 10), $formKey);
	}
	
	public function buildHomepage()
	{
		$homepageOrder = $this->homepageOrderList();
		$homepageBuilt = '';
		foreach ($homepageOrder as $key => $value) {
			//new way
			if (method_exists($this, $key)) {
				$homepageBuilt .= $this->$key();
			} else {
				$homepageBuilt .= '<div id="' . $key . '"></div>';
			}
			//old way
			//$homepageBuilt .= $this->buildHomepageItem($key);
		}
		return $homepageBuilt;
	}
	
	public function buildHomepageSettings()
	{
		$homepageOrder = $this->homepageOrderList();
		$homepageList = '<h4>Drag Homepage Items to Order Them</h4><div id="homepage-items-sort" class="external-events">';
		$inputList = '<form id="homepage-values" class="row">';
		foreach ($homepageOrder as $key => $val) {
			switch ($key) {
				case 'homepageOrdercustomhtml':
					$class = 'bg-info';
					$image = 'plugins/images/tabs/custom1.png';
					if (!$this->config['homepageCustomHTMLoneEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrdercustomhtmlTwo':
					$class = 'bg-info';
					$image = 'plugins/images/tabs/custom2.png';
					if (!$this->config['homepageCustomHTMLtwoEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrdertransmission':
					$class = 'bg-transmission';
					$image = 'plugins/images/tabs/transmission.png';
					if (!$this->config['homepageTransmissionEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrdernzbget':
					$class = 'bg-nzbget';
					$image = 'plugins/images/tabs/nzbget.png';
					if (!$this->config['homepageNzbgetEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderjdownloader':
					$class = 'bg-sab';
					$image = 'plugins/images/tabs/jdownloader.png';
					if (!$this->config['homepageJdownloaderEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrdersabnzbd':
					$class = 'bg-sab';
					$image = 'plugins/images/tabs/sabnzbd.png';
					if (!$this->config['homepageSabnzbdEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderdeluge':
					$class = 'bg-deluge';
					$image = 'plugins/images/tabs/deluge.png';
					if (!$this->config['homepageDelugeEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderqBittorrent':
					$class = 'bg-qbit';
					$image = 'plugins/images/tabs/qBittorrent.png';
					if (!$this->config['homepageqBittorrentEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderrTorrent':
					$class = 'bg-qbit';
					$image = 'plugins/images/tabs/rTorrent.png';
					if (!$this->config['homepagerTorrentEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderplexnowplaying':
				case 'homepageOrderplexrecent':
				case 'homepageOrderplexplaylist':
					$class = 'bg-plex';
					$image = 'plugins/images/tabs/plex.png';
					if (!$this->config['homepagePlexEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderembynowplaying':
				case 'homepageOrderembyrecent':
					$class = 'bg-emby';
					$image = 'plugins/images/tabs/emby.png';
					if (!$this->config['homepageEmbyEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderjellyfinnowplaying':
				case 'homepageOrderjellyfinrecent':
					$class = 'bg-jellyfin';
					$image = 'plugins/images/tabs/jellyfin.png';
					if (!$this->config['homepageJellyfinEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderombi':
					$class = 'bg-inverse';
					$image = 'plugins/images/tabs/ombi.png';
					if (!$this->config['homepageOmbiEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrdercalendar':
					$class = 'bg-primary';
					$image = 'plugins/images/tabs/calendar.png';
					if (!$this->config['homepageCalendarEnabled'] && !$this->config['homepageSonarrEnabled'] && !$this->config['homepageRadarrEnabled'] && !$this->config['homepageSickrageEnabled'] && !$this->config['homepageCouchpotatoEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderdownloader':
					$class = 'bg-inverse';
					$image = 'plugins/images/tabs/downloader.png';
					if (!$this->config['jdownloaderCombine'] && !$this->config['sabnzbdCombine'] && !$this->config['nzbgetCombine'] && !$this->config['rTorrentCombine'] && !$this->config['delugeCombine'] && !$this->config['transmissionCombine'] && !$this->config['qBittorrentCombine']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderhealthchecks':
					$class = 'bg-healthchecks';
					$image = 'plugins/images/tabs/healthchecks.png';
					if (!$this->config['homepageHealthChecksEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderunifi':
					$class = 'bg-info';
					$image = 'plugins/images/tabs/ubnt.png';
					if (!$this->config['homepageUnifiEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrdertautulli':
					$class = 'bg-info';
					$image = 'plugins/images/tabs/tautulli.png';
					if (!$this->config['homepageTautulliEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderPihole':
					$class = 'bg-info';
					$image = 'plugins/images/tabs/pihole.png';
					if (!$this->config['homepagePiholeEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderMonitorr':
					$class = 'bg-info';
					$image = 'plugins/images/tabs/monitorr.png';
					if (!$this->config['homepageMonitorrEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderWeatherAndAir':
					$class = 'bg-success';
					$image = 'plugins/images/tabs/wind.png';
					if (!$this->config['homepageWeatherAndAirEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderSpeedtest':
					$class = 'bg-success';
					$image = 'plugins/images/tabs/speedtest-icon.png';
					if (!$this->config['homepageSpeedtestEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderNetdata':
					$class = 'bg-success';
					$image = 'plugins/images/tabs/netdata.png';
					if (!$this->config['homepageNetdataEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderOctoprint':
					$class = 'bg-success';
					$image = 'plugins/images/tabs/octoprint.png';
					if (!$this->config['homepageOctoprintEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderSonarrQueue':
					$class = 'bg-sonarr';
					$image = 'plugins/images/tabs/sonarr.png';
					if (!$this->config['homepageSonarrQueueEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderRadarrQueue':
					$class = 'bg-radarr';
					$image = 'plugins/images/tabs/radarr.png';
					if (!$this->config['homepageRadarrQueueEnabled']) {
						$class .= ' faded';
					}
					break;
				case 'homepageOrderJackett':
					$class = 'bg-inverse';
					$image = 'plugins/images/tabs/jackett.png';
					if (!$this->config['homepageJackettEnabled']) {
						$class .= ' faded';
					}
					break;
				default:
					$class = 'blue-bg';
					$image = '';
					break;
			}
			$homepageList .= '
		<div class="col-md-3 col-xs-12 sort-homepage m-t-10 hvr-grow clearfix">
			<div class="homepage-drag fc-event ' . $class . ' lazyload"  data-src="' . $image . '">
				<span class="ordinal-position text-uppercase badge bg-org homepage-number" data-link="' . $key . '" style="float:left;width: 30px;">' . $val . '</span>
				<span class="homepage-text">&nbsp; ' . strtoupper(substr($key, 13)) . '</span>

			</div>
		</div>
		';
			$inputList .= '<input type="hidden" name="' . $key . '">';
		}
		$homepageList .= '</div>';
		$inputList .= '</form>';
		return $homepageList . $inputList;
	}
	
	public function setGroupOptionsVariable()
	{
		$this->groupOptions = $this->groupSelect();
	}
	
	public function getSettingsHomepageItem($item)
	{
		$items = $this->getSettingsHomepage();
		foreach ($items as $k => $v) {
			if ($v['name'] === $item) {
				return $v;
			}
		}
		$this->setAPIResponse('error', 'Homepage item was not found', 404);
		return null;
	}
	
	public function getSettingsHomepage()
	{
		$this->setGroupOptionsVariable();
		return $this->getHomepageSettingsCombined();
	}
	
	public function isTabNameTaken($name, $id = null)
	{
		if ($id) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM tabs WHERE `name` LIKE ? AND `id` != ?',
						$name,
						$id
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM tabs WHERE `name` LIKE ?',
						$name
					)
				),
			];
		}
		return $this->processQueries($response);
	}
	
	public function isCategoryNameTaken($name, $id = null)
	{
		if ($id) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM categories WHERE `category` LIKE ? AND `id` != ?',
						$name,
						$id
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM categories WHERE `category` LIKE ?',
						$name
					)
				),
			];
		}
		return $this->processQueries($response);
	}
	
	public function isGroupNameTaken($name, $id = null)
	{
		if ($id) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM groups WHERE `group` LIKE ? AND `id` != ?',
						$name,
						$id
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM groups WHERE `group` LIKE ?',
						$name
					)
				),
			];
		}
		return $this->processQueries($response);
	}
	
	public function getTableColumns($table)
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'PRAGMA table_info(?)',
					$table
				)
			),
		];
		return $this->processQueries($response);
		
	}
	
	public function getTableColumnsFormatted($table)
	{
		$columns = $this->getTableColumns($table);
		if ($columns) {
			$columnsFormatted = [];
			foreach ($columns as $k => $v) {
				$columnsFormatted[$v['name']] = $v;
			}
			return $columnsFormatted;
		} else {
			return false;
		}
	}
	
	public function getTabById($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM tabs WHERE `id` = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getTabGroupByTabName($tab)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT group_id FROM tabs WHERE name LIKE %~like~',
					$tab
				)
			),
		];
		$query = $this->processQueries($response);
		return $query ? $query['group_id'] : 0;
	}
	
	public function getCategoryById($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM categories WHERE `id` = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getGroupUserCountById($id)
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT count(username) AS count FROM groups INNER JOIN users ON users.group_id = groups.group_id AND groups.id = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getGroupById($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM groups WHERE `id` = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getGroupByGroupId($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM groups WHERE `group_id` = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getDefaultGroup()
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM groups WHERE `default` = 1'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getDefaultGroupId()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `group_id` FROM groups WHERE `default` = 1'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getDefaultCategory()
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM categories WHERE `default` = 1'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getDefaultCategoryId()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `category_id` FROM categories WHERE `default` = 1'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getNextTabOrder()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `order` from tabs ORDER BY `order` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getNextCategoryOrder()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `order` from categories ORDER BY `order` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getNextGroupOrder()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `group_id` from groups WHERE `group_id` != "999" ORDER BY `group_id` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function getNextCategoryId()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `category_id` from categories ORDER BY `category_id` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function clearTabDefault()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE tabs SET `default` = 0'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function clearCategoryDefault()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE categories SET `default` = 0'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function clearGroupDefault()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE groups SET `default` = 0'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function checkKeys($tabInfo, $newData)
	{
		foreach ($newData as $k => $v) {
			if (!array_key_exists($k, $tabInfo)) {
				unset($newData[$k]);
			}
		}
		return $newData;
	}
	
	public function deleteTab($id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM tabs WHERE id = ?',
					$id
				)
			),
		];
		$tabInfo = $this->getTabById($id);
		if ($tabInfo) {
			$this->writeLog('success', 'Tab Delete Function -  Deleted Tab [' . $tabInfo['name'] . ']', $this->user['username']);
			$this->setAPIResponse('success', 'Tab deleted', 204);
			return $this->processQueries($response);
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}
	
	public function addTab($array)
	{
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$array = $this->checkKeys($this->getTableColumnsFormatted('tabs'), $array);
		$array['group_id'] = ($array['group_id']) ?? $this->getDefaultGroupId();
		$array['category_id'] = ($array['category_id']) ?? $this->getDefaultCategoryId();
		$array['enabled'] = ($array['enabled']) ?? 0;
		$array['default'] = ($array['default']) ?? 0;
		$array['type'] = ($array['type']) ?? 1;
		$array['order'] = ($array['order']) ?? $this->getNextTabOrder() + 1;
		if (array_key_exists('name', $array)) {
			if ($this->isTabNameTaken($array['name'])) {
				$this->setAPIResponse('error', 'Tab name: ' . $array['name'] . ' is already taken', 409);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Tab name was not supplied', 422);
			return false;
		}
		if (!array_key_exists('url', $array) && !array_key_exists('url_local', $array)) {
			$this->setAPIResponse('error', 'Tab url or url_local was not supplied', 422);
			return false;
		}
		if (!array_key_exists('image', $array)) {
			$this->setAPIResponse('error', 'Tab image was not supplied', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [tabs]',
					$array
				)
			),
		];
		$this->setAPIResponse(null, 'Tab added');
		$this->writeLog('success', 'Tab Editor Function -  Added Tab for [' . $array['name'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function updateTab($id, $array)
	{
		if (!$id || $id == '') {
			$this->setAPIResponse('error', 'id was not set', 422);
			return null;
		}
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$tabInfo = $this->getTabById($id);
		if ($tabInfo) {
			$array = $this->checkKeys($tabInfo, $array);
		} else {
			$this->setAPIResponse('error', 'No tab info found', 404);
			return false;
		}
		if (array_key_exists('name', $array)) {
			if ($this->isTabNameTaken($array['name'], $id)) {
				$this->setAPIResponse('error', 'Tab name: ' . $array['name'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('default', $array)) {
			if ($array['default']) {
				$this->clearTabDefault();
			}
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE tabs SET',
					$array,
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->setAPIResponse(null, 'Tab info updated');
		$this->writeLog('success', 'Tab Editor Function -  Edited Tab Info for [' . $tabInfo['name'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function updateTabOrder($array)
	{
		if (count($array) >= 1) {
			foreach ($array as $tab) {
				if (count($tab) !== 2) {
					$this->setAPIResponse('error', 'data is malformed', 422);
					break;
				}
				$id = $tab['id'] ?? null;
				$order = $tab['order'] ?? null;
				if ($id && $order) {
					$response = [
						array(
							'function' => 'query',
							'query' => array(
								'UPDATE tabs set `order` = ? WHERE `id` = ?',
								$order,
								$id
							)
						),
					];
					$this->processQueries($response);
					$this->setAPIResponse(null, 'Tab Order updated');
				} else {
					$this->setAPIResponse('error', 'data is malformed', 422);
				}
			}
		} else {
			$this->setAPIResponse('error', 'data is empty or not in array', 422);
			return false;
		}
	}
	
	public function addCategory($array)
	{
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$array = $this->checkKeys($this->getTableColumnsFormatted('categories'), $array);
		$array['default'] = ($array['default']) ?? 0;
		$array['order'] = ($array['order']) ?? $this->getNextCategoryOrder() + 1;
		$array['category_id'] = ($array['category_id']) ?? $this->getNextCategoryId() + 1;
		if (array_key_exists('category', $array)) {
			if ($this->isCategoryNameTaken($array['category'])) {
				$this->setAPIResponse('error', 'Category name: ' . $array['category'] . ' is already taken', 409);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Category name was not supplied', 422);
			return false;
		}
		if (!array_key_exists('image', $array)) {
			$this->setAPIResponse('error', 'Category image was not supplied', 422);
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [categories]',
					$array
				)
			),
		];
		$this->setAPIResponse(null, 'Category added');
		$this->writeLog('success', 'Category Editor Function -  Added Category for [' . $array['category'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function updateCategory($id, $array)
	{
		if (!$id || $id == '') {
			$this->setAPIResponse('error', 'id was not set', 422);
			return null;
		}
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$categoryInfo = $this->getCategoryById($id);
		if ($categoryInfo) {
			$array = $this->checkKeys($categoryInfo, $array);
		} else {
			$this->setAPIResponse('error', 'No category info found', 404);
			return false;
		}
		if (array_key_exists('category', $array)) {
			if ($this->isCategoryNameTaken($array['category'], $id)) {
				$this->setAPIResponse('error', 'Category name: ' . $array['category'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('default', $array)) {
			if ($array['default']) {
				$this->clearCategoryDefault();
			}
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE categories SET',
					$array,
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->setAPIResponse(null, 'Category info updated');
		$this->writeLog('success', 'Category Editor Function -  Edited Category Info for [' . $categoryInfo['category'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function updateCategoryOrder($array)
	{
		if (count($array) >= 1) {
			foreach ($array as $category) {
				if (count($category) !== 2) {
					$this->setAPIResponse('error', 'data is malformed', 422);
					break;
				}
				$id = $category['id'] ?? null;
				$order = $category['order'] ?? null;
				if ($id && $order) {
					$response = [
						array(
							'function' => 'query',
							'query' => array(
								'UPDATE categories set `order` = ? WHERE `id` = ?',
								$order,
								$id
							)
						),
					];
					$this->processQueries($response);
					$this->setAPIResponse(null, 'Category Order updated');
				} else {
					$this->setAPIResponse('error', 'data is malformed', 422);
				}
			}
		} else {
			$this->setAPIResponse('error', 'data is empty or not in array', 422);
			return false;
		}
	}
	
	public function deleteCategory($id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM categories WHERE id = ?',
					$id
				)
			),
		];
		$categoryInfo = $this->getCategoryById($id);
		if ($categoryInfo) {
			$this->writeLog('success', 'Category Delete Function -  Deleted Category [' . $categoryInfo['category'] . ']', $this->user['username']);
			$this->setAPIResponse('success', 'Category deleted', 204);
			return $this->processQueries($response);
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}
	
	public function marketplaceFileListFormat($files, $folder, $type)
	{
		foreach ($files as $k => $v) {
			$splitFiles = explode('|', $v);
			$prePath = (strlen($k) !== 1) ? $k . '/' : $k;
			foreach ($splitFiles as $file) {
				$filesList[] = array(
					'fileName' => $file,
					'path' => $prePath,
					'githubPath' => 'https://raw.githubusercontent.com/causefx/Organizr/v2-' . $type . '/' . $folder . $prePath . $file
				);
			}
		}
		return $filesList;
		
	}
	
	public function removeTheme($theme)
	{
		$theme = $this->reverseCleanClassName($theme);
		$array = $this->getThemesGithub();
		$arrayLower = array_change_key_case($array);
		if (!$array) {
			$this->setAPIResponse('error', 'Could not access theme marketplace', 409);
			return false;
		}
		if (!$arrayLower[$theme]) {
			$this->setAPIResponse('error', 'Theme does not exist in marketplace', 404);
			return false;
		} else {
			$key = array_search($theme, array_keys($arrayLower));
			$theme = array_keys($array)[$key];
		}
		$array = $array[$theme];
		$downloadList = $this->marketplaceFileListFormat($array['files'], $array['github_folder'], 'themes');
		if (!$downloadList) {
			$this->setAPIResponse('error', 'Could not get download list for theme', 409);
			return false;
		}
		$name = $theme;
		$version = $array['version'];
		$installedThemesNew = '';
		foreach ($downloadList as $k => $v) {
			$file = array(
				'from' => $v['githubPath'],
				'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'] . $v['fileName']),
				'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'])
			);
			if (!$this->rrmdir($file['to'])) {
				$this->writeLog('error', 'Theme Function -  Remove File Failed  for: ' . $v['githubPath'], $this->user['username']);
				return false;
			}
		}
		if ($this->config['installedThemes'] !== '') {
			$installedThemes = explode('|', $this->config['installedThemes']);
			foreach ($installedThemes as $k => $v) {
				$themes = explode(':', $v);
				$installedThemesList[$themes[0]] = $themes[1];
			}
			if (isset($installedThemesList[$name])) {
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
		}
		$this->updateConfig(array('installedThemes' => $installedThemesNew));
		$this->setAPIResponse('success', 'Theme removed', 200, $installedThemesNew);
		return true;
	}
	
	public function installTheme($theme)
	{
		$theme = $this->reverseCleanClassName($theme);
		$array = $this->getThemesGithub();
		$arrayLower = array_change_key_case($array);
		if (!$array) {
			$this->setAPIResponse('error', 'Could not access theme marketplace', 409);
			return false;
		}
		if (!$arrayLower[$theme]) {
			$this->setAPIResponse('error', 'Theme does not exist in marketplace', 404);
			return false;
		} else {
			$key = array_search($theme, array_keys($arrayLower));
			$theme = array_keys($array)[$key];
		}
		$array = $array[$theme];
		$downloadList = $this->marketplaceFileListFormat($array['files'], $array['github_folder'], 'themes');
		if (!$downloadList) {
			$this->setAPIResponse('error', 'Could not get download list for theme', 409);
			return false;
		}
		$name = $theme;
		$version = $array['version'];
		foreach ($downloadList as $k => $v) {
			$file = array(
				'from' => $v['githubPath'],
				'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'] . $v['fileName']),
				'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'])
			);
			if (!$this->downloadFileToPath($file['from'], $file['to'], $file['path'])) {
				$this->writeLog('error', 'Theme Function -  Downloaded File Failed  for: ' . $v['githubPath'], $this->user['username']);
				$this->setAPIResponse('error', 'Theme download failed', 500);
				return false;
			}
		}
		if ($this->config['installedThemes'] !== '') {
			$installedThemes = explode('|', $this->config['installedThemes']);
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
				$installedThemesNew = $this->config['installedThemes'] . '|' . $name . ':' . $version;
			}
		} else {
			$installedThemesNew = $name . ':' . $version;
		}
		$this->updateConfig(array('installedThemes' => $installedThemesNew));
		$this->setAPIResponse('success', 'Theme installed', 200, $installedThemesNew);
		return true;
	}
	
	public function removePlugin($plugin)
	{
		$plugin = $this->reverseCleanClassName($plugin);
		$array = $this->getPluginsGithub();
		$arrayLower = array_change_key_case($array);
		if (!$array) {
			$this->setAPIResponse('error', 'Could not access plugin marketplace', 409);
			return false;
		}
		if (!$arrayLower[$plugin]) {
			$this->setAPIResponse('error', 'Plugin does not exist in marketplace', 404);
			return false;
		} else {
			$key = array_search($plugin, array_keys($arrayLower));
			$plugin = array_keys($array)[$key];
		}
		$array = $array[$plugin];
		$downloadList = $this->marketplaceFileListFormat($array['files'], $array['github_folder'], 'plugins');
		if (!$downloadList) {
			$this->setAPIResponse('error', 'Could not get download list for plugin', 409);
			return false;
		}
		$name = $plugin;
		$version = $array['version'];
		$installedPluginsNew = '';
		foreach ($downloadList as $k => $v) {
			$file = array(
				'from' => $v['githubPath'],
				'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'] . $v['fileName']),
				'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'])
			);
			if (!$this->rrmdir($file['to'])) {
				$this->writeLog('error', 'Plugin Function -  Remove File Failed  for: ' . $v['githubPath'], $this->user['username']);
				return false;
			}
		}
		if ($this->config['installedPlugins'] !== '') {
			$installedPlugins = explode('|', $this->config['installedPlugins']);
			foreach ($installedPlugins as $k => $v) {
				$plugins = explode(':', $v);
				$installedPluginsList[$plugins[0]] = $plugins[1];
			}
			if (isset($installedPluginsList[$name])) {
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
		}
		$this->updateConfig(array('installedPlugins' => $installedPluginsNew));
		$this->setAPIResponse('success', 'Plugin removed', 200, $installedPluginsNew);
		return true;
	}
	
	public function installPlugin($plugin)
	{
		$plugin = $this->reverseCleanClassName($plugin);
		$array = $this->getPluginsGithub();
		$arrayLower = array_change_key_case($array);
		if (!$array) {
			$this->setAPIResponse('error', 'Could not access plugin marketplace', 409);
			return false;
		}
		if (!$arrayLower[$plugin]) {
			$this->setAPIResponse('error', 'Plugin does not exist in marketplace', 404);
			return false;
		} else {
			$key = array_search($plugin, array_keys($arrayLower));
			$plugin = array_keys($array)[$key];
		}
		$array = $array[$plugin];
		$downloadList = $this->marketplaceFileListFormat($array['files'], $array['github_folder'], 'plugins');
		if (!$downloadList) {
			$this->setAPIResponse('error', 'Could not get download list for plugin', 409);
			return false;
		}
		$name = $plugin;
		$version = $array['version'];
		foreach ($downloadList as $k => $v) {
			$file = array(
				'from' => $v['githubPath'],
				'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'] . $v['fileName']),
				'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'])
			);
			if (!$this->downloadFileToPath($file['from'], $file['to'], $file['path'])) {
				$this->writeLog('error', 'Plugin Function -  Downloaded File Failed  for: ' . $v['githubPath'], $this->user['username']);
				$this->setAPIResponse('error', 'Plugin download failed', 500);
				return false;
			}
		}
		if ($this->config['installedPlugins'] !== '') {
			$installedPlugins = explode('|', $this->config['installedPlugins']);
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
				$installedPluginsNew = $this->config['installedPlugins'] . '|' . $name . ':' . $version;
			}
		} else {
			$installedPluginsNew = $name . ':' . $version;
		}
		$this->updateConfig(array('installedPlugins' => $installedPluginsNew));
		$this->setAPIResponse('success', 'Plugin installed', 200, $installedPluginsNew);
		return true;
	}
	
	public function getThemesGithub()
	{
		$url = 'https://raw.githubusercontent.com/causefx/Organizr/v2-themes/themes.json';
		$options = (localURL($url)) ? array('verify' => false) : array();
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			return json_decode($response->body, true);
		}
		return false;
	}
	
	public function getPluginsGithub()
	{
		$url = 'https://raw.githubusercontent.com/causefx/Organizr/v2-plugins/plugins.json';
		$options = (localURL($url)) ? array('verify' => false) : array();
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			return json_decode($response->body, true);
		}
		return false;
	}
	
	public function getOpenCollectiveBackers()
	{
		$url = 'https://opencollective.com/organizr/members/users.json?limit=100&offset=0';
		$options = (localURL($url)) ? array('verify' => false) : array();
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			$api = json_decode($response->body, true);
			$this->setAPIResponse('success', '', 200, $api);
			return $api;
		}
		$this->setAPIResponse('error', 'Error connecting to Open Collective', 409);
		return false;
	}
	
	public function guestHash($start, $end)
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		$ip = md5($ip);
		return substr($ip, $start, $end);
	}
	
	public function rrmdir($dir)
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		if (is_dir($dir)) {
			$files = scandir($dir);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					$this->rrmdir("$dir/$file");
				}
			}
			rmdir($dir);
		} elseif (file_exists($dir)) {
			unlink($dir);
		}
		return true;
	}
	
	public function rcopy($src, $dst)
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		$src = $this->cleanPath($src);
		$dst = $this->cleanPath($dst);
		if (is_dir($src)) {
			if (!file_exists($dst)) : mkdir($dst);
			endif;
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					$this->rcopy("$src/$file", "$dst/$file");
				}
			}
		} elseif (file_exists($src)) {
			copy($src, $dst);
		}
		return true;
	}
	
	public function unzipFile($zipFile)
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		$zip = new ZipArchive;
		$extractPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "upgrade/";
		if ($zip->open($extractPath . $zipFile) != "true") {
			$this->writeLog("error", "organizr could not unzip upgrade.zip");
		} else {
			$this->writeLog("success", "organizr unzipped upgrade.zip");
		}
		/* Extract Zip File */
		$zip->extractTo($extractPath);
		$zip->close();
		return true;
	}
	
	public function downloadFile($url, $path)
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		$folderPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "upgrade" . DIRECTORY_SEPARATOR;
		if (!file_exists($folderPath)) {
			if (@!mkdir($folderPath)) {
				$this->writeLog('error', 'Update Function -  Folder Creation failed', $this->user['username']);
				return false;
			}
		}
		$newfname = $folderPath . $path;
		$context = stream_context_create(
			array(
				'ssl' => array(
					'verify_peer' => true,
					'cafile' => $this->getCert()
				)
			)
		);
		$file = fopen($url, 'rb', false, $context);
		if ($file) {
			$newf = fopen($newfname, 'wb');
			if ($newf) {
				while (!feof($file)) {
					fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
				}
			}
		} else {
			$this->writeLog("error", "organizr could not download $url");
			return false;
		}
		if ($file) {
			fclose($file);
			$this->writeLog("success", "organizr finished downloading the github zip file");
		} else {
			$this->writeLog("error", "organizr could not download the github zip file");
			return false;
		}
		if ($newf) {
			fclose($newf);
			$this->writeLog("success", "organizr created upgrade zip file from github zip file");
		} else {
			$this->writeLog("error", "organizr could not create upgrade zip file from github zip file");
			return false;
		}
		return true;
	}
	
	public function downloadFileToPath($from, $to, $path)
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		if (@!mkdir($path, 0777, true)) {
			$this->writeLog("error", "organizr could not create folder or folder already exists", 'SYSTEM');
		}
		$file = fopen($from, 'rb');
		if ($file) {
			$newf = fopen($to, 'wb');
			if ($newf) {
				while (!feof($file)) {
					fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
				}
			}
		} else {
			$this->writeLog("error", "organizr could not download file", 'SYSTEM');
		}
		if ($file) {
			fclose($file);
			$this->writeLog("success", "organizr finished downloading the file", 'SYSTEM');
		} else {
			$this->writeLog("error", "organizr could not download the file", 'SYSTEM');
		}
		if ($newf) {
			fclose($newf);
			$this->writeLog("success", "organizr saved/moved the file", 'SYSTEM');
		} else {
			$this->writeLog("error", "organizr could not saved/moved the file", 'SYSTEM');
		}
		return true;
	}
	
	public function getAllUsers($includeGroups = false)
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM users'
				),
				'key' => 'users'
			),
		];
		$groups = array(
			'function' => 'fetchAll',
			'query' => array(
				'SELECT * FROM groups ORDER BY group_id ASC'
			),
			'key' => 'groups'
		);
		$addGroups = (isset($_GET['includeGroups']) || $includeGroups) ?? false;
		if ($addGroups) {
			array_push($response, $groups);
		}
		return $this->processQueries($response);
	}
	
	public function getAllGroups()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM groups ORDER BY group_id ASC'
				),
				'key' => 'groups'
			),
		];
		$users = array(
			'function' => 'fetchAll',
			'query' => array(
				'SELECT * FROM users'
			),
			'key' => 'users'
		);
		$addUsers = (isset($_GET['includeUsers'])) ?? false;
		if ($addUsers) {
			array_push($response, $users);
		}
		return $this->processQueries($response);
	}
	
	public function importUsers($array)
	{
		$imported = 0;
		foreach ($array as $user) {
			$password = $this->random_ascii_string(30);
			if ($user['username'] !== '' && $user['email'] !== '' && $password !== '') {
				$newUser = $this->createUser($user['username'], $password, $user['email']);
				if (!$newUser) {
					$this->writeLog('error', 'Import Function - Error', $user['username']);
				} else {
					$imported++;
				}
			}
		}
		$this->setAPIResponse('success', 'Imported ' . $imported . ' users', 200);
		return true;
	}
	
	public function importUsersType($type)
	{
		if ($type !== '') {
			switch ($type) {
				case 'plex':
					return $this->importUsers($this->allPlexUsers(true));
				case 'jellyfin':
					return $this->importUsers($this->allJellyfinUsers(true));
				case 'emby':
					return $this->importUsers($this->allEmbyUsers(true));
				default:
					return false;
			}
		}
		return false;
	}
	
	public function allPlexUsers($newOnly = false, $friendsOnly = false)
	{
		try {
			if (!empty($this->config['plexToken'])) {
				$url = 'https://plex.tv/api/users';
				$headers = array(
					'X-Plex-Token' => $this->config['plexToken'],
				);
				$response = Requests::get($url, $headers);
				if ($response->success) {
					libxml_use_internal_errors(true);
					$userXML = simplexml_load_string($response->body);
					if (is_array($userXML) || is_object($userXML)) {
						$results = array();
						foreach ($userXML as $child) {
							if (((string)$child['restricted'] == '0')) {
								if ($newOnly) {
									$taken = $this->usernameTaken((string)$child['username'], (string)$child['email']);
									if (!$taken) {
										$results[] = array(
											'username' => (string)$child['username'],
											'email' => (string)$child['email'],
											'id' => (string)$child['id'],
										);
									}
								} elseif ($friendsOnly) {
									$machineMatches = false;
									foreach ($child->Server as $server) {
										if ((string)$server['machineIdentifier'] == $this->config['plexID']) {
											$machineMatches = true;
										}
									}
									if ($machineMatches) {
										$results[] = array(
											'username' => (string)$child['username'],
											'email' => (string)$child['email'],
											'id' => (string)$child['id'],
										);
									}
								} else {
									$results[] = array(
										'username' => (string)$child['username'],
										'email' => (string)$child['email'],
										'id' => (string)$child['id'],
									);
								}
								
							}
						}
						return $results;
					}
				}
			}
			return false;
		} catch (Requests_Exception $e) {
			$this->writeLog('success', 'Plex Import User Function - Error: ' . $e->getMessage(), 'SYSTEM');
		}
		return false;
	}
	
	public function allJellyfinUsers($newOnly = false)
	{
		try {
			if (!empty($this->config['jellyfinURL']) && !empty($this->config['jellyfinToken'])) {
				$url = $this->qualifyURL($this->config['jellyfinURL']) . '/Users?api_key=' . $this->config['jellyfinToken'];
				$headers = array();
				$response = Requests::get($url, $headers);
				if ($response->success) {
					$users = json_decode($response->body, true);
					if (is_array($users) || is_object($users)) {
						$results = array();
						foreach ($users as $child) {
							// Jellyfin doesn't list emails for some reason
							$email = $this->random_ascii_string(10) . '@placeholder.eml';
							if ($newOnly) {
								$taken = $this->usernameTaken((string)$child['Name'], $email);
								if (!$taken) {
									$results[] = array(
										'username' => (string)$child['Name'],
										'email' => $email
									);
								}
							} else {
								$results[] = array(
									'username' => (string)$child['Name'],
									'email' => $email,
								);
							}
						}
						return $results;
					}
				}
			}
			return false;
		} catch (Requests_Exception $e) {
			$this->writeLog('success', 'Jellyfin Import User Function - Error: ' . $e->getMessage(), 'SYSTEM');
		}
		return false;
	}
	
	public function allEmbyUsers($newOnly = false)
	{
		try {
			if (!empty($this->config['embyURL']) && !empty($this->config['embyToken'])) {
				$url = $this->qualifyURL($this->config['embyURL']) . '/Users?api_key=' . $this->config['embyToken'];
				$headers = array();
				$response = Requests::get($url, $headers);
				if ($response->success) {
					$users = json_decode($response->body, true);
					if (is_array($users) || is_object($users)) {
						$results = array();
						foreach ($users as $child) {
							// Emby doesn't list emails for some reason
							$email = $this->random_ascii_string(10) . '@placeholder.eml';
							if ($newOnly) {
								$taken = $this->usernameTaken((string)$child['Name'], $email);
								if (!$taken) {
									$results[] = array(
										'username' => (string)$child['Name'],
										'email' => $email
									);
								}
							} else {
								$results[] = array(
									'username' => (string)$child['Name'],
									'email' => $email,
								);
							}
						}
						return $results;
					}
				}
			}
			return false;
		} catch (Requests_Exception $e) {
			$this->writeLog('success', 'Emby Import User Function - Error: ' . $e->getMessage(), 'SYSTEM');
		}
		return false;
	}
	
	public function updateUser($id, $array)
	{
		if (!$id) {
			$this->setAPIResponse('error', 'Id was not supplied', 422);
			return false;
		}
		if ($id !== $this->user['userID']) {
			if (!$this->qualifyRequest('1', true)) {
				return false;
			}
		}
		$user = $this->getUserById($id);
		if ($user) {
			$array = $this->checkKeys($user, $array);
		} else {
			$this->setAPIResponse('error', 'User was not found', 404);
			return false;
		}
		if ($user['group_id'] == 0 && $this->user['groupID'] !== 0) {
			$this->setAPIResponse('error', 'Cannot update admin unless you are admin', 401);
			return false;
		}
		if (array_key_exists('username', $array)) {
			if ($array['username'] == '') {
				$this->setAPIResponse('error', 'Username was set but empty', 409);
				return false;
			}
			if ($this->usernameTaken($array['username'], $array['username'], $id)) {
				$this->setAPIResponse('error', 'Username: ' . $array['username'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('email', $array)) {
			if ($array['email'] == '') {
				$this->setAPIResponse('error', 'Email was set but empty', 409);
				return false;
			}
			if ($this->usernameTaken($array['email'], $array['email'], $id)) {
				$this->setAPIResponse('error', 'Email: ' . $array['email'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('group_id', $array)) {
			if ($array['group_id'] == '') {
				$this->setAPIResponse('error', 'group_id was set but empty', 409);
				return false;
			}
			if (!$this->qualifyRequest('1', false)) {
				$this->setAPIResponse('error', 'Cannot change your own group_id', 401);
				return false;
			}
			if (($id == $this->user['userID']) && $this->user['groupID'] == 0) {
				$array['group_id'] = 0;
			}
			if (($id == $this->user['userID']) && ($array['group_id'] == 0 && $this->user['groupID'] !== 0)) {
				$this->setAPIResponse('error', 'Only admins can make others admins', 401);
				return false;
			}
			$array['group'] = $this->getGroupByGroupId($array['group_id'])['group'];
			if (!$array['group']) {
				$this->setAPIResponse('error', 'group_id does not exist', 404);
				return false;
			}
		}
		if (array_key_exists('locked', $array)) {
			//$this->setAPIResponse('error', 'Cannot use endpoint to unlock or lock user - please use /users/{id}/lock', 409);
			//return false;
		}
		if (array_key_exists('password', $array)) {
			if ($array['password'] == '') {
				$this->setAPIResponse('error', 'Password was set but empty', 409);
				return false;
			}
			$array['password'] = password_hash($array['password'], PASSWORD_BCRYPT);
		}
		if (array_key_exists('register_date', $array)) {
			$this->setAPIResponse('error', 'Cannot update register date', 409);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					$array,
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->setAPIResponse(null, 'User info updated');
		$this->writeLog('success', 'User Editor Function -  Updated User Info for [' . $user['username'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function deleteUser($id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM users WHERE id = ?',
					$id
				)
			),
		];
		$userInfo = $this->getUserById($id);
		if ($id == $this->user['userID']) {
			$this->setAPIResponse('error', 'Cannot delete your own user', 409);
			return false;
		}
		if ($userInfo) {
			$this->writeLog('success', 'User Delete Function -  Deleted User [' . $userInfo['username'] . ']', $this->user['username']);
			$this->setAPIResponse('success', 'User deleted', 204);
			return $this->processQueries($response);
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}
	
	public function addUser($array)
	{
		$username = $array['username'] ?? null;
		$password = $array['password'] ?? null;
		$email = $array['email'] ?? null;
		if (!$username) {
			$this->setAPIResponse('error', 'Username was not supplied', 409);
			return false;
		}
		if (!$password) {
			$this->setAPIResponse('error', 'Password was not supplied', 409);
			return false;
		}
		if ($this->createUser($username, $password, $email)) {
			$this->writeLog('success', 'Create User Function - Account created for [' . $username . ']', $this->user['username']);
			return true;
		} else {
			$this->writeLog('error', 'Create User Function - An error occurred', $this->user['username']);
			return false;
		}
	}
	
	public function createUser($username, $password, $email = null)
	{
		$username = $username ?? null;
		$password = $password ?? null;
		$email = ($email) ? $email : $this->random_ascii_string(10) . '@placeholder.eml';
		if (!$username) {
			$this->setAPIResponse('error', 'Username was set but empty', 409);
			return false;
		}
		if (!$password) {
			$this->setAPIResponse('error', 'Password was set but empty', 409);
			return false;
		}
		if ($this->usernameTaken($username, $email)) {
			$this->setAPIResponse('error', 'Username: ' . $username . ' or Email: ' . $email . ' is already taken', 409);
			return false;
		}
		$defaults = $this->getDefaultGroup();
		$userInfo = [
			'username' => $username,
			'password' => password_hash($password, PASSWORD_BCRYPT),
			'email' => $email,
			'group' => $defaults['group'],
			'group_id' => $defaults['group_id'],
			'image' => $this->gravatar($email),
			'register_date' => $this->currentTime,
		];
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [users]',
					$userInfo
				)
			),
		];
		$this->setAPIResponse('success', 'User created', 200);
		return $this->processQueries($response);
	}
	
	public function updateGroup($id, $array)
	{
		if (!$id || $id == '') {
			$this->setAPIResponse('error', 'id was not set', 422);
			return null;
		}
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$groupInfo = $this->getGroupById($id);
		if ($groupInfo) {
			$array = $this->checkKeys($groupInfo, $array);
		} else {
			$this->setAPIResponse('error', 'No category info found', 404);
			return false;
		}
		if (array_key_exists('group_id', $array)) {
			$this->setAPIResponse('error', 'Cannot change group_id', 409);
			return false;
			
		}
		if (array_key_exists('group', $array)) {
			if ($array['group'] == '') {
				$this->setAPIResponse('error', 'Group was set but empty', 409);
				return false;
			}
			if ($this->isGroupNameTaken($array['group'], $id)) {
				$this->setAPIResponse('error', 'Group name: ' . $array['group'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('image', $array)) {
			if ($array['image'] == '') {
				$this->setAPIResponse('error', 'Image was set but empty', 409);
				return false;
			}
		}
		if (array_key_exists('default', $array)) {
			if ($groupInfo['group_id'] == 0 || $groupInfo['group_id'] == 999) {
				$this->setAPIResponse('error', 'Setting ' . $groupInfo['group'] . ' as default group is not allowed', 409);
				return false;
			}
			if ($array['default']) {
				$this->clearGroupDefault();
				$array['default'] = 1;
			}
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE groups SET',
					$array,
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->setAPIResponse(null, 'Group info updated');
		$this->writeLog('success', 'Group Editor Function -  Edited Group Info for [' . $groupInfo['group'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function deleteGroup($id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM groups WHERE id = ?',
					$id
				)
			),
		];
		$groupInfo = $this->getGroupById($id);
		if ($groupInfo['group_id'] == 0 || $groupInfo['group_id'] == 999) {
			$this->setAPIResponse('error', 'Cannot delete group: ' . $groupInfo['group'] . ' as it is not allowed', 409);
			return false;
		}
		if ($this->getGroupUserCountById($id) >= 1) {
			$this->setAPIResponse('error', 'Cannot delete group as group still has users assigned to it', 409);
			return false;
		}
		if ($groupInfo) {
			$this->writeLog('success', 'Group Delete Function -  Deleted Group [' . $groupInfo['group'] . ']', $this->user['username']);
			$this->setAPIResponse('success', 'Group deleted', 204);
			return $this->processQueries($response);
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}
	
	public function addGroup($array)
	{
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$array = $this->checkKeys($this->getTableColumnsFormatted('groups'), $array);
		$array['default'] = ($array['default']) ?? 0;
		$array['group_id'] = $this->getNextGroupOrder() + 1;
		if (array_key_exists('group', $array)) {
			if ($this->isGroupNameTaken($array['group'])) {
				$this->setAPIResponse('error', 'Group name: ' . $array['group'] . ' is already taken', 409);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Group name was not supplied', 422);
			return false;
		}
		if (array_key_exists('image', $array)) {
			if ($array['image'] == '') {
				$this->setAPIResponse('error', 'Group image cannot be empty', 422);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Group image was not supplied', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [groups]',
					$array
				)
			),
		];
		$this->setAPIResponse(null, 'Tab added');
		$this->writeLog('success', 'Tab Editor Function -  Added Tab for [' . $array['name'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}
	
	public function userList($type = null)
	{
		switch ($type) {
			case 'plex':
				if (!empty($this->config['plexToken']) && !empty($this->config['plexID'])) {
					$url = 'https://plex.tv/api/servers/' . $this->config['plexID'] . '/shared_servers';
					try {
						$headers = array(
							"Accept" => "application/json",
							"X-Plex-Token" => $this->config['plexToken']
						);
						$response = Requests::get($url, $headers, array());
						libxml_use_internal_errors(true);
						if ($response->success) {
							$libraryList = array();
							$plex = simplexml_load_string($response->body);
							foreach ($plex->SharedServer as $child) {
								if (!empty($child['username'])) {
									$username = (string)strtolower($child['username']);
									$email = (string)strtolower($child['email']);
									$libraryList['users'][$username] = (string)$child['id'];
									$libraryList['emails'][$email] = (string)$child['id'];
									$libraryList['both'][$username] = $email;
								}
							}
							$libraryList = array_change_key_case($libraryList, CASE_LOWER);
							return $libraryList;
						}
					} catch (Requests_Exception $e) {
						$this->writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
					}
				}
				break;
			default:
				# code...
				break;
		}
		return false;
	}
	
	
	public function encrypt($password, $key = null)
	{
		$key = ($key) ? $key : ((isset($this->config['organizrHash'])) ? $this->config['organizrHash'] : null);
		return openssl_encrypt($password, 'AES-256-CBC', $key, 0, $this->fillString($key, 16));
	}
	
	public function decrypt($password, $key = null)
	{
		if (empty($password)) {
			return '';
		}
		$key = ($key) ? $key : ((isset($this->config['organizrHash'])) ? $this->config['organizrHash'] : null);
		return openssl_decrypt($password, 'AES-256-CBC', $key, 0, $this->fillString($key, 16));
	}
	
	public function getCert()
	{
		$url = 'http://curl.haxx.se/ca/cacert.pem';
		$file = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert.pem';
		$file2 = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert-initial.pem';
		$useCert = (file_exists($file)) ? $file : $file2;
		if ($this->config['selfSignedCert'] !== '') {
			if (file_exists($this->config['selfSignedCert'])) {
				return $this->config['selfSignedCert'];
			}
		}
		$context = stream_context_create(
			array(
				'ssl' => array(
					'verify_peer' => true,
					'cafile' => $useCert
				)
			)
		);
		if (!file_exists($file)) {
			file_put_contents($file, fopen($url, 'r', false, $context));
		} elseif (file_exists($file) && time() - 2592000 > filemtime($file)) {
			file_put_contents($file, fopen($url, 'r', false, $context));
		}
		return $file;
	}
	
	public function plexJoinAPI($array)
	{
		$username = ($array['username']) ?? null;
		$email = ($array['email']) ?? null;
		$password = ($array['password']) ?? null;
		if (!$username) {
			$this->setAPIResponse('error', 'Username not supplied', 409);
			return false;
		}
		if (!$email) {
			$this->setAPIResponse('error', 'Email not supplied', 409);
			return false;
		}
		if (!$password) {
			$this->setAPIResponse('error', 'Password not supplied', 409);
			return false;
		}
		return $this->plexJoin($username, $email, $password);
	}
	
	public function plexJoin($username, $email, $password)
	{
		
		try {
			$url = 'https://plex.tv/api/v2/users';
			$headers = array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
				'X-Plex-Product' => 'Organizr',
				'X-Plex-Version' => '2.0',
				'X-Plex-Client-Identifier' => $this->config['uuid'],
			);
			$data = array(
				'email' => $email,
				'username' => $username,
				'password' => $password,
			);
			$response = Requests::post($url, $headers, $data, array());
			$json = json_decode($response->body, true);
			$errors = !empty($json['errors']);
			$success = !empty($json['user']);
			//Use This for later
			$errorMessage = "";
			if ($errors) {
				foreach ($json['errors'] as $error) {
					if (isset($error['message']) && isset($error['field'])) {
						$errorMessage .= "[Plex.tv Error: " . $error['message'] . " for field: (" . $error['field'] . ")]";
					}
				}
			}
			$msg = (!empty($success) && empty($errors)) ? 'User has joined Plex' : $errorMessage;
			$status = (!empty($success) && empty($errors)) ? 'success' : 'error';
			$code = (!empty($success) && empty($errors)) ? 200 : 422;
			$this->setAPIResponse($status, $msg, $code);
			return (!empty($success) && empty($errors));
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Plex.TV Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'An Error Occurred', 409);
			return false;
		}
		return false;
	}
	
	public function lockCurrentUser()
	{
		if ($this->user['userID'] == '999') {
			$this->setAPIResponse('error', 'Locking not allowed on Guest users', 409);
			return false;
		}
		return $this->lockUser($this->user['userID']);
	}
	
	public function lockUser($id)
	{
		
		$user = $this->getUserById($id);
		if (!$user) {
			$this->setAPIResponse('error', 'User not found', 404);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['locked' => '1'],
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->writeLog('success', 'User Lockout Function - User: ' . $user['username'] . ' account locked', $this->user['username']);
		$this->setAPIResponse('success', 'User account locked', 200);
		return $this->processQueries($response);
	}
	
	public function unlockCurrentUser($array)
	{
		if ($array['password'] == '') {
			$this->setAPIResponse('error', 'Password Not Set', 422);
			return false;
		}
		$user = $this->getUserById($this->user['userID']);
		if (!password_verify($array['password'], $user['password'])) {
			$this->setAPIResponse('error', 'Password Incorrect', 401);
			return false;
		}
		return $this->unlockUser($this->user['userID']);
	}
	
	public function unlockUser($id)
	{
		$user = $this->getUserById($id);
		if (!$user) {
			$this->setAPIResponse('error', 'User not found', 404);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['locked' => '0'],
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->writeLog('success', 'User Lockout Function - User: ' . $user['username'] . ' account unlocked', $this->user['username']);
		$this->setAPIResponse('success', 'User account unlocked', 200);
		return $this->processQueries($response);
	}
	
	public function youtubeSearch($query)
	{
		if (!$query) {
			$this->setAPIResponse('error', 'No query supplied', 422);
			return false;
		}
		$keys = array(
			'AIzaSyBsdt8nLJRMTwOq5PY5A5GLZ2q7scgn01w',
			'AIzaSyD-8SHutB60GCcSM8q_Fle38rJUV7ujd8k',
			'AIzaSyBzOpVBT6VII-b-8gWD0MOEosGg4hyhCsQ',
			'AIzaSyBKnRe1P8fpfBHgooJpmT0WOsrdUtZ4cpk'
		);
		$randomKeyIndex = array_rand($keys);
		$key = $keys[$randomKeyIndex];
		$apikey = ($this->config['youtubeAPI'] !== '') ? $this->config['youtubeAPI'] : $key;
		$results = false;
		$url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=$query+official+trailer&part=snippet&maxResults=1&type=video&videoDuration=short&key=$apikey";
		$response = Requests::get($url);
		if ($response->success) {
			$results = json_decode($response->body, true);
			$this->setAPIResponse('success', null, 200, $results);
			return $results;
		} else {
			$this->setAPIResponse('error', 'Bad response from YouTube', 500);
			return false;
		}
	}
	
	public function scrapePage($array)
	{
		try {
			$url = $array['url'] ?? false;
			$type = $array['type'] ?? false;
			if (!$url) {
				$this->setAPIResponse('error', 'URL was not supplied', 422);
				return false;
			}
			$url = $this->qualifyURL($url);
			$data = array(
				'full_url' => $url,
				'drill_url' => $this->qualifyURL($url, true)
			);
			$options = array('verify' => false);
			$response = Requests::get($url, array(), $options);
			$data['response_code'] = $response->status_code;
			if ($response->success) {
				$data['result'] = 'Success';
				switch ($type) {
					case 'html':
						$data['data'] = html_entity_decode($response->body);
						break;
					case 'json':
						$data['data'] = json_decode($response->body);
						break;
					default:
						$data['data'] = $response->body;
				}
				$this->setAPIResponse('success', null, 200, $data);
				return $data;
			} else {
				$this->setAPIResponse('error', 'Error getting successful response', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function socks($url, $enabled, $auth, $requestObject, $header = null)
	{
		$error = false;
		if (!$this->config[$enabled]) {
			$error = true;
			$this->setAPIResponse('error', 'SOCKS module is not enabled', 409);
		}
		if (!$this->qualifyRequest($this->config[$auth], true)) {
			$error = true;
		}
		if (!$error) {
			$pre = explode('/api/v2/socks/', $requestObject->getUri()->getPath());
			$endpoint = explode('/', $pre[1]);
			$new = str_ireplace($endpoint[0], '', $pre[1]);
			$getParams = ($_GET) ? '?' . http_build_query($_GET) : '';
			$url = $this->qualifyURL($this->config[$url]) . $new . $getParams;
			$url = $this->cleanPath($url);
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$headers = [];
			if ($header) {
				if ($requestObject->hasHeader($header)) {
					$headerKey = $requestObject->getHeaderLine($header);
					$headers[$header] = $headerKey;
				}
			}
			switch ($requestObject->getMethod()) {
				case 'GET':
					$call = Requests::get($url, $headers, $options);
					break;
				case 'POST':
					$call = Requests::post($url, $headers, $this->apiData($requestObject), $options);
					break;
				case 'DELETE':
					$call = Requests::delete($url, $headers, $options);
					break;
				case 'PUT':
					$call = Requests::put($url, $headers, $this->apiData($requestObject), $options);
					break;
				default:
					$call = Requests::get($url, $headers, $options);
			}
			return $call->body;
		} else {
			return null;
		}
	}
	
	public function getPlexServers()
	{
		if ($this->config['plexToken'] == '') {
			$this->setAPIResponse('error', 'Plex Token cannot be empty', 422);
			return false;
		}
		$ownedOnly = isset($_GET['owned']) ?? false;
		$url = $this->qualifyURL('https://plex.tv/pms/servers');
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$headers = [
			'X-Plex-Product' => 'Organizr',
			'X-Plex-Version' => '2.0',
			'X-Plex-Client-Identifier' => '01010101-10101010',
			'X-Plex-Token' => $this->config['plexToken'],
		];
		$response = Requests::get($url, $headers, $options);
		libxml_use_internal_errors(true);
		if ($response->success) {
			$items = array();
			$plex = simplexml_load_string($response->body);
			foreach ($plex as $server) {
				if ($ownedOnly) {
					if ($server['owned'] == 1) {
						$items[] = array(
							'name' => (string)$server['name'],
							'address' => (string)$server['address'],
							'machineIdentifier' => (string)$server['machineIdentifier'],
							'owned' => (float)$server['owned'],
						);
					}
				} else {
					$items[] = array(
						'name' => (string)$server['name'],
						'address' => (string)$server['address'],
						'machineIdentifier' => (string)$server['machineIdentifier'],
						'owned' => (float)$server['owned'],
					);
				}
				
			}
			$this->setAPIResponse('success', null, 200, $items);
			return $items;
		}
		
	}
	
	public function getIcons()
	{
		$term = $_GET['search'] ?? null;
		$page = $_GET['page'] ?? 1;
		$limit = $_GET['limit'] ?? 20;
		$offset = ($page * $limit) - $limit;
		$goodIcons['results'] = [];
		$goodIcons['limit'] = $limit;
		$goodIcons['page'] = $page;
		$goodIcons['term'] = $term;
		$allIcons = file_get_contents($this->root . '/js/icons.json');
		$iconListing = json_decode($allIcons, true);
		foreach ($iconListing as $setKey => $set) {
			foreach ($set['children'] as $k => $v) {
				if (stripos($v['text'], $term) !== false || !$term) {
					$goodIcons['results'][] = $v;
				}
			}
		}
		$total = count($goodIcons['results']);
		$goodIcons['total'] = $total;
		$goodIcons['results'] = array_slice($goodIcons['results'], $offset, $limit);
		$goodIcons['pagination']['more'] = $page < (ceil($total / $limit));
		return $goodIcons;
	}
	
	protected function processQueries(array $request, $migration = false)
	{
		$results = array();
		$firstKey = '';
		try {
			foreach ($request as $k => $v) {
				
				$query = ($migration) ? $this->otherDb->query($v['query']) : $this->db->query($v['query']);
				$keyName = (isset($v['key'])) ? $v['key'] : $k;
				$firstKey = (isset($v['key']) && $k == 0) ? $v['key'] : $k;
				switch ($v['function']) {
					case 'fetchAll':
						$results[$keyName] = $query->fetchAll();
						break;
					case 'fetch':
						$results[$keyName] = $query->fetch();
						break;
					case 'getAffectedRows':
						$results[$keyName] = $query->getAffectedRows();
						break;
					case 'getRowCount':
						$results[$keyName] = $query->getRowCount();
						break;
					case 'fetchSingle':
						$results[$keyName] = $query->fetchSingle();
						break;
					case 'query':
						$results[$keyName] = $query;
						break;
					default:
						return false;
				}
			}
			
		} catch (Exception $e) {
			return $e;
		}
		return count($request) > 1 ? $results : $results[$firstKey];
	}
	
}
