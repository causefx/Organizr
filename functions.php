<?php

// ===================================
// Define Version
 define('INSTALLEDVERSION', '1.40');
// ===================================

// Debugging output functions
function debug_out($variable, $die = false) {
	$trace = debug_backtrace()[0];
	echo '<pre style="background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">'.$trace['file'].':'.$trace['line']."\n\n".print_r($variable, true).'</pre>';
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
				writeLog("error", "$username is not an authorized user or entered invalid password");
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
    return '<div class="col-sm-6 col-md-3"><div class="thumbnail ultra-widget"><div style="display: none;" np="'.$id.'" class="overlay content-box small-box gray-bg">'.$streamInfo.'</div><span class="w-refresh w-p-icon gray" link="'.$id.'"><span class="fa-stack fa-lg" style="font-size: .5em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-info-circle fa-stack-1x fa-inverse"></i></span></span><a href="'.$URL.'" target="_blank"><img style="width: 500px; display:inherit;" src="'.$image_url.'" alt="'.$itemDetails['Name'].'"></a><div class="progress progress-bar-sm zero-m"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$watched.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$watched.'%"></div><div class="progress-bar palette-Grey-500 bg" style="width: 0%"></div></div><div class="caption"><i style="float:left" class="fa fa-'.$state.'"></i>'.$topTitle.''.$bottomTitle.'</div></div></div>';
    }else{
 return '<div class="item-'.$itemDetails['Type'].'"><a href="'.$URL.'" target="_blank"><img alt="'.$itemDetails['Name'].'" class="'.$image.'" data-lazy="'.$image_url.'"></a><small style="margin-right: 13px" class="elip">'.$title.'</small></div>';
}
}

