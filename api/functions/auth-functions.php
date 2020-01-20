<?php
function authRegister($username, $password, $defaults, $email, $token = null)
{
	if ($GLOBALS['authBackend'] !== '') {
		ombiImport($GLOBALS['authBackend']);
	}
	ssoCheck($username, $password, $token);
	if (createUser($username, $password, $defaults, $email)) {
		writeLog('success', 'Registration Function - A User has registered', $username);
		if ($GLOBALS['PHPMAILER-enabled'] && $email !== '') {
			$emailTemplate = array(
				'type' => 'registration',
				'body' => $GLOBALS['PHPMAILER-emailTemplateRegisterUser'],
				'subject' => $GLOBALS['PHPMAILER-emailTemplateRegisterUserSubject'],
				'user' => $username,
				'password' => null,
				'inviteCode' => null,
			);
			$emailTemplate = phpmEmailTemplate($emailTemplate);
			$sendEmail = array(
				'to' => $email,
				'user' => $username,
				'subject' => $emailTemplate['subject'],
				'body' => phpmBuildEmail($emailTemplate),
			);
			phpmSendEmail($sendEmail);
		}
		if (createToken($username, $email, gravatar($email), $defaults['group'], $defaults['group_id'], $GLOBALS['organizrHash'], $GLOBALS['rememberMeDays'])) {
			writeLoginLog($username, 'success');
			writeLog('success', 'Login Function - A User has logged in', $username);
			return true;
		}
	} else {
		writeLog('error', 'Registration Function - An error occurred', $username);
		return 'username taken';
	}
	return false;
}

function checkPlexToken($token = '')
{
	try {
		if (($token !== '')) {
			$url = 'https://plex.tv/users/account.json';
			$headers = array(
				'X-Plex-Token' => $token,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			);
			$response = Requests::get($url, $headers);
			if ($response->success) {
				return json_decode($response->body, true);
			}
		} else {
			return false;
		}
		
	} catch (Requests_Exception $e) {
		writeLog('success', 'Plex Token Check Function - Error: ' . $e->getMessage(), SYSTEM);
	}
	return false;
}

function checkPlexUser($username)
{
	try {
		if (!empty($GLOBALS['plexToken'])) {
			$url = 'https://plex.tv/api/users';
			$headers = array(
				'X-Plex-Token' => $GLOBALS['plexToken'],
			);
			$response = Requests::get($url, $headers);
			if ($response->success) {
				libxml_use_internal_errors(true);
				$userXML = simplexml_load_string($response->body);
				if (is_array($userXML) || is_object($userXML)) {
					$usernameLower = strtolower($username);
					foreach ($userXML as $child) {
						if (isset($child['username']) && strtolower($child['username']) == $usernameLower || isset($child['email']) && strtolower($child['email']) == $usernameLower) {
							writeLog('success', 'Plex User Check - Found User on Friends List', $username);
							$machineMatches = false;
							if ($GLOBALS['plexStrictFriends']) {
								foreach ($child->Server as $server) {
									if ((string)$server['machineIdentifier'] == $GLOBALS['plexID']) {
										$machineMatches = true;
									}
								}
							} else {
								$machineMatches = true;
							}
							if ($machineMatches) {
								writeLog('success', 'Plex User Check - User Approved for Login', $username);
								return true;
							} else {
								writeLog('error', 'Plex User Check - User not Approved User', $username);
							}
						}
					}
				}
			}
		}
		return false;
	} catch (Requests_Exception $e) {
		writeLog('error', 'Plex User Check Function - Error: ' . $e->getMessage(), $username);
	}
	return false;
}

