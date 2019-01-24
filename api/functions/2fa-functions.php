<?php
function create2FA($type)
{
	$result['type'] = $type;
	switch ($type) {
		case 'google':
			try {
				$google2fa = new PragmaRX\Google2FA\Google2FA();
				$google2fa->setAllowInsecureCallToGoogleApis(true);
				$result['secret'] = $google2fa->generateSecretKey();
				$result['url'] = $google2fa->getQRCodeGoogleUrl(
					$GLOBALS['title'],
					$GLOBALS['organizrUser']['username'],
					$result['secret']
				);
			} catch (PragmaRX\Google2FA\Exceptions\InsecureCallException $e) {
				return false;
			}
			break;
		default:
			return false;
	}
	return $result;
}

function save2FA($secret, $type)
{
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$connect->query('
            UPDATE users SET', [
			'auth_service' => $type . '::' . $secret
		], '
            WHERE id=?', $GLOBALS['organizrUser']['userID']);
		writeLog('success', 'User Management Function - User added 2FA', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (Dibi\Exception $e) {
		writeLog('error', 'User Management Function - Error Adding User 2FA', $GLOBALS['organizrUser']['username']);
		return false;
	}
}

function verify2FA($secret, $code, $type)
{
	switch ($type) {
		case 'google':
			$google2fa = new PragmaRX\Google2FA\Google2FA();
			$google2fa->setWindow(5);
			$valid = $google2fa->verifyKey($secret, $code);
			break;
		default:
			return false;
	}
	return ($valid) ? true : false;
}

function remove2FA()
{
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$connect->query('
            UPDATE users SET', [
			'auth_service' => 'internal'
		], '
            WHERE id=?', $GLOBALS['organizrUser']['userID']);
		writeLog('success', 'User Management Function - User removed 2FA', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (Dibi\Exception $e) {
		writeLog('error', 'User Management Function - Error Removing User 2FA', $GLOBALS['organizrUser']['username']);
		return false;
	}
}