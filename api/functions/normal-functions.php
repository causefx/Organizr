<?php

trait NormalFunctions
{
	public function formatSeconds($seconds)
	{
		$hours = 0;
		$milliseconds = str_replace("0.", '', $seconds - floor($seconds));
		if ($seconds > 3600) {
			$hours = floor($seconds / 3600);
		}
		$seconds = $seconds % 3600;
		$time = str_pad($hours, 2, '0', STR_PAD_LEFT)
			. gmdate(':i:s', $seconds)
			. ($milliseconds ? '.' . $milliseconds : '');
		$parts = explode(':', $time);
		$timeExtra = explode('.', $parts[2]);
		if ($parts[0] !== '00') { // hours
			return $time;
		} elseif ($parts[1] !== '00') { // mins
			return $parts[1] . 'min(s) ' . $timeExtra[0] . 's';
		} elseif ($timeExtra[0] !== '00') { // secs
			return substr($parts[2], 0, 5) . 's | ' . substr($parts[2], 0, 7) * 1000 . 'ms';
		} else {
			return substr($parts[2], 0, 7) * 1000 . 'ms';
		}
		//return $timeExtra[0] . 's ' . (number_format(('0.' . substr($timeExtra[1], 0, 4)), 4, '.', '') * 1000) . 'ms';
		//return (number_format(('0.' . substr($timeExtra[1], 0, 4)), 4, '.', '') * 1000) . 'ms';
	}

	public function getExtension($string)
	{
		return preg_replace("#(.+)?\.(\w+)(\?.+)?#", "$2", $string);
	}

