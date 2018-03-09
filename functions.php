<?php

// ===================================
// Define Version
 define('INSTALLEDVERSION', '1.75');
// ===================================
$debugOrganizr = true;
if($debugOrganizr == true && file_exists('debug.php')){ require_once('debug.php'); }
use Kryptonit3\Sonarr\Sonarr;
use Kryptonit3\SickRage\SickRage;
//homepage order
function homepageOrder(){
	$homepageOrder = array(
		"homepageOrdercustomhtml" => homepageOrdercustomhtml,
		"homepageOrdernotice" => homepageOrdernotice,
		"homepageOrderplexsearch" => homepageOrderplexsearch,
		"homepageOrderspeedtest" => homepageOrderspeedtest,
		"homepageOrdernzbget" => homepageOrdernzbget,
		"homepageOrdersabnzbd" => homepageOrdersabnzbd,
		"homepageOrderplexnowplaying" => homepageOrderplexnowplaying,
		"homepageOrderplexrecent" => homepageOrderplexrecent,
		"homepageOrderplexplaylist" => homepageOrderplexplaylist,
		"homepageOrderembynowplaying" => homepageOrderembynowplaying,
		"homepageOrderembyrecent" => homepageOrderembyrecent,
		"homepageOrderombi" => homepageOrderombi,
		"homepageOrdercalendar" => homepageOrdercalendar,
		"homepageOrdernoticeguest" => homepageOrdernoticeguest,
		"homepageOrdertransmisson" => homepageOrdertransmisson,
	);
	asort($homepageOrder);
	return $homepageOrder;
}
// Debugging output functions
function debug_out($variable, $die = false) {
	$trace = debug_backtrace()[0];
	echo "<center><img height='200px' src='images/confused.png'></center>";
	echo "<center>Look's like something happened, here are the errors and perhaps how to fix them:</center>";
	echo '<pre style="white-space: pre-line; background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">'.$trace['file'].':'.$trace['line']."\n\n".print_r($variable, true).'</pre>';
	if ($die) { http_response_code(503); die(); }
}

//Cookie Function
function coookie($type, $name, $value = '', $days = -1, $http = true){
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https"){
		$Secure = true;
 	   	$HTTPOnly = true;
	}elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$Secure = true;
 	   	$HTTPOnly = true;
	} else {
		$Secure = false;
 	   	$HTTPOnly = false;
   }
   if(!$http){ $HTTPOnly = false; }
	$Path = '/';
	$Domain = $_SERVER['HTTP_HOST'];
	$Port = strpos($Domain, ':');
	if ($Port !== false)  $Domain = substr($Domain, 0, $Port);
	$Port = strpos($Domain, ':');
	$check = substr_count($Domain, '.');
	if($check >= 3){
		if(is_numeric($Domain[0])){
			$Domain = '';
		}else{
			$Domain = '.'.explode('.',$Domain)[1].'.'.explode('.',$Domain)[2].'.'.explode('.',$Domain)[3];
		}
	}elseif($check == 2){
		$Domain = '.'.explode('.',$Domain)[1].'.'.explode('.',$Domain)[2];
	}elseif($check == 1){
		$Domain = '.' . $Domain;
	}else{
		$Domain = '';
	}
	if($type = 'set'){
		$_COOKIE[$name] = $value;
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
							. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
							. (empty($Path) ? '' : '; path=' . $Path)
							. (empty($Domain) ? '' : '; domain=' . $Domain)
							. (!$Secure ? '' : '; secure')
							. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}elseif($type = 'delete'){
		unset($_COOKIE[$name]);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
							. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
							. (empty($Path) ? '' : '; path=' . $Path)
							. (empty($Domain) ? '' : '; domain=' . $Domain)
							. (!$Secure ? '' : '; secure')
							. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}

}
// ==== Auth Plugins START ====
if (function_exists('ldap_connect')) :
	// Pass credentials to LDAP backend
	function plugin_auth_ldap($username, $password) {
		$ldapServers = explode(',',AUTHBACKENDHOST);
		foreach($ldapServers as $key => $value) {
			// Calculate parts
			$digest = parse_url(trim($value));
			$scheme = strtolower((isset($digest['scheme'])?$digest['scheme']:'ldap'));
			$host = (isset($digest['host'])?$digest['host']:(isset($digest['path'])?$digest['path']:''));
			$port = (isset($digest['port'])?$digest['port']:(strtolower($scheme)=='ldap'?389:636));

			// Reassign
			$ldapServers[$key] = $scheme.'://'.$host.':'.$port;
		}

		// returns true or false
		$ldap = ldap_connect(implode(' ',$ldapServers));
		if(empty(AUTHBACKENDDOMAINFORMAT)){
			if ($bind = ldap_bind($ldap, AUTHBACKENDDOMAIN.'\\'.$username, $password)) {
				writeLog("success", "LDAP authentication success");
				return true;
			} else {
				writeLog("error", "LDAP could not authenticate");
				return false;
			}
		}else{
			ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
			$bind = @ldap_bind($ldap, sprintf(AUTHBACKENDDOMAINFORMAT, $username), $password);
			if ($bind) {
				writeLog("success", "LDAP authentication success");
				return true;
			} else {
				writeLog("error", "LDPA could not authenticate");
				return false;
			}
		}
  		writeLog("error", "LDAP could not authenticate");
		return false;
	}
else :
	// Ldap Auth Missing Dependancy
	function plugin_auth_ldap_disabled() {
		return 'LDAP - Disabled (Dependancy: php-ldap missing!)';
	}
endif;

// Pass credentials to FTP backend
function plugin_auth_ftp($username, $password) {
	// Calculate parts
	$digest = parse_url(AUTHBACKENDHOST);
	$scheme = strtolower((isset($digest['scheme'])?$digest['scheme']:(function_exists('ftp_ssl_connect')?'ftps':'ftp')));
	$host = (isset($digest['host'])?$digest['host']:(isset($digest['path'])?$digest['path']:''));
	$port = (isset($digest['port'])?$digest['port']:21);

	// Determine Connection Type
	if ($scheme == 'ftps') {
		$conn_id = ftp_ssl_connect($host, $port, 20);
	} elseif ($scheme == 'ftp') {
		$conn_id = ftp_connect($host, $port, 20);
	} else {
		debug_out('Invalid FTP scheme. Use ftp or ftps');
  		writeLog("error", "invalid FTP scheme");
		return false;
	}

	// Check if valid FTP connection
	if ($conn_id) {
		// Attempt login
		@$login_result = ftp_login($conn_id, $username, $password);
		ftp_close($conn_id);

		// Return Result
		if ($login_result) {
   			writeLog("success", "$username authenticated");
			return true;
		} else {
   			writeLog("error", "$username could not authenticate");
			return false;
		}
	} else {
		return false;
	}
	return false;
}

// Pass credentials to Emby Backend
function plugin_auth_emby_local($username, $password) {
	$embyAddress = qualifyURL(EMBYURL);

	$headers = array(
		'Authorization'=> 'MediaBrowser UserId="e8837bc1-ad67-520e-8cd2-f629e3155721", Client="None", Device="Organizr", DeviceId="xxx", Version="1.0.0.0"',
		'Content-Type' => 'application/json',
	);
	$body = array(
		'Username' => $username,
		'Password' => sha1($password),
		'PasswordMd5' => md5($password),
	);

	$response = post_router($embyAddress.'/Users/AuthenticateByName', $body, $headers);

	if (isset($response['content'])) {
		$json = json_decode($response['content'], true);
		if (is_array($json) && isset($json['SessionInfo']) && isset($json['User']) && $json['User']['HasPassword'] == true) {
			// Login Success - Now Logout Emby Session As We No Longer Need It
			$headers = array(
				'X-Mediabrowser-Token' => $json['AccessToken'],
			);
			$response = post_router($embyAddress.'/Sessions/Logout', array(), $headers);
			return true;
		}
	}
	return false;
}

if (function_exists('curl_version')) :
	// Authenticate Against Emby Local (first) and Emby Connect
	function plugin_auth_emby_all($username, $password) {
		$localResult = plugin_auth_emby_local($username, $password);
		if ($localResult) {
			return $localResult;
		} else {
			return plugin_auth_emby_connect($username, $password);
		}
	}

	// Authenicate against emby connect
	function plugin_auth_emby_connect($username, $password) {
		$embyAddress = qualifyURL(EMBYURL);

		// Get A User
		$connectId = '';
		$userIds = json_decode(@file_get_contents($embyAddress.'/Users?api_key='.EMBYTOKEN),true);
		if (is_array($userIds)) {
			foreach ($userIds as $key => $value) { // Scan for this user
				if (isset($value['ConnectUserName']) && isset($value['ConnectUserId'])) { // Qualifty as connect account
					if ($value['ConnectUserName'] == $username || $value['Name'] == $username) {
						$connectId = $value['ConnectUserId'];
						break;
					}

				}
			}

			if ($connectId) {
				$connectURL = 'https://connect.emby.media/service/user/authenticate';
				$headers = array(
					'Accept'=> 'application/json',
					'Content-Type' => 'application/x-www-form-urlencoded',
				);
				$body = array(
					'nameOrEmail' => $username,
					'rawpw' => $password,
				);

				$result = curl_post($connectURL, $body, $headers);

				if (isset($result['content'])) {
					$json = json_decode($result['content'], true);
					if (is_array($json) && isset($json['AccessToken']) && isset($json['User']) && $json['User']['Id'] == $connectId) {
						return array(
							'email' => $json['User']['Email'],
							'image' => $json['User']['ImageUrl'],
						);
					}
				}
			}
		}

		return false;
	}

	// Pass credentials to Plex Backend
	function plugin_auth_plex($username, $password) {
		// Quick out
		$isAdmin = false;
		if ((strtolower(PLEXUSERNAME) == strtolower($username)) && $password == PLEXPASSWORD) {
   			writeLog("success", "Admin: ".$username." authenticated as plex Admin");
			$isAdmin = true;
		}

		//Get User List
		$userURL = 'https://plex.tv/pms/friends/all';
		$userHeaders = array(
			'Authorization' => 'Basic '.base64_encode(PLEXUSERNAME.':'.PLEXPASSWORD),
		);
		libxml_use_internal_errors(true);
		$userXML = simplexml_load_string(curl_get($userURL, $userHeaders));

		if (is_array($userXML) || is_object($userXML)) {
			$isUser = false;
			$usernameLower = strtolower($username);
			foreach($userXML AS $child) {
				if(isset($child['username']) && strtolower($child['username']) == $usernameLower || isset($child['email']) && strtolower($child['email']) == $usernameLower) {
					$isUser = true;
     				writeLog("success", $usernameLower." was found in plex friends list");
					break;
				}
			}

			if ($isUser || $isAdmin) {
				//Login User
				$connectURL = 'https://plex.tv/users/sign_in.json';
				$headers = array(
					'Accept'=> 'application/json',
					'Content-Type' => 'application/x-www-form-urlencoded',
					'X-Plex-Product' => 'Organizr',
					'X-Plex-Version' => '1.0',
					'X-Plex-Client-Identifier' => '01010101-10101010',
				);
				$body = array(
					'user[login]' => $username,
					'user[password]' => $password,
				);
				$result = curl_post($connectURL, $body, $headers);
				if (isset($result['content'])) {
					$json = json_decode($result['content'], true);
					if ((is_array($json) && isset($json['user']) && isset($json['user']['username'])) && strtolower($json['user']['username']) == $usernameLower || strtolower($json['user']['email']) == $usernameLower) {
						writeLog("success", $json['user']['username']." was logged into organizr using plex credentials");
                        return array(
							'email' => $json['user']['email'],
							'image' => $json['user']['thumb'],
							'token' => $json['user']['authToken'],
							'type' => $isAdmin ? 'admin' : 'user',
						);
					}
				}else{
					writeLog("error", "error occured while trying to sign $username into plex");
				}
			}else{
				writeLog("error", "$username is not an authorized PLEX user or entered invalid password");
			}
		}else{
  			writeLog("error", "error occured logging into plex might want to check curl.cainfo=/path/to/downloaded/cacert.pem in php.ini");
		}
		return false;
	}
else :
	// Plex Auth Missing Dependancy
	function plugin_auth_plex_disabled() {
		return 'Plex - Disabled (Dependancy: php-curl missing!)';
	}

	// Emby Connect Auth Missing Dependancy
	function plugin_auth_emby_connect_disabled() {
		return 'Emby Connect - Disabled (Dependancy: php-curl missing!)';
	}

	// Emby Both Auth Missing Dependancy
	function plugin_auth_emby_both_disabled() {
		return 'Emby Both - Disabled (Dependancy: php-curl missing!)';
	}
endif;
// ==== Auth Plugins END ====
// ==== General Class Definitions START ====
$userLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : "en";
class setLanguage {
    private $language = null;
	   private $langCode = null;

	   function __construct($language = false) {
        // Default
        if (!$language) {
            $language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : "en";
        }

        if (!file_exists("lang/{$language}.ini")) {
            $language = 'en';
        }

        $this->langCode = $language;

        $this->language = parse_ini_file("lang/{$language}.ini", false, INI_SCANNER_RAW);
        if (file_exists("lang/{$language}.cust.ini")) {
            foreach($tmp = parse_ini_file("lang/{$language}.cust.ini", false, INI_SCANNER_RAW) as $k => $v) {
                $this->language[$k] = $v;
            }
        }
    }


	public function getLang() {
		return $this->langCode;
	}

    public function translate($originalWord) {
        $getArg = func_num_args();
        if ($getArg > 1) {
            $allWords = func_get_args();
            array_shift($allWords);
        } else {
            $allWords = array();
        }

        $translatedWord = isset($this->language[$originalWord]) ? $this->language[$originalWord] : null;
        if (!$translatedWord) {
			return ucwords(str_replace("_", " ", strtolower($originalWord)));
			//echo "WHOA!!!!!!! $originalWord";
        }

        $translatedWord = htmlspecialchars($translatedWord, ENT_QUOTES);

        return vsprintf($translatedWord, $allWords);
    }
}
$language = new setLanguage;
// ==== General Class Definitions END ====

// Direct request to curl if it exists, otherwise handle if not HTTPS
function post_router($url, $data, $headers = array(), $referer='') {
	if (function_exists('curl_version')) {
		return curl_post($url, $data, $headers, $referer);
	} else {
		return post_request($url, $data, $headers, $referer);
	}
}

if (function_exists('curl_version')) :
	// Curl Post
	function curl_post($url, $data, $headers = array(), $referer='') {
		// Initiate cURL
		$curlReq = curl_init($url);
		// As post request
		curl_setopt($curlReq, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlReq, CURLOPT_CAINFO, getCert());
		if(localURL($url)){
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYPEER, 0);
		}
		// Format Data
		switch (isset($headers['Content-Type'])?$headers['Content-Type']:'') {
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
		// Format Headers
		$cHeaders = array();
		foreach ($headers as $k => $v) {
			$cHeaders[] = $k.': '.$v;
		}
		if (count($cHeaders)) {
			curl_setopt($curlReq, CURLOPT_HTTPHEADER, $cHeaders);
		}
		// Execute
		$result = curl_exec($curlReq);
		$httpcode = curl_getinfo($curlReq);
		// Close
		curl_close($curlReq);
		// Return
		return array('content'=>$result, 'http_code'=>$httpcode);
	}

	// Curl Put
	function curl_put($url, $data, $headers = array(), $referer='') {
		// Initiate cURL
		$curlReq = curl_init($url);
		// As post request
		curl_setopt($curlReq, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlReq, CURLOPT_CAINFO, getCert());
		if(localURL($url)){
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYPEER, 0);
		}
		// Format Data
		switch (isset($headers['Content-Type'])?$headers['Content-Type']:'') {
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
		// Format Headers
		$cHeaders = array();
		foreach ($headers as $k => $v) {
			$cHeaders[] = $k.': '.$v;
		}
		if (count($cHeaders)) {
			curl_setopt($curlReq, CURLOPT_HTTPHEADER, $cHeaders);
		}
		// Execute
		$result = curl_exec($curlReq);
		$httpcode = curl_getinfo($curlReq);
		// Close
		curl_close($curlReq);
		// Return
		return array('content'=>$result, 'http_code'=>$httpcode);
	}

	//Curl Get Function
	function curl_get($url, $headers = array()) {
		// Initiate cURL
		$curlReq = curl_init($url);
		// As post request
		curl_setopt($curlReq, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlReq, CURLOPT_CAINFO, getCert());
  		curl_setopt($curlReq, CURLOPT_CONNECTTIMEOUT, 5);
		if(localURL($url)){
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYPEER, 0);
		}
		// Format Headers
		$cHeaders = array();
		foreach ($headers as $k => $v) {
			$cHeaders[] = $k.': '.$v;
		}
		if (count($cHeaders)) {
			curl_setopt($curlReq, CURLOPT_HTTPHEADER, $cHeaders);
		}
		// Execute
		$result = curl_exec($curlReq);
		// Close
		curl_close($curlReq);
		// Return
		return $result;
	}

	//Curl Delete Function
	function curl_delete($url, $headers = array()) {
		// Initiate cURL
		$curlReq = curl_init($url);
		// As post request
		curl_setopt($curlReq, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
  		curl_setopt($curlReq, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curlReq, CURLOPT_CAINFO, getCert());
		if(localURL($url)){
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curlReq, CURLOPT_SSL_VERIFYPEER, 0);
		}
		// Format Headers
		$cHeaders = array();
		foreach ($headers as $k => $v) {
			$cHeaders[] = $k.': '.$v;
		}
		if (count($cHeaders)) {
			curl_setopt($curlReq, CURLOPT_HTTPHEADER, $cHeaders);
		}
		// Execute
		$result = curl_exec($curlReq);
		$httpcode = curl_getinfo($curlReq);
		// Close
		curl_close($curlReq);
		// Return
		return array('content'=>$result, 'http_code'=>$httpcode);
	}
endif;

//Case-Insensitive Function
function in_arrayi($needle, $haystack) {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

// HTTP post request (Removes need for curl, probably useless)
function post_request($url, $data, $headers = array(), $referer='') {
	// Adapted from http://stackoverflow.com/a/28387011/6810513

    // Convert the data array into URL Parameters like a=b&foo=bar etc.
	if (isset($headers['Content-Type'])) {
		switch ($headers['Content-Type']) {
			case 'application/json':
				$data = json_encode($data);
				break;
			case 'application/x-www-form-urlencoded':
				$data = http_build_query($data);
				break;
		}
	} else {
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		$data = http_build_query($data);
	}

    // parse the given URL
    $urlDigest = parse_url($url);

    // extract host and path:
    $host = $urlDigest['host'];
    $path = $urlDigest['path'];

    if ($urlDigest['scheme'] != 'http') {
        die('Error: Only HTTP request are supported, please use cURL to add HTTPS support! ('.$urlDigest['scheme'].'://'.$host.')');
    }

    // open a socket connection on port 80 - timeout: 30 sec
    $fp = fsockopen($host, (isset($urlDigest['port'])?':'.$urlDigest['port']:80), $errno, $errstr, 30);

    if ($fp){

        // send the request headers:
        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");

        if ($referer != '')
            fputs($fp, "Referer: $referer\r\n");

        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
		foreach($headers as $k => $v) {
			fputs($fp, $k.": ".$v."\r\n");
		}
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }
    }
    else {
        return array(
            'status' => 'err',
            'error' => "$errstr ($errno)"
        );
    }

    // close the socket connection:
    fclose($fp);

    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);

    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    // return as structured array:
    return array(
        'status' => 'ok',
        'header' => $header,
        'content' => $content,
	);
}

// Format item from Emby for Carousel
function resolveEmbyItem($address, $token, $item, $nowPlaying = false, $showNames = false, $role = false, $moreInfo = false) {
	// Static Height
	$height = 444;

	// Get Item Details
	$itemDetails = json_decode(@file_get_contents($address.'/Items?Ids='.$item['Id'].'&api_key='.$token),true)['Items'][0];
	/*if (substr_count(EMBYURL, ':') == 2) {
		$URL = "http://app.emby.media/itemdetails.html?id=".$itemDetails['Id'];
	}else{
		$URL = EMBYURL."/web/itemdetails.html?id=".$itemDetails['Id'];
	}*/
	$URL = EMBYURL."/web/itemdetails.html?id=".$itemDetails['Id'];
	switch ($itemDetails['Type']) {
		case 'Episode':
		case 'Series':
			$title = (isset($itemDetails['SeriesName'])?$itemDetails['SeriesName']:"");
			$imageId = (isset($itemDetails['SeriesId'])?$itemDetails['SeriesId']:$itemDetails['Id']);
			$width = 300;
			$style = '';
			$image = 'slick-image-tall';
			if(!$nowPlaying){
				$imageType = (isset($itemDetails['ImageTags']['Primary']) ? "Primary" : false);
				$key = $itemDetails['Id'] . "-list";
				$itemType = 'season';
			}else{
				$height = 281;
				$width = 500;
				$imageId = isset($itemDetails['ParentThumbItemId']) ?	$itemDetails['ParentThumbItemId'] : (isset($itemDetails['ParentBackdropItemId']) ? $itemDetails['ParentBackdropItemId'] : false);
				$imageType = isset($itemDetails['ParentThumbItemId']) ?	"Thumb" : (isset($itemDetails['ParentBackdropItemId']) ? "Backdrop" : false);
				$key = (isset($itemDetails['ParentThumbItemId']) ? $itemDetails['ParentThumbItemId']."-np" : "none-np");
				$elapsed = $moreInfo['PlayState']['PositionTicks'];
				$duration = $moreInfo['NowPlayingItem']['RunTimeTicks'];
				$watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
				//$transcoded = floor($item->TranscodeSession['progress']- $watched);
				$stream = $moreInfo['PlayState']['PlayMethod'];
				$user = $role == "admin" ? $moreInfo['UserName'] : "";
				$id = $moreInfo['DeviceId'];
				$streamInfo = buildStream(array(
					'platform' => (string) $moreInfo['Client'],
					'device' => (string) $moreInfo['DeviceName'],
					'stream' => streamType($stream),
					'video' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "video"),
					'audio' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
				));
				$state = (($moreInfo['PlayState']['IsPaused'] == "1") ? "pause" : "play");
				$topTitle = '<h5 class="text-center zero-m elip">'.$title.' - '.$itemDetails['Name'].'</h5>';
				$bottomTitle = '<small class="zero-m">S'.$itemDetails['ParentIndexNumber'].' · E'.$itemDetails['IndexNumber'].'</small>';
				if($showNames == "true"){ $bottomTitle .= '</small><small class="zero-m pull-right">'.$user.'</small>'; }
			}
			break;
		case 'MusicAlbum':
		case 'Audio':
			$title = $itemDetails['Name'];
			$imageId = $itemDetails['Id'];
			$width = 444;
    		$style = '';
    		$image = 'slick-image-short';
			if(!$nowPlaying){
				$imageType = (isset($itemDetails['ImageTags']['Primary']) ? "Primary" : false);
				$key = $itemDetails['Id'] . "-list";
				$itemType = 'album';
			}else{
				$height = 281;
				$width = 500;
				$imageId = (isset($itemDetails['ParentBackdropItemId']) ? $itemDetails['ParentBackdropItemId'] : false);
				$imageType = (isset($itemDetails['ParentBackdropItemId']) ? "Backdrop" : false);
				$key = (isset($itemDetails['ParentBackdropItemId']) ? $itemDetails['ParentBackdropItemId'] : "no-np") . "-np";
				$elapsed = $moreInfo['PlayState']['PositionTicks'];
				$duration = $moreInfo['NowPlayingItem']['RunTimeTicks'];
				$watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
				//$transcoded = floor($item->TranscodeSession['progress']- $watched);
				$stream = $moreInfo['PlayState']['PlayMethod'];
				$user = $role == "admin" ? $moreInfo['UserName'] : "";
				$id = $moreInfo['DeviceId'];
				$streamInfo = buildStream(array(
					'platform' => (string) $moreInfo['Client'],
					'device' => (string) $moreInfo['DeviceName'],
					'stream' => streamType($stream),
					'audio' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
				));
				$state = (($moreInfo['PlayState']['IsPaused'] == "1") ? "pause" : "play");
				$topTitle = '<h5 class="text-center zero-m elip">'.$itemDetails['AlbumArtist'].' - '.$itemDetails['Album'].'</h5>';
				$bottomTitle = '<small class="zero-m">'.$title.'</small>';
				if($showNames == "true"){ $bottomTitle .= '</small><small class="zero-m pull-right">'.$user.'</small>'; }
			}
			break;
  		case 'TvChannel':
			$title = $itemDetails['CurrentProgram']['Name'];
			$imageId = $itemDetails['Id'];
			$width = 300;
			$style = '';
			$image = 'slick-image-tall';
			if(!$nowPlaying){
				$imageType = "Primary";
				$key = $itemDetails['Id'] . "-list";
			}else{
				$height = 281;
				$width = 500;
				$imageType = "Thumb";
				$key = $itemDetails['Id'] . "-np";
				$useImage = "images/livetv.png";
				$watched = "0";
				$stream = $moreInfo['PlayState']['PlayMethod'];
				$user = $role == "admin" ? $moreInfo['UserName'] : "";
				$id = $moreInfo['DeviceId'];
				$streamInfo = buildStream(array(
					'platform' => (string) $moreInfo['Client'],
					'device' => (string) $moreInfo['DeviceName'],
					'stream' => streamType($stream),
					'video' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "video"),
					'audio' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
				));
				$state = (($moreInfo['PlayState']['IsPaused'] == "1") ? "pause" : "play");
				$topTitle = '<h5 class="text-center zero-m elip">'.$title.'</h5>';
				$bottomTitle = '<small class="zero-m">'.$itemDetails['Name'].' - '.$itemDetails['ChannelNumber'].'</small>';
				if($showNames == "true"){ $bottomTitle .= '</small><small class="zero-m pull-right">'.$user.'</small>'; }
			}
			break;
		default:
			$title = $itemDetails['Name'];
			$imageId = $itemDetails['Id'];
			$width = 300;
			$style = '';
			$image = 'slick-image-tall';
			if(!$nowPlaying){
				$imageType = (isset($itemDetails['ImageTags']['Primary']) ? "Primary" : false);
				$key = $itemDetails['Id'] . "-list";
				$itemType = 'movie';
			}else{
				$height = 281;
				$width = 500;
				$imageType = isset($itemDetails['ImageTags']['Thumb']) ? "Thumb" : (isset($itemDetails['BackdropImageTags']) ? "Backdrop" : false);
				$key = $itemDetails['Id'] . "-np";
				$elapsed = $moreInfo['PlayState']['PositionTicks'];
				$duration = $moreInfo['NowPlayingItem']['RunTimeTicks'];
				$watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
				//$transcoded = floor($item->TranscodeSession['progress']- $watched);
				$stream = $moreInfo['PlayState']['PlayMethod'];
				$user = $role == "admin" ? $moreInfo['UserName'] : "";
				$id = $moreInfo['DeviceId'];
				$streamInfo = buildStream(array(
					'platform' => (string) $moreInfo['Client'],
					'device' => (string) $moreInfo['DeviceName'],
					'stream' => streamType($stream),
					'video' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "video"),
					'audio' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
				));
				$state = (($moreInfo['PlayState']['IsPaused'] == "1") ? "pause" : "play");
				$topTitle = '<h5 class="text-center zero-m elip">'.$title.'</h5>';
				$bottomTitle = '<small class="zero-m">'.$moreInfo['NowPlayingItem']['ProductionYear'].'</small>';
				if($showNames == "true"){ $bottomTitle .= '</small><small class="zero-m pull-right">'.$user.'</small>'; }
			}
	}

	// If No Overview
	if (!isset($itemDetails['Overview'])) {
		$itemDetails['Overview'] = '';
	}
	$original_image_url = 'ajax.php?a=emby-image&type='.$imageType.'&img='.$imageId.'&height='.$height.'&width='.$width.'&key='.$key.'$'.randString();
	if (file_exists('images/cache/'.$key.'.jpg')){ $image_url = 'images/cache/'.$key.'.jpg'; }
    if (file_exists('images/cache/'.$key.'.jpg') && (time() - 604800) > filemtime('images/cache/'.$key.'.jpg') || !file_exists('images/cache/'.$key.'.jpg')) {
        $image_url = 'ajax.php?a=emby-image&type='.$imageType.'&img='.$imageId.'&height='.$height.'&width='.$width.'&key='.$key.'';
    }

    if($nowPlaying){
        if(!$imageType){ $original_image_url = $image_url = "images/no-np.png"; $key = "no-np"; }
        if(!$imageId){ $original_image_url = $image_url = "images/no-np.png"; $key = "no-np"; }
    }else{
        if(!$imageType){ $original_image_url = $image_url = "images/no-list.png"; $key = "no-list"; }
        if(!$imageId){ $original_image_url = $image_url = "images/no-list.png"; $key = "no-list"; }
    }
    if(isset($useImage)){ $image_url = $useImage; }

	// Assemble Item And Cache Into Array
	if($nowPlaying){
    	//prettyPrint($itemDetails);
    	return '<div class="col-sm-6 col-md-3"><div class="thumbnail ultra-widget"><div style="display: none;" np="'.$id.'" class="overlay content-box small-box gray-bg">'.$streamInfo.'</div><span class="refreshNP w-refresh w-p-icon gray" link="'.$id.'"><span class="fa-stack fa-lg" style="font-size: .5em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-info-circle fa-stack-1x fa-inverse"></i></span></span><a href="'.$URL.'" target="_blank"><img style="width: 100%; display:inherit;" src="'.$image_url.'" alt="'.$itemDetails['Name'].'" original-image="'.$original_image_url.'" class="refreshImageSource"></a><div class="progress progress-bar-sm zero-m"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$watched.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$watched.'%"></div><div class="progress-bar palette-Grey-500 bg" style="width: 0%"></div></div><div class="caption"><i style="float:left" class="fa fa-'.$state.'"></i>'.$topTitle.''.$bottomTitle.'</div></div></div>';
    }else{
		 return '<div class="item-'.$itemType.'"><div class="ultra-widget refreshImage"><span class="w-refresh w-p-icon gray"><span class="fa-stack fa-lg" style="font-size: .4em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-refresh fa-stack-1x fa-inverse"></i></span></span></div><a href="'.$URL.'" target="_blank"><img alt="'.$itemDetails['Name'].'" class="'.$image.' refreshImageSource" data-lazy="'.$image_url.'" original-image="'.$original_image_url.'"></a><small class="elip slick-bottom-title">'.$title.'</small></div>';
	}
}

// Format item from Plex for Carousel
function resolvePlexItem($server, $token, $item, $nowPlaying = false, $showNames = false, $role = false, $playlist = false) {
    // Static Height
    $height = 444;
	$widthOverride = 100;
	$playlist = ($playlist) ? " playlist-$playlist" : "";

    switch ($item['type']) {
    	case 'season':
            $title = $item['parentTitle'];
            $summary = $item['parentSummary'];
            $width = 300;
            $image = 'slick-image-tall';
            $style = '';
            if(!$nowPlaying){
                $thumb = $item['thumb'];
                $key = $item['ratingKey'] . "-list";
            }else {
                $height = 281;
                $width = 500;
                $thumb = $item['art'];
                $key = $item['ratingKey'] . "-np";
                $elapsed = $item['viewOffset'];
                $duration = ($item['duration']) ? $item['duration'] : $item->Media['duration'];
                $watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
                $transcoded = floor($item->TranscodeSession['progress']- $watched);
                $stream = $item->Media->Part->Stream['decision'];
                $user = $role == "admin" ? $item->User['title'] : "";
                $id = str_replace('"', '', $item->Player['machineIdentifier']);
                $streamInfo = buildStream(array(
                    'platform' => (string) $item->Player['platform'],
                    'device' => (string) $item->Player['device'],
                    'stream' => streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
                ));
                $state = (($item->Player['state'] == "paused") ? "pause" : "play");
            }
            break;
        case 'episode':
            $title = $item['grandparentTitle'];
            $summary = $item['title'];
            $width = 300;
            $image = 'slick-image-tall';
            $style = '';
            if(!$nowPlaying){
                $thumb = ($item['parentThumb'] ? $item['parentThumb'] : $item['grandparentThumb']);
                $key = $item['ratingKey'] . "-list";
            }else {
                $height = 281;
                $width = 500;
                $thumb = $item['art'];
                $key = $item['ratingKey'] . "-np";
                $elapsed = $item['viewOffset'];
                $duration = ($item['duration']) ? $item['duration'] : $item->Media['duration'];
                $watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
                $transcoded = floor($item->TranscodeSession['progress']- $watched);
                $stream = $item->Media->Part->Stream['decision'];
                $user = $role == "admin" ? $item->User['title'] : "";
                $id = str_replace('"', '', $item->Player['machineIdentifier']);
                $streamInfo = buildStream(array(
                    'platform' => (string) $item->Player['platform'],
                    'device' => (string) $item->Player['device'],
                    'stream' => streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
                ));
                $state = (($item->Player['state'] == "paused") ? "pause" : "play");
                $topTitle = '<h5 class="text-center zero-m elip">'.$title.' - '.$item['title'].'</h5>';
                $bottomTitle = '<small class="zero-m">S'.$item['parentIndex'].' · E'.$item['index'].'</small>';
                if($showNames == "true"){ $bottomTitle .= '<small class="zero-m pull-right">'.$user.'</small>'; }
            }
            break;
        case 'clip':
            $title = $item['title'];
            $summary = $item['summary'];
            $width = 300;
            $image = 'slick-image-tall';
            $style = '';
            if(!$nowPlaying){
                $thumb = $item['thumb'];
                $key = $item['ratingKey'] . "-list";
            }else {
                $height = 281;
                $width = 500;
                $thumb = $item['art'];
                $key = isset($item['ratingKey']) ? $item['ratingKey'] . "-np" : (isset($item['live']) ? "livetv.png" : ":)");
				$useImage = (isset($item['live']) ? "images/livetv.png" : null);
				$extraInfo = isset($item['extraType']) ? "Trailer" : (isset($item['live']) ? "Live TV" : ":)");
                $elapsed = $item['viewOffset'];
                $duration = ($item['duration']) ? $item['duration'] : $item->Media['duration'];
                $watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
                $transcoded = floor($item->TranscodeSession['progress']- $watched);
                $stream = $item->Media->Part->Stream['decision'];
                $user = $role == "admin" ? $item->User['title'] : "";
                $id = str_replace('"', '', $item->Player['machineIdentifier']);
                $streamInfo = buildStream(array(
                    'platform' => (string) $item->Player['platform'],
                    'device' => (string) $item->Player['device'],
                    'stream' => streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
                ));
                $state = (($item->Player['state'] == "paused") ? "pause" : "play");
                $topTitle = '<h5 class="text-center zero-m elip">'.$title.'</h5>';
                $bottomTitle = '<small class="zero-m">'.$extraInfo.'</small>';
                if($showNames == "true"){ $bottomTitle .= '<small class="zero-m pull-right">'.$user.'</small>'; }
            }
            break;
        case 'album':
        case 'track':
            $title = $item['parentTitle'];
            $summary = $item['title'];
            $image = 'slick-image-short';
            $style = 'left: 160px !important;';
			$item['ratingKey'] = $item['parentRatingKey'];
            if(!$nowPlaying){
                $width = 444;
                $thumb = $item['thumb'];
                $key = $item['ratingKey'] . "-list";
            }else {
                $height = 281;
                $width = 500;
				$thumb = ($item['parentThumb']) ? $item['parentThumb'] :  $item['art'];
				$widthOverride = ($item['parentThumb']) ? 56 :  100;
                $key = $item['ratingKey'] . "-np";
                $elapsed = $item['viewOffset'];
                $duration = ($item['duration']) ? $item['duration'] : $item->Media['duration'];
                $watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
                $transcoded = floor($item->TranscodeSession['progress']- $watched);
                $stream = $item->Media->Part->Stream['decision'];
                $user = $role == "admin" ? $item->User['title'] : "";
                $id = str_replace('"', '', $item->Player['machineIdentifier']);
                $streamInfo = buildStream(array(
                    'platform' => (string) $item->Player['platform'],
                    'device' => (string) $item->Player['device'],
                    'stream' => streamType($item->Media->Part['decision']),
                    'audio' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['channels']."ch)",
                ));
                $state = (($item->Player['state'] == "paused") ? "pause" : "play");
                $topTitle = '<h5 class="text-center zero-m elip">'.$item['grandparentTitle'].' - '.$item['title'].'</h5>';
                $bottomTitle = '<small class="zero-m">'.$title.'</small>';
                if($showNames == "true"){ $bottomTitle .= '<small class="zero-m pull-right">'.$user.'</small>'; }
            }
            break;
        default:
            $title = $item['title'];
            $summary = $item['summary'];
            $image = 'slick-image-tall';
            $style = '';
            if(!$nowPlaying){
                $width = 300;
                $thumb = $item['thumb'];
                $key = $item['ratingKey'] . "-list";
            }else {
                $height = 281;
                $width = 500;
                $thumb = $item['art'];
                $key = $item['ratingKey'] . "-np";
                $elapsed = $item['viewOffset'];
                $duration = ($item['duration']) ? $item['duration'] : $item->Media['duration'];
                $watched = (!empty($elapsed) ? floor(($elapsed / $duration) * 100) : 0);
                $transcoded = floor($item->TranscodeSession['progress']- $watched);
                $stream = $item->Media->Part->Stream['decision'];
                $user = $role == "admin" ? $item->User['title'] : "";
                $id = str_replace('"', '', $item->Player['machineIdentifier']);
                $streamInfo = buildStream(array(
                    'platform' => (string) $item->Player['platform'],
                    'device' => (string) $item->Player['device'],
                    'stream' => streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
                ));
                $state = (($item->Player['state'] == "paused") ? "pause" : "play");
                $topTitle = '<h5 class="text-center zero-m elip">'.$title.'</h5>';
                $bottomTitle = '<small class="zero-m">'.$item['year'].'</small>';
                if($showNames == "true"){ $bottomTitle .= '<small class="zero-m pull-right">'.$user.'</small>'; }
            }
		}

		if (PLEXTABURL) {
			$address = PLEXTABURL."/web/index.html#!/server/$server/details?key=/library/metadata/".$item['ratingKey'];
		}else{
			$address = "https://app.plex.tv/web/app#!/server/$server/details?key=/library/metadata/".$item['ratingKey'];
		}

    // If No Overview
    if (!isset($itemDetails['Overview'])) { $itemDetails['Overview'] = ''; }
	$original_image_url = 'ajax.php?a=plex-image&img='.$thumb.'&height='.$height.'&width='.$width.'&key='.$key.'$'.randString();
    if (file_exists('images/cache/'.$key.'.jpg')){ $image_url = 'images/cache/'.$key.'.jpg'; }
    if (file_exists('images/cache/'.$key.'.jpg') && (time() - 604800) > filemtime('images/cache/'.$key.'.jpg') || !file_exists('images/cache/'.$key.'.jpg')) {
        $image_url = 'ajax.php?a=plex-image&img='.$thumb.'&height='.$height.'&width='.$width.'&key='.$key.'';
    }
    if($nowPlaying){
        if(!$thumb){ $original_image_url = $image_url = "images/no-np.png"; $key = "no-np"; }
    }else{
        if(!$thumb){ $original_image_url = $image_url = "images/no-list.png"; $key = "no-list"; }
    }
	if(isset($useImage)){ $image_url = $useImage; }
	$openTab = (PLEXTABNAME) ? "true" : "false";
    // Assemble Item And Cache Into Array
    if($nowPlaying){
		$musicOverlay = ($widthOverride == 56) ? '<img class="" style="width: 55%;display:block;position: absolute;top: 4px;left:5px;overflow: hidden;filter: blur(0px) grayscale(1);" src="'.$image_url.'">
		<img class="" style="width: 55%;display:block;position: absolute;top: 4px;right:5px;overflow: hidden;filter: blur(0px) grayscale(1);" src="'.$image_url.'">' : '';
        return '<div class="col-sm-6 col-md-3"><div class="thumbnail ultra-widget"><div style="display: none;" np="'.$id.'" class="overlay content-box small-box gray-bg">'.$streamInfo.'</div><span class="refreshNP w-refresh w-p-icon gray" link="'.$id.'"><span class="fa-stack fa-lg" style="font-size: .5em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-info-circle fa-stack-1x fa-inverse"></i></span></span><div class="ultra-widget refreshImage"><span class="w-refresh w-p-icon gray"><span class="fa-stack fa-lg" style="font-size: .4em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-refresh fa-stack-1x fa-inverse"></i></span></span></div><a class="openTab" extraTitle="'.$title.'" extraType="'.$item['type'].'" openTab="'.$openTab.'" href="'.$address.'" target="_blank">'.$musicOverlay.'<img class="refreshImageSource" style="width: '.$widthOverride.'%; display:block; position: relative" src="'.$image_url.'" original-image="'.$original_image_url.'" alt="'.$item['Name'].'"></a><div class="progress progress-bar-sm zero-m"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$watched.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$watched.'%"></div><div class="progress-bar palette-Grey-500 bg" style="width: '.$transcoded.'%"></div></div><div class="caption"><i style="float:left" class="fa fa-'.$state.'"></i>'.$topTitle.''.$bottomTitle.'</div></div></div>';
    }else{
        return '<div class="item-'.$item['type'].$playlist.'"><div style="" class="ultra-widget refreshImage"><span class="w-refresh w-p-icon gray"><span class="fa-stack fa-lg" style="font-size: .4em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-refresh fa-stack-1x fa-inverse"></i></span></span></div><a class="openTab" extraTitle="'.$title.'" extraType="'.$item['type'].'" openTab="'.$openTab.'" href="'.$address.'" target="_blank"><img alt="'.$item['Name'].'" class="'.$image.' refreshImageSource" data-lazy="'.$image_url.'" original-image="'.$original_image_url.'"></a><small class="elip slick-bottom-title">'.$title.'</small></div>';
    }
}
//$hideMenu .= '<li data-filter="playlist-'.$className.'" data-name="'.$api['title'].'"><a class="js-filter-'.$className.'" href="javascript:void(0)">'.$api['title'].'</a></li>';
//Recent Added
function outputRecentAdded($header, $items, $script = false, $array, $type) {
    $hideMenu = '<div class="pull-right"><div class="btn-group" role="group"><button type="button" class="btn waves btn-default btn-sm dropdown-toggle waves-effect waves-float" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Filter &nbsp;<span class="caret"></span></button><ul style="right:0; left: auto" class="dropdown-menu filter-recent-event">';
    if(preg_grep("/item-movie/", $items)){
        $hideMenu .= '<li data-filter="item-movie" data-name="Movies" data-filter-on="false"><a class="js-filter-movie" href="javascript:void(0)">Movies</a></li>';
    }
	if(preg_grep("/item-season/", $items)){
        $hideMenu .= '<li data-filter="item-season" data-name="TV Shows" data-filter-on="false"><a class="js-filter-season" href="javascript:void(0)">Shows</a></li>';
    }
    if(preg_grep("/item-album/", $items)){
        $hideMenu .= '<li data-filter="item-album" data-name="Music Albums" data-filter-on="false"><a class="js-filter-album" href="javascript:void(0)">Music</a></li>';
    }
	$hideMenu .= '<li data-filter="item-all" data-name="Content" data-filter-on="false"><a class="js-filter-all" href="javascript:void(0)">All</a></li>';
    $hideMenu .= '</ul></div></div>';
    // If None Populate Empty Item
    if (!count($items)) {
        return '<div id="recentMedia'.$type.'" class="content-box box-shadow big-box"><h5 class="text-center">'.$header.'</h5><p class="text-center">No Media Found</p></div>';
    }else{
		$className = str_replace(' ', '', $header.' on '.$type);
        return '<div id="recentMedia'.$type.'" class="content-box box-shadow big-box"><h5 id="recentContent-title-'.$type.'" style="margin-bottom: -20px" class="text-center"><span>'.$header.'</span></h5><div class="recentHeader inbox-pagination '.$className.'">'.$hideMenu.'</div><br/><br/><div class="recentItems-recent-'.$type.'" data-name="'.$className.'">'.implode('',$items).'</div></div>'.($script?'<script>'.$script.'</script>':'');
    }

}

// Create Carousel
function outputNowPlaying($header, $size, $type, $items, $script = false) {
	// If None Populate Empty Item
	if (!count($items)) {
		return '<div id="'.$type.'"></div>'.($script?'<script>'.$script.'</script>':'');
	}else{
	   return '<div id="'.$type.'"><h5 class="zero-m big-box"><strong>'.$header.'</strong></h5>'.implode('',$items).'</div>'.($script?'<script>'.$script.'</script>':'');
 }

}

// Get Now Playing Streams From Emby
function getEmbyStreams($size, $showNames, $role) {
	$address = qualifyURL(EMBYURL);

	$api = json_decode(@file_get_contents($address.'/Sessions?api_key='.EMBYTOKEN),true);
	if (!is_array($api)) { return 'Could not load!'; }

	$playingItems = array();
	foreach($api as $key => $value) {
		if (isset($value['NowPlayingItem'])) {
			$playingItems[] = resolveEmbyItem($address, EMBYTOKEN, $value['NowPlayingItem'], true, $showNames, $role, $value);
		}
	}

	return outputNowPlaying(translate('PLAYING_NOW_ON_EMBY')." ( ".count($playingItems)." Streams )", $size, 'streams-emby', $playingItems, ajaxLoop('emby-streams',NOWPLAYINGREFRESH));
}

// Get Now Playing Streams From Plex
function getPlexStreams($size, $showNames, $role){
    $address = qualifyURL(PLEXURL);

	// Perform API requests
	$api = @curl_get($address."/status/sessions?X-Plex-Token=".PLEXTOKEN);
	libxml_use_internal_errors(true);
    $api = simplexml_load_string($api);
	if (is_array($api) || is_object($api)){
		if (!$api->head->title){
			$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
			if (!$getServer) { return 'Could not load!'; }

			// Identify the local machine
			$gotServer = $getServer['machineIdentifier'];

			$items = array();
			foreach($api AS $child) {
				$items[] = resolvePlexItem($gotServer, PLEXTOKEN, $child, true, $showNames, $role);
			}

			return outputNowPlaying(translate('PLAYING_NOW_ON_PLEX')." ( ".count($items)." Streams )", $size, 'streams-plex', $items, ajaxLoop('plex-streams',NOWPLAYINGREFRESH));
		}else{
			writeLog("error", "PLEX STREAM ERROR: could not connect - check token - if HTTPS, is cert valid");
		}
	}else{
		writeLog("error", "PLEX STREAM ERROR: could not connect - check URL - if HTTPS, is cert valid");
	}
}

// Get Recent Content From Emby
function getEmbyRecent($array) {
    $address = qualifyURL(EMBYURL);
    $header = translate('RECENT_CONTENT');
    // Currently Logged In User
    $username = false;
    if (isset($GLOBALS['USER'])) {
        $username = strtolower($GLOBALS['USER']->username);
    }

    // Get A User
    $userIds = json_decode(@file_get_contents($address.'/Users?api_key='.EMBYTOKEN),true);
    if (!is_array($userIds)) { return 'Could not load!'; }

    $showPlayed = true;
    foreach ($userIds as $value) { // Scan for admin user
        if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
            $userId = $value['Id'];
        }
        if ($username && strtolower($value['Name']) == $username) {
            $userId = $value['Id'];
            $showPlayed = false;
            break;
        }
    }

    // Get the latest Items
    $latest = json_decode(@file_get_contents($address.'/Users/'.$userId.'/Items/Latest?EnableImages=false&Limit='.EMBYRECENTITEMS.'&api_key='.EMBYTOKEN.($showPlayed?'':'&IsPlayed=false')),true);

    // For Each Item In Category
    $items = array();
    foreach ($latest as $k => $v) {
        $type = (string) $v['Type'];
        if(@$array[$type] == "true"){
            $items[] = resolveEmbyItem($address, EMBYTOKEN, $v, false, false, false);
        }
    }

    $array["movie"] = $array["Movie"];
    $array["season"] = $array["Episode"];
    $array["album"] = $array["MusicAlbum"];
    unset($array["Movie"]);
    unset($array["Episode"]);
    unset($array["MusicAlbum"]);
    unset($array["Series"]);

    return outputRecentAdded($header, $items, ajaxLoop('emby-recent',RECENTREFRESH,'loadSlick();'), $array, 'Emby');
}

// Get Recent Content From Plex
function getPlexRecent($array){
    $address = qualifyURL(PLEXURL);
	$header = translate('RECENT_CONTENT');

	// Perform Requests
	$api = @curl_get($address."/library/recentlyAdded?limit=".PLEXRECENTITEMS."&X-Plex-Token=".PLEXTOKEN);
	libxml_use_internal_errors(true);
    $api = simplexml_load_string($api);
	if (is_array($api) || is_object($api)){
		if (!$api->head->title){
			$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
			if (!$getServer) { return 'Could not load!'; }

			// Identify the local machine
			$gotServer = $getServer['machineIdentifier'];

			$items = array();
			foreach($api AS $child) {
			 $type = (string) $child['type'];
				if($array[$type] == "true"){
					$items[] = resolvePlexItem($gotServer, PLEXTOKEN, $child, false, false, false);
				}
			}

			return outputRecentAdded($header, $items, ajaxLoop('plex-recent',RECENTREFRESH,'loadSlick();'), $array, 'Plex');
		}else{
			writeLog("error", "PLEX RECENT-ITEMS ERROR: could not connect - check token - if HTTPS, is cert valid");
		}
	}else{
		writeLog("error", "PLEX RECENT-ITEMS ERROR: could not connect - check URL - if HTTPS, is cert valid");
	}
}

// Get Image From Emby
function getEmbyImage() {
	$refresh = false;
	$embyAddress = qualifyURL(EMBYURL);
    if (!file_exists('images/cache')) {
        mkdir('images/cache', 0777, true);
    }

	$itemId = $_GET['img'];
 	$key = $_GET['key'];
	if(strpos($key, '$') !== false){
		$key = explode('$', $key)[0];
		$refresh = true;
	}
	$itemType = $_GET['type'];
	$imgParams = array();
	if (isset($_GET['height'])) { $imgParams['height'] = 'maxHeight='.$_GET['height']; }
	if (isset($_GET['width'])) { $imgParams['width'] = 'maxWidth='.$_GET['width']; }

	if(isset($itemId)) {
	    $image_src = $embyAddress . '/Items/'.$itemId.'/Images/'.$itemType.'?'.implode('&', $imgParams);
	    $cachefile = 'images/cache/'.$key.'.jpg';
	    $cachetime = 604800;
	    // Serve from the cache if it is younger than $cachetime
	    if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $refresh !== true) {
	        header("Content-type: image/jpeg");
	        @readfile($cachefile);
	        exit;
	    }
        ob_start(); // Start the output buffer
        header('Content-type: image/jpeg');
        //@readfile($image_src);
		echo @curl_get($image_src);
        // Cache the output to a file
        $fp = fopen($cachefile, 'wb');
        fwrite($fp, ob_get_contents());
        fclose($fp);
        ob_end_flush(); // Send the output to the browser
        die();
	} else {
		debug_out('Invalid Request',1);
	}
}

// Get Image From Plex
function getPlexImage() {
	$refresh = false;
	$plexAddress = qualifyURL(PLEXURL);
    if (!file_exists('images/cache')) {
        mkdir('images/cache', 0777, true);
    }

	$image_url = $_GET['img'];
	$key = $_GET['key'];
	if(strpos($key, '$') !== false){
		$key = explode('$', $key)[0];
		$refresh = true;
	}
	$image_height = $_GET['height'];
	$image_width = $_GET['width'];

	if(isset($image_url) && isset($image_height) && isset($image_width)) {
		$image_src = $plexAddress . '/photo/:/transcode?height='.$image_height.'&width='.$image_width.'&upscale=1&url=' . $image_url . '&X-Plex-Token=' . PLEXTOKEN;
        $cachefile = 'images/cache/'.$key.'.jpg';
        $cachetime = 604800;
        // Serve from the cache if it is younger than $cachetime
        if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $refresh == false) {
            header("Content-type: image/jpeg");
            @readfile($cachefile);
            exit;
        }
		ob_start(); // Start the output buffer
        header('Content-type: image/jpeg');
		//@readfile($image_src);
		echo @curl_get($image_src);
        // Cache the output to a file
        $fp = fopen($cachefile, 'wb');
        fwrite($fp, ob_get_contents());
        fclose($fp);
        ob_end_flush(); // Send the output to the browser
		die();
	} else {
		echo "Invalid Plex Request";
	}
}

// Simplier access to class
function translate($string) {
	if (isset($GLOBALS['language'])) {
		return $GLOBALS['language']->translate($string);
	} else {
		return '!Translations Not Loaded!';
	}
}

// Generate Random string
function randString($length = 10, $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ') {
	$tmp = '';
	for ($i = 0; $i < $length; $i++) {
		$tmp .= substr(str_shuffle($chars), 0, 1);
	}
    return $tmp;
}

// Create config file in the return syntax
function createConfig($array, $path = 'config/config.php', $nest = 0) {
	// Define Initial Value
	$output = array();

	// Sort Items
	ksort($array);

	// Update the current config version
	if (!$nest) {
		// Inject Current Version
		$output[] = "\t'CONFIG_VERSION' => '".(isset($array['apply_CONFIG_VERSION'])?$array['apply_CONFIG_VERSION']:INSTALLEDVERSION)."'";
	}
	unset($array['CONFIG_VERSION']);
	unset($array['apply_CONFIG_VERSION']);

	// Process Settings
	foreach ($array as $k => $v) {
		$allowCommit = true;
		switch (gettype($v)) {
			case 'boolean':
				$item = ($v?'true':'false');
				break;
			case 'integer':
			case 'double':
			case 'integer':
			case 'NULL':
				$item = $v;
				break;
			case 'string':
				$item = "'".str_replace(array('\\',"'"),array('\\\\',"\'"),$v)."'";
				break;
			case 'array':
				$item = createConfig($v, false, $nest+1);
				break;
			default:
				$allowCommit = false;
		}

		if($allowCommit) {
			$output[] = str_repeat("\t",$nest+1)."'$k' => $item";
		}
	}

	// Build output
	$output = (!$nest?"<?php\nreturn ":'')."array(\n".implode(",\n",$output)."\n".str_repeat("\t",$nest).')'.(!$nest?';':'');

	if (!$nest && $path) {
		$pathDigest = pathinfo($path);

		@mkdir($pathDigest['dirname'], 0770, true);

		if (file_exists($path)) {
			rename($path, $pathDigest['dirname'].'/'.$pathDigest['filename'].'.bak.php');
		}

		$file = fopen($path, 'w');
		fwrite($file, $output);
		fclose($file);
		if (file_exists($path)) {
			return true;
		}
		writeLog("error", "config was unable to write");
		return false;
	} else {
  		writeLog("success", "config was updated with new values");
		return $output;
	}
}

// Load a config file written in the return syntax
function loadConfig($path = 'config/config.php') {
	// Adapted from http://stackoverflow.com/a/14173339/6810513
    if (!is_file($path)) {
        return null;
    } else {
		return (array) call_user_func(function() use($path) {
			return include($path);
		});
	}
}

// Commit new values to the configuration
function updateConfig($new, $current = false) {
	// Get config if not supplied
	if ($current === false) {
		$current = loadConfig();
	} else if (is_string($current) && is_file($current)) {
		$current = loadConfig($current);
	}

	// Inject Parts
	foreach ($new as $k => $v) {
		$current[$k] = $v;
	}

	// Return Create
	return createConfig($current);
}

// Inject Defaults As Needed
function fillDefaultConfig($array, $path = 'config/configDefaults.php') {
	if (is_string($path)) {
		$loadedDefaults = loadConfig($path);
	} else {
		$loadedDefaults = $path;
	}

	return (is_array($loadedDefaults) ? fillDefaultConfig_recurse($array, $loadedDefaults) : false);
}

// support function for fillDefaultConfig()
function fillDefaultConfig_recurse($current, $defaults) {
	foreach($defaults as $k => $v) {
		if (!isset($current[$k])) {
			$current[$k] = $v;
		} else if (is_array($current[$k]) && is_array($v)) {
			$current[$k] = fillDefaultConfig_recurse($current[$k], $v);
		}
	}
	return $current;
};

// Define Scalar Variables (nest non-secular with underscores)
function defineConfig($array, $anyCase = true, $nest_prefix = false) {
	foreach($array as $k => $v) {
		if (is_scalar($v) && !defined($nest_prefix.$k)) {
			define($nest_prefix.$k, $v, $anyCase);
		} else if (is_array($v)) {
			defineConfig($v, $anyCase, $nest_prefix.$k.'_');
		}
	}
}

// This function exists only because I am lazy
function configLazy($path = 'config/config.php') {
	// Load config or default
	if (file_exists($path)) {
		$config = fillDefaultConfig(loadConfig($path));
	} else {
		$config = loadConfig('config/configDefaults.php');
	}

	if (is_array($config)) {
		defineConfig($config);
	}
	return $config;
}

// Qualify URL
function qualifyURL($url) {
 //local address?
 if(substr($url, 0,1) == "/"){
     if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https://";
    } else {
        $protocol = "http://";
    }
     $url = $protocol.getServer().$url;
 }
	// Get Digest
	$digest = parse_url($url);

	// http/https
	if (!isset($digest['scheme'])) {
		if (isset($digest['port']) && in_array($digest['port'], array(80,8080,8096,32400,7878,8989,8182,8081,6789))) {
			$scheme = 'http';
		} else {
			$scheme = 'https';
		}
	} else {
		$scheme = $digest['scheme'];
	}

	// Host
	$host = (isset($digest['host'])?$digest['host']:'');

	// Port
	$port = (isset($digest['port'])?':'.$digest['port']:'');

	// Path
	$path = (isset($digest['path'])?$digest['path']:'');

	// Output
	return $scheme.'://'.$host.$port.$path;
}

// Function to be called at top of each to allow upgrading environment as the spec changes
function upgradeCheck() {
	// Upgrade to 1.31
	if (file_exists('homepageSettings.ini.php')) {
		$databaseConfig = parse_ini_file('databaseLocation.ini.php', true);
		$homepageConfig = parse_ini_file('homepageSettings.ini.php', true);

		$databaseConfig = array_merge($databaseConfig, $homepageConfig);

		$databaseData = '; <?php die("Access denied"); ?>' . "\r\n";
		foreach($databaseConfig as $k => $v) {
			if(substr($v, -1) == "/") : $v = rtrim($v, "/"); endif;
			$databaseData .= $k . " = \"" . $v . "\"\r\n";
		}

		write_ini_file($databaseData, 'databaseLocation.ini.php');
		unlink('homepageSettings.ini.php');
		unset($databaseData);
		unset($homepageConfig);
	}

	// Upgrade to 1.32
	if (file_exists('databaseLocation.ini.php')) {
		// Load Existing
		$config = parse_ini_file('databaseLocation.ini.php', true);

		// Refactor
		$config['database_Location'] = preg_replace('/\/\/$/','/',$config['databaseLocation'].'/');
		$config['user_home'] = $config['database_Location'].'users/';
		unset($config['databaseLocation']);

		// Turn Off Emby And Plex Recent
		$config["embyURL"] = $config["embyURL"].(!empty($config["embyPort"])?':'.$config["embyPort"]:'');
		unset($config["embyPort"]);
		$config["plexURL"] = $config["plexURL"].(!empty($config["plexPort"])?':'.$config["plexPort"]:'');
		unset($config["plexPort"]);
		$config["nzbgetURL"] = $config["nzbgetURL"].(!empty($config["nzbgetPort"])?':'.$config["nzbgetPort"]:'');
		unset($config["nzbgetPort"]);
		$config["sabnzbdURL"] = $config["sabnzbdURL"].(!empty($config["sabnzbdPort"])?':'.$config["sabnzbdPort"]:'');
		unset($config["sabnzbdPort"]);
		$config["headphonesURL"] = $config["headphonesURL"].(!empty($config["headphonesPort"])?':'.$config["headphonesPort"]:'');
		unset($config["headphonesPort"]);

		// Write config file
		$config['CONFIG_VERSION'] = '1.32';
		copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][pre1.32].bak.php');
		$createConfigSuccess = createConfig($config);

		// Create new config
		if ($createConfigSuccess) {
			if (file_exists('config/config.php')) {
				// Remove Old ini file
				unlink('databaseLocation.ini.php');
			} else {
				debug_out('Something is not right here!');
			}
		} else {
			debug_out('Couldn\'t create updated configuration.' ,1);
		}
	}

	// Upgrade to 1.33
	$config = loadConfig();
	if (isset($config['database_Location']) && (!isset($config['CONFIG_VERSION']) || $config['CONFIG_VERSION'] < '1.33')) {
		// Fix User Directory
		$config['database_Location'] = preg_replace('/\/\/$/','/',$config['database_Location'].'/');
		$config['user_home'] = $config['database_Location'].'users/';
		unset($config['USER_HOME']);

		// Backend auth merge
		if (isset($config['authBackendPort']) && !isset(parse_url($config['authBackendHost'])['port'])) {
			$config['authBackendHost'] .= ':'.$config['authBackendPort'];
		}
		unset($config['authBackendPort']);

		// If auth is being used move it to embyURL as that is now used in auth functions
		if ((isset($config['authType']) && $config['authType'] == 'true') && (isset($config['authBackendHost']) && $config['authBackendHost'] == 'true') && (isset($config['authBackend']) && in_array($config['authBackend'], array('emby_all','emby_local','emby_connect')))) {
			$config['embyURL'] = $config['authBackendHost'];
		}

		// Upgrade database to latest version
		updateSQLiteDB($config['database_Location'],'1.32');

		// Update Version and Commit
		$config['apply_CONFIG_VERSION'] = '1.33';
		copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][1.32].bak.php');
		$createConfigSuccess = createConfig($config);
		unset($config);
	}

	// Upgrade to 1.34
	$config = loadConfig();
	if (isset($config['database_Location']) && (!isset($config['CONFIG_VERSION']) || $config['CONFIG_VERSION'] < '1.34')) {
		// Upgrade database to latest version
		updateSQLiteDB($config['database_Location'],'1.33');

		// Update Version and Commit
		$config['CONFIG_VERSION'] = '1.34';
		copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][1.33].bak.php');
		$createConfigSuccess = createConfig($config);
		unset($config);
	}

	// Upgrade to 1.40
	$config = loadConfig();
	if (isset($config['database_Location']) && (!isset($config['CONFIG_VERSION']) || $config['CONFIG_VERSION'] < '1.40')) {
		// Upgrade database to latest version
		updateSQLiteDB($config['database_Location'],'1.38');

		// Update Version and Commit
		$config['CONFIG_VERSION'] = '1.40';
		copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][1.38].bak.php');
		$createConfigSuccess = createConfig($config);
		unset($config);
	}

	// Upgrade to 1.50
	$config = loadConfig();
	if (isset($config['database_Location']) && (!isset($config['CONFIG_VERSION']) || $config['CONFIG_VERSION'] < '1.50')) {
		// Upgrade database to latest version
		updateSQLiteDB($config['database_Location'],'1.40');

		// Update Version and Commit
		$config['CONFIG_VERSION'] = '1.50';
		copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][1.40].bak.php');
		$createConfigSuccess = createConfig($config);
		unset($config);
	}
	// Upgrade to 1.603
	$config = loadConfig();
	if (isset($config['database_Location']) && (!isset($config['CONFIG_VERSION']) || $config['CONFIG_VERSION'] < '1.603')) {
		// Update Version and Commit
		$config['CONFIG_VERSION'] = '1.603';
		copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][1.601].bak.php');
		$createConfigSuccess = createConfig($config);
		unset($config);
		if(file_exists('org.log')){
			copy('org.log', DATABASE_LOCATION.'org.log');
			unlink('org.log');
		}
		if(file_exists('loginLog.json')){
			copy('loginLog.json', DATABASE_LOCATION.'loginLog.json');
			unlink('loginLog.json');
		}
	}

	return true;
}

// Get OS from server
function getOS(){
	if(PHP_SHLIB_SUFFIX == "dll"){
		return "win";
	}else{
		return "nix";
	}
}

//Get Error by Server OS
function getError($os, $error){
	$ini = (!empty(php_ini_loaded_file()) ? php_ini_loaded_file() : "php.ini");
	$ext = (!empty(ini_get('extension_dir')) ? "uncomment ;extension_dir = and make sure it says -> extension_dir = '".ini_get('extension_dir')."'" : "uncomment ;extension_dir = and add path to 'ext' to make it like extension_dir = 'C:\nginx\php\ext'");
	$errors = array(
		'pdo_sqlite' => array(
			'win' => '<b>PDO:SQLite</b> not enabled, uncomment ;extension=php_pdo_sqlite.dll in the file php.ini | '.$ext,
			'nix' => '<b>PDO:SQLite</b> not enabled, PHP7 -> run sudo apt-get install php7.0-sqlite | PHP5 -> run sudo apt-get install php5-sqlite',
		),
		'sqlite3' => array(
			'win' => '<b>SQLite3</b> not enabled, uncomment ;extension=php_sqlite3.dll in the file php.ini | uncomment ;sqlite3.extension_dir = and add "ext" to make it sqlite3.extension_dir = ext',
			'nix' => '<b>SQLite3</b> not enabled, run sudo apt-get install php-sqlite3',
		),
		'curl' => array(
			'win' => '<b>cURL</b> not enabled, uncomment ;extension=php_curl.dll in the file php.ini | '.$ext,
			'nix' => '<b>cURL</b> not enabled, PHP7 -> sudo apt-get install php-curl or sudo apt-get install php7.0-curl | PHP5 -> run sudo apt-get install php5.6-curl',
		),
		'zip' => array(
			'win' => '<b>PHP Zip</b> not enabled, uncomment ;extension=php_zip.dll in the file php.ini, if that doesn\'t work remove that line',
			'nix' => '<b>PHP Zip</b> not enabled, PHP7 -> run sudo apt-get install php7.0-zip | PHP5 -> run sudo apt-get install php5.6-zip',
		),

	);
	return (isset($errors[$error][$os]) ? $errors[$error][$os] : 'No Error Info Found');
}

// Check if all software dependancies are met
function dependCheck() {
	$output = array();
	$i = 1;
	if (!extension_loaded('pdo_sqlite')) { $output["Step $i"] = getError(getOS(),'pdo_sqlite'); $i++; }
	if (!extension_loaded('curl')) { $output["Step $i"] = getError(getOS(),'curl'); $i++; }
	if (!extension_loaded('zip')) { $output["Step $i"] = getError(getOS(),'zip'); $i++; }
	//if (!extension_loaded('sqlite3')) { $output[] = getError(getOS(),'sqlite3'); }

	if ($output) {
		$output["Step $i"] = "<b>Restart PHP and/or Webserver to apply changes</b>"; $i++;
		$output["Step $i"] = "<b>Please visit here to also check status of necessary components after you fix them: <a href='check.php'>check.php<a/></b>"; $i++;
		debug_out($output,1);
	}
	return true;
}

// Process file uploads
function uploadFiles($path, $ext_mask = null) {
	if (isset($_FILES) && count($_FILES)) {
		require_once('class.uploader.php');

		$uploader = new Uploader();
		$data = $uploader->upload($_FILES['files'], array(
			'limit' => 10,
			'maxSize' => 10,
			'extensions' => $ext_mask,
			'required' => false,
			'uploadDir' => str_replace('//','/',$path.'/'),
			'title' => array('name'),
			'removeFiles' => true,
			'replace' => true,
		));

		if($data['isComplete']){
			$files = $data['data'];
   			writeLog("success", $files['metas'][0]['name']." was uploaded");
			echo json_encode($files['metas'][0]['name']);
		}

		if($data['hasErrors']){
			$errors = $data['errors'];
   			writeLog("error", $files['metas'][0]['name']." was not able to upload");
			echo json_encode($errors);
		}
	} else {
  		writeLog("error", "image was not uploaded");
		echo json_encode('No files submitted!');
	}
}
// Process file uploads
function uploadAvatar($path, $ext_mask = null) {
	if (isset($_FILES) && count($_FILES)) {
		require_once('class.uploader.php');

		$uploader = new Uploader();
		$data = $uploader->upload($_FILES['files'], array(
			'limit' => 10,
			'maxSize' => 10,
			'extensions' => $ext_mask,
			'required' => false,
			'uploadDir' => str_replace('//','/',$path.'/'),
			'title' => array('name'),
			'removeFiles' => true,
			'replace' => true,
		));

		if($data['isComplete']){
			$files = $data['data'];
   			writeLog("success", $files['metas'][0]['name']." was uploaded");
			echo json_encode($files['metas'][0]['name']);
		}

		if($data['hasErrors']){
			$errors = $data['errors'];
   			writeLog("error", $files['metas'][0]['name']." was not able to upload");
			echo json_encode($errors);
		}
	} else {
  		writeLog("error", "image was not uploaded");
		echo json_encode('No files submitted!');
	}
}

// Remove file
function removeFiles($path) {
    if(is_file($path)) {
        writeLog("success", "file was removed");
        unlink($path);
    } else {
  		writeLog("error", "file was not removed");
		echo json_encode('No file specified for removal!');
	}
}

// Lazy select options
function resolveSelectOptions($array, $selected = '', $multi = false) {
	$output = array();
	$selectedArr = ($multi?explode('|', $selected):array());
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			if (isset($value['optgroup'])) {
				$output[] = '<optgroup label="'.$key.'">';
				foreach($value['optgroup'] as $k => $v) {
					$output[] = '<option value="'.$v['value'].'"'.($selected===$v['value']||in_array($v['value'],$selectedArr)?' selected':'').(isset($v['disabled']) && $v['disabled']?' disabled':'').'>'.$k.'</option>';
				}
			} else {
				$output[] = '<option value="'.$value['value'].'"'.($selected===$value['value']||in_array($value['value'],$selectedArr)?' selected':'').(isset($value['disabled']) && $value['disabled']?' disabled':'').'>'.$key.'</option>';
			}
		} else {
			$output[] = '<option value="'.$value.'"'.($selected===$value||in_array($value,$selectedArr)?' selected':'').'>'.$key.'</option>';
		}

	}
	return implode('',$output);
}

// Check if user is allowed to continue
function qualifyUser($type, $errOnFail = false) {
	if (!isset($GLOBALS['USER'])) {
		require_once("user.php");
		$GLOBALS['USER'] = new User('registration_callback');
	}

	if (is_bool($type)) {
		if ($type === true) {
			$authorized = ($GLOBALS['USER']->authenticated == true);
		} else {
			$authorized = true;
		}
	} elseif (is_string($type) || is_array($type)) {
		if ($type !== 'false') {
			if (!is_array($type)) {
				$type = explode('|',$type);
			}
			$authorized = ($GLOBALS['USER']->authenticated && in_array($GLOBALS['USER']->role,$type));
		} else {
			$authorized = true;
		}
	} else {
		debug_out('Invalid Syntax!',1);
	}

	if (!$authorized && $errOnFail) {
		if ($GLOBALS['USER']->authenticated) {
			header('Location: '.rtrim(dirname($_SERVER['SCRIPT_NAME']), '/').'/error.php?error=401');
			echo '<script>window.location.href = \''.rtrim(dirname($_SERVER['SCRIPT_NAME']), '/').'/error.php?error=401\'</script>';
		} else {
			header('Location: '.rtrim(dirname($_SERVER['SCRIPT_NAME']), '/').'/error.php?error=999');
			echo '<script>window.location.href = \''.rtrim(dirname($_SERVER['SCRIPT_NAME']), '/').'/error.php?error=999\'</script>';
		}

		debug_out('Not Authorized' ,1);
	} else {
		return $authorized;
	}
}

// Build an (optionally) tabbed settings page.
function buildSettings($array) {
	/*
	array(
		'title' => '',
		'id' => '',
		'fields' => array( See buildField() ),
		'tabs' => array(
			array(
				'title' => '',
				'id' => '',
				'image' => '',
				'fields' => array( See buildField() ),
			),
		),
	);
	*/

	$notifyExplode = explode("-", NOTIFYEFFECT);

	$fieldFunc = function($fieldArr) {
		$fields = '<div class="row">';
		foreach($fieldArr as $key => $value) {
			$isSingle = isset($value['type']);
			if ($isSingle) { $value = array($value); }
			$tmpField = '';
			$sizeLg = max(floor(12/count($value)),2);
			$sizeMd = max(floor(($isSingle?12:6)/count($value)),3);
			foreach($value as $k => $v) {
				$tmpField .= buildField($v, 12, $sizeMd, $sizeLg);
			}
			$fields .= ($isSingle?$tmpField:'<div class="row col-sm-12 content-form">'.$tmpField.'</div>');
		}
		$fields .= '</div>';
		return $fields;
	};

	$fields = (isset($array['fields'])?$fieldFunc($array['fields']):'');

	$tabSelectors = array();
	$tabContent = array();
	if (isset($array['tabs'])) {
		foreach($array['tabs'] as $key => $value) {
			$id = (isset($value['id'])?$value['id']:randString(32));
			$tabSelectors[$key] = '<li class="apps'.($tabSelectors?'':' active').'"><a href="#tab-'.$id.'" data-toggle="tab" aria-expanded="true"><img style="height:40px; width:40px;" src="'.(isset($value['image'])?$value['image']:'images/organizr.png').'"></a></li>';
			$tabContent[$key] = '<div class="tab-pane big-box fade'.($tabContent?'':' active in').'" id="tab-'.$id.'">'.$fieldFunc($value['fields']).'</div>';
		}
	}

	$pageID = (isset($array['id'])?$array['id']:str_replace(array(' ','"',"'"),array('_'),strtolower($array['id'])));
	$extraClick = ($pageID == 'appearance_settings' ? "$('#advanced_settings_form_submit').click();console.log('add theme settings');" : "");

	return '
	<div class="email-body">
		<div class="email-header gray-bg">
			<button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
			<button id="'.$pageID.'_form_submit" class="btn waves btn-labeled btn-success btn btn-sm text-uppercase waves-effect waves-float save-btn-form">
			<span class="btn-label"><i class="fa fa-floppy-o"></i></span>Save
			</button>
			<h1>'.$array['title'].'</h1>
		</div>
		<div class="email-inner small-box">
			<div class="email-inner-section">
				<div class="small-box fade in" id="'.$pageID.'_frame">
					<div class="col-lg-12">
						'.(isset($array['customBeforeForm'])?$array['customBeforeForm']:'').'
						<form class="content-form" name="'.$pageID.'" id="'.$pageID.'_form" onsubmit="return false;">
							'.$fields.($tabContent?'
							<div class="tabbable tabs-with-bg" id="'.$pageID.'_tabs">
								<ul class="nav nav-tabs apps">
									'.implode('', $tabSelectors).'
								</ul>
								<div class="clearfix"></div>
								<div class="tab-content">
									'.implode('', $tabContent).'
								</div>
							</div>':'').'
						</form>
						'.(isset($array['customAfterForm'])?$array['customAfterForm']:'').'
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
		$(document).ready(function() {
			$(\'#'.$pageID.'_form\').find(\'input, select, textarea\').on(\'change\', function() { $(this).attr(\'data-changed\', \'true\'); });
			var '.$pageID.'Validate = function() { if (this.value && !RegExp(\'^\'+this.pattern+\'$\').test(this.value)) { $(this).addClass(\'invalid\'); } else { $(this).removeClass(\'invalid\'); } };
			$(\'#'.$pageID.'_form\').find(\'input[pattern]\').each('.$pageID.'Validate).on(\'keyup\', '.$pageID.'Validate);
			$(\'#'.$pageID.'_form\').find(\'select[multiple]\').on(\'change click\', function() { $(this).attr(\'data-changed\', \'true\'); });

			$(\'#'.$pageID.'_form_submit\').on(\'click\', function () {
				var newVals = {};
				var hasVals = false;
				var errorFields = [];
				$(\'#'.$pageID.'_form\').find(\'[data-changed=true][name]\').each(function() {
					hasVals = true;
					if (this.type == \'checkbox\') {
						newVals[this.name] = this.checked;
					} else if ($(this).hasClass(\'summernote\')) {
						newVals[$(this).attr(\'name\')] = $(this).siblings(\'.note-editor\').find(\'.panel-body\').html();
					} else {
						if (this.value && this.pattern && !RegExp(\'^\'+this.pattern+\'$\').test(this.value)) { errorFields.push(this.name); }
						var fieldVal = $(this).val();
						if (typeof fieldVal == \'object\') {
							if (typeof fieldVal.join == \'function\') {
								fieldVal = fieldVal.join(\'|\');
							} else {
								fieldVal = JSON.stringify(fieldVal);
							}
						}
						newVals[this.name] = fieldVal;
					}
				});
				if (errorFields.length) {
					parent.notify(\'Fields have errors: \'+errorFields.join(\', \')+\'!\', \'bullhorn\', \'error\', 5000, \''.$notifyExplode[0].'\', \''.$notifyExplode[1].'\');
				} else if (hasVals) {
					console.log(newVals);
					ajax_request(\'POST\', \''.(isset($array['submitAction'])?$array['submitAction']:'update-config').'\', newVals, function(data, code) {
						$(\'#'.$pageID.'_form\').find(\'[data-changed=true][name]\').removeAttr(\'data-changed\');
					});
					'.$extraClick.'
				} else {
					parent.notify(\'Nothing to update!\', \'bullhorn\', \'error\', 5000, \''.$notifyExplode[0].'\', \''.$notifyExplode[1].'\');
				}
				return false;
			});
			'.(isset($array['onready'])?$array['onready']:'').'
		});
	</script>
	';
}

// Build Settings Fields
function buildField($params, $sizeSm = 12, $sizeMd = 12, $sizeLg = 12) {
	/*
	array(
		'type' => '',
		'placeholder' => '',
		'label' => '',
		'labelTranslate' => '',
		'assist' => '',
		'name' => '',
		'pattern' => '',
		'options' => array( // For SELECT only
			'Display' => 'value',
		),
	)
	*/

	// Tags
	$tags = array();
	foreach(array('placeholder','style','disabled','readonly','pattern','min','max','required','onkeypress','onchange','onfocus','onleave','href','onclick','autocomplete') as $value) {
		if (isset($params[$value])) {
			if (is_string($params[$value])) { $tags[] = $value.'="'.$params[$value].'"';
			} else if ($params[$value] === true) { $tags[] = $value; }
		}
	}

	$format = (isset($params['format']) && in_array($params['format'],array(false,'colour','color'))?$params['format']:false);
	$name = (isset($params['name'])?$params['name']:(isset($params['id'])?$params['id']:''));
	$id = (isset($params['id'])?$params['id']:(isset($params['name'])?$params['name'].'_id':randString(32)));
	$val = (isset($params['value'])?$params['value']:'');
	$class = (isset($params['class'])?' '.$params['class']:'');
	$wrapClass = (isset($params['wrapClass'])?$params['wrapClass']:'form-content');
	$assist = (isset($params['assist'])?' - i.e. '.$params['assist']:'');
	$label = (isset($params['labelTranslate'])?translate($params['labelTranslate']):(isset($params['label'])?$params['label']:''));
	$labelOut = '<p class="help-text">'.$label.$assist.'</p>';

	// Field Design
	switch ($params['type']) {
		case 'text':
		case 'number':
		case 'password':
			$field = '<input id="'.$id.'" name="'.$name.'" type="'.$params['type'].'" class="form-control material input-sm'.$class.'" '.implode(' ',$tags).' autocorrect="off" autocapitalize="off" value="'.$val.'">';
			break;
		case 'select':
		case 'dropdown':
			$field = '<select id="'.$id.'" name="'.$name.'" class="form-control material input-sm" '.implode(' ',$tags).'>'.resolveSelectOptions($params['options'], $val).'</select>';
			break;
		case 'select-multi':
		case 'dropdown-multi':
			$field = '<select id="'.$id.'" name="'.$name.'" class="form-control input-sm" '.implode(' ',$tags).' multiple="multiple">'.resolveSelectOptions($params['options'], $val, true).'</select>';
			break;
		case 'check':
		case 'checkbox':
		case 'toggle':
			$checked = ((is_bool($val) && $val) || trim($val) === 'true'?' checked':'');
			$colour = (isset($params['colour'])?$params['colour']:'success');
			$labelOut = '<label for="'.$id.'"></label>'.$label;
			$field = '<input id="'.$id.'" name="'.$name.'" type="checkbox" class="switcher switcher-'.$colour.' '.$class.'" '.implode(' ',$tags).' data-value="'.$val.'"'.$checked.'>';
			break;
		case 'radio':
			$labelOut = '';
			$checked = ((is_bool($val) && $val) || ($val && trim($val) !== 'false')?' checked':'');
			$bType = (isset($params['buttonType'])?$params['buttonType']:'success');
			$field = '<div class="radio radio-'.$bType.'"><input id="'.$id.'" name="'.$name.'" type="radio" class="'.$class.'" '.implode(' ',$tags).' value="'.$val.'"'.$checked.'><label for="'.$id.'">'.$label.'</label></div>';
			break;
		case 'date':
			$field = 'Unsupported, planned.';
			break;
		case 'hidden':
			return '<input id="'.$id.'" name="'.$name.'" type="hidden" class="'.$class.'" '.implode(' ',$tags).' value="'.$val.'">';
			break;
		case 'header':
			$labelOut = '';
			$headType = (isset($params['value'])?$params['value']:3);
			$field = '<h'.$headType.' class="'.$class.'" '.implode(' ',$tags).'>'.$label.'</h'.$headType.'>';
			break;
		case 'button':
			$labelOut = '';
			$icon = (isset($params['icon'])?$params['icon']:'flask');
			$bType = (isset($params['buttonType'])?$params['buttonType']:'success');
			$bDropdown = (isset($params['buttonDrop'])?$params['buttonDrop']:'');
			$field = ($bDropdown?'<div class="btn-group">':'').'<button id="'.$id.'" type="button" class="btn waves btn-labeled btn-'.$bType.' btn-sm text-uppercase waves-effect waves-float'.$class.''.($bDropdown?' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"':'"').' '.implode(' ',$tags).'><span class="btn-label"><i class="fa fa-'.$icon.'"></i></span><span class="btn-text">'.$label.'</span></button>'.($bDropdown?$bDropdown.'</div>':'');
			break;
		case 'textarea':
			$rows = (isset($params['rows'])?$params['rows']:5);
			$field = '<textarea id="'.$id.'" name="'.$name.'" class="form-control'.$class.'" rows="'.$rows.'" '.implode(' ',$tags).'>'.$val.'</textarea>';
			break;
		case 'custom':
			// Settings
			$settings = array(
				'$id' => $id,
				'$name' => $name,
				'$val' => $val,
				'$label' => $label,
				'$labelOut' => $labelOut,
			);
			// Get HTML
			$html = (isset($params['html'])?$params['html']:'Nothing Specified!');
			// If LabelOut is in html dont print it twice
			$labelOut = (strpos($html,'$label')!==false?'':$labelOut);
			// Replace variables in settings
			$html = preg_replace_callback('/\$\w+\b/', function ($match) use ($settings) { return (isset($settings[$match[0]])?$settings[$match[0]]:'{'.$match[0].' is undefined}'); }, $html);
			// Build Field
			$field = '<div id="'.$id.'_html" class="custom-field">'.$html.'</div>';
			break;
		case 'space':
			$labelOut = '';
			$field = str_repeat('<br>', (isset($params['value'])?$params['value']:1));
			break;
		default:
			$field = 'Unsupported field type';
			break;
	}

	// Field Formats
	switch ($format) {
		case 'colour': // Fuckin Eh, Canada!
		case 'color':
			$labelBef = '<center>'.$label.'</center>';
			$wrapClass = 'gray-bg colour-field';
			$labelAft = '';
			$field = str_replace(' material input-sm','',$field);
			break;
		default:
			$labelBef = '';
			$labelAft = $labelOut;
	}

	return '<div class="'.$wrapClass.' col-sm-'.$sizeSm.' col-md-'.$sizeMd.' col-lg-'.$sizeLg.'">'.$labelBef.$field.$labelAft.'</div>';
}

// Tab Settings Generation
function printTabRow($data) {
	$hidden = false;
	if ($data===false) {
		$hidden = true;
		$data = array( // New Tab Defaults
			'id' => 'new',
			'name' => '',
			'url' => '',
			'icon' => 'fa-diamond',
			'iconurl' => '',
			'active' => 'true',
			'user' => 'true',
			'guest' => 'true',
			'window' => 'false',
			'splash' => 'true',
			'ping' => 'false',
			'ping_url' => '',
			'defaultz' => '',
		);
	}
	$image = '<span style="font: normal normal normal 30px/1 FontAwesome;" class="fa fa-hand-paper-o"></span>';

	$output = '
		<li id="tab-'.$data['id'].'" class="list-group-item" style="position: relative; left: 0px; top: 0px; '.($hidden?' display: none;':'').'">
			<tab class="content-form form-inline">
				<div class="row">
					'.buildField(array(
						'type' => 'custom',
						'html' => '<div class="action-btns tabIconView"><a style="margin-left: 0px">'.($data['iconurl']?'<img src="'.$data['iconurl'].'" height="30" width="30">':'<span style="font: normal normal normal 30px/1 FontAwesome;" class="fa '.($data['icon']?$data['icon']:'hand-paper-o').'"></span>').'</a></div>',
					),12,1,1).'
					'.buildField(array(
						'type' => 'hidden',
						'id' => 'tab-'.$data['id'].'-id',
						'name' => 'id['.$data['id'].']',
						'value' => $data['id'],
					),12,2,1).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-name',
						'name' => 'name['.$data['id'].']',
						'required' => true,
						'placeholder' => 'Organizr Homepage',
						'labelTranslate' => 'TAB_NAME',
						'value' => $data['name'],
						'class' => 'darkBold',
					),12,2,1).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-url',
						'name' => 'url['.$data['id'].']',
						'required' => true,
						'placeholder' => 'homepage.php',
						'labelTranslate' => 'TAB_URL',
						'value' => $data['url'],
						'class' => 'darkBold',
					),12,2,2).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-iconurl',
						'name' => 'iconurl['.$data['id'].']',
						'placeholder' => 'images/organizr.png',
						'labelTranslate' => 'ICON_URL',
						'value' => $data['iconurl'],
						'class' => 'darkBold',
					),12,2,1).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-icon',
						'name' => 'icon['.$data['id'].']',
						'placeholder' => 'fa-icon',
						'labelTranslate' => 'OR_ICON_NAME',
						'value' => $data['icon'],
						'class' => 'iconpickeradd darkBold',
					),12,1,1).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-ping_url',
						'name' => 'ping_url['.$data['id'].']',
						'placeholder' => 'host:port',
						'labelTranslate' => 'PING_URL',
						'value' => $data['ping_url'],
						'class' => 'darkBold',
					),12,2,1).'
					'.buildField(array(
						'type' => 'radio',
						'labelTranslate' => 'DEFAULT',
						'name' => 'defaultz['.$data['id'].']',
						'value' => $data['defaultz'],
						'onclick' => "$('[type=radio][id!=\''+this.id+'\']').each(function() { this.checked=false; });",
					),12,1,1).'
					'.buildField(array(
						'type' => 'button',
						'icon' => 'chevron-down',
                        'buttonType' => 'success',
						'labelTranslate' => 'MORE',
						'onclick' => "$(this).parent().parent().parent().find('.slideInUp').toggle()",
						'class' => 'toggleTabExtra',
					),12,1,1).'
					'.buildField(array(
						'type' => 'button',
						'icon' => 'trash',
                        'buttonType' => 'danger',
						'labelTranslate' => 'REMOVE',
						'onclick' => "$(this).parents('li').remove();",
					),12,1,1).'</div><div id = "tab-'.$data['id'].'-row" class = "row animated slideInUp" style = "display:none;" ><div></div>
					'.buildField(array(
						'type' => 'checkbox',
						'labelTranslate' => 'ACTIVE',
						'name' => 'active['.$data['id'].']',
						'value' => $data['active'],
					),12,1,1).'
					'.buildField(array(
						'type' => 'checkbox',
						'labelTranslate' => 'USER',
						'colour' => 'primary',
						'name' => 'user['.$data['id'].']',
						'value' => $data['user'],
					),12,1,1).'
					'.buildField(array(
						'type' => 'checkbox',
						'labelTranslate' => 'GUEST',
						'colour' => 'warning',
						'name' => 'guest['.$data['id'].']',
						'value' => $data['guest'],
					),12,1,1).'
					'.buildField(array(
						'type' => 'checkbox',
						'labelTranslate' => 'NO_IFRAME',
						'colour' => 'danger',
						'name' => 'window['.$data['id'].']',
						'value' => $data['window'],
					),12,1,1).'
					'.buildField(array(
						'type' => 'checkbox',
						'labelTranslate' => 'SPLASH',
						'colour' => 'success',
						'name' => 'splash['.$data['id'].']',
						'value' => $data['splash'],
					),12,1,1).'
					'.buildField(array(
						'type' => 'checkbox',
						'labelTranslate' => 'PING',
						'colour' => 'success',
						'name' => 'ping['.$data['id'].']',
						'value' => $data['ping'],
					),12,1,1).'
				</div>
			</tab>
		</li>
	';
	return $output;
}

// Timezone array
function timezoneOptions() {
	$output = array();
	$timezones = array();
    $regions = array(
        'Africa' => DateTimeZone::AFRICA,
        'America' => DateTimeZone::AMERICA,
        'Antarctica' => DateTimeZone::ANTARCTICA,
        'Arctic' => DateTimeZone::ARCTIC,
        'Asia' => DateTimeZone::ASIA,
        'Atlantic' => DateTimeZone::ATLANTIC,
        'Australia' => DateTimeZone::AUSTRALIA,
        'Europe' => DateTimeZone::EUROPE,
        'Indian' => DateTimeZone::INDIAN,
        'Pacific' => DateTimeZone::PACIFIC
    );

    foreach ($regions as $name => $mask) {
        $zones = DateTimeZone::listIdentifiers($mask);
        foreach($zones as $timezone) {
            $time = new DateTime(NULL, new DateTimeZone($timezone));
            $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';

			$output[$name]['optgroup'][substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm]['value'] = $timezone;
        }
    }

	return $output;
}

// Build Database
function createSQLiteDB($path = false) {
	if ($path === false) {
		if (DATABASE_LOCATION){
			$path = DATABASE_LOCATION;
		} else {
			debug_out('No Path Specified!');
		}
	}

	if (!is_file($path.'users.db') || filesize($path.'users.db') <= 0) {
		if (!isset($GLOBALS['file_db'])) {
			$GLOBALS['file_db'] = new PDO('sqlite:'.$path.'users.db');
			$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		// Create Users
		$users = $GLOBALS['file_db']->query('CREATE TABLE `users` (
			`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			`username`	TEXT UNIQUE,
			`password`	TEXT,
			`email`	TEXT,
			`token`	TEXT,
			`role`	TEXT,
			`active`	TEXT,
			`last`	TEXT,
			`auth_service`	TEXT DEFAULT \'internal\'
		);');

		// Create Tabs
		$tabs = $GLOBALS['file_db']->query('CREATE TABLE `tabs` (
			`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			`order`	INTEGER,
			`users_id`	INTEGER,
			`name`	TEXT,
			`url`	TEXT,
			`defaultz`	TEXT,
			`active`	TEXT,
			`user`	TEXT,
			`guest`	TEXT,
			`icon`	TEXT,
			`iconurl`	TEXT,
			`window`	TEXT,
			`splash`	TEXT,
			`ping`		TEXT,
			`ping_url`	TEXT
		);');

		// Create Options
		$options = $GLOBALS['file_db']->query('CREATE TABLE `options` (
			`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			`users_id`	INTEGER UNIQUE,
			`title`	TEXT UNIQUE,
			`topbar`	TEXT,
			`bottombar`	TEXT,
			`sidebar`	TEXT,
			`hoverbg`	TEXT,
			`topbartext`	TEXT,
			`activetabBG`	TEXT,
			`activetabicon`	TEXT,
			`activetabtext`	TEXT,
			`inactiveicon`	TEXT,
			`inactivetext`	TEXT,
			`loading`	TEXT,
			`hovertext`	TEXT
		);');

		// Create Invites
		$invites = $GLOBALS['file_db']->query('CREATE TABLE `invites` (
			`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
			`code`	TEXT UNIQUE,
			`date`	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			`email`	TEXT,
			`username`	TEXT,
			`dateused`	TIMESTAMP,
			`usedby`	TEXT,
			`ip`	TEXT,
			`valid`	TEXT
		);');

		writeLog("success", "database created/saved");
		return $users && $tabs && $options && $invites;
	} else {
  		writeLog("error", "database was unable to be created/saved");
		return false;
	}
}

// Upgrade Database
function updateSQLiteDB($db_path = false, $oldVerNum = false) {
	if (!$db_path) {
		if (defined('DATABASE_LOCATION')) {
			$db_path = DATABASE_LOCATION;
		} else {
			debug_out('No Path Specified',1);
		}
	}
	if (!isset($GLOBALS['file_db'])) {
		$GLOBALS['file_db'] = new PDO('sqlite:'.$db_path.'users.db');
		$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	// Cache current DB
	$cache = array();
	foreach($GLOBALS['file_db']->query('SELECT name FROM sqlite_master WHERE type="table";') as $table) {
		foreach($GLOBALS['file_db']->query('SELECT * FROM '.$table['name'].';') as $key => $row) {
			foreach($row as $k => $v) {
				if (is_string($k)) {
					$cache[$table['name']][$key][$k] = $v;
				}
			}
		}
	}

	// Remove Current Database
	$GLOBALS['file_db'] = null;
	$pathDigest = pathinfo($db_path.'users.db');
	if (file_exists($db_path.'users.db')) {
		rename($db_path.'users.db', $pathDigest['dirname'].'/'.$pathDigest['filename'].'['.date('Y-m-d_H-i-s').']'.($oldVerNum?'['.$oldVerNum.']':'').'.bak.db');
	}

	// Create New Database
	$success = createSQLiteDB($db_path);

	// Restore Items
	if ($success) {
		foreach($cache as $table => $tableData) {
			if ($tableData) {
				$queryBase = 'INSERT INTO '.$table.' (`'.implode('`,`',array_keys(current($tableData))).'`) values ';
				$insertValues = array();
				reset($tableData);
				foreach($tableData as $key => $value) {
					$insertValues[] = '('.implode(',',array_map(function($d) {
						return (isset($d)?$GLOBALS['file_db']->quote($d):'null');
					}, $value)).')';
				}
				$GLOBALS['file_db']->query($queryBase.implode(',',$insertValues).';');
			}
		}
  writeLog("success", "database values have been updated");
		return true;
	} else {
  writeLog("error", "database values unable to be updated");
		return false;
	}
}

// Commit colours to database
function updateDBOptions($values) {
	if (!isset($GLOBALS['file_db'])) {
		$GLOBALS['file_db'] = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
		$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	// Commit new values to database
	if ($GLOBALS['file_db']->query('UPDATE options SET '.implode(',',array_map(function($d, $k) {
		return '`'.$k.'` = '.(isset($d)?"'".addslashes($d)."'":'null');
	}, $values, array_keys($values))).';')->rowCount()) {
		return true;
	} else if ($GLOBALS['file_db']->query('INSERT OR IGNORE INTO options (`'.implode('`,`',array_keys($values)).'`) VALUES (\''.implode("','",$values).'\');')->rowCount()) {
  writeLog("success", "database values for options table have been updated");
		return true;
	} else {
  writeLog("error", "database values for options table unable to be updated");
		return false;
	}
}

// Send AJAX notification
function sendNotification($success, $message = false, $send = true) {
	$notifyExplode = explode("-", NOTIFYEFFECT);
	if ($success) {
		$msg = array(
			'html' => ($message?''.$message:'<strong>'.translate("SETTINGS_SAVED").'</strong>'),
			'icon' => 'floppy-o',
			'type' => 'success',
			'length' => '5000',
			'layout' => $notifyExplode[0],
			'effect' => $notifyExplode[1],
		);
	} else {
		$msg = array(
			'html' => ($message?''.$message:'<strong>'.translate("SETTINGS_NOT_SAVED").'</strong>'),
			'icon' => 'floppy-o',
			'type' => 'failed',
			'length' => '5000',
			'layout' => $notifyExplode[0],
			'effect' => $notifyExplode[1],
		);
	}

	// Send and kill script?
	if ($send) {
		header('Content-Type: application/json');
		echo json_encode(array('notify'=>$msg));
		die();
	}
	return $msg;
}

// Load colours from the database
function loadAppearance() {
	// Defaults
	$defaults = array(
		'title' => 'Organizr',
		'topbartext' => '#66D9EF',
		'topbar' => '#333333',
		'bottombar' => '#333333',
		'sidebar' => '#393939',
		'hoverbg' => '#AD80FD',
		'activetabBG' => '#F92671',
		'activetabicon' => '#FFFFFF',
		'activetabtext' => '#FFFFFF',
		'inactiveicon' => '#66D9EF',
		'inactivetext' => '#66D9EF',
		'loading' => '#66D9EF',
		'hovertext' => '#000000',
	);

	if (DATABASE_LOCATION) {
		if(is_file(DATABASE_LOCATION.'users.db') && filesize(DATABASE_LOCATION.'users.db') > 0){
			if (!isset($GLOBALS['file_db'])) {
				$GLOBALS['file_db'] = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
				$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}

			// Database Lookup
			$options = $GLOBALS['file_db']->query('SELECT * FROM options');
			// Replace defaults with filled options
			foreach($options as $row) {
				foreach($defaults as $key => $value) {
					if (isset($row[$key]) && $row[$key]) {
						$defaults[$key] = $row[$key];
					}
				}
			}
		}
	}

	// Return the Results
	return $defaults;
}

// Delete Database
function deleteDatabase() {
    unset($_COOKIE['Organizr']);
    setcookie('Organizr', '', time() - 3600, '/');
    unset($_COOKIE['OrganizrU']);
    setcookie('OrganizrU', '', time() - 3600, '/');

    $GLOBALS['file_db'] = null;

    unlink(DATABASE_LOCATION.'users.db');

    foreach(glob(substr_replace($userdirpath, "", -1).'/*') as $file) {
        if(is_dir($file)) {
            rmdir($file);
        } elseif (!is_dir($file)) {
            unlink($file);
        }
	}

    rmdir($userdirpath);
	writeLog("success", "database has been deleted");
	return true;
}

// Upgrade the installation
function upgradeInstall($branch = 'master') {
    function downloadFile($url, $path){
        ini_set('max_execution_time',0);
        $folderPath = "upgrade/";
        if(!mkdir($folderPath)){
            writeLog("error", "organizr could not create upgrade folder");
        }
        $newfname = $folderPath . $path;
        $file = fopen ($url, 'rb');
        if ($file) {
            $newf = fopen ($newfname, 'wb');
            if ($newf) {
                while(!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                }
            }
        }else{
            writeLog("error", "organizr could not download $url");
        }

        if ($file) {
            fclose($file);
            writeLog("success", "organizr finished downloading the github zip file");
        }else{
            writeLog("error", "organizr could not download the github zip file");
        }

        if ($newf) {
            fclose($newf);
            writeLog("success", "organizr created upgrade zip file from github zip file");
        }else{
            writeLog("error", "organizr could not create upgrade zip file from github zip file");
        }
    }

    function unzipFile($zipFile){
        $zip = new ZipArchive;
        $extractPath = "upgrade/";
        if($zip->open($extractPath . $zipFile) != "true"){
            writeLog("error", "organizr could not unzip upgrade.zip");
        }else{
            writeLog("success", "organizr unzipped upgrade.zip");
        }

        /* Extract Zip File */
        $zip->extractTo($extractPath);
        $zip->close();
    }

    // Function to remove folders and files
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file)
                if ($file != "." && $file != "..") rrmdir("$dir/$file");
            rmdir($dir);
        }
        else if (file_exists($dir)) unlink($dir);
    }

    // Function to Copy folders and files
    function rcopy($src, $dst) {
        if (is_dir ( $src )) {
            if (!file_exists($dst)) : mkdir ( $dst ); endif;
            $files = scandir ( $src );
            foreach ( $files as $file )
                if ($file != "." && $file != "..")
                    rcopy ( "$src/$file", "$dst/$file" );
        } else if (file_exists ( $src ))
            copy ( $src, $dst );
    }

    $url = 'https://github.com/causefx/Organizr/archive/'.$branch.'.zip';
    $file = "upgrade.zip";
    $source = __DIR__ . '/upgrade/Organizr-'.$branch.'/';
    $cleanup = __DIR__ . "/upgrade/";
    $destination = __DIR__ . "/";
	writeLog("success", "starting organizr upgrade process");
    downloadFile($url, $file);
    unzipFile($file);
    rcopy($source, $destination);
    writeLog("success", "new organizr files copied");
    rrmdir($cleanup);
    writeLog("success", "organizr upgrade folder removed");
	writeLog("success", "organizr has been updated");
	return true;
}
// Transmission Items
function transmissionConnect($list = 'listgroups') {
    $url = qualifyURL(TRANSMISSIONURL);
	$digest = parse_url($url);
	$scheme = (isset($digest['scheme'])) ? $digest['scheme'].'://' : 'http://';
	$host = (isset($digest['host'])) ? $digest['host'] : '';
	$port = (isset($digest['port'])) ? ':'.$digest['port'] : '';
	$path = (isset($digest['path'])) ? $digest['path'] : '';
	$passwordInclude = (TRANSMISSIONUSERNAME != '' && TRANSMISSIONPASSWORD != '') ? TRANSMISSIONUSERNAME.':'.TRANSMISSIONPASSWORD."@" : '';
	$url = $scheme.$passwordInclude.$host.$port.$path.'/rpc';
	$contextopts = array(
		'http' => array(
			'user_agent'  => 'HTTP_UA',
			'ignore_errors' => true,
		)
	);
	$context  = stream_context_create( $contextopts );
	$fp = @fopen( $url, 'r', false, $context );
	$stream_meta = stream_get_meta_data( $fp );
    fclose( $fp );
	foreach( $stream_meta['wrapper_data'] as $header ){
		if( strpos( $header, 'X-Transmission-Session-Id: ' ) === 0 ){
			$session_id = trim( substr( $header, 27 ) );
			break;
		}
	}

	$headers = array(
		'X-Transmission-Session-Id' => $session_id,
		'Content-Type' => 'application/json'
	);
	$data = array(
		'method' => 'torrent-get',
		'arguments' => array(
			'fields' => array(
				"id", "name", "totalSize", "eta", "isFinished", "isStalled", "percentDone", "rateDownload", "status", "downloadDir"
			),
		),
		'tags' => ''
	);
    $api = curl_post($url, $data, $headers);
    $api = json_decode($api['content'], true);
    $gotTorrent = array();
    if (is_array($api) || is_object($api)){
		foreach ($api['arguments']['torrents'] AS $child) {
			$downloadName = htmlentities($child['name'], ENT_QUOTES);
			$downloadDirectory = $child['downloadDir'];
			$downloadPercent = $child['percentDone'] * 100;
			$progressBar = "progress-bar-striped active";
			if($child['status'] == "6"){
				$downloadStatus = "Seeding";
				$downloadHealth = "success";
			}elseif($child['status'] == "4"){
				$downloadStatus = "Downloading";
				$downloadHealth = "danger";
			}elseif($child['status'] == "3"){
				$downloadStatus = "Queued";
				$downloadHealth = "warning";
			}elseif($child['status'] == "0"){
				$downloadStatus = "Complete";
				$downloadHealth = "success";
			}
			$gotTorrent[] = '<tr>
							<td class="col-xs-6 nzbtable-file-row">'.$downloadName.'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">'.$downloadStatus.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.$downloadDirectory.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.realSize($child['totalSize']).'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">
								<div class="progress">
									<div class="progress-bar progress-bar-'.$downloadHealth.' '.$progressBar.'" role="progressbar" aria-valuenow="'.$downloadPercent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$downloadPercent.'%">
										<p class="text-center">'.round($downloadPercent).'%</p>
										<span class="sr-only">'.$downloadPercent.'% Complete</span>
									</div>
								</div>
							</td>
						</tr>';
		}
		if ($gotTorrent) {
			return implode('',$gotTorrent);
		} else {
			return '<tr><td colspan="5"><p class="text-center">No Results</p></td></tr>';
		}
	}else{
		writeLog("error", "TRANSMISSION ERROR: could not connect - check URL and/or check token and/or Username and Password - if HTTPS, is cert valid");
	}
}

// NzbGET Items
function nzbgetConnect($list = 'listgroups') {
    $url = qualifyURL(NZBGETURL);

    $api = curl_get($url.'/'.NZBGETUSERNAME.':'.NZBGETPASSWORD.'/jsonrpc/'.$list);
    $api = json_decode($api, true);
    $gotNZB = array();
    if (is_array($api) || is_object($api)){
		foreach ($api['result'] AS $child) {
			$downloadName = htmlentities($child['NZBName'], ENT_QUOTES);
			$downloadStatus = $child['Status'];
			$downloadCategory = $child['Category'];
			if($list == "history"){ $downloadPercent = "100"; $progressBar = ""; }
			if($list == "listgroups"){ $downloadPercent = (($child['FileSizeMB'] - $child['RemainingSizeMB']) / $child['FileSizeMB']) * 100; $progressBar = "progress-bar-striped active"; }
			if($child['Health'] <= "750"){
				$downloadHealth = "danger";
			}elseif($child['Health'] <= "900"){
				$downloadHealth = "warning";
			}elseif($child['Health'] <= "1000"){
				$downloadHealth = "success";
			}

			$gotNZB[] = '<tr>
							<td class="col-xs-6 nzbtable-file-row">'.$downloadName.'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">'.$downloadStatus.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.$downloadCategory.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.realSize(($child['FileSizeMB']*1024)*1024).'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">
								<div class="progress">
									<div class="progress-bar progress-bar-'.$downloadHealth.' '.$progressBar.'" role="progressbar" aria-valuenow="'.$downloadPercent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$downloadPercent.'%">
										<p class="text-center">'.round($downloadPercent).'%</p>
										<span class="sr-only">'.$downloadPercent.'% Complete</span>
									</div>
								</div>
							</td>
						</tr>';
		}

		if ($gotNZB) {
			return implode('',$gotNZB);
		} else {
			return '<tr><td colspan="5"><p class="text-center">No Results</p></td></tr>';
		}
	}else{
		writeLog("error", "NZBGET ERROR: could not connect - check URL and/or check token and/or Username and Password - if HTTPS, is cert valid");
	}
}

// Sabnzbd Items
function sabnzbdConnect($list = 'queue') {
    $url = qualifyURL(SABNZBDURL);

    $api = @file_get_contents($url.'/api?mode='.$list.'&output=json&apikey='.SABNZBDKEY);
    $api = json_decode($api, true);

    $gotNZB = array();
	if (is_array($api) || is_object($api)){
	    foreach ($api[$list]['slots'] AS $child) {
	        if($list == "queue"){ $downloadName = $child['filename']; $downloadCategory = $child['cat']; $downloadPercent = (($child['mb'] - $child['mbleft']) / $child['mb']) * 100; $progressBar = "progress-bar-striped active"; }
	        if($list == "history"){ $downloadName = $child['name']; $downloadCategory = $child['category']; $downloadPercent = "100"; $progressBar = ""; }
	        $downloadStatus = $child['status'];

	        $gotNZB[] = '<tr>
							<td class="col-xs-6 nzbtable-file-row">'.$downloadName.'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">'.$downloadStatus.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.$downloadCategory.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.$child['size'].'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">
								<div class="progress">
									<div class="progress-bar progress-bar-success '.$progressBar.'" role="progressbar" aria-valuenow="'.$downloadPercent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$downloadPercent.'%">
										<p class="text-center">'.round($downloadPercent).'%</p>
										<span class="sr-only">'.$downloadPercent.'% Complete</span>
									</div>
								</div>
							</td>
						</tr>';
	    }

		if ($gotNZB) {
			return implode('',$gotNZB);
		} else {
			return '<tr><td colspan="5"><p class="text-center">No Results</p></td></tr>';
		}
	}else{
		writeLog("error", "SABNZBD ERROR: could not connect - check URL and/or check token - if HTTPS, is cert valid");
	}
}

// Apply new tab settings
function updateTabs($tabs) {
	if (!isset($GLOBALS['file_db'])) {
		$GLOBALS['file_db'] = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
		$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	// Validate
	if (!isset($tabs['defaultz'])) { $tabs['defaultz'][current(array_keys($tabs['name']))] = 'true'; }
	if (isset($tabs['name']) && isset($tabs['url']) && is_array($tabs['name'])) {
		// Clear Existing Tabs
		$GLOBALS['file_db']->query("DELETE FROM tabs");
		// Process New Tabs
		$totalValid = 0;
		foreach ($tabs['name'] as $key => $value) {
			// Qualify
			if (!$value || !isset($tabs['url']) || !$tabs['url'][$key]) { continue; }
			$totalValid++;
			$fields = array();
			foreach(array('id','name','url','icon','iconurl','order','ping_url') as $v) {
				if (isset($tabs[$v]) && isset($tabs[$v][$key])) { $fields[$v] = $tabs[$v][$key]; }
			}
			foreach(array('active','user','guest','defaultz','window','splash','ping') as $v) {
				if (isset($tabs[$v]) && isset($tabs[$v][$key])) { $fields[$v] = ($tabs[$v][$key]!=='false'?'true':'false'); }
			}
			$GLOBALS['file_db']->query('INSERT INTO tabs (`'.implode('`,`',array_keys($fields)).'`) VALUES (\''.implode("','",$fields).'\');');
		}
  		writeLog("success", "tabs successfully saved");
		return $totalValid;
	} else {
  		writeLog("error", "tabs could not save");
		return false;
	}
 	writeLog("error", "tabs could not save");
	return false;
}

// ==============

function clean($strin) {
    $strout = null;

    for ($i = 0; $i < strlen($strin); $i++) {
            $ord = ord($strin[$i]);

            if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
                    $strout .= "&amp;#{$ord};";
            }
            else {
                    switch ($strin[$i]) {
                            case '<':
                                    $strout .= '&lt;';
                                    break;
                            case '>':
                                    $strout .= '&gt;';
                                    break;
                            case '&':
                                    $strout .= '&amp;';
                                    break;
                            case '"':
                                    $strout .= '&quot;';
                                    break;
                            default:
                                    $strout .= $strin[$i];
                    }
            }
    }

    return $strout;

}

function registration_callback($username, $email, $userdir){

    global $data;

    $data = array($username, $email, $userdir);

}

function printArray($arrayName){

    $messageCount = count($arrayName);

    $i = 0;

    foreach ( $arrayName as $item ) :

        $i++;

        if($i < $messageCount) :

            echo "<small class='text-uppercase'>" . $item . "</small> & ";

        elseif($i = $messageCount) :

            echo "<small class='text-uppercase'>" . $item . "</small>";

        endif;

    endforeach;

}

function write_ini_file($content, $path) {

    if (!$handle = fopen($path, 'w')) {

        return false;

    }

    $success = fwrite($handle, trim($content));

    fclose($handle);

    return $success;

}

function gotTimezone(){

    $regions = array(
        'Africa' => DateTimeZone::AFRICA,
        'America' => DateTimeZone::AMERICA,
        'Antarctica' => DateTimeZone::ANTARCTICA,
        'Arctic' => DateTimeZone::ARCTIC,
        'Asia' => DateTimeZone::ASIA,
        'Atlantic' => DateTimeZone::ATLANTIC,
        'Australia' => DateTimeZone::AUSTRALIA,
        'Europe' => DateTimeZone::EUROPE,
        'Indian' => DateTimeZone::INDIAN,
        'Pacific' => DateTimeZone::PACIFIC
    );

    $timezones = array();

    foreach ($regions as $name => $mask) {

        $zones = DateTimeZone::listIdentifiers($mask);

        foreach($zones as $timezone) {

            $time = new DateTime(NULL, new DateTimeZone($timezone));

            $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';

            $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;

        }

    }

    print '<select name="timezone" id="timezone" class="form-control material input-sm" required>';

    foreach($timezones as $region => $list) {

        print '<optgroup label="' . $region . '">' . "\n";

        foreach($list as $timezone => $name) {

            if($timezone == TIMEZONE) : $selected = " selected"; else : $selected = ""; endif;

            print '<option value="' . $timezone . '"' . $selected . '>' . $name . '</option>' . "\n";

        }

        print '</optgroup>' . "\n";

    }

    print '</select>';

}

function getTimezone(){

    $regions = array(
        'Africa' => DateTimeZone::AFRICA,
        'America' => DateTimeZone::AMERICA,
        'Antarctica' => DateTimeZone::ANTARCTICA,
        'Arctic' => DateTimeZone::ARCTIC,
        'Asia' => DateTimeZone::ASIA,
        'Atlantic' => DateTimeZone::ATLANTIC,
        'Australia' => DateTimeZone::AUSTRALIA,
        'Europe' => DateTimeZone::EUROPE,
        'Indian' => DateTimeZone::INDIAN,
        'Pacific' => DateTimeZone::PACIFIC
    );

    $timezones = array();

    foreach ($regions as $name => $mask) {

        $zones = DateTimeZone::listIdentifiers($mask);

        foreach($zones as $timezone) {

            $time = new DateTime(NULL, new DateTimeZone($timezone));

            $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';

            $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;

        }

    }

    print '<select name="timezone" id="timezone" class="form-control material" required>';

    foreach($timezones as $region => $list) {

        print '<optgroup label="' . $region . '">' . "\n";

        foreach($list as $timezone => $name) {

            print '<option value="' . $timezone . '">' . $name . '</option>' . "\n";

        }

        print '</optgroup>' . "\n";

    }

    print '</select>';

}

function explosion($string, $position){

    $getWord = explode("|", $string);
    return $getWord[$position];

}

function getServerPath() {
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https"){
		$protocol = "https://";
	}elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https://";
    } else {
        $protocol = "http://";
    }
	$domain = '';
    if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], '.') !== false){
        $domain = $_SERVER['SERVER_NAME'];
	}elseif(isset($_SERVER['HTTP_HOST'])){
		if (strpos($_SERVER['HTTP_HOST'], ':') !== false) {
			$domain = explode(':', $_SERVER['HTTP_HOST'])[0];
			$port = explode(':', $_SERVER['HTTP_HOST'])[1];
			if ($port == "80" || $port == "443"){
				$domain = $domain;
			}else{
				$domain = $_SERVER['HTTP_HOST'];
			}
		}else{
        	$domain = $_SERVER['HTTP_HOST'];
		}
	}
    return $protocol . $domain . str_replace("\\", "/", dirname($_SERVER['REQUEST_URI']));
}

function get_browser_name() {

    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';

    return 'Other';

}

function getSickrageCalendarWanted($array){

    $array = json_decode($array, true);
    //$gotCalendar = "";
    $gotCalendar = array();
    $i = 0;

    foreach($array['data']['missed'] AS $child) {

            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
            if($downloaded == "0" && isset($unaired)){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg";}else{ $downloaded = "red-bg"; }
            //$gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded tvID--$episodeID\", imagetype: \"tv\" }, \n";
			array_push($gotCalendar, array(
				"id" => "Sick-Miss-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => $downloaded." tvID--".$episodeID,
				"imagetype" => "tv",
			));

    }

    foreach($array['data']['today'] AS $child) {

            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
            if($downloaded == "0" && isset($unaired)){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg";}else{ $downloaded = "red-bg"; }
            //$gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded tvID--$episodeID\", imagetype: \"tv\" }, \n";
			array_push($gotCalendar, array(
				"id" => "Sick-Today-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => $downloaded." tvID--".$episodeID,
				"imagetype" => "tv",
			));
    }

    foreach($array['data']['soon'] AS $child) {

            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
            if($downloaded == "0" && isset($unaired)){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg";}else{ $downloaded = "red-bg"; }
            //$gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded tvID--$episodeID\", imagetype: \"tv\" }, \n";
			array_push($gotCalendar, array(
				"id" => "Sick-Soon-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => $downloaded." tvID--".$episodeID,
				"imagetype" => "tv",
			));
    }

    foreach($array['data']['later'] AS $child) {

            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
            if($downloaded == "0" && isset($unaired)){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg";}else{ $downloaded = "red-bg"; }
            //$gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded tvID--$episodeID\", imagetype: \"tv\" }, \n";
			array_push($gotCalendar, array(
				"id" => "Sick-Later-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => $downloaded." tvID--".$episodeID,
				"imagetype" => "tv",
			));

    }

    if ($i != 0){ return $gotCalendar; }

}

function getSickrageCalendarHistory($array){

    $array = json_decode($array, true);
    //$gotCalendar = "";
    $gotCalendar = array();
    $i = 0;

    foreach($array['data'] AS $child) {

            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['date'];
            $downloaded = "green-bg";
            //$gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded tvID--$episodeID\", imagetype: \"tv\" }, \n";
			array_push($gotCalendar, array(
				"id" => "Sick-History-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => $downloaded." tvID--".$episodeID,
				"imagetype" => "tv",
			));

    }

    if ($i != 0){ return $gotCalendar; }

}

function getSonarrCalendar($array){

    $array = json_decode($array, true);
    //$gotCalendar = "";
    $gotCalendar = array();
    $i = 0;
    foreach($array AS $child) {

        $i++;
        $seriesName = $child['series']['title'];
        $episodeID = $child['series']['tvdbId'];
        if(!isset($episodeID)){ $episodeID = ""; }
        $episodeName = htmlentities($child['title'], ENT_QUOTES);
        if($child['episodeNumber'] == "1"){ $episodePremier = "true"; }else{ $episodePremier = "false"; }
        $episodeAirDate = $child['airDateUtc'];
        $episodeAirDate = strtotime($episodeAirDate);
        $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);

        if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }

        $downloaded = $child['hasFile'];
        $monitored = $child['monitored'];
        if($downloaded == "0" && isset($unaired) && $episodePremier == "true"){ $downloaded = "light-blue-bg"; }elseif(isset($unaired) && $monitored == "0"){ $downloaded = "light-gray-bg"; }elseif($downloaded == "0" && isset($unaired)){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg";}else{ $downloaded = "red-bg"; }

        //$gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded tvID--$episodeID\", imagetype: \"tv\" }, \n";
		array_push($gotCalendar, array(
			"id" => "Sonarr-".$i,
			"title" => $seriesName,
			"start" => $episodeAirDate,
			"className" => $downloaded." tvID--".$episodeID,
			"imagetype" => "tv",
		));

    }

    if ($i != 0){ return $gotCalendar; }

}

function getCouchCalendar(){
	$url = qualifyURL(COUCHURL);
    $api = curl_get($url."/api/".COUCHAPI."/media.list");
    $api = json_decode($api, true);
    $i = 0;
    $gotCalendar = array();
	if (is_array($api) || is_object($api)){
		foreach($api['movies'] AS $child) {
			if($child['status'] == "active" || $child['status'] == "done" ){
				$i++;
				$movieName = $child['info']['original_title'];
				$movieID = $child['info']['tmdb_id'];
				if(!isset($movieID)){ $movieID = ""; }
				$physicalRelease = (isset($child['info']['released']) ? $child['info']['released'] : null);
				$backupRelease = (isset($child['info']['release_date']['theater']) ? $child['info']['release_date']['theater'] : null);
				$physicalRelease = (isset($physicalRelease) ? $physicalRelease : $backupRelease);
				$physicalRelease = strtotime($physicalRelease);
				$physicalRelease = date("Y-m-d", $physicalRelease);
				if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }
				$downloaded = ($child['status'] == "active") ? "0" : "1";
				if($downloaded == "0" && $notReleased == "true"){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg"; }else{ $downloaded = "red-bg"; }
				array_push($gotCalendar, array(
					"id" => "CouchPotato-".$i,
					"title" => $movieName,
					"start" => $physicalRelease,
					"className" => $downloaded." movieID--".$movieID,
					"imagetype" => "film",
				));
			}
		}
    	if ($i != 0){ return $gotCalendar; }
	}else{
		writeLog("error", "CouchPotato ERROR: could not connect - check URL and/or check API key - if HTTPS, is cert valid");
	}
}

function getRadarrCalendar($array){
    $array = json_decode($array, true);
    $gotCalendar = array();
    $i = 0;
    foreach($array AS $child) {
        if(isset($child['physicalRelease'])){
            $i++;
            $movieName = $child['title'];
            $movieID = $child['tmdbId'];
            if(!isset($movieID)){ $movieID = ""; }
			$physicalRelease = $child['physicalRelease'];
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date("Y-m-d", $physicalRelease);
			if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }
			$downloaded = $child['hasFile'];
			if($downloaded == "0" && $notReleased == "true"){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg"; }else{ $downloaded = "red-bg"; }
			array_push($gotCalendar, array(
				"id" => "Radarr-".$i,
				"title" => $movieName,
				"start" => $physicalRelease,
				"className" => $downloaded." movieID--".$movieID,
				"imagetype" => "film",
			));
        }
    }
    if ($i != 0){ return $gotCalendar; }
}

function getHeadphonesCalendar($url, $key, $list){
	$url = qualifyURL(HEADPHONESURL);
    $api = curl_get($url."/api?apikey=".$key."&cmd=$list");
    $api = json_decode($api, true);
    $i = 0;
    //$gotCalendar = "";
    $gotCalendar = array();;
	if (is_array($api) || is_object($api)){
		foreach($api AS $child) {
			if($child['Status'] == "Wanted" && $list == "getWanted" && $child['ReleaseDate']){
				$i++;
				$albumName = addslashes($child['AlbumTitle']);
				$albumArtist = htmlentities($child['ArtistName'], ENT_QUOTES);
				$albumDate = (strlen($child['ReleaseDate']) > 4) ? $child['ReleaseDate'] : $child['ReleaseDate']."-01-01";
				$albumID = $child['AlbumID'];
				$albumDate = strtotime($albumDate);
				$albumDate = date("Y-m-d", $albumDate);
				$albumStatus = $child['Status'];

				if (new DateTime() < new DateTime($albumDate)) {  $notReleased = "true"; }else{ $notReleased = "false"; }

				if($albumStatus == "Wanted" && $notReleased == "true"){ $albumStatusColor = "indigo-bg"; }elseif($albumStatus == "Downloaded"){ $albumStatusColor = "green-bg"; }else{ $albumStatusColor = "red-bg"; }

				//$gotCalendar .= "{ title: \"$albumArtist - $albumName\", start: \"$albumDate\", className: \"$albumStatusColor\", imagetype: \"music\", url: \"https://musicbrainz.org/release-group/$albumID\" }, \n";
				array_push($gotCalendar, array(
					"id" => "Headphones-".$i,
					"title" => $albumArtist.' - '.$albumName,
					"start" => $albumDate,
					"className" => $albumStatusColor,
					"imagetype" => "music",
					'url' => "https://musicbrainz.org/release-group/".$albumID,
				));
			}
			if($child['Status'] == "Processed" && $list == "getHistory"){
				$i++;
				$find = array('_','[', ']', '\n');
				$replace = array(' ','(', ')', ' ');
				$albumName = addslashes(str_replace($find,$replace,$child['FolderName']));
				$albumDate = $child['DateAdded'];
				$albumID = $child['AlbumID'];
				$albumDate = strtotime($albumDate);
				$albumDate = date("Y-m-d", $albumDate);
				$albumStatusColor = "green-bg";
				if (new DateTime() < new DateTime($albumDate)) {  $notReleased = "true"; }else{ $notReleased = "false"; }

				//$gotCalendar .= "{ title: \"$albumName\", start: \"$albumDate\", className: \"$albumStatusColor\", imagetype: \"music\", url: \"https://musicbrainz.org/release-group/$albumID\" }, \n";
				array_push($gotCalendar, array(
					"id" => "Headphones-".$i,
					"title" => $albumName,
					"start" => $albumDate,
					"className" => $albumStatusColor,
					"imagetype" => "music",
					'url' => "https://musicbrainz.org/release-group/".$albumID,
				));
			}
		}
    	if ($i != 0){ return $gotCalendar; }
	}else{
		writeLog("error", "HEADPHONES $list ERROR: could not connect - check URL and/or check API key - if HTTPS, is cert valid");
	}
}

function checkRootPath($string){
    if($string == "\\" || $string == "/"){
        return "/";
    }else{
        return str_replace("\\", "/", $string) . "/";
    }
}

function strip($string){
	$string = strip_tags($string);
	return preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $string));
}

function writeLog($type, $message){
	if(file_exists(DATABASE_LOCATION."org.log")){
		if(filesize(DATABASE_LOCATION."org.log") > 500000){
			rename(DATABASE_LOCATION.'org.log',DATABASE_LOCATION.'org['.date('Y-m-d').'].log');
			$message2 = date("Y-m-d H:i:s")."|".$type."|".strip("ORG LOG: Creating backup of org.log to org[".date('Y-m-d')."].log ")."\n";
			file_put_contents(DATABASE_LOCATION."org.log", $message2, FILE_APPEND | LOCK_EX);

		}
	}
    $message = date("Y-m-d H:i:s")."|".$type."|".strip($message)."\n";
    file_put_contents(DATABASE_LOCATION."org.log", $message, FILE_APPEND | LOCK_EX);
}

function readLog(){
    $log = file(DATABASE_LOCATION."org.log",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log = array_reverse($log);
    foreach($log as $line){
		if(substr_count($line, '|') == 2){
			$line = explode("|", strip($line));
			$line[1] = ($line[1] == "error") ? '<span class="label label-danger">Error</span>' : '<span class="label label-primary">Success</span>';
			echo "<tr><td>".$line[0]."</td><td>".$line[2]."</td><td>".$line[1]."</td></tr>";
		}
    }
}

function buildStream($array){
    $result = "";
    if (array_key_exists('platform', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:0; position: absolute; bottom: 10px; left: 10px;"><div style="margin-right: 0;" class="item pull-left text-center"><img alt="'.$array['platform'].'" class="img-circle" height="55px" src="images/platforms/'.getPlatform($array['platform']).'"></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('device', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-laptop fa-fw"></i>'.$array['device'].'</span></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('stream', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-play fa-fw"></i>'.$array['stream'].'</span></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('video', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-film fa-fw"></i>'.$array['video'].'</span></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('audio', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-volume-up fa-fw"></i>'.$array['audio'].'</span></div></div><div class="clearfix"></div>';
    }
    return $result;
}

function streamType($value){
    if($value == "transcode" || $value == "Transcode"){
        return "Transcode";
    }elseif($value == "copy" || $value == "DirectStream"){
        return "Direct Stream";
    }elseif($value == "directplay" || $value == "DirectPlay"){
        return "Direct Play";
    }else{
        return "Direct Play";
    }
}

function getPlatform($platform){
    $allPlatforms = array(
        "Chrome" => "chrome.png",
        "tvOS" => "atv.png",
        "iOS" => "ios.png",
        "Xbox One" => "xbox.png",
        "Mystery 4" => "playstation.png",
        "Samsung" => "samsung.png",
        "Roku" => "roku.png",
        "Emby for iOS" => "ios.png",
        "Emby Mobile" => "emby.png",
        "Emby Theater" => "emby.png",
        "Emby Classic" => "emby.png",
        "Safari" => "safari.png",
        "Android" => "android.png",
        "AndroidTv" => "android.png",
        "Chromecast" => "chromecast.png",
        "Dashboard" => "emby.png",
        "Dlna" => "dlna.png",
        "Windows Phone" => "wp.png",
        "Windows RT" => "win8.png",
        "Kodi" => "kodi.png",
    );
    if (array_key_exists($platform, $allPlatforms)) {
        return $allPlatforms[$platform];
    }else{
        return "pmp.png";
    }
}

function getServer(){
    $server = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"];
    return $server;
}

function prettyPrint($v) {
	$trace = debug_backtrace()[0];
	echo '<pre style="white-space: pre; text-overflow: ellipsis; overflow: hidden; background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">'.$trace['file'].':'.$trace['line'].' '.gettype($v)."\n\n".print_r($v, 1).'</pre><br/>';
}

function checkFrame($array, $url){
    if(array_key_exists("x-frame-options", $array)){
        if($array['x-frame-options'] == "deny"){
            return false;
        }elseif($array['x-frame-options'] == "sameorgin"){
            $digest = parse_url($url);
            $host = (isset($digest['host'])?$digest['host']:'');
            if(getServer() == $host){
                return true;
            }else{
                return false;
            }
        }
    }else{
        if(!$array){
            return false;
        }
        return true;
    }
}

function frameTest($url){
    $array = array_change_key_case(get_headers(qualifyURL($url), 1));
    $url = qualifyURL($url);
    if(checkFrame($array, $url)){
        return true;
    }else{
        return false;
    }
}

function sendResult($result, $icon = "floppy-o", $message = false, $success = "WAS_SUCCESSFUL", $fail = "HAS_FAILED", $send = true) {
	$notifyExplode = explode("-", NOTIFYEFFECT);
	if ($result) {
		$msg = array(
			'html' => ($message?''.$message.' <strong>'.translate($success).'</strong>':'<strong>'.translate($success).'</strong>'),
			'icon' => $icon,
			'type' => 'success',
			'length' => '5000',
			'layout' => $notifyExplode[0],
			'effect' => $notifyExplode[1],
		);
	} else {
		$msg = array(
			'html' => ($message?''.$message.' <strong>'.translate($fail).'</strong>':'<strong>'.translate($fail).'</strong>'),
			'icon' => $icon,
			'type' => 'error',
			'length' => '5000',
			'layout' => $notifyExplode[0],
			'effect' => $notifyExplode[1],
		);
	}

	// Send and kill script?
	if ($send) {
		header('Content-Type: application/json');
		echo json_encode(array('notify'=>$msg));
		die();
	}
	return $msg;
}

function buildHomepageNotice($layout, $type, $title, $message){
    switch ($layout) {
		      case 'elegant':
            return '
            <div id="homepageNotice" class="row">
                <div class="col-lg-12">
                    <div class="content-box big-box box-shadow panel-box panel-'.$type.'">
                        <div class="content-title i-block">
                            <h4 class="zero-m"><strong>'.$title.'</strong></h4>
                            <div class="content-tools i-block pull-right">
                                <a class="close-btn">
                                    <i class="fa fa-times"></i>
                                </a>
                            </div>
                        </div>
                        '.$message.'
                    </div>
                </div>
            </div>
            ';
            break;
        case 'basic':
            return '
            <div id="homepageNotice" class="row">
                <div class="col-lg-12">
                    <div class="panel panel-'.$type.'">
                        <div class="panel-heading">
                            <h3 class="panel-title">'.$title.'</h3>
                        </div>
                        <div class="panel-body">
                            '.$message.'
                        </div>
                    </div>
                </div>
            </div>
            ';
            break;
        case 'jumbotron';
            return '
            <div id="homepageNotice" class="row">
                <div class="col-lg-12">
                    <div class="jumbotron">
                        <div class="container">
                            <h1>'.$title.'</h1>
                            <p>'.$message.'</p>
                        </div>
                    </div>
                </div>
            </div>
            ';
    }
}

function embyArray($array, $type) {
    $key = ($type == "video" ? "Height" : "Channels");
    if (array_key_exists($key, $array)) {
        switch ($type) {
            case "video":
                $codec = $array["Codec"];
                $height = $array["Height"];
                $width = $array["Width"];
            break;
            default:
                $codec = $array["Codec"];
                $channels = $array["Channels"];
        }
        return ($type == "video" ?  "(".$codec.") (".$width."x".$height.")" : "(".$codec.") (".$channels."ch)");
    }
    foreach ($array as $element) {
        if (is_array($element)) {
            if (embyArray($element, $type)) {
                return embyArray($element, $type);
            }
        }
    }
}

// Get Now Playing Streams From Plex
function searchPlex($query){
    $address = qualifyURL(PLEXURL);
	$openTab = (PLEXTABNAME) ? "true" : "false";

    // Perform API requests
	$api = @curl_get($address."/search?query=".rawurlencode($query)."&X-Plex-Token=".PLEXTOKEN);
	libxml_use_internal_errors(true);
    $api = simplexml_load_string($api);
	$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
    if (!$getServer) { return 'Could not load!'; }

	// Identify the local machine
    $server = $getServer['machineIdentifier'];
    $pre = "<table  class=\"table table-hover table-stripped\"><thead><tr><th>Cover</th><th>Title</th><th>Genre</th><th>Year</th><th>Type</th><th>Added</th><th>Extra Info</th></tr></thead><tbody>";
    $items = "";
    $albums = $movies = $shows = 0;

    $style = 'style="vertical-align: middle"';
    foreach($api AS $child) {
        if($child['type'] != "artist" && $child['type'] != "episode" && isset($child['librarySectionID'])){
            $time = (string)$child['addedAt'];
            $time = new DateTime("@$time");
            $results = array(
                "title" => (string)$child['title'],
                "image" => (string)$child['thumb'],
                "type" => (string)ucwords($child['type']),
                "year" => (string)$child['year'],
                "key" => (string)$child['ratingKey']."-search",
                "ratingkey" => (string)$child['ratingKey'],
                "genre" => (string)$child->Genre['tag'],
                "added" => $time->format('Y-m-d'),
                "extra" => "",
            );
            switch ($child['type']){
                case "album":
                    $push = array(
                        "title" => (string)$child['parentTitle']." - ".(string)$child['title'],
                    );
                    $results = array_replace($results,$push);
                    $albums++;
                    break;
                case "movie":
					$push = array(
                        "extra" => "Content Rating: ".(string)$child['contentRating']."<br/>Movie Rating: ".(string)$child['rating'],
                    );
			  		$results = array_replace($results,$push);
                    $movies++;
                    break;
                case "show":
			  		$push = array(
                        "extra" => "Seasons: ".(string)$child['childCount']."<br/>Episodes: ".(string)$child['leafCount'],
                    );
			  		$results = array_replace($results,$push);
                    $shows++;
                    break;
            }
			if (file_exists('images/cache/'.$results['key'].'.jpg')){ $image_url = 'images/cache/'.$results['key'].'.jpg'; }
    		if (file_exists('images/cache/'.$results['key'].'.jpg') && (time() - 604800) > filemtime('images/cache/'.$results['key'].'.jpg') || !file_exists('images/cache/'.$results['key'].'.jpg')) {
        		$image_url = 'ajax.php?a=plex-image&img='.$results['image'].'&height=150&width=100&key='.$results['key'];
    		}
    		if(!$results['image']){ $image_url = "images/no-search.png"; $key = "no-search"; }

			if (PLEXTABURL) {
				$link = PLEXTABURL."/web/index.html#!/server/$server/details?key=/library/metadata/".$results['ratingkey'];
			}else{
				$link = "https://app.plex.tv/web/app#!/server/$server/details?key=/library/metadata/".$results['ratingkey'];
			}

            $items .= '<tr style="cursor: pointer;" class="openTab" extraTitle="'.$results['title'].'" extraType="'.$child['type'].'" openTab="'.$openTab.'" href="'.$link.'">
            <th scope="row"><img src="'.$image_url.'"></th>
            <td class="col-xs-2 nzbtable nzbtable-row"'.$style.'>'.$results['title'].'</td>
            <td class="col-xs-3 nzbtable nzbtable-row"'.$style.'>'.$results['genre'].'</td>
            <td class="col-xs-1 nzbtable nzbtable-row"'.$style.'>'.$results['year'].'</td>
            <td class="col-xs-1 nzbtable nzbtable-row"'.$style.'>'.$results['type'].'</td>
            <td class="col-xs-3 nzbtable nzbtable-row"'.$style.'>'.$results['added'].'</td>
            <td class="col-xs-2 nzbtable nzbtable-row"'.$style.'>'.$results['extra'].'</td>
            </tr>';
        }
    }
    $totals = '<div style="margin: 10px;" class="sort-todo pull-right">
              <span class="badge gray-bg"><i class="fa fa-film fa-2x white"></i><strong style="
    font-size: 23px;
">&nbsp;'.$movies.'</strong></span>
              <span class="badge gray-bg"><i class="fa fa-tv fa-2x white"></i><strong style="
    font-size: 23px;
">&nbsp;'.$shows.'</strong></span>
              <span class="badge gray-bg"><i class="fa fa-music fa-2x white"></i><strong style="
    font-size: 23px;
">&nbsp;'.$albums.'</strong></span>
            </div>';
    return (!empty($items) ? $totals.$pre.$items."</div></table>" : "<h2 class='text-center'>No Results for $query</h2>" );
}

function getBannedUsers($string){
    if (strpos($string, ',') !== false) {
        $banned = explode(",", $string);
    }else{
        $banned = array($string);
    }
    return $banned;
}

function getWhitelist($string){
    if (strpos($string, ',') !== false) {
        $whitelist = explode(",", $string);
    }else{
        $whitelist = array($string);
    }
    foreach($whitelist as &$ip){
        $ip = is_numeric(substr($ip, 0, 1)) ? $ip : gethostbyname($ip);
    }
    return $whitelist;
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

//EMAIL SHIT
function sendEmail($email, $username = "Organizr User", $subject, $body, $cc = null, $bcc = null){

	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = SMTPHOST;
	$mail->SMTPAuth = SMTPHOSTAUTH;
	$mail->Username = SMTPHOSTUSERNAME;
	$mail->Password = SMTPHOSTPASSWORD;
	$mail->SMTPSecure = SMTPHOSTTYPE;
	$mail->Port = SMTPHOSTPORT;
	$mail->setFrom(SMTPHOSTSENDEREMAIL, SMTPHOSTSENDERNAME);
	$mail->addReplyTo(SMTPHOSTSENDEREMAIL, SMTPHOSTSENDERNAME);
	$mail->isHTML(true);
	if($email){
		$mail->addAddress($email, $username);
	}
	if($cc){
		$mail->addCC($cc);
	}
	if($bcc){
		if(strpos($bcc , ',') === false){
			$mail->addBCC($bcc);
		}else{
			$allEmails = explode(",",$bcc);
			foreach($allEmails as $gotEmail){
				$mail->addBCC($gotEmail);
			}
		}
	}
	$mail->Subject = $subject;
	$mail->Body    = $body;
	//$mail->send();
	if(!$mail->send()) {
		writeLog("error", "mail failed to send");
	} else {
		writeLog("success", "mail has been sent");
	}

}

//EMAIL SHIT
function sendTestEmail($to, $from, $host, $auth, $username, $password, $type, $port, $sendername){

	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = $host;
	$mail->SMTPAuth = $auth;
	$mail->Username = $username;
	$mail->Password = $password;
	$mail->SMTPSecure = $type;
	$mail->Port = $port;
	$mail->setFrom($from, $sendername);
	$mail->addReplyTo($from, $sendername);
	$mail->isHTML(true);
	$mail->addAddress($to, "Organizr Admin");
	$mail->Subject = "Organizr Test E-Mail";
	$mail->Body    = "This was just a test!";
	//$mail->send();
	if(!$mail->send()) {
		writeLog("error", "EMAIL TEST: mail failed to send - Error:".$mail->ErrorInfo);
		return false;
	} else {
		writeLog("success", "EMAIL TEST: mail has been sent successfully");
		return true;
	}

}

function libraryList(){
    $address = qualifyURL(PLEXURL);
	$headers = array(
		"Accept" => "application/json",
		"X-Plex-Token" => PLEXTOKEN
	);
	libxml_use_internal_errors(true);
	$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
    if (!$getServer) { return 'Could not load!'; }else { $gotServer = $getServer['machineIdentifier']; }

	$api = simplexml_load_string(@curl_get("https://plex.tv/api/servers/$gotServer/shared_servers", $headers));
	$libraryList = array();
    foreach($api->SharedServer->Section AS $child) {
		$libraryList['libraries'][(string)$child['title']] = (string)$child['id'];
    }
	foreach($api->SharedServer AS $child) {
		if(!empty($child['username'])){
			$username = (string)strtolower($child['username']);
			$email = (string)strtolower($child['email']);
			$libraryList['users'][$username] = (string)$child['id'];
			$libraryList['emails'][$email] = (string)$child['id'];
			$libraryList['both'][$username] = $email;
		}
    }
    return (!empty($libraryList) ? array_change_key_case($libraryList,CASE_LOWER) : null );
}

function plexUserShare($username){
    $address = qualifyURL(PLEXURL);
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json",
		"X-Plex-Token" => PLEXTOKEN
	);
	libxml_use_internal_errors(true);
	$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
    if (!$getServer) { return 'Could not load!'; }else { $gotServer = $getServer['machineIdentifier']; }

	$json = array(
		"server_id" => $gotServer,
		"shared_server" => array(
			//"library_section_ids" => "[26527637]",
			"invited_email" => $username
		)
	);

	$api = curl_post("https://plex.tv/api/servers/$gotServer/shared_servers/", $json, $headers);

	switch ($api['http_code']['http_code']){
		case 400:
			writeLog("error", "PLEX INVITE: $username already has access to the shared libraries");
			$result = "$username already has access to the shared libraries";
			break;
		case 401:
			writeLog("error", "PLEX INVITE: Invalid Plex Token");
			$result = "Invalid Plex Token";
			break;
		case 200:
			writeLog("success", "PLEX INVITE: $username now has access to your Plex Library");
			$result = "$username now has access to your Plex Library";
			break;
		default:
			writeLog("error", "PLEX INVITE: unknown error");
			$result = false;
	}
    return (!empty($result) ? $result : null );
}

function plexUserDelete($username){
    $address = qualifyURL(PLEXURL);
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json",
		"X-Plex-Token" => PLEXTOKEN
	);
	libxml_use_internal_errors(true);
	$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
    if (!$getServer) { return 'Could not load!'; }else { $gotServer = $getServer['machineIdentifier']; }
	$id = (is_numeric($username) ? $id : convertPlexName($username, "id"));

	$api = curl_delete("https://plex.tv/api/servers/$gotServer/shared_servers/$id", $headers);

	switch ($api['http_code']['http_code']){
		case 401:
			writeLog("error", "PLEX INVITE: Invalid Plex Token");
			$result = "Invalid Plex Token";
			break;
		case 200:
			writeLog("success", "PLEX INVITE: $username doesn't have access to your Plex Library anymore");
			$result = "$username doesn't have access to your Plex Library anymore";
			break;
		default:
			writeLog("error", "PLEX INVITE: unknown error");
			$result = false;
	}
    return (!empty($result) ? $result : null );
}

function convertPlexName($user, $type){
	$array = libraryList();
	switch ($type){
		case "username":
			$plexUser = array_search ($user, $array['users']);
			break;
		case "id":
			if (array_key_exists(strtolower($user), $array['users'])) {
				$plexUser = $array['users'][strtolower($user)];
			}
			break;
		default:
			$plexUser = false;
	}
	return (!empty($plexUser) ? $plexUser : null );
}

function randomCode($length = 5, $type = null) {
	switch ($type){
		case "alpha":
			$legend = array_merge(range('A', 'Z'));
			break;
		case "numeric":
			$legend = array_merge(range(0,9));
			break;
		default:
			$legend = array_merge(range(0,9),range('A', 'Z'));
	}
    $code = "";
    for($i=0; $i < $length; $i++) {
        $code .= $legend[mt_rand(0, count($legend) - 1)];
    }
    return $code;
}

function inviteCodes($action, $code = null, $usedBy = null) {
	if (!isset($GLOBALS['file_db'])) {
		$GLOBALS['file_db'] = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
		$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	$now = date("Y-m-d H:i:s");

	switch ($action) {
		case "get":
			// Start Array
			$result = array();
			// Database Lookup
			$invites = $GLOBALS['file_db']->query('SELECT * FROM invites WHERE valid = "Yes"');
			// Get Codes
			foreach($invites as $row) {
				array_push($result, $row['code']);
			}
			// Return the Results
			return (!empty($result) ? $result : false );
			break;
		case "check":
			// Start Array
			$result = array();
			// Database Lookup
			$invites = $GLOBALS['file_db']->query('SELECT * FROM invites WHERE valid = "Yes" AND code = "'.$code.'"');
			// Get Codes
			foreach($invites as $row) {
				$result = $row['code'];
			}
			// Return the Results
			return (!empty($result) ? $result : false );
			break;
		case "use":
			$currentIP = get_client_ip();
			$invites = $GLOBALS['file_db']->query('UPDATE invites SET valid = "No", usedby = "'.$usedBy.'", dateused = "'.$now.'", ip = "'.$currentIP.'" WHERE code = "'.$code.'"');
			if(ENABLEMAIL){
				if (!isset($GLOBALS['USER'])) {
					require_once("user.php");
					$GLOBALS['USER'] = new User('registration_callback');
				}
				$emailTemplate = array(
					'type' => 'mass',
					'body' => 'The user: {user} has reddemed the code: {inviteCode} his IP Address was '.$currentIP,
					'subject' => 'Invite Code '.$code.' Has Been Used',
					'user' => $usedBy,
					'password' => null,
					'inviteCode' => $code,
				);
				$emailTemplate = emailTemplate($emailTemplate);
				$subject = $emailTemplate['subject'];
				$body = buildEmail($emailTemplate);
				sendEmail($GLOBALS['USER']->adminEmail, "Admin", $subject, $body);
			}
			return (!empty($invites) ? true : false );
			break;
	}

}

function plexJoin($username, $email, $password){
	$connectURL = 'https://plex.tv/users.json';
	$headers = array(
		'Accept'=> 'application/json',
		'Content-Type' => 'application/x-www-form-urlencoded',
		'X-Plex-Product' => 'Organizr',
		'X-Plex-Version' => '1.0',
		'X-Plex-Client-Identifier' => '01010101-10101010',
	);
	$body = array(
		'user[email]' => $email,
		'user[username]' => $username,
		'user[password]' => $password,
	);

	$api = curl_post($connectURL, $body, $headers);
	$json = json_decode($api['content'], true);
	$errors = (!empty($json['errors']) ? true : false);
	$success = (!empty($json['user']) ? true : false);
	//Use This for later
	$usernameError = (!empty($json['errors']['username']) ? $json['errors']['username'][0] : false);
	$emailError = (!empty($json['errors']['email']) ? $json['errors']['email'][0] : false);
	$passwordError = (!empty($json['errors']['password']) ? $json['errors']['password'][0] : false);
	$errorMessage = "";
	if($errors){
		if($usernameError){ $errorMessage .= "[Username Error: ". $usernameError ."]"; }
		if($emailError){ $errorMessage .= "[Email Error: ". $emailError ."]"; }
		if($passwordError){ $errorMessage .= "[Password Error: ". $passwordError ."]"; }
	}

	switch ($api['http_code']['http_code']){
		case 400:
			writeLog("error", "PLEX JOIN: Error: ".$api['http_code']['http_code']." $username already has access to the shared libraries $errorMessage");
			break;
		case 401:
			writeLog("error", "PLEX JOIN: Error: ".$api['http_code']['http_code']." invalid Plex Token $errorMessage");
			break;
		case 422:
			writeLog("error", "PLEX JOIN: Error: ".$api['http_code']['http_code']." user info error $errorMessage");
			break;
		case 429:
			writeLog("error", "PLEX JOIN: Error: ".$api['http_code']['http_code']." too many requests to plex.tv please try later $errorMessage");
			break;
		case 200:
		case 201:
			writeLog("success", "PLEX JOIN: $username now has access to your Plex Library");
			break;
		default:
			writeLog("error", "PLEX JOIN: unknown error, $errorMessage Error: ".$api['http_code']['http_code']);
	}
	//prettyPrint($api);
	//prettyPrint(json_decode($api['content'], true));
    return (!empty($success) && empty($errors) ? true : false );

}

function getCert(){
	$url = "http://curl.haxx.se/ca/cacert.pem";
	$file = getcwd()."/config/cacert.pem";
	$directory = getcwd()."/config/";
	@mkdir($directory, 0770, true);
	if(!file_exists($file)){
    	file_put_contents( $file, fopen($url, 'r'));
		writeLog("success", "CERT PEM: pem file created");
	}elseif (file_exists($file) && time() - 2592000 > filemtime($file)) {
		file_put_contents( $file, fopen($url, 'r'));
		writeLog("success", "CERT PEM: downloaded new pem file");
	}
	return $file;
}

function customCSS(){
	if(CUSTOMCSS == "true") {
		$template_file = "custom.css";
		$file_handle = fopen($template_file, "rb");
		echo "\n";
		echo fread($file_handle, filesize($template_file));
		fclose($file_handle);
		echo "\n";
	}
}

function tvdbToken(){
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json"
	);
	$json = array(
		"apikey" => "FBE7B62621F4CAD7",
         "userkey" => "328BB46EB1E9A0F5",
         "username" => "causefx"
	);
	$api = curl_post("https://api.thetvdb.com/login", $json, $headers);
    return json_decode($api['content'], true)['token'];
}

function tvdbGet($id){
	$headers = array(
		"Accept" => "application/json",
		"Authorization" => "Bearer ".tvdbToken(),
		"trakt-api-key" => "4502cfdf8f7282fe454878ff8583f5636392cdc5fcac30d0cc4565f7173bf443",
		"trakt-api-version" => "2"
	);

	$trakt = curl_get("https://api.trakt.tv/search/tvdb/$id?type=show", $headers);
	@$api['trakt'] = json_decode($trakt, true)[0]['show']['ids'];

	if(empty($api['trakt'])){
		$series = curl_get("https://api.thetvdb.com/series/$id", $headers);
		$poster = curl_get("https://api.thetvdb.com/series/$id/images/query?keyType=poster", $headers);
		$backdrop = curl_get("https://api.thetvdb.com/series/$id/images/query?keyType=fanart", $headers);
		$api['series'] = json_decode($series, true)['data'];
		$api['poster'] = json_decode($poster, true)['data'];
		$api['backdrop'] = json_decode($backdrop, true)['data'];
	}
	return $api;
}

function tvdbSearch($name, $type){
	$name = rawurlencode(preg_replace("/\(([^()]*+|(?R))*\)/","", $name));
	$headers = array(
		"Accept" => "application/json",
		"Authorization" => "Bearer ".tvdbToken(),
		"trakt-api-key" => "4502cfdf8f7282fe454878ff8583f5636392cdc5fcac30d0cc4565f7173bf443",
		"trakt-api-version" => "2"
	);

	$trakt = curl_get("https://api.trakt.tv/search/$type?query=$name", $headers);
	@$api['trakt'] = json_decode($trakt, true)[0][$type]['ids'];

	return $api;
}

function getPlexPlaylists(){
    $address = qualifyURL(PLEXURL);

	// Perform API requests
	$api = @curl_get($address."/playlists?X-Plex-Token=".PLEXTOKEN);
	libxml_use_internal_errors(true);
    $api = simplexml_load_string($api);
	if (is_array($api) || is_object($api)){
		if (!$api->head->title){
			$getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
			if (!$getServer) { return 'Could not load!'; }
			// Identify the local machine
			$gotServer = $getServer['machineIdentifier'];
			$output = "";
			$hideMenu = '<div class="pull-right"><div class="btn-group" role="group"><button type="button" id="playlist-Name" class="btn waves btn-default btn-sm dropdown-toggle waves-effect waves-float" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Choose A Playlist &nbsp;<span class="caret"></span></button><ul style="right:0; left: auto; height: 200px;" class="dropdown-menu filter-recent-playlist playlist-listing">';
			foreach($api AS $child) {
				$items = array();
				if ($child['playlistType'] == "video" && strpos(strtolower($child['title']) , 'private') === false){
					$api = @curl_get($address.$child['key']."?X-Plex-Token=".PLEXTOKEN);
					$api = simplexml_load_string($api);
					if (is_array($api) || is_object($api)){
						if (!$api->head->title){
							$className = preg_replace("/(\W)+/", "", $api['title']);
							$hideMenu .= '<li data-filter="playlist-'.$className.'" data-name="'.$api['title'].'"><a class="js-filter-'.$className.'" href="javascript:void(0)">'.$api['title'].'</a></li>';
							foreach($api->Video AS $child){
								$items[] = resolvePlexItem($gotServer, PLEXTOKEN, $child, false, false,false,$className);
							}
							if (count($items)) {
								$output .= ''.implode('',$items).'';
							}
						}
					}
				}
			}
			$hideMenu .= '</ul></div></div>';
			return '<div id="playlist-all" class="content-box box-shadow big-box"><h5 id="playlist-title" style="margin-bottom: -20px" class="text-center">All Playlists</h5><div class="recentHeader inbox-pagination all">'.$hideMenu.'</div><br/><br/><div class="recentItems-playlists" data-name="all">'.$output.'</div></div>';
		}else{
			writeLog("error", "PLEX PLAYLIST ERROR: could not connect - check token - if HTTPS, is cert valid");
		}
	}else{
		writeLog("error", "PLEX PLAYLIST ERROR: could not connect - check URL - if HTTPS, is cert valid");
	}
}

function readExternalLog($type,$filename,$name = null){
    $log = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log = array_reverse($log);
    foreach($log as $line){
		if(!empty($line) && $line[0] != " "){
			$line = strip($line);
			if($type == "single"){
				if( strpos( strtolower($line), "ror" ) !== false ) {
					echo "<tr><td class='red-bg'>".$line."</td></tr>";
				}else{
					echo "<tr><td>".$line."</td></tr>";
				}
			}elseif($type == "all"){
				if( strpos( strtolower($line), "ror" ) !== false ) {
					echo "<tr><td class='red-bg'>".$name."</td>";
					echo "<td class='red-bg'>".$line."</td></tr>";
				}else{
					echo "<tr><td>".$name."</td>";
					echo "<td>".$line."</td></tr>";
				}
			}
		}
    }
}

function getLogs(){
    $path = __DIR__ ."/logs/";
	@mkdir($path, 0770, true);
    $logs = array();
    $files = array_diff(scandir($path), array('.', '..'));
    foreach($files as $v){
        $title = explode(".", $v)[0];
        $logs[$title] = $path.$v;
    }
    return $logs;
}

function getBackups(){
    $path = DATABASE_LOCATION ."backups/";
	@mkdir($path, 0770, true);
    $backups = array();
    $files = array_diff(scandir($path), array('.', '..'));
    return array_reverse($files);
}

function getExtension($string) {
    return preg_replace("#(.+)?\.(\w+)(\?.+)?#", "$2", $string);
}

function showFile(){
	$file = $_GET['file'];
	$fileType = getExtension($file);
	if($fileType != 'php'){
		header("Content-type: ".mimeTypes()[$fileType]);
		@readfile($file);
	}
}

function getCalendar(){
	$sonarr = new Sonarr(SONARRURL, SONARRKEY);
	$radarr = new Sonarr(RADARRURL, RADARRKEY);
	$sickrage = new SickRage(SICKRAGEURL, SICKRAGEKEY);
	$startDate = date('Y-m-d',strtotime("-".CALENDARSTARTDAY." days"));
	$endDate = date('Y-m-d',strtotime("+".CALENDARENDDAY." days"));
	$calendarItems = array();
	if (SONARRURL != "" && qualifyUser(SONARRHOMEAUTH)){
		try {
			$sonarrCalendar = getSonarrCalendar($sonarr->getCalendar($startDate, $endDate, SONARRUNMONITORED));
			if(!empty($sonarrCalendar)) { $calendarItems = array_merge($calendarItems, $sonarrCalendar); 
      }
		} catch (Exception $e) {
			writeLog("error", "SONARR ERROR: ".strip($e->getMessage()));
		}
	}
	if (RADARRURL != "" && qualifyUser(RADARRHOMEAUTH)){
		try {
			$radarrCalendar = getRadarrCalendar($radarr->getCalendar($startDate, $endDate));
			if(!empty($radarrCalendar)) { $calendarItems = array_merge($calendarItems, $radarrCalendar); }
		} catch (Exception $e) {
			writeLog("error", "RADARR ERROR: ".strip($e->getMessage()));
		}
	}
	if (COUCHURL != "" && qualifyUser(COUCHHOMEAUTH)){
		$couchCalendar = getCouchCalendar();
		if(!empty($couchCalendar)) { $calendarItems = array_merge($calendarItems, $couchCalendar); }

	}
	if (HEADPHONESURL != "" && qualifyUser(HEADPHONESHOMEAUTH)){
		$headphonesHistory = getHeadphonesCalendar(HEADPHONESURL, HEADPHONESKEY, "getHistory");
		$headphonesWanted = getHeadphonesCalendar(HEADPHONESURL, HEADPHONESKEY, "getWanted");
		if(!empty($headphonesHistory)) { $calendarItems = array_merge($calendarItems, $headphonesHistory); }
		if(!empty($headphonesWanted)) { $calendarItems = array_merge($calendarItems, $headphonesWanted); }

	}
	if (SICKRAGEURL != "" && qualifyUser(SICKRAGEHOMEAUTH)){
		try {
			$sickrageFuture = getSickrageCalendarWanted($sickrage->future());
			if(!empty($sickrageFuture)) { $calendarItems = array_merge($calendarItems, $sickrageFuture); }
		} catch (Exception $e) {
			writeLog("error", "SICKRAGE/BEARD ERROR: ".strip($e->getMessage()));
		} try {
			$sickrageHistory = getSickrageCalendarHistory($sickrage->history("100","downloaded"));
			if(!empty($sickrageHistory)) { $calendarItems = array_merge($calendarItems, $sickrageHistory); }
		} catch (Exception $e) {
			writeLog("error", "SICKRAGE/BEARD ERROR: ".strip($e->getMessage()));
		}
	}
	return $calendarItems;
}

function localURL($url){
	if (strpos($url, 'https') !== false) {
		preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $url, $result);
		$result = (!empty($result) ? true : false);
		return $result;
	}
}

function fileArray($files){
	foreach($files as $file){
		if(file_exists($file)){
			$list[] = $file;
		}
	}
	if(!empty($list)){ return $list; }
}

function backupDB(){
	if (extension_loaded('ZIP')) {
		$directory = DATABASE_LOCATION."backups/";
		@mkdir($directory, 0770, true);
		$orgFiles = array(
			'css' => 'custom.css',
			'temp' => 'cus.sd',
			'orgLog' => DATABASE_LOCATION.'org.log',
			'loginLog' => DATABASE_LOCATION.'loginLog.json',
			'chatDB' => 'chatpack.db',
			'config' => 'config/config.php',
			'database' => DATABASE_LOCATION.'users.db'
		);
		$files = fileArray($orgFiles);
		if(!empty($files)){
			writeLog("success", "BACKUP: backup process started");
			$zipname = $directory.'backup['.date('Y-m-d_H-i').']['.INSTALLEDVERSION.'].zip';
			$zip = new ZipArchive;
			$zip->open($zipname, ZipArchive::CREATE);
			foreach ($files as $file) {
				$zip->addFile($file);
			}
			$zip->close();
			writeLog("success", "BACKUP: backup process finished");
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}

class Ping {

	private $host;
	private $ttl;
	private $timeout;
	private $port = 80;
	private $data = 'Ping';
	private $commandOutput;

	/**
	* Called when the Ping object is created.
	*
	* @param string $host
	*   The host to be pinged.
	* @param int $ttl
	*   Time-to-live (TTL) (You may get a 'Time to live exceeded' error if this
	*   value is set too low. The TTL value indicates the scope or range in which
	*   a packet may be forwarded. By convention:
	*     - 0 = same host
	*     - 1 = same subnet
	*     - 32 = same site
	*     - 64 = same region
	*     - 128 = same continent
	*     - 255 = unrestricted
	* @param int $timeout
	*   Timeout (in seconds) used for ping and fsockopen().
	* @throws \Exception if the host is not set.
	*/
	public function __construct($host, $ttl = 255, $timeout = 10) {
	if (!isset($host)) {
		throw new \Exception("Error: Host name not supplied.");
	}

	$this->host = $host;
	$this->ttl = $ttl;
	$this->timeout = $timeout;
	}

	/**
	* Set the ttl (in hops).
	*
	* @param int $ttl
	*   TTL in hops.
	*/
	public function setTtl($ttl) {
	$this->ttl = $ttl;
	}

	/**
	* Get the ttl.
	*
	* @return int
	*   The current ttl for Ping.
	*/
	public function getTtl() {
	return $this->ttl;
	}

	/**
	* Set the timeout.
	*
	* @param int $timeout
	*   Time to wait in seconds.
	*/
	public function setTimeout($timeout) {
	$this->timeout = $timeout;
	}

	/**
	* Get the timeout.
	*
	* @return int
	*   Current timeout for Ping.
	*/
	public function getTimeout() {
	return $this->timeout;
	}

	/**
	* Set the host.
	*
	* @param string $host
	*   Host name or IP address.
	*/
	public function setHost($host) {
	$this->host = $host;
	}

	/**
	* Get the host.
	*
	* @return string
	*   The current hostname for Ping.
	*/
	public function getHost() {
	return $this->host;
	}

	/**
	* Set the port (only used for fsockopen method).
	*
	* Since regular pings use ICMP and don't need to worry about the concept of
	* 'ports', this is only used for the fsockopen method, which pings servers by
	* checking port 80 (by default).
	*
	* @param int $port
	*   Port to use for fsockopen ping (defaults to 80 if not set).
	*/
	public function setPort($port) {
	$this->port = $port;
	}

	/**
	* Get the port (only used for fsockopen method).
	*
	* @return int
	*   The port used by fsockopen pings.
	*/
	public function getPort() {
	return $this->port;
	}

	/**
	* Return the command output when method=exec.
	* @return string
	*/
	public function getCommandOutput(){
	return $this->commandOutput;
	}

	/**
	* Matches an IP on command output and returns.
	* @return string
	*/
	public function getIpAddress() {
	$out = array();
	if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $this->commandOutput, $out)){
		return $out[0];
	}
	return null;
	}

	/**
	* Ping a host.
	*
	* @param string $method
	*   Method to use when pinging:
	*     - exec (default): Pings through the system ping command. Fast and
	*       robust, but a security risk if you pass through user-submitted data.
	*     - fsockopen: Pings a server on port 80.
	*     - socket: Creates a RAW network socket. Only usable in some
	*       environments, as creating a SOCK_RAW socket requires root privileges.
	*
	* @throws InvalidArgumentException if $method is not supported.
	*
	* @return mixed
	*   Latency as integer, in ms, if host is reachable or FALSE if host is down.
	*/
	public function ping($method = 'exec') {
	$latency = false;

	switch ($method) {
		case 'exec':
		$latency = $this->pingExec();
		break;

		case 'fsockopen':
		$latency = $this->pingFsockopen();
		break;

		case 'socket':
		$latency = $this->pingSocket();
		break;

		default:
		throw new \InvalidArgumentException('Unsupported ping method.');
	}

	// Return the latency.
	return $latency;
	}

	/**
	* The exec method uses the possibly insecure exec() function, which passes
	* the input to the system. This is potentially VERY dangerous if you pass in
	* any user-submitted data. Be SURE you sanitize your inputs!
	*
	* @return int
	*   Latency, in ms.
	*/
	private function pingExec() {
	$latency = false;

	$ttl = escapeshellcmd($this->ttl);
	$timeout = escapeshellcmd($this->timeout);
	$host = escapeshellcmd($this->host);

	// Exec string for Windows-based systems.
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		// -n = number of pings; -i = ttl; -w = timeout (in milliseconds).
		$exec_string = 'ping -n 1 -i ' . $ttl . ' -w ' . ($timeout * 1000) . ' ' . $host;
	}
	// Exec string for Darwin based systems (OS X).
	else if(strtoupper(PHP_OS) === 'DARWIN') {
		// -n = numeric output; -c = number of pings; -m = ttl; -t = timeout.
		$exec_string = 'ping -n -c 1 -m ' . $ttl . ' -t ' . $timeout . ' ' . $host;
	}
	// Exec string for other UNIX-based systems (Linux).
	else {
		// -n = numeric output; -c = number of pings; -t = ttl; -W = timeout
		$exec_string = 'ping -n -c 1 -t ' . $ttl . ' -W ' . $timeout . ' ' . $host . ' 2>&1';
	}

	exec($exec_string, $output, $return);

	// Strip empty lines and reorder the indexes from 0 (to make results more
	// uniform across OS versions).
	$this->commandOutput = implode($output, '');
	$output = array_values(array_filter($output));

	// If the result line in the output is not empty, parse it.
	if (!empty($output[1])) {
		// Search for a 'time' value in the result line.
		$response = preg_match("/time(?:=|<)(?<time>[\.0-9]+)(?:|\s)ms/", $output[1], $matches);

		// If there's a result and it's greater than 0, return the latency.
		if ($response > 0 && isset($matches['time'])) {
		$latency = round($matches['time'], 2);
		}
	}

	return $latency;
	}

	/**
	* The fsockopen method simply tries to reach the host on a port. This method
	* is often the fastest, but not necessarily the most reliable. Even if a host
	* doesn't respond, fsockopen may still make a connection.
	*
	* @return int
	*   Latency, in ms.
	*/
	private function pingFsockopen() {
	$start = microtime(true);
	// fsockopen prints a bunch of errors if a host is unreachable. Hide those
	// irrelevant errors and deal with the results instead.
	$fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
	if (!$fp) {
		$latency = false;
	}
	else {
		$latency = microtime(true) - $start;
		$latency = round($latency * 1000, 2);
	}
	return $latency;
	}

	/**
	* The socket method uses raw network packet data to try sending an ICMP ping
	* packet to a server, then measures the response time. Using this method
	* requires the script to be run with root privileges, though, so this method
	* only works reliably on Windows systems and on Linux servers where the
	* script is not being run as a web user.
	*
	* @return int
	*   Latency, in ms.
	*/
	private function pingSocket() {
	// Create a package.
	$type = "\x08";
	$code = "\x00";
	$checksum = "\x00\x00";
	$identifier = "\x00\x00";
	$seq_number = "\x00\x00";
	$package = $type . $code . $checksum . $identifier . $seq_number . $this->data;

	// Calculate the checksum.
	$checksum = $this->calculateChecksum($package);

	// Finalize the package.
	$package = $type . $code . $checksum . $identifier . $seq_number . $this->data;

	// Create a socket, connect to server, then read socket and calculate.
	if ($socket = socket_create(AF_INET, SOCK_RAW, 1)) {
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array(
		'sec' => 10,
		'usec' => 0,
		));
		// Prevent errors from being printed when host is unreachable.
		@socket_connect($socket, $this->host, null);
		$start = microtime(true);
		// Send the package.
		@socket_send($socket, $package, strlen($package), 0);
		if (socket_read($socket, 255) !== false) {
		$latency = microtime(true) - $start;
		$latency = round($latency * 1000, 2);
		}
		else {
		$latency = false;
		}
	}
	else {
		$latency = false;
	}
	// Close the socket.
	socket_close($socket);
	return $latency;
	}

	/**
	* Calculate a checksum.
	*
	* @param string $data
	*   Data for which checksum will be calculated.
	*
	* @return string
	*   Binary string checksum of $data.
	*/
	private function calculateChecksum($data) {
	if (strlen($data) % 2) {
		$data .= "\x00";
	}

	$bit = unpack('n*', $data);
	$sum = array_sum($bit);

	while ($sum >> 16) {
		$sum = ($sum >> 16) + ($sum & 0xffff);
	}

	return pack('n*', ~$sum);
	}
}

function ping($pings, $type = "string") {
	$ping = new Ping("");
	$ping->setTtl(128);
	$ping->setTimeout(2);
	switch ($type){
		case "array":
			$results = [];
			foreach ($pings as $k => $v) {
				if(strpos($v, ':') !== false){
					$domain = explode(':', $v)[0];
					$port = explode(':', $v)[1];
					$ping->setHost($domain);
					$ping->setPort($port);
					$latency = $ping->ping('fsockopen');
				}else{
					$ping->setHost($v);
					$latency = $ping->ping();
				}
				if ($latency || $latency === 0) {
					$results[$k] = $latency;
				} else {
					$results[$k] = 0;
				}
			}
			break;
		case "string":
			if(strpos($pings, ':') !== false){
				$domain = explode(':', $pings)[0];
				$port = explode(':', $pings)[1];
				$ping->setHost($domain);
				$ping->setPort($port);
				$latency = $ping->ping('fsockopen');
			}else{
				$ping->setHost($pings);
				$latency = $ping->ping();
			}
			if ($latency || $latency === 0) {
				$results = $latency;
			} else {
				$results = 0;
			}
			break;
	}

	return $results;
}

function getPing($url, $style, $refresh = null){
	if(ping($url) !== 0){
		$class = 'success';
		if(!$refresh){
			$class .= " animated slideInLeft";
		}
	}else{
		$class = "warning";
		if(!$refresh){
			$class .= " animated flash loop-animation-timeout";
		}
	}
	echo '<span class="pingcheck badge ping-'.$class.'" style="position: absolute;z-index: 100;right: 5px; padding: 0px 0px;'.$style.';font-size: 10px;">&nbsp;</span>';
}

function speedTestData(){
	$file_db = DATABASE_LOCATION."speedtest.db";
	if(file_exists($file_db)){
		$conn = new PDO("sqlite:$file_db") or die("1");
		$result = $conn->query('SELECT * FROM speedtest_users');
		$conn = null;
		if (is_array($result) || is_object($result)){
			foreach($result as $k => $v){
				$return[$k] = $v;
			}
			return $return;
		}
	}
}

function speedTestDisplay($array, $output){
	if (is_array($array) || is_object($array)){
		if($output == "graph"){
			$result = "Morris.Line({element: 'morris-line',data: [";
			foreach($array as $k => $v){
				$result .= "{ y: '".substr($v['timestamp'],0,10)."', a: ".$v['ul'].", b: ".$v['dl'].", c: ".$v['ping']." },";
			}
			$result .= "],xkey: 'y',ykeys: ['a', 'b', 'c'],labels: ['Upload', 'Download', 'Ping'],hideHover: 'auto',resize: true,lineColors: ['#63A8EB','#ccc','#000'] });";
		}elseif($output == "table"){
			$result = "";
			foreach($array as $k => $v){
				$result .= "<tr><td>".$v['timestamp']."</td><td>".$v['ip']."</td><td>".$v['dl']."</td><td>".$v['ul']."</td><td>".$v['ping']."</td><td>".$v['jitter']."</td></tr>";
			}
		}
		return $result;
	}
}

function buildMenuPhone($array){
	if (is_array($array) || is_object($array)){
		$result = '
		<div class="content-box profile-sidebar box-shadow">
			<img src="images/organizr-logo-h-d.png" width="100%" style="margin-top: -10px;">
			<div class="profile-usermenu">
				<ul class="nav" id="settings-list">
		';
		foreach($array as $k => $v){
			if($v['id'] == 'open-invites' && empty(PLEXURL)){
				continue;
			}
			if($v['id'] == 'open-email' && ENABLEMAIL !== "true"){
				continue;
			}
			/*$result .= '
			<li>
				<a id="'.$v['id'].'" box="'.$v['box'].'">'.$v['name'].'
					<span class="fa-stack fa-fw pull-right" style="margin-top: -5px;margin-right: -10px;">
						<i class="fa fa-'.$v['icon_1'].' fa-stack-2x '.$v['color'].'" style="font-size:null;"></i>
						<i class="fa fa-'.$v['icon_2'].' fa-stack-1x fa-inverse"></i>
					</span>
				</a>
			</li>
			';*/
			$result .= '<li><a id="'.$v['id'].'" box="'.$v['box'].'"><i class="fa fa-'.$v['icon_2'].' '.$v['color'].' fa-fw pull-right"></i>'.$v['name'].'</a></li>';
		}
		$result .= '</ul></div></div>';
		return $result;
	}
}
function buildMenu($array){
	if (is_array($array) || is_object($array)){
		$result = '<div class="settingsList">';
		foreach($array as $k => $v){
			if($v['id'] == 'open-invites' && empty(PLEXURL)){
				continue;
			}
			if($v['id'] == 'open-email' && ENABLEMAIL !== "true"){
				continue;
			}
			$result .= '
			<button id="'.$v['id'].'" box="'.$v['box'].'" type="button" style="border-radius: 0px !important; -webkit-border-radius: 0px !important;margin-bottom: 3px;margin-left:5px;color:white;" class="btn '.$v['color2'].' btn-icon waves waves-circle waves-effect waves-float settingsMenu">
				<i class="mdi mdi-'.$v['icon_1'].' fa-fw pull-left" style="padding-left: '.$v['padding'].'px;font-size: 30px"></i>
				<p class="" style="text-align: center;direction: rtl;display:none;margin: 2px;"><strong>'.$v['name'].'</strong></p>
			</button>
			';
		}
		$result .= '</div>';
		return $result;
	}
}

function requestInvite($email, $username){
	//sendEmail($email, $username = "Organizr User", $subject, $body, $cc = null, $bcc = null)
	sendEmail($GLOBALS['USER']->adminEmail, "Admin", "Plex Invite Request", orgEmail("PLEX Invite Request", "Look who wants to join the cool club", "Admin", "Hey, The User: $user has requested access to your Plex Library.", "Generate Invite", null, "What Next?", "Well, That is up to you.  You can go check on them if you like."));

}

function errormessage($msg) {
	echo "<div style=\"margin-top: 50px;\">";
	echo "<span style=\"color:#d89334;\">error </span>";
	echo $msg;
	echo "</div>";
}
function ajaxLoop($ajaxFunction, $refresh, $extraFunction = ''){
	return "
	setInterval(function() {
		$.ajax({
			url: 'ajax.php?a=".$ajaxFunction."',
			timeout: 10000,
			type: 'GET',
			success: function(response) {
				var getDiv = response;
				var loadedID = 	$(getDiv).attr('id');
				if (typeof loadedID !== 'undefined') {
					var oldElement = $('#'+loadedID).prop('outerHTML');
					var newElement = $(getDiv).prop('outerHTML');
					if(oldElement !== newElement){
						$('#'+loadedID).replaceWith($(getDiv).prop('outerHTML'));
						".$extraFunction."
						console.log('".$ajaxFunction." has been updated');
					}
				}else{
					console.log('".$ajaxFunction." data was not sufficent or is offline');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('".$ajaxFunction." could not be updated');
			}
		});
	}, ".$refresh.");
	";
}
function getOrgUsers(){
	$file_db = DATABASE_LOCATION."users.db";
	if(file_exists($file_db)){
		$conn = new PDO("sqlite:$file_db") or die("1");
		$result = $conn->query('SELECT * FROM users');
		$conn = null;
		if (is_array($result) || is_object($result)){
			foreach($result as $k => $v){
				$return[$v['username']] = $v['email'];
			}
			return $return;
		}
	}
}

function getEmails($type = 'org'){
	if($type == 'plex'){
		$emails = array_merge(libraryList()['both'],getOrgUsers());
	}elseif($type == 'emby'){
		$emails = getOrgUsers();
	}else{
		$emails = getOrgUsers();
	}
	return $emails;
}

function printEmails($emails){
	$result = '';
	foreach($emails as $k => $v){
		$result .= '<option value="'.$v.'">'.$k.'</option>';
	}
	return $result;
}

function massEmail($to, $subject, $message){
	if (!isset($GLOBALS['file_db'])) {
		$GLOBALS['file_db'] = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
		$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	$emailTemplate = array(
		'type' => 'mass',
		'body' => $message,
		'subject' => $subject,
		'user' => null,
		'password' => null,
		'inviteCode' => null,
	);
	$emailTemplate = emailTemplate($emailTemplate);
	$subject = $emailTemplate['subject'];
	$body = buildEmail($emailTemplate);
	sendEmail(null, null, $subject, $body, $GLOBALS['USER']->adminEmail,$to);
}

function q2a($q){
	if (is_array($q) || is_object($q)){
		foreach ($q as $k => $v){
			$a[$k] = $v;
		}
		if(!empty($a)){
			return $a;
		}
	}
}

function getOmbiToken($username, $password){
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json"
	);
	$json = array(
		"username" => $username,
        "password" => $password,
		"rememberMe" => "true",
         );
	$api = curl_post(OMBIURL."/api/v1/Token", $json, $headers);
	if (isset($api['content'])) {
		return json_decode($api['content'], true)['access_token'];
	}else{
		return false;
	}

}

function ombiAction($id, $action, $type){
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json",
		"Apikey" => OMBIKEY
	);
	$body = array(
		'id' => $id,
	);
	switch ($type) {
		case 'season':
		case 'tv':
			$type = 'tv';
			break;
		default:
			$type = 'movie';
			break;
	}
	switch ($action) {
		case 'approve':
			$api = curl_post(OMBIURL."/api/v1/Request/".$type."/approve", $body, $headers);
			break;
		case 'available':
				$api = curl_post(OMBIURL."/api/v1/Request/".$type."/available", $body, $headers);
				break;
		case 'unavailable':
				$api = curl_post(OMBIURL."/api/v1/Request/".$type."/unavailable", $body, $headers);
				break;
		case 'deny':
			$api = curl_put(OMBIURL."/api/v1/Request/".$type."/deny", $body, $headers);
			break;
		case 'delete':
			$api = curl_delete(OMBIURL."/api/v1/Request/".$type."/".$id, $headers);
			break;
		default:
			# code...
			break;
	}
	switch ($api['http_code']['http_code']){
		case 401:
			writeLog("error", "OMBI: Invalid API KEY");
			return false;
			break;
		case 200:
			writeLog("success", "OMBI: action completed successfully for [type: $type - action: $action - id: $id]");
			return true;
			break;
		default:
			writeLog("error", "OMBI: unknown error with request [type: $type - action: $action - id: $id]");
			return false;
	}
    //return (!empty($result) ? $result : null );
}

function getOmbiRequests($type = "both"){
	$headers = array(
		"Accept" => "application/json",
		"Apikey" => OMBIKEY,
	);
	$requests = array();
	switch ($type) {
		case 'movie':
			$movie = json_decode(curl_get(OMBIURL."/api/v1/Request/movie", $headers), true);
			break;
		case 'tv':
			$tv = json_decode(curl_get(OMBIURL."/api/v1/Request/tv", $headers), true);
			break;

		default:
			$movie = json_decode(curl_get(OMBIURL."/api/v1/Request/movie", $headers), true);
			$tv = json_decode(curl_get(OMBIURL."/api/v1/Request/tv", $headers), true);
			break;
	}
	if(isset($movie)){
		//$movie = array_reverse($movie);
		foreach ($movie as $key => $value) {
			$poster = explode('/',$value['posterPath']);
			$requests[] = array(
				'id' => $value['theMovieDbId'],
				'title' => $value['title'],
				'poster' => (strpos($value['posterPath'], "/") !== false) ? 'https://image.tmdb.org/t/p/w300/'.end($poster) : 'https://image.tmdb.org/t/p/w300/'.$value['posterPath'],
				'approved' => $value['approved'],
				'available' => $value['available'],
				'denied' => $value['denied'],
				'deniedReason' => $value['deniedReason'],
				'user' => $value['requestedUser']['userName'],
				'request_id' => $value['id'],
				'request_date' => $value['requestedDate'],
				'release_date' => $value['releaseDate'],
				'type' => 'movie',
				'icon' => 'mdi mdi-filmstrip',
				'color' => 'palette-Deep-Purple-900 bg white',
			);
		}
	}
	if(isset($tv) && (is_array($tv) || is_object($tv))){
		foreach ($tv as $key => $value) {
			if(is_array($value['childRequests'][0])){
				$requests[] = array(
					'id' => $value['tvDbId'],
					'title' => $value['title'],
					'poster' => $value['posterPath'],
					'approved' => $value['childRequests'][0]['approved'],
					'available' => $value['childRequests'][0]['available'],
					'denied' => $value['childRequests'][0]['denied'],
					'deniedReason' => $value['childRequests'][0]['deniedReason'],
					'user' => $value['childRequests'][0]['requestedUser']['userName'],
					'request_id' => $value['id'],
					'request_date' => $value['childRequests'][0]['requestedDate'],
					'release_date' => $value['releaseDate'],
					'type' => 'tv',
					'icon' => 'mdi mdi-television',
					'color' => 'grayish-blue-bg',
				);
			}
		}
	}
    return (empty($requests)) ? '' : $requests;
}

function convertOmbiString($type, $value){
	switch ($type) {
		case 'approved':
			$string['string'] = ($value) ? 'Approved' : 'Approval-Pending';
			$string['icon'] = ($value) ? 'mdi mdi-check' : 'mdi mdi-clock';
			$string['color'] = ($value) ? 'green-bg' : 'yellow-bg';
			break;
		case 'available':
			$string['string'] = ($value) ? 'Available' : 'Not Downloaded';
			$string['icon'] = ($value) ? 'mdi mdi-server' : 'mdi mdi-server-off';
			$string['color'] = ($value) ? 'green-bg' : 'red-bg';
			break;
		case 'denied':
			$string['string'] = ($value) ? 'Denied' : 'Approved';
			$string['icon'] = ($value) ? 'mdi mdi-emoticon-sad' : 'mdi mdi-emoticon-happy';
			$string['color'] = ($value) ? 'red-bg' : 'green-bg';
			break;
		case 'status':
			switch ($value) {
				case '1':
					$string['string'] = 'Denied';
					$string['icon'] = 'mdi mdi-window-close';
					$string['color'] = 'red-bg';
					break;
				case '2':
					$string['string'] = 'Approved';
					$string['icon'] = 'mdi mdi-check';
					$string['color'] = 'green-bg';
					break;
				case '3':
					$string['string'] = 'Not Approved';
					$string['icon'] = 'mdi mdi-clock';
					$string['color'] = 'yellow-bg';
					break;

				default:
					# code...
					break;
			}
			break;
		default:
			$string['string'] = ($value) ? 'Approved' : 'Approval-Pending';
			$string['color'] = ($value) ? 'green-bg' : 'red-bg';
			break;
	}
	return $string;
}
function buildOmbiItem($type, $group, $user, $request){
	if (is_array($request) || is_object($request)){
		$actions = '';
		if($request['denied']){
			$status = 1;
			$actions .= '<li request-type="'.$type.'" request-id="'.$request['request_id'].'" request-name="approve"><a class="requestAction" href="javascript:void(0)">Approve</a></li>';
		}else{
			if($request['approved']){
				$status = 2;
			}else{
				$status = 3;
				$actions .= '<li request-type="'.$type.'" request-id="'.$request['request_id'].'" request-name="approve"><a class="requestAction" href="javascript:void(0)">Approve</a></li>';
				$actions .= '<li request-type="'.$type.'" request-id="'.$request['request_id'].'" request-name="deny"><a class="requestAction" href="javascript:void(0)">Deny</a></li>';
			}
		}
		if($request['available']){
			$actions .= '<li request-type="'.$type.'" request-id="'.$request['request_id'].'" request-name="unavailable"><a class="requestAction" href="javascript:void(0)">Mark as Unavailable</a></li>';
		}else{
			$actions .= '<li request-type="'.$type.'" request-id="'.$request['request_id'].'" request-name="available"><a class="requestAction" href="javascript:void(0)">Mark as Available</a></li>';
		}
		$actions .= '<li request-type="'.$type.'" request-id="'.$request['request_id'].'" request-name="delete"><a class="requestAction" href="javascript:void(0)">Delete</a></li>';
		if(isset($group) && $group == 'admin'){
			$actionMenu = '
			<div class="requestOptions">
				<div class="btn-group transparent" role="group">
					<button type="button" class="btn waves btn-success  btn-sm dropdown-toggle waves-effect waves-float transparent" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical mdi-24px"></i></button>
					<ul class="dropdown-menu"><h6 class="text-center requestHeader gray-bg">'.$request['user'].'</h6>'.$actions.'</ul>
				</div>
			</div>
			';
		}else{
			$actionMenu = '';
		}
		if((isset($group)) && $group == 'admin' || REQUESTEDUSERONLY == 'false'){
			return '
			<div class="item-'.$type.'-'.convertOmbiString('approved', $request['approved'])['string'].'">
				'.$actionMenu.'
				<a class="openTab" extraTitle="'.$request['title'].'" extraType="'.$type.'" openTab="true"><img alt="" class="slick-image-tall" data-lazy="'.$request['poster'].'"></a>
				<div class="requestBottom text-center">
					<div data-toggle="tooltip" data-placement="top" data-original-title="'.$request['type'].'" class="zero-m requestGroup '.$request['color'].'">
						<i class="'.$request['icon'].'"></i>
					</div>
					<div data-toggle="tooltip" data-placement="top" data-original-title="'.convertOmbiString('status', $status)['string'].'" class="zero-m requestGroup '.convertOmbiString('status', $status)['color'].'">
						<i class="'.convertOmbiString('status', $status)['icon'].'"></i>
					</div>
					<div data-toggle="tooltip" data-placement="top" data-original-title="'.convertOmbiString('available', $request['available'])['string'].'" class="zero-m requestGroup '.convertOmbiString('available', $request['available'])['color'].'">
						<i class="'.convertOmbiString('available', $request['available'])['icon'].'"></i>
					</div>
				</div>
				<small class="elip slick-bottom-title">'.$request['title'].'</small>
			</div>';
		}else{
			if(strtolower($request['user']) == strtolower($user)){
				return '
				<div class="item-'.$type.'-'.convertOmbiString('approved', $request['approved'])['string'].'">
					'.$actionMenu.'
					<a class="openTab" extraTitle="'.$request['title'].'" extraType="'.$type.'" openTab="true"><img alt="" class="slick-image-tall" data-lazy="'.$request['poster'].'"></a>
					<div class="requestBottom text-center">
						<div data-toggle="tooltip" data-placement="top" data-original-title="'.$request['type'].'" class="zero-m requestGroup '.$request['color'].'">
							<i class="'.$request['icon'].'"></i>
						</div>
						<div data-toggle="tooltip" data-placement="top" data-original-title="'.convertOmbiString('status', $status)['string'].'" class="zero-m requestGroup '.convertOmbiString('status', $status)['color'].'">
							<i class="'.convertOmbiString('status', $status)['icon'].'"></i>
						</div>
						<div data-toggle="tooltip" data-placement="top" data-original-title="'.convertOmbiString('available', $request['available'])['string'].'" class="zero-m requestGroup '.convertOmbiString('available', $request['available'])['color'].'">
							<i class="'.convertOmbiString('available', $request['available'])['icon'].'"></i>
						</div>
					</div>
					<small class="elip slick-bottom-title">'.$request['title'].'</small>
				</div>';
			}
		}
	}
}
function buildOmbiList($group, $user){
	$requests = array();
	$movieList = getOmbiRequests('movie');
	$tvList = getOmbiRequests('tv');
	if(is_array($movieList) && is_array($tvList)){
		$result = array_merge($movieList , $tvList );
	}else{
		if(is_array($movieList)){
			$result = $movieList;
		}elseif(is_array($tvList)){
			$result = $tvList;
		}else{
			$result = false;
		}
	}
	if (is_array($result) || is_object($result)){
		usort($result, function ($item1, $item2) {
			if ($item1['request_date'] == $item2['request_date']) return 0;
			return $item1['request_date'] > $item2['request_date'] ? -1 : 1;
		});
		foreach ($result as $request) {
			if($request['type'] == 'movie'){
		        $requests[] = buildOmbiItem('movie', $group, $user, $request);
		    }elseif($request['type'] == 'tv'){
		        $requests[] = buildOmbiItem('season', $group, $user, $request);
		    }
		}
	}
	return outputOmbiRequests("Requested Content", $requests, ajaxLoop('ombi-requests',REQUESTREFRESH,'loadSlick();'), false);
}

function outputOmbiRequests($header = "Requested Content", $items, $script = false, $array) {
    $hideMenu = '<div class="pull-right"><div class="btn-group" role="group"><button type="button" class="btn waves btn-default btn-sm dropdown-toggle waves-effect waves-float" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Filter &nbsp;<span class="caret"></span></button><ul style="right:0; left: auto" class="dropdown-menu filter-request-event">';
	if(preg_grep("/item-movie-Approved/", $items)){
        $hideMenu .= '<li data-filter="item-movie-Approved" data-name="Approved Movies" data-filter-on="false"><a class="js-filter-movie" href="javascript:void(0)">Approved Movies</a></li>';
    }
	if(preg_grep("/item-movie-Approval-Pending/", $items)){
        $hideMenu .= '<li data-filter="item-movie-Approval-Pending" data-name="Approval Pending Movies" data-filter-on="false"><a class="js-filter-movie" href="javascript:void(0)">Approval Pending Movies</a></li>';
    }
	if(preg_grep("/item-season-Approved/", $items)){
        $hideMenu .= '<li data-filter="item-season-Approved" data-name="Approved TV Shows" data-filter-on="false"><a class="js-filter-season" href="javascript:void(0)">Approved Shows</a></li>';
    }
	if(preg_grep("/item-season-Approval-Pending/", $items)){
        $hideMenu .= '<li data-filter="item-season-Approval-Pending" data-name="Approval Pending TV Shows" data-filter-on="false"><a class="js-filter-season" href="javascript:void(0)">Approval Pending Shows</a></li>';
    }
	$hideMenu .= '<li data-filter="item-all" data-name="Content" data-filter-on="false"><a class="js-filter-all" href="javascript:void(0)">All</a></li>';
    $hideMenu .= '</ul></div></div>';
    // If None Populate Empty Item
    //if (count(array_flip($items)) < 1) {
	if(!array_filter($items)) {
        return '<div id="recentRequests"></div>';
    }else{
		$className = str_replace(' ', '', $header);
        return '<div id="recentRequests" class="content-box box-shadow big-box"><h5 id="requestContent-title" style="margin-bottom: -20px" class="text-center"><span>'.$header.'</span></h5><div class="recentHeader inbox-pagination '.$className.'">'.$hideMenu.'</div><br/><br/><div class="recentItems-request" data-name="'.$className.'">'.implode('',$items).'</div></div>'.($script?'<script>'.$script.'</script>':'');
    }
}

function ombiAPI($action){
	$headers = array(
		"Accept" => "application/json",
		"Content-Type" => "application/json",
		"Apikey" => OMBIKEY
	);
	$body = array();
	switch ($action) {
		case 'plex-cache':
			$api = curl_post(OMBIURL."/api/v1/Job/plexcontentcacher", $body, $headers);
			break;
		default:
			break;
	}
	if(is_array($api) || is_object($api)){
		switch ($api['http_code']['http_code']){
			case 200:
				return true;
				break;
			default:
				return false;
		}
	}else{
		return false;
	}
}

function loadIcons(){
	$dirname = "images/";
	$images = scandir($dirname);
	$ignore = Array(".", "..", "favicon", "settings", "cache", "platforms", "._.DS_Store", ".DS_Store", "confused.png", "sowwy.png", "sort-btns", "loading.png", "titlelogo.png", "default.svg", "login.png", "no-np.png", "no-list.png", "no-np.psd", "no-list.psd", "themes", "nadaplaying.jpg", "organizr-logo-h-d.png", "organizr-logo-h.png");
	$allIcons = '';
	foreach($images as $curimg){
		if(!in_array($curimg, $ignore)) {
			$allIcons .= '
			<div class="col-xs-2" style="width: 75px; height: 75px; padding-right: 0px;">
				<a data-toggle="tooltip" data-placement="bottom" title="'.$dirname.$curimg.'" class="thumbnail" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
					<img style="width: 50px; height: 50px;" data-src="'.$dirname.$curimg.'" alt="thumbnail" class="allIcons lazyload shadow">
				</a>
			</div>
			';
		}
	}
	return $allIcons;
}

function buildHomepageSettings(){
	$homepageOrder = homepageOrder();
	$homepageList = '<h4>Drag Homepage Items to Order Them</h4><div id="homepage-items" class="external-events">';
	$inputList = '<div id="homepage-values" class="row">';
	foreach ($homepageOrder as $key => $val) {
		switch ($key) {
			case 'homepageOrdercustomhtml':
				$class = 'palette-Deep-Purple-100 bg gray';
				$image = 'images/html.png';
				if(empty(HOMEPAGECUSTOMHTML1)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrdernotice':
				$class = 'palette-Cyan-A400 bg gray';
				$image = 'images/pin.png';
				if(empty(HOMEPAGENOTICETITLE) && empty(HOMEPAGENOTICEMESSAGE)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrdernoticeguest':
				$class = 'palette-Cyan-A400 bg gray';
				$image = 'images/pin.png';
				if(empty(HOMEPAGENOTICETITLEGUEST) && empty(HOMEPAGENOTICEMESSAGEGUEST)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrderspeedtest':
				$class = 'red-bg';
				$image = 'images/settings/full-color/png/64px/speedometer.png';
				if(SPEEDTEST !== "true"){
					$class .= ' faded';
				}
				break;
			case 'homepageOrdertransmisson':
				$class = 'green-bg';
				$image = 'images/transmission.png';
				if(empty(TRANSMISSIONURL)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrdernzbget':
				$class = 'green-bg';
				$image = 'images/nzbget.png';
				if(empty(NZBGETURL)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrdersabnzbd':
				$class = 'yellow-bg';
				$image = 'images/sabnzbd.png';
				if(empty(SABNZBDURL)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrderplexsearch':
			case 'homepageOrderplexnowplaying':
			case 'homepageOrderplexrecent':
			case 'homepageOrderplexplaylist':
				$class = 'palette-Amber-A700 bg gray';
				$image = 'images/plex.png';
				if(empty(PLEXURL)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrderembynowplaying':
			case 'homepageOrderembyrecent':
				$class = 'palette-Green-A700 bg gray';
				$image = 'images/emby.png';
				if(empty(EMBYURL)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrderombi':
				$class = 'orange-bg';
				$image = 'images/ombi.png';
				if(empty(OMBIURL)){
					$class .= ' faded';
				}
				break;
			case 'homepageOrdercalendar':
				$class = 'palette-Blue-400 bg gray';
				$image = 'images/calendar.png';
				if(SONARRURL == "" && RADARRURL == "" && HEADPHONESURL == "" && SICKRAGEURL == "" && COUCHURL == "" ){
					$class .= ' faded';
				}
				break;
			default:
				$class = 'blue-bg';
				$image = '';
				break;
		}
		$homepageList .= '
		<div class="col-md-3 sort-homepage"><div class="fc-event '.$class.'">
			<span class="ordinal-position text-uppercase badge badge-gray" data-link="'.$key.'" style="float:left;width: 30px;">'.$val.'</span>
			&nbsp; '.strtoupper(substr($key, 13)).'
			<span class="remove-event"><img style="width: 22px;" src="'.$image.'"></span>
		</div></div>';
		$inputList .= '<input type="hidden" name="'.$key.'">';
	}
	$homepageList .= '</div>';
	$inputList .= '</div>';
	return $homepageList.$inputList;
}

function buildHomepage($group, $user){
	$homepageOrder = homepageOrder();
	$homepageBuilt = '';
	foreach ($homepageOrder as $key => $value) {
		$homepageBuilt .= buildHomepageItem($key, $group, $user);
	}
	return $homepageBuilt;
}

function realSize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' '.@$size[$factor];
}

function buildHomepageItem($homepageItem, $group, $user){
	$homepageItemBuilt = '';
	switch ($homepageItem) {
		case 'homepageOrderplexsearch':
			if((qualifyUser(PLEXSEARCHAUTH) && PLEXSEARCH == "true" && qualifyUser(PLEXHOMEAUTH))) {
				$homepageItemBuilt .= '
				<div id="searchPlexRow" class="row">
					<div class="col-lg-12">
						<div class="content-box box-shadow big-box todo-list">
							<form id="plexSearchForm" onsubmit="return false;" autocomplete="off">
								<div class="">
									<div class="input-group">
										<div style="border-radius: 25px 0 0 25px; border:0" class="input-group-addon gray-bg"><i class="fa fa-search white"></i></div>
										<input id="searchInput" type="text" style="border-radius: 0;" autocomplete="off" name="search-title" class="form-control input-group-addon gray-bg" placeholder="Media Search">
										<div id="clearSearch" style="border-radius: 0 25px 25px 0;border:0; cursor: pointer;" class="input-group-addon gray-bg"><i class="fa fa-close white"></i></div>
										<button style="display:none" id="plexSearchForm_submit" class="btn btn-primary waves"></button>
									</div>
								</div>
							</form>
							<div id="resultshere" class="table-responsive"></div>
						</div>
					</div>
				</div>
				';
			}
			break;
		case 'homepageOrdercustomhtml':
			if (qualifyUser(HOMEPAGECUSTOMHTML1AUTH) && HOMEPAGECUSTOMHTML1) {
				$homepageItemBuilt .= "<div>" . HOMEPAGECUSTOMHTML1 . "</div>";
			}
			break;
		case 'homepageOrdernotice':
			if (qualifyUser(HOMEPAGENOTICEAUTH) && HOMEPAGENOTICETITLE && HOMEPAGENOTICETYPE && HOMEPAGENOTICEMESSAGE && HOMEPAGENOTICELAYOUT) {
				$homepageItemBuilt .= buildHomepageNotice(HOMEPAGENOTICELAYOUT, HOMEPAGENOTICETYPE, HOMEPAGENOTICETITLE, HOMEPAGENOTICEMESSAGE);
			}
			break;
		case 'homepageOrdernoticeguest':
			if ($group == 'guest' && HOMEPAGENOTICETITLEGUEST && HOMEPAGENOTICETYPEGUEST && HOMEPAGENOTICEMESSAGEGUEST && HOMEPAGENOTICELAYOUTGUEST) {
				$homepageItemBuilt .= buildHomepageNotice(HOMEPAGENOTICELAYOUTGUEST, HOMEPAGENOTICETYPEGUEST, HOMEPAGENOTICETITLEGUEST, HOMEPAGENOTICEMESSAGEGUEST);
			}
			break;
		case 'homepageOrderspeedtest':
			if(SPEEDTEST == "true" && qualifyUser(SPEEDTESTAUTH)){
				$homepageItemBuilt .= '
				<style type="text/css">
					.flash {
						animation: flash 0.6s linear infinite;
					}
					@keyframes flash {
						0% { opacity: 0.6; }
						50% { opacity: 1; }
					}
				</style>
				<script type="text/javascript">
				var w = null
					function runTest() {
						document.getElementById("startBtn").style.display = "none"
						document.getElementById("testArea").style.display = ""
						document.getElementById("abortBtn").style.display = ""
						w = new Worker("bower_components/speed/speedtest_worker.js")
						var interval = setInterval(function () { w.postMessage("status") }, 100)
						w.onmessage = function (event) {
							var data = event.data.split(";")
							var status = Number(data[0])
							var dl = document.getElementById("download")
							var ul = document.getElementById("upload")
							var ping = document.getElementById("ping")
							var jitter = document.getElementById("jitter")
							dl.className = status === 1 ? "w-name flash" : "w-name"
							ping.className = status === 2 ? "w-name flash" : "w-name"
							jitter.className = ul.className = status === 3 ? "w-name flash" : "w-name"
							if (status >= 4) {
								clearInterval(interval)
								document.getElementById("abortBtn").style.display = "none"
								document.getElementById("startBtn").style.display = ""
								w = null
							}
							if (status === 5) {
								document.getElementById("testArea").style.display = "none"
							}
							dl.textContent = data[1] + " Mbit/s";
							$("#downloadpercent").attr("style", "width: " + data[1] + "%;");
							$("#uploadpercent").attr("style", "width: " + data[2] + "%;");
							$("#pingpercent").attr("style", "width: " + data[3] + "%;");
							$("#jitterpercent").attr("style", "width: " + data[5] + "%;");
							ul.textContent = data[2] + " Mbit/s";
							ping.textContent = data[3] + " ms";
							jitter.textContent = data[5] + " ms";
						}
						w.postMessage(\'start {"telemetry_level":"basic"}\')
						//w.postMessage("start")
					}
					function abortTest() {
						if (w) w.postMessage("abort")
					}
				</script>
				<div class="row" id="testArea" style="display:none">
					<div class="test col-sm-3 col-lg-3">
						<div class="content-box ultra-widget green-bg" data-counter="">
							<div id="downloadpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
							<div class="w-content">
								<div class="w-icon right pull-right"><i class="mdi mdi-cloud-download"></i></div>
								<div class="w-descr left pull-left text-center">
									<span class="testName text-uppercase w-name">Download</span>
									<br>
									<span class="w-name counter" id="download" ></span>
								</div>
							</div>
						</div>
					</div>
					<div class="test col-sm-3 col-lg-3">
						<div class="content-box ultra-widget red-bg" data-counter="">
							<div id="uploadpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
							<div class="w-content">
								<div class="w-icon right pull-right"><i class="mdi mdi-cloud-upload"></i></div>
								<div class="w-descr left pull-left text-center">
									<span class="testName text-uppercase w-name">Upload</span>
									<br>
									<span class="w-name counter" id="upload" ></span>
								</div>
							</div>
						</div>
					</div>
					<div class="test col-sm-3 col-lg-3">
						<div class="content-box ultra-widget yellow-bg" data-counter="">
							<div id="pingpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
							<div class="w-content">
								<div class="w-icon right pull-right"><i class="mdi mdi-timer"></i></div>
								<div class="w-descr left pull-left text-center">
									<span class="testName text-uppercase w-name">Latency</span>
									<br>
									<span class="w-name counter" id="ping" ></span>
								</div>
							</div>
						</div>
					</div>
					<div class="test col-sm-3 col-lg-3">
						<div class="content-box ultra-widget blue-bg" data-counter="">
							<div id="jitterpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
							<div class="w-content">
								<div class="w-icon right pull-right"><i class="mdi mdi-pulse"></i></div>
								<div class="w-descr left pull-left text-center">
									<span class="testName text-uppercase w-name">Jitter</span>
									<br>
									<span class="w-name counter" id="jitter" ></span>
								</div>
							</div>
						</div>
					</div>
					<br/>
				</div>
				<div id="abortBtn" class="row" style="display: none" onclick="javascript:abortTest()">
					<div class="col-lg-12">
						<div class="content-box red-bg" style="cursor: pointer;">
							<h1 style="margin: 10px" class="text-uppercase text-center">Abort Speed Test</h1>
							<div class="clearfix"></div>
						</div>
					</div>
				</div>
				<div id="startBtn" class="row" onclick="javascript:runTest()">
					<div class="col-lg-12">
						<div class="content-box green-bg" style="cursor: pointer;">
							<h1 style="margin: 10px" class="text-uppercase text-center">Run Speed Test</h1>
							<div class="clearfix"></div>
						</div>
					</div>
				</div>
				';
			}
			break;
		case 'homepageOrdertransmisson':
			if(TRANSMISSIONURL != "" && qualifyUser(TRANSMISSIONHOMEAUTH)){
				$homepageItemBuilt .= buildDownloader('transmission', 'no');
			}
			break;
		case 'homepageOrdernzbget':
			if(NZBGETURL != "" && qualifyUser(NZBGETHOMEAUTH)){
				$homepageItemBuilt .= buildDownloader('nzbget');
			}
			break;
		case 'homepageOrdersabnzbd':
			if(SABNZBDURL != "" && qualifyUser(SABNZBDHOMEAUTH)) {
				$homepageItemBuilt .= buildDownloader('sabnzbd');
			}
			break;
		case 'homepageOrderplexnowplaying':
			if (qualifyUser(PLEXHOMEAUTH) && PLEXTOKEN) {
				if(qualifyUser(PLEXPLAYINGNOWAUTH) && PLEXPLAYINGNOW == "true"){
					$homepageItemBuilt .= '<div id="plexRowNowPlaying" class="row">';
					$homepageItemBuilt .= getPlexStreams(12, PLEXSHOWNAMES, $group);
					$homepageItemBuilt .= '</div>';
				}
			}
			break;
		case 'homepageOrderplexrecent':
			if (qualifyUser(PLEXHOMEAUTH) && PLEXTOKEN) {
				if(qualifyUser(PLEXRECENTMOVIEAUTH) && PLEXRECENTMOVIE == "true" || qualifyUser(PLEXRECENTTVAUTH) && PLEXRECENTTV == "true" || qualifyUser(PLEXRECENTMUSICAUTH) &&  PLEXRECENTMUSIC == "true"){
					$plexArray = array("movie" => PLEXRECENTMOVIE, "season" => PLEXRECENTTV, "album" => PLEXRECENTMUSIC);
					$homepageItemBuilt .= '<div id="plexRow" class="row"><div class="col-lg-12">';
					$homepageItemBuilt .= getPlexRecent($plexArray);
					$homepageItemBuilt .= '</div></div>';
				}
			}
			break;
		case 'homepageOrderplexplaylist':
			if (qualifyUser(PLEXHOMEAUTH) && PLEXTOKEN) {
				if(qualifyUser(PLEXPLAYLISTSAUTH) && PLEXPLAYLISTS == "true"){
					$homepageItemBuilt .= '<div id="plexPlaylists" class="row"><div class="col-lg-12">';
					$plexArray = array("movie" => PLEXRECENTMOVIE, "season" => PLEXRECENTTV, "album" => PLEXRECENTMUSIC);
					$homepageItemBuilt .= getPlexPlaylists($plexArray);
					$homepageItemBuilt .= '</div> </div>';
				}
			}
			break;
		case 'homepageOrderembynowplaying':
			if (qualifyUser(EMBYHOMEAUTH) && EMBYTOKEN) {
				if(qualifyUser(EMBYPLAYINGNOWAUTH) && EMBYPLAYINGNOW == "true"){
					$homepageItemBuilt .= '<div id="embyRowNowPlaying" class="row">';
					$homepageItemBuilt .= getEmbyStreams(12, EMBYSHOWNAMES, $group);
					$homepageItemBuilt .= '</div>';
				}
			}
			break;
		case 'homepageOrderembyrecent':
			if (qualifyUser(EMBYHOMEAUTH) && EMBYTOKEN) {
				if(qualifyUser(EMBYRECENTMOVIEAUTH) && EMBYRECENTMOVIE == "true" || qualifyUser(EMBYRECENTTVAUTH) && EMBYRECENTTV == "true" || qualifyUser(EMBYRECENTMUSICAUTH) && EMBYRECENTMUSIC == "true"){
					$embyArray = array("Movie" => EMBYRECENTMOVIE, "Episode" => EMBYRECENTTV, "MusicAlbum" => EMBYRECENTMUSIC, "Series" => EMBYRECENTTV);
					$homepageItemBuilt .= '<div id="embyRow" class="row"><div class="col-lg-12">';
					$homepageItemBuilt .= getEmbyRecent($embyArray);
					$homepageItemBuilt .= '</div></div>';
				}
			}
			break;
		case 'homepageOrderombi':
			if (qualifyUser(OMBIAUTH) && OMBIURL) {
				$homepageItemBuilt .= '<div id="ombiRequests" class="row"><div class="col-lg-12">';
				$homepageItemBuilt .= buildOmbiList($group, $user);
				$homepageItemBuilt .= '</div></div>';
			}
			break;
		case 'homepageOrdercalendar':
			if ((SONARRURL != "" && qualifyUser(SONARRHOMEAUTH)) || (RADARRURL != "" && qualifyUser(RADARRHOMEAUTH)) || (HEADPHONESURL != "" && qualifyUser(HEADPHONESHOMEAUTH)) || (SICKRAGEURL != "" && qualifyUser(SICKRAGEHOMEAUTH)) || (COUCHURL != "" && qualifyUser(COUCHHOMEAUTH))) {
				$calendarItems = '';
				if(RADARRURL != ""){ $calendarItems .= '<li><a class="calendarOption" calendarOption="film" href="javascript:void(0)">Movies</a></li>'; }
				if(SONARRURL != ""){ $calendarItems .= '<li><a class="calendarOption" calendarOption="tv" href="javascript:void(0)">TV Shows</a></li>'; }
				if(HEADPHONESURL != ""){ $calendarItems .= '<li><a class="calendarOption" calendarOption="music" href="javascript:void(0)">Music</a></li>'; }
				$homepageItemBuilt .= '
				<div id="calendarLegendRow" class="row" style="padding: 0 0 10px 0;">
					<div class="col-lg-12 content-form form-inline">
						<div class="form-group pull-right">
							<span class="swal-legend label label-primary well-sm">Legend</span>&nbsp;
							<div class="btn-group" role="group">
								<button id="calendarSelected" style="margin-right: 0px;" type="button" class="btn waves btn-default btn-sm dropdown-toggle waves-effect waves-float" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">View All&nbsp;<span class="caret"></span></button>
								<ul style="right:0; left: auto" class="dropdown-menu">
									<li><a class="calendarOption" calendarOption="all" href="javascript:void(0)">View All</a></li>
									'.$calendarItems.'
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div id="calendarRow" class="row">
					<div class="col-lg-12">
						<div id="calendar" class="fc-calendar box-shadow fc fc-ltr fc-unthemed"></div>
					</div>
				</div>
				';
			}
			break;
		default:
			# code...
			break;
	}
	return $homepageItemBuilt;
}

function buildAccordion($items){
	$i = 1;
	$variables = '&nbsp; Available Variables: ';
	$accordion = '<div style="margin-bottom: 0px;" class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
	foreach ($items as $key => $value) {
		if($value['type'] == 'template' || $value['type'] == 'templateCustom'){
			foreach ($value['variables'] as $variable) {
				$variables .= '<mark>'.$variable.'</mark>';
			}
			$templateCustom = '
			<div class="form-content col-sm-12 col-md-12 col-lg-12">
				<input id="'.$value['template'].'Name_id" name="'.$value['template'].'Name" type="text" class="form-control material input-sm" autocorrect="off" autocapitalize="off" value="'.$value['title'].'">
				<p class="help-text">Custom Template Name</p>
			</div>
			';
			$accordion .= '
			<div class="panel panel-default">
				<div class="panel-heading" role="tab" id="heading-'.$i.'">
					<h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-'.$i.'" aria-expanded="true" aria-controls="collapse-'.$i.'">'.$value['title'].'</h4>
				</div>
				<div id="collapse-'.$i.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-'.$i.'" aria-expanded="true">
					<br/>'.$variables.'<br/></br/>
					'.$templateCustom.'
					<div class="form-content col-sm-12 col-md-12 col-lg-12">
						<input id="'.$value['template'].'Subject_id" name="'.$value['template'].'Subject" type="text" class="form-control material input-sm" autocorrect="off" autocapitalize="off" value="'.$value['subject'].'">
						<p class="help-text">Email Subject</p>
					</div>
					<br/></br/>
					<div class="summernote" name="'.$value['template'].'">'.$value['body'].'</div>
				</div>
			</div>
			';
			$i++;
			$variables = '&nbsp; Available Variables: ';
		}else{
			$accordion .= '
			<div class="panel panel-default">
				<div class="panel-heading" role="tab" id="heading-'.$i.'">
					<h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-'.$i.'" aria-expanded="true" aria-controls="collapse-'.$i.'">Logo URL For Title</h4>
				</div>
				<div id="collapse-'.$i.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-'.$i.'" aria-expanded="true">
					<div class="form-content col-sm-12 col-md-12 col-lg-12">
						<input id="'.$value['name'].'_id" name="'.$value['name'].'" type="text" class="form-control material input-sm" autocorrect="off" autocapitalize="off" value="'.$value['value'].'">
						<p class="help-text">Logo URL For Title</p>
					</div>
					<br/></br/><br/>
				</div>
			</div>
			';
			$i++;
		}
	}
	$accordion .= '</div>';
	return $accordion;
}

function emailTemplate($emailTemplate){
	$variables = [
		'{user}' => $emailTemplate['user'],
		'{domain}' => DOMAIN,
		'{password}' => $emailTemplate['password'],
		'{inviteCode}' => $emailTemplate['inviteCode'],
		'{fullDomain}' => getServerPath(),
	];
	$emailTemplate['body'] = strtr($emailTemplate['body'], $variables);
	$emailTemplate['subject'] = strtr($emailTemplate['subject'], $variables);
	return $emailTemplate;
}

function buildEmail($email){
	$subject = (isset($email['subject'])) ? $email['subject'] : 'Message from Server';
	$body = (isset($email['body'])) ? $email['body'] : 'Message Error Occured';
	$type = (isset($email['type'])) ? $email['type'] : 'No Type';
	switch ($type) {
		case 'invite':
			$extra = 'invite';
			break;
		case 'reset':
			$extra = 'reset';
			break;
		default:
			$extra = null;
			break;
	}
	include('email.php');
	return $email;
}

function buildDownloader($name, $type = 'both'){
	if($type == 'both'){
		$tabs = '
		<ul class="nav nav-tabs pull-right">
			<li class="active"><a href="#downloadQueue-'.$name.'" data-toggle="tab" aria-expanded="true">'.translate("QUEUE").'</a></li>
			<li class=""><a href="#downloadHistory-'.$name.'" data-toggle="tab" aria-expanded="false">'.translate("HISTORY").'</a></li>
		</ul>
		';
		$bodyHistory = '
		<div class="tab-pane fade" id="downloadHistory-'.$name.'">
			<div class="table-responsive" style="max-height: 300px">
				<table class="table table-striped progress-widget zero-m" style="max-height: 300px">
					<thead>
						<tr>
							<th class="col-xs-7 nzbtable-file-row">'.translate("FILE").'</th>
							<th class="col-xs-2 nzbtable">'.translate("STATUS").'</th>
							<th class="col-xs-1 nzbtable">'.translate("CATEGORY").'</th>
							<th class="col-xs-1 nzbtable">'.translate("SIZE").'</th>
							<th class="col-xs-2 nzbtable">'.translate("PROGRESS").'</th>
						</tr>
					</thead>
					<tbody class="dl-history '.$name.'"></tbody>
				</table>
			</div>
		</div>
		';
	}else{
		$tabs = '';
		$bodyHistory = '';
	}
	return '
<div id="downloadClientRow" class="row">
	<div class="col-xs-12 col-md-12">
		<div class="content-box">
			<div class="tabbable panel with-nav-tabs panel-default">
				<div class="panel-heading">
					<div class="content-tools i-block pull-right">
						<a id="getDownloader" class="repeat-btn">
							<i class="fa fa-repeat"></i>
						</a>
					</div>
					<h3 class="pull-left"><span>'.strtoupper($name).'</span></h3>
					'.$tabs.'
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<div class="tab-content">
						<div class="tab-pane fade active in" id="downloadQueue-'.$name.'">
							<div class="table-responsive" style="max-height: 300px">
								<table class="table table-striped progress-widget zero-m" style="max-height: 300px">
									<thead>
										<tr>
											<th class="col-xs-7 nzbtable-file-row">'.translate("FILE").'</th>
											<th class="col-xs-2 nzbtable">'.translate("STATUS").'</th>
											<th class="col-xs-1 nzbtable">'.translate("CATEGORY").'</th>
											<th class="col-xs-1 nzbtable">'.translate("SIZE").'</th>
											<th class="col-xs-2 nzbtable">'.translate("PROGRESS").'</th>
										</tr>
									</thead>
									<tbody class="dl-queue '.$name.'"></tbody>
								</table>
							</div>
						</div>
						'.$bodyHistory.'
					</div>
				</div>
			</div>
		</div>
	</div>
</div>';
}
class Mobile_Detect
{
    /**
     * Mobile detection type.
     *
     * @deprecated since version 2.6.9
     */
    const DETECTION_TYPE_MOBILE     = 'mobile';

    /**
     * Extended detection type.
     *
     * @deprecated since version 2.6.9
     */
    const DETECTION_TYPE_EXTENDED   = 'extended';

    /**
     * A frequently used regular expression to extract version #s.
     *
     * @deprecated since version 2.6.9
     */
    const VER                       = '([\w._\+]+)';

    /**
     * Top-level device.
     */
    const MOBILE_GRADE_A            = 'A';

    /**
     * Mid-level device.
     */
    const MOBILE_GRADE_B            = 'B';

    /**
     * Low-level device.
     */
    const MOBILE_GRADE_C            = 'C';

    /**
     * Stores the version number of the current release.
     */
    const VERSION                   = '2.8.26';

    /**
     * A type for the version() method indicating a string return value.
     */
    const VERSION_TYPE_STRING       = 'text';

    /**
     * A type for the version() method indicating a float return value.
     */
    const VERSION_TYPE_FLOAT        = 'float';

    /**
     * A cache for resolved matches
     * @var array
     */
    protected $cache = array();

    /**
     * The User-Agent HTTP header is stored in here.
     * @var string
     */
    protected $userAgent = null;

    /**
     * HTTP headers in the PHP-flavor. So HTTP_USER_AGENT and SERVER_SOFTWARE.
     * @var array
     */
    protected $httpHeaders = array();

    /**
     * CloudFront headers. E.g. CloudFront-Is-Desktop-Viewer, CloudFront-Is-Mobile-Viewer & CloudFront-Is-Tablet-Viewer.
     * @var array
     */
    protected $cloudfrontHeaders = array();

    /**
     * The matching Regex.
     * This is good for debug.
     * @var string
     */
    protected $matchingRegex = null;

    /**
     * The matches extracted from the regex expression.
     * This is good for debug.
     * @var string
     */
    protected $matchesArray = null;

    /**
     * The detection type, using self::DETECTION_TYPE_MOBILE or self::DETECTION_TYPE_EXTENDED.
     *
     * @deprecated since version 2.6.9
     *
     * @var string
     */
    protected $detectionType = self::DETECTION_TYPE_MOBILE;

    /**
     * HTTP headers that trigger the 'isMobile' detection
     * to be true.
     *
     * @var array
     */
    protected static $mobileHeaders = array(

            'HTTP_ACCEPT'                  => array('matches' => array(
                                                                        // Opera Mini; @reference: http://dev.opera.com/articles/view/opera-binary-markup-language/
                                                                        'application/x-obml2d',
                                                                        // BlackBerry devices.
                                                                        'application/vnd.rim.html',
                                                                        'text/vnd.wap.wml',
                                                                        'application/vnd.wap.xhtml+xml'
                                            )),
            'HTTP_X_WAP_PROFILE'           => null,
            'HTTP_X_WAP_CLIENTID'          => null,
            'HTTP_WAP_CONNECTION'          => null,
            'HTTP_PROFILE'                 => null,
            // Reported by Opera on Nokia devices (eg. C3).
            'HTTP_X_OPERAMINI_PHONE_UA'    => null,
            'HTTP_X_NOKIA_GATEWAY_ID'      => null,
            'HTTP_X_ORANGE_ID'             => null,
            'HTTP_X_VODAFONE_3GPDPCONTEXT' => null,
            'HTTP_X_HUAWEI_USERID'         => null,
            // Reported by Windows Smartphones.
            'HTTP_UA_OS'                   => null,
            // Reported by Verizon, Vodafone proxy system.
            'HTTP_X_MOBILE_GATEWAY'        => null,
            // Seen this on HTC Sensation. SensationXE_Beats_Z715e.
            'HTTP_X_ATT_DEVICEID'          => null,
            // Seen this on a HTC.
            'HTTP_UA_CPU'                  => array('matches' => array('ARM')),
    );

    /**
     * List of mobile devices (phones).
     *
     * @var array
     */
    protected static $phoneDevices = array(
        'iPhone'        => '\biPhone\b|\biPod\b', // |\biTunes
        'BlackBerry'    => 'BlackBerry|\bBB10\b|rim[0-9]+',
        'HTC'           => 'HTC|HTC.*(Sensation|Evo|Vision|Explorer|6800|8100|8900|A7272|S510e|C110e|Legend|Desire|T8282)|APX515CKT|Qtek9090|APA9292KT|HD_mini|Sensation.*Z710e|PG86100|Z715e|Desire.*(A8181|HD)|ADR6200|ADR6400L|ADR6425|001HT|Inspire 4G|Android.*\bEVO\b|T-Mobile G1|Z520m',
        'Nexus'         => 'Nexus One|Nexus S|Galaxy.*Nexus|Android.*Nexus.*Mobile|Nexus 4|Nexus 5|Nexus 6',
        // @todo: Is 'Dell Streak' a tablet or a phone? ;)
        'Dell'          => 'Dell.*Streak|Dell.*Aero|Dell.*Venue|DELL.*Venue Pro|Dell Flash|Dell Smoke|Dell Mini 3iX|XCD28|XCD35|\b001DL\b|\b101DL\b|\bGS01\b',
        'Motorola'      => 'Motorola|DROIDX|DROID BIONIC|\bDroid\b.*Build|Android.*Xoom|HRI39|MOT-|A1260|A1680|A555|A853|A855|A953|A955|A956|Motorola.*ELECTRIFY|Motorola.*i1|i867|i940|MB200|MB300|MB501|MB502|MB508|MB511|MB520|MB525|MB526|MB611|MB612|MB632|MB810|MB855|MB860|MB861|MB865|MB870|ME501|ME502|ME511|ME525|ME600|ME632|ME722|ME811|ME860|ME863|ME865|MT620|MT710|MT716|MT720|MT810|MT870|MT917|Motorola.*TITANIUM|WX435|WX445|XT300|XT301|XT311|XT316|XT317|XT319|XT320|XT390|XT502|XT530|XT531|XT532|XT535|XT603|XT610|XT611|XT615|XT681|XT701|XT702|XT711|XT720|XT800|XT806|XT860|XT862|XT875|XT882|XT883|XT894|XT901|XT907|XT909|XT910|XT912|XT928|XT926|XT915|XT919|XT925|XT1021|\bMoto E\b',
        'Samsung'       => '\bSamsung\b|SM-G9250|GT-19300|SGH-I337|BGT-S5230|GT-B2100|GT-B2700|GT-B2710|GT-B3210|GT-B3310|GT-B3410|GT-B3730|GT-B3740|GT-B5510|GT-B5512|GT-B5722|GT-B6520|GT-B7300|GT-B7320|GT-B7330|GT-B7350|GT-B7510|GT-B7722|GT-B7800|GT-C3010|GT-C3011|GT-C3060|GT-C3200|GT-C3212|GT-C3212I|GT-C3262|GT-C3222|GT-C3300|GT-C3300K|GT-C3303|GT-C3303K|GT-C3310|GT-C3322|GT-C3330|GT-C3350|GT-C3500|GT-C3510|GT-C3530|GT-C3630|GT-C3780|GT-C5010|GT-C5212|GT-C6620|GT-C6625|GT-C6712|GT-E1050|GT-E1070|GT-E1075|GT-E1080|GT-E1081|GT-E1085|GT-E1087|GT-E1100|GT-E1107|GT-E1110|GT-E1120|GT-E1125|GT-E1130|GT-E1160|GT-E1170|GT-E1175|GT-E1180|GT-E1182|GT-E1200|GT-E1210|GT-E1225|GT-E1230|GT-E1390|GT-E2100|GT-E2120|GT-E2121|GT-E2152|GT-E2220|GT-E2222|GT-E2230|GT-E2232|GT-E2250|GT-E2370|GT-E2550|GT-E2652|GT-E3210|GT-E3213|GT-I5500|GT-I5503|GT-I5700|GT-I5800|GT-I5801|GT-I6410|GT-I6420|GT-I7110|GT-I7410|GT-I7500|GT-I8000|GT-I8150|GT-I8160|GT-I8190|GT-I8320|GT-I8330|GT-I8350|GT-I8530|GT-I8700|GT-I8703|GT-I8910|GT-I9000|GT-I9001|GT-I9003|GT-I9010|GT-I9020|GT-I9023|GT-I9070|GT-I9082|GT-I9100|GT-I9103|GT-I9220|GT-I9250|GT-I9300|GT-I9305|GT-I9500|GT-I9505|GT-M3510|GT-M5650|GT-M7500|GT-M7600|GT-M7603|GT-M8800|GT-M8910|GT-N7000|GT-S3110|GT-S3310|GT-S3350|GT-S3353|GT-S3370|GT-S3650|GT-S3653|GT-S3770|GT-S3850|GT-S5210|GT-S5220|GT-S5229|GT-S5230|GT-S5233|GT-S5250|GT-S5253|GT-S5260|GT-S5263|GT-S5270|GT-S5300|GT-S5330|GT-S5350|GT-S5360|GT-S5363|GT-S5369|GT-S5380|GT-S5380D|GT-S5560|GT-S5570|GT-S5600|GT-S5603|GT-S5610|GT-S5620|GT-S5660|GT-S5670|GT-S5690|GT-S5750|GT-S5780|GT-S5830|GT-S5839|GT-S6102|GT-S6500|GT-S7070|GT-S7200|GT-S7220|GT-S7230|GT-S7233|GT-S7250|GT-S7500|GT-S7530|GT-S7550|GT-S7562|GT-S7710|GT-S8000|GT-S8003|GT-S8500|GT-S8530|GT-S8600|SCH-A310|SCH-A530|SCH-A570|SCH-A610|SCH-A630|SCH-A650|SCH-A790|SCH-A795|SCH-A850|SCH-A870|SCH-A890|SCH-A930|SCH-A950|SCH-A970|SCH-A990|SCH-I100|SCH-I110|SCH-I400|SCH-I405|SCH-I500|SCH-I510|SCH-I515|SCH-I600|SCH-I730|SCH-I760|SCH-I770|SCH-I830|SCH-I910|SCH-I920|SCH-I959|SCH-LC11|SCH-N150|SCH-N300|SCH-R100|SCH-R300|SCH-R351|SCH-R400|SCH-R410|SCH-T300|SCH-U310|SCH-U320|SCH-U350|SCH-U360|SCH-U365|SCH-U370|SCH-U380|SCH-U410|SCH-U430|SCH-U450|SCH-U460|SCH-U470|SCH-U490|SCH-U540|SCH-U550|SCH-U620|SCH-U640|SCH-U650|SCH-U660|SCH-U700|SCH-U740|SCH-U750|SCH-U810|SCH-U820|SCH-U900|SCH-U940|SCH-U960|SCS-26UC|SGH-A107|SGH-A117|SGH-A127|SGH-A137|SGH-A157|SGH-A167|SGH-A177|SGH-A187|SGH-A197|SGH-A227|SGH-A237|SGH-A257|SGH-A437|SGH-A517|SGH-A597|SGH-A637|SGH-A657|SGH-A667|SGH-A687|SGH-A697|SGH-A707|SGH-A717|SGH-A727|SGH-A737|SGH-A747|SGH-A767|SGH-A777|SGH-A797|SGH-A817|SGH-A827|SGH-A837|SGH-A847|SGH-A867|SGH-A877|SGH-A887|SGH-A897|SGH-A927|SGH-B100|SGH-B130|SGH-B200|SGH-B220|SGH-C100|SGH-C110|SGH-C120|SGH-C130|SGH-C140|SGH-C160|SGH-C170|SGH-C180|SGH-C200|SGH-C207|SGH-C210|SGH-C225|SGH-C230|SGH-C417|SGH-C450|SGH-D307|SGH-D347|SGH-D357|SGH-D407|SGH-D415|SGH-D780|SGH-D807|SGH-D980|SGH-E105|SGH-E200|SGH-E315|SGH-E316|SGH-E317|SGH-E335|SGH-E590|SGH-E635|SGH-E715|SGH-E890|SGH-F300|SGH-F480|SGH-I200|SGH-I300|SGH-I320|SGH-I550|SGH-I577|SGH-I600|SGH-I607|SGH-I617|SGH-I627|SGH-I637|SGH-I677|SGH-I700|SGH-I717|SGH-I727|SGH-i747M|SGH-I777|SGH-I780|SGH-I827|SGH-I847|SGH-I857|SGH-I896|SGH-I897|SGH-I900|SGH-I907|SGH-I917|SGH-I927|SGH-I937|SGH-I997|SGH-J150|SGH-J200|SGH-L170|SGH-L700|SGH-M110|SGH-M150|SGH-M200|SGH-N105|SGH-N500|SGH-N600|SGH-N620|SGH-N625|SGH-N700|SGH-N710|SGH-P107|SGH-P207|SGH-P300|SGH-P310|SGH-P520|SGH-P735|SGH-P777|SGH-Q105|SGH-R210|SGH-R220|SGH-R225|SGH-S105|SGH-S307|SGH-T109|SGH-T119|SGH-T139|SGH-T209|SGH-T219|SGH-T229|SGH-T239|SGH-T249|SGH-T259|SGH-T309|SGH-T319|SGH-T329|SGH-T339|SGH-T349|SGH-T359|SGH-T369|SGH-T379|SGH-T409|SGH-T429|SGH-T439|SGH-T459|SGH-T469|SGH-T479|SGH-T499|SGH-T509|SGH-T519|SGH-T539|SGH-T559|SGH-T589|SGH-T609|SGH-T619|SGH-T629|SGH-T639|SGH-T659|SGH-T669|SGH-T679|SGH-T709|SGH-T719|SGH-T729|SGH-T739|SGH-T746|SGH-T749|SGH-T759|SGH-T769|SGH-T809|SGH-T819|SGH-T839|SGH-T919|SGH-T929|SGH-T939|SGH-T959|SGH-T989|SGH-U100|SGH-U200|SGH-U800|SGH-V205|SGH-V206|SGH-X100|SGH-X105|SGH-X120|SGH-X140|SGH-X426|SGH-X427|SGH-X475|SGH-X495|SGH-X497|SGH-X507|SGH-X600|SGH-X610|SGH-X620|SGH-X630|SGH-X700|SGH-X820|SGH-X890|SGH-Z130|SGH-Z150|SGH-Z170|SGH-ZX10|SGH-ZX20|SHW-M110|SPH-A120|SPH-A400|SPH-A420|SPH-A460|SPH-A500|SPH-A560|SPH-A600|SPH-A620|SPH-A660|SPH-A700|SPH-A740|SPH-A760|SPH-A790|SPH-A800|SPH-A820|SPH-A840|SPH-A880|SPH-A900|SPH-A940|SPH-A960|SPH-D600|SPH-D700|SPH-D710|SPH-D720|SPH-I300|SPH-I325|SPH-I330|SPH-I350|SPH-I500|SPH-I600|SPH-I700|SPH-L700|SPH-M100|SPH-M220|SPH-M240|SPH-M300|SPH-M305|SPH-M320|SPH-M330|SPH-M350|SPH-M360|SPH-M370|SPH-M380|SPH-M510|SPH-M540|SPH-M550|SPH-M560|SPH-M570|SPH-M580|SPH-M610|SPH-M620|SPH-M630|SPH-M800|SPH-M810|SPH-M850|SPH-M900|SPH-M910|SPH-M920|SPH-M930|SPH-N100|SPH-N200|SPH-N240|SPH-N300|SPH-N400|SPH-Z400|SWC-E100|SCH-i909|GT-N7100|GT-N7105|SCH-I535|SM-N900A|SGH-I317|SGH-T999L|GT-S5360B|GT-I8262|GT-S6802|GT-S6312|GT-S6310|GT-S5312|GT-S5310|GT-I9105|GT-I8510|GT-S6790N|SM-G7105|SM-N9005|GT-S5301|GT-I9295|GT-I9195|SM-C101|GT-S7392|GT-S7560|GT-B7610|GT-I5510|GT-S7582|GT-S7530E|GT-I8750|SM-G9006V|SM-G9008V|SM-G9009D|SM-G900A|SM-G900D|SM-G900F|SM-G900H|SM-G900I|SM-G900J|SM-G900K|SM-G900L|SM-G900M|SM-G900P|SM-G900R4|SM-G900S|SM-G900T|SM-G900V|SM-G900W8|SHV-E160K|SCH-P709|SCH-P729|SM-T2558|GT-I9205|SM-G9350|SM-J120F|SM-G920F|SM-G920V|SM-G930F|SM-N910C',
        'LG'            => '\bLG\b;|LG[- ]?(C800|C900|E400|E610|E900|E-900|F160|F180K|F180L|F180S|730|855|L160|LS740|LS840|LS970|LU6200|MS690|MS695|MS770|MS840|MS870|MS910|P500|P700|P705|VM696|AS680|AS695|AX840|C729|E970|GS505|272|C395|E739BK|E960|L55C|L75C|LS696|LS860|P769BK|P350|P500|P509|P870|UN272|US730|VS840|VS950|LN272|LN510|LS670|LS855|LW690|MN270|MN510|P509|P769|P930|UN200|UN270|UN510|UN610|US670|US740|US760|UX265|UX840|VN271|VN530|VS660|VS700|VS740|VS750|VS910|VS920|VS930|VX9200|VX11000|AX840A|LW770|P506|P925|P999|E612|D955|D802|MS323)',
        'Sony'          => 'SonyST|SonyLT|SonyEricsson|SonyEricssonLT15iv|LT18i|E10i|LT28h|LT26w|SonyEricssonMT27i|C5303|C6902|C6903|C6906|C6943|D2533',
        'Asus'          => 'Asus.*Galaxy|PadFone.*Mobile',
        'NokiaLumia'    => 'Lumia [0-9]{3,4}',
        // http://www.micromaxinfo.com/mobiles/smartphones
        // Added because the codes might conflict with Acer Tablets.
        'Micromax'      => 'Micromax.*\b(A210|A92|A88|A72|A111|A110Q|A115|A116|A110|A90S|A26|A51|A35|A54|A25|A27|A89|A68|A65|A57|A90)\b',
        // @todo Complete the regex.
        'Palm'          => 'PalmSource|Palm', // avantgo|blazer|elaine|hiptop|plucker|xiino ;
        'Vertu'         => 'Vertu|Vertu.*Ltd|Vertu.*Ascent|Vertu.*Ayxta|Vertu.*Constellation(F|Quest)?|Vertu.*Monika|Vertu.*Signature', // Just for fun ;)
        // http://www.pantech.co.kr/en/prod/prodList.do?gbrand=VEGA (PANTECH)
        // Most of the VEGA devices are legacy. PANTECH seem to be newer devices based on Android.
        'Pantech'       => 'PANTECH|IM-A850S|IM-A840S|IM-A830L|IM-A830K|IM-A830S|IM-A820L|IM-A810K|IM-A810S|IM-A800S|IM-T100K|IM-A725L|IM-A780L|IM-A775C|IM-A770K|IM-A760S|IM-A750K|IM-A740S|IM-A730S|IM-A720L|IM-A710K|IM-A690L|IM-A690S|IM-A650S|IM-A630K|IM-A600S|VEGA PTL21|PT003|P8010|ADR910L|P6030|P6020|P9070|P4100|P9060|P5000|CDM8992|TXT8045|ADR8995|IS11PT|P2030|P6010|P8000|PT002|IS06|CDM8999|P9050|PT001|TXT8040|P2020|P9020|P2000|P7040|P7000|C790',
        // http://www.fly-phone.com/devices/smartphones/ ; Included only smartphones.
        'Fly'           => 'IQ230|IQ444|IQ450|IQ440|IQ442|IQ441|IQ245|IQ256|IQ236|IQ255|IQ235|IQ245|IQ275|IQ240|IQ285|IQ280|IQ270|IQ260|IQ250',
        // http://fr.wikomobile.com
        'Wiko'          => 'KITE 4G|HIGHWAY|GETAWAY|STAIRWAY|DARKSIDE|DARKFULL|DARKNIGHT|DARKMOON|SLIDE|WAX 4G|RAINBOW|BLOOM|SUNSET|GOA(?!nna)|LENNY|BARRY|IGGY|OZZY|CINK FIVE|CINK PEAX|CINK PEAX 2|CINK SLIM|CINK SLIM 2|CINK +|CINK KING|CINK PEAX|CINK SLIM|SUBLIM',
        'iMobile'        => 'i-mobile (IQ|i-STYLE|idea|ZAA|Hitz)',
        // Added simvalley mobile just for fun. They have some interesting devices.
        // http://www.simvalley.fr/telephonie---gps-_22_telephonie-mobile_telephones_.html
        'SimValley'     => '\b(SP-80|XT-930|SX-340|XT-930|SX-310|SP-360|SP60|SPT-800|SP-120|SPT-800|SP-140|SPX-5|SPX-8|SP-100|SPX-8|SPX-12)\b',
         // Wolfgang - a brand that is sold by Aldi supermarkets.
         // http://www.wolfgangmobile.com/
        'Wolfgang'      => 'AT-B24D|AT-AS50HD|AT-AS40W|AT-AS55HD|AT-AS45q2|AT-B26D|AT-AS50Q',
        'Alcatel'       => 'Alcatel',
        'Nintendo' => 'Nintendo 3DS',
        // http://en.wikipedia.org/wiki/Amoi
        'Amoi'          => 'Amoi',
        // http://en.wikipedia.org/wiki/INQ
        'INQ'           => 'INQ',
        // @Tapatalk is a mobile app; http://support.tapatalk.com/threads/smf-2-0-2-os-and-browser-detection-plugin-and-tapatalk.15565/#post-79039
        'GenericPhone'  => 'Tapatalk|PDA;|SAGEM|\bmmp\b|pocket|\bpsp\b|symbian|Smartphone|smartfon|treo|up.browser|up.link|vodafone|\bwap\b|nokia|Series40|Series60|S60|SonyEricsson|N900|MAUI.*WAP.*Browser',
    );

    /**
     * List of tablet devices.
     *
     * @var array
     */
    protected static $tabletDevices = array(
        // @todo: check for mobile friendly emails topic.
        'iPad'              => 'iPad|iPad.*Mobile',
        // Removed |^.*Android.*Nexus(?!(?:Mobile).)*$
        // @see #442
        'NexusTablet'       => 'Android.*Nexus[\s]+(7|9|10)',
        'SamsungTablet'     => 'SAMSUNG.*Tablet|Galaxy.*Tab|SC-01C|GT-P1000|GT-P1003|GT-P1010|GT-P3105|GT-P6210|GT-P6800|GT-P6810|GT-P7100|GT-P7300|GT-P7310|GT-P7500|GT-P7510|SCH-I800|SCH-I815|SCH-I905|SGH-I957|SGH-I987|SGH-T849|SGH-T859|SGH-T869|SPH-P100|GT-P3100|GT-P3108|GT-P3110|GT-P5100|GT-P5110|GT-P6200|GT-P7320|GT-P7511|GT-N8000|GT-P8510|SGH-I497|SPH-P500|SGH-T779|SCH-I705|SCH-I915|GT-N8013|GT-P3113|GT-P5113|GT-P8110|GT-N8010|GT-N8005|GT-N8020|GT-P1013|GT-P6201|GT-P7501|GT-N5100|GT-N5105|GT-N5110|SHV-E140K|SHV-E140L|SHV-E140S|SHV-E150S|SHV-E230K|SHV-E230L|SHV-E230S|SHW-M180K|SHW-M180L|SHW-M180S|SHW-M180W|SHW-M300W|SHW-M305W|SHW-M380K|SHW-M380S|SHW-M380W|SHW-M430W|SHW-M480K|SHW-M480S|SHW-M480W|SHW-M485W|SHW-M486W|SHW-M500W|GT-I9228|SCH-P739|SCH-I925|GT-I9200|GT-P5200|GT-P5210|GT-P5210X|SM-T311|SM-T310|SM-T310X|SM-T210|SM-T210R|SM-T211|SM-P600|SM-P601|SM-P605|SM-P900|SM-P901|SM-T217|SM-T217A|SM-T217S|SM-P6000|SM-T3100|SGH-I467|XE500|SM-T110|GT-P5220|GT-I9200X|GT-N5110X|GT-N5120|SM-P905|SM-T111|SM-T2105|SM-T315|SM-T320|SM-T320X|SM-T321|SM-T520|SM-T525|SM-T530NU|SM-T230NU|SM-T330NU|SM-T900|XE500T1C|SM-P605V|SM-P905V|SM-T337V|SM-T537V|SM-T707V|SM-T807V|SM-P600X|SM-P900X|SM-T210X|SM-T230|SM-T230X|SM-T325|GT-P7503|SM-T531|SM-T330|SM-T530|SM-T705|SM-T705C|SM-T535|SM-T331|SM-T800|SM-T700|SM-T537|SM-T807|SM-P907A|SM-T337A|SM-T537A|SM-T707A|SM-T807A|SM-T237|SM-T807P|SM-P607T|SM-T217T|SM-T337T|SM-T807T|SM-T116NQ|SM-T116BU|SM-P550|SM-T350|SM-T550|SM-T9000|SM-P9000|SM-T705Y|SM-T805|GT-P3113|SM-T710|SM-T810|SM-T815|SM-T360|SM-T533|SM-T113|SM-T335|SM-T715|SM-T560|SM-T670|SM-T677|SM-T377|SM-T567|SM-T357T|SM-T555|SM-T561|SM-T713|SM-T719|SM-T813|SM-T819|SM-T580|SM-T355Y|SM-T280|SM-T817A|SM-T820|SM-W700|SM-P580|SM-T587|SM-P350|SM-P555M|SM-P355M|SM-T113NU|SM-T815Y', // SCH-P709|SCH-P729|SM-T2558|GT-I9205 - Samsung Mega - treat them like a regular phone.
        // http://docs.aws.amazon.com/silk/latest/developerguide/user-agent.html
        'Kindle'            => 'Kindle|Silk.*Accelerated|Android.*\b(KFOT|KFTT|KFJWI|KFJWA|KFOTE|KFSOWI|KFTHWI|KFTHWA|KFAPWI|KFAPWA|WFJWAE|KFSAWA|KFSAWI|KFASWI|KFARWI|KFFOWI|KFGIWI|KFMEWI)\b|Android.*Silk/[0-9.]+ like Chrome/[0-9.]+ (?!Mobile)',
        // Only the Surface tablets with Windows RT are considered mobile.
        // http://msdn.microsoft.com/en-us/library/ie/hh920767(v=vs.85).aspx
        'SurfaceTablet'     => 'Windows NT [0-9.]+; ARM;.*(Tablet|ARMBJS)',
        // http://shopping1.hp.com/is-bin/INTERSHOP.enfinity/WFS/WW-USSMBPublicStore-Site/en_US/-/USD/ViewStandardCatalog-Browse?CatalogCategoryID=JfIQ7EN5lqMAAAEyDcJUDwMT
        'HPTablet'          => 'HP Slate (7|8|10)|HP ElitePad 900|hp-tablet|EliteBook.*Touch|HP 8|Slate 21|HP SlateBook 10',
        // Watch out for PadFone, see #132.
        // http://www.asus.com/de/Tablets_Mobile/Memo_Pad_Products/
        'AsusTablet'        => '^.*PadFone((?!Mobile).)*$|Transformer|TF101|TF101G|TF300T|TF300TG|TF300TL|TF700T|TF700KL|TF701T|TF810C|ME171|ME301T|ME302C|ME371MG|ME370T|ME372MG|ME172V|ME173X|ME400C|Slider SL101|\bK00F\b|\bK00C\b|\bK00E\b|\bK00L\b|TX201LA|ME176C|ME102A|\bM80TA\b|ME372CL|ME560CG|ME372CG|ME302KL| K010 | K011 | K017 | K01E |ME572C|ME103K|ME170C|ME171C|\bME70C\b|ME581C|ME581CL|ME8510C|ME181C|P01Y|PO1MA|P01Z|\bP027\b',
        'BlackBerryTablet'  => 'PlayBook|RIM Tablet',
        'HTCtablet'         => 'HTC_Flyer_P512|HTC Flyer|HTC Jetstream|HTC-P715a|HTC EVO View 4G|PG41200|PG09410',
        'MotorolaTablet'    => 'xoom|sholest|MZ615|MZ605|MZ505|MZ601|MZ602|MZ603|MZ604|MZ606|MZ607|MZ608|MZ609|MZ615|MZ616|MZ617',
        'NookTablet'        => 'Android.*Nook|NookColor|nook browser|BNRV200|BNRV200A|BNTV250|BNTV250A|BNTV400|BNTV600|LogicPD Zoom2',
        // http://www.acer.ro/ac/ro/RO/content/drivers
        // http://www.packardbell.co.uk/pb/en/GB/content/download (Packard Bell is part of Acer)
        // http://us.acer.com/ac/en/US/content/group/tablets
        // http://www.acer.de/ac/de/DE/content/models/tablets/
        // Can conflict with Micromax and Motorola phones codes.
        'AcerTablet'        => 'Android.*; \b(A100|A101|A110|A200|A210|A211|A500|A501|A510|A511|A700|A701|W500|W500P|W501|W501P|W510|W511|W700|G100|G100W|B1-A71|B1-710|B1-711|A1-810|A1-811|A1-830)\b|W3-810|\bA3-A10\b|\bA3-A11\b|\bA3-A20\b|\bA3-A30',
        // http://eu.computers.toshiba-europe.com/innovation/family/Tablets/1098744/banner_id/tablet_footerlink/
        // http://us.toshiba.com/tablets/tablet-finder
        // http://www.toshiba.co.jp/regza/tablet/
        'ToshibaTablet'     => 'Android.*(AT100|AT105|AT200|AT205|AT270|AT275|AT300|AT305|AT1S5|AT500|AT570|AT700|AT830)|TOSHIBA.*FOLIO',
        // http://www.nttdocomo.co.jp/english/service/developer/smart_phone/technical_info/spec/index.html
        // http://www.lg.com/us/tablets
        'LGTablet'          => '\bL-06C|LG-V909|LG-V900|LG-V700|LG-V510|LG-V500|LG-V410|LG-V400|LG-VK810\b',
        'FujitsuTablet'     => 'Android.*\b(F-01D|F-02F|F-05E|F-10D|M532|Q572)\b',
        // Prestigio Tablets http://www.prestigio.com/support
        'PrestigioTablet'   => 'PMP3170B|PMP3270B|PMP3470B|PMP7170B|PMP3370B|PMP3570C|PMP5870C|PMP3670B|PMP5570C|PMP5770D|PMP3970B|PMP3870C|PMP5580C|PMP5880D|PMP5780D|PMP5588C|PMP7280C|PMP7280C3G|PMP7280|PMP7880D|PMP5597D|PMP5597|PMP7100D|PER3464|PER3274|PER3574|PER3884|PER5274|PER5474|PMP5097CPRO|PMP5097|PMP7380D|PMP5297C|PMP5297C_QUAD|PMP812E|PMP812E3G|PMP812F|PMP810E|PMP880TD|PMT3017|PMT3037|PMT3047|PMT3057|PMT7008|PMT5887|PMT5001|PMT5002',
        // http://support.lenovo.com/en_GB/downloads/default.page?#
        'LenovoTablet'      => 'Lenovo TAB|Idea(Tab|Pad)( A1|A10| K1|)|ThinkPad([ ]+)?Tablet|YT3-850M|YT3-X90L|YT3-X90F|YT3-X90X|Lenovo.*(S2109|S2110|S5000|S6000|K3011|A3000|A3500|A1000|A2107|A2109|A1107|A5500|A7600|B6000|B8000|B8080)(-|)(FL|F|HV|H|)',
        // http://www.dell.com/support/home/us/en/04/Products/tab_mob/tablets
        'DellTablet'        => 'Venue 11|Venue 8|Venue 7|Dell Streak 10|Dell Streak 7',
        // http://www.yarvik.com/en/matrix/tablets/
        'YarvikTablet'      => 'Android.*\b(TAB210|TAB211|TAB224|TAB250|TAB260|TAB264|TAB310|TAB360|TAB364|TAB410|TAB411|TAB420|TAB424|TAB450|TAB460|TAB461|TAB464|TAB465|TAB467|TAB468|TAB07-100|TAB07-101|TAB07-150|TAB07-151|TAB07-152|TAB07-200|TAB07-201-3G|TAB07-210|TAB07-211|TAB07-212|TAB07-214|TAB07-220|TAB07-400|TAB07-485|TAB08-150|TAB08-200|TAB08-201-3G|TAB08-201-30|TAB09-100|TAB09-211|TAB09-410|TAB10-150|TAB10-201|TAB10-211|TAB10-400|TAB10-410|TAB13-201|TAB274EUK|TAB275EUK|TAB374EUK|TAB462EUK|TAB474EUK|TAB9-200)\b',
        'MedionTablet'      => 'Android.*\bOYO\b|LIFE.*(P9212|P9514|P9516|S9512)|LIFETAB',
        'ArnovaTablet'      => '97G4|AN10G2|AN7bG3|AN7fG3|AN8G3|AN8cG3|AN7G3|AN9G3|AN7dG3|AN7dG3ST|AN7dG3ChildPad|AN10bG3|AN10bG3DT|AN9G2',
        // http://www.intenso.de/kategorie_en.php?kategorie=33
        // @todo: http://www.nbhkdz.com/read/b8e64202f92a2df129126bff.html - investigate
        'IntensoTablet'     => 'INM8002KP|INM1010FP|INM805ND|Intenso Tab|TAB1004',
        // IRU.ru Tablets http://www.iru.ru/catalog/soho/planetable/
        'IRUTablet'         => 'M702pro',
        'MegafonTablet'     => 'MegaFon V9|\bZTE V9\b|Android.*\bMT7A\b',
        // http://www.e-boda.ro/tablete-pc.html
        'EbodaTablet'       => 'E-Boda (Supreme|Impresspeed|Izzycomm|Essential)',
        // http://www.allview.ro/produse/droseries/lista-tablete-pc/
        'AllViewTablet'           => 'Allview.*(Viva|Alldro|City|Speed|All TV|Frenzy|Quasar|Shine|TX1|AX1|AX2)',
        // http://wiki.archosfans.com/index.php?title=Main_Page
        // @note Rewrite the regex format after we add more UAs.
        'ArchosTablet'      => '\b(101G9|80G9|A101IT)\b|Qilive 97R|Archos5|\bARCHOS (70|79|80|90|97|101|FAMILYPAD|)(b|c|)(G10| Cobalt| TITANIUM(HD|)| Xenon| Neon|XSK| 2| XS 2| PLATINUM| CARBON|GAMEPAD)\b',
        // http://www.ainol.com/plugin.php?identifier=ainol&module=product
        'AinolTablet'       => 'NOVO7|NOVO8|NOVO10|Novo7Aurora|Novo7Basic|NOVO7PALADIN|novo9-Spark',
        'NokiaLumiaTablet'  => 'Lumia 2520',
        // @todo: inspect http://esupport.sony.com/US/p/select-system.pl?DIRECTOR=DRIVER
        // Readers http://www.atsuhiro-me.net/ebook/sony-reader/sony-reader-web-browser
        // http://www.sony.jp/support/tablet/
        'SonyTablet'        => 'Sony.*Tablet|Xperia Tablet|Sony Tablet S|SO-03E|SGPT12|SGPT13|SGPT114|SGPT121|SGPT122|SGPT123|SGPT111|SGPT112|SGPT113|SGPT131|SGPT132|SGPT133|SGPT211|SGPT212|SGPT213|SGP311|SGP312|SGP321|EBRD1101|EBRD1102|EBRD1201|SGP351|SGP341|SGP511|SGP512|SGP521|SGP541|SGP551|SGP621|SGP612|SOT31',
        // http://www.support.philips.com/support/catalog/worldproducts.jsp?userLanguage=en&userCountry=cn&categoryid=3G_LTE_TABLET_SU_CN_CARE&title=3G%20tablets%20/%20LTE%20range&_dyncharset=UTF-8
        'PhilipsTablet'     => '\b(PI2010|PI3000|PI3100|PI3105|PI3110|PI3205|PI3210|PI3900|PI4010|PI7000|PI7100)\b',
        // db + http://www.cube-tablet.com/buy-products.html
        'CubeTablet'        => 'Android.*(K8GT|U9GT|U10GT|U16GT|U17GT|U18GT|U19GT|U20GT|U23GT|U30GT)|CUBE U8GT',
        // http://www.cobyusa.com/?p=pcat&pcat_id=3001
        'CobyTablet'        => 'MID1042|MID1045|MID1125|MID1126|MID7012|MID7014|MID7015|MID7034|MID7035|MID7036|MID7042|MID7048|MID7127|MID8042|MID8048|MID8127|MID9042|MID9740|MID9742|MID7022|MID7010',
        // http://www.match.net.cn/products.asp
        'MIDTablet'         => 'M9701|M9000|M9100|M806|M1052|M806|T703|MID701|MID713|MID710|MID727|MID760|MID830|MID728|MID933|MID125|MID810|MID732|MID120|MID930|MID800|MID731|MID900|MID100|MID820|MID735|MID980|MID130|MID833|MID737|MID960|MID135|MID860|MID736|MID140|MID930|MID835|MID733|MID4X10',
        // http://www.msi.com/support
        // @todo Research the Windows Tablets.
        'MSITablet' => 'MSI \b(Primo 73K|Primo 73L|Primo 81L|Primo 77|Primo 93|Primo 75|Primo 76|Primo 73|Primo 81|Primo 91|Primo 90|Enjoy 71|Enjoy 7|Enjoy 10)\b',
        // @todo http://www.kyoceramobile.com/support/drivers/
    //    'KyoceraTablet' => null,
        // @todo http://intexuae.com/index.php/category/mobile-devices/tablets-products/
    //    'IntextTablet' => null,
        // http://pdadb.net/index.php?m=pdalist&list=SMiT (NoName Chinese Tablets)
        // http://www.imp3.net/14/show.php?itemid=20454
        'SMiTTablet'        => 'Android.*(\bMID\b|MID-560|MTV-T1200|MTV-PND531|MTV-P1101|MTV-PND530)',
        // http://www.rock-chips.com/index.php?do=prod&pid=2
        'RockChipTablet'    => 'Android.*(RK2818|RK2808A|RK2918|RK3066)|RK2738|RK2808A',
        // http://www.fly-phone.com/devices/tablets/ ; http://www.fly-phone.com/service/
        'FlyTablet'         => 'IQ310|Fly Vision',
        // http://www.bqreaders.com/gb/tablets-prices-sale.html
        'bqTablet'          => 'Android.*(bq)?.*(Elcano|Curie|Edison|Maxwell|Kepler|Pascal|Tesla|Hypatia|Platon|Newton|Livingstone|Cervantes|Avant|Aquaris [E|M]10)|Maxwell.*Lite|Maxwell.*Plus',
        // http://www.huaweidevice.com/worldwide/productFamily.do?method=index&directoryId=5011&treeId=3290
        // http://www.huaweidevice.com/worldwide/downloadCenter.do?method=index&directoryId=3372&treeId=0&tb=1&type=software (including legacy tablets)
        'HuaweiTablet'      => 'MediaPad|MediaPad 7 Youth|IDEOS S7|S7-201c|S7-202u|S7-101|S7-103|S7-104|S7-105|S7-106|S7-201|S7-Slim',
        // Nec or Medias Tab
        'NecTablet'         => '\bN-06D|\bN-08D',
        // Pantech Tablets: http://www.pantechusa.com/phones/
        'PantechTablet'     => 'Pantech.*P4100',
        // Broncho Tablets: http://www.broncho.cn/ (hard to find)
        'BronchoTablet'     => 'Broncho.*(N701|N708|N802|a710)',
        // http://versusuk.com/support.html
        'VersusTablet'      => 'TOUCHPAD.*[78910]|\bTOUCHTAB\b',
        // http://www.zync.in/index.php/our-products/tablet-phablets
        'ZyncTablet'        => 'z1000|Z99 2G|z99|z930|z999|z990|z909|Z919|z900',
        // http://www.positivoinformatica.com.br/www/pessoal/tablet-ypy/
        'PositivoTablet'    => 'TB07STA|TB10STA|TB07FTA|TB10FTA',
        // https://www.nabitablet.com/
        'NabiTablet'        => 'Android.*\bNabi',
        'KoboTablet'        => 'Kobo Touch|\bK080\b|\bVox\b Build|\bArc\b Build',
        // French Danew Tablets http://www.danew.com/produits-tablette.php
        'DanewTablet'       => 'DSlide.*\b(700|701R|702|703R|704|802|970|971|972|973|974|1010|1012)\b',
        // Texet Tablets and Readers http://www.texet.ru/tablet/
        'TexetTablet'       => 'NaviPad|TB-772A|TM-7045|TM-7055|TM-9750|TM-7016|TM-7024|TM-7026|TM-7041|TM-7043|TM-7047|TM-8041|TM-9741|TM-9747|TM-9748|TM-9751|TM-7022|TM-7021|TM-7020|TM-7011|TM-7010|TM-7023|TM-7025|TM-7037W|TM-7038W|TM-7027W|TM-9720|TM-9725|TM-9737W|TM-1020|TM-9738W|TM-9740|TM-9743W|TB-807A|TB-771A|TB-727A|TB-725A|TB-719A|TB-823A|TB-805A|TB-723A|TB-715A|TB-707A|TB-705A|TB-709A|TB-711A|TB-890HD|TB-880HD|TB-790HD|TB-780HD|TB-770HD|TB-721HD|TB-710HD|TB-434HD|TB-860HD|TB-840HD|TB-760HD|TB-750HD|TB-740HD|TB-730HD|TB-722HD|TB-720HD|TB-700HD|TB-500HD|TB-470HD|TB-431HD|TB-430HD|TB-506|TB-504|TB-446|TB-436|TB-416|TB-146SE|TB-126SE',
        // Avoid detecting 'PLAYSTATION 3' as mobile.
        'PlaystationTablet' => 'Playstation.*(Portable|Vita)',
        // http://www.trekstor.de/surftabs.html
        'TrekstorTablet'    => 'ST10416-1|VT10416-1|ST70408-1|ST702xx-1|ST702xx-2|ST80208|ST97216|ST70104-2|VT10416-2|ST10216-2A|SurfTab',
        // http://www.pyleaudio.com/Products.aspx?%2fproducts%2fPersonal-Electronics%2fTablets
        'PyleAudioTablet'   => '\b(PTBL10CEU|PTBL10C|PTBL72BC|PTBL72BCEU|PTBL7CEU|PTBL7C|PTBL92BC|PTBL92BCEU|PTBL9CEU|PTBL9CUK|PTBL9C)\b',
        // http://www.advandigital.com/index.php?link=content-product&jns=JP001
        // because of the short codenames we have to include whitespaces to reduce the possible conflicts.
        'AdvanTablet'       => 'Android.* \b(E3A|T3X|T5C|T5B|T3E|T3C|T3B|T1J|T1F|T2A|T1H|T1i|E1C|T1-E|T5-A|T4|E1-B|T2Ci|T1-B|T1-D|O1-A|E1-A|T1-A|T3A|T4i)\b ',
        // http://www.danytech.com/category/tablet-pc
        'DanyTechTablet' => 'Genius Tab G3|Genius Tab S2|Genius Tab Q3|Genius Tab G4|Genius Tab Q4|Genius Tab G-II|Genius TAB GII|Genius TAB GIII|Genius Tab S1',
        // http://www.galapad.net/product.html
        'GalapadTablet'     => 'Android.*\bG1\b',
        // http://www.micromaxinfo.com/tablet/funbook
        'MicromaxTablet'    => 'Funbook|Micromax.*\b(P250|P560|P360|P362|P600|P300|P350|P500|P275)\b',
        // http://www.karbonnmobiles.com/products_tablet.php
        'KarbonnTablet'     => 'Android.*\b(A39|A37|A34|ST8|ST10|ST7|Smart Tab3|Smart Tab2)\b',
        // http://www.myallfine.com/Products.asp
        'AllFineTablet'     => 'Fine7 Genius|Fine7 Shine|Fine7 Air|Fine8 Style|Fine9 More|Fine10 Joy|Fine11 Wide',
        // http://www.proscanvideo.com/products-search.asp?itemClass=TABLET&itemnmbr=
        'PROSCANTablet'     => '\b(PEM63|PLT1023G|PLT1041|PLT1044|PLT1044G|PLT1091|PLT4311|PLT4311PL|PLT4315|PLT7030|PLT7033|PLT7033D|PLT7035|PLT7035D|PLT7044K|PLT7045K|PLT7045KB|PLT7071KG|PLT7072|PLT7223G|PLT7225G|PLT7777G|PLT7810K|PLT7849G|PLT7851G|PLT7852G|PLT8015|PLT8031|PLT8034|PLT8036|PLT8080K|PLT8082|PLT8088|PLT8223G|PLT8234G|PLT8235G|PLT8816K|PLT9011|PLT9045K|PLT9233G|PLT9735|PLT9760G|PLT9770G)\b',
        // http://www.yonesnav.com/products/products.php
        'YONESTablet' => 'BQ1078|BC1003|BC1077|RK9702|BC9730|BC9001|IT9001|BC7008|BC7010|BC708|BC728|BC7012|BC7030|BC7027|BC7026',
        // http://www.cjshowroom.com/eproducts.aspx?classcode=004001001
        // China manufacturer makes tablets for different small brands (eg. http://www.zeepad.net/index.html)
        'ChangJiaTablet'    => 'TPC7102|TPC7103|TPC7105|TPC7106|TPC7107|TPC7201|TPC7203|TPC7205|TPC7210|TPC7708|TPC7709|TPC7712|TPC7110|TPC8101|TPC8103|TPC8105|TPC8106|TPC8203|TPC8205|TPC8503|TPC9106|TPC9701|TPC97101|TPC97103|TPC97105|TPC97106|TPC97111|TPC97113|TPC97203|TPC97603|TPC97809|TPC97205|TPC10101|TPC10103|TPC10106|TPC10111|TPC10203|TPC10205|TPC10503',
        // http://www.gloryunion.cn/products.asp
        // http://www.allwinnertech.com/en/apply/mobile.html
        // http://www.ptcl.com.pk/pd_content.php?pd_id=284 (EVOTAB)
        // @todo: Softwiner tablets?
        // aka. Cute or Cool tablets. Not sure yet, must research to avoid collisions.
        'GUTablet'          => 'TX-A1301|TX-M9002|Q702|kf026', // A12R|D75A|D77|D79|R83|A95|A106C|R15|A75|A76|D71|D72|R71|R73|R77|D82|R85|D92|A97|D92|R91|A10F|A77F|W71F|A78F|W78F|W81F|A97F|W91F|W97F|R16G|C72|C73E|K72|K73|R96G
        // http://www.pointofview-online.com/showroom.php?shop_mode=product_listing&category_id=118
        'PointOfViewTablet' => 'TAB-P506|TAB-navi-7-3G-M|TAB-P517|TAB-P-527|TAB-P701|TAB-P703|TAB-P721|TAB-P731N|TAB-P741|TAB-P825|TAB-P905|TAB-P925|TAB-PR945|TAB-PL1015|TAB-P1025|TAB-PI1045|TAB-P1325|TAB-PROTAB[0-9]+|TAB-PROTAB25|TAB-PROTAB26|TAB-PROTAB27|TAB-PROTAB26XL|TAB-PROTAB2-IPS9|TAB-PROTAB30-IPS9|TAB-PROTAB25XXL|TAB-PROTAB26-IPS10|TAB-PROTAB30-IPS10',
        // http://www.overmax.pl/pl/katalog-produktow,p8/tablety,c14/
        // @todo: add more tests.
        'OvermaxTablet'     => 'OV-(SteelCore|NewBase|Basecore|Baseone|Exellen|Quattor|EduTab|Solution|ACTION|BasicTab|TeddyTab|MagicTab|Stream|TB-08|TB-09)',
        // http://hclmetablet.com/India/index.php
        'HCLTablet'         => 'HCL.*Tablet|Connect-3G-2.0|Connect-2G-2.0|ME Tablet U1|ME Tablet U2|ME Tablet G1|ME Tablet X1|ME Tablet Y2|ME Tablet Sync',
        // http://www.edigital.hu/Tablet_es_e-book_olvaso/Tablet-c18385.html
        'DPSTablet'         => 'DPS Dream 9|DPS Dual 7',
        // http://www.visture.com/index.asp
        'VistureTablet'     => 'V97 HD|i75 3G|Visture V4( HD)?|Visture V5( HD)?|Visture V10',
        // http://www.mijncresta.nl/tablet
        'CrestaTablet'     => 'CTP(-)?810|CTP(-)?818|CTP(-)?828|CTP(-)?838|CTP(-)?888|CTP(-)?978|CTP(-)?980|CTP(-)?987|CTP(-)?988|CTP(-)?989',
        // MediaTek - http://www.mediatek.com/_en/01_products/02_proSys.php?cata_sn=1&cata1_sn=1&cata2_sn=309
        'MediatekTablet' => '\bMT8125|MT8389|MT8135|MT8377\b',
        // Concorde tab
        'ConcordeTablet' => 'Concorde([ ]+)?Tab|ConCorde ReadMan',
        // GoClever Tablets - http://www.goclever.com/uk/products,c1/tablet,c5/
        'GoCleverTablet' => 'GOCLEVER TAB|A7GOCLEVER|M1042|M7841|M742|R1042BK|R1041|TAB A975|TAB A7842|TAB A741|TAB A741L|TAB M723G|TAB M721|TAB A1021|TAB I921|TAB R721|TAB I720|TAB T76|TAB R70|TAB R76.2|TAB R106|TAB R83.2|TAB M813G|TAB I721|GCTA722|TAB I70|TAB I71|TAB S73|TAB R73|TAB R74|TAB R93|TAB R75|TAB R76.1|TAB A73|TAB A93|TAB A93.2|TAB T72|TAB R83|TAB R974|TAB R973|TAB A101|TAB A103|TAB A104|TAB A104.2|R105BK|M713G|A972BK|TAB A971|TAB R974.2|TAB R104|TAB R83.3|TAB A1042',
        // Modecom Tablets - http://www.modecom.eu/tablets/portal/
        'ModecomTablet' => 'FreeTAB 9000|FreeTAB 7.4|FreeTAB 7004|FreeTAB 7800|FreeTAB 2096|FreeTAB 7.5|FreeTAB 1014|FreeTAB 1001 |FreeTAB 8001|FreeTAB 9706|FreeTAB 9702|FreeTAB 7003|FreeTAB 7002|FreeTAB 1002|FreeTAB 7801|FreeTAB 1331|FreeTAB 1004|FreeTAB 8002|FreeTAB 8014|FreeTAB 9704|FreeTAB 1003',
        // Vonino Tablets - http://www.vonino.eu/tablets
        'VoninoTablet'  => '\b(Argus[ _]?S|Diamond[ _]?79HD|Emerald[ _]?78E|Luna[ _]?70C|Onyx[ _]?S|Onyx[ _]?Z|Orin[ _]?HD|Orin[ _]?S|Otis[ _]?S|SpeedStar[ _]?S|Magnet[ _]?M9|Primus[ _]?94[ _]?3G|Primus[ _]?94HD|Primus[ _]?QS|Android.*\bQ8\b|Sirius[ _]?EVO[ _]?QS|Sirius[ _]?QS|Spirit[ _]?S)\b',
        // ECS Tablets - http://www.ecs.com.tw/ECSWebSite/Product/Product_Tablet_List.aspx?CategoryID=14&MenuID=107&childid=M_107&LanID=0
        'ECSTablet'     => 'V07OT2|TM105A|S10OT1|TR10CS1',
        // Storex Tablets - http://storex.fr/espace_client/support.html
        // @note: no need to add all the tablet codes since they are guided by the first regex.
        'StorexTablet'  => 'eZee[_\']?(Tab|Go)[0-9]+|TabLC7|Looney Tunes Tab',
        // Generic Vodafone tablets.
        'VodafoneTablet' => 'SmartTab([ ]+)?[0-9]+|SmartTabII10|SmartTabII7|VF-1497',
        // French tablets - Essentiel B http://www.boulanger.fr/tablette_tactile_e-book/tablette_tactile_essentiel_b/cl_68908.htm?multiChoiceToDelete=brand&mc_brand=essentielb
        // Aka: http://www.essentielb.fr/
        'EssentielBTablet' => 'Smart[ \']?TAB[ ]+?[0-9]+|Family[ \']?TAB2',
        // Ross & Moor - http://ross-moor.ru/
        'RossMoorTablet' => 'RM-790|RM-997|RMD-878G|RMD-974R|RMT-705A|RMT-701|RME-601|RMT-501|RMT-711',
        // i-mobile http://product.i-mobilephone.com/Mobile_Device
        'iMobileTablet'        => 'i-mobile i-note',
        // http://www.tolino.de/de/vergleichen/
        'TolinoTablet'  => 'tolino tab [0-9.]+|tolino shine',
        // AudioSonic - a Kmart brand
        // http://www.kmart.com.au/webapp/wcs/stores/servlet/Search?langId=-1&storeId=10701&catalogId=10001&categoryId=193001&pageSize=72&currentPage=1&searchCategory=193001%2b4294965664&sortBy=p_MaxPrice%7c1
        'AudioSonicTablet' => '\bC-22Q|T7-QC|T-17B|T-17P\b',
        // AMPE Tablets - http://www.ampe.com.my/product-category/tablets/
        // @todo: add them gradually to avoid conflicts.
        'AMPETablet' => 'Android.* A78 ',
        // Skk Mobile - http://skkmobile.com.ph/product_tablets.php
        'SkkTablet' => 'Android.* (SKYPAD|PHOENIX|CYCLOPS)',
        // Tecno Mobile (only tablet) - http://www.tecno-mobile.com/index.php/product?filterby=smart&list_order=all&page=1
        'TecnoTablet' => 'TECNO P9',
        // JXD (consoles & tablets) - http://jxd.hk/products.asp?selectclassid=009008&clsid=3
        'JXDTablet' => 'Android.* \b(F3000|A3300|JXD5000|JXD3000|JXD2000|JXD300B|JXD300|S5800|S7800|S602b|S5110b|S7300|S5300|S602|S603|S5100|S5110|S601|S7100a|P3000F|P3000s|P101|P200s|P1000m|P200m|P9100|P1000s|S6600b|S908|P1000|P300|S18|S6600|S9100)\b',
        // i-Joy tablets - http://www.i-joy.es/en/cat/products/tablets/
        'iJoyTablet' => 'Tablet (Spirit 7|Essentia|Galatea|Fusion|Onix 7|Landa|Titan|Scooby|Deox|Stella|Themis|Argon|Unique 7|Sygnus|Hexen|Finity 7|Cream|Cream X2|Jade|Neon 7|Neron 7|Kandy|Scape|Saphyr 7|Rebel|Biox|Rebel|Rebel 8GB|Myst|Draco 7|Myst|Tab7-004|Myst|Tadeo Jones|Tablet Boing|Arrow|Draco Dual Cam|Aurix|Mint|Amity|Revolution|Finity 9|Neon 9|T9w|Amity 4GB Dual Cam|Stone 4GB|Stone 8GB|Andromeda|Silken|X2|Andromeda II|Halley|Flame|Saphyr 9,7|Touch 8|Planet|Triton|Unique 10|Hexen 10|Memphis 4GB|Memphis 8GB|Onix 10)',
        // http://www.intracon.eu/tablet
        'FX2Tablet' => 'FX2 PAD7|FX2 PAD10',
        // http://www.xoro.de/produkte/
        // @note: Might be the same brand with 'Simply tablets'
        'XoroTablet'        => 'KidsPAD 701|PAD[ ]?712|PAD[ ]?714|PAD[ ]?716|PAD[ ]?717|PAD[ ]?718|PAD[ ]?720|PAD[ ]?721|PAD[ ]?722|PAD[ ]?790|PAD[ ]?792|PAD[ ]?900|PAD[ ]?9715D|PAD[ ]?9716DR|PAD[ ]?9718DR|PAD[ ]?9719QR|PAD[ ]?9720QR|TelePAD1030|Telepad1032|TelePAD730|TelePAD731|TelePAD732|TelePAD735Q|TelePAD830|TelePAD9730|TelePAD795|MegaPAD 1331|MegaPAD 1851|MegaPAD 2151',
        // http://www1.viewsonic.com/products/computing/tablets/
        'ViewsonicTablet'   => 'ViewPad 10pi|ViewPad 10e|ViewPad 10s|ViewPad E72|ViewPad7|ViewPad E100|ViewPad 7e|ViewSonic VB733|VB100a',
        // http://www.odys.de/web/internet-tablet_en.html
        'OdysTablet'        => 'LOOX|XENO10|ODYS[ -](Space|EVO|Xpress|NOON)|\bXELIO\b|Xelio10Pro|XELIO7PHONETAB|XELIO10EXTREME|XELIOPT2|NEO_QUAD10',
        // http://www.captiva-power.de/products.html#tablets-en
        'CaptivaTablet'     => 'CAPTIVA PAD',
        // IconBIT - http://www.iconbit.com/products/tablets/
        'IconbitTablet' => 'NetTAB|NT-3702|NT-3702S|NT-3702S|NT-3603P|NT-3603P|NT-0704S|NT-0704S|NT-3805C|NT-3805C|NT-0806C|NT-0806C|NT-0909T|NT-0909T|NT-0907S|NT-0907S|NT-0902S|NT-0902S',
        // http://www.teclast.com/topic.php?channelID=70&topicID=140&pid=63
        'TeclastTablet' => 'T98 4G|\bP80\b|\bX90HD\b|X98 Air|X98 Air 3G|\bX89\b|P80 3G|\bX80h\b|P98 Air|\bX89HD\b|P98 3G|\bP90HD\b|P89 3G|X98 3G|\bP70h\b|P79HD 3G|G18d 3G|\bP79HD\b|\bP89s\b|\bA88\b|\bP10HD\b|\bP19HD\b|G18 3G|\bP78HD\b|\bA78\b|\bP75\b|G17s 3G|G17h 3G|\bP85t\b|\bP90\b|\bP11\b|\bP98t\b|\bP98HD\b|\bG18d\b|\bP85s\b|\bP11HD\b|\bP88s\b|\bA80HD\b|\bA80se\b|\bA10h\b|\bP89\b|\bP78s\b|\bG18\b|\bP85\b|\bA70h\b|\bA70\b|\bG17\b|\bP18\b|\bA80s\b|\bA11s\b|\bP88HD\b|\bA80h\b|\bP76s\b|\bP76h\b|\bP98\b|\bA10HD\b|\bP78\b|\bP88\b|\bA11\b|\bA10t\b|\bP76a\b|\bP76t\b|\bP76e\b|\bP85HD\b|\bP85a\b|\bP86\b|\bP75HD\b|\bP76v\b|\bA12\b|\bP75a\b|\bA15\b|\bP76Ti\b|\bP81HD\b|\bA10\b|\bT760VE\b|\bT720HD\b|\bP76\b|\bP73\b|\bP71\b|\bP72\b|\bT720SE\b|\bC520Ti\b|\bT760\b|\bT720VE\b|T720-3GE|T720-WiFi',
        // Onda - http://www.onda-tablet.com/buy-android-onda.html?dir=desc&limit=all&order=price
        'OndaTablet' => '\b(V975i|Vi30|VX530|V701|Vi60|V701s|Vi50|V801s|V719|Vx610w|VX610W|V819i|Vi10|VX580W|Vi10|V711s|V813|V811|V820w|V820|Vi20|V711|VI30W|V712|V891w|V972|V819w|V820w|Vi60|V820w|V711|V813s|V801|V819|V975s|V801|V819|V819|V818|V811|V712|V975m|V101w|V961w|V812|V818|V971|V971s|V919|V989|V116w|V102w|V973|Vi40)\b[\s]+',
        'JaytechTablet'     => 'TPC-PA762',
        'BlaupunktTablet'   => 'Endeavour 800NG|Endeavour 1010',
        // http://www.digma.ru/support/download/
        // @todo: Ebooks also (if requested)
        'DigmaTablet' => '\b(iDx10|iDx9|iDx8|iDx7|iDxD7|iDxD8|iDsQ8|iDsQ7|iDsQ8|iDsD10|iDnD7|3TS804H|iDsQ11|iDj7|iDs10)\b',
        // http://www.evolioshop.com/ro/tablete-pc.html
        // http://www.evolio.ro/support/downloads_static.html?cat=2
        // @todo: Research some more
        'EvolioTablet' => 'ARIA_Mini_wifi|Aria[ _]Mini|Evolio X10|Evolio X7|Evolio X8|\bEvotab\b|\bNeura\b',
        // @todo http://www.lavamobiles.com/tablets-data-cards
        'LavaTablet' => 'QPAD E704|\bIvoryS\b|E-TAB IVORY|\bE-TAB\b',
        // http://www.breezetablet.com/
        'AocTablet' => 'MW0811|MW0812|MW0922|MTK8382|MW1031|MW0831|MW0821|MW0931|MW0712',
        // http://www.mpmaneurope.com/en/products/internet-tablets-14/android-tablets-14/
        'MpmanTablet' => 'MP11 OCTA|MP10 OCTA|MPQC1114|MPQC1004|MPQC994|MPQC974|MPQC973|MPQC804|MPQC784|MPQC780|\bMPG7\b|MPDCG75|MPDCG71|MPDC1006|MP101DC|MPDC9000|MPDC905|MPDC706HD|MPDC706|MPDC705|MPDC110|MPDC100|MPDC99|MPDC97|MPDC88|MPDC8|MPDC77|MP709|MID701|MID711|MID170|MPDC703|MPQC1010',
        // https://www.celkonmobiles.com/?_a=categoryphones&sid=2
        'CelkonTablet' => 'CT695|CT888|CT[\s]?910|CT7 Tab|CT9 Tab|CT3 Tab|CT2 Tab|CT1 Tab|C820|C720|\bCT-1\b',
        // http://www.wolderelectronics.com/productos/manuales-y-guias-rapidas/categoria-2-miTab
        'WolderTablet' => 'miTab \b(DIAMOND|SPACE|BROOKLYN|NEO|FLY|MANHATTAN|FUNK|EVOLUTION|SKY|GOCAR|IRON|GENIUS|POP|MINT|EPSILON|BROADWAY|JUMP|HOP|LEGEND|NEW AGE|LINE|ADVANCE|FEEL|FOLLOW|LIKE|LINK|LIVE|THINK|FREEDOM|CHICAGO|CLEVELAND|BALTIMORE-GH|IOWA|BOSTON|SEATTLE|PHOENIX|DALLAS|IN 101|MasterChef)\b',
        // http://www.mi.com/en
        'MiTablet' => '\bMI PAD\b|\bHM NOTE 1W\b',
        // http://www.nbru.cn/index.html
        'NibiruTablet' => 'Nibiru M1|Nibiru Jupiter One',
        // http://navroad.com/products/produkty/tablety/
        // http://navroad.com/products/produkty/tablety/
        'NexoTablet' => 'NEXO NOVA|NEXO 10|NEXO AVIO|NEXO FREE|NEXO GO|NEXO EVO|NEXO 3G|NEXO SMART|NEXO KIDDO|NEXO MOBI',
        // http://leader-online.com/new_site/product-category/tablets/
        // http://www.leader-online.net.au/List/Tablet
        'LeaderTablet' => 'TBLT10Q|TBLT10I|TBL-10WDKB|TBL-10WDKBO2013|TBL-W230V2|TBL-W450|TBL-W500|SV572|TBLT7I|TBA-AC7-8G|TBLT79|TBL-8W16|TBL-10W32|TBL-10WKB|TBL-W100',
        // http://www.datawind.com/ubislate/
        'UbislateTablet' => 'UbiSlate[\s]?7C',
        // http://www.pocketbook-int.com/ru/support
        'PocketBookTablet' => 'Pocketbook',
        // http://www.kocaso.com/product_tablet.html
        'KocasoTablet' => '\b(TB-1207)\b',
        // http://global.hisense.com/product/asia/tablet/Sero7/201412/t20141215_91832.htm
        'HisenseTablet' => '\b(F5281|E2371)\b',
        // http://www.tesco.com/direct/hudl/
        'Hudl'              => 'Hudl HT7S3|Hudl 2',
        // http://www.telstra.com.au/home-phone/thub-2/
        'TelstraTablet'     => 'T-Hub2',
        'GenericTablet'     => 'Android.*\b97D\b|Tablet(?!.*PC)|BNTV250A|MID-WCDMA|LogicPD Zoom2|\bA7EB\b|CatNova8|A1_07|CT704|CT1002|\bM721\b|rk30sdk|\bEVOTAB\b|M758A|ET904|ALUMIUM10|Smartfren Tab|Endeavour 1010|Tablet-PC-4|Tagi Tab|\bM6pro\b|CT1020W|arc 10HD|\bTP750\b|\bQTAQZ3\b'
    );

    /**
     * List of mobile Operating Systems.
     *
     * @var array
     */
    protected static $operatingSystems = array(
        'AndroidOS'         => 'Android',
        'BlackBerryOS'      => 'blackberry|\bBB10\b|rim tablet os',
        'PalmOS'            => 'PalmOS|avantgo|blazer|elaine|hiptop|palm|plucker|xiino',
        'SymbianOS'         => 'Symbian|SymbOS|Series60|Series40|SYB-[0-9]+|\bS60\b',
        // @reference: http://en.wikipedia.org/wiki/Windows_Mobile
        'WindowsMobileOS'   => 'Windows CE.*(PPC|Smartphone|Mobile|[0-9]{3}x[0-9]{3})|Window Mobile|Windows Phone [0-9.]+|WCE;',
        // @reference: http://en.wikipedia.org/wiki/Windows_Phone
        // http://wifeng.cn/?r=blog&a=view&id=106
        // http://nicksnettravels.builttoroam.com/post/2011/01/10/Bogus-Windows-Phone-7-User-Agent-String.aspx
        // http://msdn.microsoft.com/library/ms537503.aspx
        // https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
        'WindowsPhoneOS'   => 'Windows Phone 10.0|Windows Phone 8.1|Windows Phone 8.0|Windows Phone OS|XBLWP7|ZuneWP7|Windows NT 6.[23]; ARM;',
        'iOS'               => '\biPhone.*Mobile|\biPod|\biPad',
        // http://en.wikipedia.org/wiki/MeeGo
        // @todo: research MeeGo in UAs
        'MeeGoOS'           => 'MeeGo',
        // http://en.wikipedia.org/wiki/Maemo
        // @todo: research Maemo in UAs
        'MaemoOS'           => 'Maemo',
        'JavaOS'            => 'J2ME/|\bMIDP\b|\bCLDC\b', // '|Java/' produces bug #135
        'webOS'             => 'webOS|hpwOS',
        'badaOS'            => '\bBada\b',
        'BREWOS'            => 'BREW',
    );

    /**
     * List of mobile User Agents.
     *
     * IMPORTANT: This is a list of only mobile browsers.
     * Mobile Detect 2.x supports only mobile browsers,
     * it was never designed to detect all browsers.
     * The change will come in 2017 in the 3.x release for PHP7.
     *
     * @var array
     */
    protected static $browsers = array(
        //'Vivaldi'         => 'Vivaldi',
        // @reference: https://developers.google.com/chrome/mobile/docs/user-agent
        'Chrome'          => '\bCrMo\b|CriOS|Android.*Chrome/[.0-9]* (Mobile)?',
        'Dolfin'          => '\bDolfin\b',
        'Opera'           => 'Opera.*Mini|Opera.*Mobi|Android.*Opera|Mobile.*OPR/[0-9.]+|Coast/[0-9.]+',
        'Skyfire'         => 'Skyfire',
        'Edge'             => 'Mobile Safari/[.0-9]* Edge',
        'IE'              => 'IEMobile|MSIEMobile', // |Trident/[.0-9]+
        'Firefox'         => 'fennec|firefox.*maemo|(Mobile|Tablet).*Firefox|Firefox.*Mobile|FxiOS',
        'Bolt'            => 'bolt',
        'TeaShark'        => 'teashark',
        'Blazer'          => 'Blazer',
        // @reference: http://developer.apple.com/library/safari/#documentation/AppleApplications/Reference/SafariWebContent/OptimizingforSafarioniPhone/OptimizingforSafarioniPhone.html#//apple_ref/doc/uid/TP40006517-SW3
        'Safari'          => 'Version.*Mobile.*Safari|Safari.*Mobile|MobileSafari',
        // http://en.wikipedia.org/wiki/Midori_(web_browser)
        //'Midori'          => 'midori',
        //'Tizen'           => 'Tizen',
        'UCBrowser'       => 'UC.*Browser|UCWEB',
        'baiduboxapp'     => 'baiduboxapp',
        'baidubrowser'    => 'baidubrowser',
        // https://github.com/serbanghita/Mobile-Detect/issues/7
        'DiigoBrowser'    => 'DiigoBrowser',
        // http://www.puffinbrowser.com/index.php
        'Puffin'            => 'Puffin',
        // http://mercury-browser.com/index.html
        'Mercury'          => '\bMercury\b',
        // http://en.wikipedia.org/wiki/Obigo_Browser
        'ObigoBrowser' => 'Obigo',
        // http://en.wikipedia.org/wiki/NetFront
        'NetFront' => 'NF-Browser',
        // @reference: http://en.wikipedia.org/wiki/Minimo
        // http://en.wikipedia.org/wiki/Vision_Mobile_Browser
        'GenericBrowser'  => 'NokiaBrowser|OviBrowser|OneBrowser|TwonkyBeamBrowser|SEMC.*Browser|FlyFlow|Minimo|NetFront|Novarra-Vision|MQQBrowser|MicroMessenger',
        // @reference: https://en.wikipedia.org/wiki/Pale_Moon_(web_browser)
        'PaleMoon'        => 'Android.*PaleMoon|Mobile.*PaleMoon',
    );

    /**
     * Utilities.
     *
     * @var array
     */
    protected static $utilities = array(
        // Experimental. When a mobile device wants to switch to 'Desktop Mode'.
        // http://scottcate.com/technology/windows-phone-8-ie10-desktop-or-mobile/
        // https://github.com/serbanghita/Mobile-Detect/issues/57#issuecomment-15024011
        // https://developers.facebook.com/docs/sharing/best-practices
        'Bot'         => 'Googlebot|facebookexternalhit|AdsBot-Google|Google Keyword Suggestion|Facebot|YandexBot|YandexMobileBot|bingbot|ia_archiver|AhrefsBot|Ezooms|GSLFbot|WBSearchBot|Twitterbot|TweetmemeBot|Twikle|PaperLiBot|Wotbox|UnwindFetchor|Exabot|MJ12bot|YandexImages|TurnitinBot|Pingdom',
        'MobileBot'   => 'Googlebot-Mobile|AdsBot-Google-Mobile|YahooSeeker/M1A1-R2D2',
        'DesktopMode' => 'WPDesktop',
        'TV'          => 'SonyDTV|HbbTV', // experimental
        'WebKit'      => '(webkit)[ /]([\w.]+)',
        // @todo: Include JXD consoles.
        'Console'     => '\b(Nintendo|Nintendo WiiU|Nintendo 3DS|PLAYSTATION|Xbox)\b',
        'Watch'       => 'SM-V700',
    );

    /**
     * All possible HTTP headers that represent the
     * User-Agent string.
     *
     * @var array
     */
    protected static $uaHttpHeaders = array(
        // The default User-Agent string.
        'HTTP_USER_AGENT',
        // Header can occur on devices using Opera Mini.
        'HTTP_X_OPERAMINI_PHONE_UA',
        // Vodafone specific header: http://www.seoprinciple.com/mobile-web-community-still-angry-at-vodafone/24/
        'HTTP_X_DEVICE_USER_AGENT',
        'HTTP_X_ORIGINAL_USER_AGENT',
        'HTTP_X_SKYFIRE_PHONE',
        'HTTP_X_BOLT_PHONE_UA',
        'HTTP_DEVICE_STOCK_UA',
        'HTTP_X_UCBROWSER_DEVICE_UA'
    );

    /**
     * The individual segments that could exist in a User-Agent string. VER refers to the regular
     * expression defined in the constant self::VER.
     *
     * @var array
     */
    protected static $properties = array(

        // Build
        'Mobile'        => 'Mobile/[VER]',
        'Build'         => 'Build/[VER]',
        'Version'       => 'Version/[VER]',
        'VendorID'      => 'VendorID/[VER]',

        // Devices
        'iPad'          => 'iPad.*CPU[a-z ]+[VER]',
        'iPhone'        => 'iPhone.*CPU[a-z ]+[VER]',
        'iPod'          => 'iPod.*CPU[a-z ]+[VER]',
        //'BlackBerry'    => array('BlackBerry[VER]', 'BlackBerry [VER];'),
        'Kindle'        => 'Kindle/[VER]',

        // Browser
        'Chrome'        => array('Chrome/[VER]', 'CriOS/[VER]', 'CrMo/[VER]'),
        'Coast'         => array('Coast/[VER]'),
        'Dolfin'        => 'Dolfin/[VER]',
        // @reference: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent/Firefox
        'Firefox'       => array('Firefox/[VER]', 'FxiOS/[VER]'),
        'Fennec'        => 'Fennec/[VER]',
        // http://msdn.microsoft.com/en-us/library/ms537503(v=vs.85).aspx
        // https://msdn.microsoft.com/en-us/library/ie/hh869301(v=vs.85).aspx
        'Edge' => 'Edge/[VER]',
        'IE'      => array('IEMobile/[VER];', 'IEMobile [VER]', 'MSIE [VER];', 'Trident/[0-9.]+;.*rv:[VER]'),
        // http://en.wikipedia.org/wiki/NetFront
        'NetFront'      => 'NetFront/[VER]',
        'NokiaBrowser'  => 'NokiaBrowser/[VER]',
        'Opera'         => array( ' OPR/[VER]', 'Opera Mini/[VER]', 'Version/[VER]' ),
        'Opera Mini'    => 'Opera Mini/[VER]',
        'Opera Mobi'    => 'Version/[VER]',
        'UC Browser'    => 'UC Browser[VER]',
        'MQQBrowser'    => 'MQQBrowser/[VER]',
        'MicroMessenger' => 'MicroMessenger/[VER]',
        'baiduboxapp'   => 'baiduboxapp/[VER]',
        'baidubrowser'  => 'baidubrowser/[VER]',
        'SamsungBrowser' => 'SamsungBrowser/[VER]',
        'Iron'          => 'Iron/[VER]',
        // @note: Safari 7534.48.3 is actually Version 5.1.
        // @note: On BlackBerry the Version is overwriten by the OS.
        'Safari'        => array( 'Version/[VER]', 'Safari/[VER]' ),
        'Skyfire'       => 'Skyfire/[VER]',
        'Tizen'         => 'Tizen/[VER]',
        'Webkit'        => 'webkit[ /][VER]',
        'PaleMoon'         => 'PaleMoon/[VER]',

        // Engine
        'Gecko'         => 'Gecko/[VER]',
        'Trident'       => 'Trident/[VER]',
        'Presto'        => 'Presto/[VER]',
        'Goanna'           => 'Goanna/[VER]',

        // OS
        'iOS'              => ' \bi?OS\b [VER][ ;]{1}',
        'Android'          => 'Android [VER]',
        'BlackBerry'       => array('BlackBerry[\w]+/[VER]', 'BlackBerry.*Version/[VER]', 'Version/[VER]'),
        'BREW'             => 'BREW [VER]',
        'Java'             => 'Java/[VER]',
        // @reference: http://windowsteamblog.com/windows_phone/b/wpdev/archive/2011/08/29/introducing-the-ie9-on-windows-phone-mango-user-agent-string.aspx
        // @reference: http://en.wikipedia.org/wiki/Windows_NT#Releases
        'Windows Phone OS' => array( 'Windows Phone OS [VER]', 'Windows Phone [VER]'),
        'Windows Phone'    => 'Windows Phone [VER]',
        'Windows CE'       => 'Windows CE/[VER]',
        // http://social.msdn.microsoft.com/Forums/en-US/windowsdeveloperpreviewgeneral/thread/6be392da-4d2f-41b4-8354-8dcee20c85cd
        'Windows NT'       => 'Windows NT [VER]',
        'Symbian'          => array('SymbianOS/[VER]', 'Symbian/[VER]'),
        'webOS'            => array('webOS/[VER]', 'hpwOS/[VER];'),
    );

    /**
     * Construct an instance of this class.
     *
     * @param array  $headers   Specify the headers as injection. Should be PHP _SERVER flavored.
     *                          If left empty, will use the global _SERVER['HTTP_*'] vars instead.
     * @param string $userAgent Inject the User-Agent header. If null, will use HTTP_USER_AGENT
     *                          from the $headers array instead.
     */
    public function __construct(
        array $headers = null,
        $userAgent = null
    ) {
        $this->setHttpHeaders($headers);
        $this->setUserAgent($userAgent);
    }

    /**
     * Get the current script version.
     * This is useful for the demo.php file,
     * so people can check on what version they are testing
     * for mobile devices.
     *
     * @return string The version number in semantic version format.
     */
    public static function getScriptVersion()
    {
        return self::VERSION;
    }

    /**
     * Set the HTTP Headers. Must be PHP-flavored. This method will reset existing headers.
     *
     * @param array $httpHeaders The headers to set. If null, then using PHP's _SERVER to extract
     *                           the headers. The default null is left for backwards compatibility.
     */
    public function setHttpHeaders($httpHeaders = null)
    {
        // use global _SERVER if $httpHeaders aren't defined
        if (!is_array($httpHeaders) || !count($httpHeaders)) {
            $httpHeaders = $_SERVER;
        }

        // clear existing headers
        $this->httpHeaders = array();

        // Only save HTTP headers. In PHP land, that means only _SERVER vars that
        // start with HTTP_.
        foreach ($httpHeaders as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $this->httpHeaders[$key] = $value;
            }
        }

        // In case we're dealing with CloudFront, we need to know.
        $this->setCfHeaders($httpHeaders);
    }

    /**
     * Retrieves the HTTP headers.
     *
     * @return array
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }

    /**
     * Retrieves a particular header. If it doesn't exist, no exception/error is caused.
     * Simply null is returned.
     *
     * @param string $header The name of the header to retrieve. Can be HTTP compliant such as
     *                       "User-Agent" or "X-Device-User-Agent" or can be php-esque with the
     *                       all-caps, HTTP_ prefixed, underscore seperated awesomeness.
     *
     * @return string|null The value of the header.
     */
    public function getHttpHeader($header)
    {
        // are we using PHP-flavored headers?
        if (strpos($header, '_') === false) {
            $header = str_replace('-', '_', $header);
            $header = strtoupper($header);
        }

        // test the alternate, too
        $altHeader = 'HTTP_' . $header;

        //Test both the regular and the HTTP_ prefix
        if (isset($this->httpHeaders[$header])) {
            return $this->httpHeaders[$header];
        } elseif (isset($this->httpHeaders[$altHeader])) {
            return $this->httpHeaders[$altHeader];
        }

        return null;
    }

    public function getMobileHeaders()
    {
        return self::$mobileHeaders;
    }

    /**
     * Get all possible HTTP headers that
     * can contain the User-Agent string.
     *
     * @return array List of HTTP headers.
     */
    public function getUaHttpHeaders()
    {
        return self::$uaHttpHeaders;
    }


    /**
     * Set CloudFront headers
     * http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/header-caching.html#header-caching-web-device
     *
     * @param array $cfHeaders List of HTTP headers
     *
     * @return  boolean If there were CloudFront headers to be set
     */
    public function setCfHeaders($cfHeaders = null) {
        // use global _SERVER if $cfHeaders aren't defined
        if (!is_array($cfHeaders) || !count($cfHeaders)) {
            $cfHeaders = $_SERVER;
        }

        // clear existing headers
        $this->cloudfrontHeaders = array();

        // Only save CLOUDFRONT headers. In PHP land, that means only _SERVER vars that
        // start with cloudfront-.
        $response = false;
        foreach ($cfHeaders as $key => $value) {
            if (substr(strtolower($key), 0, 16) === 'http_cloudfront_') {
                $this->cloudfrontHeaders[strtoupper($key)] = $value;
                $response = true;
            }
        }

        return $response;
    }

    /**
     * Retrieves the cloudfront headers.
     *
     * @return array
     */
    public function getCfHeaders()
    {
        return $this->cloudfrontHeaders;
    }

    /**
     * Set the User-Agent to be used.
     *
     * @param string $userAgent The user agent string to set.
     *
     * @return string|null
     */
    public function setUserAgent($userAgent = null)
    {
        // Invalidate cache due to #375
        $this->cache = array();

        if (false === empty($userAgent)) {
            return $this->userAgent = $userAgent;
        } else {
            $this->userAgent = null;
            foreach ($this->getUaHttpHeaders() as $altHeader) {
                if (false === empty($this->httpHeaders[$altHeader])) { // @todo: should use getHttpHeader(), but it would be slow. (Serban)
                    $this->userAgent .= $this->httpHeaders[$altHeader] . " ";
                }
            }

            if (!empty($this->userAgent)) {
                return $this->userAgent = trim($this->userAgent);
            }
        }

        if (count($this->getCfHeaders()) > 0) {
            return $this->userAgent = 'Amazon CloudFront';
        }
        return $this->userAgent = null;
    }

    /**
     * Retrieve the User-Agent.
     *
     * @return string|null The user agent if it's set.
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set the detection type. Must be one of self::DETECTION_TYPE_MOBILE or
     * self::DETECTION_TYPE_EXTENDED. Otherwise, nothing is set.
     *
     * @deprecated since version 2.6.9
     *
     * @param string $type The type. Must be a self::DETECTION_TYPE_* constant. The default
     *                     parameter is null which will default to self::DETECTION_TYPE_MOBILE.
     */
    public function setDetectionType($type = null)
    {
        if ($type === null) {
            $type = self::DETECTION_TYPE_MOBILE;
        }

        if ($type !== self::DETECTION_TYPE_MOBILE && $type !== self::DETECTION_TYPE_EXTENDED) {
            return;
        }

        $this->detectionType = $type;
    }

    public function getMatchingRegex()
    {
        return $this->matchingRegex;
    }

    public function getMatchesArray()
    {
        return $this->matchesArray;
    }

    /**
     * Retrieve the list of known phone devices.
     *
     * @return array List of phone devices.
     */
    public static function getPhoneDevices()
    {
        return self::$phoneDevices;
    }

    /**
     * Retrieve the list of known tablet devices.
     *
     * @return array List of tablet devices.
     */
    public static function getTabletDevices()
    {
        return self::$tabletDevices;
    }

    /**
     * Alias for getBrowsers() method.
     *
     * @return array List of user agents.
     */
    public static function getUserAgents()
    {
        return self::getBrowsers();
    }

    /**
     * Retrieve the list of known browsers. Specifically, the user agents.
     *
     * @return array List of browsers / user agents.
     */
    public static function getBrowsers()
    {
        return self::$browsers;
    }

    /**
     * Retrieve the list of known utilities.
     *
     * @return array List of utilities.
     */
    public static function getUtilities()
    {
        return self::$utilities;
    }

    /**
     * Method gets the mobile detection rules. This method is used for the magic methods $detect->is*().
     *
     * @deprecated since version 2.6.9
     *
     * @return array All the rules (but not extended).
     */
    public static function getMobileDetectionRules()
    {
        static $rules;

        if (!$rules) {
            $rules = array_merge(
                self::$phoneDevices,
                self::$tabletDevices,
                self::$operatingSystems,
                self::$browsers
            );
        }

        return $rules;

    }

    /**
     * Method gets the mobile detection rules + utilities.
     * The reason this is separate is because utilities rules
     * don't necessary imply mobile. This method is used inside
     * the new $detect->is('stuff') method.
     *
     * @deprecated since version 2.6.9
     *
     * @return array All the rules + extended.
     */
    public function getMobileDetectionRulesExtended()
    {
        static $rules;

        if (!$rules) {
            // Merge all rules together.
            $rules = array_merge(
                self::$phoneDevices,
                self::$tabletDevices,
                self::$operatingSystems,
                self::$browsers,
                self::$utilities
            );
        }

        return $rules;
    }

    /**
     * Retrieve the current set of rules.
     *
     * @deprecated since version 2.6.9
     *
     * @return array
     */
    public function getRules()
    {
        if ($this->detectionType == self::DETECTION_TYPE_EXTENDED) {
            return self::getMobileDetectionRulesExtended();
        } else {
            return self::getMobileDetectionRules();
        }
    }

    /**
     * Retrieve the list of mobile operating systems.
     *
     * @return array The list of mobile operating systems.
     */
    public static function getOperatingSystems()
    {
        return self::$operatingSystems;
    }

    /**
     * Check the HTTP headers for signs of mobile.
     * This is the fastest mobile check possible; it's used
     * inside isMobile() method.
     *
     * @return bool
     */
    public function checkHttpHeadersForMobile()
    {

        foreach ($this->getMobileHeaders() as $mobileHeader => $matchType) {
            if (isset($this->httpHeaders[$mobileHeader])) {
                if (is_array($matchType['matches'])) {
                    foreach ($matchType['matches'] as $_match) {
                        if (strpos($this->httpHeaders[$mobileHeader], $_match) !== false) {
                            return true;
                        }
                    }

                    return false;
                } else {
                    return true;
                }
            }
        }

        return false;

    }

    /**
     * Magic overloading method.
     *
     * @method boolean is[...]()
     * @param  string                 $name
     * @param  array                  $arguments
     * @return mixed
     * @throws BadMethodCallException when the method doesn't exist and doesn't start with 'is'
     */
    public function __call($name, $arguments)
    {
        // make sure the name starts with 'is', otherwise
        if (substr($name, 0, 2) !== 'is') {
            throw new BadMethodCallException("No such method exists: $name");
        }

        $this->setDetectionType(self::DETECTION_TYPE_MOBILE);

        $key = substr($name, 2);

        return $this->matchUAAgainstKey($key);
    }

    /**
     * Find a detection rule that matches the current User-agent.
     *
     * @param  null    $userAgent deprecated
     * @return boolean
     */
    protected function matchDetectionRulesAgainstUA($userAgent = null)
    {
        // Begin general search.
        foreach ($this->getRules() as $_regex) {
            if (empty($_regex)) {
                continue;
            }

            if ($this->match($_regex, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Search for a certain key in the rules array.
     * If the key is found then try to match the corresponding
     * regex against the User-Agent.
     *
     * @param string $key
     *
     * @return boolean
     */
    protected function matchUAAgainstKey($key)
    {
        // Make the keys lowercase so we can match: isIphone(), isiPhone(), isiphone(), etc.
        $key = strtolower($key);
        if (false === isset($this->cache[$key])) {

            // change the keys to lower case
            $_rules = array_change_key_case($this->getRules());

            if (false === empty($_rules[$key])) {
                $this->cache[$key] = $this->match($_rules[$key]);
            }

            if (false === isset($this->cache[$key])) {
                $this->cache[$key] = false;
            }
        }

        return $this->cache[$key];
    }

    /**
     * Check if the device is mobile.
     * Returns true if any type of mobile device detected, including special ones
     * @param  null $userAgent   deprecated
     * @param  null $httpHeaders deprecated
     * @return bool
     */
    public function isMobile($userAgent = null, $httpHeaders = null)
    {

        if ($httpHeaders) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent) {
            $this->setUserAgent($userAgent);
        }

        // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
        if ($this->getUserAgent() === 'Amazon CloudFront') {
            $cfHeaders = $this->getCfHeaders();
            if(array_key_exists('HTTP_CLOUDFRONT_IS_MOBILE_VIEWER', $cfHeaders) && $cfHeaders['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] === 'true') {
                return true;
            }
        }

        $this->setDetectionType(self::DETECTION_TYPE_MOBILE);

        if ($this->checkHttpHeadersForMobile()) {
            return true;
        } else {
            return $this->matchDetectionRulesAgainstUA();
        }

    }

    /**
     * Check if the device is a tablet.
     * Return true if any type of tablet device is detected.
     *
     * @param  string $userAgent   deprecated
     * @param  array  $httpHeaders deprecated
     * @return bool
     */
    public function isTablet($userAgent = null, $httpHeaders = null)
    {
        // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
        if ($this->getUserAgent() === 'Amazon CloudFront') {
            $cfHeaders = $this->getCfHeaders();
            if(array_key_exists('HTTP_CLOUDFRONT_IS_TABLET_VIEWER', $cfHeaders) && $cfHeaders['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] === 'true') {
                return true;
            }
        }

        $this->setDetectionType(self::DETECTION_TYPE_MOBILE);

        foreach (self::$tabletDevices as $_regex) {
            if ($this->match($_regex, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method checks for a certain property in the
     * userAgent.
     * @todo: The httpHeaders part is not yet used.
     *
     * @param  string        $key
     * @param  string        $userAgent   deprecated
     * @param  string        $httpHeaders deprecated
     * @return bool|int|null
     */
    public function is($key, $userAgent = null, $httpHeaders = null)
    {
        // Set the UA and HTTP headers only if needed (eg. batch mode).
        if ($httpHeaders) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent) {
            $this->setUserAgent($userAgent);
        }

        $this->setDetectionType(self::DETECTION_TYPE_EXTENDED);

        return $this->matchUAAgainstKey($key);
    }

    /**
     * Some detection rules are relative (not standard),
     * because of the diversity of devices, vendors and
     * their conventions in representing the User-Agent or
     * the HTTP headers.
     *
     * This method will be used to check custom regexes against
     * the User-Agent string.
     *
     * @param $regex
     * @param  string $userAgent
     * @return bool
     *
     * @todo: search in the HTTP headers too.
     */
    public function match($regex, $userAgent = null)
    {
        $match = (bool) preg_match(sprintf('#%s#is', $regex), (false === empty($userAgent) ? $userAgent : $this->userAgent), $matches);
        // If positive match is found, store the results for debug.
        if ($match) {
            $this->matchingRegex = $regex;
            $this->matchesArray = $matches;
        }

        return $match;
    }

    /**
     * Get the properties array.
     *
     * @return array
     */
    public static function getProperties()
    {
        return self::$properties;
    }

    /**
     * Prepare the version number.
     *
     * @todo Remove the error supression from str_replace() call.
     *
     * @param string $ver The string version, like "2.6.21.2152";
     *
     * @return float
     */
    public function prepareVersionNo($ver)
    {
        $ver = str_replace(array('_', ' ', '/'), '.', $ver);
        $arrVer = explode('.', $ver, 2);

        if (isset($arrVer[1])) {
            $arrVer[1] = @str_replace('.', '', $arrVer[1]); // @todo: treat strings versions.
        }

        return (float) implode('.', $arrVer);
    }

    /**
     * Check the version of the given property in the User-Agent.
     * Will return a float number. (eg. 2_0 will return 2.0, 4.3.1 will return 4.31)
     *
     * @param string $propertyName The name of the property. See self::getProperties() array
     *                             keys for all possible properties.
     * @param string $type         Either self::VERSION_TYPE_STRING to get a string value or
     *                             self::VERSION_TYPE_FLOAT indicating a float value. This parameter
     *                             is optional and defaults to self::VERSION_TYPE_STRING. Passing an
     *                             invalid parameter will default to the this type as well.
     *
     * @return string|float The version of the property we are trying to extract.
     */
    public function version($propertyName, $type = self::VERSION_TYPE_STRING)
    {
        if (empty($propertyName)) {
            return false;
        }

        // set the $type to the default if we don't recognize the type
        if ($type !== self::VERSION_TYPE_STRING && $type !== self::VERSION_TYPE_FLOAT) {
            $type = self::VERSION_TYPE_STRING;
        }

        $properties = self::getProperties();

        // Check if the property exists in the properties array.
        if (true === isset($properties[$propertyName])) {

            // Prepare the pattern to be matched.
            // Make sure we always deal with an array (string is converted).
            $properties[$propertyName] = (array) $properties[$propertyName];

            foreach ($properties[$propertyName] as $propertyMatchString) {

                $propertyPattern = str_replace('[VER]', self::VER, $propertyMatchString);

                // Identify and extract the version.
                preg_match(sprintf('#%s#is', $propertyPattern), $this->userAgent, $match);

                if (false === empty($match[1])) {
                    $version = ($type == self::VERSION_TYPE_FLOAT ? $this->prepareVersionNo($match[1]) : $match[1]);

                    return $version;
                }

            }

        }

        return false;
    }

    /**
     * Retrieve the mobile grading, using self::MOBILE_GRADE_* constants.
     *
     * @return string One of the self::MOBILE_GRADE_* constants.
     */
    public function mobileGrade()
    {
        $isMobile = $this->isMobile();

        if (
            // Apple iOS 4-7.0 – Tested on the original iPad (4.3 / 5.0), iPad 2 (4.3 / 5.1 / 6.1), iPad 3 (5.1 / 6.0), iPad Mini (6.1), iPad Retina (7.0), iPhone 3GS (4.3), iPhone 4 (4.3 / 5.1), iPhone 4S (5.1 / 6.0), iPhone 5 (6.0), and iPhone 5S (7.0)
            $this->is('iOS') && $this->version('iPad', self::VERSION_TYPE_FLOAT) >= 4.3 ||
            $this->is('iOS') && $this->version('iPhone', self::VERSION_TYPE_FLOAT) >= 4.3 ||
            $this->is('iOS') && $this->version('iPod', self::VERSION_TYPE_FLOAT) >= 4.3 ||

            // Android 2.1-2.3 - Tested on the HTC Incredible (2.2), original Droid (2.2), HTC Aria (2.1), Google Nexus S (2.3). Functional on 1.5 & 1.6 but performance may be sluggish, tested on Google G1 (1.5)
            // Android 3.1 (Honeycomb)  - Tested on the Samsung Galaxy Tab 10.1 and Motorola XOOM
            // Android 4.0 (ICS)  - Tested on a Galaxy Nexus. Note: transition performance can be poor on upgraded devices
            // Android 4.1 (Jelly Bean)  - Tested on a Galaxy Nexus and Galaxy 7
            ( $this->version('Android', self::VERSION_TYPE_FLOAT)>2.1 && $this->is('Webkit') ) ||

            // Windows Phone 7.5-8 - Tested on the HTC Surround (7.5), HTC Trophy (7.5), LG-E900 (7.5), Nokia 800 (7.8), HTC Mazaa (7.8), Nokia Lumia 520 (8), Nokia Lumia 920 (8), HTC 8x (8)
            $this->version('Windows Phone OS', self::VERSION_TYPE_FLOAT) >= 7.5 ||

            // Tested on the Torch 9800 (6) and Style 9670 (6), BlackBerry® Torch 9810 (7), BlackBerry Z10 (10)
            $this->is('BlackBerry') && $this->version('BlackBerry', self::VERSION_TYPE_FLOAT) >= 6.0 ||
            // Blackberry Playbook (1.0-2.0) - Tested on PlayBook
            $this->match('Playbook.*Tablet') ||

            // Palm WebOS (1.4-3.0) - Tested on the Palm Pixi (1.4), Pre (1.4), Pre 2 (2.0), HP TouchPad (3.0)
            ( $this->version('webOS', self::VERSION_TYPE_FLOAT) >= 1.4 && $this->match('Palm|Pre|Pixi') ) ||
            // Palm WebOS 3.0  - Tested on HP TouchPad
            $this->match('hp.*TouchPad') ||

            // Firefox Mobile 18 - Tested on Android 2.3 and 4.1 devices
            ( $this->is('Firefox') && $this->version('Firefox', self::VERSION_TYPE_FLOAT) >= 18 ) ||

            // Chrome for Android - Tested on Android 4.0, 4.1 device
            ( $this->is('Chrome') && $this->is('AndroidOS') && $this->version('Android', self::VERSION_TYPE_FLOAT) >= 4.0 ) ||

            // Skyfire 4.1 - Tested on Android 2.3 device
            ( $this->is('Skyfire') && $this->version('Skyfire', self::VERSION_TYPE_FLOAT) >= 4.1 && $this->is('AndroidOS') && $this->version('Android', self::VERSION_TYPE_FLOAT) >= 2.3 ) ||

            // Opera Mobile 11.5-12: Tested on Android 2.3
            ( $this->is('Opera') && $this->version('Opera Mobi', self::VERSION_TYPE_FLOAT) >= 11.5 && $this->is('AndroidOS') ) ||

            // Meego 1.2 - Tested on Nokia 950 and N9
            $this->is('MeeGoOS') ||

            // Tizen (pre-release) - Tested on early hardware
            $this->is('Tizen') ||

            // Samsung Bada 2.0 - Tested on a Samsung Wave 3, Dolphin browser
            // @todo: more tests here!
            $this->is('Dolfin') && $this->version('Bada', self::VERSION_TYPE_FLOAT) >= 2.0 ||

            // UC Browser - Tested on Android 2.3 device
            ( ($this->is('UC Browser') || $this->is('Dolfin')) && $this->version('Android', self::VERSION_TYPE_FLOAT) >= 2.3 ) ||

            // Kindle 3 and Fire  - Tested on the built-in WebKit browser for each
            ( $this->match('Kindle Fire') ||
            $this->is('Kindle') && $this->version('Kindle', self::VERSION_TYPE_FLOAT) >= 3.0 ) ||

            // Nook Color 1.4.1 - Tested on original Nook Color, not Nook Tablet
            $this->is('AndroidOS') && $this->is('NookTablet') ||

            // Chrome Desktop 16-24 - Tested on OS X 10.7 and Windows 7
            $this->version('Chrome', self::VERSION_TYPE_FLOAT) >= 16 && !$isMobile ||

            // Safari Desktop 5-6 - Tested on OS X 10.7 and Windows 7
            $this->version('Safari', self::VERSION_TYPE_FLOAT) >= 5.0 && !$isMobile ||

            // Firefox Desktop 10-18 - Tested on OS X 10.7 and Windows 7
            $this->version('Firefox', self::VERSION_TYPE_FLOAT) >= 10.0 && !$isMobile ||

            // Internet Explorer 7-9 - Tested on Windows XP, Vista and 7
            $this->version('IE', self::VERSION_TYPE_FLOAT) >= 7.0 && !$isMobile ||

            // Opera Desktop 10-12 - Tested on OS X 10.7 and Windows 7
            $this->version('Opera', self::VERSION_TYPE_FLOAT) >= 10 && !$isMobile
        ){
            return self::MOBILE_GRADE_A;
        }

        if (
            $this->is('iOS') && $this->version('iPad', self::VERSION_TYPE_FLOAT)<4.3 ||
            $this->is('iOS') && $this->version('iPhone', self::VERSION_TYPE_FLOAT)<4.3 ||
            $this->is('iOS') && $this->version('iPod', self::VERSION_TYPE_FLOAT)<4.3 ||

            // Blackberry 5.0: Tested on the Storm 2 9550, Bold 9770
            $this->is('Blackberry') && $this->version('BlackBerry', self::VERSION_TYPE_FLOAT) >= 5 && $this->version('BlackBerry', self::VERSION_TYPE_FLOAT)<6 ||

            //Opera Mini (5.0-6.5) - Tested on iOS 3.2/4.3 and Android 2.3
            ($this->version('Opera Mini', self::VERSION_TYPE_FLOAT) >= 5.0 && $this->version('Opera Mini', self::VERSION_TYPE_FLOAT) <= 7.0 &&
            ($this->version('Android', self::VERSION_TYPE_FLOAT) >= 2.3 || $this->is('iOS')) ) ||

            // Nokia Symbian^3 - Tested on Nokia N8 (Symbian^3), C7 (Symbian^3), also works on N97 (Symbian^1)
            $this->match('NokiaN8|NokiaC7|N97.*Series60|Symbian/3') ||

            // @todo: report this (tested on Nokia N71)
            $this->version('Opera Mobi', self::VERSION_TYPE_FLOAT) >= 11 && $this->is('SymbianOS')
        ){
            return self::MOBILE_GRADE_B;
        }

        if (
            // Blackberry 4.x - Tested on the Curve 8330
            $this->version('BlackBerry', self::VERSION_TYPE_FLOAT) <= 5.0 ||
            // Windows Mobile - Tested on the HTC Leo (WinMo 5.2)
            $this->match('MSIEMobile|Windows CE.*Mobile') || $this->version('Windows Mobile', self::VERSION_TYPE_FLOAT) <= 5.2 ||

            // Tested on original iPhone (3.1), iPhone 3 (3.2)
            $this->is('iOS') && $this->version('iPad', self::VERSION_TYPE_FLOAT) <= 3.2 ||
            $this->is('iOS') && $this->version('iPhone', self::VERSION_TYPE_FLOAT) <= 3.2 ||
            $this->is('iOS') && $this->version('iPod', self::VERSION_TYPE_FLOAT) <= 3.2 ||

            // Internet Explorer 7 and older - Tested on Windows XP
            $this->version('IE', self::VERSION_TYPE_FLOAT) <= 7.0 && !$isMobile
        ){
            return self::MOBILE_GRADE_C;
        }

        // All older smartphone platforms and featurephones - Any device that doesn't support media queries
        // will receive the basic, C grade experience.
        return self::MOBILE_GRADE_C;
    }
}
$mobileDetect = new Mobile_Detect;
$userDevice = ($mobileDetect->isMobile() ? ($mobileDetect->isTablet() ? 'tablet' : 'phone') : 'computer');

function orgEmail($header = "Message From Admin", $title = "Important Message", $user = "Organizr User", $mainMessage = "", $button = null, $buttonURL = null, $subTitle = "", $subMessage = ""){
	$path = getServerPath();
	return '
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <!--[if gte mso 9]><xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml><![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <title></title>
    <!--[if !mso]><!-- -->
    <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet" type="text/css">
    <!--<![endif]-->
    <style type="text/css" id="media-query">
        body {
            margin: 0;
            padding: 0;
        }
        table,
        tr,
        td {
            vertical-align: top;
            border-collapse: collapse;
        }
        .ie-browser table,
        .mso-container table {
            table-layout: fixed;
        }
        * {
            line-height: inherit;
        }
        a[x-apple-data-detectors=true] {
            color: inherit !important;
            text-decoration: none !important;
        }
        [owa] .img-container div,
        [owa] .img-container button {
            display: block !important;
        }
        [owa] .fullwidth button {
            width: 100% !important;
        }
        [owa] .block-grid .col {
            display: table-cell;
            float: none !important;
            vertical-align: top;
        }
        .ie-browser .num12,
        .ie-browser .block-grid,
        [owa] .num12,
        [owa] .block-grid {
            width: 615px !important;
        }
        .ExternalClass,
        .ExternalClass p,
        .ExternalClass span,
        .ExternalClass font,
        .ExternalClass td,
        .ExternalClass div {
            line-height: 100%;
        }
        .ie-browser .mixed-two-up .num4,
        [owa] .mixed-two-up .num4 {
            width: 204px !important;
        }
        .ie-browser .mixed-two-up .num8,
        [owa] .mixed-two-up .num8 {
            width: 408px !important;
        }
        .ie-browser .block-grid.two-up .col,
        [owa] .block-grid.two-up .col {
            width: 307px !important;
        }
        .ie-browser .block-grid.three-up .col,
        [owa] .block-grid.three-up .col {
            width: 205px !important;
        }
        .ie-browser .block-grid.four-up .col,
        [owa] .block-grid.four-up .col {
            width: 153px !important;
        }
        .ie-browser .block-grid.five-up .col,
        [owa] .block-grid.five-up .col {
            width: 123px !important;
        }
        .ie-browser .block-grid.six-up .col,
        [owa] .block-grid.six-up .col {
            width: 102px !important;
        }
        .ie-browser .block-grid.seven-up .col,
        [owa] .block-grid.seven-up .col {
            width: 87px !important;
        }
        .ie-browser .block-grid.eight-up .col,
        [owa] .block-grid.eight-up .col {
            width: 76px !important;
        }
        .ie-browser .block-grid.nine-up .col,
        [owa] .block-grid.nine-up .col {
            width: 68px !important;
        }
        .ie-browser .block-grid.ten-up .col,
        [owa] .block-grid.ten-up .col {
            width: 61px !important;
        }
        .ie-browser .block-grid.eleven-up .col,
        [owa] .block-grid.eleven-up .col {
            width: 55px !important;
        }
        .ie-browser .block-grid.twelve-up .col,
        [owa] .block-grid.twelve-up .col {
            width: 51px !important;
        }
        @media only screen and (min-width: 635px) {
            .block-grid {
                width: 615px !important;
            }
            .block-grid .col {
                display: table-cell;
                Float: none !important;
                vertical-align: top;
            }
            .block-grid .col.num12 {
                width: 615px !important;
            }
            .block-grid.mixed-two-up .col.num4 {
                width: 204px !important;
            }
            .block-grid.mixed-two-up .col.num8 {
                width: 408px !important;
            }
            .block-grid.two-up .col {
                width: 307px !important;
            }
            .block-grid.three-up .col {
                width: 205px !important;
            }
            .block-grid.four-up .col {
                width: 153px !important;
            }
            .block-grid.five-up .col {
                width: 123px !important;
            }
            .block-grid.six-up .col {
                width: 102px !important;
            }
            .block-grid.seven-up .col {
                width: 87px !important;
            }
            .block-grid.eight-up .col {
                width: 76px !important;
            }
            .block-grid.nine-up .col {
                width: 68px !important;
            }
            .block-grid.ten-up .col {
                width: 61px !important;
            }
            .block-grid.eleven-up .col {
                width: 55px !important;
            }
            .block-grid.twelve-up .col {
                width: 51px !important;
            }
        }
        @media (max-width: 635px) {
            .block-grid,
            .col {
                min-width: 320px !important;
                max-width: 100% !important;
            }
            .block-grid {
                width: calc(100% - 40px) !important;
            }
            .col {
                width: 100% !important;
            }
            .col>div {
                margin: 0 auto;
            }
            img.fullwidth {
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body class="clean-body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #FFFFFF">
    <!--[if IE]><div class="ie-browser"><![endif]-->
    <!--[if mso]><div class="mso-container"><![endif]-->
    <div class="nl-container" style="min-width: 320px;Margin: 0 auto;background-color: #FFFFFF">
        <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #FFFFFF;"><![endif]-->
        <div style="background-color:#333333;">
            <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
                class="block-grid ">
                <div style="border-collapse: collapse;display: table;width: 100%;">
                    <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#333333;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
                    <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:0px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
                    <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
                        <div style="background-color: transparent; width: 100% !important;">
                            <!--[if (!mso)&(!IE)]><!-->
                            <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:0px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
                                <!--<![endif]-->
                                <div align="left" class="img-container left fullwidth" style="padding-right: 30px;	padding-left: 30px;">
                                    <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 30px; padding-left: 30px;" align="left"><![endif]-->
                                    <img class="left fullwidth" align="left" border="0" src="'.$path.'images/organizr-logo-h.png" alt="Image" title="Image"
                                        style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: block !important;border: 0;height: auto;float: none;width: 100%;max-width: 555px"
                                        width="555">
                                    <!--[if mso]></td></tr></table><![endif]-->
                                </div>
                                <!--[if (!mso)&(!IE)]><!-->
                            </div>
                            <!--<![endif]-->
                        </div>
                    </div>
                    <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                </div>
            </div>
        </div>
        <div style="background-color:#333333;">
            <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
                class="block-grid ">
                <div style="border-collapse: collapse;display: table;width: 100%;">
                    <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#333333;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
                    <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:0px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
                    <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
                        <div style="background-color: transparent; width: 100% !important;">
                            <!--[if (!mso)&(!IE)]><!-->
                            <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:0px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
                                <!--<![endif]-->
                                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top: 0px; padding-bottom: 0px;"><![endif]-->
                                <div style="font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#FFFFFF; padding-right: 0px; padding-left: 0px; padding-top: 0px; padding-bottom: 0px;">
                                    <div style="font-size:12px;line-height:14px;color:#FFFFFF;font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                                        <p style="margin: 0;font-size: 12px;line-height: 14px;text-align: center"><span style="font-size: 16px; line-height: 19px;"><strong><span style="line-height: 19px; font-size: 16px;">'.$header.'</span></strong>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <!--[if mso]></td></tr></table><![endif]-->
                                <!--[if (!mso)&(!IE)]><!-->
                            </div>
                            <!--<![endif]-->
                        </div>
                    </div>
                    <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                </div>
            </div>
        </div>
        <div style="background-color:#393939;">
            <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
                class="block-grid ">
                <div style="border-collapse: collapse;display: table;width: 100%;">
                    <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#393939;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
                    <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
                    <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
                        <div style="background-color: transparent; width: 100% !important;">
                            <!--[if (!mso)&(!IE)]><!-->
                            <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
                                <!--<![endif]-->
                                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 30px; padding-left: 30px; padding-top: 0px; padding-bottom: 0px;"><![endif]-->
                                <div style="font-family:\'Ubuntu\', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#FFFFFF; padding-right: 30px; padding-left: 30px; padding-top: 0px; padding-bottom: 0px;">
                                    <div style="font-family:Ubuntu, Tahoma, Verdana, Segoe, sans-serif;font-size:12px;line-height:14px;color:#FFFFFF;text-align:left;">
                                        <p style="margin: 0;font-size: 12px;line-height: 14px;text-align: center"><span style="font-size: 16px; line-height: 19px;"><strong>'.$title.'</strong></span></p>
                                    </div>
                                </div>
                                <!--[if mso]></td></tr></table><![endif]-->
                                <div style="padding-right: 5px; padding-left: 5px; padding-top: 5px; padding-bottom: 5px;">
                                    <!--[if (mso)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 5px;padding-left: 5px; padding-top: 5px; padding-bottom: 5px;"><table width="55%" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                                    <div align="center">
                                        <div style="border-top: 2px solid #66D9EF; width:55%; line-height:2px; height:2px; font-size:2px;">&#160;</div>
                                    </div>
                                    <!--[if (mso)]></td></tr></table></td></tr></table><![endif]-->
                                </div>
                                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 30px; padding-left: 30px; padding-top: 15px; padding-bottom: 10px;"><![endif]-->
                                <div style="font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#FFFFFF; padding-right: 30px; padding-left: 30px; padding-top: 15px; padding-bottom: 10px;">
                                    <div style="font-family:\'Lato\',Tahoma,Verdana,Segoe,sans-serif;font-size:12px;line-height:14px;color:#FFFFFF;text-align:left;">
                                        <p style="margin: 0;font-size: 12px;line-height: 14px"><span style="font-size: 28px; line-height: 33px;">Hey '.$user.',</span></p>
                                    </div>
                                </div>
                                <!--[if mso]></td></tr></table><![endif]-->
                                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 15px; padding-left: 30px; padding-top: 10px; padding-bottom: 25px;"><![endif]-->
                                <div style="font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;line-height:180%;color:#FFFFFF; padding-right: 15px; padding-left: 30px; padding-top: 10px; padding-bottom: 25px;">
                                    <div style="font-size:12px;line-height:22px;font-family:\'Lato\',Tahoma,Verdana,Segoe,sans-serif;color:#FFFFFF;text-align:left;">
                                        <p style="margin: 0;font-size: 14px;line-height: 25px"><span style="font-size: 18px; line-height: 32px;"><em><span style="line-height: 32px; font-size: 18px;">'.$mainMessage.'</span></em>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <!--[if mso]></td></tr></table><![endif]-->
                                <div align="center" class="button-container center" style="padding-right: 30px; padding-left: 30px; padding-top:15px; padding-bottom:15px;">
                                    <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;"><tr><td style="padding-right: 30px; padding-left: 30px; padding-top:15px; padding-bottom:15px;" align="center"><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.$path.'" style="height:48px; v-text-anchor:middle; width:194px;" arcsize="53%" strokecolor="" fillcolor="#66D9EF"><w:anchorlock/><center style="color:#000; font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif; font-size:18px;"><![endif]-->
                                    <a href="'.$buttonURL.'" target="_blank" style="display: inline-block;text-decoration: none;-webkit-text-size-adjust: none;text-align: center;color: #000; background-color: #66D9EF; border-radius: 25px; -webkit-border-radius: 25px; -moz-border-radius: 25px; max-width: 180px; width: 114px; width: auto; border-top: 3px solid transparent; border-right: 3px solid transparent; border-bottom: 3px solid transparent; border-left: 3px solid transparent; padding-top: 5px; padding-right: 30px; padding-bottom: 5px; padding-left: 30px; font-family: \'Lato\', Tahoma, Verdana, Segoe, sans-serif;mso-border-alt: none">
<span style="font-size:12px;line-height:21px;"><span style="font-size: 18px; line-height: 32px;" data-mce-style="font-size: 18px; line-height: 44px;">'.$button.'</span></span></a>
                                    <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                                </div>
                                <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                            </div>
                            <!--[if (!mso)&(!IE)]><!-->
                        </div>
                        <!--<![endif]-->
                    </div>
                </div>
                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
            </div>
        </div>
    </div>
    <div style="background-color:#ffffff;">
        <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
            class="block-grid ">
            <div style="border-collapse: collapse;display: table;width: 100%;">
                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#ffffff;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
                <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:30px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
                <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
                    <div style="background-color: transparent; width: 100% !important;">
                        <!--[if (!mso)&(!IE)]><!-->
                        <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:5px; padding-bottom:30px; padding-right: 0px; padding-left: 0px;">
                            <!--<![endif]-->
                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 10px;"><![endif]-->
                            <div style="font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#555555; padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 10px;">
                                <div style="font-size:12px;line-height:14px;color:#555555;font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                                    <p style="margin: 0;font-size: 14px;line-height: 17px;text-align: center"><strong><span style="font-size: 26px; line-height: 31px;">'.$subTitle.'<br></span></strong></p>
                                </div>
                            </div>
                            <!--[if mso]></td></tr></table><![endif]-->
                            <div style="padding-right: 20px; padding-left: 20px; padding-top: 15px; padding-bottom: 20px;">
                                <!--[if (mso)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 20px;padding-left: 20px; padding-top: 15px; padding-bottom: 20px;"><table width="40%" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                                <div align="center">
                                    <div style="border-top: 3px solid #66D9EF; width:40%; line-height:3px; height:3px; font-size:3px;">&#160;</div>
                                </div>
                                <!--[if (mso)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px;"><![endif]-->
                            <div style="font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;line-height:180%;color:#7E7D7D; padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px;">
                                <div style="font-size:12px;line-height:22px;color:#7E7D7D;font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                                    <p style="margin: 0;font-size: 14px;line-height: 25px;text-align: center"><em><span style="font-size: 18px; line-height: 32px;">'.$subMessage.'</span></em></p>
                                </div>
                            </div>
                            <!--[if mso]></td></tr></table><![endif]-->
                            <!--[if (!mso)&(!IE)]><!-->
                        </div>
                        <!--<![endif]-->
                    </div>
                </div>
                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
            </div>
        </div>
    </div>
    <div style="background-color:#333333;">
        <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
            class="block-grid ">
            <div style="border-collapse: collapse;display: table;width: 100%;">
                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#333333;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
                <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
                <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
                    <div style="background-color: transparent; width: 100% !important;">
                        <!--[if (!mso)&(!IE)]><!-->
                        <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
                            <!--<![endif]-->
                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px;"><![endif]-->
                            <div style="font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#959595; padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px;">
                                <div style="font-size:12px;line-height:14px;color:#959595;font-family:\'Lato\', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                                    <p style="margin: 0;font-size: 14px;line-height: 17px;text-align: center">This&#160;email was sent by <a style="color:#AD80FD;text-decoration: underline;" title="Organizr"
                                            href="https://github.com/causefx/Organizr" target="_blank" rel="noopener noreferrer">Organizr</a><strong><br></strong></p>
                                </div>
                            </div>
                            <!--[if mso]></td></tr></table><![endif]-->
                            <!--[if (!mso)&(!IE)]><!-->
                        </div>
                        <!--<![endif]-->
                    </div>
                </div>
                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
            </div>
        </div>
    </div>
    <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
    </div>
    <!--[if (mso)|(IE)]></div><![endif]-->
</body>
</html>
	';
}

function mimeTypes(){
	return array(
		'123' => 'application/vnd.lotus-1-2-3',
		'3dml' => 'text/vnd.in3d.3dml',
		'3ds' => 'image/x-3ds',
		'3g2' => 'video/3gpp2',
		'3gp' => 'video/3gpp',
		'7z' => 'application/x-7z-compressed',
		'aab' => 'application/x-authorware-bin',
		'aac' => 'audio/x-aac',
		'aam' => 'application/x-authorware-map',
		'aas' => 'application/x-authorware-seg',
		'abw' => 'application/x-abiword',
		'ac' => 'application/pkix-attr-cert',
		'acc' => 'application/vnd.americandynamics.acc',
		'ace' => 'application/x-ace-compressed',
		'acu' => 'application/vnd.acucobol',
		'acutc' => 'application/vnd.acucorp',
		'adp' => 'audio/adpcm',
		'aep' => 'application/vnd.audiograph',
		'afm' => 'application/x-font-type1',
		'afp' => 'application/vnd.ibm.modcap',
		'ahead' => 'application/vnd.ahead.space',
		'ai' => 'application/postscript',
		'aif' => 'audio/x-aiff',
		'aifc' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'air' => 'application/vnd.adobe.air-application-installer-package+zip',
		'ait' => 'application/vnd.dvb.ait',
		'ami' => 'application/vnd.amiga.ami',
		'apk' => 'application/vnd.android.package-archive',
		'appcache' => 'text/cache-manifest',
		'application' => 'application/x-ms-application',
		'apr' => 'application/vnd.lotus-approach',
		'arc' => 'application/x-freearc',
		'asc' => 'application/pgp-signature',
		'asf' => 'video/x-ms-asf',
		'asm' => 'text/x-asm',
		'aso' => 'application/vnd.accpac.simply.aso',
		'asx' => 'video/x-ms-asf',
		'atc' => 'application/vnd.acucorp',
		'atom' => 'application/atom+xml',
		'atomcat' => 'application/atomcat+xml',
		'atomsvc' => 'application/atomsvc+xml',
		'atx' => 'application/vnd.antix.game-component',
		'au' => 'audio/basic',
		'avi' => 'video/x-msvideo',
		'aw' => 'application/applixware',
		'azf' => 'application/vnd.airzip.filesecure.azf',
		'azs' => 'application/vnd.airzip.filesecure.azs',
		'azw' => 'application/vnd.amazon.ebook',
		'bat' => 'application/x-msdownload',
		'bcpio' => 'application/x-bcpio',
		'bdf' => 'application/x-font-bdf',
		'bdm' => 'application/vnd.syncml.dm+wbxml',
		'bed' => 'application/vnd.realvnc.bed',
		'bh2' => 'application/vnd.fujitsu.oasysprs',
		'bin' => 'application/octet-stream',
		'blb' => 'application/x-blorb',
		'blorb' => 'application/x-blorb',
		'bmi' => 'application/vnd.bmi',
		'bmp' => 'image/bmp',
		'book' => 'application/vnd.framemaker',
		'box' => 'application/vnd.previewsystems.box',
		'boz' => 'application/x-bzip2',
		'bpk' => 'application/octet-stream',
		'btif' => 'image/prs.btif',
		'bz' => 'application/x-bzip',
		'bz2' => 'application/x-bzip2',
		'c' => 'text/x-c',
		'c11amc' => 'application/vnd.cluetrust.cartomobile-config',
		'c11amz' => 'application/vnd.cluetrust.cartomobile-config-pkg',
		'c4d' => 'application/vnd.clonk.c4group',
		'c4f' => 'application/vnd.clonk.c4group',
		'c4g' => 'application/vnd.clonk.c4group',
		'c4p' => 'application/vnd.clonk.c4group',
		'c4u' => 'application/vnd.clonk.c4group',
		'cab' => 'application/vnd.ms-cab-compressed',
		'caf' => 'audio/x-caf',
		'cap' => 'application/vnd.tcpdump.pcap',
		'car' => 'application/vnd.curl.car',
		'cat' => 'application/vnd.ms-pki.seccat',
		'cb7' => 'application/x-cbr',
		'cba' => 'application/x-cbr',
		'cbr' => 'application/x-cbr',
		'cbt' => 'application/x-cbr',
		'cbz' => 'application/x-cbr',
		'cc' => 'text/x-c',
		'cct' => 'application/x-director',
		'ccxml' => 'application/ccxml+xml',
		'cdbcmsg' => 'application/vnd.contact.cmsg',
		'cdf' => 'application/x-netcdf',
		'cdkey' => 'application/vnd.mediastation.cdkey',
		'cdmia' => 'application/cdmi-capability',
		'cdmic' => 'application/cdmi-container',
		'cdmid' => 'application/cdmi-domain',
		'cdmio' => 'application/cdmi-object',
		'cdmiq' => 'application/cdmi-queue',
		'cdx' => 'chemical/x-cdx',
		'cdxml' => 'application/vnd.chemdraw+xml',
		'cdy' => 'application/vnd.cinderella',
		'cer' => 'application/pkix-cert',
		'cfs' => 'application/x-cfs-compressed',
		'cgm' => 'image/cgm',
		'chat' => 'application/x-chat',
		'chm' => 'application/vnd.ms-htmlhelp',
		'chrt' => 'application/vnd.kde.kchart',
		'cif' => 'chemical/x-cif',
		'cii' => 'application/vnd.anser-web-certificate-issue-initiation',
		'cil' => 'application/vnd.ms-artgalry',
		'cla' => 'application/vnd.claymore',
		'class' => 'application/java-vm',
		'clkk' => 'application/vnd.crick.clicker.keyboard',
		'clkp' => 'application/vnd.crick.clicker.palette',
		'clkt' => 'application/vnd.crick.clicker.template',
		'clkw' => 'application/vnd.crick.clicker.wordbank',
		'clkx' => 'application/vnd.crick.clicker',
		'clp' => 'application/x-msclip',
		'cmc' => 'application/vnd.cosmocaller',
		'cmdf' => 'chemical/x-cmdf',
		'cml' => 'chemical/x-cml',
		'cmp' => 'application/vnd.yellowriver-custom-menu',
		'cmx' => 'image/x-cmx',
		'cod' => 'application/vnd.rim.cod',
		'com' => 'application/x-msdownload',
		'conf' => 'text/plain',
		'cpio' => 'application/x-cpio',
		'cpp' => 'text/x-c',
		'cpt' => 'application/mac-compactpro',
		'crd' => 'application/x-mscardfile',
		'crl' => 'application/pkix-crl',
		'crt' => 'application/x-x509-ca-cert',
		'cryptonote' => 'application/vnd.rig.cryptonote',
		'csh' => 'application/x-csh',
		'csml' => 'chemical/x-csml',
		'csp' => 'application/vnd.commonspace',
		'css' => 'text/css',
		'cst' => 'application/x-director',
		'csv' => 'text/csv',
		'cu' => 'application/cu-seeme',
		'curl' => 'text/vnd.curl',
		'cww' => 'application/prs.cww',
		'cxt' => 'application/x-director',
		'cxx' => 'text/x-c',
		'dae' => 'model/vnd.collada+xml',
		'daf' => 'application/vnd.mobius.daf',
		'dart' => 'application/vnd.dart',
		'dataless' => 'application/vnd.fdsn.seed',
		'davmount' => 'application/davmount+xml',
		'dbk' => 'application/docbook+xml',
		'dcr' => 'application/x-director',
		'dcurl' => 'text/vnd.curl.dcurl',
		'dd2' => 'application/vnd.oma.dd2+xml',
		'ddd' => 'application/vnd.fujixerox.ddd',
		'deb' => 'application/x-debian-package',
		'def' => 'text/plain',
		'deploy' => 'application/octet-stream',
		'der' => 'application/x-x509-ca-cert',
		'dfac' => 'application/vnd.dreamfactory',
		'dgc' => 'application/x-dgc-compressed',
		'dic' => 'text/x-c',
		'dir' => 'application/x-director',
		'dis' => 'application/vnd.mobius.dis',
		'dist' => 'application/octet-stream',
		'distz' => 'application/octet-stream',
		'djv' => 'image/vnd.djvu',
		'djvu' => 'image/vnd.djvu',
		'dll' => 'application/x-msdownload',
		'dmg' => 'application/x-apple-diskimage',
		'dmp' => 'application/vnd.tcpdump.pcap',
		'dms' => 'application/octet-stream',
		'dna' => 'application/vnd.dna',
		'doc' => 'application/msword',
		'docm' => 'application/vnd.ms-word.document.macroenabled.12',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'dot' => 'application/msword',
		'dotm' => 'application/vnd.ms-word.template.macroenabled.12',
		'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		'dp' => 'application/vnd.osgi.dp',
		'dpg' => 'application/vnd.dpgraph',
		'dra' => 'audio/vnd.dra',
		'dsc' => 'text/prs.lines.tag',
		'dssc' => 'application/dssc+der',
		'dtb' => 'application/x-dtbook+xml',
		'dtd' => 'application/xml-dtd',
		'dts' => 'audio/vnd.dts',
		'dtshd' => 'audio/vnd.dts.hd',
		'dump' => 'application/octet-stream',
		'dvb' => 'video/vnd.dvb.file',
		'dvi' => 'application/x-dvi',
		'dwf' => 'model/vnd.dwf',
		'dwg' => 'image/vnd.dwg',
		'dxf' => 'image/vnd.dxf',
		'dxp' => 'application/vnd.spotfire.dxp',
		'dxr' => 'application/x-director',
		'ecelp4800' => 'audio/vnd.nuera.ecelp4800',
		'ecelp7470' => 'audio/vnd.nuera.ecelp7470',
		'ecelp9600' => 'audio/vnd.nuera.ecelp9600',
		'ecma' => 'application/ecmascript',
		'edm' => 'application/vnd.novadigm.edm',
		'edx' => 'application/vnd.novadigm.edx',
		'efif' => 'application/vnd.picsel',
		'ei6' => 'application/vnd.pg.osasli',
		'elc' => 'application/octet-stream',
		'emf' => 'application/x-msmetafile',
		'eml' => 'message/rfc822',
		'emma' => 'application/emma+xml',
		'emz' => 'application/x-msmetafile',
		'eol' => 'audio/vnd.digital-winds',
		'eot' => 'application/vnd.ms-fontobject',
		'eps' => 'application/postscript',
		'epub' => 'application/epub+zip',
		'es3' => 'application/vnd.eszigno3+xml',
		'esa' => 'application/vnd.osgi.subsystem',
		'esf' => 'application/vnd.epson.esf',
		'et3' => 'application/vnd.eszigno3+xml',
		'etx' => 'text/x-setext',
		'eva' => 'application/x-eva',
		'evy' => 'application/x-envoy',
		'exe' => 'application/x-msdownload',
		'exi' => 'application/exi',
		'ext' => 'application/vnd.novadigm.ext',
		'ez' => 'application/andrew-inset',
		'ez2' => 'application/vnd.ezpix-album',
		'ez3' => 'application/vnd.ezpix-package',
		'f' => 'text/x-fortran',
		'f4v' => 'video/x-f4v',
		'f77' => 'text/x-fortran',
		'f90' => 'text/x-fortran',
		'fbs' => 'image/vnd.fastbidsheet',
		'fcdt' => 'application/vnd.adobe.formscentral.fcdt',
		'fcs' => 'application/vnd.isac.fcs',
		'fdf' => 'application/vnd.fdf',
		'fe_launch' => 'application/vnd.denovo.fcselayout-link',
		'fg5' => 'application/vnd.fujitsu.oasysgp',
		'fgd' => 'application/x-director',
		'fh' => 'image/x-freehand',
		'fh4' => 'image/x-freehand',
		'fh5' => 'image/x-freehand',
		'fh7' => 'image/x-freehand',
		'fhc' => 'image/x-freehand',
		'fig' => 'application/x-xfig',
		'flac' => 'audio/x-flac',
		'fli' => 'video/x-fli',
		'flo' => 'application/vnd.micrografx.flo',
		'flv' => 'video/x-flv',
		'flw' => 'application/vnd.kde.kivio',
		'flx' => 'text/vnd.fmi.flexstor',
		'fly' => 'text/vnd.fly',
		'fm' => 'application/vnd.framemaker',
		'fnc' => 'application/vnd.frogans.fnc',
		'for' => 'text/x-fortran',
		'fpx' => 'image/vnd.fpx',
		'frame' => 'application/vnd.framemaker',
		'fsc' => 'application/vnd.fsc.weblaunch',
		'fst' => 'image/vnd.fst',
		'ftc' => 'application/vnd.fluxtime.clip',
		'fti' => 'application/vnd.anser-web-funds-transfer-initiation',
		'fvt' => 'video/vnd.fvt',
		'fxp' => 'application/vnd.adobe.fxp',
		'fxpl' => 'application/vnd.adobe.fxp',
		'fzs' => 'application/vnd.fuzzysheet',
		'g2w' => 'application/vnd.geoplan',
		'g3' => 'image/g3fax',
		'g3w' => 'application/vnd.geospace',
		'gac' => 'application/vnd.groove-account',
		'gam' => 'application/x-tads',
		'gbr' => 'application/rpki-ghostbusters',
		'gca' => 'application/x-gca-compressed',
		'gdl' => 'model/vnd.gdl',
		'geo' => 'application/vnd.dynageo',
		'gex' => 'application/vnd.geometry-explorer',
		'ggb' => 'application/vnd.geogebra.file',
		'ggt' => 'application/vnd.geogebra.tool',
		'ghf' => 'application/vnd.groove-help',
		'gif' => 'image/gif',
		'gim' => 'application/vnd.groove-identity-message',
		'gml' => 'application/gml+xml',
		'gmx' => 'application/vnd.gmx',
		'gnumeric' => 'application/x-gnumeric',
		'gph' => 'application/vnd.flographit',
		'gpx' => 'application/gpx+xml',
		'gqf' => 'application/vnd.grafeq',
		'gqs' => 'application/vnd.grafeq',
		'gram' => 'application/srgs',
		'gramps' => 'application/x-gramps-xml',
		'gre' => 'application/vnd.geometry-explorer',
		'grv' => 'application/vnd.groove-injector',
		'grxml' => 'application/srgs+xml',
		'gsf' => 'application/x-font-ghostscript',
		'gtar' => 'application/x-gtar',
		'gtm' => 'application/vnd.groove-tool-message',
		'gtw' => 'model/vnd.gtw',
		'gv' => 'text/vnd.graphviz',
		'gxf' => 'application/gxf',
		'gxt' => 'application/vnd.geonext',
		'h' => 'text/x-c',
		'h261' => 'video/h261',
		'h263' => 'video/h263',
		'h264' => 'video/h264',
		'hal' => 'application/vnd.hal+xml',
		'hbci' => 'application/vnd.hbci',
		'hdf' => 'application/x-hdf',
		'hh' => 'text/x-c',
		'hlp' => 'application/winhlp',
		'hpgl' => 'application/vnd.hp-hpgl',
		'hpid' => 'application/vnd.hp-hpid',
		'hps' => 'application/vnd.hp-hps',
		'hqx' => 'application/mac-binhex40',
		'htke' => 'application/vnd.kenameaapp',
		'htm' => 'text/html',
		'html' => 'text/html',
		'hvd' => 'application/vnd.yamaha.hv-dic',
		'hvp' => 'application/vnd.yamaha.hv-voice',
		'hvs' => 'application/vnd.yamaha.hv-script',
		'i2g' => 'application/vnd.intergeo',
		'icc' => 'application/vnd.iccprofile',
		'ice' => 'x-conference/x-cooltalk',
		'icm' => 'application/vnd.iccprofile',
		'ico' => 'image/x-icon',
		'ics' => 'text/calendar',
		'ief' => 'image/ief',
		'ifb' => 'text/calendar',
		'ifm' => 'application/vnd.shana.informed.formdata',
		'iges' => 'model/iges',
		'igl' => 'application/vnd.igloader',
		'igm' => 'application/vnd.insors.igm',
		'igs' => 'model/iges',
		'igx' => 'application/vnd.micrografx.igx',
		'iif' => 'application/vnd.shana.informed.interchange',
		'imp' => 'application/vnd.accpac.simply.imp',
		'ims' => 'application/vnd.ms-ims',
		'in' => 'text/plain',
		'ink' => 'application/inkml+xml',
		'inkml' => 'application/inkml+xml',
		'install' => 'application/x-install-instructions',
		'iota' => 'application/vnd.astraea-software.iota',
		'ipfix' => 'application/ipfix',
		'ipk' => 'application/vnd.shana.informed.package',
		'irm' => 'application/vnd.ibm.rights-management',
		'irp' => 'application/vnd.irepository.package+xml',
		'iso' => 'application/x-iso9660-image',
		'itp' => 'application/vnd.shana.informed.formtemplate',
		'ivp' => 'application/vnd.immervision-ivp',
		'ivu' => 'application/vnd.immervision-ivu',
		'jad' => 'text/vnd.sun.j2me.app-descriptor',
		'jam' => 'application/vnd.jam',
		'jar' => 'application/java-archive',
		'java' => 'text/x-java-source',
		'jisp' => 'application/vnd.jisp',
		'jlt' => 'application/vnd.hp-jlyt',
		'jnlp' => 'application/x-java-jnlp-file',
		'joda' => 'application/vnd.joost.joda-archive',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jpgm' => 'video/jpm',
		'jpgv' => 'video/jpeg',
		'jpm' => 'video/jpm',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'jsonml' => 'application/jsonml+json',
		'kar' => 'audio/midi',
		'karbon' => 'application/vnd.kde.karbon',
		'kfo' => 'application/vnd.kde.kformula',
		'kia' => 'application/vnd.kidspiration',
		'kml' => 'application/vnd.google-earth.kml+xml',
		'kmz' => 'application/vnd.google-earth.kmz',
		'kne' => 'application/vnd.kinar',
		'knp' => 'application/vnd.kinar',
		'kon' => 'application/vnd.kde.kontour',
		'kpr' => 'application/vnd.kde.kpresenter',
		'kpt' => 'application/vnd.kde.kpresenter',
		'kpxx' => 'application/vnd.ds-keypoint',
		'ksp' => 'application/vnd.kde.kspread',
		'ktr' => 'application/vnd.kahootz',
		'ktx' => 'image/ktx',
		'ktz' => 'application/vnd.kahootz',
		'kwd' => 'application/vnd.kde.kword',
		'kwt' => 'application/vnd.kde.kword',
		'lasxml' => 'application/vnd.las.las+xml',
		'latex' => 'application/x-latex',
		'lbd' => 'application/vnd.llamagraphics.life-balance.desktop',
		'lbe' => 'application/vnd.llamagraphics.life-balance.exchange+xml',
		'les' => 'application/vnd.hhe.lesson-player',
		'lha' => 'application/x-lzh-compressed',
		'link66' => 'application/vnd.route66.link66+xml',
		'list' => 'text/plain',
		'list3820' => 'application/vnd.ibm.modcap',
		'listafp' => 'application/vnd.ibm.modcap',
		'lnk' => 'application/x-ms-shortcut',
		'log' => 'text/plain',
		'lostxml' => 'application/lost+xml',
		'lrf' => 'application/octet-stream',
		'lrm' => 'application/vnd.ms-lrm',
		'ltf' => 'application/vnd.frogans.ltf',
		'lvp' => 'audio/vnd.lucent.voice',
		'lwp' => 'application/vnd.lotus-wordpro',
		'lzh' => 'application/x-lzh-compressed',
		'm13' => 'application/x-msmediaview',
		'm14' => 'application/x-msmediaview',
		'm1v' => 'video/mpeg',
		'm21' => 'application/mp21',
		'm2a' => 'audio/mpeg',
		'm2v' => 'video/mpeg',
		'm3a' => 'audio/mpeg',
		'm3u' => 'audio/x-mpegurl',
		'm3u8' => 'application/vnd.apple.mpegurl',
		'm4a' => 'audio/mp4',
		'm4u' => 'video/vnd.mpegurl',
		'm4v' => 'video/x-m4v',
		'ma' => 'application/mathematica',
		'mads' => 'application/mads+xml',
		'mag' => 'application/vnd.ecowin.chart',
		'maker' => 'application/vnd.framemaker',
		'man' => 'text/troff',
		'mar' => 'application/octet-stream',
		'mathml' => 'application/mathml+xml',
		'mb' => 'application/mathematica',
		'mbk' => 'application/vnd.mobius.mbk',
		'mbox' => 'application/mbox',
		'mc1' => 'application/vnd.medcalcdata',
		'mcd' => 'application/vnd.mcd',
		'mcurl' => 'text/vnd.curl.mcurl',
		'mdb' => 'application/x-msaccess',
		'mdi' => 'image/vnd.ms-modi',
		'me' => 'text/troff',
		'mesh' => 'model/mesh',
		'meta4' => 'application/metalink4+xml',
		'metalink' => 'application/metalink+xml',
		'mets' => 'application/mets+xml',
		'mfm' => 'application/vnd.mfmp',
		'mft' => 'application/rpki-manifest',
		'mgp' => 'application/vnd.osgeo.mapguide.package',
		'mgz' => 'application/vnd.proteus.magazine',
		'mid' => 'audio/midi',
		'midi' => 'audio/midi',
		'mie' => 'application/x-mie',
		'mif' => 'application/vnd.mif',
		'mime' => 'message/rfc822',
		'mj2' => 'video/mj2',
		'mjp2' => 'video/mj2',
		'mk3d' => 'video/x-matroska',
		'mka' => 'audio/x-matroska',
		'mks' => 'video/x-matroska',
		'mkv' => 'video/x-matroska',
		'mlp' => 'application/vnd.dolby.mlp',
		'mmd' => 'application/vnd.chipnuts.karaoke-mmd',
		'mmf' => 'application/vnd.smaf',
		'mmr' => 'image/vnd.fujixerox.edmics-mmr',
		'mng' => 'video/x-mng',
		'mny' => 'application/x-msmoney',
		'mobi' => 'application/x-mobipocket-ebook',
		'mods' => 'application/mods+xml',
		'mov' => 'video/quicktime',
		'movie' => 'video/x-sgi-movie',
		'mp2' => 'audio/mpeg',
		'mp21' => 'application/mp21',
		'mp2a' => 'audio/mpeg',
		'mp3' => 'audio/mpeg',
		'mp4' => 'video/mp4',
		'mp4a' => 'audio/mp4',
		'mp4s' => 'application/mp4',
		'mp4v' => 'video/mp4',
		'mpc' => 'application/vnd.mophun.certificate',
		'mpe' => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpg4' => 'video/mp4',
		'mpga' => 'audio/mpeg',
		'mpkg' => 'application/vnd.apple.installer+xml',
		'mpm' => 'application/vnd.blueice.multipass',
		'mpn' => 'application/vnd.mophun.application',
		'mpp' => 'application/vnd.ms-project',
		'mpt' => 'application/vnd.ms-project',
		'mpy' => 'application/vnd.ibm.minipay',
		'mqy' => 'application/vnd.mobius.mqy',
		'mrc' => 'application/marc',
		'mrcx' => 'application/marcxml+xml',
		'ms' => 'text/troff',
		'mscml' => 'application/mediaservercontrol+xml',
		'mseed' => 'application/vnd.fdsn.mseed',
		'mseq' => 'application/vnd.mseq',
		'msf' => 'application/vnd.epson.msf',
		'msh' => 'model/mesh',
		'msi' => 'application/x-msdownload',
		'msl' => 'application/vnd.mobius.msl',
		'msty' => 'application/vnd.muvee.style',
		'mts' => 'model/vnd.mts',
		'mus' => 'application/vnd.musician',
		'musicxml' => 'application/vnd.recordare.musicxml+xml',
		'mvb' => 'application/x-msmediaview',
		'mwf' => 'application/vnd.mfer',
		'mxf' => 'application/mxf',
		'mxl' => 'application/vnd.recordare.musicxml',
		'mxml' => 'application/xv+xml',
		'mxs' => 'application/vnd.triscape.mxs',
		'mxu' => 'video/vnd.mpegurl',
		'n-gage' => 'application/vnd.nokia.n-gage.symbian.install',
		'n3' => 'text/n3',
		'nb' => 'application/mathematica',
		'nbp' => 'application/vnd.wolfram.player',
		'nc' => 'application/x-netcdf',
		'ncx' => 'application/x-dtbncx+xml',
		'nfo' => 'text/x-nfo',
		'ngdat' => 'application/vnd.nokia.n-gage.data',
		'nitf' => 'application/vnd.nitf',
		'nlu' => 'application/vnd.neurolanguage.nlu',
		'nml' => 'application/vnd.enliven',
		'nnd' => 'application/vnd.noblenet-directory',
		'nns' => 'application/vnd.noblenet-sealer',
		'nnw' => 'application/vnd.noblenet-web',
		'npx' => 'image/vnd.net-fpx',
		'nsc' => 'application/x-conference',
		'nsf' => 'application/vnd.lotus-notes',
		'ntf' => 'application/vnd.nitf',
		'nzb' => 'application/x-nzb',
		'oa2' => 'application/vnd.fujitsu.oasys2',
		'oa3' => 'application/vnd.fujitsu.oasys3',
		'oas' => 'application/vnd.fujitsu.oasys',
		'obd' => 'application/x-msbinder',
		'obj' => 'application/x-tgif',
		'oda' => 'application/oda',
		'odb' => 'application/vnd.oasis.opendocument.database',
		'odc' => 'application/vnd.oasis.opendocument.chart',
		'odf' => 'application/vnd.oasis.opendocument.formula',
		'odft' => 'application/vnd.oasis.opendocument.formula-template',
		'odg' => 'application/vnd.oasis.opendocument.graphics',
		'odi' => 'application/vnd.oasis.opendocument.image',
		'odm' => 'application/vnd.oasis.opendocument.text-master',
		'odp' => 'application/vnd.oasis.opendocument.presentation',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'odt' => 'application/vnd.oasis.opendocument.text',
		'oga' => 'audio/ogg',
		'ogg' => 'audio/ogg',
		'ogv' => 'video/ogg',
		'ogx' => 'application/ogg',
		'omdoc' => 'application/omdoc+xml',
		'onepkg' => 'application/onenote',
		'onetmp' => 'application/onenote',
		'onetoc' => 'application/onenote',
		'onetoc2' => 'application/onenote',
		'opf' => 'application/oebps-package+xml',
		'opml' => 'text/x-opml',
		'oprc' => 'application/vnd.palm',
		'org' => 'application/vnd.lotus-organizer',
		'osf' => 'application/vnd.yamaha.openscoreformat',
		'osfpvg' => 'application/vnd.yamaha.openscoreformat.osfpvg+xml',
		'otc' => 'application/vnd.oasis.opendocument.chart-template',
		'otf' => 'application/x-font-otf',
		'otg' => 'application/vnd.oasis.opendocument.graphics-template',
		'oth' => 'application/vnd.oasis.opendocument.text-web',
		'oti' => 'application/vnd.oasis.opendocument.image-template',
		'otp' => 'application/vnd.oasis.opendocument.presentation-template',
		'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
		'ott' => 'application/vnd.oasis.opendocument.text-template',
		'oxps' => 'application/oxps',
		'oxt' => 'application/vnd.openofficeorg.extension',
		'p' => 'text/x-pascal',
		'p10' => 'application/pkcs10',
		'p12' => 'application/x-pkcs12',
		'p7b' => 'application/x-pkcs7-certificates',
		'p7c' => 'application/pkcs7-mime',
		'p7m' => 'application/pkcs7-mime',
		'p7r' => 'application/x-pkcs7-certreqresp',
		'p7s' => 'application/pkcs7-signature',
		'p8' => 'application/pkcs8',
		'pas' => 'text/x-pascal',
		'paw' => 'application/vnd.pawaafile',
		'pbd' => 'application/vnd.powerbuilder6',
		'pbm' => 'image/x-portable-bitmap',
		'pcap' => 'application/vnd.tcpdump.pcap',
		'pcf' => 'application/x-font-pcf',
		'pcl' => 'application/vnd.hp-pcl',
		'pclxl' => 'application/vnd.hp-pclxl',
		'pct' => 'image/x-pict',
		'pcurl' => 'application/vnd.curl.pcurl',
		'pcx' => 'image/x-pcx',
		'pdb' => 'application/vnd.palm',
		'pdf' => 'application/pdf',
		'pfa' => 'application/x-font-type1',
		'pfb' => 'application/x-font-type1',
		'pfm' => 'application/x-font-type1',
		'pfr' => 'application/font-tdpfr',
		'pfx' => 'application/x-pkcs12',
		'pgm' => 'image/x-portable-graymap',
		'pgn' => 'application/x-chess-pgn',
		'pgp' => 'application/pgp-encrypted',
		'pic' => 'image/x-pict',
		'pkg' => 'application/octet-stream',
		'pki' => 'application/pkixcmp',
		'pkipath' => 'application/pkix-pkipath',
		'plb' => 'application/vnd.3gpp.pic-bw-large',
		'plc' => 'application/vnd.mobius.plc',
		'plf' => 'application/vnd.pocketlearn',
		'pls' => 'application/pls+xml',
		'pml' => 'application/vnd.ctc-posml',
		'png' => 'image/png',
		'pnm' => 'image/x-portable-anymap',
		'portpkg' => 'application/vnd.macports.portpkg',
		'pot' => 'application/vnd.ms-powerpoint',
		'potm' => 'application/vnd.ms-powerpoint.template.macroenabled.12',
		'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
		'ppam' => 'application/vnd.ms-powerpoint.addin.macroenabled.12',
		'ppd' => 'application/vnd.cups-ppd',
		'ppm' => 'image/x-portable-pixmap',
		'pps' => 'application/vnd.ms-powerpoint',
		'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
		'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'ppt' => 'application/vnd.ms-powerpoint',
		'pptm' => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'pqa' => 'application/vnd.palm',
		'prc' => 'application/x-mobipocket-ebook',
		'pre' => 'application/vnd.lotus-freelance',
		'prf' => 'application/pics-rules',
		'ps' => 'application/postscript',
		'psb' => 'application/vnd.3gpp.pic-bw-small',
		'psd' => 'image/vnd.adobe.photoshop',
		'psf' => 'application/x-font-linux-psf',
		'pskcxml' => 'application/pskc+xml',
		'ptid' => 'application/vnd.pvi.ptid1',
		'pub' => 'application/x-mspublisher',
		'pvb' => 'application/vnd.3gpp.pic-bw-var',
		'pwn' => 'application/vnd.3m.post-it-notes',
		'pya' => 'audio/vnd.ms-playready.media.pya',
		'pyv' => 'video/vnd.ms-playready.media.pyv',
		'qam' => 'application/vnd.epson.quickanime',
		'qbo' => 'application/vnd.intu.qbo',
		'qfx' => 'application/vnd.intu.qfx',
		'qps' => 'application/vnd.publishare-delta-tree',
		'qt' => 'video/quicktime',
		'qwd' => 'application/vnd.quark.quarkxpress',
		'qwt' => 'application/vnd.quark.quarkxpress',
		'qxb' => 'application/vnd.quark.quarkxpress',
		'qxd' => 'application/vnd.quark.quarkxpress',
		'qxl' => 'application/vnd.quark.quarkxpress',
		'qxt' => 'application/vnd.quark.quarkxpress',
		'ra' => 'audio/x-pn-realaudio',
		'ram' => 'audio/x-pn-realaudio',
		'rar' => 'application/x-rar-compressed',
		'ras' => 'image/x-cmu-raster',
		'rcprofile' => 'application/vnd.ipunplugged.rcprofile',
		'rdf' => 'application/rdf+xml',
		'rdz' => 'application/vnd.data-vision.rdz',
		'rep' => 'application/vnd.businessobjects',
		'res' => 'application/x-dtbresource+xml',
		'rgb' => 'image/x-rgb',
		'rif' => 'application/reginfo+xml',
		'rip' => 'audio/vnd.rip',
		'ris' => 'application/x-research-info-systems',
		'rl' => 'application/resource-lists+xml',
		'rlc' => 'image/vnd.fujixerox.edmics-rlc',
		'rld' => 'application/resource-lists-diff+xml',
		'rm' => 'application/vnd.rn-realmedia',
		'rmi' => 'audio/midi',
		'rmp' => 'audio/x-pn-realaudio-plugin',
		'rms' => 'application/vnd.jcp.javame.midlet-rms',
		'rmvb' => 'application/vnd.rn-realmedia-vbr',
		'rnc' => 'application/relax-ng-compact-syntax',
		'roa' => 'application/rpki-roa',
		'roff' => 'text/troff',
		'rp9' => 'application/vnd.cloanto.rp9',
		'rpss' => 'application/vnd.nokia.radio-presets',
		'rpst' => 'application/vnd.nokia.radio-preset',
		'rq' => 'application/sparql-query',
		'rs' => 'application/rls-services+xml',
		'rsd' => 'application/rsd+xml',
		'rss' => 'application/rss+xml',
		'rtf' => 'application/rtf',
		'rtx' => 'text/richtext',
		's' => 'text/x-asm',
		's3m' => 'audio/s3m',
		'saf' => 'application/vnd.yamaha.smaf-audio',
		'sbml' => 'application/sbml+xml',
		'sc' => 'application/vnd.ibm.secure-container',
		'scd' => 'application/x-msschedule',
		'scm' => 'application/vnd.lotus-screencam',
		'scq' => 'application/scvp-cv-request',
		'scs' => 'application/scvp-cv-response',
		'scurl' => 'text/vnd.curl.scurl',
		'sda' => 'application/vnd.stardivision.draw',
		'sdc' => 'application/vnd.stardivision.calc',
		'sdd' => 'application/vnd.stardivision.impress',
		'sdkd' => 'application/vnd.solent.sdkm+xml',
		'sdkm' => 'application/vnd.solent.sdkm+xml',
		'sdp' => 'application/sdp',
		'sdw' => 'application/vnd.stardivision.writer',
		'see' => 'application/vnd.seemail',
		'seed' => 'application/vnd.fdsn.seed',
		'sema' => 'application/vnd.sema',
		'semd' => 'application/vnd.semd',
		'semf' => 'application/vnd.semf',
		'ser' => 'application/java-serialized-object',
		'setpay' => 'application/set-payment-initiation',
		'setreg' => 'application/set-registration-initiation',
		'sfd-hdstx' => 'application/vnd.hydrostatix.sof-data',
		'sfs' => 'application/vnd.spotfire.sfs',
		'sfv' => 'text/x-sfv',
		'sgi' => 'image/sgi',
		'sgl' => 'application/vnd.stardivision.writer-global',
		'sgm' => 'text/sgml',
		'sgml' => 'text/sgml',
		'sh' => 'application/x-sh',
		'shar' => 'application/x-shar',
		'shf' => 'application/shf+xml',
		'sid' => 'image/x-mrsid-image',
		'sig' => 'application/pgp-signature',
		'sil' => 'audio/silk',
		'silo' => 'model/mesh',
		'sis' => 'application/vnd.symbian.install',
		'sisx' => 'application/vnd.symbian.install',
		'sit' => 'application/x-stuffit',
		'sitx' => 'application/x-stuffitx',
		'skd' => 'application/vnd.koan',
		'skm' => 'application/vnd.koan',
		'skp' => 'application/vnd.koan',
		'skt' => 'application/vnd.koan',
		'sldm' => 'application/vnd.ms-powerpoint.slide.macroenabled.12',
		'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
		'slt' => 'application/vnd.epson.salt',
		'sm' => 'application/vnd.stepmania.stepchart',
		'smf' => 'application/vnd.stardivision.math',
		'smi' => 'application/smil+xml',
		'smil' => 'application/smil+xml',
		'smv' => 'video/x-smv',
		'smzip' => 'application/vnd.stepmania.package',
		'snd' => 'audio/basic',
		'snf' => 'application/x-font-snf',
		'so' => 'application/octet-stream',
		'spc' => 'application/x-pkcs7-certificates',
		'spf' => 'application/vnd.yamaha.smaf-phrase',
		'spl' => 'application/x-futuresplash',
		'spot' => 'text/vnd.in3d.spot',
		'spp' => 'application/scvp-vp-response',
		'spq' => 'application/scvp-vp-request',
		'spx' => 'audio/ogg',
		'sql' => 'application/x-sql',
		'src' => 'application/x-wais-source',
		'srt' => 'application/x-subrip',
		'sru' => 'application/sru+xml',
		'srx' => 'application/sparql-results+xml',
		'ssdl' => 'application/ssdl+xml',
		'sse' => 'application/vnd.kodak-descriptor',
		'ssf' => 'application/vnd.epson.ssf',
		'ssml' => 'application/ssml+xml',
		'st' => 'application/vnd.sailingtracker.track',
		'stc' => 'application/vnd.sun.xml.calc.template',
		'std' => 'application/vnd.sun.xml.draw.template',
		'stf' => 'application/vnd.wt.stf',
		'sti' => 'application/vnd.sun.xml.impress.template',
		'stk' => 'application/hyperstudio',
		'stl' => 'application/vnd.ms-pki.stl',
		'str' => 'application/vnd.pg.format',
		'stw' => 'application/vnd.sun.xml.writer.template',
		'sub' => 'image/vnd.dvb.subtitle',
		'sub' => 'text/vnd.dvb.subtitle',
		'sus' => 'application/vnd.sus-calendar',
		'susp' => 'application/vnd.sus-calendar',
		'sv4cpio' => 'application/x-sv4cpio',
		'sv4crc' => 'application/x-sv4crc',
		'svc' => 'application/vnd.dvb.service',
		'svd' => 'application/vnd.svd',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',
		'swa' => 'application/x-director',
		'swf' => 'application/x-shockwave-flash',
		'swi' => 'application/vnd.aristanetworks.swi',
		'sxc' => 'application/vnd.sun.xml.calc',
		'sxd' => 'application/vnd.sun.xml.draw',
		'sxg' => 'application/vnd.sun.xml.writer.global',
		'sxi' => 'application/vnd.sun.xml.impress',
		'sxm' => 'application/vnd.sun.xml.math',
		'sxw' => 'application/vnd.sun.xml.writer',
		't' => 'text/troff',
		't3' => 'application/x-t3vm-image',
		'taglet' => 'application/vnd.mynfc',
		'tao' => 'application/vnd.tao.intent-module-archive',
		'tar' => 'application/x-tar',
		'tcap' => 'application/vnd.3gpp2.tcap',
		'tcl' => 'application/x-tcl',
		'teacher' => 'application/vnd.smart.teacher',
		'tei' => 'application/tei+xml',
		'teicorpus' => 'application/tei+xml',
		'tex' => 'application/x-tex',
		'texi' => 'application/x-texinfo',
		'texinfo' => 'application/x-texinfo',
		'text' => 'text/plain',
		'tfi' => 'application/thraud+xml',
		'tfm' => 'application/x-tex-tfm',
		'tga' => 'image/x-tga',
		'thmx' => 'application/vnd.ms-officetheme',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		'tmo' => 'application/vnd.tmobile-livetv',
		'torrent' => 'application/x-bittorrent',
		'tpl' => 'application/vnd.groove-tool-template',
		'tpt' => 'application/vnd.trid.tpt',
		'tr' => 'text/troff',
		'tra' => 'application/vnd.trueapp',
		'trm' => 'application/x-msterminal',
		'tsd' => 'application/timestamped-data',
		'tsv' => 'text/tab-separated-values',
		'ttc' => 'application/x-font-ttf',
		'ttf' => 'application/x-font-ttf',
		'ttl' => 'text/turtle',
		'twd' => 'application/vnd.simtech-mindmapper',
		'twds' => 'application/vnd.simtech-mindmapper',
		'txd' => 'application/vnd.genomatix.tuxedo',
		'txf' => 'application/vnd.mobius.txf',
		'txt' => 'text/plain',
		'u32' => 'application/x-authorware-bin',
		'udeb' => 'application/x-debian-package',
		'ufd' => 'application/vnd.ufdl',
		'ufdl' => 'application/vnd.ufdl',
		'ulx' => 'application/x-glulx',
		'umj' => 'application/vnd.umajin',
		'unityweb' => 'application/vnd.unity',
		'uoml' => 'application/vnd.uoml+xml',
		'uri' => 'text/uri-list',
		'uris' => 'text/uri-list',
		'urls' => 'text/uri-list',
		'ustar' => 'application/x-ustar',
		'utz' => 'application/vnd.uiq.theme',
		'uu' => 'text/x-uuencode',
		'uva' => 'audio/vnd.dece.audio',
		'uvd' => 'application/vnd.dece.data',
		'uvf' => 'application/vnd.dece.data',
		'uvg' => 'image/vnd.dece.graphic',
		'uvh' => 'video/vnd.dece.hd',
		'uvi' => 'image/vnd.dece.graphic',
		'uvm' => 'video/vnd.dece.mobile',
		'uvp' => 'video/vnd.dece.pd',
		'uvs' => 'video/vnd.dece.sd',
		'uvt' => 'application/vnd.dece.ttml+xml',
		'uvu' => 'video/vnd.uvvu.mp4',
		'uvv' => 'video/vnd.dece.video',
		'uvva' => 'audio/vnd.dece.audio',
		'uvvd' => 'application/vnd.dece.data',
		'uvvf' => 'application/vnd.dece.data',
		'uvvg' => 'image/vnd.dece.graphic',
		'uvvh' => 'video/vnd.dece.hd',
		'uvvi' => 'image/vnd.dece.graphic',
		'uvvm' => 'video/vnd.dece.mobile',
		'uvvp' => 'video/vnd.dece.pd',
		'uvvs' => 'video/vnd.dece.sd',
		'uvvt' => 'application/vnd.dece.ttml+xml',
		'uvvu' => 'video/vnd.uvvu.mp4',
		'uvvv' => 'video/vnd.dece.video',
		'uvvx' => 'application/vnd.dece.unspecified',
		'uvvz' => 'application/vnd.dece.zip',
		'uvx' => 'application/vnd.dece.unspecified',
		'uvz' => 'application/vnd.dece.zip',
		'vcard' => 'text/vcard',
		'vcd' => 'application/x-cdlink',
		'vcf' => 'text/x-vcard',
		'vcg' => 'application/vnd.groove-vcard',
		'vcs' => 'text/x-vcalendar',
		'vcx' => 'application/vnd.vcx',
		'vis' => 'application/vnd.visionary',
		'viv' => 'video/vnd.vivo',
		'vob' => 'video/x-ms-vob',
		'vor' => 'application/vnd.stardivision.writer',
		'vox' => 'application/x-authorware-bin',
		'vrml' => 'model/vrml',
		'vsd' => 'application/vnd.visio',
		'vsf' => 'application/vnd.vsf',
		'vss' => 'application/vnd.visio',
		'vst' => 'application/vnd.visio',
		'vsw' => 'application/vnd.visio',
		'vtu' => 'model/vnd.vtu',
		'vxml' => 'application/voicexml+xml',
		'w3d' => 'application/x-director',
		'wad' => 'application/x-doom',
		'wav' => 'audio/x-wav',
		'wax' => 'audio/x-ms-wax',
		'wbmp' => 'image/vnd.wap.wbmp',
		'wbs' => 'application/vnd.criticaltools.wbs+xml',
		'wbxml' => 'application/vnd.wap.wbxml',
		'wcm' => 'application/vnd.ms-works',
		'wdb' => 'application/vnd.ms-works',
		'wdp' => 'image/vnd.ms-photo',
		'weba' => 'audio/webm',
		'webm' => 'video/webm',
		'webp' => 'image/webp',
		'wg' => 'application/vnd.pmi.widget',
		'wgt' => 'application/widget',
		'wks' => 'application/vnd.ms-works',
		'wm' => 'video/x-ms-wm',
		'wma' => 'audio/x-ms-wma',
		'wmd' => 'application/x-ms-wmd',
		'wmf' => 'application/x-msmetafile',
		'wml' => 'text/vnd.wap.wml',
		'wmlc' => 'application/vnd.wap.wmlc',
		'wmls' => 'text/vnd.wap.wmlscript',
		'wmlsc' => 'application/vnd.wap.wmlscriptc',
		'wmv' => 'video/x-ms-wmv',
		'wmx' => 'video/x-ms-wmx',
		'wmz' => 'application/x-ms-wmz',
		'wmz' => 'application/x-msmetafile',
		'woff' => 'application/font-woff',
		'wpd' => 'application/vnd.wordperfect',
		'wpl' => 'application/vnd.ms-wpl',
		'wps' => 'application/vnd.ms-works',
		'wqd' => 'application/vnd.wqd',
		'wri' => 'application/x-mswrite',
		'wrl' => 'model/vrml',
		'wsdl' => 'application/wsdl+xml',
		'wspolicy' => 'application/wspolicy+xml',
		'wtb' => 'application/vnd.webturbo',
		'wvx' => 'video/x-ms-wvx',
		'x32' => 'application/x-authorware-bin',
		'x3d' => 'model/x3d+xml',
		'x3db' => 'model/x3d+binary',
		'x3dbz' => 'model/x3d+binary',
		'x3dv' => 'model/x3d+vrml',
		'x3dvz' => 'model/x3d+vrml',
		'x3dz' => 'model/x3d+xml',
		'xaml' => 'application/xaml+xml',
		'xap' => 'application/x-silverlight-app',
		'xar' => 'application/vnd.xara',
		'xbap' => 'application/x-ms-xbap',
		'xbd' => 'application/vnd.fujixerox.docuworks.binder',
		'xbm' => 'image/x-xbitmap',
		'xdf' => 'application/xcap-diff+xml',
		'xdm' => 'application/vnd.syncml.dm+xml',
		'xdp' => 'application/vnd.adobe.xdp+xml',
		'xdssc' => 'application/dssc+xml',
		'xdw' => 'application/vnd.fujixerox.docuworks',
		'xenc' => 'application/xenc+xml',
		'xer' => 'application/patch-ops-error+xml',
		'xfdf' => 'application/vnd.adobe.xfdf',
		'xfdl' => 'application/vnd.xfdl',
		'xht' => 'application/xhtml+xml',
		'xhtml' => 'application/xhtml+xml',
		'xhvml' => 'application/xv+xml',
		'xif' => 'image/vnd.xiff',
		'xla' => 'application/vnd.ms-excel',
		'xlam' => 'application/vnd.ms-excel.addin.macroenabled.12',
		'xlc' => 'application/vnd.ms-excel',
		'xlf' => 'application/x-xliff+xml',
		'xlm' => 'application/vnd.ms-excel',
		'xls' => 'application/vnd.ms-excel',
		'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
		'xlsm' => 'application/vnd.ms-excel.sheet.macroenabled.12',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xlt' => 'application/vnd.ms-excel',
		'xltm' => 'application/vnd.ms-excel.template.macroenabled.12',
		'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
		'xlw' => 'application/vnd.ms-excel',
		'xm' => 'audio/xm',
		'xml' => 'application/xml',
		'xo' => 'application/vnd.olpc-sugar',
		'xop' => 'application/xop+xml',
		'xpi' => 'application/x-xpinstall',
		'xpl' => 'application/xproc+xml',
		'xpm' => 'image/x-xpixmap',
		'xpr' => 'application/vnd.is-xpr',
		'xps' => 'application/vnd.ms-xpsdocument',
		'xpw' => 'application/vnd.intercon.formnet',
		'xpx' => 'application/vnd.intercon.formnet',
		'xsl' => 'application/xml',
		'xslt' => 'application/xslt+xml',
		'xsm' => 'application/vnd.syncml+xml',
		'xspf' => 'application/xspf+xml',
		'xul' => 'application/vnd.mozilla.xul+xml',
		'xvm' => 'application/xv+xml',
		'xvml' => 'application/xv+xml',
		'xwd' => 'image/x-xwindowdump',
		'xyz' => 'chemical/x-xyz',
		'xz' => 'application/x-xz',
		'yang' => 'application/yang',
		'yin' => 'application/yin+xml',
		'z1' => 'application/x-zmachine',
		'z2' => 'application/x-zmachine',
		'z3' => 'application/x-zmachine',
		'z4' => 'application/x-zmachine',
		'z5' => 'application/x-zmachine',
		'z6' => 'application/x-zmachine',
		'z7' => 'application/x-zmachine',
		'z8' => 'application/x-zmachine',
		'zaz' => 'application/vnd.zzazz.deck+xml',
		'zip' => 'application/zip',
		'zir' => 'application/vnd.zul',
		'zirz' => 'application/vnd.zul',
		'zmm' => 'application/vnd.handheld-entertainment+xml'
	);
}

// Always run this
if(strpos($_SERVER['SCRIPT_NAME'], 'check.php') == false){
	dependCheck();
}
