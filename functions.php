<?php

// ===================================
// Define Version
 define('INSTALLEDVERSION', '1.42');
// ===================================
use Kryptonit3\Sonarr\Sonarr;
use Kryptonit3\SickRage\SickRage;

// Debugging output functions
function debug_out($variable, $die = false) {
	$trace = debug_backtrace()[0];
	echo "<center><img height='200px' src='images/confused.png'></center>";
	echo "<center>Look's like something happened, here are the errors and perhaps how to fix them:</center>";
	echo '<pre style="white-space: pre-line; background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">'.$trace['file'].':'.$trace['line']."\n\n".print_r($variable, true).'</pre>';
	if ($die) { http_response_code(503); die(); }
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
		if ($bind = ldap_bind($ldap, AUTHBACKENDDOMAIN.'\\'.$username, $password)) {
   			writeLog("success", "LDAP authentication success"); 
			return true;
		} else {
   			writeLog("error", "LDPA could not authenticate"); 
			return false;
		}
  		writeLog("error", "LDPA could not authenticate");      
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
		$userIds = json_decode(file_get_contents($embyAddress.'/Users?api_key='.EMBYTOKEN),true);
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
		if ((strtolower(PLEXUSERNAME) == strtolower($username)) && $password == PLEXPASSWORD) {
   			writeLog("success", $username." authenticated by plex");
			return true;
		}
		
		//Get User List
		$userURL = 'https://plex.tv/pms/friends/all';
		$userHeaders = array(
			'Authorization' => 'Basic '.base64_encode(PLEXUSERNAME.':'.PLEXPASSWORD), 
		);
		$userXML = simplexml_load_string(curl_get($userURL, $userHeaders));
		
		if (is_array($userXML) || is_object($userXML)) {
			$isUser = false;
			$usernameLower = strtolower($username);
			foreach($userXML AS $child) {
				if(isset($child['username']) && strtolower($child['username']) == $usernameLower) {
					$isUser = true;
     				writeLog("success", $usernameLower." was found in plex friends list");
					break;
				}
			}
			
			if ($isUser) {
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
					if (is_array($json) && isset($json['user']) && isset($json['user']['username']) && strtolower($json['user']['username']) == $usernameLower) {
						writeLog("success", $json['user']['username']." was logged into organizr using plex credentials");
                        return array(
							'email' => $json['user']['email'],
							'image' => $json['user']['thumb']
						);
					}
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
			case 'application/x-www-form-urlencoded';
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
	$itemDetails = json_decode(file_get_contents($address.'/Items?Ids='.$item['Id'].'&api_key='.$token),true)['Items'][0];
	if (substr_count(EMBYURL, ':') == 2) {
		$URL = "http://app.emby.media/itemdetails.html?id=".$itemDetails['Id'];
	}else{
		$URL = EMBYURL."/web/itemdetails.html?id=".$itemDetails['Id'];
	}
	//$URL = "http://app.emby.media/itemdetails.html?id=".$itemDetails['Id'];
	switch ($itemDetails['Type']) {
		case 'Episode':
			$title = (isset($itemDetails['SeriesName'])?$itemDetails['SeriesName']:"");
			$imageId = (isset($itemDetails['SeriesId'])?$itemDetails['SeriesId']:$itemDetails['Id']);
			$width = 300;
			$style = '';
			$image = 'slick-image-tall';
			if(!$nowPlaying){ 
				$imageType = (isset($itemDetails['ImageTags']['Primary']) ? "Primary" : false);
				$key = $itemDetails['Id'] . "-list";
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
					'stream' => "&nbsp;".streamType($stream),
					'video' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "video"),
					'audio' => "&nbsp;".streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
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
					'stream' => "&nbsp;".streamType($stream),
					'audio' => "&nbsp;".streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
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
					'stream' => "&nbsp;".streamType($stream),
					'video' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "video"),
					'audio' => "&nbsp;".streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
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
					'stream' => "&nbsp;".streamType($stream),
					'video' => streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "video"),
					'audio' => "&nbsp;".streamType($stream)." ".embyArray($moreInfo['NowPlayingItem']['MediaStreams'], "audio"),
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
    
	if (file_exists('images/cache/'.$key.'.jpg')){ $image_url = 'images/cache/'.$key.'.jpg'; }
    if (file_exists('images/cache/'.$key.'.jpg') && (time() - 604800) > filemtime('images/cache/'.$key.'.jpg') || !file_exists('images/cache/'.$key.'.jpg')) {
        $image_url = 'ajax.php?a=emby-image&type='.$imageType.'&img='.$imageId.'&height='.$height.'&width='.$width.'&key='.$key.'';        
    }
    
    if($nowPlaying){
        if(!$imageType){ $image_url = "images/no-np.png"; $key = "no-np"; }
        if(!$imageId){ $image_url = "images/no-np.png"; $key = "no-np"; }
    }else{
        if(!$imageType){ $image_url = "images/no-list.png"; $key = "no-list"; }
        if(!$imageId){ $image_url = "images/no-list.png"; $key = "no-list"; }
    }
    if(isset($useImage)){ $image_url = $useImage; }
	
	// Assemble Item And Cache Into Array     
	if($nowPlaying){
    	//prettyPrint($itemDetails);
    	return '<div class="col-sm-6 col-md-3"><div class="thumbnail ultra-widget"><div style="display: none;" np="'.$id.'" class="overlay content-box small-box gray-bg">'.$streamInfo.'</div><span class="w-refresh w-p-icon gray" link="'.$id.'"><span class="fa-stack fa-lg" style="font-size: .5em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-info-circle fa-stack-1x fa-inverse"></i></span></span><a href="'.$URL.'" target="_blank"><img style="width: 100%; display:inherit;" src="'.$image_url.'" alt="'.$itemDetails['Name'].'"></a><div class="progress progress-bar-sm zero-m"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$watched.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$watched.'%"></div><div class="progress-bar palette-Grey-500 bg" style="width: 0%"></div></div><div class="caption"><i style="float:left" class="fa fa-'.$state.'"></i>'.$topTitle.''.$bottomTitle.'</div></div></div>';
    }else{
		 return '<div class="item-'.$itemDetails['Type'].'"><a href="'.$URL.'" target="_blank"><img alt="'.$itemDetails['Name'].'" class="'.$image.'" data-lazy="'.$image_url.'"></a><small style="margin-right: 13px" class="elip">'.$title.'</small></div>';
	}
}

// Format item from Plex for Carousel
function resolvePlexItem($server, $token, $item, $nowPlaying = false, $showNames = false, $role = false, $playlist = false) {
    // Static Height
    $height = 444;  
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
                    'stream' => "&nbsp;".streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => "&nbsp;".streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
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
                    'stream' => "&nbsp;".streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => "&nbsp;".streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
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
                    'stream' => "&nbsp;".streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => "&nbsp;".streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
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
                    'stream' => "&nbsp;".streamType($item->Media->Part['decision']),
                    'audio' => "&nbsp;".streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['channels']."ch)",
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
                    'stream' => "&nbsp;".streamType($item->Media->Part['decision']),
                    'video' => streamType($item->Media->Part->Stream[0]['decision'])." (".$item->Media->Part->Stream[0]['codec'].") (".$item->Media->Part->Stream[0]['width']."x".$item->Media->Part->Stream[0]['height'].")",
                    'audio' => "&nbsp;".streamType($item->Media->Part->Stream[1]['decision'])." (".$item->Media->Part->Stream[1]['codec'].") (".$item->Media->Part->Stream[1]['channels']."ch)",
                ));
                $state = (($item->Player['state'] == "paused") ? "pause" : "play");
                $topTitle = '<h5 class="text-center zero-m elip">'.$title.'</h5>';
                $bottomTitle = '<small class="zero-m">'.$item['year'].'</small>';
                if($showNames == "true"){ $bottomTitle .= '<small class="zero-m pull-right">'.$user.'</small>'; }
            }
		}
	
		if (substr_count(PLEXURL, '.') != 2) {
			$address = "https://app.plex.tv/web/app#!/server/$server/details?key=/library/metadata/".$item['ratingKey'];
		}else{
			$address = PLEXURL."/web/index.html#!/server/$server/details?key=/library/metadata/".$item['ratingKey'];
		}

    // If No Overview
    if (!isset($itemDetails['Overview'])) { $itemDetails['Overview'] = ''; }

    if (file_exists('images/cache/'.$key.'.jpg')){ $image_url = 'images/cache/'.$key.'.jpg'; }
    if (file_exists('images/cache/'.$key.'.jpg') && (time() - 604800) > filemtime('images/cache/'.$key.'.jpg') || !file_exists('images/cache/'.$key.'.jpg')) {
        $image_url = 'ajax.php?a=plex-image&img='.$thumb.'&height='.$height.'&width='.$width.'&key='.$key.'';        
    }
    if($nowPlaying){
        if(!$thumb){ $image_url = "images/no-np.png"; $key = "no-np"; }
    }else{
        if(!$thumb){ $image_url = "images/no-list.png"; $key = "no-list"; }
    }
	if(isset($useImage)){ $image_url = $useImage; }
	$openTab = (PLEXTABNAME) ? "true" : "false";
    // Assemble Item And Cache Into Array 
    if($nowPlaying){
        return '<div class="col-sm-6 col-md-3"><div class="thumbnail ultra-widget"><div style="display: none;" np="'.$id.'" class="overlay content-box small-box gray-bg">'.$streamInfo.'</div><span class="w-refresh w-p-icon gray" link="'.$id.'"><span class="fa-stack fa-lg" style="font-size: .5em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-info-circle fa-stack-1x fa-inverse"></i></span></span><a class="openTab" extraTitle="'.$title.'" extraType="'.$item['type'].'" openTab="'.$openTab.'" href="'.$address.'" target="_blank"><img style="width: 100%; display:inherit;" src="'.$image_url.'" alt="'.$item['Name'].'"></a><div class="progress progress-bar-sm zero-m"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$watched.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$watched.'%"></div><div class="progress-bar palette-Grey-500 bg" style="width: '.$transcoded.'%"></div></div><div class="caption"><i style="float:left" class="fa fa-'.$state.'"></i>'.$topTitle.''.$bottomTitle.'</div></div></div>';
    }else{
        return '<div class="item-'.$item['type'].$playlist.'"><a class="openTab" extraTitle="'.$title.'" extraType="'.$item['type'].'" openTab="'.$openTab.'" href="'.$address.'" target="_blank"><img alt="'.$item['Name'].'" class="'.$image.'" data-lazy="'.$image_url.'"></a><small style="margin-right: 13px" class="elip">'.$title.'</small></div>';
    }
}
//$hideMenu .= '<li data-filter="playlist-'.$className.'" data-name="'.$api['title'].'"><a class="js-filter-'.$className.'" href="javascript:void(0)">'.$api['title'].'</a></li>';
//Recent Added
function outputRecentAdded($header, $items, $script = false, $array) {
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
        return '<div id="recentMedia" class="content-box box-shadow big-box"><h5 class="text-center">'.$header.'</h5><p class="text-center">No Media Found</p></div>';
    }else{
		$className = str_replace(' ', '', $header);
        return '<div id="recentMedia" class="content-box box-shadow big-box"><h5 id="recentContent-title" style="margin-bottom: -20px" class="text-center">'.$header.'</h5><div class="recentHeader inbox-pagination '.$className.'">'.$hideMenu.'</div><br/><br/><div class="recentItems-recent" data-name="'.$className.'">'.implode('',$items).'</div></div>'.($script?'<script>'.$script.'</script>':'');
    }
    
}