	public function get_browser_name()
	{
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) {
			return 'Opera';
		} elseif (strpos($user_agent, 'Edge')) {
			return 'Edge';
		} elseif (strpos($user_agent, 'Chrome')) {
			return 'Chrome';
		} elseif (strpos($user_agent, 'Safari')) {
			return 'Safari';
		} elseif (strpos($user_agent, 'Firefox')) {
			return 'Firefox';
		} elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
			return 'Internet Explorer';
		}
		return 'Other';
	}

	public function array_filter_key(array $array, $callback)
	{
		$matchedKeys = array_filter(array_keys($array), $callback);
		return array_intersect_key($array, array_flip($matchedKeys));
	}

	public function getOS()
	{
		if (PHP_SHLIB_SUFFIX == "dll") {
			return "win";
		} else {
			return "*nix";
		}
	}

	// Get Gravatar Email Image
	public function gravatar($email = '')
	{
		$email = md5(strtolower(trim($email)));
		return "https://www.gravatar.com/avatar/$email?s=100&d=mm";
	}

	// Clean Directory string
	public function cleanDirectory($path)
	{
		$path = str_replace(array('/', '\\'), '/', $path);
		if (substr($path, -1) != '/') {
			$path = $path . '/';
		}
		if ($path[0] != '/' && $path[1] != ':') {
			$path = '/' . $path;
		}
		return $path;
	}

	// Print output all purrty
	public function prettyPrint($v, $error = false)
	{
		if ($error) {
			$background = 'red';
			$border = 'black';
			$text = 'white';
		} else {
			$background = '#f2f2f2';
			$border = 'black';
			$text = 'black';
		}
		$trace = debug_backtrace()[0];
		echo '<pre style="white-space: pre; text-overflow: ellipsis; overflow: hidden; color: ' . $text . '; background-color: ' . $background . '; border: 2px solid ' . $border . '; border-radius: 5px; padding: 5px; margin: 5px;">' . $trace['file'] . ':' . $trace['line'] . ' ' . gettype($v) . "\n\n" . print_r($v, 1) . '</pre><br/>';
	}

	public function gen_uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	public function dbExtension($string)
	{
		return (substr($string, -3) == '.db') ? $string : $string . '.db';
	}

	public function removeDbExtension($string)
	{
		return substr($string, 0, -3);
	}

	public function cleanPath($path)
	{
		$path = preg_replace('/([^:])(\/{2,})/', '$1/', $path);
		$path = rtrim($path, '/');
		return $path;
	}

	public function searchArray($array, $field, $value)
	{
		foreach ($array as $key => $item) {
			if ($item[$field] === $value)
				return $key;
		}
		return false;
	}

	public function localURL($url, $force = false)
	{
		if ($force) {
			return true;
		}
		if (strpos($url, 'https') !== false) {
			preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $url, $result);
			$result = !empty($result);
			return $result;
		}
		return false;
	}

	public function arrayIP($string)
	{
		if (strpos($string, ',') !== false) {
			$result = explode(",", $string);
		} else {
			$result = array($string);
		}
		foreach ($result as &$ip) {
			$ip = is_numeric(substr($ip, 0, 1)) ? $ip : gethostbyname($ip);
		}
		return $result;
	}

	public function timeExecution($previous = null)
	{
		if (!$previous) {
			return microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		} else {
			return (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) - $previous;
		}
	}

	public function getallheaders()
	{
		if (!function_exists('getallheaders')) {
			function getallheaders()
			{
				$headers = array();
				foreach ($_SERVER as $name => $value) {
					if (substr($name, 0, 5) == 'HTTP_') {
						$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
					}
				}
				return $headers;
			}
		} else {
			return getallheaders();
		}
	}

	public function getallheadersi()
	{
		return array_change_key_case($this->getallheaders(), CASE_LOWER);
	}

	public function random_ascii_string($length)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	// Generate Random string
	public function randString($length = 10, $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')
	{
		$tmp = '';
		for ($i = 0; $i < $length; $i++) {
			$tmp .= substr(str_shuffle($chars), 0, 1);
		}
		return $tmp;
	}

	public function isEncrypted($password = null)
	{
		return ($password == null || $password == '') ? false : $this->decrypt($password);
	}

	public function fillString($string, $length)
	{
		$filler = '0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*';
		if (strlen($string) < $length) {
			$diff = $length - strlen($string);
			$filler = substr($filler, 0, $diff);
			return $string . $filler;
		} elseif (strlen($string) > $length) {
			return substr($string, 0, $length);
		} else {
			return $string;
		}
	}

	public function userIP()
	{
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			$ipaddress = '127.0.0.1';
		}
		if (strpos($ipaddress, ',') !== false) {
			list($first, $last) = explode(",", $ipaddress);
			unset($last);
			return $first;
		} else {
			return $ipaddress;
		}
	}

	public function serverIP()
	{
		if (array_key_exists('SERVER_ADDR', $_SERVER)) {
			return $_SERVER['SERVER_ADDR'];
		}
		return '127.0.0.1';
	}

	public function parseDomain($value, $force = false)
	{
		$badDomains = array('ddns.net', 'ddnsking.com', '3utilities.com', 'bounceme.net', 'freedynamicdns.net', 'freedynamicdns.org', 'gotdns.ch', 'hopto.org', 'myddns.me', 'myds.me', 'myftp.biz', 'myftp.org', 'myvnc.com', 'noip.com', 'onthewifi.com', 'redirectme.net', 'serveblog.net', 'servecounterstrike.com', 'serveftp.com', 'servegame.com', 'servehalflife.com', 'servehttp.com', 'serveirc.com', 'serveminecraft.net', 'servemp3.com', 'servepics.com', 'servequake.com', 'sytes.net', 'viewdns.net', 'webhop.me', 'zapto.org');
		$Domain = $value;
		$Port = strpos($Domain, ':');
		if ($Port !== false) {
			$Domain = substr($Domain, 0, $Port);
			$value = $Domain;
		}
		$check = substr_count($Domain, '.');
		if ($check >= 3) {
			if (is_numeric($Domain[0])) {
				$Domain = '';
			} else {
				if (in_array(strtolower(explode('.', $Domain)[2] . '.' . explode('.', $Domain)[3]), $badDomains)) {
					$Domain = '.' . explode('.', $Domain)[0] . '.' . explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2] . '.' . explode('.', $Domain)[3];
				} else {
					$Domain = '.' . explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2] . '.' . explode('.', $Domain)[3];
				}
			}
		} elseif ($check == 2) {
			if (in_array(strtolower(explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2]), $badDomains)) {
				$Domain = '.' . explode('.', $Domain)[0] . '.' . explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2];
			} elseif (explode('.', $Domain)[0] == 'www') {
				$Domain = '.' . explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2];
			} elseif (explode('.', $Domain)[1] == 'co') {
				$Domain = '.' . explode('.', $Domain)[0] . '.' . explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2];
			} else {
				$Domain = '.' . explode('.', $Domain)[1] . '.' . explode('.', $Domain)[2];
			}
		} elseif ($check == 1) {
			$Domain = '.' . $Domain;
		} else {
			$Domain = '';
		}
		return ($force) ? $value : $Domain;
	}

	// Cookie Custom Function
	public function coookie($type, $name, $value = '', $days = -1, $http = true, $path = '/')
	{
		$days = ($days > 365) ? 365 : $days;
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") {
			$Secure = true;
			$HTTPOnly = true;
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
			$Secure = true;
			$HTTPOnly = true;
		} else {
			$Secure = false;
			$HTTPOnly = false;
		}
		if (!$http) {
			$HTTPOnly = false;
		}
		$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '';
		$Domain = $this->parseDomain($_SERVER['HTTP_HOST']);
		$DomainTest = $this->parseDomain($_SERVER['HTTP_HOST'], true);
		if ($type == 'set') {
			$_COOKIE[$name] = $value;
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $Domain)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $DomainTest)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		} elseif ($type == 'delete') {
			unset($_COOKIE[$name]);
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $Domain)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $DomainTest)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		}
	}

	public function coookieSeconds($type, $name, $value = '', $ms = null, $http = true, $path = '/')
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") {
			$Secure = true;
			$HTTPOnly = true;
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
			$Secure = true;
			$HTTPOnly = true;
		} else {
			$Secure = false;
			$HTTPOnly = false;
		}
		if (!$http) {
			$HTTPOnly = false;
		}
		$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '';
		$Domain = $this->parseDomain($_SERVER['HTTP_HOST']);
		$DomainTest = $this->parseDomain($_SERVER['HTTP_HOST'], true);
		if ($type == 'set') {
			$_COOKIE[$name] = $value;
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + ($ms / 1000)) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $Domain)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + ($ms / 1000)) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $DomainTest)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		} elseif ($type == 'delete') {
			unset($_COOKIE[$name]);
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $Domain)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
			header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
				. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
				. (empty($path) ? '' : '; path=' . $path)
				. (empty($Domain) ? '' : '; domain=' . $DomainTest)
				. (!$Secure ? '' : '; SameSite=None; Secure')
				. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		}
	}

	// Qualify URL
	public function qualifyURL($url, $return = false, $includeTrailing = false)
	{
		//local address?
		if (substr($url, 0, 1) == "/") {
			if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] != 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'http')) {
				$protocol = "https://";
			} else {
				$protocol = "http://";
			}
			$url = $protocol . $this->getServer() . $url;
		}
		// Get Digest
		$digest = $includeTrailing ? parse_url($url) : parse_url(rtrim(preg_replace('/\s+/', '', $url), '/'));
		// http/https
		if (!isset($digest['scheme'])) {
			$scheme = 'http';
		} else {
			$scheme = $digest['scheme'];
		}
		// Host
		$host = ($digest['host'] ?? '');
		// Port
		$port = (isset($digest['port']) ? ':' . $digest['port'] : '');
		// Path
		$path = ($digest['path'] ?? '');
		// Query
		$query = (isset($digest['query']) ? '?' . $digest['query'] : '');
		// Fragment
		$fragment = (isset($digest['fragment']) ? '#' . $digest['fragment'] : '');
		// Output
		$array = [
			'scheme' => $scheme,
			'host' => $host,
			'port' => $port,
			'path' => $path,
			'query' => $query,
			'fragment' => $fragment,
			'digest' => $digest
		];
		return ($return) ? $array : $scheme . '://' . $host . $port . $path . $query . $fragment;
	}

	public function getServer($over = false)
	{
		if ($over) {
			if ($this->config['PHPMAILER-domain'] !== '') {
				return $this->config['PHPMAILER-domain'];
			}
		}
		return isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"];
	}

	public function getServerPath($over = false)
	{
		if ($over) {
			if ($this->config['PHPMAILER-domain'] !== '') {
				return $this->config['PHPMAILER-domain'];
			}
		}
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") {
			$protocol = "https://";
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
			$protocol = "https://";
		} else {
			$protocol = "http://";
		}
		$domain = '';
		if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], '.') !== false) {
			$domain = $_SERVER['SERVER_NAME'];
		} elseif (isset($_SERVER['HTTP_HOST'])) {
			if (strpos($_SERVER['HTTP_HOST'], ':') !== false) {
				$domain = explode(':', $_SERVER['HTTP_HOST'])[0];
				$port = explode(':', $_SERVER['HTTP_HOST'])[1];
				if ($port !== "80" && $port !== "443") {
					$domain = $_SERVER['HTTP_HOST'];
				}
			} else {
				$domain = $_SERVER['HTTP_HOST'];
			}
		}
		$path = str_replace("\\", "/", dirname($_SERVER['REQUEST_URI']));
		$path = ($path !== '.') ? $path : '';
		$url = $protocol . $domain . $path;
		if (strpos($url, '/api') !== false) {
			$url = explode('/api', $url);
			return $url[0] . '/';
		} else {
			return $url;
		}
	}

	public function convertIPToRange($ip)
	{
		$ip = trim($ip);
		if (strpos($ip, '/') !== false) {
			$explodeIP = explode('/', $ip);
			$prefix = $explodeIP[1];
			$start_ip = $explodeIP[0];
			$explodeStart = explode('.', $start_ip);
			if (count($explodeStart) == 4) {
				$explodeStart[3] = $prefix == 32 ? $explodeStart[3] : 0;
				$start_ip = implode('.', $explodeStart);
			}
			$ip_count = 1 << (32 - $prefix);
			$start_ip_long = long2ip(ip2long($start_ip));
			$last_ip_long = long2ip(ip2long($start_ip) + $ip_count - 1);
		} elseif (substr_count($ip, '.') == 3) {
			$start_ip_long = long2ip(ip2long($ip));
			$last_ip_long = long2ip(ip2long($ip));
		} else {
			return false;
		}
		return [
			'from' => $start_ip_long,
			'to' => $last_ip_long
		];
	}

	public function convertIPStringToRange($string = null)
	{
		$ips = [];
		if ($string) {
			$ipListing = explode(',', $string);
			if (count($ipListing) > 0) {
				foreach ($ipListing as $ip) {
					$ips[] = $this->convertIPToRange($ip);
				}
			}
		}
		return $ips;
	}

	public function localIPRanges()
	{
		$mainArray = [
			[
				'from' => '10.0.0.0',
				'to' => '10.255.255.255'
			],
			[
				'from' => '172.16.0.0',
				'to' => '172.31.255.255'
			],
			[
				'from' => '192.168.0.0',
				'to' => '192.168.255.255'
			],
			[
				'from' => '127.0.0.1',
				'to' => '127.255.255.255'
			],
		];
		if (isset($this->config['localIPList'])) {
			if ($this->config['localIPList'] !== '') {
				$ipListing = explode(',', $this->config['localIPList']);
				if (count($ipListing) > 0) {
					foreach ($ipListing as $ip) {
						$ipInfo = $this->convertIPToRange($ip);
						if ($ipInfo) {
							array_push($mainArray, $ipInfo);
						}
					}
				}
			}
		}
		/*
		if ($this->config['localIPFrom']) {
			$from = trim($this->config['localIPFrom']);
			$override = true;
		}
		if ($this->config['localIPTo']) {
			$to = trim($this->config['localIPTo']);
		}
		
		if ($override) {
			$newArray = array(
				'from' => $from,
				'to' => (isset($to)) ? $to : $from
			);
			array_push($mainArray, $newArray);
		}
		*/
		return $mainArray;
	}

	public function isLocal($checkIP = null)
	{
		$isLocal = false;
		$userIP = ($checkIP) ? ip2long($checkIP) : ip2long($this->userIP());
		$range = $this->localIPRanges();
		foreach ($range as $ip) {
			$low = ip2long($ip['from']);
			$high = ip2long($ip['to']);
			if ($userIP <= $high && $low <= $userIP) {
				$isLocal = true;
			}
		}
		return $isLocal;
	}

	public function isLocalOrServer()
	{
		$isLocalOrServer = false;
		$isLocal = $this->isLocal();
		if (!$isLocal) {
			if ($this->userIP() == $this->serverIP()) {
				$isLocalOrServer = true;
			}
		} else {
			$isLocalOrServer = true;
		}
		return $isLocalOrServer;
	}

	public function human_filesize($bytes, $dec = 2)
	{
		$bytes = number_format($bytes, 0, '.', '');
		$size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$dec}f %s", $bytes / (1024 ** $factor), $size[$factor]);
	}

	public function apiResponseFormatter($response)
	{
		if (is_array($response)) {
			return $response;
		}
		if (empty($response) || $response == '') {
			return ['api_response' => 'No data'];
		}
		if ($this->json_validator($response)) {
			return json_decode($response, true);
		}
		return ['api_response' => 'No data'];
	}

	public function json_validator($data = null)
	{
		if (!empty($data)) {
			@json_decode($data);
			return (json_last_error() === JSON_ERROR_NONE);
		}
		return false;
	}

	public function replace_first($search_str, $replacement_str, $src_str)
	{
		return (false !== ($pos = strpos($src_str, $search_str))) ? substr_replace($src_str, $replacement_str, $pos, strlen($search_str)) : $src_str;
	}

	/**
	 *  Check if an array is a multidimensional array.
	 *
	 * @param array $arr The array to check
	 * @return  boolean       Whether the the array is a multidimensional array or not
	 */
	public function is_multi_array($x)
	{
		if (count(array_filter($x, 'is_array')) > 0) return true;
		return false;
	}

	/**
	 *  Convert an object to an array.
	 *
	 * @param array $object The object to convert
	 * @return  array            The converted array
	 */
	public function object_to_array($object)
	{
		if (!is_object($object) && !is_array($object)) return $object;
		return array_map(array($this, 'object_to_array'), (array)$object);
	}

	/**
	 *  Check if a value exists in the array/object.
	 *
	 * @param mixed $needle The value that you are searching for
	 * @param mixed $haystack The array/object to search
	 * @param boolean $strict Whether to use strict search or not
	 * @return  boolean             Whether the value was found or not
	 */
	public function search_for_value($needle, $haystack, $strict = true)
	{
		$haystack = $this->object_to_array($haystack);
		if (is_array($haystack)) {
			if ($this->is_multi_array($haystack)) {   // Multidimensional array
				foreach ($haystack as $subhaystack) {
					if ($this->search_for_value($needle, $subhaystack, $strict)) {
						return true;
					}
				}
			} elseif (array_keys($haystack) !== range(0, count($haystack) - 1)) {    // Associative array
				foreach ($haystack as $key => $val) {
					if ($needle == $val && !$strict) {
						return true;
					} elseif ($needle === $val && $strict) {
						return true;
					}
				}
				return false;
			} else {    // Normal array
				if ($needle == $haystack && !$strict) {
					return true;
				} elseif ($needle === $haystack && $strict) {
					return true;
				}
			}
		}
		return false;
	}

	public function makeDir($dirPath, $mode = 0777)
	{
		return is_dir($dirPath) || @mkdir($dirPath, $mode, true);
	}
}

