<?php

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
