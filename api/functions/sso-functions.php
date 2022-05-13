<?php

trait SSOFunctions
{
	public function ssoCookies()
	{
		$cookies = array(
			'myPlexAccessToken' => $_COOKIE['mpt'] ?? false,
			'id_token' => $_COOKIE['Auth'] ?? false,
			'jellyfin_credentials' => $_COOKIE['jellyfin_credentials'] ?? false,
			'komga_token' => $_COOKIE['komga_token'] ?? false
		);
		// Jellyfin cookie
		foreach (array_keys($_COOKIE) as $k => $v) {
			if (strpos($v, 'user-') !== false) {
				$cookiesToAdd = [
					$v => $_COOKIE[$v]
				];
				$cookies = array_merge($cookies, $cookiesToAdd);
			}
		}
		return $cookies;
	}

	public function getSSOUserFor($app, $userobj)
	{
		$map = array(
			'jellyfin' => 'username',
			'ombi' => 'username',
			'overseerr' => 'email',
			'tautulli' => 'username',
			'petio' => 'username',
			'komga' => 'email'
		);
		return (gettype($userobj) == 'string') ? $userobj : $userobj[$map[$app]];
	}

	public function ssoCheck($userobj, $password, $token = null)
	{
		$this->setCurrentUser(false);
		$this->setLoggerChannel('Authentication', $this->user['username']);
		$this->logger->debug('Starting SSO check function');
		if ($this->config['ssoPlex'] && $token) {
			$this->logger->debug('Setting Plex SSO cookie');
			$this->coookie('set', 'mpt', $token, $this->config['rememberMeDays'], false);
		}
		if ($this->config['ssoOmbi']) {
			$this->logger->debug('Starting Ombi SSO check function');
			$fallback = ($this->config['ombiFallbackUser'] !== '' && $this->config['ombiFallbackPassword'] !== '');
			$ombiToken = $this->getOmbiToken($this->getSSOUserFor('ombi', $userobj), $password, $token, $fallback);
			if ($ombiToken) {
				$this->logger->debug('Setting Ombi SSO cookie');
				$this->coookie('set', 'Auth', $ombiToken, $this->config['rememberMeDays'], false);
			} else {
				$this->logger->debug('No Ombi token received from backend');
			}
		}
		if ($this->config['ssoTautulli'] && $this->qualifyRequest($this->config['ssoTautulliAuth'])) {
			$this->logger->debug('Starting Tautulli SSO check function');
			$tautulliToken = $this->getTautulliToken($this->getSSOUserFor('tautulli', $userobj), $password, $token);
			if ($tautulliToken) {
				foreach ($tautulliToken as $key => $value) {
					$this->logger->debug('Setting Tautulli SSO cookie');
					$this->coookie('set', 'tautulli_token_' . $value['uuid'], $value['token'], $this->config['rememberMeDays'], true, $value['path']);
				}
			} else {
				$this->logger->debug('No Tautulli token received from backend');
			}
		}
		if ($this->config['ssoJellyfin']) {
			$this->logger->debug('Starting Jellyfin SSO check function');
			$jellyfinToken = $this->getJellyfinToken($this->getSSOUserFor('jellyfin', $userobj), $password);
			if ($jellyfinToken) {
				foreach ($jellyfinToken as $k => $v) {
					$this->logger->debug('Setting Jellyfin SSO cookie');
					$this->coookie('set', $k, $v, $this->config['rememberMeDays'], false);
				}
			} else {
				$this->logger->debug('No Jellyfin token received from backend');
			}
		}
		if ($this->config['ssoOverseerr']) {
			$this->logger->debug('Starting Overseerr SSO check function');
			$fallback = ($this->config['overseerrFallbackUser'] !== '' && $this->config['overseerrFallbackPassword'] !== '');
			$overseerrToken = $this->getOverseerrToken($this->getSSOUserFor('overseerr', $userobj), $password, $token, $fallback);
			if ($overseerrToken) {
				$this->logger->debug('Setting Overseerr SSO cookie');
				$this->coookie('set', 'connect.sid', $overseerrToken, $this->config['rememberMeDays'], false);
			} else {
				$this->logger->debug('No Overseerr token received from backend');
			}
		}
		if ($this->config['ssoPetio']) {
			$this->logger->debug('Starting Petio SSO check function');
			$fallback = ($this->config['petioFallbackUser'] !== '' && $this->config['petioFallbackPassword'] !== '');
			$petioToken = $this->getPetioToken($this->getSSOUserFor('petio', $userobj), $password, $token, $fallback);
			if ($petioToken) {
				$this->logger->debug('Setting Petio SSO cookie');
				$this->coookie('set', 'petio_jwt', $petioToken, $this->config['rememberMeDays'], false);
			} else {
				$this->logger->debug('No Petio token received from backend');
			}
		}
		if ($this->config['ssoKomga'] && $this->qualifyRequest($this->config['ssoKomgaAuth'])) {
			$this->logger->debug('Starting Komga SSO check function');
			$fallback = ($this->config['komgaFallbackUser'] !== '' && $this->config['komgaFallbackPassword'] !== '');
			$komga = $this->getKomgaToken($this->getSSOUserFor('komga', $userobj), $password, $fallback);
			if ($komga) {
				$this->logger->debug('Setting Komga SSO cookie');
				$this->coookie('set', 'komga_token', $komga, $this->config['rememberMeDays'], false);
			} else {
				$this->logger->debug('No Komga token received from backend');
			}
		}
		return true;
	}

