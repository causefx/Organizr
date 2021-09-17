<?php

trait TokenFunctions
{
	public function jwtParse($token)
	{
		try {
			$result = array();
			$result['valid'] = false;
			// Check Token with JWT
			// Set key
			if (!isset($this->config['organizrHash'])) {
				return null;
			}
			$key = $this->config['organizrHash'];
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
					$result['username'] = ($jwttoken->hasClaim('username')) ? $jwttoken->getClaim('username') : 'N/A';
					$result['group'] = ($jwttoken->hasClaim('group')) ? $jwttoken->getClaim('group') : 'N/A';
					$result['groupID'] = $jwttoken->getClaim('groupID');
					$result['userID'] = $jwttoken->getClaim('userID');
					$result['email'] = $jwttoken->getClaim('email');
					$result['image'] = $jwttoken->getClaim('image');
					$result['tokenExpire'] = $jwttoken->getClaim('exp');
					$result['tokenDate'] = $jwttoken->getClaim('iat');
					//$result['token'] = $jwttoken->getClaim('exp');
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
}