function allPlexUsers($newOnly = false)
{
	try {
		if (!empty($GLOBALS['plexToken'])) {
			$url = 'https://plex.tv/api/users';
			$headers = array(
				'X-Plex-Token' => $GLOBALS['plexToken'],
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
								$taken = usernameTaken((string)$child['username'], (string)$child['email']);
								if (!$taken) {
									$results[] = array(
										'username' => (string)$child['username'],
										'email' => (string)$child['email']
									);
								}
							} else {
								$results[] = array(
									'username' => (string)$child['username'],
									'email' => (string)$child['email'],
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
		writeLog('success', 'Plex User Function - Error: ' . $e->getMessage(), $username);
	}
	return false;
}

function plugin_auth_plex($username, $password)
{
	try {
		$usernameLower = strtolower($username);
		//Login User
		$url = 'https://plex.tv/users/sign_in.json';
		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'X-Plex-Product' => 'Organizr',
			'X-Plex-Version' => '2.0',
			'X-Plex-Client-Identifier' => $GLOBALS['uuid'],
		);
		$data = array(
			'user[login]' => $username,
			'user[password]' => $password,
		);
		$response = Requests::post($url, $headers, $data);
		if ($response->success) {
			$json = json_decode($response->body, true);
			if ((is_array($json) && isset($json['user']) && isset($json['user']['username'])) && strtolower($json['user']['username']) == $usernameLower || strtolower($json['user']['email']) == $usernameLower) {
				//writeLog("success", $json['user']['username']." was logged into organizr using plex credentials");
				if ((!empty($GLOBALS['plexAdmin']) && (strtolower($GLOBALS['plexAdmin']) == strtolower($json['user']['username']) || strtolower($GLOBALS['plexAdmin']) == strtolower($json['user']['email']))) || checkPlexUser($json['user']['username'])) {
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
	} catch (Requests_Exception $e) {
		writeLog('success', 'Plex Auth Function - Error: ' . $e->getMessage(), $username);
	}
	return false;
}

if (function_exists('ldap_connect')) {
	// Pass credentials to LDAP backend
	function plugin_auth_ldap($username, $password)
	{
		if (!empty($GLOBALS['authBaseDN']) && !empty($GLOBALS['authBackendHost'])) {
			$ad = new \Adldap\Adldap();
			// Create a configuration array.
			$ldapServers = explode(',', $GLOBALS['authBackendHost']);
			$i = 0;
			foreach ($ldapServers as $key => $value) {
				// Calculate parts
				$digest = parse_url(trim($value));
				$scheme = strtolower((isset($digest['scheme']) ? $digest['scheme'] : 'ldap'));
				$host = (isset($digest['host']) ? $digest['host'] : (isset($digest['path']) ? $digest['path'] : ''));
				$port = (isset($digest['port']) ? $digest['port'] : (strtolower($scheme) == 'ldap' ? 389 : 636));
				// Reassign
				$ldapHosts[] = $host;
				$ldapServersNew[$key] = $scheme . '://' . $host . ':' . $port; // May use this later
				if ($i == 0) {
					$ldapPort = $port;
				}
				$i++;
			}
			$config = [
				// Mandatory Configuration Options
				'hosts' => $ldapHosts,
				'base_dn' => $GLOBALS['authBaseDN'],
				'username' => (empty($GLOBALS['ldapBindUsername'])) ? null : $GLOBALS['ldapBindUsername'],
				'password' => (empty($GLOBALS['ldapBindPassword'])) ? null : decrypt($GLOBALS['ldapBindPassword']),
				// Optional Configuration Options
				'schema' => (($GLOBALS['ldapType'] == '1') ? Adldap\Schemas\ActiveDirectory::class : (($GLOBALS['ldapType'] == '2') ? Adldap\Schemas\OpenLDAP::class : Adldap\Schemas\FreeIPA::class)),
				'account_prefix' => (empty($GLOBALS['authBackendHostPrefix'])) ? null : $GLOBALS['authBackendHostPrefix'],
				'account_suffix' => (empty($GLOBALS['authBackendHostSuffix'])) ? null : $GLOBALS['authBackendHostSuffix'],
				'port' => $ldapPort,
				'follow_referrals' => false,
				'use_ssl' => false,
				'use_tls' => false,
				'version' => 3,
				'timeout' => 5,
				// Custom LDAP Options
				'custom_options' => [
					// See: http://php.net/ldap_set_option
					//LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD
				]
			];
			// Add a connection provider to Adldap.
			$ad->addProvider($config);
			try {
				// If a successful connection is made to your server, the provider will be returned.
				$provider = $ad->connect();
				//prettyPrint($provider);
				if ($provider->auth()->attempt($username, $password)) {
					// Passed.
					return true;
				} else {
					// Failed.
					return false;
				}
			} catch (\Adldap\Auth\BindException $e) {
				writeLog('error', 'LDAP Function - Error: ' . $e->getMessage(), $username);
				// There was an issue binding / connecting to the server.
			} catch (Adldap\Auth\UsernameRequiredException $e) {
				writeLog('error', 'LDAP Function - Error: ' . $e->getMessage(), $username);
				// The user didn't supply a username.
			} catch (Adldap\Auth\PasswordRequiredException $e) {
				writeLog('error', 'LDAP Function - Error: ' . $e->getMessage(), $username);
				// The user didn't supply a password.
			}
		}
		return false;
	}
} else {
	// Ldap Auth Missing Dependency
	function plugin_auth_ldap_disabled()
	{
		return 'LDAP - Disabled (Dependency: php-ldap missing!)';
	}
}
// Pass credentials to FTP backend
function plugin_auth_ftp($username, $password)
{
	// Calculate parts
	$digest = parse_url($GLOBALS['authBackendHost']);
	$scheme = strtolower((isset($digest['scheme']) ? $digest['scheme'] : (function_exists('ftp_ssl_connect') ? 'ftps' : 'ftp')));
	$host = (isset($digest['host']) ? $digest['host'] : (isset($digest['path']) ? $digest['path'] : ''));
	$port = (isset($digest['port']) ? $digest['port'] : 21);
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
}

// Pass credentials to Emby Backend
function plugin_auth_emby_local($username, $password)
{
	try {
		$url = qualifyURL($GLOBALS['embyURL']) . '/Users/AuthenticateByName';
		$headers = array(
			'Authorization' => 'Emby UserId="e8837bc1-ad67-520e-8cd2-f629e3155721", Client="None", Device="Organizr", DeviceId="xxx", Version="1.0.0.0"',
			'Content-Type' => 'application/json',
		);
		$data = array(
			'Username' => $username,
			'pw' => $password,
			'Password' => sha1($password),
			'PasswordMd5' => md5($password),
		);
		$response = Requests::post($url, $headers, json_encode($data));
		if ($response->success) {
			$json = json_decode($response->body, true);
			if (is_array($json) && isset($json['SessionInfo']) && isset($json['User']) && $json['User']['HasPassword'] == true) {
				// Login Success - Now Logout Emby Session As We No Longer Need It
				$headers = array(
					'X-Emby-Token' => $json['AccessToken'],
					'X-Mediabrowser-Token' => $json['AccessToken'],
				);
				$response = Requests::post(qualifyURL($GLOBALS['embyURL']) . '/Sessions/Logout', $headers, array());
				if ($response->success) {
					return true;
				}
			}
		}
		return false;
	} catch (Requests_Exception $e) {
		writeLog('error', 'Emby Local Auth Function - Error: ' . $e->getMessage(), $username);
	}
	return false;
}

// Authenticate against emby connect
function plugin_auth_emby_connect($username, $password)
{
	// Emby disabled EmbyConnect on their API
	// https://github.com/MediaBrowser/Emby/issues/3553
	return plugin_auth_emby_local($username, $password);
	/*
	try {
		// Get A User
		$connectId = '';
		$url = qualifyURL($GLOBALS['embyURL']) . '/Users?api_key=' . $GLOBALS['embyToken'];
		$response = Requests::get($url);
		if ($response->success) {
			$json = json_decode($response->body, true);
			if (is_array($json)) {
				foreach ($json as $key => $value) { // Scan for this user
					if (isset($value['ConnectUserName']) && isset($value['ConnectUserId'])) { // Qualify as connect account
						if ($value['ConnectUserName'] == $username || $value['Name'] == $username) {
							$connectId = $value['ConnectUserId'];
							writeLog('success', 'Emby Connect Auth Function - Found User', $username);
							break;
						}
					}
				}
				if ($connectId) {
					writeLog('success', 'Emby Connect Auth Function - Attempting to Login with Emby ID: ' . $connectId, $username);
					$connectURL = 'https://connect.emby.media/service/user/authenticate';
					$headers = array(
						'Accept' => 'application/json',
						'X-Application' => 'Organizr/2.0'
					);
					$data = array(
						'nameOrEmail' => $username,
						'rawpw' => $password,
					);
					$response = Requests::post($connectURL, $headers, $data);
					if ($response->success) {
						$json = json_decode($response->body, true);
						if (is_array($json) && isset($json['AccessToken']) && isset($json['User']) && $json['User']['Id'] == $connectId) {
							return array(
								'email' => $json['User']['Email'],
								'image' => $json['User']['ImageUrl'],
							);
						} else {
							writeLog('error', 'Emby Connect Auth Function - Bad Response', $username);
						}
					} else {
						writeLog('error', 'Emby Connect Auth Function - 401 From Emby Connect', $username);
					}
				}
			}
		}
		return false;
	} catch (Requests_Exception $e) {
		writeLog('error', 'Emby Connect Auth Function - Error: ' . $e->getMessage(), $username);
		return false;
	}
	*/
}

// Authenticate Against Emby Local (first) and Emby Connect
function plugin_auth_emby_all($username, $password)
{
	// Emby disabled EmbyConnect on their API
	// https://github.com/MediaBrowser/Emby/issues/3553
	$localResult = plugin_auth_emby_local($username, $password);
	return $localResult;
	/*
	if ($localResult) {
		return $localResult;
	} else {
		return plugin_auth_emby_connect($username, $password);
	}
	*/
}
