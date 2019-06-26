<?php
// Print output all purrty
function prettyPrint($v)
{
	$trace = debug_backtrace()[0];
	echo '<pre style="white-space: pre; text-overflow: ellipsis; overflow: hidden; background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">' . $trace['file'] . ':' . $trace['line'] . ' ' . gettype($v) . "\n\n" . print_r($v, 1) . '</pre><br/>';
}

// Clean Directory string
function cleanDirectory($path)
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

// Get Gravatar Email Image
function gravatar($email = '')
{
	$email = md5(strtolower(trim($email)));
	$gravurl = "https://www.gravatar.com/avatar/$email?s=100&d=mm";
	return $gravurl;
}

function parseDomain($value, $force = false)
{
	$badDomains = array('ddns.net', 'ddnsking.com', '3utilities.com', 'bounceme.net', 'duckdns.org', 'freedynamicdns.net', 'freedynamicdns.org', 'gotdns.ch', 'hopto.org', 'myddns.me', 'myds.me', 'myftp.biz', 'myftp.org', 'myvnc.com', 'noip.com', 'onthewifi.com', 'redirectme.net', 'serveblog.net', 'servecounterstrike.com', 'serveftp.com', 'servegame.com', 'servehalflife.com', 'servehttp.com', 'serveirc.com', 'serveminecraft.net', 'servemp3.com', 'servepics.com', 'servequake.com', 'sytes.net', 'viewdns.net', 'webhop.me', 'zapto.org');
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
	/*
	if (is_numeric($Domain[0]) || strpos($Domain, '.') == false) {
		$Domain = '';
	} else {
		if (substr($Domain, 0, 3) == 'www') {
			$Domain = substr($Domain, 3, strlen($Domain) - 3);
		} else {
			$Domain = '.' . $Domain;
		}
	}
	*/
	return ($force) ? $value : $Domain;
}

// Cookie Custom Function
function coookie($type, $name, $value = '', $days = -1, $http = true)
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
	$Path = '/';
	$Domain = parseDomain($_SERVER['HTTP_HOST']);
	$DomainTest = parseDomain($_SERVER['HTTP_HOST'], true);
	if ($type == 'set') {
		$_COOKIE[$name] = $value;
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $Domain)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $DomainTest)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	} elseif ($type == 'delete') {
		unset($_COOKIE[$name]);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $Domain)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $DomainTest)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}
}

function coookieSeconds($type, $name, $value = '', $ms, $http = true)
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
	$Path = '/';
	$Domain = parseDomain($_SERVER['HTTP_HOST']);
	$DomainTest = parseDomain($_SERVER['HTTP_HOST'], true);
	if ($type == 'set') {
		$_COOKIE[$name] = $value;
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + ($ms / 1000)) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $Domain)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + ($ms / 1000)) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $DomainTest)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	} elseif ($type == 'delete') {
		unset($_COOKIE[$name]);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $Domain)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
			. (empty($ms) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
			. (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $DomainTest)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}
}

function getOS()
{
	if (PHP_SHLIB_SUFFIX == "dll") {
		return "win";
	} else {
		return "*nix";
	}
}

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
}
function random_ascii_string($length)
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
function randString($length = 10, $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
	$tmp = '';
	for ($i = 0; $i < $length; $i++) {
		$tmp .= substr(str_shuffle($chars), 0, 1);
	}
	return $tmp;
}

function isEncrypted($password)
{
	switch (strlen($password)) {
		case '24':
			return (strpos($password, '==') !== false) ? true : false;
			break;
		case '44':
			return (substr($password, -1, 1) == '=') ? true : false;
			break;
		case '64':
			return true;
		case '88':
			return (strpos($password, '==') !== false) ? true : false;
			break;
		case '108':
			return (substr($password, -1, 1) == '=') ? true : false;
			break;
		default:
			return false;
	}
}

function encrypt($password, $key = null)
{
	$key = (isset($GLOBALS['organizrHash'])) ? $GLOBALS['organizrHash'] : $key;
	return openssl_encrypt($password, 'AES-256-CBC', $key, 0, fillString($key, 16));
}

function decrypt($password, $key = null)
{
	if (empty($password)) {
		return '';
	}
	$key = (isset($GLOBALS['organizrHash'])) ? $GLOBALS['organizrHash'] : $key;
	return openssl_decrypt($password, 'AES-256-CBC', $key, 0, fillString($key, 16));
}

function fillString($string, $length)
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

function userIP()
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
		$ipaddress = 'UNKNOWN';
	}
	if (strpos($ipaddress, ',') !== false) {
		list($first, $last) = explode(",", $ipaddress);
		unset($last);
		return $first;
	} else {
		return $ipaddress;
	}
}

function arrayIP($string)
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

function getCert()
{
	$url = 'http://curl.haxx.se/ca/cacert.pem';
	$file = __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cacert.pem';
	if (!file_exists($file)) {
		file_put_contents($file, fopen($url, 'r'));
	} elseif (file_exists($file) && time() - 2592000 > filemtime($file)) {
		file_put_contents($file, fopen($url, 'r'));
	}
	return $file;
}

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

function download($url, $path)
{
	ini_set('max_execution_time', 0);
	set_time_limit(0);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
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

function localURL($url)
{
	if (strpos($url, 'https') !== false) {
		preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $url, $result);
		$result = (!empty($result) ? true : false);
		return $result;
	}
	return false;
}

function array_filter_key(array $array, $callback)
{
	$matchedKeys = array_filter(array_keys($array), $callback);
	return array_intersect_key($array, array_flip($matchedKeys));
}

function searchArray($array, $field, $value)
{
	foreach ($array as $key => $item) {
		if ($item[$field] === $value)
			return $key;
	}
	return false;
}

