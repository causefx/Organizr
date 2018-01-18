<?php
function authRegister($username,$password,$defaults,$email){
	$defaults = defaultUserGroup();
	if(createUser($username,$password,$defaults,$email)){
		writeLog('success', 'Registration Function - A User has registered', $username);
		if(createToken($username,$email,gravatar($email),$defaults['group'],$defaults['group_id'],$GLOBALS['organizrHash'],7)){
			writeLoginLog($username, 'success');
			writeLog('success', 'Login Function - A User has logged in', $username);
			return true;
		}
	}else{
		writeLog('error', 'Registration Function - An error occured', $username);
		return 'username taken';
	}
}
function checkPlexUser($username){
	try{
		if(!empty($GLOBALS['plexToken'])){
			$url = 'https://plex.tv/pms/friends/all';
			$headers = array(
				'X-Plex-Token' => $GLOBALS['plexToken'],
			);
			$response = Requests::get($url, $headers);
			if($response->success){
				libxml_use_internal_errors(true);
				$userXML = simplexml_load_string($response->body);
				if (is_array($userXML) || is_object($userXML)) {
					$usernameLower = strtolower($username);
					foreach($userXML AS $child) {
						if(isset($child['username']) && strtolower($child['username']) == $usernameLower || isset($child['email']) && strtolower($child['email']) == $usernameLower) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}catch( Requests_Exception $e ) {
		writeLog('success', 'Plex User Check Function - Error: '.$e->getMessage(), $username);
	};
}
function plugin_auth_plex($username, $password) {
	try{
		$usernameLower = strtolower($username);
		if(checkPlexUser($username)){
			//Login User
			$url = 'https://plex.tv/users/sign_in.json';
			$headers = array(
				'Accept'=> 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
				'X-Plex-Product' => 'Organizr',
				'X-Plex-Version' => '2.0',
				'X-Plex-Client-Identifier' => '01010101-10101010',
			);
			$data = array(
				'user[login]' => $username,
				'user[password]' => $password,
			);
			$response = Requests::post($url, $headers, $data);
			if($response->success){
				$json = json_decode($response->body, true);
				if ((is_array($json) && isset($json['user']) && isset($json['user']['username'])) && strtolower($json['user']['username']) == $usernameLower || strtolower($json['user']['email']) == $usernameLower) {
					//writeLog("success", $json['user']['username']." was logged into organizr using plex credentials");
	                return array(
						'username' => $json['user']['username'],
						'email' => $json['user']['email'],
						'image' => $json['user']['thumb'],
						'token' => $json['user']['authToken']
					);
				}
			}
		}
		return false;
	}catch( Requests_Exception $e ) {
		writeLog('success', 'Plex Auth Function - Error: '.$e->getMessage(), $username);
	};
}
if (function_exists('ldap_connect')){
	// Pass credentials to LDAP backend
	function plugin_auth_ldap($username, $password) {
		if(!empty($GLOBALS['authBaseDN']) && !empty($GLOBALS['authBackendHost'])){
			$ldapServers = explode(',',$GLOBALS['authBackendHost']);
			foreach($ldapServers as $key => $value) {
				// Calculate parts
				$digest = parse_url(trim($value));
				$scheme = strtolower((isset($digest['scheme'])?$digest['scheme']:'ldap'));
				$host = (isset($digest['host'])?$digest['host']:(isset($digest['path'])?$digest['path']:''));
				$port = (isset($digest['port'])?$digest['port']:(strtolower($scheme)=='ldap'?389:636));
				// Reassign
				$ldapServers[$key] = $scheme.'://'.$host.':'.$port;
			}
			$ldap = ldap_connect(implode(' ',$ldapServers));
			ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
			$bind = @ldap_bind($ldap, sprintf($GLOBALS['authBaseDN'], $username), $password);
			return ($bind) ? true : false;
		}
		return false;
	}
}else{
	// Ldap Auth Missing Dependancy
	function plugin_auth_ldap_disabled() {
		return 'LDAP - Disabled (Dependancy: php-ldap missing!)';
	}
}

// Pass credentials to FTP backend
function plugin_auth_ftp($username, $password) {
	// Calculate parts
	$digest = parse_url($GLOBALS['authBackendHost']);
	$scheme = strtolower((isset($digest['scheme'])?$digest['scheme']:(function_exists('ftp_ssl_connect')?'ftps':'ftp')));
	$host = (isset($digest['host'])?$digest['host']:(isset($digest['path'])?$digest['path']:''));
	$port = (isset($digest['port'])?$digest['port']:21);
	// Determine Connection Type
	if ($scheme == 'ftps') {
		$conn_id = ftp_ssl_connect($host, $port, 20);
	} elseif ($scheme == 'ftp') {
		$conn_id = ftp_connect($host, $port, 20);
	} else {
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
