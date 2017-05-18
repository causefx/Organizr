<?php

// ===================================
// Define Version
 define('INSTALLEDVERSION', '1.341');
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
			return true;
		} else {
			return false;
		}
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
		return false;
	}
	
	// Check if valid FTP connection
	if ($conn_id) {
		// Attempt login
		@$login_result = ftp_login($conn_id, $username, $password);
		ftp_close($conn_id);
		
		// Return Result
		if ($login_result) {
			return true;
		} else {
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
                        return array(
							'email' => $json['user']['email'],
							'image' => $json['user']['thumb']
						);
					}
				}
			}
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
		
		$this->langCode = $language;
		
        if (file_exists("lang/{$language}.ini")) {
            $this->language = parse_ini_file("lang/{$language}.ini", false, INI_SCANNER_RAW);
        } else {
            $this->language = parse_ini_file("lang/en.ini", false, INI_SCANNER_RAW);
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
            echo ("Translation not found for: $originalWord");
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
		// Close
		curl_close($curlReq);
		// Return
		return array('content'=>$result);
	}

	//Curl Get Function
	function curl_get($url, $headers = array()) {
		// Initiate cURL
		$curlReq = curl_init($url);
		// As post request
		curl_setopt($curlReq, CURLOPT_CUSTOMREQUEST, "GET"); 
		curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
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
function resolveEmbyItem($address, $token, $item) {
	// Static Height
	$height = 150;
	
	// Get Item Details
	$itemDetails = json_decode(file_get_contents($address.'/Items?Ids='.$item['Id'].'&Fields=Overview&api_key='.$token),true)['Items'][0];
	
	switch ($itemDetails['Type']) {
		case 'Episode':
			$title = (isset($itemDetails['SeriesName'])?$itemDetails['SeriesName'].': ':'').$itemDetails['Name'].(isset($itemDetails['ParentIndexNumber']) && isset($itemDetails['IndexNumber'])?' (Season '.$itemDetails['ParentIndexNumber'].': Episode '.$itemDetails['IndexNumber'].')':'');
			$imageId = (isset($itemDetails['SeriesId'])?$itemDetails['SeriesId']:$itemDetails['Id']);
			$width = 100;
			$image = 'carousel-image season';
			$style = '';
			break;
		case 'MusicAlbum':
			$title = $itemDetails['Name'];
			$imageId = $itemDetails['Id'];
			$width = 150;
			$image = 'music';
			$style = 'left: 160px !important;';
			break;
		default:
			$title = $itemDetails['Name'];
			$imageId = $itemDetails['Id'];
			$width = 100;
			$image = 'carousel-image movie';
			$style = '';
	}
	
	// If No Overview
	if (!isset($itemDetails['Overview'])) {
		$itemDetails['Overview'] = '';
	}
	
	// Assemble Item And Cache Into Array 
	return '<div class="item"><a href="'.$address.'/web/itemdetails.html?id='.$itemDetails['Id'].'" target="_blank"><img alt="'.$itemDetails['Name'].'" class="'.$image.'" src="ajax.php?a=emby-image&img='.$imageId.'&height='.$height.'&width='.$width.'"></a><div class="carousel-caption" style="'.$style.'"><h4>'.$title.'</h4><small><em>'.$itemDetails['Overview'].'</em></small></div></div>';
}

// Format item from Plex for Carousel
function resolvePlexItem($server, $token, $item) {
	// Static Height
	$height = 150;
	
	$address = "https://app.plex.tv/web/app#!/server/$server/details?key=/library/metadata/".$item['ratingKey'];
	
	switch ($item['type']) {
		case 'season':
			$title = $item['parentTitle'];
			$summary = $item['parentSummary'];
			$width = 100;
			$image = 'carousel-image season';
			$style = '';
            $thumb = $item['thumb'];
			break;
        case 'episode':
			$title = $item['grandparentTitle'];
			$summary = $item['title'];
			$width = 100;
			$image = 'carousel-image season';
			$style = '';
            $thumb = $item['parentThumb'];
			break;
		case 'album':
		case 'track':
			$title = $item['parentTitle'];
			$summary = $item['title'];
			$width = 150;
			$image = 'album';
			$style = 'left: 160px !important;';
            $thumb = $item['thumb'];
			break;
		default:
			$title = $item['title'];
			$summary = $item['summary'];
			$width = 100;
			$image = 'carousel-image movie';
			$style = '';
            $thumb = $item['thumb'];
	}
	
	// If No Overview
	if (!isset($itemDetails['Overview'])) {
		$itemDetails['Overview'] = '';
	}
	
	// Assemble Item And Cache Into Array 
	return '<div class="item"><a href="'.$address.'" target="_blank"><img alt="'.$item['Name'].'" class="'.$image.'" src="ajax.php?a=plex-image&img='.$thumb.'&height='.$height.'&width='.$width.'"></a><div class="carousel-caption" style="'.$style.'"><h4>'.$title.'</h4><small><em>'.$summary.'</em></small></div></div>';
}

// Create Carousel
function outputCarousel($header, $size, $type, $items, $script = false) {
	// If None Populate Empty Item
	if (!count($items)) {
		$items = array('<div class="item"><img alt="nada" class="carousel-image movie" src="images/nadaplaying.jpg"><div class="carousel-caption"><h4>Nothing To Show</h4><small><em>Get Some Stuff Going!</em></small></div></div>');
	}
	
	// Set First As Active
	$items[0] = preg_replace('/^<div class="item ?">/','<div class="item active">', $items[0]);
	
	// Add Buttons
	$buttons = '';
	if (count($items) > 1) {
		$buttons = '
			<a class="left carousel-control '.$type.'" href="#carousel-'.$type.'" role="button" data-slide="prev"><span class="fa fa-chevron-left" aria-hidden="true"></span><span class="sr-only">Previous</span></a>
			<a class="right carousel-control '.$type.'" href="#carousel-'.$type.'" role="button" data-slide="next"><span class="fa fa-chevron-right" aria-hidden="true"></span><span class="sr-only">Next</span></a>';
	}
	
	return '
	<div class="col-lg-'.$size.'">
		<h5 class="text-center">'.$header.'</h5>
		<div id="carousel-'.$type.'" class="carousel slide box-shadow white-bg" data-ride="carousel"><div class="carousel-inner" role="listbox">
			'.implode('',$items).'
		</div>'.$buttons.'
	</div></div>'.($script?'<script>'.$script.'</script>':''); 
}

// Get Now Playing Streams From Emby
function getEmbyStreams($size) {
	$address = qualifyURL(EMBYURL);
	
	$api = json_decode(file_get_contents($address.'/Sessions?api_key='.EMBYTOKEN),true);
	
	$playingItems = array();
	foreach($api as $key => $value) {
		if (isset($value['NowPlayingItem'])) {
			$playingItems[] = resolveEmbyItem($address, EMBYTOKEN, $value['NowPlayingItem']);
		}
	}
	
	return outputCarousel(translate('PLAYING_NOW_ON_EMBY'), $size, 'streams-emby', $playingItems, "
		setInterval(function() {
			$('<div></div>').load('ajax.php?a=emby-streams',function() {
				var element = $(this).find('[id]');
				var loadedID = 	element.attr('id');
				$('#'+loadedID).replaceWith(element);
				console.log('Loaded updated: '+loadedID);
			});
		}, 10000);
	");
}

// Get Now Playing Streams From Plex
function getPlexStreams($size){
    $address = qualifyURL(PLEXURL);
    
	// Perform API requests
    $api = file_get_contents($address."/status/sessions?X-Plex-Token=".PLEXTOKEN);
    $api = simplexml_load_string($api);
    $getServer = simplexml_load_string(file_get_contents($address."/?X-Plex-Token=".PLEXTOKEN));
    
	// Identify the local machine
    $gotServer = $getServer['machineIdentifier'];
	
	$items = array();
	foreach($api AS $child) {
		$items[] = resolvePlexItem($gotServer, PLEXTOKEN, $child);
	}
	
	return outputCarousel(translate('PLAYING_NOW_ON_PLEX'), $size, 'streams-plex', $items, "
		setInterval(function() {
			$('<div></div>').load('ajax.php?a=plex-streams',function() {
				var element = $(this).find('[id]');
				var loadedID = 	element.attr('id');
				$('#'+loadedID).replaceWith(element);
				console.log('Loaded updated: '+loadedID);
			});
		}, 10000);
	");
}

// Get Recent Content From Emby
function getEmbyRecent($type, $size) {
    $address = qualifyURL(EMBYURL);
	
	// Currently Logged In User
	$username = false;
	if (isset($GLOBALS['USER'])) {
		$username = strtolower($GLOBALS['USER']->username);
	}
	
	// Resolve Types
	switch ($type) {
		case 'movie':
			$embyTypeQuery = 'IncludeItemTypes=Movie&';
			$header = translate('MOVIES');
			break;
		case 'season':
			$embyTypeQuery = 'IncludeItemTypes=Episode&';
			$header = translate('TV_SHOWS');
			break;
		case 'album':
			$embyTypeQuery = 'IncludeItemTypes=MusicAlbum&';
			$header = translate('MUSIC');
			break;
		default:
			$embyTypeQuery = '';
			$header = translate('RECENT_CONTENT');
	}
	
	// Get A User
	$userIds = json_decode(file_get_contents($address.'/Users?api_key='.EMBYTOKEN),true);
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
	$latest = json_decode(file_get_contents($address.'/Users/'.$userId.'/Items/Latest?'.$embyTypeQuery.'EnableImages=false&api_key='.EMBYTOKEN.($showPlayed?'':'&IsPlayed=false')),true);
	
	// For Each Item In Category
	$items = array();
	foreach ($latest as $k => $v) {
		$items[] = resolveEmbyItem($address, EMBYTOKEN, $v);
	}
	
	return outputCarousel($header, $size, $type.'-emby', $items);
}

// Get Recent Content From Plex
function getPlexRecent($type, $size){
    $address = qualifyURL(PLEXURL);
    
	// Resolve Types
	switch ($type) {
		case 'movie':
			$header = translate('MOVIES');
			break;
		case 'season':
			$header = translate('TV_SHOWS');
			break;
		case 'album':
			$header = translate('MUSIC');
			break;
		default:
			$header = translate('RECENT_CONTENT');
	}
	
	// Perform Requests
    $api = file_get_contents($address."/library/recentlyAdded?X-Plex-Token=".PLEXTOKEN);
    $api = simplexml_load_string($api);
    $getServer = simplexml_load_string(file_get_contents($address."/?X-Plex-Token=".PLEXTOKEN));
	
	// Identify the local machine
    $gotServer = $getServer['machineIdentifier'];
	
	$items = array();
	foreach($api AS $child) {
		if($child['type'] == $type){
			$items[] = resolvePlexItem($gotServer, PLEXTOKEN, $child);
		}
	}
	
	return outputCarousel($header, $size, $type.'-plex', $items);
}

// Get Image From Emby
function getEmbyImage() {
	$embyAddress = qualifyURL(EMBYURL);
	
	$itemId = $_GET['img'];
	$imgParams = array();
	if (isset($_GET['height'])) { $imgParams['height'] = 'maxHeight='.$_GET['height']; }
	if (isset($_GET['width'])) { $imgParams['width'] = 'maxWidth='.$_GET['width']; }

	if(isset($itemId)) {
		$image_src = $embyAddress . '/Items/'.$itemId.'/Images/Primary?'.implode('&', $imgParams);
		header('Content-type: image/jpeg');
		readfile($image_src);
		die();
	} else {
		debug_out('Invalid Request',1);
	}
}

// Get Image From Plex
function getPlexImage() {
	$plexAddress = qualifyURL(PLEXURL);
	
	$image_url = $_GET['img'];
	$image_height = $_GET['height'];
	$image_width = $_GET['width'];
	
	if(isset($image_url) && isset($image_height) && isset($image_width)) {
		$image_src = $plexAddress . '/photo/:/transcode?height='.$image_height.'&width='.$image_width.'&upscale=1&url=' . $image_url . '&X-Plex-Token=' . PLEXTOKEN;
		header('Content-type: image/jpeg');
		readfile($image_src);
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
		
		return false;
	} else {
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

			echo json_encode($files['metas'][0]['name']);
		}

		if($data['hasErrors']){
			$errors = $data['errors'];
			echo json_encode($errors);
		}
	} else {
		echo json_encode('No files submitted!');
	}
}

// Remove file
function removeFiles($path) {
    if(is_file($path)) {
        unlink($path);
    } else {
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
							<button type="submit" class="btn waves btn-labeled btn-success btn btn-sm pull-right text-uppercase waves-effect waves-float">
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
			$(\'#'.$pageID.'_form\').find(\'select[multiple]\').on(\'click\', function() { $(this).attr(\'data-changed\', \'true\'); });
			
			$(\'#'.$pageID.'_form\').submit(function () {
				var newVals = {};
				var hasVals = false;
				$(\'#'.$pageID.'_form\').find(\'[data-changed=true]\').each(function() {
					hasVals = true;
					if (this.type == \'checkbox\') {
						newVals[this.name] = this.checked;
					} else {
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
				if (hasVals) {
					console.log(newVals);
					ajax_request(\'POST\', \''.(isset($array['submitAction'])?$array['submitAction']:'update-config').'\', newVals, function(data, code) {
						$(\'#'.$pageID.'_form\').find(\'[data-changed=true]\').removeAttr(\'data-changed\');
					});
				} else {
					parent.notify(\'Nothing to update!\', \'bullhorn\', \'success\', 5000, \'bar\', \'slidetop\');
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
		
		return $users && $tabs && $options;
	} else {
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
		return true;
	} else {
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
		return true;
	} else {
		return false;
	}
}

// Send AJAX notification
function sendNotification($success, $message = false, $send = true) {
	$notifyExplode = explode("-", NOTIFYEFFECT);
	if ($success) {
		$msg = array(
			'html' => ($message?'<br>'.$message:'<strong>'.translate("SETTINGS_SAVED").'</strong>'),
			'icon' => 'floppy-o',
			'type' => 'success',
			'length' => '5000',
			'layout' => $notifyExplode[0],
			'effect' => $notifyExplode[1],
		);
	} else {
		$msg = array(
			'html' => ($message?'<br>'.$message:'<strong>'.translate("SETTINGS_NOT_SAVED").'</strong>'),
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
	
	return true;
}

// Upgrade the installation
function upgradeInstall($branch = 'master') {
    function downloadFile($url, $path){
        $folderPath = "upgrade/";
        if(!mkdir($folderPath)) : echo "can't make dir"; endif;
        $newfname = $folderPath . $path;
        $file = fopen ($url, 'rb');
        if ($file) {
            $newf = fopen ($newfname, 'wb');
            if ($newf) {
                while(!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                }
            }
        }

        if ($file) {
            fclose($file);
        }

        if ($newf) {
            fclose($newf);
        }
    }

    function unzipFile($zipFile){
        $zip = new ZipArchive;
        $extractPath = "upgrade/";
        if($zip->open($extractPath . $zipFile) != "true"){
            echo "Error :- Unable to open the Zip File";
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
    downloadFile($url, $file);
    unzipFile($file);
    rcopy($source, $destination);
    rrmdir($cleanup);
	
	return true;
}

// NzbGET Items
function nzbgetConnect($list = 'listgroups') {
    $url = qualifyURL(NZBGETURL);
    
    $api = file_get_contents($url.'/'.NZBGETUSERNAME.':'.NZBGETPASSWORD.'/jsonrpc/'.$list);          
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
                        <td>'.$downloadName.'</td>
                        <td>'.$downloadStatus.'</td>
                        <td>'.$downloadCategory.'</td>
                        <td>
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
		return $totalValid;
	} else {
		return false;
	}
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
    
    $api = file_get_contents($url."/api?apikey=".$key."&cmd=$list");
    
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



// Always run this
dependCheck();