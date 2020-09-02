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
	use ICalHomepageItem;
	use JDownloaderHomepageItem;
	use JellyfinHomepageItem;
	use LidarrHomepageItem;
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
	public $version = '2.1.0';
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
			'API Folder' => dirname(__DIR__, 1) . DIRECTORY_SEPARATOR,
			'DB Folder' => ($this->hasDB()) ? $this->config['dbLocation'] : false
		);
		// Connect to DB
		$this->connectDB();
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
		$route = $request->getUri()->getPath();
		$method = $request->getMethod();
		$data = $this->apiData($request);
		if (!in_array($route, $GLOBALS['bypass'])) {
			if ($this->isApprovedRequest($method, $data) === false) {
				$this->setAPIResponse('error', 'Not authorized', 401);
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
		return ($name) ? (str_replace(array('%20', '-', '_'), ' ', $name)) : '';
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
		ksort($allIconsPrep);
		foreach ($allIconsPrep as $item) {
			$allIcons[] = $item['path'] . $item['name'];
		}
		return $allIcons;
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
					'options' => $this->optionNotificationTypes()
				),
				array(
					'type' => 'select',
					'name' => 'notificationPosition',
					'class' => 'notifyPositionChanger',
					'label' => 'Position',
					'value' => $this->config['notificationPosition'],
					'options' => $this->optionNotificationPositions()
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
					'class' => 'popup-with-form getPlexTokenAuth plexAuth switchAuth',
					'icon' => 'fa fa-ticket',
					'text' => 'Retrieve',
					'href' => '#auth-plex-token-form',
					'attr' => 'data-effect="mfp-3d-unfold"'
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
					'class' => 'popup-with-form getPlexMachineAuth plexAuth switchAuth',
					'icon' => 'fa fa-id-badge',
					'text' => 'Retrieve',
					'href' => '#auth-plex-machine-form',
					'attr' => 'data-effect="mfp-3d-unfold"'
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
					'options' => $this->optionTime()
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
					'options' => $this->optionTime()
				),
				array(
					'type' => 'select',
					'name' => 'otherPingRefresh',
					'label' => 'Everyone Refresh Seconds',
					'value' => $this->config['otherPingRefresh'],
					'options' => $this->optionTime()
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
					'class' => 'popup-with-form getPlexTokenSSO',
					'icon' => 'fa fa-ticket',
					'text' => 'Retrieve',
					'href' => '#sso-plex-token-form',
					'attr' => 'data-effect="mfp-3d-unfold"'
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
					'class' => 'popup-with-form getPlexMachineSSO',
					'icon' => 'fa fa-id-badge',
					'text' => 'Retrieve',
					'href' => '#sso-plex-machine-form',
					'attr' => 'data-effect="mfp-3d-unfold"'
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
					'type' => 'switch',
					'name' => 'ssoOmbi',
					'label' => 'Enable',
					'value' => $this->config['ssoOmbi']
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
	
	public function revokeTokenCurrentUser($token)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM tokens WHERE user_id = ? or token = ?',
					[$this->user['userID']],
					[$token]
				)
			),
		];
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
			$homepageBuilt .= $this->buildHomepageItem($key);
		}
		return $homepageBuilt;
	}
	
	public function buildHomepageItem($homepageItem)
	{
		$item = '<div id="' . $homepageItem . '">';
		switch ($homepageItem) {
			case 'homepageOrdercustomhtml':
				if ($this->config['homepageCustomHTMLoneEnabled'] && $this->qualifyRequest($this->config['homepageCustomHTMLoneAuth'])) {
					$item .= ($this->config['customHTMLone'] !== '') ? $this->config['customHTMLone'] : '';
				}
				break;
			case 'homepageOrdercustomhtmlTwo':
				if ($this->config['homepageCustomHTMLtwoEnabled'] && $this->qualifyRequest($this->config['homepageCustomHTMLtwoAuth'])) {
					$item .= ($this->config['customHTMLtwo'] !== '') ? $this->config['customHTMLtwo'] : '';
				}
				break;
			case 'homepageOrdernotice':
				break;
			case 'homepageOrdernoticeguest':
				break;
			case 'homepageOrderqBittorrent':
				if ($this->config['homepageqBittorrentEnabled'] && $this->qualifyRequest($this->config['homepageqBittorrentAuth'])) {
					if ($this->config['qBittorrentCombine']) {
						$item .= '
	                <script>
	                // homepageOrderqBittorrent
	                buildDownloaderCombined(\'qBittorrent\');
	                homepageDownloader("qBittorrent", "' . $this->config['homepageDownloadRefresh'] . '");
	                // End homepageOrderqBittorrent
	                </script>
	                ';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
	                <script>
	                // homepageOrderqBittorrent
	                $("#' . $homepageItem . '").html(buildDownloader("qBittorrent"));
	                homepageDownloader("qBittorrent", "' . $this->config['homepageDownloadRefresh'] . '");
	                // End homepageOrderqBittorrent
	                </script>
	                ';
					}
				}
				break;
			case 'homepageOrderrTorrent':
				if ($this->config['homepagerTorrentEnabled'] && $this->qualifyRequest($this->config['homepagerTorrentAuth'])) {
					if ($this->config['rTorrentCombine']) {
						$item .= '
	                <script>
	                // homepageOrderrTorrent
	                buildDownloaderCombined(\'rTorrent\');
	                homepageDownloader("rTorrent", "' . $this->config['homepageDownloadRefresh'] . '");
	                // End homepageOrderrTorrent
	                </script>
	                ';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
	                <script>
	                // homepageOrderrTorrent
	                $("#' . $homepageItem . '").html(buildDownloader("rTorrent"));
	                homepageDownloader("rTorrent", "' . $this->config['homepageDownloadRefresh'] . '");
	                // End homepageOrderrTorrent
	                </script>
	                ';
					}
				}
				break;
			case 'homepageOrderdeluge':
				if ($this->config['homepageDelugeEnabled'] && $this->qualifyRequest($this->config['homepageDelugeAuth'])) {
					if ($this->config['delugeCombine']) {
						$item .= '
					<script>
					// Deluge
					buildDownloaderCombined(\'deluge\');
					homepageDownloader("deluge", "' . $this->config['homepageDownloadRefresh'] . '");
					// End Deluge
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// Deluge
					$("#' . $homepageItem . '").html(buildDownloader("deluge"));
					homepageDownloader("deluge", "' . $this->config['homepageDownloadRefresh'] . '");
					// End Deluge
					</script>
					';
					}
				}
				break;
			case 'homepageOrdertransmission':
				if ($this->config['homepageTransmissionEnabled'] && $this->qualifyRequest($this->config['homepageTransmissionAuth'])) {
					if ($this->config['transmissionCombine']) {
						$item .= '
					<script>
					// Transmission
					buildDownloaderCombined(\'transmission\');
					homepageDownloader("transmission", "' . $this->config['homepageDownloadRefresh'] . '");
					// End Transmission
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// Transmission
					$("#' . $homepageItem . '").html(buildDownloader("transmission"));
					homepageDownloader("transmission", "' . $this->config['homepageDownloadRefresh'] . '");
					// End Transmission
					</script>
					';
					}
				}
				break;
			case 'homepageOrdernzbget':
				if ($this->config['homepageNzbgetEnabled'] && $this->qualifyRequest($this->config['homepageNzbgetAuth'])) {
					if ($this->config['nzbgetCombine']) {
						$item .= '
					<script>
					// NZBGet
					buildDownloaderCombined(\'nzbget\');
					homepageDownloader("nzbget", "' . $this->config['homepageDownloadRefresh'] . '");
					// End NZBGet
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// NZBGet
					$("#' . $homepageItem . '").html(buildDownloader("nzbget"));
					homepageDownloader("nzbget", "' . $this->config['homepageDownloadRefresh'] . '");
					// End NZBGet
					</script>
					';
					}
				}
				break;
			case 'homepageOrderjdownloader':
				if ($this->config['homepageJdownloaderEnabled'] && $this->qualifyRequest($this->config['homepageJdownloaderAuth'])) {
					if ($this->config['jdownloaderCombine']) {
						$item .= '
					<script>
					// JDownloader
					buildDownloaderCombined(\'jdownloader\');
					homepageDownloader("jdownloader", "' . $this->config['homepageDownloadRefresh'] . '");
					// End JDownloader
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// JDownloader
					$("#' . $homepageItem . '").html(buildDownloader("jdownloader"));
					homepageDownloader("jdownloader", "' . $this->config['homepageDownloadRefresh'] . '");
					// End JDownloader
					</script>
					';
					}
				}
				break;
			case 'homepageOrdersabnzbd':
				if ($this->config['homepageSabnzbdEnabled'] && $this->qualifyRequest($this->config['homepageSabnzbdAuth'])) {
					if ($this->config['sabnzbdCombine']) {
						$item .= '
					<script>
					// SabNZBd
					buildDownloaderCombined(\'sabnzbd\');
					homepageDownloader("sabnzbd", "' . $this->config['homepageDownloadRefresh'] . '");
					// End SabNZBd
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// SabNZBd
					$("#' . $homepageItem . '").html(buildDownloader("sabnzbd"));
					homepageDownloader("sabnzbd", "' . $this->config['homepageDownloadRefresh'] . '");
					// End SabNZBd
					</script>
					';
					}
				}
				break;
			case 'homepageOrderplexnowplaying':
				if ($this->config['homepagePlexStreams']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Now Playing...</h2></div>';
					$item .= '
				<script>
				// Plex Stream
				homepageStream("plex", "' . $this->config['homepageStreamRefresh'] . '");
				// End Plex Stream
				</script>
				';
				}
				break;
			case 'homepageOrderplexrecent':
				if ($this->config['homepagePlexRecent']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Recent...</h2></div>';
					$item .= '
				<script>
				// Plex Recent
				homepageRecent("plex", "' . $this->config['homepageRecentRefresh'] . '");
				// End Plex Recent
				</script>
				';
				}
				break;
			case 'homepageOrderplexplaylist':
				if ($this->config['homepagePlexPlaylist']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Playlists...</h2></div>';
					$item .= '
				<script>
				// Plex Playlist
				homepagePlaylist("plex");
				// End Plex Playlist
				</script>
				';
				}
				break;
			case 'homepageOrderembynowplaying':
				if ($this->config['homepageEmbyStreams'] && $this->config['homepageEmbyEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Now Playing...</h2></div>';
					$item .= '
				<script>
				// Emby Stream
				homepageStream("emby", "' . $this->config['homepageStreamRefresh'] . '");
				// End Emby Stream
				</script>
				';
				}
				break;
			case 'homepageOrderembyrecent':
				if ($this->config['homepageEmbyRecent'] && $this->config['homepageEmbyEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Recent...</h2></div>';
					$item .= '
				<script>
				// Emby Recent
				homepageRecent("emby", "' . $this->config['homepageRecentRefresh'] . '");
				// End Emby Recent
				</script>
				';
				}
				break;
			case 'homepageOrderjellyfinnowplaying':
				if ($this->config['homepageJellyfinStreams'] && $this->config['homepageJellyfinEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Now Playing...</h2></div>';
					$item .= '
				<script>
				// Jellyfin Stream
				homepageStream("jellyfin", "' . $this->config['homepageStreamRefresh'] . '");
				// End Jellyfin Stream
				</script>
				';
				}
				break;
			case 'homepageOrderjellyfinrecent':
				if ($this->config['homepageJellyfinRecent'] && $this->config['homepageJellyfinEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Recent...</h2></div>';
					$item .= '
				<script>
				// Jellyfin Recent
				homepageRecent("jellyfin", "' . $this->config['homepageRecentRefresh'] . '");
				// End Jellyfin Recent
				</script>
				';
				}
				break;
			case 'homepageOrderombi':
				if ($this->config['homepageOmbiEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Requests...</h2></div>';
					$item .= '
				<script>
				// Ombi Requests
				homepageRequests("' . $this->config['ombiRefresh'] . '");
				// End Ombi Requests
				</script>
				';
				}
				break;
			case 'homepageOrdercalendar':
				if (
					($this->config['homepageLidarrEnabled'] && $this->qualifyRequest($this->config['homepageLidarrAuth'])) ||
					($this->config['homepageSonarrEnabled'] && $this->qualifyRequest($this->config['homepageSonarrAuth'])) ||
					($this->config['homepageRadarrEnabled'] && $this->qualifyRequest($this->config['homepageRadarrAuth'])) ||
					($this->config['homepageSickrageEnabled'] && $this->qualifyRequest($this->config['homepageSickrageAuth'])) ||
					($this->config['homepageCouchpotatoEnabled'] && $this->qualifyRequest($this->config['homepageCouchpotatoAuth'])) ||
					($this->config['homepageCalendarEnabled'] && $this->qualifyRequest($this->config['homepageCalendarAuth']) && $this->config['calendariCal'] !== '')
				) {
					$item .= '
				<div id="calendar" class="fc fc-ltr m-b-30"></div>
				<script>
				// Calendar
				homepageCalendar("' . $this->config['calendarRefresh'] . '");
				// End Calendar
				</script>
				';
				}
				break;
			case 'homepageOrderhealthchecks':
				if ($this->config['homepageHealthChecksEnabled'] && $this->qualifyRequest($this->config['homepageHealthChecksAuth'])) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Health Checks...</h2></div>';
					$item .= '
				<script>
				// Health Checks
				homepageHealthChecks("' . $this->config['healthChecksTags'] . '","' . $this->config['homepageHealthChecksRefresh'] . '");
				// End Health Checks
				</script>
				';
				}
				break;
			case 'homepageOrderunifi':
				if ($this->config['homepageUnifiEnabled'] && $this->qualifyRequest($this->config['homepageUnifiAuth'])) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Unifi...</h2></div>';
					$item .= '
				<script>
				// Unifi
				homepageUnifi("' . $this->config['homepageHealthChecksRefresh'] . '");
				// End Unifi
				</script>
				';
				}
				break;
			case 'homepageOrdertautulli':
				if ($this->config['homepageTautulliEnabled'] && $this->qualifyRequest($this->config['homepageTautulliAuth'])) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Tautulli...</h2></div>';
					$item .= '
				<script>
				// Tautulli
				homepageTautulli("' . $this->config['homepageTautulliRefresh'] . '");
				// End Tautulli
				</script>
				';
				}
				break;
			case 'homepageOrderPihole':
				if ($this->config['homepagePiholeEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Pi-hole Stats...</h2></div>';
					$item .= '
				<script>
				// Pi-hole Stats
				homepagePihole("' . $this->config['homepagePiholeRefresh'] . '");
				// End Pi-hole Stats
				</script>
				';
				}
				break;
			case 'homepageOrderMonitorr':
				if ($this->config['homepageMonitorrEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Monitorr...</h2></div>';
					$item .= '
				<script>
				// Monitorr
				homepageMonitorr("' . $this->config['homepageMonitorrRefresh'] . '");
				// End Monitorr
				</script>
				';
				}
				break;
			case 'homepageOrderWeatherAndAir':
				if ($this->config['homepageWeatherAndAirEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Weather And Air...</h2></div>';
					$item .= '
				<script>
				// Weather And Air
				homepageWeatherAndAir("' . $this->config['homepageWeatherAndAirRefresh'] . '");
				// End Weather And Air
				</script>
				';
				}
				break;
			case 'homepageOrderSpeedtest':
				if ($this->config['homepageSpeedtestEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Speedtest...</h2></div>';
					$item .= '
				<script>
				// Speedtest
				homepageSpeedtest("' . $this->config['homepageSpeedtestRefresh'] . '");
				// End Speedtest
				</script>
				';
				}
				break;
			case 'homepageOrderNetdata':
				if ($this->config['homepageNetdataEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Netdata...</h2></div>';
					$item .= '
				<script>
				// Netdata
				homepageNetdata("' . $this->config['homepageNetdataRefresh'] . '");
				// End Netdata
				</script>
				';
				}
				break;
			case 'homepageOrderOctoprint':
				if ($this->config['homepageOctoprintEnabled']) {
					$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Octoprint...</h2></div>';
					$item .= '
				<script>
				// Octoprint
				homepageOctoprint("' . $this->config['homepageOctoprintRefresh'] . '");
				// End Octoprint
				</script>
				';
				}
				break;
			case 'homepageOrderSonarrQueue':
				if ($this->config['homepageSonarrQueueEnabled'] && $this->qualifyRequest($this->config['homepageSonarrQueueAuth'])) {
					if ($this->config['homepageSonarrQueueCombine']) {
						$item .= '
					<script>
					// Sonarr Queue
					buildDownloaderCombined(\'sonarr\');
					homepageDownloader("sonarr", "' . $this->config['homepageSonarrQueueRefresh'] . '");
					// End Sonarr Queue
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// Sonarr Queue
					$("#' . $homepageItem . '").html(buildDownloader("sonarr"));
					homepageDownloader("sonarr", "' . $this->config['homepageSonarrQueueRefresh'] . '");
					// End Sonarr Queue
					</script>
					';
					}
				}
				break;
			case 'homepageOrderRadarrQueue':
				if ($this->config['homepageRadarrQueueEnabled'] && $this->qualifyRequest($this->config['homepageRadarrQueueAuth'])) {
					if ($this->config['homepageRadarrQueueCombine']) {
						$item .= '
					<script>
					// Radarr Queue
					buildDownloaderCombined(\'radarr\');
					homepageDownloader("radarr", "' . $this->config['homepageRadarrQueueRefresh'] . '");
					// End Radarr Queue
					</script>
					';
					} else {
						$item .= '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
						$item .= '
					<script>
					// Radarr Queue
					$("#' . $homepageItem . '").html(buildDownloader("radarr"));
					homepageDownloader("radarr", "' . $this->config['homepageRadarrQueueRefresh'] . '");
					// End Radarr Queue
					</script>
					';
					}
				}
				break;
			default:
				# code...
				break;
		}
		return $item . '</div>';
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
					if (!$this->config['homepageSonarrEnabled'] && !$this->config['homepageRadarrEnabled'] && !$this->config['homepageSickrageEnabled'] && !$this->config['homepageCouchpotatoEnabled']) {
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
	
	public function getSettingsHomepage()
	{
		$groups = $this->groupSelect();
		$ombiTvOptions = array(
			array(
				'name' => 'All Seasons',
				'value' => 'all'
			),
			array(
				'name' => 'First Season Only',
				'value' => 'first'
			),
			array(
				'name' => 'Last Season Only',
				'value' => 'last'
			),
		);
		$mediaServers = array(
			array(
				'name' => 'N/A',
				'value' => ''
			),
			array(
				'name' => 'Plex',
				'value' => 'plex'
			),
			array(
				'name' => 'Emby [Not Available]',
				'value' => 'emby'
			)
		);
		$limit = array(
			array(
				'name' => '1 Item',
				'value' => '1'
			),
			array(
				'name' => '2 Items',
				'value' => '2'
			),
			array(
				'name' => '3 Items',
				'value' => '3'
			),
			array(
				'name' => '4 Items',
				'value' => '4'
			),
			array(
				'name' => '5 Items',
				'value' => '5'
			),
			array(
				'name' => '6 Items',
				'value' => '6'
			),
			array(
				'name' => '7 Items',
				'value' => '7'
			),
			array(
				'name' => '8 Items',
				'value' => '8'
			),
			array(
				'name' => 'Unlimited',
				'value' => '1000'
			),
		);
		$day = array(
			array(
				'name' => 'Sunday',
				'value' => '0'
			),
			array(
				'name' => 'Monday',
				'value' => '1'
			),
			array(
				'name' => 'Tueday',
				'value' => '2'
			),
			array(
				'name' => 'Wednesday',
				'value' => '3'
			),
			array(
				'name' => 'Thursday',
				'value' => '4'
			),
			array(
				'name' => 'Friday',
				'value' => '5'
			),
			array(
				'name' => 'Saturday',
				'value' => '6'
			)
		);
		$calendarDefault = array(
			array(
				'name' => 'Month',
				'value' => 'month'
			),
			array(
				'name' => 'Day',
				'value' => 'basicDay'
			),
			array(
				'name' => 'Week',
				'value' => 'basicWeek'
			),
			array(
				'name' => 'List',
				'value' => 'list'
			)
		);
		$timeFormat = array(
			array(
				'name' => '6p',
				'value' => 'h(:mm)t'
			),
			array(
				'name' => '6:00p',
				'value' => 'h:mmt'
			),
			array(
				'name' => '6:00',
				'value' => 'h:mm'
			),
			array(
				'name' => '18',
				'value' => 'H(:mm)'
			),
			array(
				'name' => '18:00',
				'value' => 'H:mm'
			)
		);
		$rTorrentSortOptions = array(
			array(
				'name' => 'Date Desc',
				'value' => 'dated'
			),
			array(
				'name' => 'Date Asc',
				'value' => 'datea'
			),
			array(
				'name' => 'Hash Desc',
				'value' => 'hashd'
			),
			array(
				'name' => 'Hash Asc',
				'value' => 'hasha'
			),
			array(
				'name' => 'Name Desc',
				'value' => 'named'
			),
			array(
				'name' => 'Name Asc',
				'value' => 'namea'
			),
			array(
				'name' => 'Size Desc',
				'value' => 'sized'
			),
			array(
				'name' => 'Size Asc',
				'value' => 'sizea'
			),
			array(
				'name' => 'Label Desc',
				'value' => 'labeld'
			),
			array(
				'name' => 'Label Asc',
				'value' => 'labela'
			),
			array(
				'name' => 'Status Desc',
				'value' => 'statusd'
			),
			array(
				'name' => 'Status Asc',
				'value' => 'statusa'
			),
		);
		$qBittorrentApiOptions = array(
			array(
				'name' => 'V1',
				'value' => '1'
			),
			array(
				'name' => 'V2',
				'value' => '2'
			),
		);
		$qBittorrentSortOptions = array(
			array(
				'name' => 'Hash',
				'value' => 'hash'
			),
			array(
				'name' => 'Name',
				'value' => 'name'
			),
			array(
				'name' => 'Size',
				'value' => 'size'
			),
			array(
				'name' => 'Progress',
				'value' => 'progress'
			),
			array(
				'name' => 'Download Speed',
				'value' => 'dlspeed'
			),
			array(
				'name' => 'Upload Speed',
				'value' => 'upspeed'
			),
			array(
				'name' => 'Priority',
				'value' => 'priority'
			),
			array(
				'name' => 'Number of Seeds',
				'value' => 'num_seeds'
			),
			array(
				'name' => 'Number of Seeds in Swarm',
				'value' => 'num_complete'
			),
			array(
				'name' => 'Number of Leechers',
				'value' => 'num_leechs'
			),
			array(
				'name' => 'Number of Leechers in Swarm',
				'value' => 'num_incomplete'
			),
			array(
				'name' => 'Ratio',
				'value' => 'ratio'
			),
			array(
				'name' => 'ETA',
				'value' => 'eta'
			),
			array(
				'name' => 'State',
				'value' => 'state'
			),
			array(
				'name' => 'Category',
				'value' => 'category'
			)
		);
		$xmlStatus = (extension_loaded('xmlrpc')) ? 'Installed' : 'Not Installed';
		return array(
			array(
				'name' => 'Calendar',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/calendar.png',
				'category' => 'HOMEPAGE',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageCalendarEnabled',
							'label' => 'Enable iCal',
							'value' => $this->config['homepageCalendarEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageCalendarAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageCalendarAuth'],
							'options' => $groups
						),
						array(
							'type' => 'input',
							'name' => 'calendariCal',
							'label' => 'iCal URL\'s',
							'value' => $this->config['calendariCal'],
							'placeholder' => 'separate by comma\'s'
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'number',
							'name' => 'calendarStart',
							'label' => '# of Days Before',
							'value' => $this->config['calendarStart'],
							'placeholder' => ''
						),
						array(
							'type' => 'number',
							'name' => 'calendarEnd',
							'label' => '# of Days After',
							'value' => $this->config['calendarEnd'],
							'placeholder' => ''
						),
						array(
							'type' => 'select',
							'name' => 'calendarFirstDay',
							'label' => 'Start Day',
							'value' => $this->config['calendarFirstDay'],
							'options' => $day
						),
						array(
							'type' => 'select',
							'name' => 'calendarDefault',
							'label' => 'Default View',
							'value' => $this->config['calendarDefault'],
							'options' => $calendarDefault
						),
						array(
							'type' => 'select',
							'name' => 'calendarTimeFormat',
							'label' => 'Time Format',
							'value' => $this->config['calendarTimeFormat'],
							'options' => $timeFormat
						),
						array(
							'type' => 'select',
							'name' => 'calendarLimit',
							'label' => 'Items Per Day',
							'value' => $this->config['calendarLimit'],
							'options' => $limit
						),
						array(
							'type' => 'select',
							'name' => 'calendarRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['calendarRefresh'],
							'options' => $this->optionTime()
						)
					),
				)
			),
			array(
				'name' => 'Plex',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/plex.png',
				'category' => 'Media Server',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepagePlexEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepagePlexEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepagePlexAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepagePlexAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'plexURL',
							'label' => 'URL',
							'value' => $this->config['plexURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'plexToken',
							'label' => 'Token',
							'value' => $this->config['plexToken']
						),
						array(
							'type' => 'password-alt',
							'name' => 'plexID',
							'label' => 'Plex Machine',
							'value' => $this->config['plexID']
						)
					),
					'Active Streams' => array(
						array(
							'type' => 'switch',
							'name' => 'homepagePlexStreams',
							'label' => 'Enable',
							'value' => $this->config['homepagePlexStreams']
						),
						array(
							'type' => 'select',
							'name' => 'homepagePlexStreamsAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepagePlexStreamsAuth'],
							'options' => $groups
						),
						array(
							'type' => 'switch',
							'name' => 'homepageShowStreamNames',
							'label' => 'User Information',
							'value' => $this->config['homepageShowStreamNames']
						),
						array(
							'type' => 'select',
							'name' => 'homepageShowStreamNamesAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageShowStreamNamesAuth'],
							'options' => $groups
						),
						array(
							'type' => 'select',
							'name' => 'homepageStreamRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageStreamRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Recent Items' => array(
						array(
							'type' => 'switch',
							'name' => 'homepagePlexRecent',
							'label' => 'Enable',
							'value' => $this->config['homepagePlexRecent']
						),
						array(
							'type' => 'select',
							'name' => 'homepagePlexRecentAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepagePlexRecentAuth'],
							'options' => $groups
						),
						array(
							'type' => 'number',
							'name' => 'homepageRecentLimit',
							'label' => 'Item Limit',
							'value' => $this->config['homepageRecentLimit'],
						),
						array(
							'type' => 'select',
							'name' => 'homepageRecentRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageRecentRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Media Search' => array(
						array(
							'type' => 'switch',
							'name' => 'mediaSearch',
							'label' => 'Enable',
							'value' => $this->config['mediaSearch']
						),
						array(
							'type' => 'select',
							'name' => 'mediaSearchAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['mediaSearchAuth'],
							'options' => $groups
						),
						array(
							'type' => 'select',
							'name' => 'mediaSearchType',
							'label' => 'Media Server',
							'value' => $this->config['mediaSearchType'],
							'options' => $mediaServers
						),
					),
					'Playlists' => array(
						array(
							'type' => 'switch',
							'name' => 'homepagePlexPlaylist',
							'label' => 'Enable',
							'value' => $this->config['homepagePlexPlaylist']
						),
						array(
							'type' => 'select',
							'name' => 'homepagePlexPlaylistAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepagePlexPlaylistAuth'],
							'options' => $groups
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'input',
							'name' => 'plexTabName',
							'label' => 'Plex Tab Name',
							'value' => $this->config['plexTabName'],
							'placeholder' => 'Only use if you have Plex in a reverse proxy'
						),
						array(
							'type' => 'input',
							'name' => 'plexTabURL',
							'label' => 'Plex Tab WAN URL',
							'value' => $this->config['plexTabURL'],
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'select',
							'name' => 'cacheImageSize',
							'label' => 'Image Cache Size',
							'value' => $this->config['cacheImageSize'],
							'options' => array(
								array(
									'name' => 'Low',
									'value' => '.5'
								),
								array(
									'name' => '1x',
									'value' => '1'
								),
								array(
									'name' => '2x',
									'value' => '2'
								),
								array(
									'name' => '3x',
									'value' => '3'
								)
							)
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'plex\')"'
						),
					)
				)
			),
			array(
				'name' => 'Emby',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/emby.png',
				'category' => 'Media Server',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageEmbyEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageEmbyEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageEmbyAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageEmbyAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'embyURL',
							'label' => 'URL',
							'value' => $this->config['embyURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'embyToken',
							'label' => 'Token',
							'value' => $this->config['embyToken']
						)
					),
					'Active Streams' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageEmbyStreams',
							'label' => 'Enable',
							'value' => $this->config['homepageEmbyStreams']
						),
						array(
							'type' => 'select',
							'name' => 'homepageEmbyStreamsAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageEmbyStreamsAuth'],
							'options' => $groups
						),
						array(
							'type' => 'switch',
							'name' => 'homepageShowStreamNames',
							'label' => 'User Information',
							'value' => $this->config['homepageShowStreamNames']
						),
						array(
							'type' => 'select',
							'name' => 'homepageShowStreamNamesAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageShowStreamNamesAuth'],
							'options' => $groups
						),
						array(
							'type' => 'select',
							'name' => 'homepageStreamRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageStreamRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Recent Items' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageEmbyRecent',
							'label' => 'Enable',
							'value' => $this->config['homepageEmbyRecent']
						),
						array(
							'type' => 'select',
							'name' => 'homepageEmbyRecentAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageEmbyRecentAuth'],
							'options' => $groups
						),
						array(
							'type' => 'number',
							'name' => 'homepageRecentLimit',
							'label' => 'Item Limit',
							'value' => $this->config['homepageRecentLimit'],
						),
						array(
							'type' => 'select',
							'name' => 'homepageRecentRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageRecentRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'input',
							'name' => 'embyTabName',
							'label' => 'Emby Tab Name',
							'value' => $this->config['embyTabName'],
							'placeholder' => 'Only use if you have Emby in a reverse proxy'
						),
						array(
							'type' => 'input',
							'name' => 'embyTabURL',
							'label' => 'Emby Tab WAN URL',
							'value' => $this->config['embyTabURL'],
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'select',
							'name' => 'cacheImageSize',
							'label' => 'Image Cache Size',
							'value' => $this->config['cacheImageSize'],
							'options' => array(
								array(
									'name' => 'Low',
									'value' => '.5'
								),
								array(
									'name' => '1x',
									'value' => '1'
								),
								array(
									'name' => '2x',
									'value' => '2'
								),
								array(
									'name' => '3x',
									'value' => '3'
								)
							)
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'emby\')"'
						),
					)
				)
			),
			array(
				'name' => 'Jellyfin',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/jellyfin.png',
				'category' => 'Media Server',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageJellyfinEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageJellyfinEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageJellyfinAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageJellyfinAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'jellyfinURL',
							'label' => 'URL',
							'value' => $this->config['jellyfinURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'jellyfinToken',
							'label' => 'Token',
							'value' => $this->config['jellyfinToken']
						)
					),
					'Active Streams' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageJellyfinStreams',
							'label' => 'Enable',
							'value' => $this->config['homepageJellyfinStreams']
						),
						array(
							'type' => 'select',
							'name' => 'homepageJellyStreamsAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageJellyStreamsAuth'],
							'options' => $groups
						),
						array(
							'type' => 'switch',
							'name' => 'homepageShowStreamNames',
							'label' => 'User Information',
							'value' => $this->config['homepageShowStreamNames']
						),
						array(
							'type' => 'select',
							'name' => 'homepageShowStreamNamesAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageShowStreamNamesAuth'],
							'options' => $groups
						),
						array(
							'type' => 'select',
							'name' => 'homepageStreamRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageStreamRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Recent Items' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageJellyfinRecent',
							'label' => 'Enable',
							'value' => $this->config['homepageJellyfinRecent']
						),
						array(
							'type' => 'select',
							'name' => 'homepageJellyfinRecentAuth',
							'label' => 'Minimum Authorization',
							'value' => $this->config['homepageJellyfinRecentAuth'],
							'options' => $groups
						),
						array(
							'type' => 'number',
							'name' => 'homepageRecentLimit',
							'label' => 'Item Limit',
							'value' => $this->config['homepageRecentLimit'],
						),
						array(
							'type' => 'select',
							'name' => 'homepageRecentRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageRecentRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'input',
							'name' => 'jellyfinTabName',
							'label' => 'Jellyfin Tab Name',
							'value' => $this->config['jellyfinTabName'],
							'placeholder' => 'Only use if you have Jellyfin in a reverse proxy'
						),
						array(
							'type' => 'input',
							'name' => 'jellyfinTabURL',
							'label' => 'Jellyfin Tab WAN URL',
							'value' => $this->config['jellyfinTabURL'],
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'select',
							'name' => 'cacheImageSize',
							'label' => 'Image Cache Size',
							'value' => $this->config['cacheImageSize'],
							'options' => array(
								array(
									'name' => 'Low',
									'value' => '.5'
								),
								array(
									'name' => '1x',
									'value' => '1'
								),
								array(
									'name' => '2x',
									'value' => '2'
								),
								array(
									'name' => '3x',
									'value' => '3'
								)
							)
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'jellyfin\')"'
						),
					)
				)
			),
			array(
				'name' => 'JDownloader',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/jdownloader.png',
				'category' => 'Downloader',
				'settings' => array(
					'custom' => '
				<div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
								<span lang="en">Notice</span>
                            </div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
									<ul class="list-icons">
                                        <li><i class="fa fa-chevron-right text-danger"></i> <a href="https://pypi.org/project/myjd-api/" target="_blank">Download [myjd-api] Module</a></li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Add <b>/api/myjd</b> to the URL if you are using <a href="https://pypi.org/project/RSScrawler/" target="_blank">RSScrawler</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
				',
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageJdownloaderEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageJdownloaderEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageJdownloaderAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageJdownloaderAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'jdownloaderURL',
							'label' => 'URL',
							'value' => $this->config['jdownloaderURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'jdownloaderCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['jdownloaderCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'jdownloader\')"'
						),
					)
				)
			),
			array(
				'name' => 'SabNZBD',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/sabnzbd.png',
				'category' => 'Downloader',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageSabnzbdEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageSabnzbdEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageSabnzbdAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageSabnzbdAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'sabnzbdURL',
							'label' => 'URL',
							'value' => $this->config['sabnzbdURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'sabnzbdToken',
							'label' => 'Token',
							'value' => $this->config['sabnzbdToken']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'sabnzbdCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['sabnzbdCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'sabnzbd\')"'
						),
					)
				)
			),
			array(
				'name' => 'NZBGet',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/nzbget.png',
				'category' => 'Downloader',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageNzbgetEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageNzbgetEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageNzbgetAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageNzbgetAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'nzbgetURL',
							'label' => 'URL',
							'value' => $this->config['nzbgetURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'input',
							'name' => 'nzbgetUsername',
							'label' => 'Username',
							'value' => $this->config['nzbgetUsername']
						),
						array(
							'type' => 'password',
							'name' => 'nzbgetPassword',
							'label' => 'Password',
							'value' => $this->config['nzbgetPassword']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'nzbgetCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['nzbgetCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'nzbget\')"'
						),
					)
				)
			),
			array(
				'name' => 'Transmission',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/transmission.png',
				'category' => 'Downloader',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageTransmissionEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageTransmissionEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageTransmissionAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageTransmissionAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'transmissionURL',
							'label' => 'URL',
							'value' => $this->config['transmissionURL'],
							'help' => 'Please do not included /web in URL.  Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'input',
							'name' => 'transmissionUsername',
							'label' => 'Username',
							'value' => $this->config['transmissionUsername']
						),
						array(
							'type' => 'password',
							'name' => 'transmissionPassword',
							'label' => 'Password',
							'value' => $this->config['transmissionPassword']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'switch',
							'name' => 'transmissionHideSeeding',
							'label' => 'Hide Seeding',
							'value' => $this->config['transmissionHideSeeding']
						), array(
							'type' => 'switch',
							'name' => 'transmissionHideCompleted',
							'label' => 'Hide Completed',
							'value' => $this->config['transmissionHideCompleted']
						),
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'transmissionCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['transmissionCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'transmission\')"'
						),
					)
				)
			),
			array(
				'name' => 'qBittorrent',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/qBittorrent.png',
				'category' => 'Downloader',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageqBittorrentEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageqBittorrentEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageqBittorrentAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageqBittorrentAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'qBittorrentURL',
							'label' => 'URL',
							'value' => $this->config['qBittorrentURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'select',
							'name' => 'qBittorrentApiVersion',
							'label' => 'API Version',
							'value' => $this->config['qBittorrentApiVersion'],
							'options' => $qBittorrentApiOptions
						),
						array(
							'type' => 'input',
							'name' => 'qBittorrentUsername',
							'label' => 'Username',
							'value' => $this->config['qBittorrentUsername']
						),
						array(
							'type' => 'password',
							'name' => 'qBittorrentPassword',
							'label' => 'Password',
							'value' => $this->config['qBittorrentPassword']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'switch',
							'name' => 'qBittorrentHideSeeding',
							'label' => 'Hide Seeding',
							'value' => $this->config['qBittorrentHideSeeding']
						),
						array(
							'type' => 'switch',
							'name' => 'qBittorrentHideCompleted',
							'label' => 'Hide Completed',
							'value' => $this->config['qBittorrentHideCompleted']
						),
						array(
							'type' => 'select',
							'name' => 'qBittorrentSortOrder',
							'label' => 'Order',
							'value' => $this->config['qBittorrentSortOrder'],
							'options' => $qBittorrentSortOptions
						), array(
							'type' => 'switch',
							'name' => 'qBittorrentReverseSorting',
							'label' => 'Reverse Sorting',
							'value' => $this->config['qBittorrentReverseSorting']
						),
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'qBittorrentCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['qBittorrentCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'qbittorrent\')"'
						),
					)
				)
			),
			array(
				'name' => 'rTorrent',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/rTorrent.png',
				'category' => 'Downloader',
				'settings' => array(
					'FYI' => array(
						array(
							'type' => 'html',
							'label' => '',
							'override' => 12,
							'html' => '
						<div class="row">
						    <div class="col-lg-12">
						        <div class="panel panel-info">
						            <div class="panel-heading">
						                <span lang="en">ATTENTION</span>
						            </div>
						            <div class="panel-wrapper collapse in" aria-expanded="true">
						                <div class="panel-body">
						                	<h4 lang="en">This module requires XMLRPC</h4>
						                    <span lang="en">Status: [ <b>' . $xmlStatus . '</b> ]</span>
						                    <br/></br>
						                    <span lang="en">
						                    	<h4><b>Note about API URL</b></h4>
						                    	Organizr appends the url with <code>/RPC2</code> unless the URL ends in <code>.php</code><br/>
						                    	<h5>Possible URLs:</h5>
						                    	<li>http://localhost:8080</li>
						                    	<li>https://domain.site/xmlrpc.php</li>
						                    	<li>https://seedbox.site/rutorrent/plugins/httprpc/action.php</li>
						                    </span>
						                </div>
						            </div>
						        </div>
						    </div>
						</div>
						'
						)
					),
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepagerTorrentEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepagerTorrentEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepagerTorrentAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepagerTorrentAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'rTorrentURL',
							'label' => 'URL',
							'value' => $this->config['rTorrentURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'input',
							'name' => 'rTorrentURLOverride',
							'label' => 'rTorrent API URL Override',
							'value' => $this->config['rTorrentURLOverride'],
							'help' => 'Only use if you cannot connect.  Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port/xmlrpc'
						),
						array(
							'type' => 'input',
							'name' => 'rTorrentUsername',
							'label' => 'Username',
							'value' => $this->config['rTorrentUsername']
						),
						array(
							'type' => 'password',
							'name' => 'rTorrentPassword',
							'label' => 'Password',
							'value' => $this->config['rTorrentPassword']
						),
						array(
							'type' => 'switch',
							'name' => 'rTorrentDisableCertCheck',
							'label' => 'Disable Certificate Check',
							'value' => $this->config['rTorrentDisableCertCheck']
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'switch',
							'name' => 'rTorrentHideSeeding',
							'label' => 'Hide Seeding',
							'value' => $this->config['rTorrentHideSeeding']
						), array(
							'type' => 'switch',
							'name' => 'rTorrentHideCompleted',
							'label' => 'Hide Completed',
							'value' => $this->config['rTorrentHideCompleted']
						),
						array(
							'type' => 'select',
							'name' => 'rTorrentSortOrder',
							'label' => 'Order',
							'value' => $this->config['rTorrentSortOrder'],
							'options' => $rTorrentSortOptions
						),
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'number',
							'name' => 'rTorrentLimit',
							'label' => 'Item Limit',
							'value' => $this->config['rTorrentLimit'],
						),
						array(
							'type' => 'switch',
							'name' => 'rTorrentCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['rTorrentCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'rtorrent\')"'
						),
					)
				)
			),
			array(
				'name' => 'Deluge',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/deluge.png',
				'category' => 'Downloader',
				'settings' => array(
					'custom' => '
				<div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
								<span lang="en">Notice</span>
                            </div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
									<ul class="list-icons">
                                        <li><i class="fa fa-chevron-right text-danger"></i> <a href="https://github.com/idlesign/deluge-webapi/tree/master/dist" target="_blank">Download Plugin</a></li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Open Deluge Web UI, go to "Preferences -> Plugins -> Install plugin" and choose egg file.</li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Activate WebAPI plugin </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
				',
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageDelugeEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageDelugeEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageDelugeAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageDelugeAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'delugeURL',
							'label' => 'URL',
							'value' => $this->config['delugeURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password',
							'name' => 'delugePassword',
							'label' => 'Password',
							'value' => $this->config['delugePassword']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'switch',
							'name' => 'delugeHideSeeding',
							'label' => 'Hide Seeding',
							'value' => $this->config['delugeHideSeeding']
						), array(
							'type' => 'switch',
							'name' => 'delugeHideCompleted',
							'label' => 'Hide Completed',
							'value' => $this->config['delugeHideCompleted']
						),
						array(
							'type' => 'select',
							'name' => 'homepageDownloadRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageDownloadRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'delugeCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['delugeCombine']
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'deluge\')"'
						),
					)
				)
			),
			array(
				'name' => 'Sonarr',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/sonarr.png',
				'category' => 'PVR',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageSonarrEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageSonarrEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageSonarrAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageSonarrAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'sonarrURL',
							'label' => 'URL',
							'value' => $this->config['sonarrURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'sonarrToken',
							'label' => 'Token',
							'value' => $this->config['sonarrToken']
						)
					),
					'API SOCKS' => array(
						array(
							'type' => 'html',
							'override' => 12,
							'label' => '',
							'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">
										<h3 lang="en">Sonarr SOCKS API Connection</h3>
										<p>Using this feature allows you to access the Sonarr API without having to reverse proxy it.  Just access it from: </p>
										<code>' . $this->getServerPath() . 'api/v2/socks/sonarr/</code>
									</div>
								</div>
							</div>'
						),
						array(
							'type' => 'switch',
							'name' => 'sonarrSocksEnabled',
							'label' => 'Enable',
							'value' => $this->config['sonarrSocksEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'sonarrSocksAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['sonarrSocksAuth'],
							'options' => $groups
						),
					),
					'Queue' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageSonarrQueueEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageSonarrQueueEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageSonarrQueueAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageSonarrQueueAuth'],
							'options' => $groups
						),
						array(
							'type' => 'switch',
							'name' => 'homepageSonarrQueueCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['homepageSonarrQueueCombine']
						),
						array(
							'type' => 'select',
							'name' => 'homepageSonarrQueueRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageSonarrQueueRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Calendar' => array(
						array(
							'type' => 'number',
							'name' => 'calendarStart',
							'label' => '# of Days Before',
							'value' => $this->config['calendarStart'],
							'placeholder' => ''
						),
						array(
							'type' => 'number',
							'name' => 'calendarEnd',
							'label' => '# of Days After',
							'value' => $this->config['calendarEnd'],
							'placeholder' => ''
						),
						array(
							'type' => 'select',
							'name' => 'calendarFirstDay',
							'label' => 'Start Day',
							'value' => $this->config['calendarFirstDay'],
							'options' => $day
						),
						array(
							'type' => 'select',
							'name' => 'calendarDefault',
							'label' => 'Default View',
							'value' => $this->config['calendarDefault'],
							'options' => $calendarDefault
						),
						array(
							'type' => 'select',
							'name' => 'calendarTimeFormat',
							'label' => 'Time Format',
							'value' => $this->config['calendarTimeFormat'],
							'options' => $timeFormat
						),
						array(
							'type' => 'select',
							'name' => 'calendarLimit',
							'label' => 'Items Per Day',
							'value' => $this->config['calendarLimit'],
							'options' => $limit
						),
						array(
							'type' => 'select',
							'name' => 'calendarRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['calendarRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'sonarrUnmonitored',
							'label' => 'Show Unmonitored',
							'value' => $this->config['sonarrUnmonitored']
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'sonarr\')"'
						),
					)
				)
			),
			array(
				'name' => 'Lidarr',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/lidarr.png',
				'category' => 'PMR',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageLidarrEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageLidarrEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageLidarrAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageLidarrAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'lidarrURL',
							'label' => 'URL',
							'value' => $this->config['lidarrURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'lidarrToken',
							'label' => 'Token',
							'value' => $this->config['lidarrToken']
						)
					),
					'API SOCKS' => array(
						array(
							'type' => 'html',
							'override' => 12,
							'label' => '',
							'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">
										<h3 lang="en">Lidarr SOCKS API Connection</h3>
										<p>Using this feature allows you to access the Lidarr API without having to reverse proxy it.  Just access it from: </p>
										<code>' . $this->getServerPath() . 'api/v2/socks/lidarr/</code>
									</div>
								</div>
							</div>'
						),
						array(
							'type' => 'switch',
							'name' => 'lidarrSocksEnabled',
							'label' => 'Enable',
							'value' => $this->config['lidarrSocksEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'lidarrSocksAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['lidarrSocksAuth'],
							'options' => $groups
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'number',
							'name' => 'calendarStart',
							'label' => '# of Days Before',
							'value' => $this->config['calendarStart'],
							'placeholder' => ''
						),
						array(
							'type' => 'number',
							'name' => 'calendarEnd',
							'label' => '# of Days After',
							'value' => $this->config['calendarEnd'],
							'placeholder' => ''
						),
						array(
							'type' => 'select',
							'name' => 'calendarFirstDay',
							'label' => 'Start Day',
							'value' => $this->config['calendarFirstDay'],
							'options' => $day
						),
						array(
							'type' => 'select',
							'name' => 'calendarDefault',
							'label' => 'Default View',
							'value' => $this->config['calendarDefault'],
							'options' => $calendarDefault
						),
						array(
							'type' => 'select',
							'name' => 'calendarTimeFormat',
							'label' => 'Time Format',
							'value' => $this->config['calendarTimeFormat'],
							'options' => $timeFormat
						),
						array(
							'type' => 'select',
							'name' => 'calendarLimit',
							'label' => 'Items Per Day',
							'value' => $this->config['calendarLimit'],
							'options' => $limit
						),
						array(
							'type' => 'select',
							'name' => 'calendarRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['calendarRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'lidarr\')"'
						),
					)
				)
			),
			array(
				'name' => 'Radarr',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/radarr.png',
				'category' => 'PVR',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageRadarrEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageRadarrEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageRadarrAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageRadarrAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'radarrURL',
							'label' => 'URL',
							'value' => $this->config['radarrURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'radarrToken',
							'label' => 'Token',
							'value' => $this->config['radarrToken']
						)
					),
					'API SOCKS' => array(
						array(
							'type' => 'html',
							'override' => 12,
							'label' => '',
							'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">
										<h3 lang="en">Radarr SOCKS API Connection</h3>
										<p>Using this feature allows you to access the Radarr API without having to reverse proxy it.  Just access it from: </p>
										<code>' . $this->getServerPath() . 'api/v2/socks/radarr/</code>
									</div>
								</div>
							</div>'
						),
						array(
							'type' => 'switch',
							'name' => 'radarrSocksEnabled',
							'label' => 'Enable',
							'value' => $this->config['radarrSocksEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'radarrSocksAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['radarrSocksAuth'],
							'options' => $groups
						),
					),
					'Queue' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageRadarrQueueEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageRadarrQueueEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageRadarrQueueAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageRadarrQueueAuth'],
							'options' => $groups
						),
						array(
							'type' => 'switch',
							'name' => 'homepageRadarrQueueCombine',
							'label' => 'Add to Combined Downloader',
							'value' => $this->config['homepageRadarrQueueCombine']
						),
						array(
							'type' => 'select',
							'name' => 'homepageRadarrQueueRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageRadarrQueueRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Calendar' => array(
						array(
							'type' => 'number',
							'name' => 'calendarStart',
							'label' => '# of Days Before',
							'value' => $this->config['calendarStart'],
							'placeholder' => ''
						),
						array(
							'type' => 'number',
							'name' => 'calendarEnd',
							'label' => '# of Days After',
							'value' => $this->config['calendarEnd'],
							'placeholder' => ''
						),
						array(
							'type' => 'select',
							'name' => 'calendarFirstDay',
							'label' => 'Start Day',
							'value' => $this->config['calendarFirstDay'],
							'options' => $day
						),
						array(
							'type' => 'select',
							'name' => 'calendarDefault',
							'label' => 'Default View',
							'value' => $this->config['calendarDefault'],
							'options' => $calendarDefault
						),
						array(
							'type' => 'select',
							'name' => 'calendarTimeFormat',
							'label' => 'Time Format',
							'value' => $this->config['calendarTimeFormat'],
							'options' => $timeFormat
						),
						array(
							'type' => 'select',
							'name' => 'calendarLimit',
							'label' => 'Items Per Day',
							'value' => $this->config['calendarLimit'],
							'options' => $limit
						),
						array(
							'type' => 'select',
							'name' => 'calendarRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['calendarRefresh'],
							'options' => $this->optionTime()
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'radarr\')"'
						),
					)
				)
			),
			array(
				'name' => 'CouchPotato',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/couchpotato.png',
				'category' => 'PVR',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageCouchpotatoEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageCouchpotatoEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageCouchpotatoAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageCouchpotatoAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'couchpotatoURL',
							'label' => 'URL',
							'value' => $this->config['couchpotatoURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'couchpotatoToken',
							'label' => 'Token',
							'value' => $this->config['couchpotatoToken']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'calendarFirstDay',
							'label' => 'Start Day',
							'value' => $this->config['calendarFirstDay'],
							'options' => $day
						),
						array(
							'type' => 'select',
							'name' => 'calendarDefault',
							'label' => 'Default View',
							'value' => $this->config['calendarDefault'],
							'options' => $calendarDefault
						),
						array(
							'type' => 'select',
							'name' => 'calendarTimeFormat',
							'label' => 'Time Format',
							'value' => $this->config['calendarTimeFormat'],
							'options' => $timeFormat
						),
						array(
							'type' => 'select',
							'name' => 'calendarLimit',
							'label' => 'Items Per Day',
							'value' => $this->config['calendarLimit'],
							'options' => $limit
						),
						array(
							'type' => 'select',
							'name' => 'calendarRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['calendarRefresh'],
							'options' => $this->optionTime()
						)
					)
				)
			),
			array(
				'name' => 'SickRage',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/sickrage.png',
				'category' => 'PVR',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageSickrageEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageSickrageEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageSickrageAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageSickrageAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'sickrageURL',
							'label' => 'URL',
							'value' => $this->config['sickrageURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'sickrageToken',
							'label' => 'Token',
							'value' => $this->config['sickrageToken']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'calendarFirstDay',
							'label' => 'Start Day',
							'value' => $this->config['calendarFirstDay'],
							'options' => $day
						),
						array(
							'type' => 'select',
							'name' => 'calendarDefault',
							'label' => 'Default View',
							'value' => $this->config['calendarDefault'],
							'options' => $calendarDefault
						),
						array(
							'type' => 'select',
							'name' => 'calendarTimeFormat',
							'label' => 'Time Format',
							'value' => $this->config['calendarTimeFormat'],
							'options' => $timeFormat
						),
						array(
							'type' => 'select',
							'name' => 'calendarLimit',
							'label' => 'Items Per Day',
							'value' => $this->config['calendarLimit'],
							'options' => $limit
						),
						array(
							'type' => 'select',
							'name' => 'calendarRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['calendarRefresh'],
							'options' => $this->optionTime()
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'sickrage\')"'
						),
					)
				)
			),
			array(
				'name' => 'Ombi',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/ombi.png',
				'category' => 'Requests',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageOmbiEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageOmbiEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageOmbiAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageOmbiAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'ombiURL',
							'label' => 'URL',
							'value' => $this->config['ombiURL'],
							'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
							'placeholder' => 'http(s)://hostname:port'
						),
						array(
							'type' => 'password-alt',
							'name' => 'ombiToken',
							'label' => 'Token',
							'value' => $this->config['ombiToken']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'homepageOmbiRequestAuth',
							'label' => 'Minimum Group to Request',
							'value' => $this->config['homepageOmbiRequestAuth'],
							'options' => $groups
						),
						array(
							'type' => 'select',
							'name' => 'ombiTvDefault',
							'label' => 'TV Show Default Request',
							'value' => $this->config['ombiTvDefault'],
							'options' => $ombiTvOptions
						),
						array(
							'type' => 'switch',
							'name' => 'ombiLimitUser',
							'label' => 'Limit to User',
							'value' => $this->config['ombiLimitUser']
						),
						array(
							'type' => 'number',
							'name' => 'ombiLimit',
							'label' => 'Item Limit',
							'value' => $this->config['ombiLimit'],
						),
						array(
							'type' => 'select',
							'name' => 'ombiRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['ombiRefresh'],
							'options' => $this->optionTime()
						),
						array(
							'type' => 'switch',
							'name' => 'ombiAlias',
							'label' => 'Use Ombi Alias Names',
							'value' => $this->config['ombiAlias'],
							'help' => 'Use Ombi Alias Names instead of Usernames - If Alias is blank, Alias will fallback to Username'
						)
					),
					'Default Filter' => array(
						array(
							'type' => 'switch',
							'name' => 'ombiDefaultFilterAvailable',
							'label' => 'Show Available',
							'value' => $this->config['ombiDefaultFilterAvailable'],
							'help' => 'Show All Available Ombi Requests'
						),
						array(
							'type' => 'switch',
							'name' => 'ombiDefaultFilterUnavailable',
							'label' => 'Show Unavailable',
							'value' => $this->config['ombiDefaultFilterUnavailable'],
							'help' => 'Show All Unavailable Ombi Requests'
						),
						array(
							'type' => 'switch',
							'name' => 'ombiDefaultFilterApproved',
							'label' => 'Show Approved',
							'value' => $this->config['ombiDefaultFilterApproved'],
							'help' => 'Show All Approved Ombi Requests'
						),
						array(
							'type' => 'switch',
							'name' => 'ombiDefaultFilterUnapproved',
							'label' => 'Show Unapproved',
							'value' => $this->config['ombiDefaultFilterUnapproved'],
							'help' => 'Show All Unapproved Ombi Requests'
						),
						array(
							'type' => 'switch',
							'name' => 'ombiDefaultFilterDenied',
							'label' => 'Show Denied',
							'value' => $this->config['ombiDefaultFilterDenied'],
							'help' => 'Show All Denied Ombi Requests'
						)
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'ombi\')"'
						),
					)
				)
			),
			array(
				'name' => 'Unifi',
				'enabled' => true,
				'image' => 'plugins/images/tabs/ubnt.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageUnifiEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageUnifiEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageUnifiAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageUnifiAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'unifiURL',
							'label' => 'URL',
							'value' => $this->config['unifiURL'],
							'help' => 'URL for Unifi',
							'placeholder' => 'Unifi API URL'
						),
						array(
							'type' => 'blank',
							'label' => ''
						),
						array(
							'type' => 'input',
							'name' => 'unifiUsername',
							'label' => 'Username',
							'value' => $this->config['unifiUsername'],
							'help' => 'Username is case-sensitive',
						),
						array(
							'type' => 'password',
							'name' => 'unifiPassword',
							'label' => 'Password',
							'value' => $this->config['unifiPassword']
						),
						array(
							'type' => 'input',
							'name' => 'unifiSiteName',
							'label' => 'Site Name (Not for UnifiOS)',
							'value' => $this->config['unifiSiteName'],
							'help' => 'Site Name - not Site ID nor Site Description',
						),
						array(
							'type' => 'button',
							'label' => 'Grab Unifi Site (Not for UnifiOS)',
							'icon' => 'fa fa-building',
							'text' => 'Get Unifi Site',
							'attr' => 'onclick="getUnifiSite()"'
						),
					),
					'Misc Options' => array(
						array(
							'type' => 'select',
							'name' => 'homepageUnifiRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageUnifiRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'unifi\')"'
						),
					)
				)
			),
			array(
				'name' => 'HealthChecks',
				'enabled' => true,
				'image' => 'plugins/images/tabs/healthchecks.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageHealthChecksEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageHealthChecksEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageHealthChecksAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageHealthChecksAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'healthChecksURL',
							'label' => 'URL',
							'value' => $this->config['healthChecksURL'],
							'help' => 'URL for HealthChecks API',
							'placeholder' => 'HealthChecks API URL'
						),
						array(
							'type' => 'password-alt',
							'name' => 'healthChecksToken',
							'label' => 'Token',
							'value' => $this->config['healthChecksToken']
						)
					),
					'Misc Options' => array(
						array(
							'type' => 'input',
							'name' => 'healthChecksTags',
							'label' => 'Tags',
							'value' => $this->config['healthChecksTags'],
							'help' => 'Pull only checks with this tag - Blank for all',
							'placeholder' => 'Multiple tags using CSV - tag1,tag2'
						),
						array(
							'type' => 'select',
							'name' => 'homepageHealthChecksRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageHealthChecksRefresh'],
							'options' => $this->optionTime()
						),
					),
				)
			),
			array(
				'name' => 'CustomHTML-1',
				'enabled' => strpos('personal,business', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/custom1.png',
				'category' => 'Custom',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageCustomHTMLoneEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageCustomHTMLoneEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageCustomHTMLoneAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageCustomHTMLoneAuth'],
							'options' => $groups
						)
					),
					'Code' => array(
						array(
							'type' => 'textbox',
							'name' => 'customHTMLone',
							'class' => 'hidden customHTMLoneTextarea',
							'label' => '',
							'value' => $this->config['customHTMLone'],
						),
						array(
							'type' => 'html',
							'override' => 12,
							'label' => 'Custom HTML/JavaScript',
							'html' => '<button type="button" class="hidden savecustomHTMLoneTextarea btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customHTMLoneEditor" style="height:300px">' . htmlentities($this->config['customHTMLone']) . '</div>'
						),
					)
				)
			),
			array(
				'name' => 'CustomHTML-2',
				'enabled' => strpos('personal,business', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/custom2.png',
				'category' => 'Custom',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageCustomHTMLtwoEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageCustomHTMLtwoEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageCustomHTMLtwoAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageCustomHTMLtwoAuth'],
							'options' => $groups
						)
					),
					'Code' => array(
						array(
							'type' => 'textbox',
							'name' => 'customHTMLtwo',
							'class' => 'hidden customHTMLtwoTextarea',
							'label' => '',
							'value' => $this->config['customHTMLtwo'],
						),
						array(
							'type' => 'html',
							'override' => 12,
							'label' => 'Custom HTML/JavaScript',
							'html' => '<button type="button" class="hidden savecustomHTMLtwoTextarea btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customHTMLtwoEditor" style="height:300px">' . htmlentities($this->config['customHTMLtwo']) . '</div>'
						),
					)
				)
			),
			array(
				'name' => 'Misc',
				'enabled' => true,
				'image' => 'plugins/images/organizr/logo-no-border.png',
				'category' => 'Custom',
				'settings' => array(
					'YouTube' => array(
						array(
							'type' => 'input',
							'name' => 'youtubeAPI',
							'label' => 'Youtube API Key',
							'value' => $this->config['youtubeAPI'],
							'help' => 'Please make sure to input this API key as the organizr one gets limited'
						),
						array(
							'type' => 'html',
							'override' => 6,
							'label' => 'Instructions',
							'html' => '<a href="https://www.slickremix.com/docs/get-api-key-for-youtube/" target="_blank">Click here for instructions</a>'
						),
					)
				)
			),
			array(
				'name' => 'Pi-hole',
				'enabled' => true,
				'image' => 'plugins/images/tabs/pihole.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepagePiholeEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepagePiholeEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepagePiholeAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepagePiholeAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'piholeURL',
							'label' => 'URL',
							'value' => $this->config['piholeURL'],
							'help' => 'Please make sure to use local IP address and port and to include \'/admin/\' at the end of the URL. You can add multiple Pi-holes by comma separating the URLs.',
							'placeholder' => 'http(s)://hostname:port/admin/'
						),
					),
					'Misc' => array(
						array(
							'type' => 'switch',
							'name' => 'piholeHeaderToggle',
							'label' => 'Toggle Title',
							'value' => $this->config['piholeHeaderToggle'],
							'help' => 'Shows/hides the title of this homepage module'
						),
						array(
							'type' => 'switch',
							'name' => 'homepagePiholeCombine',
							'label' => 'Combine stat cards',
							'value' => $this->config['homepagePiholeCombine'],
							'help' => 'This controls whether to combine the stats for multiple pihole instances into 1 card.',
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'pihole\')"'
						),
					)
				)
			),
			array(
				'name' => 'Tautulli',
				'enabled' => strpos('personal', $this->config['license']) !== false,
				'image' => 'plugins/images/tabs/tautulli.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageTautulliEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageTautulliEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageTautulliAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageTautulliAuth'],
							'options' => $groups
						)
					),
					'Options' => array(
						array(
							'type' => 'input',
							'name' => 'tautulliHeader',
							'label' => 'Title',
							'value' => $this->config['tautulliHeader'],
							'help' => 'Sets the title of this homepage module'
						),
						array(
							'type' => 'switch',
							'name' => 'tautulliHeaderToggle',
							'label' => 'Toggle Title',
							'value' => $this->config['tautulliHeaderToggle'],
							'help' => 'Shows/hides the title of this homepage module'
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'tautulliURL',
							'label' => 'URL',
							'value' => $this->config['tautulliURL'],
							'help' => 'URL for Tautulli API, include the IP, the port and the base URL (e.g. /tautulli/) in the URL',
							'placeholder' => 'http://<ip>:<port>'
						),
						array(
							'type' => 'password-alt',
							'name' => 'tautulliApikey',
							'label' => 'API Key',
							'value' => $this->config['tautulliApikey']
						),
						array(
							'type' => 'select',
							'name' => 'homepageTautulliRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageTautulliRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Library Stats' => array(
						array(
							'type' => 'switch',
							'name' => 'tautulliLibraries',
							'label' => 'Libraries',
							'value' => $this->config['tautulliLibraries'],
							'help' => 'Shows/hides the card with library information.',
						),
						array(
							'type' => 'select',
							'name' => 'homepageTautulliLibraryAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageTautulliLibraryAuth'],
							'options' => $groups
						),
					),
					'Viewing Stats' => array(
						array(
							'type' => 'switch',
							'name' => 'tautulliPopularMovies',
							'label' => 'Popular Movies',
							'value' => $this->config['tautulliPopularMovies'],
							'help' => 'Shows/hides the card with Popular Movies information.',
						),
						array(
							'type' => 'switch',
							'name' => 'tautulliPopularTV',
							'label' => 'Popular TV',
							'value' => $this->config['tautulliPopularTV'],
							'help' => 'Shows/hides the card with Popular TV information.',
						),
						array(
							'type' => 'switch',
							'name' => 'tautulliTopMovies',
							'label' => 'Top Movies',
							'value' => $this->config['tautulliTopMovies'],
							'help' => 'Shows/hides the card with Top Movies information.',
						),
						array(
							'type' => 'switch',
							'name' => 'tautulliTopTV',
							'label' => 'Top TV',
							'value' => $this->config['tautulliTopTV'],
							'help' => 'Shows/hides the card with Top TV information.',
						),
						array(
							'type' => 'select',
							'name' => 'homepageTautulliViewsAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageTautulliViewsAuth'],
							'options' => $groups
						),
					),
					'Misc Stats' => array(
						array(
							'type' => 'switch',
							'name' => 'tautulliTopUsers',
							'label' => 'Top Users',
							'value' => $this->config['tautulliTopUsers'],
							'help' => 'Shows/hides the card with Top Users information.',
						),
						array(
							'type' => 'switch',
							'name' => 'tautulliTopPlatforms',
							'label' => 'Top Platforms',
							'value' => $this->config['tautulliTopPlatforms'],
							'help' => 'Shows/hides the card with Top Platforms information.',
						),
						array(
							'type' => 'select',
							'name' => 'homepageTautulliMiscAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageTautulliMiscAuth'],
							'options' => $groups
						),
					),
					'Test Connection' => array(
						array(
							'type' => 'blank',
							'label' => 'Please Save before Testing'
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-flask',
							'class' => 'pull-right',
							'text' => 'Test Connection',
							'attr' => 'onclick="testAPIConnection(\'tautulli\')"'
						),
					)
				)
			),
			array(
				'name' => 'Monitorr',
				'enabled' => true,
				'image' => 'plugins/images/tabs/monitorr.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageMonitorrEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageMonitorrEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageMonitorrAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageMonitorrAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'monitorrURL',
							'label' => 'URL',
							'value' => $this->config['monitorrURL'],
							'help' => 'URL for Monitorr. Please use the revers proxy URL i.e. https://domain.com/monitorr/.',
							'placeholder' => 'http://domain.com/monitorr/'
						),
						array(
							'type' => 'select',
							'name' => 'homepageMonitorrRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageMonitorrRefresh'],
							'options' => $this->optionTime()
						),
					),
					'Options' => array(
						array(
							'type' => 'input',
							'name' => 'monitorrHeader',
							'label' => 'Title',
							'value' => $this->config['monitorrHeader'],
							'help' => 'Sets the title of this homepage module',
						),
						array(
							'type' => 'switch',
							'name' => 'monitorrHeaderToggle',
							'label' => 'Toggle Title',
							'value' => $this->config['monitorrHeaderToggle'],
							'help' => 'Shows/hides the title of this homepage module'
						),
						array(
							'type' => 'switch',
							'name' => 'monitorrCompact',
							'label' => 'Compact view',
							'value' => $this->config['monitorrCompact'],
							'help' => 'Toggles the compact view of this homepage module'
						),
					),
				)
			),
			array(
				'name' => 'Weather-Air',
				'enabled' => true,
				'image' => 'plugins/images/tabs/wind.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageWeatherAndAirEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageWeatherAndAirEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageWeatherAndAirAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageWeatherAndAirAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'homepageWeatherAndAirLatitude',
							'label' => 'Latitude',
							'value' => $this->config['homepageWeatherAndAirLatitude'],
							'help' => 'Please enter full latitude including minus if needed'
						),
						array(
							'type' => 'input',
							'name' => 'homepageWeatherAndAirLongitude',
							'label' => 'Longitude',
							'value' => $this->config['homepageWeatherAndAirLongitude'],
							'help' => 'Please enter full longitude including minus if needed'
						),
						array(
							'type' => 'blank',
							'label' => ''
						),
						array(
							'type' => 'button',
							'label' => '',
							'icon' => 'fa fa-search',
							'class' => 'pull-right',
							'text' => 'Need Help With Coordinates?',
							'attr' => 'onclick="showLookupCoordinatesModal()"'
						),
					),
					'Options' => array(
						array(
							'type' => 'input',
							'name' => 'homepageWeatherAndAirWeatherHeader',
							'label' => 'Title',
							'value' => $this->config['homepageWeatherAndAirWeatherHeader'],
							'help' => 'Sets the title of this homepage module',
						),
						array(
							'type' => 'switch',
							'name' => 'homepageWeatherAndAirWeatherHeaderToggle',
							'label' => 'Toggle Title',
							'value' => $this->config['homepageWeatherAndAirWeatherHeaderToggle'],
							'help' => 'Shows/hides the title of this homepage module'
						),
						array(
							'type' => 'switch',
							'name' => 'homepageWeatherAndAirWeatherEnabled',
							'label' => 'Enable Weather',
							'value' => $this->config['homepageWeatherAndAirWeatherEnabled'],
							'help' => 'Toggles the view module for Weather'
						),
						array(
							'type' => 'switch',
							'name' => 'homepageWeatherAndAirAirQualityEnabled',
							'label' => 'Enable Air Quality',
							'value' => $this->config['homepageWeatherAndAirAirQualityEnabled'],
							'help' => 'Toggles the view module for Air Quality'
						),
						array(
							'type' => 'switch',
							'name' => 'homepageWeatherAndAirPollenEnabled',
							'label' => 'Enable Pollen',
							'value' => $this->config['homepageWeatherAndAirPollenEnabled'],
							'help' => 'Toggles the view module for Pollen'
						),
						array(
							'type' => 'select',
							'name' => 'homepageWeatherAndAirUnits',
							'label' => 'Unit of Measurement',
							'value' => $this->config['homepageWeatherAndAirUnits'],
							'options' => array(
								array(
									'name' => 'Imperial',
									'value' => 'imperial'
								),
								array(
									'name' => 'Metric',
									'value' => 'metric'
								)
							)
						),
						array(
							'type' => 'select',
							'name' => 'homepageWeatherAndAirRefresh',
							'label' => 'Refresh Seconds',
							'value' => $this->config['homepageWeatherAndAirRefresh'],
							'options' => $this->optionTime()
						),
					),
				)
			),
			array(
				'name' => 'Speedtest',
				'enabled' => true,
				'image' => 'plugins/images/tabs/speedtest-icon.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'html',
							'override' => 6,
							'label' => 'Info',
							'html' => '<p>This homepage item requires <a href="https://github.com/henrywhitaker3/Speedtest-Tracker" target="_blank" rel="noreferrer noopener">Speedtest-Tracker <i class="fa fa-external-link" aria-hidden="true"></i></a> to be running on your network.</p>'
						),
						array(
							'type' => 'switch',
							'name' => 'homepageSpeedtestEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageSpeedtestEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageSpeedtestAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageSpeedtestAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'speedtestURL',
							'label' => 'URL',
							'value' => $this->config['speedtestURL'],
							'help' => 'Enter the IP:PORT of your speedtest instance e.g. http(s)://<ip>:<port>'
						),
					),
					'Options' => array(
						array(
							'type' => 'input',
							'name' => 'speedtestHeader',
							'label' => 'Title',
							'value' => $this->config['speedtestHeader'],
							'help' => 'Sets the title of this homepage module',
						),
						array(
							'type' => 'switch',
							'name' => 'speedtestHeaderToggle',
							'label' => 'Toggle Title',
							'value' => $this->config['speedtestHeaderToggle'],
							'help' => 'Shows/hides the title of this homepage module'
						),
					),
				)
			),
			$this->netdataSettingsArray(),
			array(
				'name' => 'Octoprint',
				'enabled' => true,
				'image' => 'plugins/images/tabs/octoprint.png',
				'category' => 'Monitor',
				'settings' => array(
					'Enable' => array(
						array(
							'type' => 'switch',
							'name' => 'homepageOctoprintEnabled',
							'label' => 'Enable',
							'value' => $this->config['homepageOctoprintEnabled']
						),
						array(
							'type' => 'select',
							'name' => 'homepageOctoprintAuth',
							'label' => 'Minimum Authentication',
							'value' => $this->config['homepageOctoprintAuth'],
							'options' => $groups
						)
					),
					'Connection' => array(
						array(
							'type' => 'input',
							'name' => 'octoprintURL',
							'label' => 'URL',
							'value' => $this->config['octoprintURL'],
							'help' => 'Enter the IP:PORT of your Octoprint instance e.g. http://octopi.local'
						),
						array(
							'type' => 'input',
							'name' => 'octoprintToken',
							'label' => 'API Key',
							'value' => $this->config['octoprintToken'],
							'help' => 'Enter your Octoprint API key, found in Octoprint settings page.'
						),
					),
					'Options' => array(
						array(
							'type' => 'input',
							'name' => 'octoprintHeader',
							'label' => 'Title',
							'value' => $this->config['octoprintHeader'],
							'help' => 'Sets the title of this homepage module',
						),
						array(
							'type' => 'switch',
							'name' => 'octoprintToggle',
							'label' => 'Toggle Title',
							'value' => $this->config['octoprintHeaderToggle'],
							'help' => 'Shows/hides the title of this homepage module'
						),
					),
				)
			),
		);
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
			$this->setAPIResponse('error', 'Cannot use endpoint to unlock or lock user - please use /users/{id}/lock', 409);
			return false;
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
			$new = str_ireplace('/api/v2/socks/' . $endpoint[0], '', $requestObject->getUri()->getPath());
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