<?php
function ssoCheck($username, $password, $token = null)
{
	$test = '';
	if ($GLOBALS['ssoPlex'] && $token) {
		coookie('set', 'mpt', $token, 7);
	}
	if ($GLOBALS['ssoOmbi']) {
		$ombiToken = getOmbiToken($username, $password);
		if ($ombiToken) {
			coookie('set', 'Auth', $ombiToken, 7, false);
		}
	}
	if ($GLOBALS['ssoTautulli']) {
		$tautulliToken = getTautulliToken($username, $password, $token);
		if ($tautulliToken) {
			foreach ($tautulliToken as $key => $value) {
				coookie('set', 'tautulli_token_' . $value['uuid'], $value['token'], 7, false);
			}
		}
	}
	return true;
}

function getOmbiToken($username, $password, $oAuthToken = null)
{
	$token = null;
	try {
		$url = qualifyURL($GLOBALS['ombiURL']);
		$headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/json"
		);
		$data = array(
			"username" => $username,
			"password" => $password,
			"rememberMe" => "true"
		);
		$options = (localURL($url)) ? array('verify' => false) : array();
		$response = Requests::post($url . '/api/v1/Token', $headers, json_encode($data), $options);
		if ($response->success) {
			$token = json_decode($response->body, true)['access_token'];
			writeLog('success', 'Ombi Token Function - Grabbed token.', $username);
		}
	} catch (Requests_Exception $e) {
		writeLog('success', 'Ombi Token Function - Error: ' . $e->getMessage(), $username);
	};
	return ($token) ? $token : false;
}

function getTautulliToken($username, $password, $plexToken = null)
{
	$token = null;
	$tautulliURLList = explode(',', $GLOBALS['tautulliURL']);
	if (count($tautulliURLList) !== 0) {
		foreach ($tautulliURLList as $key => $value) {
			try {
				$url = $value . '/auth/signin';
				$headers = array(
					"Accept" => "application/json",
					"Content-Type" => "application/x-www-form-urlencoded",
					"User-Agent" => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : null
				);
				$data = array(
					"username" => $username,
					"password" => $password,
					"token" => $plexToken,
					"remember_me" => 1,
				);
				$options = (localURL($url)) ? array('verify' => false) : array();
				$response = Requests::post($url, $headers, $data, $options);
				if ($response->success) {
					$token[$key]['token'] = json_decode($response->body, true)['token'];
					$token[$key]['uuid'] = json_decode($response->body, true)['uuid'];
					writeLog('success', 'Tautulli Token Function - Grabbed token from: ' . $url, $username);
				} else {
					writeLog('error', 'Tautulli Token Function - Error on URL: ' . $url, $username);
				}
			} catch (Requests_Exception $e) {
				writeLog('error', 'Tautulli Token Function - Error: [' . $url . ']' . $e->getMessage(), $username);
			};
		}
	}
	return ($token) ? $token : false;
}
