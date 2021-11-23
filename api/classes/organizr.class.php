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
	use DemoFunctions;
	use HomepageConnectFunctions;
	use HomepageFunctions;
	use LogFunctions;
	use NetDataFunctions;
	use NormalFunctions;
	use OAuthFunctions;
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
	use OverseerrHomepageItem;
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
	use TraktHomepageItem;
	use TransmissionHomepageItem;
	use UnifiHomepageItem;
	use WeatherHomepageItem;
	use uTorrentHomepageItem;
    use BookmarksHomepageItem;

	// ===================================
	// Organizr Version
	public $version = '2.1.1140';
	// ===================================
	// Quick php Version check
	public $minimumPHP = '7.3';
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
	public $log;
	public $logger;
	public $organizrLog;
	public $organizrLoginLog;
	public $timeExecution;
	public $root;
	public $paths;
	public $updating;
	public $groupOptions;
	public $warnings;
	public $errors;

	public function __construct($updating = false)
	{
		// First Check PHP Version
		$this->checkPHP();
		// Check Disk Space
		$this->checkDiskSpace();
		// Set UUID for device
		$this->setDeviceUUID();
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
		// Set variable if install is for develop and set php Error levels
		$this->dev = (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Dev.txt'));
		$this->phpErrors();
		// Set variable if install is for demo
		$this->demo = (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Demo.txt'));
		// Set variable if install has commit hash
		$this->commit = ($this->docker && !$this->dev) ? file_get_contents(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Github.txt') : null;
		// Set variable to be used as hash for files
		$this->fileHash = ($this->commit) ?? $this->version;
		$this->fileHash = trim($this->fileHash);
		// Load Config file
		$this->config = $this->config();
		// Set organizr Logs and logger
		$this->log = $this->setOrganizrLog();
		$this->setLoggerChannel();
		// Set organizr Log file location - will deprecate soon
		$this->organizrLog = ($this->hasDB()) ? $this->config['dbLocation'] . 'organizrLog.json' : false;
		// Set organizr Login Log file location - will deprecate soon
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
		$this->setCurrentUser();
		// might just run this at index
		$this->upgradeCheck();
		// Is Page load Organizr OAuth?
		$this->checkForOrganizrOAuth();
		// Is user Blacklisted?
		$this->checkIfUserIsBlacklisted();
	}

	public function __destruct()
	{
		$this->disconnectDB();
	}

	protected function connectDB()
	{
		if ($this->hasDB()) {
			try {
				$connect = [
					'driver' => 'sqlite3',
					'database' => $this->config['dbLocation'] . $this->config['dbName']
				];
				$this->db = new Connection($connect);
			} catch (Dibi\Exception $e) {
				$this->db = null;
			}
		} else {
			$this->db = null;
		}
	}

	public function disconnectDB()
	{
		if ($this->hasDB()) {
			$this->db->disconnect();
			$this->db = null;
			unset($this->db);
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

	public function setDeviceUUID()
	{
		if (!isset($_COOKIE['organizr_user_uuid'])) {
			$this->coookie('set', 'organizr_user_uuid', $this->gen_uuid(), 7);
		}
	}

	public function refreshDeviceUUID()
	{
		if (isset($_COOKIE['organizr_user_uuid'])) {
			$this->coookie('delete', 'organizr_user_uuid');
		}
		$this->coookie('set', 'organizr_user_uuid', $this->gen_uuid(), 7);
	}

	public function setCurrentUser($validate = true)
	{
		$user = false;
		if ($this->hasDB()) {
			if ($this->hasCookie()) {
				$user = $this->getUserFromToken($_COOKIE[$this->cookieName]);
			}
		}
		$this->user = ($user) ?: $this->guestUser();
		$this->setLoggerChannel(null, $this->user['username']);
		if ($validate) {
			$this->checkUserTokenForValidation();
		}
	}

	public function checkUserTokenForValidation()
	{
		if ($this->hasDB()) {
			if ($this->hasCookie()) {
				$this->validateToken($_COOKIE[$this->cookieName]);
			}
		}
	}

	public function phpErrors()
	{
		$errorTypes = $this->dev ? E_ERROR | E_WARNING | E_PARSE | E_NOTICE : 0;
		// Temp overwrite for now
		$errorTypes = E_ERROR | E_WARNING | E_PARSE | E_NOTICE;
		$displayErrors = $this->dev ? 1 : 0;
		error_reporting($errorTypes);
		ini_set('display_errors', $displayErrors);
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

	public function checkIfUserIsBlacklisted()
	{
		if ($this->hasDB()) {
			$currentIP = $this->userIP();
			if ($this->config['blacklisted'] !== '') {
				if (in_array($currentIP, $this->arrayIP($this->config['blacklisted']))) {
					$this->setLoggerChannel('Authentication');
					$this->logger->debug('User was sent to black hole', ['blacklist' => $this->config['blacklisted']]);
					die($this->showHTML('Blacklisted', $this->config['blacklistedMessage']));
				}
			}
		}
	}

	public function checkDiskSpace($directory = './')
	{
		$readable = @is_readable($directory);
		if ($readable) {
			$disk = $this->checkDisk($directory);
			$diskLevels = [
				'warn' => 1000000000,
				'warn_human_readable' => $this->human_filesize(1000000000, 0),
				'error' => 100000000,
				'error_human_readable' => $this->human_filesize(100000000, 0),
			];
			if ($disk['free']['raw'] <= $diskLevels['error']) {
				die($this->showHTML('Low Disk Space', 'You are dangerously low on disk space.<br/>There is only ' . $disk['free']['human_readable'] . ' remaining.<br/><b>Percent Used = ' . $disk['used']['percent_used'] . '%</b>'));
			} elseif ($disk['free']['raw'] <= $diskLevels['warn']) {
				$this->warnings[] = 'You are low on disk space.  There is only ' . $disk['free']['human_readable'] . ' remaining.  This warning shows up because you are past the warning threshold of ' . $diskLevels['warn_human_readable'];
			}
		}
		return true;
	}

	public function getFreeSpace($directory = './')
	{
		$disk = disk_free_space($directory);
		return [
			'raw' => $disk,
			'human_readable' => $this->human_filesize($disk, 0)
		];
	}

	public function getDiskSpace($directory = './')
	{
		$disk = disk_total_space($directory);
		return [
			'raw' => $disk,
			'human_readable' => $this->human_filesize($disk, 0)
		];
	}

	public function getUsedSpace($directory = './')
	{
		$diskFree = $this->getFreeSpace($directory);
		$diskTotal = $this->getDiskSpace($directory);
		$diskUsed = $diskTotal['raw'] - $diskFree['raw'];
		$percentUsed = ($diskUsed / $diskTotal['raw']) * 100;
		$percentFree = 100 - $percentUsed;
		return [
			'raw' => $diskUsed,
			'human_readable' => $this->human_filesize($diskUsed, 0),
			'percent_used' => round($percentUsed),
			'percent_free' => round($percentFree)
		];
	}

	public function checkDisk($directory = './')
	{
		$readable = @is_readable($directory);
		if ($readable) {
			return [
				'free' => $this->getFreeSpace($directory),
				'used' => $this->getUsedSpace($directory),
				'total' => $this->getDiskSpace($directory),
			];
		} else {
			return [
				'free' => 'error accessing path',
				'used' => 'error accessing path',
				'total' => 'error accessing path',
			];
		}
	}

	public function errorCodes($error = 000)
	{
		$errorCodes = [
			400 => [
				'type' => 'Bad Request',
				'description' => 'The request was incorrect'
			],
			401 => [
				'type' => 'Unauthorized ',
				'description' => 'You are not authorized to view this page'
			],
			402 => [
				'type' => 'Payment Required',
				'description' => 'Payment required before you can view this page'
			],
			403 => [
				'type' => 'Forbidden',
				'description' => 'You are forbidden to view this page'
			],
			404 => [
				'type' => 'Not Found',
				'description' => 'The requested resource was not found'
			],
			405 => [
				'type' => 'Method Not Allowed',
				'description' => 'The requested method is not allowed'
			],
			406 => [
				'type' => 'Not Acceptable',
				'description' => 'There was an issue with the requests Headers'
			],
			407 => [
				'type' => 'Proxy Authentication Required',
				'description' => 'Authentication is required and was not passed'
			],
			408 => [
				'type' => 'Request Time-out',
				'description' => 'The request has timed out'
			],
			409 => [
				'type' => 'Conflict',
				'description' => 'An error has occurred'
			],
			410 => [
				'type' => 'Gone',
				'description' => 'The requested resource is no longer available and has been permanently removed'
			],
			411 => [
				'type' => 'Length Required',
				'description' => 'The request can not be processed without a "Content-Length" header field'
			],
			412 => [
				'type' => 'Precondition Failed',
				'description' => ' A header needed was not found'
			],
			413 => [
				'type' => 'Request Entity Too Large',
				'description' => 'The query was too large to be processed by the server'
			],
			414 => [
				'type' => 'Request-URI Too Long',
				'description' => 'The URI of the request was too long'
			],
			415 => [
				'type' => 'Unsupported Media Type',
				'description' => 'The contents of the request has been submitted with invalid or out of defined media type'
			],
			416 => [
				'type' => 'Requested range not satisfiable',
				'description' => 'The requested resource was part of an invalid or is not on the server'
			],
			417 => [
				'type' => 'Expectation Failed',
				'description' => 'Expected Header was not found'
			],
			444 => [
				'type' => 'No Response',
				'description' => 'Nothing was returned from server'
			],
			500 => [
				'type' => 'Internal Server Error',
				'description' => 'An unexpected server error'
			],
			501 => [
				'type' => 'Not Implemented',
				'description' => 'The functionality to process the request is not available from this server'
			],
			502 => [
				'type' => 'Bad Gateway',
				'description' => 'The server could not fulfill its function as a gateway or proxy'
			],
			503 => [
				'type' => 'Service Unavailable',
				'description' => 'The server is temporarily unavailable, due to overloading or maintenance'
			],
			504 => [
				'type' => 'Gateway Time-out',
				'description' => 'The server could not fulfill its function as a gateway or proxy'
			],
			505 => [
				'type' => 'HTTP version not supported',
				'description' => 'The used version of HTTP is not supported by the server or rejected'
			],
			507 => [
				'type' => 'Insufficient Storage',
				'description' => 'The request could not be processed because the server disk space it currently is not sufficient'
			],
			509 => [
				'type' => 'Bandwidth Limit Exceeded',
				'description' => 'The request was rejected, because otherwise the bandwidth would be exceeded'
			],
			510 => [
				'type' => 'Not Extended',
				'description' => 'The request does not contain all information that is waiting for the requested server extension imperative'
			],
			000 => [
				'type' => 'Unexpected Error',
				'description' => 'An unexpected error occurred'
			],
		];
		return (isset($errorCodes[$error])) ? $errorCodes[$error] : $errorCodes[000];
	}

	public function showTopBarHamburger()
	{
		if ($this->config['allowCollapsableSideMenu']) {
			if ($this->config['sideMenuCollapsed']) {
				return '<a class="toggle-side-menu" href="javascript:void(0)"><i class="ti-menu fa-fw"></i></a>';
			} else {
				return '<a class="toggle-side-menu hidden" href="javascript:void(0)"><i class="ti-menu fa-fw"></i></a>';
			}
		}
		return '';
	}

	public function showSideBarHamburger()
	{
		if ($this->config['allowCollapsableSideMenu']) {
			if (!$this->config['sideMenuCollapsed']) {
				return '<i class="hidden-xs ti-shift-left mouse"></i>';
			}
		}
		return '<i class="ti-menu hidden-xs"></i>';
	}

	public function showSideBarText()
	{
		if ($this->config['allowCollapsableSideMenu']) {
			if (!$this->config['sideMenuCollapsed']) {
				return '<span class="hide-menu hidden-xs" lang="en">Hide Menu</span>';
			}
		}
		return '<span class="hide-menu hidden-xs" lang="en">Navigation</span>';
	}

	public function auth()
	{
		if ($this->hasDB()) {
			$this->setLoggerChannel('Auth');
			if (isset($_GET['type'])) {
				switch (strtolower($_GET['type'])) {
					case 'whitelist':
					case 'white':
					case 'w':
					case 'wl':
					case 'allow':
						$_GET['whitelist'] = $_GET['ips'] ?? false;
						break;
					case 'blacklist':
					case 'black':
					case 'b':
					case 'bl':
					case 'deny':
						$_GET['blacklist'] = $_GET['ips'] ?? false;
						break;
					default:
						$this->setAPIResponse('error', $_GET['type'] . ' is not a valid type', 401);
						return true;
				}
			}
			$whitelist = $_GET['whitelist'] ?? false;
			$blacklist = $_GET['blacklist'] ?? false;
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
			$unlocked = !($this->user['locked'] == '1');
			if (isset($this->user)) {
				$currentUser = $this->user['username'];
				$currentGroup = $this->user['groupID'];
				$currentEmail = $this->user['email'];
			} else {
				$currentUser = 'Guest';
				$currentGroup = $this->getUserLevel();
				$currentEmail = 'guest@guest.com';
			}
			$userInfo = [
				"user" => $currentUser,
				"group" => $currentGroup,
				"email" => $currentEmail,
				"user_ip" => $currentIP,
				"requested_group" => $group,
				"uuid" => $_COOKIE['organizr_user_uuid'] ?? 'n/a'
			];
			$this->logger->debug('Starting check', $userInfo);
			$responseMessage = 'User is not Authorized or User is locked';
			if ($whitelist) {
				if (in_array($currentIP, $this->arrayIP($whitelist))) {
					$responseMessage = 'User is whitelisted';
					$this->setAPIResponse('success', $responseMessage, 200, $userInfo);
					$this->logger->debug($responseMessage, $userInfo);
					return true;
				}
			}
			if ($blacklist) {
				if (in_array($currentIP, $this->arrayIP($blacklist))) {
					$responseMessage = 'User is blacklisted';
					$this->setAPIResponse('error', $responseMessage, 401, $userInfo);
					$this->logger->debug($responseMessage, $userInfo);
					return true;
				}
			}
			if ($group !== null) {
				if ((isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'traefik') || $this->config['traefikAuthEnable']) {
					$return = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && isset($_SERVER['HTTP_X_FORWARDED_URI']) && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) ? '?return=' . $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_X_FORWARDED_HOST'] . $_SERVER['HTTP_X_FORWARDED_URI'] : '';
					$redirectDomain = ($this->config['traefikDomainOverride'] !== '') ? $this->config['traefikDomainOverride'] : $this->getServerPath();
					$redirect = 'Location: ' . $redirectDomain . $return;
				}
				if ($this->qualifyRequest($group) && $unlocked) {
					header("X-Organizr-User: $currentUser");
					header("X-Organizr-Email: $currentEmail");
					header("X-Organizr-Group: $currentGroup");
					$responseMessage = 'User is authorized';
					$this->setAPIResponse('success', $responseMessage, 200, $userInfo);
					$this->logger->debug($responseMessage, $userInfo);
				} else {
					if (!$redirect) {
						$this->setAPIResponse('error', $responseMessage, 401, $userInfo);
						$this->logger->debug($responseMessage, $userInfo);
					} else {
						exit(http_response_code(401) . header($redirect));
					}
				}
			} else {
				$this->setAPIResponse('error', 'Missing info', 401);
				$this->logger->debug('Missing info', $userInfo);
			}
			return true;
		} else {
			$this->setAPIResponse('error', 'Organizr is not setup or an error occurred', 401);
			return false;
		}
	}

	public function getIpInfo($ip = null)
	{
		if (!$ip) {
			$this->setResponse(422, 'No IP Address supplied');
			return false;
		}
		try {
			$options = array('verify' => false);
			$response = Requests::get('https://ipinfo.io/' . $ip . '/?token=ddd0c072ad5021', array(), $options);
			if ($response->success) {
				$api = json_decode($response->body, true);
				$this->setResponse(200, null, $api);
				return true;
			} else {
				$this->setResponse(500, 'An error occurred', null);
			}
		} catch (Requests_Exception $e) {
			$this->setResponse(500, 'An error occurred', $e->getMessage());
		}
		return false;
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

	public function setResponse(int $responseCode = 200, string $message = null, $data = null)
	{
		switch ($responseCode) {
			case 200:
			case 201:
			case 204:
				$result = 'success';
				break;
			default:
				$result = 'error';
				break;
		}
		$GLOBALS['api']['response']['result'] = $result;
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
				if (stripos($request->getHeaderLine('Content-Type'), 'application/json') !== false) {
					return json_decode(file_get_contents('php://input', 'r'), true);
				} else {
					return $request->getParsedBody();
				}
			default:
				if (stripos($request->getHeaderLine('Content-Type'), 'application/json') !== false) {
					return json_decode(file_get_contents('php://input', 'r'), true);
				} else {
					return null;
				}
		}
	}

	public function getPlugins($returnType = 'all')
	{
		if ($this->hasDB()) {
			switch ($returnType) {
				case 'enabled':
					$returnType = 'enabled';
					break;
				case 'disabled':
					$returnType = 'disabled';
					break;
				default:
					$returnType = 'all';
			}
			$pluginList = [];
			foreach ($GLOBALS['plugins'] as $key => $value) {
				if (strpos($value['license'], $this->config['license']) !== false) {
					$GLOBALS['plugins'][$key]['enabled'] = $this->config[$value['configPrefix'] . '-enabled'];
					if ($returnType == 'all') {
						$pluginList[$key] = $GLOBALS['plugins'][$key];
					} elseif ($returnType == 'enabled' && $this->config[$value['configPrefix'] . '-enabled'] == true) {
						$pluginList[$key] = $GLOBALS['plugins'][$key];
					} elseif ($returnType == 'disabled' && $this->config[$value['configPrefix'] . '-enabled'] == false) {
						$pluginList[$key] = $GLOBALS['plugins'][$key];
					}
				}
			}
			asort($pluginList);
			return $pluginList;
		}
		return false;
	}

	public function refreshCookieName()
	{
		$this->cookieName = $this->config['uuid'] !== '' ? 'organizr_token_' . $this->config['uuid'] : 'organizr_token_temp';
	}

	public function favIcons($rootPath = '')
	{
		$favicon = '
			<link rel="apple-touch-icon" sizes="180x180" href="' . $rootPath . 'plugins/images/favicon/apple-touch-icon.png">
			<link rel="icon" type="image/png" sizes="32x32" href="' . $rootPath . 'plugins/images/favicon/favicon-32x32.png">
			<link rel="icon" type="image/png" sizes="16x16" href="' . $rootPath . 'plugins/images/favicon/favicon-16x16.png">
			<link rel="manifest" href="' . $rootPath . 'plugins/images/favicon/site.webmanifest" crossorigin="use-credentials">
			<link rel="mask-icon" href="' . $rootPath . 'plugins/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
			<link rel="shortcut icon" href="' . $rootPath . 'plugins/images/favicon/favicon.ico">
			<meta name="msapplication-TileColor" content="#da532c">
			<meta name="msapplication-TileImage" content="' . $rootPath . 'plugins/images/favicon/mstile-144x144.png">
			<meta name="msapplication-config" content="' . $rootPath . 'plugins/images/favicon/browserconfig.xml">
			<meta name="theme-color" content="#ffffff">
		';
		if ($this->config['favIcon'] !== '' && $rootPath !== '') {
			$this->config['favIcon'] = str_replace('plugins/images/faviconCustom', $rootPath . 'plugins/images/faviconCustom', $this->config['favIcon']);
		}
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
				<script src="https://apis.google.com/js/client.js?onload=googleApiClientReady"></script>
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

	public function getRootPath()
	{
		$count = (count(explode('/', $_SERVER['REQUEST_URI']))) - 2;
		$rootPath = '';
		$rootPath .= str_repeat('../', $count);
		return $rootPath;
	}

	public function setTheme($theme = null, $rootPath = '')
	{
		$theme = $theme ?? $this->config['theme'];
		return '<link id="theme" href="' . $rootPath . 'css/themes/' . $theme . '.css?v=' . $this->fileHash . '" rel="stylesheet">';
	}

	public function pluginFiles($type, $settings = false, $rootPath = '')
	{
		$files = '';
		$folder = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins';
		$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
		$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
		switch ($type) {
			case 'js':
				foreach ($iteratorIterator as $info) {
					if (pathinfo($info->getPathname(), PATHINFO_EXTENSION) == 'js') {
						$pluginEnabled = false;
						$keyOriginal = strtoupper(basename(dirname($info->getPathname())));
						$key = str_replace('-SETTINGS', '', $keyOriginal);
						$continue = false;
						if ($settings) {
							if ($info->getFilename() == 'settings.js') {
								$continue = true;
							}
						} else {
							if ($info->getFilename() !== 'settings.js') {
								$continue = true;
							}
						}
						switch ($key) {
							case 'PHP-MAILER':
								$key = 'PHPMAILER';
								break;
							case 'NGXC':
								$key = 'ngxc';
								break;
							default:
								$key = $key;
						}
						if (isset($this->config[$key . '-enabled'])) {
							if ($this->config[$key . '-enabled']) {
								$pluginEnabled = true;
							}
						}
						if ($pluginEnabled || $settings) {
							if ($continue) {
								$files .= '<script src="' . $rootPath . 'api/plugins/' . basename(dirname($info->getPathname())) . '/' . basename($info->getFilename()) . '?v=' . $this->fileHash . '" defer="true"></script>';
							}
						}
					}
				}
				break;
			case 'css':
				foreach ($iteratorIterator as $info) {
					if (pathinfo($info->getPathname(), PATHINFO_EXTENSION) == 'css') {
						$files .= '<link href="' . $rootPath . 'api/plugins/' . basename(dirname($info->getPathname())) . '/' . basename($info->getFilename()) . '?v=' . $this->fileHash . '" rel="stylesheet">';
					}
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
			die($this->showHTML('PHP Version', 'Organizr needs PHP Version: ' . $this->minimumPHP . '<br/> You have PHP Version: ' . PHP_VERSION));
		}
	}

	private function checkWritableDB()
	{
		if ($this->hasDB()) {
			if (isset($this->config['dbLocation']) && isset($this->config['dbName'])) {
				$db = is_writable($this->config['dbLocation'] . $this->config['dbName']);
				if (!$db) {
					die($this->showHTML('Organizr DB is not writable!', 'Please check permissions and/or disk space'));
				}
			} else {
				die($this->showHTML('Config File Malformed', 'dbLocation and/or dbName is not listed in config.php'));
			}
		}
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
		$output = (!$nest ? "<?php\nreturn " : '') . "[\n" . implode(",\n", $output) . "\n" . str_repeat("\t", $nest) . ']' . (!$nest ? ';' : '');
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
			$this->config[$k] = $v;
		}
		// Return Create
		return $this->createConfig($current);
	}

	public function removeConfigItem($new, $current = false)
	{
		// Get config if not supplied
		if ($current === false) {
			$current = $this->config;
		} elseif (is_string($current) && is_file($current)) {
			$current = $this->loadConfig($current);
		}
		// Inject Parts
		foreach ($new as $k) {
			if (isset($current[$k])) {
				$current['deletedConfigItems'][$k] = $current[$k];
				$this->config['deletedConfigItems'][$k] = $current[$k];
			}
			unset($current[$k]);
			unset($this->config[$k]);
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
		$folder = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins';
		$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
		$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
		foreach ($iteratorIterator as $info) {
			if ($info->getFilename() == 'config.php') {
				$loadedDefaults = array_merge($loadedDefaults, $this->loadConfig($info->getPathname()));
			}
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

	public function config($tries = 1)
	{
		// Load config or default
		if (file_exists($this->userConfigPath)) {
			$config = $this->fillDefaultConfig($this->loadConfig($this->userConfigPath));
		} else {
			$config = $this->fillDefaultConfig($this->loadConfig($this->defaultConfigPath));
		}
		if ((!is_array($config) || !file_exists($this->userConfigPath)) && $tries < 5) {
			$tries++;
			return $this->config($tries);
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
		$status['php_user'] = get_current_user();
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

	protected function invalidToken($token)
	{
		if (isset($_COOKIE[$this->cookieName])) {
			if ($token == $_COOKIE[$this->cookieName]) {
				$this->setLoggerChannel('Authentication');
				$this->logger->debug('Token was invalid - deleting cookie and user session');
				$this->coookie('delete', $this->cookieName);
				$this->user = null;
			}
		}
	}

	public function validateToken($token, $api = false)
	{
		// Validate script
		$userInfo = $this->jwtParse($token);
		$validated = (bool)$userInfo;
		if ($validated == true) {
			$allTokens = $this->getAllUserTokens($userInfo['userID']);
			$user = $this->getUserById($userInfo['userID']);
			$tokenCheck = ($this->searchArray($allTokens, 'token', $token) !== false);
			if (!$tokenCheck) {
				$this->setLoggerChannel('Authentication');
				$this->logger->debug('Token failed check against all token listings', $allTokens);
				$this->invalidToken($token);
				if ($api) {
					$this->setResponse(403, 'Token was not in approved list');
				}
				return false;
			} else {
				if ($api) {
					$this->setResponse(200, 'Token is valid');
				}
				return array(
					'token' => $token,
					'tokenDate' => $userInfo['tokenDate'],
					'tokenExpire' => $userInfo['tokenExpire'],
					'username' => $user['username'] ?? $userInfo['username'],
					'uid' => $this->guestHash(0, 5),
					'group' => $user['group'] ?? $userInfo['group'],
					'groupID' => $user['group_id'] ?? $userInfo['groupID'],
					'email' => $user['email'] ?? $userInfo['email'],
					'image' => $user['image'] ?? $userInfo['image'],
					'userID' => $user['id'] ?? $userInfo['userID'],
					'loggedin' => true,
					'locked' => $user['locked'] ?? 0,
					'tokenList' => $allTokens,
					'authService' => (isset($user['auth_service'])) ? explode('::', $user['auth_service'])[0] : 'internal'
				);
			}
		} else {
			if ($api) {
				$this->setResponse(403, 'Token was invalid');
			}
			$this->setLoggerChannel('Authentication');
			$this->logger->debug('User  token was invalid', ['token' => $token]);
			$this->invalidToken($token);
		}
		if ($api) {
			$this->setResponse(403, 'Token was invalid');
		}
		return false;
	}

	public function getUserFromToken($token)
	{
		// Validate script
		$userInfo = $this->jwtParse($token);
		$validated = (bool)$userInfo;
		if ($validated == true) {
			$user = $this->getUserById($userInfo['userID']);
			$allTokens = $this->getAllUserTokens($userInfo['userID']);
			return array(
				'token' => $token,
				'tokenDate' => $userInfo['tokenDate'],
				'tokenExpire' => $userInfo['tokenExpire'],
				'username' => $user['username'] ?? $userInfo['username'],
				'uid' => $this->guestHash(0, 5),
				'group' => $user['group'] ?? $userInfo['group'],
				'groupID' => $user['group_id'] ?? $userInfo['groupID'],
				'email' => $user['email'] ?? $userInfo['email'],
				'image' => $user['image'] ?? $userInfo['image'],
				'userID' => $user['id'] ?? $userInfo['userID'],
				'loggedin' => true,
				'locked' => $user['locked'] ?? 0,
				'tokenList' => $allTokens,
				'authService' => (isset($user['auth_service'])) ? explode('::', $user['auth_service'])[0] : 'internal'
			);
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
		$query = $this->processQueries($response);
		$this->applyTabVariables($query['tabs']);
		return $query;
	}

	public function applyTabVariables($tabs)
	{
		$variables = [
			'{domain}' => $this->getServer(),
			'{username}' => $this->user['username'],
			'{username_lower}' => $this->user['username'],
			'{email}' => $this->user['email'],
			'{group}' => $this->user['group'],
			'{group_id}' => $this->user['groupID'],
			'{komga}' => $_COOKIE['komga_token'] ?? ''
		];
		if (empty($tabs)) {
			return $tabs;
		}
		foreach ($tabs as $id => $tab) {
			$tabs[$id]['url'] = $this->userDefinedIdReplacementLink($tab['url'], $variables);
			$tabs[$id]['url_local'] = $this->userDefinedIdReplacementLink($tab['url_local'], $variables);
		}
		return $tabs;
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
		$requesterToken = $this->getallheaders()['Token'] ?? ($_GET['apikey'] ?? false);
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
		if ($this->approvedFileExtension($removeImage, 'image')) {
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
		if (!empty($filesCheck) && $this->approvedFileExtension($_FILES['file']['name'], 'image') && strpos($_FILES['file']['type'], 'image/') !== false) {
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

	public function getPluginSettings()
	{
		return [
			'Marketplace' => [
				$this->settingsOption('notice', null, ['notice' => 'danger', 'body' => '3rd Party Repositories are not affiliated with Organizr and therefore the code on these repositories are not inspected.  Use at your own risk.']),
				$this->settingsOption('multiple-url', 'externalPluginMarketplaceRepos', ['override' => 12, 'label' => 'External Marketplace Repo', 'help' => 'Only supports Github repos']),
				$this->settingsOption('token', 'githubAccessToken', ['label' => 'Github Person Access Token', 'help' => 'The Github Person Access Token will help with API rate limiting as well as let you access your own Private Repos']),
				$this->settingsOption('switch', 'checkForPluginUpdate', ['label' => 'Check for Plugin Updates', ['help' => 'Check for updates on page load']])
			]
		];
	}

	public function getCustomizeAppearance()
	{
		return [
			'Top Bar' => [
				$this->settingsOption('input', 'logo', ['label' => 'Logo URL']),
				$this->settingsOption('input', 'title', ['label' => 'Organizr Title']),
				$this->settingsOption('switch', 'useLogo', ['label' => 'Use Logo instead of Title', 'help' => 'Also sets the title of your site']),
				$this->settingsOption('input', 'description', ['label' => 'Meta Description', 'help' => 'Used to set the description for SEO meta tags']),
			],
			'Side Menu' => [
				$this->settingsOption('switch', 'allowCollapsableSideMenu', ['label' => 'Allow Side Menu to be Collapsable']),
				$this->settingsOption('switch', 'sideMenuCollapsed', ['label' => 'Side Menu Collapsed at Launch']),
				$this->settingsOption('switch', 'collapseSideMenuOnClick', ['label' => 'Collapse Side Menu after clicking Tab']),
				$this->settingsOption('switch', 'githubMenuLink', ['label' => 'Show GitHub Repo Link']),
				$this->settingsOption('switch', 'organizrFeatureRequestLink', ['label' => 'Show Organizr Feature Request Link']),
				$this->settingsOption('switch', 'organizrSupportMenuLink', ['label' => 'Show Organizr Support Link']),
				$this->settingsOption('switch', 'organizrDocsMenuLink', ['label' => 'Show Organizr Docs Link']),
				$this->settingsOption('switch', 'organizrSignoutMenuLink', ['label' => 'Show Organizr Sign out & in Button on Sidebar']),
				$this->settingsOption('switch', 'expandCategoriesByDefault', ['label' => 'Expand All Categories']),
				$this->settingsOption('switch', 'autoCollapseCategories', ['label' => 'Auto-Collapse Categories']),
				$this->settingsOption('switch', 'autoExpandNavBar', ['label' => 'Auto-Expand Nav Bar']),
				$this->settingsOption('select', 'unsortedTabs', ['label' => 'Unsorted Tab Placement', 'options' => [['name' => 'Top', 'value' => 'top'], ['name' => 'Bottom', 'value' => 'bottom']]]),
			],
			'Login Page' => [
				$this->settingsOption('input', 'loginLogo', ['label' => 'Login Logo URL']),
				$this->settingsOption('multiple-url', 'loginWallpaper', ['label' => 'Login Wallpaper URL', 'help' => 'You may enter multiple URL\'s']),
				$this->settingsOption('switch', 'useLogoLogin', ['label' => 'Use Logo instead of Title on Login Page']),
				$this->settingsOption('switch', 'minimalLoginScreen', ['label' => 'Minimal Login Screen']),
			],
			'Options' => [
				$this->settingsOption('switch', 'alternateHomepageHeaders', ['label' => 'Alternate Homepage Titles']),
				$this->settingsOption('switch', 'debugErrors', ['label' => 'Show Debug Errors']),
				$this->settingsOption('switch', 'easterEggs', ['label' => 'Show Easter Eggs']),
				$this->settingsOption('input', 'gaTrackingID', ['label' => 'Google Analytics Tracking ID', 'placeholder' => 'e.g. UA-XXXXXXXXX-X']),
			],
			'Colors & Themes' => [
				$this->settingsOption('notice', null, ['notice' => 'info', 'title' => 'Attention', 'bodyHTML' => '<span lang="en">The value of #987654 is just a placeholder, you can change to any value you like.</span><span lang="en">To revert back to default, save with no value defined in the relevant field.</span>']),
				$this->settingsOption('blank'),
				$this->settingsOption('button', '', ['label' => 'Reset Colors', 'icon' => 'fa fa-ticket', 'text' => 'Reset', 'attr' => 'onclick="resetCustomColors()"']),
				$this->settingsOption('blank'),
				$this->settingsOption('color', 'headerColor', ['label' => 'Nav Bar Color']),
				$this->settingsOption('color', 'headerTextColor', ['label' => 'Nav Bar Text Color']),
				$this->settingsOption('color', 'sidebarColor', ['label' => 'Side Bar Color']),
				$this->settingsOption('color', 'sidebarTextColor', ['label' => 'Side Bar Text Color']),
				$this->settingsOption('color', 'accentColor', ['label' => 'Accent Color']),
				$this->settingsOption('color', 'accentTextColor', ['label' => 'Accent Text Color']),
				$this->settingsOption('color', 'buttonColor', ['label' => 'Button Color']),
				$this->settingsOption('color', 'buttonTextColor', ['label' => 'Button Text Color']),
				$this->settingsOption('select', 'theme', ['label' => 'Theme', 'class' => 'themeChanger', 'options' => $this->getThemes()]),
				$this->settingsOption('select', 'style', ['label' => 'Style', 'class' => 'styleChanger', 'options' => [['name' => 'Light', 'value' => 'light'], ['name' => 'Dark', 'value' => 'dark'], ['name' => 'Horizontal', 'value' => 'horizontal']]]),
			],
			'Notifications' => [
				$this->settingsOption('select', 'notificationBackbone', ['label' => 'Type', 'class' => 'notifyChanger', 'options' => $this->notificationTypesOptions()]),
				$this->settingsOption('select', 'notificationPosition', ['label' => 'Position', 'class' => 'notifyPositionChanger', 'options' => $this->notificationPositionsOptions()]),
				$this->settingsOption('html', null, ['label' => 'Test Message', 'html' => '
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
					']
				),
			],
			'FavIcon' => [
				$this->settingsOption('html', null, ['label' => 'Instructions', 'override' => 12, 'html' => '
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
					']
				),
				$this->settingsOption('code-editor', 'favIcon', ['label' => 'Fav Icon Code', 'mode' => 'html']),
			],
			'Custom CSS' => [
				$this->settingsOption('code-editor', 'customCss', ['label' => 'Custom CSS', 'mode' => 'css']),
			],
			'Theme CSS' => [
				$this->settingsOption('code-editor', 'customThemeCss', ['label' => 'Theme CSS', 'mode' => 'css']),
			],
			'Custom Javascript' => [
				$this->settingsOption('code-editor', 'customJava', ['label' => 'Custom Javascript', 'mode' => 'javascript']),
			],
			'Theme Javascript' => [
				$this->settingsOption('code-editor', 'customThemeJava', ['label' => 'Theme Javascript', 'mode' => 'javascript']),
			],
		];
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
		$certificateStatus = $this->hasCustomCert() ? '<span lang="en">Custom Certificate Loaded</span><br />Located at <span>' . $this->getCustomCert() . '</span>' : '<span lang="en">Custom Certificate not found - please upload below</span>';
		return [
			'Settings Page' => [
				$this->settingsOption('select', 'defaultSettingsTab', ['label' => 'Default Settings Tab', 'options' => $this->getSettingsTabs(), 'help' => 'Choose which Settings Tab to be default when opening settings page']),
			],
			'Database' => [
				$this->settingsOption('notice', '', ['notice' => 'danger', 'title' => 'Warning', 'body' => 'This feature is experimental - You may face unexpected database is locked errors in logs']),
				$this->settingsOption('html', '', ['label' => 'Journal Mode Status', 'html' => '<script>getJournalMode();</script><h4 class="journal-mode font-bold text-uppercase"><i class="fa fa-spin fa-circle-o-notch"></i></h4>']),
				$this->settingsOption('button', '', ['label' => 'Set DELETE Mode (Default)', 'icon' => 'icon-notebook', 'text' => 'Set', 'attr' => 'onclick="setJournalMode(\'DELETE\')"']),
				$this->settingsOption('button', '', ['label' => 'Set WAL Mode', 'icon' => 'icon-notebook', 'text' => 'Set', 'attr' => 'onclick="setJournalMode(\'WAL\')"']),
			],
			'Github' => [
				$this->settingsOption('select', 'branch', ['label' => 'Branch', 'value' => $this->config['branch'], 'options' => $this->getBranches(), 'disabled' => $this->docker, 'help' => ($this->docker) ? 'Since you are using the Official Docker image, Change the image to change the branch' : 'Choose which branch to download from']),
				$this->settingsOption('button', 'force-install-branch', ['label' => 'Force Install Branch', 'class' => 'updateNow', 'icon' => 'fa fa-download', 'text' => 'Retrieve', 'attr' => ($this->docker) ? 'title="You can just restart your docker to update"' : '', 'help' => ($this->docker) ? 'Since you are using the official Docker image, you can just restart your Docker container to update Organizr' : 'This will re-download all of the source files for Organizr']),
			],
			'API' => [
				$this->settingsOption('password-alt-copy', 'organizrAPI', ['label' => 'Organizr API']),
				$this->settingsOption('button', null, ['label' => 'Generate New API Key', 'class' => 'newAPIKey', 'icon' => 'fa fa-refresh', 'text' => 'Generate']),
				$this->settingsOption('notice', null, ['title' => 'API Documentation', 'body' => 'The documentation for Organizr\'s API is included with this installation.  To access the docs, use the button below.', 'bodyHTML' => '<br/><br/><div class="row"><div class="col-lg-2 col-sm-4 col-xs-12"><a href="' . $this->getServerPath() . 'docs/" target="_blank" class="btn btn-block btn-primary text-white" lang="en">Organizr Docs</a></div></div>'])
			],
			'Authentication' => [
				$this->settingsOption('select', 'authType', ['id' => 'authSelect', 'label' => 'Authentication Type', 'value' => $this->config['authType'], 'options' => $this->getAuthTypes()]),
				$this->settingsOption('select', 'authBackend', ['id' => 'authBackendSelect', 'label' => 'Authentication Backend', 'class' => 'backendAuth switchAuth', 'value' => $this->config['authBackend'], 'options' => $this->getAuthBackends()]),
				$this->settingsOption('token', 'plexToken', ['class' => 'plexAuth switchAuth']),
				$this->settingsOption('button', '', ['class' => 'getPlexTokenAuth plexAuth switchAuth', 'label' => 'Get Plex Token', 'icon' => 'fa fa-ticket', 'text' => 'Retrieve', 'attr' => 'onclick="PlexOAuth(oAuthSuccess,oAuthError, null, \'#settings-main-form [name=plexToken]\')"']),
				$this->settingsOption('password-alt', 'plexID', ['class' => 'plexAuth switchAuth', 'label' => 'Plex Machine', 'placeholder' => 'Use Get Plex Machine Button']),
				$this->settingsOption('button', '', ['class' => 'getPlexMachineAuth plexAuth switchAuth', 'label' => 'Get Plex Machine', 'icon' => 'fa fa-id-badge', 'text' => 'Retrieve', 'attr' => 'onclick="showPlexMachineForm(\'#settings-main-form [name=plexID]\')"']),
				$this->settingsOption('input', 'plexAdmin', ['label' => 'Plex Admin Username or Email', 'class' => 'plexAuth switchAuth', 'placeholder' => 'Admin username for Plex']),
				$this->settingsOption('switch', 'plexoAuth', ['label' => 'Enable Plex oAuth', 'class' => 'plexAuth switchAuth']),
				$this->settingsOption('switch', 'ignoreTFAIfPlexOAuth', ['label' => 'Ignore 2FA if Plex OAuth ', 'class' => 'plexAuth switchAuth', 'help' => 'Enabling this will disable Organizr 2FA (If applicable) if User uses Plex OAuth to login']),
				$this->settingsOption('switch', 'plexStrictFriends', ['label' => 'Strict Plex Friends ', 'class' => 'plexAuth switchAuth', 'help' => 'Enabling this will only allow Friends that have shares to the Machine ID entered above to login, Having this disabled will allow all Friends on your Friends list to login']),
				$this->settingsOption('switch', 'ignoreTFALocal', ['label' => 'Ignore External 2FA on Local Subnet', 'help' => 'Enabling this will bypass external 2FA security if user is on local Subnet']),
				$this->settingsOption('url', 'authBackendHost', ['class' => 'ldapAuth ftpAuth switchAuth', 'label' => 'Host Address', 'placeholder' => 'http(s) | ftp(s) | ldap(s)://hostname:port']),
				$this->settingsOption('input', 'authBaseDN', ['class' => 'ldapAuth switchAuth', 'label' => 'Host Base DN', 'placeholder' => 'cn=%s,dc=sub,dc=domain,dc=com']),
				$this->settingsOption('input', 'authBackendHostPrefix', ['class' => 'ldapAuth switchAuth', 'label' => 'Account Prefix', 'id' => 'authBackendHostPrefix-input', 'placeholder' => 'Account prefix - i.e. Controller\ from Controller\Username for AD - uid= for OpenLDAP']),
				$this->settingsOption('input', 'authBackendHostSuffix', ['class' => 'ldapAuth switchAuth', 'label' => 'Account Suffix', 'id' => 'authBackendHostSuffix-input', 'placeholder' => 'Account suffix - start with comma - ,ou=people,dc=domain,dc=tld']),
				$this->settingsOption('input', 'ldapBindUsername', ['class' => 'ldapAuth switchAuth', 'label' => 'Bind Username']),
				$this->settingsOption('password', 'ldapBindPassword', ['class' => 'ldapAuth switchAuth', 'label' => 'Bind Password']),
				$this->settingsOption('select', 'ldapType', ['id' => 'ldapType', 'label' => 'LDAP Backend Type', 'class' => 'ldapAuth switchAuth', 'options' => $this->getLDAPOptions()]),
				$this->settingsOption('html', null, ['class' => 'ldapAuth switchAuth', 'label' => 'Account DN', 'html' => '<span id="accountDN" class="ldapAuth switchAuth">' . $this->config['authBackendHostPrefix'] . 'TestAcct' . $this->config['authBackendHostSuffix'] . '</span>']),
				$this->settingsOption('blank', null, ['class' => 'ldapAuth switchAuth']),
				$this->settingsOption('switch', 'ldapSSL', ['class' => 'ldapAuth switchAuth', 'label' => 'Enable LDAP SSL', 'help' => 'This will enable the use of SSL for LDAP connections']),
				$this->settingsOption('switch', 'ldapSSL', ['class' => 'ldapAuth switchAuth', 'label' => 'Enable LDAP TLS', 'help' => 'This will enable the use of TLS for LDAP connections']),
				$this->settingsOption('test', 'ldap', ['class' => 'ldapAuth switchAuth']),
				$this->settingsOption('test', '', ['label' => 'Test Login', 'class' => 'ldapAuth switchAuth', 'text' => 'Test Login', 'attr' => 'onclick="showLDAPLoginTest()"']),
				$this->settingsOption('url', 'embyURL', ['class' => 'embyAuth switchAuth', 'label' => 'Emby URL', 'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.']),
				$this->settingsOption('token', 'embyToken', ['class' => 'embyAuth switchAuth', 'label' => 'Emby Token']),
				$this->settingsOption('url', 'jellyfinURL', ['class' => 'jellyfinAuth switchAuth', 'label' => 'Jellyfin URL', 'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.']),
				$this->settingsOption('token', 'jellyfinToken', ['class' => 'jellyfinAuth switchAuth', 'label' => 'Jellyfin Token']),
			],
			'Security' => [
				$this->settingsOption('number', 'loginAttempts', ['label' => 'Max Login Attempts']),
				$this->settingsOption('select', 'loginLockout', ['label' => 'Login Lockout Seconds', 'options' => $this->timeOptions()]),
				$this->settingsOption('number', 'lockoutTimeout', ['label' => 'Inactivity Timer [Minutes]']),
				$this->settingsOption('switch', 'lockoutSystem', ['label' => 'Inactivity Lock']),
				$this->settingsOption('select', 'lockoutMinAuth', ['label' => 'Lockout Groups From', 'options' => $this->groupSelect()]),
				$this->settingsOption('select', 'lockoutMaxAuth', ['label' => 'Lockout Groups To', 'options' => $this->groupSelect()]),
				$this->settingsOption('switch', 'traefikAuthEnable', ['label' => 'Enable Traefik Auth Redirect', 'help' => 'This will enable the webserver to forward errors so traefik will accept them']),
				$this->settingsOption('input', 'traefikDomainOverride', ['label' => 'Traefik Domain for Return Override', 'help' => 'Please use a FQDN on this URL Override', 'placeholder' => 'http(s)://domain']),
				$this->settingsOption('select', 'debugAreaAuth', ['label' => 'Minimum Authentication for Debug Area', 'options' => $this->groupSelect(), 'settings' => '{}']),
				$this->settingsOption('multiple', 'sandbox', ['override' => 12, 'label' => 'iFrame Sandbox', 'help' => 'WARNING! This can potentially mess up your iFrames', 'options' => $this->sandboxOptions()]),
				$this->settingsOption('multiple', 'blacklisted', ['override' => 12, 'label' => 'Blacklisted IP\'s', 'help' => 'WARNING! This will block anyone with these IP\'s', 'options' => $this->makeOptionsFromValues($this->config['blacklisted']), 'settings' => '{tags: true}']),
				$this->settingsOption('code-editor', 'blacklistedMessage', ['mode' => 'html']),
			],
			'Logs' => [
				$this->settingsOption('select', 'logLevel', ['label' => 'Log Level', 'options' => $this->logLevels()]),
				$this->settingsOption('switch', 'includeDatabaseQueriesInDebug', ['label' => 'Include Database Queries', 'help' => 'Include Database queries in debug logs']),
				$this->settingsOption('number', 'maxLogFiles', ['label' => 'Maximum Log Files', 'help' => 'Number of log files to preserve', 'attr' => 'min="1"']),
				$this->settingsOption('select', 'logLiveUpdateRefresh', ['label' => 'Live Update Refresh', 'options' => $this->timeOptions()]),
				$this->settingsOption('select', 'logPageSize', ['label' => 'Log Page Size', 'options' => [['name' => '10 Items', 'value' => '10'], ['name' => '25 Items', 'value' => '25'], ['name' => '50 Items', 'value' => '50'], ['name' => '100 Items', 'value' => '100']]]),
			],
			'Cron' => [
				$this->settingsOption('cron-file'),
				$this->settingsOption('blank'),
				$this->settingsOption('enable', 'autoUpdateCronEnabled', ['label' => 'Auto-Update Organizr']),
				$this->settingsOption('cron', 'autoUpdateCronSchedule'),
			],
			'Login' => [
				$this->settingsOption('password', 'registrationPassword', ['label' => 'Registration Password', 'help' => 'Sets the password for the Registration form on the login screen']),
				$this->settingsOption('switch', 'hideRegistration', ['label' => 'Hide Registration', 'help' => 'Enable this to hide the Registration button on the login screen']),
				$this->settingsOption('number', 'rememberMeDays', ['label' => 'Remember Me Length', 'help' => 'Number of days cookies and tokens will be valid for', 'attr' => 'min="1"']),
				$this->settingsOption('switch', 'rememberMe', ['label' => 'Remember Me', 'help' => 'Default status of Remember Me button on login screen']),
				$this->settingsOption('multiple-url', 'localIPList', ['label' => 'Override Local IP or Subnet', 'help' => 'IPv4 only at the moment - This will set your login as local if your IP falls within the From and To']),
				$this->settingsOption('input', 'wanDomain', ['label' => 'WAN Domain', 'placeholder' => 'only domain and tld - i.e. domain.com', 'help' => 'Enter domain if you wish to be forwarded to a local address - Local Address filled out on next item']),
				$this->settingsOption('url', 'localAddress', ['label' => 'Local Address', 'placeholder' => 'http://home.local', 'help' => 'Full local address of organizr install - i.e. http://home.local or http://192.168.0.100']),
				$this->settingsOption('switch', 'enableLocalAddressForward', ['label' => 'Enable Local Address Forward', 'help' => 'Enables the local address forward if on local address and accessed from WAN Domain']),
				$this->settingsOption('switch', 'disableRecoverPass', ['label' => 'Disable Recover Password', 'help' => 'Disables recover password area']),
				$this->settingsOption('input', 'customForgotPassText', ['label' => 'Custom Recover Password Text', 'help' => 'Text or HTML for recovery password section']),
			],
			'Auth Proxy' => [
				$this->settingsOption('switch', 'authProxyEnabled', ['label' => 'Auth Proxy', 'help' => 'Enable option to set Auth Proxy Header Login']),
				$this->settingsOption('input', 'authProxyWhitelist', ['label' => 'Auth Proxy Whitelist', 'placeholder' => 'i.e. 10.0.0.0/24 or 10.0.0.20', 'help' => 'IPv4 only at the moment - This must be set to work, will accept subnet or IP address']),
				$this->settingsOption('input', 'authProxyHeaderName', ['label' => 'Auth Proxy Header Name', 'placeholder' => 'i.e. X-Forwarded-User', 'help' => 'Please choose a unique value for added security']),
				$this->settingsOption('input', 'authProxyHeaderNameEmail', ['label' => 'Auth Proxy Header Name for Email', 'placeholder' => 'i.e. X-Forwarded-Email', 'help' => 'Please choose a unique value for added security']),
			],
			'Ping' => [
				$this->settingsOption('auth', 'pingAuth'),
				$this->settingsOption('auth', 'pingAuthMessage', ['label' => 'Minimum Authentication for Message and Sound']),
				$this->settingsOption('select', 'pingOnlineSound', ['label' => 'Online Sound', 'options' => $this->getSounds()]),
				$this->settingsOption('select', 'pingOfflineSound', ['label' => 'Offline Sound', 'options' => $this->getSounds()]),
				$this->settingsOption('switch', 'pingMs', ['label' => 'Show Ping Time']),
				$this->settingsOption('switch', 'statusSounds', ['label' => 'Enable Notify Sounds', 'help' => 'Will play a sound if the server goes down and will play sound if comes back up.']),
				$this->settingsOption('auth', 'pingAuthMs', ['label' => 'Minimum Authentication for Time Display']),
				$this->settingsOption('refresh', 'adminPingRefresh', ['label' => 'Admin Refresh Seconds']),
				$this->settingsOption('refresh', 'otherPingRefresh', ['label' => 'Everyone Refresh Seconds']),
			],
			'Certificate' => [
				$this->settingsOption('html', '', ['override' => 12,
						'html' => '
					<script>
						let myDropzone = new Dropzone("#upload-custom-certificate", {
							url: "api/v2/certificate/custom",
							headers:{ "formKey": local("g","formKey") },
							init: function() {
								this.on("complete", function(file) {
									if(file["status"] === "success"){
										$(".custom-certificate-status").html("<span lang=\"en\">Custom Certificate Loaded</span>");
									}else{
										$(".custom-certificate-status").html("<span lang=\"en\">Error Saving file...</span>");
									}
								});
							}
						});
					</script>
					<div class="row">
						<div class="col-lg-12">
							<div class="panel panel-info">
								<div class="panel-heading"><span lang="en">Notice</span></div>
								<div class="panel-wrapper collapse in" aria-expanded="true">
									<div class="panel-body">
										<span lang="en">By default, Organizr uses certificates from https://curl.se/docs/caextract.html<br/>If you would like to use your own certificate, please upload it below.  You will then need to enable each homepage item to use it.</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="white-box">
								<h3 class="box-title m-b-0" lang="en">Custom Certificate Status</h3>
								<p class="text-muted m-b-30 custom-certificate-status">' . $certificateStatus . '</p>
								<form action="#" class="dropzone dz-clickable" id="upload-custom-certificate">
									<div class="dz-default dz-message"><span lang="en">Drop Certificate file here to upload</span></div>
								</form>
							</div>
						</div>
					</div>
					']
				)
			],
		];
	}

	public function getSettingsSSO()
	{
		return [
			'FYI' => [
				$this->settingsOption('html', '', ['override' => 12,
						'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-primary">
									<div class="panel-heading"><span lang="en">Please Read First</span></div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<span lang="en">Using multiple SSO application will cause your Cookie Header item to increase.  If you haven\'t increased it by now, please follow this guide</span>
											<br/><br/>
											<div class="row">
												<div class="col-lg-2 col-sm-4 col-xs-12">
													<a href="https://docs.organizr.app/help/faq/organizr-login-error" target="_blank" class="btn btn-block btn-primary text-white" lang="en">Cookie Header Guide</a>
												</div>
											</div>
											<br/>
											<span lang="en">This is not the same as database authentication - i.e. Plex Authentication | Emby Authentication | FTP Authentication<br/>Click Main on the sub-menu above.</span>
										</div>
									</div>
								</div>
							</div>
						</div>'
					]
				),
			],
			'Plex' => [
				$this->settingsOption('token', 'plexToken'),
				$this->settingsOption('button', '', ['label' => 'Get Plex Token', 'icon' => 'fa fa-ticket', 'text' => 'Retrieve', 'attr' => 'onclick="PlexOAuth(oAuthSuccess,oAuthError, null, \'#sso-form [name=plexToken]\')"']),
				$this->settingsOption('password-alt', 'plexID', ['label' => 'Plex Machine']),
				$this->settingsOption('button', '', ['label' => 'Get Plex Machine', 'icon' => 'fa fa-id-badge', 'text' => 'Retrieve', 'attr' => 'onclick="showPlexMachineForm(\'#sso-form [name=plexID]\')"']),
				$this->settingsOption('input', 'plexAdmin', ['label' => 'Plex Admin Username or Email']),
				$this->settingsOption('blank'),
				$this->settingsOption('html', 'Plex Note', ['html' => '<span lang="en">Please make sure both Token and Machine are filled in</span>']),
				$this->settingsOption('enable', 'ssoPlex'),
			],
			'Tautulli' => [
				$this->settingsOption('multiple-url', 'tautulliURL'),
				$this->settingsOption('auth', 'ssoTautulliAuth'),
				$this->settingsOption('enable', 'ssoTautulli'),
			],
			'Overseerr' => [
				$this->settingsOption('url', 'overseerrURL'),
				$this->settingsOption('token', 'overseerrToken'),
				$this->settingsOption('username', 'overseerrFallbackUser', ['label' => 'Overseerr Fallback Email', 'help' => 'DO NOT SET THIS TO YOUR ADMIN ACCOUNT. We recommend you create a local account as a "catch all" for when Organizr is unable to perform SSO.  Organizr will request a User Token based off of this user credentials']),
				$this->settingsOption('password', 'overseerrFallbackPassword', ['label' => 'Overseerr Fallback Password']),
				$this->settingsOption('enable', 'ssoOverseerr'),
			],
			'Petio' => [
				$this->settingsOption('url', 'petioURL'),
				$this->settingsOption('token', 'petioToken'),
				$this->settingsOption('username', 'petioFallbackUser', ['label' => 'Petio Fallback Email', 'help' => 'DO NOT SET THIS TO YOUR ADMIN ACCOUNT. We recommend you create a local account as a "catch all" for when Organizr is unable to perform SSO.  Organizr will request a User Token based off of this user credentials']),
				$this->settingsOption('password', 'petioFallbackPassword', ['label' => 'Petio Fallback Password']),
				$this->settingsOption('enable', 'ssoPetio'),
			],
			'Ombi' => [
				$this->settingsOption('url', 'ombiURL'),
				$this->settingsOption('token', 'ombiToken'),
				$this->settingsOption('username', 'ombiFallbackUser', ['label' => 'Ombi Fallback Email', 'help' => 'DO NOT SET THIS TO YOUR ADMIN ACCOUNT. We recommend you create a local account as a "catch all" for when Organizr is unable to perform SSO.  Organizr will request a User Token based off of this user credentials']),
				$this->settingsOption('password', 'ombiFallbackPassword', ['label' => 'Ombi Fallback Password']),
				$this->settingsOption('enable', 'ssoOmbi'),
			],
			'Jellyfin' => [
				$this->settingsOption('url', 'jellyfinURL', ['label' => 'Jellyfin API URL', 'help' => 'Please make sure to use the local address to the API']),
				$this->settingsOption('url', 'jellyfinSSOURL', ['label' => 'Jellyfin SSO URL', 'help' => 'Please make sure to use the same (sub)domain to access Jellyfin as Organizr\'s']),
				$this->settingsOption('enable', 'ssoJellyfin'),
			],
			'Komga' => [
				$this->settingsOption('url', 'komgaURL'),
				$this->settingsOption('auth', 'ssoKomgaAuth'),
				$this->settingsOption('enable', 'ssoKomga'),
			],
		];
	}

	public function systemMenuLists()
	{
		$pluginsMenu = [
			[
				'active' => false,
				'api' => 'api/v2/page/settings_plugins_enabled',
				'anchor' => 'settings-plugins-enabled-anchor',
				'name' => 'Active',
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_plugins_disabled',
				'anchor' => 'settings-plugins-disabled-anchor',
				'name' => 'Inactive',
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_plugins_settings',
				'anchor' => 'settings-plugins-settings-anchor',
				'name' => 'Settings',
			],
			[
				'active' => false,
				'api' => false,
				'anchor' => 'settings-plugins-marketplace-anchor',
				'name' => 'Marketplace',
				'onclick' => 'loadPluginMarketplace();'
			],
		];
		$userManagementMenu = [
			[
				'active' => false,
				'api' => 'api/v2/page/settings_user_manage_users',
				'anchor' => 'settings-user-manage-users-anchor',
				'name' => 'Manage Users'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_user_manage_groups',
				'anchor' => 'settings-user-manage-groups-anchor',
				'name' => 'Manage Groups'
			],
			[
				'active' => false,
				'api' => false,
				'anchor' => 'settings-user-import-users-anchor',
				'name' => 'Import Users'
			],
		];
		$customizeMenu = [
			[
				'active' => false,
				'api' => 'api/v2/page/settings_customize_appearance',
				'anchor' => 'settings-customize-appearance-anchor',
				'name' => 'Appearance',
			],
			[
				'active' => false,
				'api' => false,
				'anchor' => 'settings-customize-marketplace-anchor',
				'name' => 'Marketplace',
				'onclick' => 'loadMarketplace(\'themes\');'
			],
		];
		$tabEditorMenu = [
			[
				'active' => false,
				'api' => 'api/v2/page/settings_tab_editor_tabs',
				'anchor' => 'settings-tab-editor-tabs-anchor',
				'name' => 'Tabs'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_tab_editor_categories',
				'anchor' => 'settings-tab-editor-categories-anchor',
				'name' => 'Categories'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_tab_editor_homepage',
				'anchor' => 'settings-tab-editor-homepage-anchor',
				'name' => 'Homepage Items'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_tab_editor_homepage_order',
				'anchor' => 'settings-tab-editor-homepage-order-anchor',
				'name' => 'Homepage Order'
			],
		];
		$systemSettingsMenu = [
			[
				'active' => true,
				'api' => false,
				'anchor' => 'settings-settings-about-anchor',
				'name' => 'About'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_settings_main',
				'anchor' => 'settings-settings-main-anchor',
				'name' => 'Main'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_settings_sso',
				'anchor' => 'settings-settings-sso-anchor',
				'name' => 'SSO'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_settings_logs',
				'anchor' => 'settings-settings-logs-anchor',
				'name' => 'Logs'
			],
			[
				'active' => false,
				'api' => false,
				'anchor' => 'settings-settings-updates-anchor',
				'name' => 'Updates'
			],
			[
				'active' => false,
				'api' => 'api/v2/page/settings_settings_backup',
				'anchor' => 'settings-settings-backup-anchor',
				'name' => 'Backup'
			],
			[
				'active' => false,
				'api' => false,
				'anchor' => 'settings-settings-donate-anchor',
				'name' => 'Donate'
			],
		];
		$systemMenus['system_settings'] = $this->buildSettingsMenus($systemSettingsMenu, 'System Settings');
		$systemMenus['tab_editor'] = $this->buildSettingsMenus($tabEditorMenu, 'Tab Editor');
		$systemMenus['customize'] = $this->buildSettingsMenus($customizeMenu, 'Customize');
		$systemMenus['user_management'] = $this->buildSettingsMenus($userManagementMenu, 'User Management');
		$systemMenus['plugins'] = $this->buildSettingsMenus($pluginsMenu, 'Plugins');
		return $systemMenus;
	}

	public function updateConfigMultiple($array)
	{
		return (bool)$this->updateConfig($array);
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
				$this->config[$k] = $v;
			}
		}
		$this->setAPIResponse('success', 'Config items updated', 200);
		return (bool)$this->updateConfig($newItem);
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
		$this->config[$array['name']] = $array['value'];
		return (bool)$this->updateConfig($newItem);
	}

	public function ignoreNewsId($id)
	{
		if (!$id) {
			$this->setAPIResponse('error', 'News id was not supplied', 409);
			return false;
		}
		$id = array(intval($id));
		$newsIds = $this->config['ignoredNewsIds'];
		$newsIds = array_merge($newsIds, $id);
		$newsIds = array_unique($newsIds);
		$this->updateConfig(['ignoredNewsIds' => $newsIds]);
		$this->setAPIResponse('success', 'News id is now ignored', 200, null);
	}

	public function getNewsIds()
	{
		$newsIds = $this->config['ignoredNewsIds'];
		$this->setAPIResponse('success', null, 200, $newsIds);
		return $newsIds;
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
			'image' => 'fontawesome::question',
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
		$this->setLoggerChannel('Authentication', $username);
		$this->logger->debug('Starting token creation function');
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
		->withClaim('name', $result['username'])// Configures a new claim, called "name"
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
		if ($jwttoken) {
			$this->logger->debug('Token has been created');
		} else {
			$this->logger->warning('Token creation error');
		}
		$this->logger->debug('Token creation function has finished');
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
		// Set logger channel
		$this->setLoggerChannel('Authentication', $username);
		$this->logger->debug('Starting login function');
		// Set  other variables
		$function = 'plugin_auth_' . $this->config['authBackend'];
		$authSuccess = false;
		$authProxy = false;
		$addEmailToAuthProxy = true;
		// Check Login attempts and kill if over limit
		if ($loginAttempts > $this->config['loginAttempts'] || isset($_COOKIE['lockout'])) {
			$this->coookieSeconds('set', 'lockout', $this->config['loginLockout'], $this->config['loginLockout']);
			$this->logger->warning('User is locked out');
			$this->setAPIResponse('error', 'User is locked out', 403);
			return false;
		}
		// Check if Auth Proxy is enabled
		if ($this->config['authProxyEnabled'] && $this->config['authProxyHeaderName'] !== '' && $this->config['authProxyWhitelist'] !== '') {
			if (isset($this->getallheaders()[$this->config['authProxyHeaderName']])) {
				$usernameHeader = $this->getallheaders()[$this->config['authProxyHeaderName']] ?? $username;
				$emailHeader = $this->getallheaders()[$this->config['authProxyHeaderNameEmail']] ?? null;
				$this->setLoggerChannel('Authentication', $usernameHeader);
				$this->logger->debug('Starting Auth Proxy verification');
				$whitelistRange = $this->analyzeIP($this->config['authProxyWhitelist']);
				$authProxy = $this->authProxyRangeCheck($whitelistRange['from'], $whitelistRange['to']);
				$username = ($authProxy) ? $usernameHeader : $username;
				$password = ($password == null) ? $this->random_ascii_string(10) : $password;
				$addEmailToAuthProxy = ($authProxy && $emailHeader) ? ['email' => $emailHeader] : true;
				if ($authProxy) {
					$this->logger->info('User has been verified using Auth Proxy');
				} else {
					$this->logger->warning('User has failed verification using Auth Proxy');
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
							$this->logger->debug('User password has been verified');
							$authSuccess = true;
						}
					}
			}
			$authSuccess = ($authProxy) ? $addEmailToAuthProxy : $authSuccess;
		} else {
			// Has oAuth Token!
			switch ($oAuthType) {
				case 'plex':
					if ($this->config['plexoAuth']) {
						$this->logger->debug('Starting Plex oAuth verification');
						$tokenInfo = $this->checkPlexToken($oAuth);
						if ($tokenInfo) {
							$authSuccess = array(
								'username' => $tokenInfo['user']['username'],
								'email' => $tokenInfo['user']['email'],
								'image' => $tokenInfo['user']['thumb'],
								'token' => $tokenInfo['user']['authToken'],
								'oauth' => 'plex'
							);
							$this->logger->debug('User\'s Plex Token has been verified');
							$this->coookie('set', 'oAuth', 'true', $this->config['rememberMeDays']);
							$authSuccess = ((!empty($this->config['plexAdmin']) && strtolower($this->config['plexAdmin']) == strtolower($tokenInfo['user']['username'])) || (!empty($this->config['plexAdmin']) && strtolower($this->config['plexAdmin']) == strtolower($tokenInfo['user']['email'])) || $this->checkPlexUser($tokenInfo['user']['username'])) ? $authSuccess : false;
						} else {
							$this->logger->warning('User\'s Plex Token has failed verification');
						}
					} else {
						$this->logger->debug('Plex oAuth is not setup');
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
					$this->setLoggerChannel('Authentication', $username);
					$this->logger->info('User Password updated from backend');
				}
				if ($token !== '') {
					if ($token !== $result['plex_token']) {
						$this->updateUserPlexToken($token, $result['id']);
						$this->setLoggerChannel('Authentication', $username);
						$this->logger->info('User Plex Token updated from backend');
					}
				}
				// 2FA might go here
				if ($result['auth_service'] !== 'internal' && strpos($result['auth_service'], '::') !== false) {
					$tfaProceed = true;
					// Add check for local or not
					if ($this->config['ignoreTFALocal'] !== false) {
						$tfaProceed = !$this->isLocal();
					}
					// Is Plex Oauth?
					if ($this->config['ignoreTFAIfPlexOAuth'] !== false) {
						if (isset($authSuccess['oauth'])) {
							if ($authSuccess['oauth'] == 'plex') {
								$tfaProceed = false;
							}
						}
					}
					if ($tfaProceed) {
						$this->setLoggerChannel('Authentication', $username);
						$this->logger->debug('Starting 2FA verification');
						$TFA = explode('::', $result['auth_service']);
						// Is code with login info?
						if ($tfaCode == '') {
							$this->logger->debug('Sending 2FA response to login UI');
							$this->setAPIResponse('warning', '2FA Code Needed', 422);
							return false;
						} else {
							if (!$this->verify2FA($TFA[1], $tfaCode, $TFA[0])) {
								$this->logger->warning('Incorrect 2FA');
								$this->setAPIResponse('error', 'Wrong 2FA', 422);
								return false;
							} else {
								$this->logger->info('2FA verification passed');
							}
						}
					}
				}
				// End 2FA
				// authentication passed - 1) mark active and update token
				$createToken = $this->createToken($result['username'], $result['email'], $days);
				if ($createToken) {
					$this->logger->info('User has logged in');
					$this->ssoCheck($result, $password, $token); //need to work on this
					return ($output) ? array('name' => $this->cookieName, 'token' => (string)$createToken) : true;
				} else {
					$this->setAPIResponse('error', 'Token creation error', 500);
					return false;
				}
			} else {
				// Create User
				$this->setLoggerChannel('Authentication', (is_array($authSuccess) && isset($authSuccess['username']) ? $authSuccess['username'] : $username));
				$this->logger->debug('Starting Registration function');
				return $this->authRegister((is_array($authSuccess) && isset($authSuccess['username']) ? $authSuccess['username'] : $username), $password, (is_array($authSuccess) && isset($authSuccess['email']) ? $authSuccess['email'] : ''), $token);
			}
		} else {
			// authentication failed
			$this->setLoggerChannel('Authentication', $username);
			$this->logger->warning('Wrong Password');
			if ($loginAttempts >= $this->config['loginAttempts']) {
				$this->logger->warning('User exceeded maximum login attempts');
				$this->coookieSeconds('set', 'lockout', $this->config['loginLockout'], $this->config['loginLockout']);
				$this->setAPIResponse('error', 'User is locked out', 403);
				return false;
			} else {
				$this->logger->debug('User has not exceeded maximum login attempts');
				$this->setAPIResponse('error', 'User credentials incorrect', 401);
				return false;
			}
		}
	}

	public function logout()
	{
		$this->setLoggerChannel('Authentication');
		$this->logger->debug('Starting log out process');
		$this->logger->info('User has logged out');
		$this->coookie('delete', $this->cookieName);
		$this->coookie('delete', 'mpt');
		$this->coookie('delete', 'Auth');
		$this->coookie('delete', 'oAuth');
		$this->coookie('delete', 'connect.sid');
		$this->coookie('delete', 'petio_jwt');
		$this->clearTautulliTokens();
		$this->clearJellyfinTokens();
		$this->revokeTokenCurrentUser($this->user['token']);
		$this->clearKomgaToken();
		$this->refreshDeviceUUID();
		$this->logger->debug('Log out process has finished');
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
					$this->setLoggerChannel('Authentication', $username);
					$this->logger->info('User has logged in');
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
		$this->setLoggerChannel('Authentication', $username);
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
			if ($this->createToken($username, $email, $this->config['rememberMeDays'])) {
				$this->setLoggerChannel('Authentication', $username);
				$this->logger->info('User has logged in');
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
		$this->applyTabVariables($queries['tabs']);
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
		// js activeInfo
		return array(
			'homepage' => array(
				'refresh' => $this->refreshList(),
				'order' => $this->homepageOrderList(),
				'search' => array(
					'enabled' => $this->qualifyRequest($this->config['mediaSearchAuth']) && $this->config['mediaSearch'] == true && $this->config['plexToken'],
					'type' => $this->config['mediaSearchType'],
				),
				'requests' => [
					'service' => $this->config['defaultRequestService'],
				],
				'ombi' => array(
					'enabled' => $this->qualifyRequest($this->config['homepageOmbiAuth']) && $this->qualifyRequest($this->config['homepageOmbiRequestAuth']) && $this->config['homepageOmbiEnabled'] == true && $this->config['ssoOmbi'] && isset($_COOKIE['Auth']),
					'authView' => $this->qualifyRequest($this->config['homepageOmbiAuth']),
					'authRequest' => $this->qualifyRequest($this->config['homepageOmbiRequestAuth']),
					'sso' => (bool)$this->config['ssoOmbi'],
					'cookie' => isset($_COOKIE['Auth']),
					'alias' => (bool)$this->config['ombiAlias'],
					'ombiDefaultFilterAvailable' => (bool)$this->config['ombiDefaultFilterAvailable'],
					'ombiDefaultFilterUnavailable' => (bool)$this->config['ombiDefaultFilterUnavailable'],
					'ombiDefaultFilterApproved' => (bool)$this->config['ombiDefaultFilterApproved'],
					'ombiDefaultFilterUnapproved' => (bool)$this->config['ombiDefaultFilterUnapproved'],
					'ombiDefaultFilterDenied' => (bool)$this->config['ombiDefaultFilterDenied']
				),
				'overseerr' => array(
					'enabled' => $this->qualifyRequest($this->config['homepageOverseerrAuth']) && $this->qualifyRequest($this->config['homepageOverseerrRequestAuth']) && $this->config['homepageOverseerrEnabled'] == true && $this->config['ssoOverseerr'] && isset($_COOKIE['connect_sid']),
					'authView' => $this->qualifyRequest($this->config['homepageOverseerrAuth']),
					'authRequest' => $this->qualifyRequest($this->config['homepageOverseerrRequestAuth']),
					'sso' => (bool)$this->config['ssoOverseerr'],
					'cookie' => isset($_COOKIE['connect_sid']),
					'userSelectTv' => (bool)$this->config['homepageOverseerrRequestAuth'] == 'user',
					'overseerrDefaultFilterAvailable' => (bool)$this->config['overseerrDefaultFilterAvailable'],
					'overseerrDefaultFilterUnavailable' => (bool)$this->config['overseerrDefaultFilterUnavailable'],
					'overseerrDefaultFilterApproved' => (bool)$this->config['overseerrDefaultFilterApproved'],
					'overseerrDefaultFilterUnapproved' => (bool)$this->config['overseerrDefaultFilterUnapproved'],
					'overseerrDefaultFilterDenied' => (bool)$this->config['overseerrDefaultFilterDenied']
				),
				'jackett' => array(
					'homepageJackettBackholeDownload' => $this->config['homepageJackettBackholeDownload'] ? true : false
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
					'enabled' => (bool)$this->config['ssoPlex'],
					'cookie' => isset($_COOKIE['mpt']),
					'machineID' => strlen($this->config['plexID']) == 40,
					'token' => $this->config['plexToken'] !== '',
					'plexAdmin' => $this->checkPlexAdminFilled(),
					'strict' => (bool)$this->config['plexStrictFriends'],
					'oAuthEnabled' => (bool)$this->config['plexoAuth'],
					'backend' => $this->config['authBackend'] == 'plex',
				),
				'tautulli' => array(
					'enabled' => (bool)$this->config['ssoTautulli'],
					'cookie' => !empty($this->tautulliList()),
					'url' => ($this->config['tautulliURL'] !== '') ? $this->config['tautulliURL'] : false,
				),
				'overseerr' => array(
					'enabled' => (bool)$this->config['ssoOverseerr'],
					'cookie' => isset($_COOKIE['connect.sid']),
					'url' => ($this->config['overseerrURL'] !== '') ? $this->config['overseerrURL'] : false,
					'api' => $this->config['overseerrToken'] !== '',
				),
				'petio' => array(
					'enabled' => (bool)$this->config['ssoPetio'],
					'cookie' => isset($_COOKIE['petio_jwt']),
					'url' => ($this->config['petioURL'] !== '') ? $this->config['petioURL'] : false,
					'api' => $this->config['petioToken'] !== '',
				),
				'ombi' => array(
					'enabled' => (bool)$this->config['ssoOmbi'],
					'cookie' => isset($_COOKIE['Auth']),
					'url' => ($this->config['ombiURL'] !== '') ? $this->config['ombiURL'] : false,
					'api' => $this->config['ombiToken'] !== '',
				),
				'jellyfin' => array(
					'enabled' => (bool)$this->config['ssoJellyfin'],
					'url' => ($this->config['jellyfinURL'] !== '') ? $this->config['jellyfinURL'] : false,
					'ssoUrl' => ($this->config['jellyfinSSOURL'] !== '') ? $this->config['jellyfinSSOURL'] : false,
				),
				'komga' => [
					'enabled' => (bool)$this->config['ssoKomga'],
					'cookie' => isset($_COOKIE['komga_token']),
					'url' => ($this->config['komgaURL'] !== '') ? $this->config['komgaURL'] : false,
				]
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
				'expandCategoriesByDefault' => $this->config['expandCategoriesByDefault'],
				'autoCollapseCategories' => $this->config['autoCollapseCategories'],
				'autoExpandNavBar' => $this->config['autoExpandNavBar'],
				'sideMenuCollapsed' => $this->config['allowCollapsableSideMenu'] && $this->config['sideMenuCollapsed'],
				'collapseSideMenuOnClick' => $this->config['allowCollapsableSideMenu'] && $this->config['collapseSideMenuOnClick']
			),
			'menuLink' => array(
				'githubMenuLink' => $this->config['githubMenuLink'],
				'organizrSupportMenuLink' => $this->config['organizrSupportMenuLink'],
				'organizrDocsMenuLink' => $this->config['organizrDocsMenuLink'],
				'organizrSignoutMenuLink' => $this->config['organizrSignoutMenuLink'],
				'organizrFeatureRequestLink' => $this->config['organizrFeatureRequestLink']
			)
		);
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

	public function writeLog($type = 'error', $message = null, $username = null)
	{
		$this->timeExecution = $this->timeExecution($this->timeExecution);
		$message = $message . ' [Execution Time: ' . $this->formatSeconds($this->timeExecution) . ']';
		$username = ($username) ? htmlspecialchars($username, ENT_QUOTES) : $this->user['username'] ?? 'SYSTEM';
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
		$requesterToken = $this->getallheaders()['Token'] ?? ($_GET['apikey'] ?? false);
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
			} elseif (strpos($key, 'homepageOrdercustomhtml') !== false) {
				$iteration = substr($key, -2);
				$homepageBuilt .= $this->homepageOrdercustomhtml($iteration);
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
		$homepageList = '<div class="col-lg-12"><h4 lang="en">Drag Homepage Items to Order Them</h4></div><div id="homepage-items-sort" class="external-events">';
		$inputList = '<form id="homepage-values" class="row">';
		foreach ($homepageOrder as $key => $val) {
			switch ($key) {
				case 'homepageOrdercustomhtml01':
				case 'homepageOrdercustomhtml02':
				case 'homepageOrdercustomhtml03':
				case 'homepageOrdercustomhtml04':
				case 'homepageOrdercustomhtml05':
				case 'homepageOrdercustomhtml06':
				case 'homepageOrdercustomhtml07':
				case 'homepageOrdercustomhtml08':
					$iteration = substr($key, -2);
					$class = 'bg-info';
					$image = 'plugins/images/tabs/HTML5.png';
					if (!$this->config['homepageCustomHTML' . $iteration . 'Enabled']) {
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
				case 'homepageOrderuTorrent':
					$class = 'bg-qbit';
					$image = 'plugins/images/tabs/utorrent.png';
					if (!$this->config['homepageuTorrentEnabled']) {
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
				case 'homepageOrderoverseerr':
					$class = 'bg-inverse';
					$image = 'plugins/images/tabs/overseerr.png';
					if (!$this->config['homepageOverseerrEnabled']) {
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
					if (!$this->config['jdownloaderCombine'] && !$this->config['sabnzbdCombine'] && !$this->config['nzbgetCombine'] && !$this->config['rTorrentCombine'] && !$this->config['delugeCombine'] && !$this->config['transmissionCombine'] && !$this->config['qBittorrentCombine'] && !$this->config['uTorrentCombine']) {
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
                case 'homepageOrderBookmarks':
                    $class = 'bg-bookmarks';
                    $image = 'plugins/images/bookmark.png';
                    if (!$this->config['homepageBookmarksEnabled']) {
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
			if (strtolower($v['name']) === strtolower($item)) {
				$functionName = $v['settingsArray'];
				return $this->$functionName();
			}
		}
		$this->setAPIResponse('error', 'Homepage item was not found', 404);
		return null;
	}

	public function getSettingsHomepageItemDebug($service)
	{
		$service = $this->getSettingsHomepageItem($service);
		if ($service) {
			$debug = [];
			foreach ($service['settings'] as $category => $items) {
				if ($category !== 'About' && $category !== 'Test Connection') {
					foreach ($items as $item) {
						if ($item['type'] !== 'html' && $item['type'] !== 'blank' && $item['type'] !== 'button') {
							if ((stripos($item['name'], 'token') !== false) || (stripos($item['name'], 'key') !== false) || (stripos($item['name'], 'password'))) {
								if ($item['value'] !== '') {
									$item['value'] = '***redacted***';
								}
							}
							$debug[$category][$item['name']] = $item['value'];
						}
					}
				}
			}
			return $debug;
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

	public function getTabByIdCheckUser($id)
	{
		$tabInfo = $this->getTabById($id);
		if ($tabInfo) {
			if ($this->qualifyRequest($tabInfo['group_id'], true)) {
				return $tabInfo;
			}
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
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

	public function inconspicuous(): string
	{
		if ($this->hasDB()) {
			if ($this->config['easterEggs']) {
				return '
				<div class="org-rox-trigger">
					<div class="org-rox">
						<div class="hair"></div>
						<div class="head">
							<div class="ear left"></div>
							<div class="ear right"></div>
							<div class="face">
								<div class="eye left"></div>
								<div class="eye right"></div>
								<div class="nose"></div>
								<div class="mouth"></div>
							</div>
						</div>
					</div>
				</div>';
			}
		}
		return '';
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

	public function pluginFileListFormat($files, $folder)
	{
		$filesList = false;
		foreach ($files as $k => $v) {
			if ($v['type'] !== 'dir') {
				$filesList[] = array(
					'fileName' => $v['name'],
					'path' => DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . str_replace($v['name'], '', $v['path']),
					'githubPath' => $v['download_url']
				);
			}
		}
		return $filesList;
	}

	public function getPluginFilesFromGithub($plugin = 'test')
	{
		$url = 'https://api.github.com/repos/causefx/organizr/contents/' . $plugin . '?ref=v2-plugins';
		$options = array('verify' => false);
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			return json_decode($response->body, true);
		}
		return false;
	}

	public function getBranchFromGithub($repo)
	{
		$url = 'https://api.github.com/repos/' . $repo;
		$options = array('verify' => false);
		$response = Requests::get($url, $this->setGithubAccessToken(), $options);
		try {
			if ($response->success) {
				$github = json_decode($response->body, true);
				return $github['default_branch'] ?? null;
			} else {
				$this->setLoggerChannel('Plugins');
				$this->logger->warning('Plugin failed to get branch from Github', $this->apiResponseFormatter($response->body));
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->logger->error($e);
			$this->setAPIResponse('error', $e->getMessage(), 401);
			return false;
		}
	}

	public function getFilesFromGithub($repo, $branch)
	{
		if (!$repo || !$branch) {
			return false;
		}
		$url = 'https://api.github.com/repos/' . $repo . '/git/trees/' . $branch . '?recursive=1';
		$options = array('verify' => false);
		$response = Requests::get($url, $this->setGithubAccessToken(), $options);
		try {
			if ($response->success) {
				$github = json_decode($response->body, true);
				return is_array($github) ? $github : null;
			} else {
				$this->setLoggerChannel('Plugins');
				$this->logger->warning('Plugin failed to get branch from Github', $this->apiResponseFormatter($response->body));
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->logger->error($e);
			$this->setAPIResponse('error', $e->getMessage(), 401);
			return false;
		}
	}

	public function formatFilesFromGithub($files, $repo, $branch, $folder)
	{
		if (!$files || !$repo || !$branch || !$folder) {
			return false;
		}
		if (isset($files['tree'])) {
			$fileList = [];
			foreach ($files['tree'] as $k => $v) {
				if ($v['type'] !== 'tree') {
					$fileInfo = pathinfo($v['path']);
					$v['name'] = $fileInfo['basename'];
					$v['download_url'] = 'https://raw.githubusercontent.com/' . $repo . '/' . $branch . '/' . $v['path'];
					if ($folder == 'root') {
						$fileList[] = $v;
					} else {
						if (stripos($v['path'], $folder) !== false) {
							$v['path'] = (substr($v['path'], 0, strlen($folder)) == $folder) ? substr($v['path'], (strlen($folder) + 1)) : $v['path'];
							$fileList[] = $v;
						}
					}
				}
			}
			return $fileList;
		}
		return false;
	}

	public function getPluginFilesFromRepo($plugin, $pluginDetails)
	{
		if (stripos($pluginDetails['repo'], 'github.com') !== false) {
			$repo = explode('https://github.com/', $pluginDetails['repo']);
		} else {
			return false;
		}
		$branch = $this->getBranchFromGithub($repo[1]);
		if ($branch) {
			return $this->formatFilesFromGithub($this->getFilesFromGithub($repo[1], $branch), $repo[1], $branch, $pluginDetails['github_folder']);
		}
		return false;
	}

	public function installPlugin($plugin)
	{
		$this->setLoggerChannel('Plugin Marketplace');
		$plugin = $this->reverseCleanClassName($plugin);
		$array = $this->getPluginsMarketplace();
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
		// Check Version of Organizr against minimum version needed
		$compare = new Composer\Semver\Comparator;
		if ($compare->lessThan($this->version, $array['minimum_organizr_version'])) {
			$this->logger->warning('Minimum Organizr version needed: ' . $array['minimum_organizr_version']);
			$this->setResponse(500, 'Minimum Organizr version needed: ' . $array['minimum_organizr_version'] . ' | Current Version: ' . $this->version);
			return true;
		}
		$files = $this->getPluginFilesFromRepo($plugin, $array);
		if ($files) {
			$downloadList = $this->pluginFileListFormat($files, $array['project_folder']);
		} else {
			$this->logger->warning('File list failed for: ' . $array['github_folder']);
			$this->setAPIResponse('error', 'Could not get download list for plugin', 409);
			return false;
		}
		if (!$downloadList) {
			$this->logger->warning('Setting download list failed for: ' . $array['github_folder']);
			$this->setAPIResponse('error', 'Could not get download list for plugin', 409);
			return false;
		}
		foreach ($downloadList as $k => $v) {
			$file = array(
				'from' => $v['githubPath'],
				'to' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'] . $v['fileName']),
				'path' => str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->root . $v['path'])
			);
			if (!$this->downloadFileToPath($file['from'], $file['to'], $file['path'])) {
				$this->setLoggerChannel('Plugin Marketplace');
				$this->logger->warning('Downloaded File Failed  for: ' . $v['githubPath']);
				$this->setAPIResponse('error', 'Plugin download failed', 500);
				return false;
			}
		}
		$this->updateInstalledPlugins('install', $plugin, $array);
		$this->setAPIResponse('success', 'Plugin installed', 200, $array);
		return true;
	}

	public function removePlugin($plugin)
	{
		$this->setLoggerChannel('Plugin Marketplace');
		$plugin = $this->reverseCleanClassName($plugin);
		$array = $this->getPluginsMarketplace();
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
		$pluginDir = $this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $array['project_folder'] . DIRECTORY_SEPARATOR;
		$dirExists = file_exists($pluginDir);
		if ($dirExists) {
			if (!$this->rrmdir($pluginDir)) {
				$this->logger->info('Remove File Failed  for: ' . $array['project_folder']);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Plugin is not installed', 404);
			return false;
		}
		$this->updateInstalledPlugins('uninstall', $plugin, $array);
		$this->setAPIResponse('success', 'Plugin removed', 200, $array);
		return true;
	}

	public function updateInstalledPlugins($action, $plugin, $pluginDetails)
	{
		if (!$action || !$plugin || !$pluginDetails) {
			return false;
		}
		$config = $this->config['installedPlugins'];
		switch ($action) {
			case 'install':
			case 'update':
				$update[$plugin] = [
					'name' => $plugin,
					'version' => $pluginDetails['version'],
					'repo' => $pluginDetails['repo']
				];
				$config = array_merge($config, $update);
				break;
			default:
				unset($config[$plugin]);
				break;
		}
		$this->updateConfig(['installedPlugins' => $config]);
	}

	public function getThemesGithub()
	{
		$url = 'https://raw.githubusercontent.com/causefx/Organizr/v2-themes/themes.json';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			return json_decode($response->body, true);
		}
		return false;
	}

	public function getPluginsGithub()
	{
		$url = 'https://raw.githubusercontent.com/causefx/Organizr/v2-plugins/plugins.json';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				return json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			return false;
		}
		return false;
	}

	public function getPluginsMarketplace()
	{
		$plugins = $this->getPluginsGithubCombined();
		foreach ($plugins as $pluginName => $pluginDetails) {
			$plugins[$pluginName]['installed'] = (isset($this->config['installedPlugins'][$pluginName]));
			$plugins[$pluginName]['installed_version'] = $this->config['installedPlugins'][$pluginName]['version'] ?? null;
			$plugins[$pluginName]['needs_update'] = ($plugins[$pluginName]['installed'] && ($plugins[$pluginName]['installed_version'] !== $plugins[$pluginName]['version']));
			$plugins[$pluginName]['status'] = $this->getPluginStatus($plugins[$pluginName]);
		}
		return $plugins;
	}

	public function getPluginStatus($pluginDetails)
	{
		if ($pluginDetails['needs_update']) {
			return 'Update Available';
		} elseif ($pluginDetails['installed']) {
			return 'Up to date';
		} else {
			return 'Not Installed';
		}
	}

	public function getPluginsGithubCombined()
	{
		// Organizr Repo
		$urls = [$this->getMarketplaceJSONFromRepo('https://github.com/Organizr/Organizr-Plugins')];
		foreach (explode(',', $this->config['externalPluginMarketplaceRepos']) as $repo) {
			$urls[] = $this->getMarketplaceJSONFromRepo($repo);
		}
		$plugins = [];
		foreach ($urls as $repo) {
			$options = ($this->localURL($repo)) ? array('verify' => false) : array();
			try {
				$response = Requests::get($repo, array(), $options);
				if ($response->success) {
					$plugins = array_merge($plugins, json_decode($response->body, true));
				} else {
					$this->setLoggerChannel('Plugins');
					$this->logger->warning('Getting Marketplace items from Github', $this->apiResponseFormatter($response->body));
					return false;
				}
			} catch (Requests_Exception $e) {
				//return false;
			}
		}
		return $plugins;
	}

	public function getMarketplaceJSONFromRepo($url)
	{
		if (stripos($url, '.json') !== false) {
			return $url;
		} elseif (stripos($url, 'github.com') !== false) {
			$repo = explode('https://github.com/', $url);
			$newURL = 'https://api.github.com/repos/' . $repo[1] . '/contents';
			$options = ($this->localURL($newURL)) ? array('verify' => false) : array();
			try {
				$response = Requests::get($newURL, $this->setGithubAccessToken(), $options);
				if ($response->success) {
					$jsonFiles = json_decode($response->body, true);
					foreach ($jsonFiles as $file) {
						if (stripos($file['name'], '.json') !== false) {
							return $file['download_url'];
						}
					}
					return false;
				} else {
					$this->setLoggerChannel('Plugins');
					$this->logger->warning('Getting Marketplace JSON from Github', $this->apiResponseFormatter($response->body));
					return false;
				}
			} catch (Requests_Exception $e) {
				return false;
			}
		}
		return false;
	}

	public function setGithubAccessToken()
	{
		return ($this->config['githubAccessToken'] !== '') ? ['Authorization' => 'token ' . $this->config['githubAccessToken']] : [];
	}

	public function formatGithubAccessToken()
	{
		$accessToken = $this->setGithubAccessToken();
		if (count($accessToken) >= 1) {
			return key($accessToken) . ': ' . $accessToken[key($accessToken)];
		} else {
			return '';
		}
	}

	public function getOpenCollectiveBackers()
	{
		$url = 'https://opencollective.com/organizr/members/users.json?limit=100&offset=0';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api = json_decode($response->body, true);
				foreach ($api as $k => $backer) {
					$api[$k] = array_merge($api[$k], ['sortName' => strtolower($backer['name'])]);
				}
				$this->setAPIResponse('success', '', 200, $api);
				return $api;
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		$this->setAPIResponse('error', 'Error connecting to Open Collective', 409);
		return false;
	}

	public function getGithubSponsors()
	{
		$url = 'https://github.com/sponsors/causefx';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			$sponsors = [];
			$dom = new PHPHtmlParser\Dom;
			try {
				$dom->loadStr($response->body);
				$contents = $dom->find('#sponsors .clearfix div');
				foreach ($contents as $content) {
					$html = $content->innerHtml;
					preg_match('/(@[a-zA-Z])\w+/', $html, $username);
					preg_match('/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'\".,<>?«»""\'\']))/', $html, $image);
					if (isset($image[0]) && isset($username[0])) {
						$sponsors[] = [
							'name' => str_replace('@', '', $username[0]),
							'sortName' => str_replace('@', '', strtolower($username[0])),
							'image' => str_replace('s=60', 's=200', $image[0]),
							'isActive' => true,
							'type' => 'USER',
							'role' => 'BACKER'
						];
					}
				}
				$this->setAPIResponse('success', '', 200, $sponsors);
				return $sponsors;
			} catch (\PHPHtmlParser\Exceptions\ChildNotFoundException | \PHPHtmlParser\Exceptions\CircularException | \PHPHtmlParser\Exceptions\LogicalException | \PHPHtmlParser\Exceptions\StrictException | \PHPHtmlParser\Exceptions\ContentLengthException | \PHPHtmlParser\Exceptions\NotLoadedException $e) {
				$this->setAPIResponse('error', 'Error connecting to Github', 409);
				return false;
			}
		}
		$this->setAPIResponse('error', 'Error connecting to Github', 409);
		return false;
	}

	public function getAllSponsors()
	{
		$sponsors = [];
		$list = [
			'openCollective' => $this->getOpenCollectiveBackers(),
			'github' => $this->getGithubSponsors()
		];
		foreach ($list as $k => $sponsor) {
			if ($sponsor) {
				$sponsors = array_merge($sponsor, $sponsors);
			}
		}
		if ($sponsors) {
			usort($sponsors, function ($a, $b) {
				return $a['sortName'] <=> $b['sortName'];
			});
		}
		$this->setAPIResponse('success', '', 200, $sponsors);
		return $sponsors;
	}

	public function getOrganizrSmtpFromAPI()
	{
		$url = 'https://api.organizr.app/?cmd=smtp';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				return json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		return false;
	}

	public function saveOrganizrSmtpFromAPI()
	{
		$api = $this->getOrganizrSmtpFromAPI();
		if ($api) {
			$this->updateConfigItems($api['response']['data']);
			$this->setAPIResponse(null, 'SMTP activated with Organizr SMTP account');
			return true;
		} else {
			return false;
		}
	}

	public function guestHash($start, $end)
	{
		$ip = $this->userIP();
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
		if ((stripos($from, 'api.github.com') !== false) && $this->config['githubAccessToken'] !== '') {
			$context = stream_context_create(
				array(
					'ssl' => array(
						'verify_peer' => false,
						'cafile' => $this->getCert()
					),
					'http' => array(
						'method' => 'GET',
						'header' => $this->formatGithubAccessToken()
					)
				)
			);
		} else {
			$context = stream_context_create([]);
		}
		ini_set('max_execution_time', 0);
		set_time_limit(0);
		if (@!mkdir($path, 0777, true)) {
			$this->writeLog("error", "organizr could not create folder or folder already exists", 'SYSTEM');
		}
		$file = fopen($from, 'rb', false, $context);
		if ($file) {
			$newf = fopen($to, 'wb', false, $context);
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
											$shareId = $server['id'];
										}
									}
									if ($machineMatches) {
										$results[] = array(
											'username' => (string)$child['username'],
											'email' => (string)$child['email'],
											'id' => (string)$child['id'],
											'shareId' => (string)$shareId
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
		if ((int)$id !== $this->user['userID']) {
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
				$array['group_id'] = 0;
				//$this->setAPIResponse('error', 'group_id was set but empty', 409);
				//return false;
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
		$this->setAPIResponse(null, 'Group added');
		$this->writeLog('success', 'Group Editor Function -  Added Group for [' . $array['group'] . ']', $this->user['username']);
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

	public function checkValidCert($file)
	{
		if (file_exists($file)) {
			return filesize($file) > 0;
		} else {
			return false;
		}
	}

	public function getCert()
	{
		$url = 'http://curl.haxx.se/ca/cacert.pem';
		$file = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert.pem';
		$file2 = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert-initial.pem';
		$useCert = ($this->checkValidCert($file)) ? $file : $file2;
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
		if (!$this->checkValidCert($file) || (file_exists($file) && time() - 2592000 > filemtime($file))) {
			file_put_contents($file, fopen($url, 'r', false, $context));
		}
		return ($this->checkValidCert($file)) ? $file : $file2;
	}

	public function hasCustomCert()
	{
		return file_exists(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'custom.pem');
	}

	public function getCustomCert()
	{
		return ($this->hasCustomCert()) ? dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'custom.pem' : false;
	}

	public function uploadCert()
	{
		$filesCheck = array_filter($_FILES);
		if (!empty($filesCheck) && $this->approvedFileExtension($_FILES['file']['name'], 'cert')) {
			ini_set('upload_max_filesize', '10M');
			ini_set('post_max_size', '10M');
			$tempFile = $_FILES['file']['tmp_name'];
			$targetPath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR;
			$targetFile = $targetPath . 'custom.pem';
			$this->setAPIResponse(null, pathinfo($_FILES['file']['name'], PATHINFO_BASENAME) . ' has been uploaded', null);
			return move_uploaded_file($tempFile, $targetFile);
		} else {
			$this->setAPIResponse('error', pathinfo($_FILES['file']['name'], PATHINFO_BASENAME) . ' is not approved to be uploaded', 403);
			return false;
		}
	}

	public function createCronFile()
	{
		$file = $this->root . DIRECTORY_SEPARATOR . 'Cron.txt';
		file_put_contents($file, time());
	}

	public function checkCronFile()
	{
		$file = $this->root . DIRECTORY_SEPARATOR . 'Cron.txt';
		return file_exists($file) && time() - 120 < filemtime($file);
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
			$success = empty($json['errors']);
			//Use This for later
			$errorMessage = '';
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

	public function chooseInstance($url = null, $token = null, $instance = 0, $type = null)
	{
		if (!$url || !$token) {
			return false;
		}
		$list = $this->csvHomepageUrlToken($url, $token);
		if ($type) {
			$type = strtolower($type);
			switch ($type) {
				case 'url':
				case 'token':
					break;
				default:
					$type = 'url';
					break;
			}
			if (is_numeric($instance)) {
				return $list[$instance][$type];
			} else {
				return $list;
			}
		}
		if (is_numeric($instance)) {
			return $list[$instance];
		} else {
			return $list;
		}
	}

	public function CBPFWTabs()
	{
		return '
		<script>
		/**
		* cbpFWTabs.js v1.0.0
		* http://www.codrops.com
		*
		* Licensed under the MIT license.
		* http://www.opensource.org/licenses/mit-license.php
		*
		* Copyright 2014, Codrops
		* http://www.codrops.com
		*/
		;( function( window ) {
			\'use strict\';
		
			function extend( a, b ) {
				for( var key in b ) {
					if( b.hasOwnProperty( key ) ) {
						a[key] = b[key];
					}
				}
				return a;
			}
		
			function CBPFWTabs( el, options ) {
				this.el = el;
				this.options = extend( {}, this.options );
				extend( this.options, options );
				this._init();
			}
		
			CBPFWTabs.prototype.options = {
				start : 0
			};
		
			CBPFWTabs.prototype._init = function() {
				// tabs elems
				this.tabs = [].slice.call( this.el.querySelectorAll( \'nav > ul > li\' ) );
				// content items
				this.items = [].slice.call( this.el.querySelectorAll( \'.content-wrap > section\' ) );
				// current index
				this.current = -1;
				// show current content item
				try{
					if(this.tabs[0].innerHTML.indexOf(\'#settings\') >= 0){
						this._show(' . $this->config['defaultSettingsTab'] . ');
						let tabId = $(this.items[' . $this->config['defaultSettingsTab'] . ']).attr("id") + "-anchor";
						$("#" + tabId).click();
						$("#" + tabId + " a").click();
					}else{
						this._show();
					}
				}catch{
					this._show();
				}
				// init events
				this._initEvents();
			};
		
			CBPFWTabs.prototype._initEvents = function() {
				var self = this;
				this.tabs.forEach( function( tab, idx ) {
					tab.addEventListener( \'click\', function( ev ) {
						ev.preventDefault();
						self._show( idx );
					} );
				} );
			};
		
			CBPFWTabs.prototype._show = function( idx ) {
				if( this.current >= 0 ) {
					this.tabs[ this.current ].className = this.items[ this.current ].className = \'\';
				}
				// change current
				this.current = idx != undefined ? idx : this.options.start >= 0 && this.options.start < this.items.length ? this.options.start : 0;
				this.tabs[ this.current ].className = \'tab-current\';
				this.items[ this.current ].className = \'content-current\';
			};
		
			// add to global namespace
			window.CBPFWTabs = CBPFWTabs;
		
		})( window );
		</script>
		';
	}

	public function socksHeadingHTML($app)
	{
		return '
		<h3 lang="en">' . ucwords($app) . ' SOCKS API Connection</h3>
		<p>Using this feature allows you to access the API without having to reverse proxy it.  Just access it from: </p>
		<code class="elip hidden-xs">' . $this->getServerPath() . 'api/v2/socks/' . $app . '/</code>
		<p>If you are using multiple URL\'s (using the csv method) you will have to use the url like these: </p>
		<code class="elip hidden-xs">' . $this->getServerPath() . 'api/v2/multiple/socks/' . $app . '/1</code>
		<br/>
		<code class="elip hidden-xs">' . $this->getServerPath() . 'api/v2/multiple/socks/' . $app . '/2</code>
		';
	}

	public function socksListing($app = null)
	{
		switch ($app) {
			case 'sonarr':
				$appDetails = [
					'url' => 'sonarrURL',
					'enabled' => 'sonarrSocksEnabled',
					'auth' => 'sonarrSocksAuth',
					'header' => 'X-Api-Key'
				];
				break;
			case 'radarr':
				$appDetails = [
					'url' => 'radarrURL',
					'enabled' => 'radarrSocksEnabled',
					'auth' => 'radarrSocksAuth',
					'header' => 'X-Api-Key'
				];
				break;
			case 'lidarr':
				$appDetails = [
					'url' => 'lidarrURL',
					'enabled' => 'lidarrSocksEnabled',
					'auth' => 'lidarrSocksAuth',
					'header' => 'X-Api-Key'
				];
				break;
			case 'sabnzbd':
				$appDetails = [
					'url' => 'sabnzbdURL',
					'enabled' => 'sabnzbdSocksEnabled',
					'auth' => 'sabnzbdSocksAuth',
					'header' => null
				];
				break;
			case 'nzbget':
				$appDetails = [
					'url' => 'nzbgetURL',
					'enabled' => 'nzbgetSocksEnabled',
					'auth' => 'nzbgetSocksAuth',
					'header' => 'Authorization'
				];
				break;
			case 'tautulli':
				$appDetails = [
					'url' => 'tautulliURL',
					'enabled' => 'tautulliSocksEnabled',
					'auth' => 'tautulliSocksAuth',
					'header' => null
				];
				break;
			case 'qbittorrent':
				$appDetails = [
					'url' => 'qBittorrentURL',
					'enabled' => 'qBittorrentSocksEnabled',
					'auth' => 'qBittorrentSocksAuth',
					'header' => null
				];
				break;
			default:
				$appDetails = null;
		}
		return $appDetails;
	}

	public function socks($appDetails, $requestObject, $multiple = null)
	{
		$url = $appDetails['url'];
		$enabled = $appDetails['enabled'];
		$auth = $appDetails['auth'];
		$header = $appDetails['header'];
		$error = false;
		if (!$this->config[$enabled]) {
			$error = true;
			$this->setAPIResponse('error', 'SOCKS module is not enabled', 409);
		}
		if (!$this->qualifyRequest($this->config[$auth], true)) {
			$error = true;
		}
		if (strpos($this->config[$url], ',') !== false) {
			if (!$multiple) {
				$error = true;
				$this->setAPIResponse('error', 'Multiple URLs found in field, please use /api/v2/multiple/socks endpoint', 409);
			}
		} else {
			if ($multiple) {
				$error = true;
				$this->setAPIResponse('error', 'Multiple endpoint accessed but multiple URLs not found in field, please use /api/v2/socks endpoint', 409);
			}
		}
		if (!$error) {
			if ($multiple) {
				$instance = $multiple - 1;
				$pre = explode('/api/v2/multiple/socks/', $requestObject->getUri()->getPath());
				$pre[1] = $this->replace_first('/' . $multiple . '/', '/', $pre[1]);
				// sent url twice since we arent using tokens
				$list = $this->csvHomepageUrlToken($this->config[$url], $this->config[$url]);
				$appURL = $list[$instance]['url'];
			} else {
				$pre = explode('/api/v2/socks/', $requestObject->getUri()->getPath());
				$appURL = $this->config[$url];
			}
			$endpoint = explode('/', $pre[1]);
			$new = urldecode(preg_replace('/' . $endpoint[0] . '/', '', $pre[1], 1));
			$getParams = ($_GET) ? '?' . http_build_query($_GET) : '';
			$url = $this->qualifyURL($appURL) . $new . $getParams;
			$url = $this->cleanPath($url);
			$options = ($this->localURL($appURL)) ? array('verify' => false, 'timeout' => 120) : array('timeout' => 120);
			$headers = [];
			$apiData = $this->json_validator($this->apiData($requestObject)) ? json_encode($this->apiData($requestObject)) : $this->apiData($requestObject);
			if ($header) {
				if ($requestObject->hasHeader($header)) {
					$headerKey = $requestObject->getHeaderLine($header);
					$headers[$header] = $headerKey;
				}
			}
			if ($requestObject->hasHeader('Content-Type')) {
				$headerKey = $requestObject->getHeaderLine('Content-Type');
				$headers['Content-Type'] = $headerKey;
			}
			$debugInformation = [
				'type' => $requestObject->getMethod(),
				'headerType' => $requestObject->getHeaderLine('Content-Type'),
				'header' => $header,
				'headers' => $headers,
				'url' => $url,
				'options' => $options,
				'data' => $apiData
			];
			$this->setLoggerChannel('Socks');
			$this->logger->debug('Sending Socks request', $debugInformation);
			try {
				switch ($requestObject->getMethod()) {
					case 'GET':
						$call = Requests::get($url, $headers, $options);
						break;
					case 'POST':
						$call = Requests::post($url, $headers, $apiData, $options);
						break;
					case 'DELETE':
						$call = Requests::delete($url, $headers, $options);
						break;
					case 'PUT':
						$call = Requests::put($url, $headers, $apiData, $options);
						break;
					default:
						$call = Requests::get($url, $headers, $options);
				}
				$this->logger->debug('Socks Response', $this->json_validator($call->body) ? json_decode($call->body, true) : $call->body);
				return $call->body;
			} catch (Requests_Exception $e) {
				$this->setAPIResponse('error', $e->getMessage(), 500);
				$this->setLoggerChannel('Socks');
				$this->logger->critical($e, $debugInformation);
				return null;
			}
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
		try {
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
		} catch (Requests_Exception $e) {
			$this->writeLog('success', 'Plex Get Servers Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
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

	public function getJournalMode()
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => 'PRAGMA journal_mode',
			),
		];
		$query = $this->processQueries($response);
		if ($query) {
			if ($query['journal_mode']) {
				$this->setResponse(200, null, $query);
			} else {
				$this->setResponse(500, 'Error getting Journal Mode');
			}
		} else {
			$this->setResponse(404, 'Journal Mode not found');
		}
		return $query;
	}

	public function setJournalMode($option = 'WAL')
	{
		$option = strtoupper($option);
		switch ($option) {
			case 'WAL':
			case 'DELETE':
				break;
			default:
				return false;
		}
		$response = [
			array(
				'function' => 'fetch',
				'query' => 'PRAGMA journal_mode = \'' . $option . '\';',
			),
		];
		$query = $this->processQueries($response);
		if ($query) {
			if ($query['journal_mode']) {
				$this->setResponse(200, 'Journal Mode updated to: ' . $option, $query);
			} else {
				$this->setResponse(500, 'Error getting Journal Mode');
			}
		} else {
			$this->setResponse(404, 'Journal Mode not found');
		}
		return $query;
	}

	public function testCronSchedule($schedule = null)
	{
		if (is_array($schedule)) {
			$schedule = str_replace('_', ' ', array_keys($schedule)[0]);
		}
		if (!$schedule) {
			$this->setResponse(409, 'Schedule was not supplied');
			return false;
		}
		try {
			$schedule = new Cron\CronExpression($schedule);
			$this->setResponse(200, 'Schedule was validated');
			return true;
		} catch (InvalidArgumentException $e) {
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	protected function processQueries(array $request, $migration = false)
	{
		$results = array();
		$firstKey = '';
		if ($this->config['includeDatabaseQueriesInDebug']) {
			$this->setLoggerChannel('Database');
			$this->logger->debug('Query to database', $request);
		}
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
						// PHP 8 Fix?
						$query->setRowClass(null);
						$results[$keyName] = $query->fetch();
						break;
					case 'getAffectedRows':
						$results[$keyName] = $query->getAffectedRows();
						break;
					case 'getRowCount':
						$results[$keyName] = $query->getRowCount();
						break;
					case 'fetchSingle':
						// PHP 8 Fix?
						$query->setRowClass(null);
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
			$this->setLoggerChannel('Database');
			$this->logger->critical($e, $request);
			return false;
		}
		if ($this->config['includeDatabaseQueriesInDebug']) {
			$this->logger->debug('Results from database', $results);
		}
		return count($request) > 1 ? $results : $results[$firstKey];
	}

}