// Leave for deluge class
function getCert()
{
	$url = 'http://curl.haxx.se/ca/cacert.pem';
	$file = __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert.pem';
	$file2 = __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert-initial.pem';
	$useCert = (file_exists($file)) ? $file : $file2;
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

// Leave for deluge class
function localURL($url, $force = false)
{
	if ($force) {
		return true;
	}
	if (strpos($url, 'https') !== false) {
		preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $url, $result);
		$result = (!empty($result) ? true : false);
		return $result;
	}
	return false;
}

// Maybe use later?
function curl($curl, $url, $headers = array(), $data = array())
{
	// Initiate cURL
	$curlReq = curl_init($url);
	if (in_array(trim(strtoupper($curl)), ["GET", "POST", "PUT", "DELETE"])) {
		curl_setopt($curlReq, CURLOPT_CUSTOMREQUEST, trim(strtoupper($curl)));
	} else {
		return null;
	}
	curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curlReq, CURLOPT_CAINFO, getCert());
	curl_setopt($curlReq, CURLOPT_CONNECTTIMEOUT, 5);
	if (localURL($url)) {
		curl_setopt($curlReq, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curlReq, CURLOPT_SSL_VERIFYPEER, 0);
	}
	// Format Headers
	$cHeaders = array();
	foreach ($headers as $k => $v) {
		$cHeaders[] = $k . ': ' . $v;
	}
	if (count($cHeaders)) {
		curl_setopt($curlReq, CURLOPT_HTTPHEADER, $cHeaders);
	}
	// Format Data
	switch (isset($headers['Content-Type']) ? $headers['Content-Type'] : '') {
		case 'application/json':
			curl_setopt($curlReq, CURLOPT_POSTFIELDS, json_encode($data));
			break;
		case 'application/x-www-form-urlencoded':
			curl_setopt($curlReq, CURLOPT_POSTFIELDS, http_build_query($data));
			break;
		default:
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
			curl_setopt($curlReq, CURLOPT_POSTFIELDS, http_build_query($data));
	}
	// Execute
	$result = curl_exec($curlReq);
	$httpcode = curl_getinfo($curlReq);
	// Close
	curl_close($curlReq);
	// Return
	return array('content' => $result, 'http_code' => $httpcode);
}

