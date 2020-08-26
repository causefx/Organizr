<?php
/** @noinspection PhpUndefinedFieldInspection */

trait HomepageConnectFunctions
{
	public function csvHomepageUrlToken($url, $token)
	{
		$list = array();
		$urlList = explode(',', $url);
		$tokenList = explode(',', $token);
		if (count($urlList) == count($tokenList)) {
			foreach ($urlList as $key => $value) {
				$list[$key] = array(
					'url' => $this->qualifyURL($value),
					'token' => $tokenList[$key]
				);
			}
		}
		return $list;
	}
	
	public function streamType($value)
	{
		if ($value == "transcode" || $value == "Transcode") {
			return "Transcode";
		} elseif ($value == "copy" || $value == "DirectStream") {
			return "Direct Stream";
		} elseif ($value == "directplay" || $value == "DirectPlay") {
			return "Direct Play";
		} else {
			return "Direct Play";
		}
	}
	
	public function getCacheImageSize($type)
	{
		switch ($type) {
			case 'height':
			case 'h':
				return 300 * $this->config['cacheImageSize'];
			case 'width':
			case 'w':
				return 200 * $this->config['cacheImageSize'];
			case 'nowPlayingHeight':
			case 'nph':
				return 675 * $this->config['cacheImageSize'];
			case 'nowPlayingWidth':
			case 'npw':
				return 1200 * $this->config['cacheImageSize'];
			
		}
	}
	
	public function ombiImport($type = null)
	{
		if (!empty($this->config['ombiURL']) && !empty($this->config['ombiToken']) && !empty($type)) {
			try {
				$url = $this->qualifyURL($this->config['ombiURL']);
				$headers = array(
					"Accept" => "application/json",
					"Content-Type" => "application/json",
					"Apikey" => $GLOBALS['ombiToken']
				);
				$options = ($this->localURL($url)) ? array('verify' => false) : array();
				switch ($type) {
					case 'emby':
					case 'emby_local':
					case 'emby_connect':
					case 'emby_all':
						$response = Requests::post($url . "/api/v1/Job/embyuserimporter", $headers, $options);
						break;
					case 'plex':
						$response = Requests::post($url . "/api/v1/Job/plexuserimporter", $headers, $options);
						break;
					default:
						return false;
						break;
				}
				if ($response->success) {
					$this->writeLog('success', 'OMBI Connect Function - Ran User Import', 'SYSTEM');
					return true;
				} else {
					$this->writeLog('error', 'OMBI Connect Function - Error: Connection Unsuccessful', 'SYSTEM');
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'OMBI Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				return false;
			}
		}
		return false;
	}
}


function testAPIConnection($array)
{
	switch ($array['data']['action']) {
		case 'ldap_login':
			$username = $array['data']['data']['username'];
			$password = $array['data']['data']['password'];
			if (empty($username) || empty($password)) {
				return 'Missing Username or Password';
			}
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
					'use_ssl' => $GLOBALS['ldapSSL'],
					'use_tls' => $GLOBALS['ldapTLS'],
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
					if ($provider->auth()->attempt($username, $password, true)) {
						// Passed.
						$user = $provider->search()->find($username);
						//return $user->getFirstAttribute('cn');
						//return $user->getGroups(['cn']);
						//return $user;
						//return $user->getUserPrincipalName();
						//return $user->getGroups(['cn']);
						return true;
					} else {
						// Failed.
						return 'Username/Password Failed to authenticate';
					}
				} catch (\Adldap\Auth\BindException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), $username);
					return $detailedError->getErrorMessage();
					// There was an issue binding / connecting to the server.
				} catch (Adldap\Auth\UsernameRequiredException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), $username);
					return $detailedError->getErrorMessage();
					// The user didn't supply a username.
				} catch (Adldap\Auth\PasswordRequiredException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), $username);
					return $detailedError->getErrorMessage();
					// The user didn't supply a password.
				}
			}
			break;
		case 'ldap':
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
					'account_prefix' => '',
					'account_suffix' => '',
					'port' => $ldapPort,
					'follow_referrals' => false,
					'use_ssl' => $GLOBALS['ldapSSL'],
					'use_tls' => $GLOBALS['ldapTLS'],
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
				} catch (\Adldap\Auth\BindException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), 'SYSTEM');
					return $detailedError->getErrorMessage();
					// There was an issue binding / connecting to the server.
				}
				return ($provider) ? true : false;
			}
			return false;
			break;
		default :
			return false;
	}
	return false;
}
