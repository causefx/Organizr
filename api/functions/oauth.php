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
				echo '
					<!DOCTYPE html>
					<html lang="en">
					<head>
						<link rel="stylesheet" href="' . $this->getServerPath() . '/css/mvp.css">
						<meta charset="utf-8">
						<meta name="description" content="Trakt OAuth">
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<title>Trakt OAuth</title>
					</head>
					<script language=javascript>
					function closemyself() {
						window.opener=self;
						window.close();
					}
					</script>
					<body onLoad="setTimeout(\'closemyself()\',3000);">
						<main>
							<section>
								<aside>
									<h3>Details Saved</h3>
									<p><sup>(This window will close automatically)</sup></p>
								</aside>
							</section>
						</main>
					</body>
					</html>
				';
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