// Maybe use later?
function getHeaders($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_CAINFO, getCert());
	if (localURL($url)) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	}
	curl_exec($ch);
	$headers = curl_getinfo($ch);
	curl_close($ch);
	return $headers;
}

// Maybe use later?
function download($url, $path)
{
	ini_set('max_execution_time', 0);
	set_time_limit(0);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_CAINFO, getCert());
	if (localURL($url)) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	}
	$raw_file_data = curl_exec($ch);
	curl_close($ch);
	file_put_contents($path, $raw_file_data);
	return (filesize($path) > 0) ? true : false;
}

// swagger
function getServerPath($over = false)
{
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") {
		$protocol = "https://";
	} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$protocol = "https://";
	} else {
		$protocol = "http://";
	}
	$domain = '';
	if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], '.') !== false) {
		$domain = $_SERVER['SERVER_NAME'];
	} elseif (isset($_SERVER['HTTP_HOST'])) {
		if (strpos($_SERVER['HTTP_HOST'], ':') !== false) {
			$domain = explode(':', $_SERVER['HTTP_HOST'])[0];
			$port = explode(':', $_SERVER['HTTP_HOST'])[1];
			if ($port !== "80" && $port !== "443") {
				$domain = $_SERVER['HTTP_HOST'];
			}
		} else {
			$domain = $_SERVER['HTTP_HOST'];
		}
	}
	$url = $protocol . $domain . str_replace("\\", "/", dirname($_SERVER['REQUEST_URI']));
	if (strpos($url, '/api') !== false) {
		$url = explode('/api', $url);
		return $url[0] . '/';
	} else {
		return $url;
	}
}

// used for api return
function safe_json_encode($value, $options = 0, $depth = 512)
{
	$encoded = json_encode($value, $options, $depth);
	if ($encoded === false && $value && json_last_error() == JSON_ERROR_UTF8) {
		$encoded = json_encode(utf8ize($value), $options, $depth);
	}
	return $encoded;
}

// used for api return
function utf8ize($mixed)
{
	if (is_array($mixed)) {
		foreach ($mixed as $key => $value) {
			$mixed[$key] = utf8ize($value);
		}
	} elseif (is_string($mixed)) {
		return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
	}
	return $mixed;
}