// Create Carousel
function outputNowPlaying($header, $size, $type, $items, $script = false) {
	// If None Populate Empty Item
	if (!count($items)) {
		return '<div id=streamz></div>'.($script?'<script>'.$script.'</script>':'');
	}else{
	   return '<div id=streamz><h5 class="zero-m big-box"><strong>'.$header.'</strong></h5>'.implode('',$items).'</div>'.($script?'<script>'.$script.'</script>':'');
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
	
	return outputNowPlaying(translate('PLAYING_NOW_ON_EMBY'), $size, 'streams-emby', $playingItems, "
		setInterval(function() {
			$('<div></div>').load('ajax.php?a=emby-streams',function() {
				var element = $(this).find('[id]');
				var loadedID = 	element.attr('id');
				$('#'+loadedID).replaceWith(element);
				console.log('Loaded updated: '+loadedID);
			});
		}, 15000);
	");
}

// Get Now Playing Streams From Plex
function getPlexStreams($size, $showNames, $role){
    $address = qualifyURL(PLEXURL);
    
	// Perform API requests
    $api = @curl_get($address."/status/sessions?X-Plex-Token=".PLEXTOKEN);
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

			return outputNowPlaying(translate('PLAYING_NOW_ON_PLEX')." ( ".count($items)." Streams )", $size, 'streams-plex', $items, "
				setInterval(function() {
					$('<div></div>').load('ajax.php?a=plex-streams',function() {
						var element = $(this).find('[id]');
						var loadedID = 	element.attr('id');
						$('#'+loadedID).replaceWith(element);
						console.log('Loaded updated: '+loadedID);
					});
				}, 15000);
			");
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
    $latest = json_decode(file_get_contents($address.'/Users/'.$userId.'/Items/Latest?EnableImages=false&Limit='.EMBYRECENTITEMS.'&api_key='.EMBYTOKEN.($showPlayed?'':'&IsPlayed=false')),true);
	
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

    return outputRecentAdded($header, $items, "", $array);
}

// Get Recent Content From Plex
function getPlexRecent($array){
    $address = qualifyURL(PLEXURL);
	$header = translate('RECENT_CONTENT');
	
	// Perform Requests
    $api = @curl_get($address."/library/recentlyAdded?limit=".PLEXRECENTITEMS."&X-Plex-Token=".PLEXTOKEN);
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

			return outputRecentAdded($header, $items, "", $array);
		}else{
			writeLog("error", "PLEX RECENT-ITEMS ERROR: could not connect - check token - if HTTPS, is cert valid");
		}
	}else{
		writeLog("error", "PLEX RECENT-ITEMS ERROR: could not connect - check URL - if HTTPS, is cert valid");
	}
}

// Get Image From Emby
function getEmbyImage() {
	$embyAddress = qualifyURL(EMBYURL);
    if (!file_exists('images/cache')) {
        mkdir('images/cache', 0777, true);
    }

	$itemId = $_GET['img'];
 	$key = $_GET['key'];
	$itemType = $_GET['type'];
	$imgParams = array();
	if (isset($_GET['height'])) { $imgParams['height'] = 'maxHeight='.$_GET['height']; }
	if (isset($_GET['width'])) { $imgParams['width'] = 'maxWidth='.$_GET['width']; }

	if(isset($itemId)) {
     $image_src = $embyAddress . '/Items/'.$itemId.'/Images/'.$itemType.'?'.implode('&', $imgParams);
    $cachefile = 'images/cache/'.$key.'.jpg';
    $cachetime = 604800;
    // Serve from the cache if it is younger than $cachetime
    if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
        header("Content-type: image/jpeg");
        @readfile($cachefile);
        exit;
    }
        ob_start(); // Start the output buffer
        header('Content-type: image/jpeg');
        @readfile($image_src);
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
	$plexAddress = qualifyURL(PLEXURL);
    if (!file_exists('images/cache')) {
        mkdir('images/cache', 0777, true);
    }
	
	$image_url = $_GET['img'];
	$key = $_GET['key'];
	$image_height = $_GET['height'];
	$image_width = $_GET['width'];
	
	if(isset($image_url) && isset($image_height) && isset($image_width)) {
		$image_src = $plexAddress . '/photo/:/transcode?height='.$image_height.'&width='.$image_width.'&upscale=1&url=' . $image_url . '&X-Plex-Token=' . PLEXTOKEN;
        $cachefile = 'images/cache/'.$key.'.jpg';
        $cachetime = 604800;
        // Serve from the cache if it is younger than $cachetime
        if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
            header("Content-type: image/jpeg");
            @readfile($cachefile);
            exit;
        }
		ob_start(); // Start the output buffer
        header('Content-type: image/jpeg');
		@readfile($image_src);
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
        writeLog("success", "image was removed");
        unlink($path);
    } else {
  		writeLog("error", "image was not removed");
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
			header('Location: '.dirname($_SERVER['SCRIPT_NAME']).'error.php?error=401');
			echo '<script>window.location.href = \''.dirname($_SERVER['SCRIPT_NAME']).'error.php?error=401\'</script>';
		} else {
			header('Location: '.dirname($_SERVER['SCRIPT_NAME']).'error.php?error=999');
			echo '<script>window.location.href = \''.dirname($_SERVER['SCRIPT_NAME']).'error.php?error=999\'</script>';
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
	
	return '
	<div class="email-body">
		<div class="email-header gray-bg">
			<button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
			<h1>'.$array['title'].'</h1>
		</div>
		<div class="email-inner small-box">
			<div class="email-inner-section">
				<div class="small-box fade in" id="'.$pageID.'_frame">
					<div class="col-lg-12">
						'.(isset($array['customBeforeForm'])?$array['customBeforeForm']:'').'
						<form class="content-form" name="'.$pageID.'" id="'.$pageID.'_form" onsubmit="return false;">
							<button id="'.$pageID.'_form_submit" class="btn waves btn-labeled btn-success btn btn-sm pull-right text-uppercase waves-effect waves-float">
							<span class="btn-label"><i class="fa fa-floppy-o"></i></span>Save
							</button>
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
	foreach(array('placeholder','style','disabled','readonly','pattern','min','max','required','onkeypress','onchange','onfocus','onleave','href','onclick') as $value) {
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
			$field = ($bDropdown?'<div class="btn-group">':'').'<button id="'.$id.'" type="button" class="btn waves btn-labeled btn-'.$bType.' btn-sm text-uppercase waves-effect waves-float'.$class.''.($bDropdown?' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"':'"').' '.implode(' ',$tags).'><span class="btn-label"><i class="fa fa-'.$icon.'"></i></span>'.$label.'</button>'.($bDropdown?$bDropdown.'</div>':'');
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
					),12,2,1).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-url',
						'name' => 'url['.$data['id'].']',
						'required' => true,
						'placeholder' => 'homepage.php',
						'labelTranslate' => 'TAB_URL',
						'value' => $data['url'],
					),12,2,1).'
					'.buildField(array(
						'type' => 'text',
						'id' => 'tab-'.$data['id'].'-iconurl',
						'name' => 'iconurl['.$data['id'].']',
						'placeholder' => 'images/organizr.png',
						'labelTranslate' => 'ICON_URL',
						'value' => $data['iconurl'],
					),12,2,1).'
					'.buildField(array(
						'type' => 'custom',
						'id' => 'tab-'.$data['id'].'-icon',
						'name' => 'icon['.$data['id'].']',
						'html' => '- '.translate('OR').' - <div class="input-group"><input data-placement="bottomRight" class="form-control material icp-auto'.($hidden?'-pend':'').'" id="$id" name="$name" value="$val" type="text" /><span class="input-group-addon"></span></div>',
						'value' => $data['icon'],
					),12,1,1).'
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
						'type' => 'radio',
						'labelTranslate' => 'DEFAULT',
						'name' => 'defaultz['.$data['id'].']',
						'value' => $data['defaultz'],
						'onclick' => "$('[type=radio][id!=\''+this.id+'\']').each(function() { this.checked=false; });",
					),12,1,1).'
					'.buildField(array(
						'type' => 'button',
						'icon' => 'trash',
                        'buttonType' => 'danger',
						'labelTranslate' => 'REMOVE',
						'onclick' => "$(this).parents('li').remove();",
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
			`window`	TEXT
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
							<td class="col-xs-7 nzbtable-file-row">'.$downloadName.'</td>
							<td class="col-xs-2 nzbtable nzbtable-row">'.$downloadStatus.'</td>
							<td class="col-xs-1 nzbtable nzbtable-row">'.$downloadCategory.'</td>
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
			return '<tr><td colspan="4"><p class="text-center">No Results</p></td></tr>';
		}
	}else{
		writeLog("error", "NZBGET ERROR: could not connect - check URL and/or check token and/or Usernamd and Password - if HTTPS, is cert valid");
	}
}