	public function getKomgaToken($email, $password, $fallback = false)
	{
		$token = null;
		try {
			$credentials = array('auth' => new Requests_Auth_Digest(array($email, $password)));
			$url = $this->qualifyURL($this->config['komgaURL']);
			$options = $this->requestOptions($url, 60000, true, false, $credentials);
			$response = Requests::get($url . '/api/v1/users/me', ['X-Auth-Token' => 'organizrSSO'], $options);
			if ($response->success) {
				if ($response->headers['x-auth-token']) {
					$this->setLoggerChannel('Komga')->info('Grabbed token');
					$token = $response->headers['x-auth-token'];
				} else {
					$this->setLoggerChannel('Komga')->warning('Komga did not return Token');
				}
			} else {
				if ($fallback) {
					$this->setLoggerChannel('Komga')->warning('Komga did not return Token - Will retry using fallback credentials');
				} else {
					$this->setLoggerChannel('Komga')->warning('Komga did not return Token');
				}
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Komga')->error($e);
		}
		if ($token) {
			return $token;
		} elseif ($fallback) {
			return $this->getKomgaToken($this->config['komgaFallbackUser'], $this->decrypt($this->config['komgaFallbackPassword']), false);
		} else {
			return false;
		}
	}

	public function getJellyfinToken($username, $password)
	{
		$token = null;
		try {
			$url = $this->qualifyURL($this->config['jellyfinURL']);
			$ssoUrl = $this->qualifyURL($this->config['jellyfinSSOURL']);
			$headers = array(
				'X-Emby-Authorization' => 'MediaBrowser Client="Organizr Jellyfin Tab", Device="Organizr_PHP", DeviceId="Organizr_SSO", Version="1.0"',
				"Accept" => "application/json",
				"Content-Type" => "application/json",
				"X-Forwarded-For" => $this->userIP()
			);
			$data = array(
				"Username" => $username,
				"Pw" => $password
			);
			$endpoint = '/Users/authenticatebyname';
			$options = $this->requestOptions($url, 60000);
			$response = Requests::post($url . $endpoint, $headers, json_encode($data), $options);
			if ($response->success) {
				$token = json_decode($response->body, true);
				$this->writeLog('success', 'Jellyfin Token Function - Grabbed token.', $username);
				$key = 'user-' . $token['User']['Id'] . '-' . $token['ServerId'];
				$jellyfin[$key] = json_encode($token['User']);
				$jellyfin['jellyfin_credentials'] = '{"Servers":[{"ManualAddress":"' . $ssoUrl . '","Id":"' . $token['ServerId'] . '","UserId":"' . $token['User']['Id'] . '","AccessToken":"' . $token['AccessToken'] . '"}]}';
				return $jellyfin;
			} else {
				$this->writeLog('error', 'Jellyfin Token Function - Jellyfin did not return Token', $username);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Token Function - Error: ' . $e->getMessage(), $username);
		}
		return false;
	}

	public function getOmbiToken($username, $password, $oAuthToken = null, $fallback = false)
	{
		$token = null;
		try {
			$url = $this->qualifyURL($this->config['ombiURL']);
			$headers = array(
				"Accept" => "application/json",
				"Content-Type" => "application/json",
				"X-Forwarded-For" => $this->userIP()
			);
			$data = array(
				"username" => ($oAuthToken ? "" : $username),
				"password" => ($oAuthToken ? "" : $password),
				"rememberMe" => "true",
				"plexToken" => $oAuthToken
			);
			$endpoint = ($oAuthToken) ? '/api/v1/Token/plextoken' : '/api/v1/Token';
			$options = $this->requestOptions($url, 60000);
			$response = Requests::post($url . $endpoint, $headers, json_encode($data), $options);
			if ($response->success) {
				$token = json_decode($response->body, true)['access_token'];
				$this->writeLog('success', 'Ombi Token Function - Grabbed token.', $username);
			} else {
				if ($fallback) {
					$this->writeLog('error', 'Ombi Token Function - Ombi did not return Token - Will retry using fallback credentials', $username);
				} else {
					$this->writeLog('error', 'Ombi Token Function - Ombi did not return Token', $username);
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Ombi Token Function - Error: ' . $e->getMessage(), $username);
		}
		if ($token) {
			return $token;
		} elseif ($fallback) {
			return $this->getOmbiToken($this->config['ombiFallbackUser'], $this->decrypt($this->config['ombiFallbackPassword']), null, false);
		} else {
			return false;
		}
	}

	public function getTautulliToken($username, $password, $plexToken = null)
	{
		$token = null;
		$tautulliURLList = explode(',', $this->config['tautulliURL']);
		if (count($tautulliURLList) !== 0) {
			foreach ($tautulliURLList as $key => $value) {
				try {
					$url = $this->qualifyURL($value);
					$headers = array(
						"Accept" => "application/json",
						"Content-Type" => "application/x-www-form-urlencoded",
						"User-Agent" => $_SERVER ['HTTP_USER_AGENT'] ?? null,
						"X-Forwarded-For" => $this->userIP()
					);
					$data = array(
						"username" => ($plexToken ? "" : $username),
						"password" => ($plexToken ? "" : $password),
						"token" => $plexToken,
						"remember_me" => 1,
					);
					$options = $this->requestOptions($url, 60000);
					$response = Requests::post($url . '/auth/signin', $headers, $data, $options);
					if ($response->success) {
						$qualifiedURL = $this->qualifyURL($url, true);
						$path = ($qualifiedURL['path']) ? $qualifiedURL['path'] : '/';
						$token[$key]['token'] = json_decode($response->body, true)['token'];
						$token[$key]['uuid'] = json_decode($response->body, true)['uuid'];
						$token[$key]['path'] = $path;
						$this->writeLog('success', 'Tautulli Token Function - Grabbed token from: ' . $url, $username);
					} else {
						$this->writeLog('error', 'Tautulli Token Function - Error on URL: ' . $url, $username);
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Tautulli Token Function - Error: [' . $url . ']' . $e->getMessage(), $username);
				}
			}
		}
		return ($token) ? $token : false;
	}

	public function getOverseerrToken($email, $password, $oAuthToken = null, $fallback = false)
	{
		$token = null;
		try {
			$url = $this->qualifyURL($this->config['overseerrURL']);
			$headers = array(
				"Content-Type" => "application/json",
				"X-Forwarded-For" => $this->userIP()
			);
			$data = array(
				"email" => ($oAuthToken ? "" : $email), // not needed yet
				"password" => ($oAuthToken ? "" : $password), // not needed yet
				"authToken" => $oAuthToken
			);
			$endpoint = ($oAuthToken ? '/api/v1/auth/plex' : '/api/v1/auth/local');
			$options = $this->requestOptions($url, 60000);
			$response = Requests::post($url . $endpoint, $headers, json_encode($data), $options);
			if ($response->success) {
				$user = json_decode($response->body, true); // not really needed yet
				$token = $response->cookies['connect.sid']->value;
				$this->writeLog('success', 'Overseerr Token Function - Grabbed token', $user['plexUsername'] ?? $email);
			} else {
				if ($fallback) {
					$this->writeLog('error', 'Overseerr Token Function - Overseerr did not return Token - Will retry using fallback credentials', $email);
				} else {
					$this->writeLog('error', 'Overseerr Token Function - Overseerr did not return Token', $email);
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Overseerr Token Function - Error: ' . $e->getMessage(), $email);
		}
		if ($token) {
			return urldecode($token);
		} elseif ($fallback) {
			return $this->getOverseerrToken($this->config['overseerrFallbackUser'], $this->decrypt($this->config['overseerrFallbackPassword']), null, false);
		} else {
			return false;
		}
	}

	public function getPetioToken($username, $password, $oAuthToken = null, $fallback = false)
	{
		$token = null;
		try {
			$url = $this->qualifyURL($this->config['petioURL']);
			$headers = array(
				"Content-Type" => "application/json",
				"X-Forwarded-For" => $this->userIP()
			);
			$data = array(
				'user' => [
					'username' => ($oAuthToken ? '' : $username),
					'password' => ($oAuthToken ? '' : $password),
					'type' => 1,
				],
				'authToken' => false,
				'token' => $oAuthToken
			);
			$endpoint = ($oAuthToken) ? '/api/login/plex_login' : '/api/login';
			$options = $this->requestOptions($url, 60000);
			$response = Requests::post($url . $endpoint, $headers, json_encode($data), $options);
			if ($response->success) {
				$user = json_decode($response->body, true)['user'];
				$token = json_decode($response->body, true)['token'];
				$this->writeLog('success', 'Petio Token Function - Grabbed token', $user['username']);
			} else {
				if ($fallback) {
					$this->writeLog('error', 'Petio Token Function - Petio did not return Token - Will retry using fallback credentials', $username);
				} else {
					$this->writeLog('error', 'Petio Token Function - Petio did not return Token', $username);
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Petio Token Function - Error: ' . $e->getMessage(), $username);
		}
		if ($token) {
			return $token;
		} elseif ($fallback) {
			return $this->getPetioToken($this->config['petioFallbackUser'], $this->decrypt($this->config['petioFallbackPassword']), null, false);
		} else {
			return false;
		}
	}
}