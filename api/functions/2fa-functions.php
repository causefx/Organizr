<?php

trait TwoFAFunctions
{
	public function create2FA($type)
	{
		$result['type'] = $type;
		switch ($type) {
			case 'google':
				try {
					$google2fa = new PragmaRX\Google2FA\Google2FA();
					$google2fa->setAllowInsecureCallToGoogleApis(true);
					$result['secret'] = $google2fa->generateSecretKey();
					$result['url'] = $google2fa->getQRCodeGoogleUrl(
						$this->config['title'],
						$this->user['username'],
						$result['secret']
					);
				} catch (PragmaRX\Google2FA\Exceptions\InsecureCallException $e) {
					$this->setAPIResponse('error', $e->getMessage(), 500);
					return null;
				}
				break;
			default:
				$this->setAPIResponse('error', $type . ' is not an available to be setup', 404);
				return null;
		}
		$this->setAPIResponse('success', '2FA code created - awaiting verification', 200);
		return $result;
	}

	public function verify2FA($secret, $code, $type)
	{
		if (!$secret || $secret == '') {
			$this->setAPIResponse('error', 'Secret was not supplied or left blank', 422);
			return false;
		}
		if (!$code || $code == '') {
			$this->setAPIResponse('error', 'Code was not supplied or left blank', 422);
			return false;
		}
		if (!$type || $type == '') {
			$this->setAPIResponse('error', 'Type was not supplied or left blank', 422);
			return false;
		}
		switch ($type) {
			case 'google':
				$google2fa = new PragmaRX\Google2FA\Google2FA();
				$google2fa->setWindow(5);
				$valid = $google2fa->verifyKey($secret, $code);
				break;
			default:
				$this->setAPIResponse('error', $type . ' is not an available to be setup', 404);
				return false;
		}
		if ($valid) {
			$this->setAPIResponse('success', 'Verification code verified', 200);
			return true;
		} else {
			$this->setAPIResponse('success', 'Verification code invalid', 401);
			return false;
		}
	}

	public function save2FA($secret, $type)
	{
		if (!$secret || $secret == '') {
			$this->setAPIResponse('error', 'Secret was not supplied or left blank', 422);
			return false;
		}
		if (!$type || $type == '') {
			$this->setAPIResponse('error', 'Type was not supplied or left blank', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['auth_service' => $type . '::' . $secret],
					'WHERE id = ?',
					$this->user['userID']
				)
			),
		];
		$this->writeLog('success', 'User Management Function - User added 2FA', $this->user['username']);
		$this->setAPIResponse('success', '2FA Added', 200);
		return $this->processQueries($response);
	}

	public function remove2FA()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['auth_service' => 'internal'],
					'WHERE id = ?',
					$this->user['userID']
				)
			),
		];
		$this->writeLog('success', 'User Management Function - User removed 2FA', $this->user['username']);
		$this->setAPIResponse('success', '2FA deleted', 204);
		return $this->processQueries($response);
	}
}