// Sabnzbd Items
function sabnzbdConnect($list = 'queue') {
    $url = qualifyURL(SABNZBDURL);
	
    $api = file_get_contents($url.'/api?mode='.$list.'&output=json&apikey='.SABNZBDKEY); 
    $api = json_decode($api, true);
    
    $gotNZB = array();
    
    foreach ($api[$list]['slots'] AS $child) {
        if($list == "queue"){ $downloadName = $child['filename']; $downloadCategory = $child['cat']; $downloadPercent = (($child['mb'] - $child['mbleft']) / $child['mb']) * 100; $progressBar = "progress-bar-striped active"; } 
        if($list == "history"){ $downloadName = $child['name']; $downloadCategory = $child['category']; $downloadPercent = "100"; $progressBar = ""; }
        $downloadStatus = $child['status'];
        
        $gotNZB[] = '<tr>
                        <td>'.$downloadName.'</td>
                        <td>'.$downloadStatus.'</td>
                        <td>'.$downloadCategory.'</td>
                        <td>
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
		return '<tr><td colspan="4"><p class="text-center">No Results</p></td></tr>';
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
			foreach(array('id','name','url','icon','iconurl','order') as $v) {
				if (isset($tabs[$v]) && isset($tabs[$v][$key])) { $fields[$v] = $tabs[$v][$key]; }
			}
			foreach(array('active','user','guest','defaultz','window') as $v) {
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
    return $protocol . $domain . dirname($_SERVER['REQUEST_URI']);
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
        if($downloaded == "0" && isset($unaired) && $episodePremier == "true"){ $downloaded = "light-blue-bg"; }elseif($downloaded == "0" && isset($unaired)){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg";}else{ $downloaded = "red-bg"; }
        
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
    $message = date("Y-m-d H:i:s")."|".$type."|".strip($message)."\n";
    file_put_contents("org.log", $message, FILE_APPEND | LOCK_EX);
}

function readLog(){
    $log = file("org.log");
    $log = array_reverse($log);
    foreach($log as $line){
        $line = explode("|", strip($line));
        $line[1] = ($line[1] == "error") ? '<span class="label label-danger">Error</span>' : '<span class="label label-primary">Success</span>';
        echo "<tr><td>".$line[0]."</td><td>".$line[2]."</td><td>".$line[1]."</td></tr>";
    }
}

function buildStream($array){
    $result = "";
    if (array_key_exists('platform', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:0; position: absolute; bottom: 10px; left: 10px;"><div style="margin-right: 0;" class="item pull-left text-center"><img alt="'.$array['platform'].'" class="img-circle" height="55px" src="images/platforms/'.getPlatform($array['platform']).'"></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('device', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-laptop"></i>'.$array['device'].'</span></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('stream', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-play"></i>'.$array['stream'].'</span></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('video', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-film"></i>'.$array['video'].'</span></div></div><div class="clearfix"></div>';
    }
    if (array_key_exists('audio', $array)) {
        $result .= '<div class="reg-info" style="margin-top:0; padding-left:5%;"><div style="margin-right: 0;" class="item pull-left text-center"><span style="font-size: 15px;" class="block text-center"><i class="fa fa-volume-up"></i>'.$array['audio'].'</span></div></div><div class="clearfix"></div>';
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

function prettyPrint($array) {
    echo "<pre>";
    print_r($array);
    echo "</pre>";
    echo "<br/>";
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
			
			if (substr_count(PLEXURL, '.') != 2) {
				$link = "https://app.plex.tv/web/app#!/server/$server/details?key=/library/metadata/".$results['ratingkey'];
			}else{
				$link = PLEXURL."/web/index.html#!/server/$server/details?key=/library/metadata/".$results['ratingkey'];
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
function sendEmail($email, $username = "Organizr User", $subject, $body, $cc = null){

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
	$mail->addAddress($email, $username);
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
				sendEmail($GLOBALS['USER']->adminEmail, "Admin", "Plex Invite Used", orgEmail("PLEX Invite Used", "Look who joined the cool club", "Admin", "Hey, The User: $usedBy has redeemd their invite code: [$code], their IP Address was: $currentIP", null, null, "What Next?", "Well, That is up to you.  You can go check on them if you like."));
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
	
	switch ($api['http_code']['http_code']){
		case 400:
			writeLog("error", "PLEX JOIN: $username already has access to the shared libraries");
			break;
		case 401:
			writeLog("error", "PLEX JOIN: invalid Plex Token");
			break;
		case 422:
			writeLog("error", "PLEX JOIN: user info error");
			break;
		case 429:
			writeLog("error", "PLEX JOIN: too many requests to plex.tv please try later");
			break;
		case 200:
		case 201:
			writeLog("success", "PLEX JOIN: $username now has access to your Plex Library");
			break;
		default:
			writeLog("error", "PLEX JOIN: unknown error, Error: ".$api['http_code']['http_code']);
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
			$sonarrCalendar = getSonarrCalendar($sonarr->getCalendar($startDate, $endDate));
			if(!empty($sonarrCalendar)) { $calendarItems = array_merge($calendarItems, $sonarrCalendar); }
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
			'orgLog' => 'org.log', 
			'loginLog' => 'loginLog.json', 
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
dependCheck();