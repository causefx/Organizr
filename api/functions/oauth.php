<?php

trait OAuthFunctions
{
	public function traktOAuth()
	{
		$provider = new Bogstag\OAuth2\Client\Provider\Trakt([
			'clientId' => $this->config['traktClientId'],
			'clientSecret' => $this->config['traktClientSecret'],
			'redirectUri' => $this->getServerPath() . 'api/v2/oauth/trakt'
		]);
		if (!isset($_GET['code'])) {
			$authUrl = $provider->getAuthorizationUrl();
			header('Location: ' . $authUrl);
			exit;
		} elseif (empty($_GET['state'])) {
			exit('Invalid state');
		} else {
			try {
				$token = $provider->getAccessToken('authorization_code', [
					'code' => $_GET['code']
				]);
				$traktDetails = [
					'traktAccessToken' => $token->getToken(),
					'traktAccessTokenExpires' => gmdate('Y-m-d\TH:i:s\Z', $token->getExpires()),
					'traktRefreshToken' => $token->getRefreshToken()
				];
				$this->updateConfig($traktDetails);
				echo 'Details saved - Please close me!';
				exit;
			} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
				exit($e->getMessage());
			}
		}
	}
	
	public function traktOAuthRefresh()
	{
		$exp = $this->config['traktAccessTokenExpires'];
		$exp = date('Y-m-d\TH:i:s\Z', strtotime($exp . ' - 30 days'));
		if (time() - 2592000 > strtotime($exp)) {
			$headers = [
				'Content-Type' => 'application/json'
			];
			$data = [
				'refresh_token' => $this->config['traktRefreshToken'],
				'clientId' => $this->config['traktClientId'],
				'clientSecret' => $this->config['traktClientSecret'],
				'redirectUri' => $this->getServerPath() . 'api/v2/oauth/trakt',
				'grant_type' => 'refresh_token'
			];
			$url = $this->qualifyURL('https://api.trakt.tv/oauth/token');
			try {
				$response = Requests::post($url, $headers, json_encode($data), []);
				if ($response->success) {
					$data = json_decode($response->body, true);
					$newExp = date('Y-m-d\TH:i:s\Z', strtotime($this->currentTime . ' + 90 days'));
					$traktDetails = [
						'traktAccessToken' => $data['access_token'],
						'traktAccessTokenExpires' => $newExp,
						'traktRefreshToken' => $data['refresh_token']
					];
					$this->updateConfig($traktDetails);
					return true;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'Trakt Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			}
		}
		
	}
}