// Format item from Plex for Carousel
function resolvePlexItem($server, $token, $item, $nowPlaying = false, $showNames = false, $role = false) {
    // Static Height
    $height = 444;    

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
                $duration = $item['duration'];
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
                $thumb = $item['parentThumb'];
                $key = $item['ratingKey'] . "-list";
            }else { 
                $height = 281;
                $width = 500;
                $thumb = $item['art'];
                $key = $item['ratingKey'] . "-np";
                $elapsed = $item['viewOffset'];
                $duration = $item['duration'];
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
                if($showNames == "true"){ $bottomTitle .= '</small><small class="zero-m pull-right">'.$user.'</small>'; }
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
                $duration = $item['duration'];
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
                $duration = $item['duration'];
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
                $duration = $item['duration'];
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
	
		if (substr_count(PLEXURL, ':') == 2) {
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
    if(!$thumb){ $image_url = "images/no-np.png"; $key = "no-np"; }
	if(isset($useImage)){ $image_url = $useImage; }
	$openTab = (PLEXTABNAME) ? "true" : "false";
    // Assemble Item And Cache Into Array 
    if($nowPlaying){
        return '<div class="col-sm-6 col-md-3"><div class="thumbnail ultra-widget"><div style="display: none;" np="'.$id.'" class="overlay content-box small-box gray-bg">'.$streamInfo.'</div><span class="w-refresh w-p-icon gray" link="'.$id.'"><span class="fa-stack fa-lg" style="font-size: .5em"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-info-circle fa-stack-1x fa-inverse"></i></span></span><a class="openTab" openTab="'.$openTab.'" href="'.$address.'" target="_blank"><img style="width: 500px; display:inherit;" src="'.$image_url.'" alt="'.$item['Name'].'"></a><div class="progress progress-bar-sm zero-m"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$watched.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$watched.'%"></div><div class="progress-bar palette-Grey-500 bg" style="width: '.$transcoded.'%"></div></div><div class="caption"><i style="float:left" class="fa fa-'.$state.'"></i>'.$topTitle.''.$bottomTitle.'</div></div></div>';
    }else{
        return '<div class="item-'.$item['type'].'"><a class="openTab" openTab="'.$openTab.'" href="'.$address.'" target="_blank"><img alt="'.$item['Name'].'" class="'.$image.'" data-lazy="'.$image_url.'"></a><small style="margin-right: 13px" class="elip">'.$title.'</small></div>';
    }
}

//Recent Added
function outputRecentAdded($header, $items, $script = false, $array) {
    $hideMenu = '<div class="pull-right"><div class="btn-group" role="group"><button type="button" class="btn waves btn-default btn-sm dropdown-toggle waves-effect waves-float" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Filter<span class="caret"></span></button><ul style="right:0; left: auto" class="dropdown-menu">';
    if($array["movie"] == "true"){
        $hideMenu .= '<li><a class="js-filter-movie" href="javascript:void(0)">Hide Movies</a></li>';
    }
    if($array["season"] == "true"){
        $hideMenu .= '<li><a class="js-filter-season" href="javascript:void(0)">Hide Show</a></li>';
    }
    if($array["album"] == "true"){
        $hideMenu .= '<li><a class="js-filter-album" href="javascript:void(0)">Hide Music</a></li>';
    }
    $hideMenu .= '</ul></div></div>';
    // If None Populate Empty Item
    if (!count($items)) {
        return '<div id=recentMedia><h5 class="text-center">'.$header.'</h5><p class="text-center">No Media Found</p></div>';
    }else{
        return '<div id=recentMedia><h5 style="margin-bottom: -20px" class="text-center">'.$header.'</h5><div class="recentHeader inbox-pagination">'.$hideMenu.'</div><br/><div class="recentItems">'.implode('',$items).'</div></div>'.($script?'<script>'.$script.'</script>':'');
    }
    
}

// Create Carousel
function outputNowPlaying($header, $size, $type, $items, $script = false) {
	// If None Populate Empty Item
	if (!count($items)) {
		return '<div id=streamz></div>'.($script?'<script>'.$script.'</script>':'');
	}else{
	   return '<div id=streamz><h5 class="text-center">'.$header.'</h5>'.implode('',$items).'</div>'.($script?'<script>'.$script.'</script>':'');
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
    $getServer = simplexml_load_string(@curl_get($address."/?X-Plex-Token=".PLEXTOKEN));
    if (!$getServer) { return 'Could not load!'; }
	
	// Identify the local machine
    $gotServer = $getServer['machineIdentifier'];
	
	$items = array();
	foreach($api AS $child) {
		$items[] = resolvePlexItem($gotServer, PLEXTOKEN, $child, true, $showNames, $role);
	}
	
	return outputNowPlaying(translate('PLAYING_NOW_ON_PLEX'), $size, 'streams-plex', $items, "
		setInterval(function() {
			$('<div></div>').load('ajax.php?a=plex-streams',function() {
				var element = $(this).find('[id]');
				var loadedID = 	element.attr('id');
				$('#'+loadedID).replaceWith(element);
				console.log('Loaded updated: '+loadedID);
			});
		}, 15000);
	");
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

// Check if all software dependancies are met
function dependCheck() {
	$output = array();
	if (!extension_loaded('pdo_sqlite')) { $output[] = 'PDO:SQLite not enabled, please add "extension = php_pdo_sqlite.dll" to php.ini'; }
	//if (!extension_loaded('sqlite3')) { $output[] = 'SQLite3 not enabled, please add "extension = php_sqlite3.dll" to php.ini'; }
	
	if ($output) {
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
			header('Location: error.php?error=401');
			echo '<script>window.location.href = \''.dirname($_SERVER['SCRIPT_NAME']).'/error.php?error=401\'</script>';
		} else {
			header('Location: error.php?error=999');
			echo '<script>window.location.href = \''.dirname($_SERVER['SCRIPT_NAME']).'/error.php?error=999\'</script>';
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
		if (defined('DATABASE_LOCATION')) {
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
	if (!isset($GLOBALS['file_db'])) {
		$GLOBALS['file_db'] = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
		$GLOBALS['file_db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
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
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') { 
        
        $protocol = "https://"; 
    
    } else {  
        
        $protocol = "http://"; 
    
    }
    
    return $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
      
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
    $gotCalendar = "";
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
            $gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded\", imagetype: \"tv\", url: \"https://thetvdb.com/?tab=series&id=$episodeID\" }, \n";
        
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
            $gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded\", imagetype: \"tv\", url: \"https://thetvdb.com/?tab=series&id=$episodeID\" }, \n";
        
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
            $gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded\", imagetype: \"tv\", url: \"https://thetvdb.com/?tab=series&id=$episodeID\" }, \n";
        
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
            $gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded\", imagetype: \"tv\", url: \"https://thetvdb.com/?tab=series&id=$episodeID\" }, \n";
        
    }

    if ($i != 0){ return $gotCalendar; }

}

function getSickrageCalendarHistory($array){
    
    $array = json_decode($array, true);
    $gotCalendar = "";
    $i = 0;

    foreach($array['data'] AS $child) {

            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['date'];
            $downloaded = "green-bg";
            $gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded\", imagetype: \"tv\", url: \"https://thetvdb.com/?tab=series&id=$episodeID\" }, \n";
        
    }

    if ($i != 0){ return $gotCalendar; }

}

function getSonarrCalendar($array){
    
    $array = json_decode($array, true);
    $gotCalendar = "";
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
        
        $gotCalendar .= "{ title: \"$seriesName\", start: \"$episodeAirDate\", className: \"$downloaded\", imagetype: \"tv\", url: \"https://thetvdb.com/?tab=series&id=$episodeID\" }, \n";
        
    }

    if ($i != 0){ return $gotCalendar; }

}

function getRadarrCalendar($array){
    
    $array = json_decode($array, true);
    $gotCalendar = "";
    $i = 0;
    foreach($array AS $child) {
        
        if(isset($child['inCinemas'])){
            
            $i++;
            $movieName = $child['title'];
            $movieID = $child['tmdbId'];
            if(!isset($movieID)){ $movieID = ""; }
            
            if(isset($child['inCinemas']) && isset($child['physicalRelease'])){ 
                
                $physicalRelease = $child['physicalRelease']; 
                $physicalRelease = strtotime($physicalRelease);
                $physicalRelease = date("Y-m-d", $physicalRelease);

                if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }

                $downloaded = $child['hasFile'];
                if($downloaded == "0" && $notReleased == "true"){ $downloaded = "indigo-bg"; }elseif($downloaded == "1"){ $downloaded = "green-bg"; }else{ $downloaded = "red-bg"; }
            
            }else{ 
                
                $physicalRelease = $child['inCinemas']; 
                $downloaded = "light-blue-bg";
            
            }
                        
            $gotCalendar .= "{ title: \"$movieName\", start: \"$physicalRelease\", className: \"$downloaded\", imagetype: \"film\", url: \"https://www.themoviedb.org/movie/$movieID\" }, \n";
        }
        
    }

    if ($i != 0){ return $gotCalendar; }

}

function getHeadphonesCalendar($url, $key, $list){
	$url = qualifyURL(HEADPHONESURL);
    
    $api = curl_get($url."/api?apikey=".$key."&cmd=$list");
    
    $api = json_decode($api, true);
    
    $i = 0;
    
    $gotCalendar = "";
	
    foreach($api AS $child) {

        if($child['Status'] == "Wanted"){
        
            $i++;
            $albumName = addslashes($child['AlbumTitle']);
            $albumArtist = htmlentities($child['ArtistName'], ENT_QUOTES);
            $albumDate = $child['ReleaseDate'];
            $albumID = $child['AlbumID'];
            $albumDate = strtotime($albumDate);
            $albumDate = date("Y-m-d", $albumDate);
            $albumStatus = $child['Status'];
            
            if (new DateTime() < new DateTime($albumDate)) {  $notReleased = "true"; }else{ $notReleased = "false"; }

            if($albumStatus == "Wanted" && $notReleased == "true"){ $albumStatusColor = "indigo-bg"; }elseif($albumStatus == "Downloaded"){ $albumStatusColor = "green-bg"; }else{ $albumStatusColor = "red-bg"; }

            $gotCalendar .= "{ title: \"$albumArtist - $albumName\", start: \"$albumDate\", className: \"$albumStatusColor\", imagetype: \"music\", url: \"https://musicbrainz.org/release-group/$albumID\" }, \n";
            
        }
        
    }

    if ($i != 0){ return $gotCalendar; }

}

function checkRootPath($string){
    if($string == "\\" || $string == "/"){
        return "/";
    }else{
        return str_replace("\\", "/", $string) . "/";
    }
}

function writeLog($type, $message){
    $message = date("Y-m-d H:i:s")."|".$type."|".$message."\n";
    file_put_contents("org.log", $message, FILE_APPEND | LOCK_EX);
}

function readLog(){
    $log = file("org.log");
    $log = array_reverse($log);
    foreach($log as $line){
        $line = explode("|", $line);
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
        "Chromecast" => "chromecast.png",
        "Dashboard" => "emby.png",
        "Dlna" => "dlna.png",
        "Windows Phone" => "wp.png",
        "Windows RT" => "win8.png",
        "Kodi" => "koid.png",
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
			
			if (substr_count(PLEXURL, ':') == 2) {
				$link = "https://app.plex.tv/web/app#!/server/$server/details?key=/library/metadata/".$results['ratingkey'];
			}else{
				$link = PLEXURL."/web/index.html#!/server/$server/details?key=/library/metadata/".$results['ratingkey'];
			}
			
            $items .= '<tr style="cursor: pointer;" class="openTab" openTab="'.$openTab.'" href="'.$link.'">
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
			$libraryList['users'][$username] = (string)$child['id'];
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
	$id = convertPlexName($username, "id");
	
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

// Always run this
dependCheck();