// Qualify URL
function qualifyURL($url, $return = false)
{
	//local address?
	if (substr($url, 0, 1) == "/") {
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
			$protocol = "https://";
		} else {
			$protocol = "http://";
		}
		$url = $protocol . getServer() . $url;
	}
	// Get Digest
	$digest = parse_url(rtrim(preg_replace('/\s+/', '', $url), '/'));
	// http/https
	if (!isset($digest['scheme'])) {
		$scheme = 'http';
	} else {
		$scheme = $digest['scheme'];
	}
	// Host
	$host = (isset($digest['host']) ? $digest['host'] : '');
	// Port
	$port = (isset($digest['port']) ? ':' . $digest['port'] : '');
	// Path
	$path = (isset($digest['path']) ? $digest['path'] : '');
	// Output
	$array = array(
		'scheme' => $scheme,
		'host' => $host,
		'port' => $port,
		'path' => $path
	);
	return ($return) ? $array : $scheme . '://' . $host . $port . $path;
}

function getServerPath($over = false)
{
	if ($over) {
		if ($GLOBALS['PHPMAILER-domain'] !== '') {
			return $GLOBALS['PHPMAILER-domain'];
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
	$url = $protocol . $domain . str_replace("\\", "/", dirname($_SERVER['REQUEST_URI']));
	if (strpos($url, '/api') !== false) {
		$url = explode('/api', $url);
		return $url[0] . '/';
	} else {
		return $url;
	}
}

function get_browser_name()
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

function getServer($over = false)
{
	if ($over) {
		if ($GLOBALS['PHPMAILER-domain'] !== '') {
			return $GLOBALS['PHPMAILER-domain'];
		}
	}
	return isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"];
}

/* Function is to get all the contents from ics and explode all the datas according to the events and its sections */
function getIcsEventsAsArray($file)
{
	$icalString = file_get_contents_curl($file);
	$icsDates = array();
	/* Explode the ICs Data to get datas as array according to string ‘BEGIN:’ */
	$icsData = explode("BEGIN:", $icalString);
	/* Iterating the icsData value to make all the start end dates as sub array */
	foreach ($icsData as $key => $value) {
		$icsDatesMeta [$key] = explode("\n", $value);
	}
	/* Itearting the Ics Meta Value */
	foreach ($icsDatesMeta as $key => $value) {
		foreach ($value as $subKey => $subValue) {
			/* to get ics events in proper order */
			$icsDates = getICSDates($key, $subKey, $subValue, $icsDates);
		}
	}
	return $icsDates;
}

/* funcion is to avaid the elements wich is not having the proper start, end  and summary informations */
function getICSDates($key, $subKey, $subValue, $icsDates)
{
	if ($key != 0 && $subKey == 0) {
		$icsDates [$key] ["BEGIN"] = $subValue;
	} else {
		$subValueArr = explode(":", $subValue, 2);
		if (isset ($subValueArr [1])) {
			$icsDates [$key] [$subValueArr [0]] = $subValueArr [1];
		}
	}
	return $icsDates;
}

function file_get_contents_curl($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function getExtension($string)
{
	return preg_replace("#(.+)?\.(\w+)(\?.+)?#", "$2", $string);
}

function safe_json_encode($value, $options = 0, $depth = 512)
{
	$encoded = json_encode($value, $options, $depth);
	if ($encoded === false && $value && json_last_error() == JSON_ERROR_UTF8) {
		$encoded = json_encode(utf8ize($value), $options, $depth);
	}
	return $encoded;
}

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

function gen_uuid()
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

function dbExtension($string)
{
	return (substr($string, -3) == '.db') ? $string : $string . '.db';
}

function localIPRanges()
{
	$mainArray = array(
		array(
			'from' => '10.0.0.0',
			'to' => '10.255.255.255'
		),
		array(
			'from' => '172.16.0.0',
			'to' => '172.31.255.255'
		),
		array(
			'from' => '192.168.0.0',
			'to' => '192.168.255.255'
		),
		array(
			'from' => '127.0.0.1',
			'to' => '127.255.255.255'
		),
	);
	$override = false;
	if ($GLOBALS['localIPFrom']) {
		$from = trim($GLOBALS['localIPFrom']);
		$override = true;
	}
	if ($GLOBALS['localIPTo']) {
		$to = trim($GLOBALS['localIPTo']);
	}
	if ($override) {
		$newArray = array(
			'from' => $from,
			'to' => (isset($to)) ? $to : $from
		);
		array_push($mainArray, $newArray);
	}
	return $mainArray;
}

function isLocal($checkIP = null)
{
	$isLocal = false;
	$userIP = ($checkIP) ? ip2long($checkIP) : ip2long(userIP());
	$range = localIPRanges();
	foreach ($range as $ip) {
		$low = ip2long($ip['from']);
		$high = ip2long($ip['to']);
		if ($userIP <= $high && $low <= $userIP) {
			$isLocal = true;
		}
	}
	return $isLocal;
}

function checkOverrideURL($url, $override)
{
	if (strpos($override, $url) !== false) {
		return $override;
	} else {
		return $url . $override;
	}
}

function clearPOSTPassword($array)
{
	if (isset($array['data'])) {
		foreach ($array['data'] as $k => $v) {
			// clear password from array
			if ($k == 'password') {
				$array['data'][$k] = '*******';
			}
		}
	}
	if (isset($array['data']['data'])) {
		foreach ($array['data']['data'] as $k => $v) {
			// clear password from array
			if ($k == 'password') {
				$array['data']['data'][$k] = '*******';
			}
		}
	}
	return $array;
}

function timeExecution($previous = null)
{
	if (!$previous) {
		return microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
	} else {
		return (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) - $previous;
	}
}

function formatSeconds($seconds)
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