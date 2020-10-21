<?php

trait SSOFunctions
{
	
	public function ssoCheck($username, $password, $token = null)
	{
		if ($this->config['ssoPlex'] && $token) {
			$this->coookie('set', 'mpt', $token, $this->config['rememberMeDays'], false);
		}
		if ($this->config['ssoOmbi']) {
			$fallback = ($this->config['ombiFallbackUser'] !== '' && $this->config['ombiFallbackPassword'] !== '');
			$ombiToken = $this->getOmbiToken($username, $password, $token, $fallback);
			if ($ombiToken) {
				$this->coookie('set', 'Auth', $ombiToken, $this->config['rememberMeDays'], false);
			}
		}
		if ($this->config['ssoTautulli']) {
			$tautulliToken = $this->getTautulliToken($username, $password, $token);
			if ($tautulliToken) {
				foreach ($tautulliToken as $key => $value) {
					$this->coookie('set', 'tautulli_token_' . $value['uuid'], $value['token'], $this->config['rememberMeDays'], true, $value['path']);
				}
			}
		}
		return true;
	}
	
	public function getOmbiToken($username, $password, $oAuthToken = null, $fallback = false)
	{
		$token = null;
		try {
			$url = $this->qualifyURL($this->config['ombiURL']);
			$headers = array(
				"Accept" => "application/json",
				"Content-Type" => "application/json"
			);
			$data = array(
				"username" => ($oAuthToken ? "" : $username),
				"password" => ($oAuthToken ? "" : $password),
				"rememberMe" => "true",
				"plexToken" => $oAuthToken
			);
			$endpoint = ($oAuthToken) ? '/api/v1/Token/plextoken' : '/api/v1/Token';
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
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
						"User-Agent" => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : null
					);
					$data = array(
						"username" => ($plexToken ? "" : $username),
						"password" => ($plexToken ? "" : $password),
						"token" => $plexToken,
						"remember_me" => 1,
					);
					$options = ($this->localURL($url)) ? array('verify' => false) : array();
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
	
}
