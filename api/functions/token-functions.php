<?php
function jwtParse($token)
{
	try {
		$result = array();
		$result['valid'] = false;
		// Check Token with JWT
		// Set key
		if (!isset($GLOBALS['organizrHash'])) {
			return null;
		}
		$key = $GLOBALS['organizrHash'];
		// SHA256 Encryption
		$signer = new Lcobucci\JWT\Signer\Hmac\Sha256();
		$jwttoken = (new Lcobucci\JWT\Parser())->parse((string)$token); // Parses from a string
		$jwttoken->getHeaders(); // Retrieves the token header
		$jwttoken->getClaims(); // Retrieves the token claims
		// Start Validation
		if ($jwttoken->verify($signer, $key)) {
			$data = new Lcobucci\JWT\ValidationData(); // It will use the current time to validate (iat, nbf and exp)
			$data->setIssuer('Organizr');
			$data->setAudience('Organizr');
			if ($jwttoken->validate($data)) {
				$result['valid'] = true;
				$result['username'] = $jwttoken->getClaim('username');
				$result['group'] = $jwttoken->getClaim('group');
				$result['groupID'] = $jwttoken->getClaim('groupID');
				$result['userID'] = $jwttoken->getClaim('userID');
				$result['email'] = $jwttoken->getClaim('email');
				$result['image'] = $jwttoken->getClaim('image');
				$result['tokenExpire'] = $jwttoken->getClaim('exp');
				$result['tokenDate'] = $jwttoken->getClaim('iat');
				$result['token'] = $jwttoken->getClaim('exp');
			}
		}
		if ($result['valid'] == true) {
			return $result;
		} else {
			return false;
		}
	} catch (\RunException $e) {
		return false;
	} catch (\OutOfBoundsException $e) {
		return false;
	} catch (\RunTimeException $e) {
		return false;
	} catch (\InvalidArgumentException $e) {
		return false;
	}
}

function createToken($username, $email, $image, $group, $groupID, $key, $days = 1)
{
	if (!isset($GLOBALS['dbLocation']) || !isset($GLOBALS['dbName'])) {
		return false;
	}
	//Quick get user ID
	try {
		$database = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$result = $database->fetch('SELECT * FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE', $username, $email);
		// Create JWT
		// Set key
		// SHA256 Encryption
		$signer = new Lcobucci\JWT\Signer\Hmac\Sha256();
		// Start Builder
		$jwttoken = (new Lcobucci\JWT\Builder())->setIssuer('Organizr')// Configures the issuer (iss claim)
		->setAudience('Organizr')// Configures the audience (aud claim)
		->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
		->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
		->setExpiration(time() + (86400 * $days))// Configures the expiration time of the token (exp claim)
		->set('username', $result['username'])// Configures a new claim, called "username"
		->set('group', $result['group'])// Configures a new claim, called "group"
		->set('groupID', $result['group_id'])// Configures a new claim, called "groupID"
		->set('email', $result['email'])// Configures a new claim, called "email"
		->set('image', $result['image'])// Configures a new claim, called "image"
		->set('userID', $result['id'])// Configures a new claim, called "image"
		->sign($signer, $key)// creates a signature using "testing" as key
		->getToken(); // Retrieves the generated token
		$jwttoken->getHeaders(); // Retrieves the token headers
		$jwttoken->getClaims(); // Retrieves the token claims
		coookie('set', $GLOBALS['cookieName'], $jwttoken, $days);
		// Add token to DB
		$addToken = [
			'token' => (string)$jwttoken,
			'user_id' => $result['id'],
			'created' => $GLOBALS['currentTime'],
			'browser' => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : null,
			'ip' => userIP(),
			'expires' => gmdate("Y-m-d\TH:i:s\Z", time() + (86400 * $days))
		];
		$database->query('INSERT INTO [tokens]', $addToken);
		return $jwttoken;
	} catch (Dibi\Exception $e) {
		writeLog('error', 'Token Error: ' . $e, 'SYSTEM');
		return false;
	}
}

function validateToken($token, $global = false)
{
	// Validate script
	$userInfo = jwtParse($token);
	$validated = $userInfo ? true : false;
	if ($validated == true) {
		if ($global == true) {
			try {
				$database = new Dibi\Connection([
					'driver' => 'sqlite3',
					'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
				]);
				$all = $database->fetchAll('SELECT * FROM `tokens` WHERE `user_id` = ? AND `expires` > ?', $userInfo['userID'], $GLOBALS['currentTime']);
				$tokenCheck = (searchArray($all, 'token', $token) !== false);
				if (!$tokenCheck) {
					// Delete cookie & reload page
					coookie('delete', $GLOBALS['cookieName']);
					$GLOBALS['organizrUser'] = false;
				}
				$result = $database->fetch('SELECT * FROM users WHERE id = ?', $userInfo['userID']);
				$GLOBALS['organizrUser'] = array(
					"token" => $token,
					"tokenDate" => $userInfo['tokenDate'],
					"tokenExpire" => $userInfo['tokenExpire'],
					"username" => $result['username'],
					"uid" => guestHash(0, 5),
					"group" => $result['group'],
					"groupID" => $result['group_id'],
					"email" => $result['email'],
					"image" => $result['image'],
					"userID" => $result['id'],
					"loggedin" => true,
					"locked" => $result['locked'],
					"tokenList" => $all,
					"authService" => explode('::', $result['auth_service'])[0]
				);
			} catch (Dibi\Exception $e) {
				$GLOBALS['organizrUser'] = false;
			}
		}
	} else {
		// Delete cookie & reload page
		coookie('delete', $GLOBALS['cookieName']);
		$GLOBALS['organizrUser'] = false;
	}
}

function getOrganizrUserToken()
{
	if (isset($_COOKIE[$GLOBALS['cookieName']])) {
		// Get token form cookie and validate
		validateToken($_COOKIE[$GLOBALS['cookieName']], true);
	} else {
		$GLOBALS['organizrUser'] = array(
			"token" => null,
			"tokenDate" => null,
			"tokenExpire" => null,
			"username" => "Guest",
			"uid" => guestHash(0, 5),
			"group" => getGuest()['group'],
			"groupID" => getGuest()['group_id'],
			"email" => null,
			//"groupImage"=>getGuest()['image'],
			"image" => getGuest()['image'],
			"userID" => null,
			"loggedin" => false,
			"locked" => false,
			"tokenList" => null,
			"authService" => null
		);
	}
}
