<?php

trait OverseerrHomepageItem
{

	public function overseerrSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Overseerr',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/overseerr.png',
			'category' => 'Requests',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageOverseerrEnabled'),
					$this->settingsOption('auth', 'homepageOverseerrAuth'),
					$this->settingsOption('notice', '', ['title' => 'Attention', 'body' => 'Since Organizr supports multiple Request Providers, You must now select which service you want to submit requests through']),
					$this->settingsOption('select', 'defaultRequestService', ['label' => 'Default Request Service', 'options' => $this->requestServiceOptions()]),
				],
				'Connection' => [
					$this->settingsOption('url', 'overseerrURL'),
					$this->settingsOption('token', 'overseerrToken'),
					$this->settingsOption('username', 'overseerrFallbackUser', ['label' => 'Overseerr Fallback User', 'help' => 'Organizr will request an Overseerr User Token based off of this user credentials']),
					$this->settingsOption('password', 'overseerrFallbackPassword', ['label' => 'Overseerr Fallback Password',]),
					$this->settingsOption('disable-cert-check', 'overseerrDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'overseerrUseCustomCertificate'),
				],
				'Misc Options' => [
					$this->settingsOption('auth', 'homepageOverseerrRequestAuth', ['label' => 'Minimum Group to Request']),
					$this->settingsOption('select', 'overseerrTvDefault', ['label' => 'TV Show Default Request', 'options' => $this->requestTvOptions(true)]),
					$this->settingsOption('switch', 'overseerrLimitUser', ['label' => 'Limit to User']),
					$this->settingsOption('limit', 'overseerrLimit'),
					$this->settingsOption('switch', 'overseerrPrefer4K', ['label' => 'Prefer 4K Server']),
					$this->settingsOption('refresh', 'overseerrRefresh'),
				],
				'Default Filter' => [
					$this->settingsOption('switch', 'overseerrDefaultFilterAvailable', ['label' => 'Show Available', 'help' => 'Show All Available Overseerr Requests']),
					$this->settingsOption('switch', 'overseerrDefaultFilterUnavailable', ['label' => 'Show Unavailable', 'help' => 'Show All Unavailable Overseerr Requests']),
					$this->settingsOption('switch', 'overseerrDefaultFilterApproved', ['label' => 'Show Approved', 'help' => 'Show All Approved Overseerr Requests']),
					$this->settingsOption('switch', 'overseerrDefaultFilterUnapproved', ['label' => 'Show Unapproved', 'help' => 'Show All Unapproved Overseerr Requests']),
					$this->settingsOption('switch', 'overseerrDefaultFilterDenied', ['label' => 'Show Denied', 'help' => 'Show All Denied Overseerr Requests']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'overseerr'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function testConnectionOverseerr()
	{
		if (!$this->homepageItemPermissions($this->overseerrHomepagePermissions('test'), true)) {
			return false;
		}
		$headers = array(
			"Accept" => "application/json",
			"X-Api-Key" => $this->config['overseerrToken'],
		);
		$url = $this->qualifyURL($this->config['overseerrURL']);
		try {
			$options = $this->requestOptions($url, null, $this->config['overseerrDisableCertCheck'], $this->config['overseerrUseCustomCertificate']);
			$test = Requests::get($url . "/api/v1/settings/main", $headers, $options);
			$testData = json_decode($test->body, true);
			if ($test->success && isset($testData["apiKey"]) && $testData["apiKey"] == $this->config['overseerrToken']) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			} else {
				$this->setResponse(401, 'API Connection failed');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Overseerr')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function overseerrHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageOverseerrEnabled'
				],
				'auth' => [
					'homepageOverseerrAuth'
				],
				'not_empty' => [
					'overseerrURL',
					'overseerrToken'
				]
			],
			'request' => [
				'enabled' => [
					'homepageOverseerrEnabled'
				],
				'auth' => [
					'homepageOverseerrAuth',
					'homepageOverseerrRequestAuth'
				],
				'not_empty' => [
					'overseerrURL',
					'overseerrToken'
				]
			],
			'test' => [
				'not_empty' => [
					'overseerrURL',
					'overseerrToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderoverseerr()
	{
		if ($this->homepageItemPermissions($this->overseerrHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Requests...</h2></div>
					<script>
						// Overseerr Requests
						homepageRequests("overseerr", "' . $this->config['overseerrRefresh'] . '");
						// End Overseerr Requests
					</script>
				</div>
				';
		}
	}


	public function getOverseerrRequests($limit = 50, $offset = 0)
	{
		if (!$this->homepageItemPermissions($this->overseerrHomepagePermissions('main'), true)) {
			return false;
		}
		$limit = is_numeric($limit) ? (int)$limit : 50;
		$offset = is_numeric($offset) ? (int)$offset : 0;
		$api['count'] = [
			'movie' => 0,
			'tv' => 0,
			'limit' => $limit,
			'offset' => $offset
		];
		$headers = [
			"Accept" => "application/json",
			"X-Api-Key" => $this->config['overseerrToken'],
		];
		$requests = [];
		$url = $this->qualifyURL($this->config['overseerrURL']);
		try {
			$options = $this->requestOptions($url, $this->config['overseerrRefresh'], $this->config['overseerrDisableCertCheck'], $this->config['overseerrUseCustomCertificate']);
			$request = Requests::get($url . "/api/v1/request?take=" . $limit . '&skip=' . $offset, $headers, $options);
			if ($request->success) {
				$requestAll = [];
				$requestsData = json_decode($request->body, true);
				foreach ($requestsData['results'] as $key => $value) {
					$requester = ($value['requestedBy']['username'] !== '' && $value['requestedBy']['username'] !== null) ? $value['requestedBy']['username'] : $value['requestedBy']['plexUsername'];
					$requesterEmail = $value['requestedBy']['email'];
					$proceed = (($this->config['overseerrLimitUser']) && strtolower($this->user['username']) == strtolower($requester)) || (strtolower($requester) == strtolower($this->config['overseerrFallbackUser'])) || (!$this->config['overseerrLimitUser']) || $this->qualifyRequest(1);
					if ($proceed) {
						$requestAll[$value['media']['tmdbId']] = [
							'url' => $url . '/api/v1/' . $value['type'] . '/' . $value['media']['tmdbId'],
							'headers' => $headers,
							'type' => Requests::GET,
						];
						$api['count'][$value['type']]++;
						$requests[$value['media']['tmdbId']] = [
							'id' => $value['media']['tmdbId'],
							'approved' => $value['status'] == 2,
							'available' => $value['media']['status'] == 5,
							'denied' => $value['status'] == 3,
							'deniedReason' => 'n/a',
							'user' => $requester,
							'userAlias' => $value['requestedBy']['displayName'],
							'request_id' => $value['id'],
							'request_date' => $value['createdAt'],
							'type' => $value['type'],
							'icon' => 'mdi mdi-' . ($value['type'] == 'movie') ? 'filmstrip' : 'television',
							'color' => ($value['type'] == 'movie') ? 'palette-Deep-Purple-900 bg white' : 'grayish-blue-bg',
						];
						/* OLD WAY
						$requestItem = Requests::get($url . '/api/v1/' . $value['type'] . '/' . $value['media']['tmdbId'], $headers, $options);
						$requestsItemData = json_decode($requestItem->body, true);
						if ($requestItem->success) {
							$api['count'][$value['type']]++;
							$requests[] = array(
								'id' => $value['media']['tmdbId'],
								'title' => ($value['type'] == 'movie') ? $requestsItemData['title'] : $requestsItemData['name'],
								'overview' => $requestsItemData['overview'],
								'poster' => (isset($requestsItemData['posterPath']) && $requestsItemData['posterPath'] !== '') ? 'https://image.tmdb.org/t/p/w300/' . $requestsItemData['posterPath'] : 'plugins/images/homepage/no-list.png',
								'background' => (isset($requestsItemData['backdropPath']) && $requestsItemData['backdropPath'] !== '') ? 'https://image.tmdb.org/t/p/w1280/' . $requestsItemData['backdropPath'] : '',
								'approved' => $value['status'] == 2,
								'available' => $value['media']['status'] == 5,
								'denied' => $value['status'] == 3,
								'deniedReason' => 'n/a',
								'user' => $requester,
								'userAlias' => $value['requestedBy']['displayName'],
								'request_id' => $value['id'],
								'request_date' => $value['createdAt'],
								'release_date' => ($value['type'] == 'movie') ? $requestsItemData['releaseDate'] : $requestsItemData['firstAirDate'],
								'type' => $value['type'],
								'icon' => 'mdi mdi-' . ($value['type'] == 'movie') ? 'filmstrip' : 'television',
								'color' => ($value['type'] == 'movie') ? 'palette-Deep-Purple-900 bg white' : 'grayish-blue-bg',
							);
						}*/
					}
				}
				$requestItems = Requests::request_multiple($requestAll, $options);
				foreach ($requestItems as $key => $requestedItem) {
					if ($requestedItem->success) {
						$requestsItemData = json_decode($requestedItem->body, true);
						$requests[$key]['title'] = $requestsItemData['title'] ?? $requestsItemData['name'];
						$requests[$key]['release_date'] = $requestsItemData['releaseDate'] ?? $requestsItemData['firstAirDate'];
						$requests[$key]['background'] = (isset($requestsItemData['backdropPath']) && $requestsItemData['backdropPath'] !== '') ? 'https://image.tmdb.org/t/p/w1280/' . $requestsItemData['backdropPath'] : '';
						$requests[$key]['poster'] = (isset($requestsItemData['posterPath']) && $requestsItemData['posterPath'] !== '') ? 'https://image.tmdb.org/t/p/w300/' . $requestsItemData['posterPath'] : 'plugins/images/homepage/no-list.png';
						$requests[$key]['overview'] = $requestsItemData['overview'];
					} else {
						unset($requests[$key]);
					}
				}
				//sort here
				usort($requests, function ($item1, $item2) {
					if ($item1['request_date'] == $item2['request_date']) {
						return 0;
					}
					return $item1['request_date'] > $item2['request_date'] ? -1 : 1;
				});
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Overseerr')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
		$api['content'] = $requests ?? false;
		$this->setResponse(200, null, $api);
		return $api;
	}

	public function getDefaultService($services)
	{
		if (empty($services)) {
			return null;
		} else {
			$default = false;
			foreach ($services as $key => $service) {
				if ($service['isDefault']) {
					if ($service['is4k']) {
						if ($this->config['overseerrPrefer4K']) {
							$default = (int)$key;
						}
					} else {
						if (!$this->config['overseerrPrefer4K']) {
							$default = (int)$key;
						}
					}
				}
			}
			return ($default) ? $services[$default] : $services[0];
		}
	}

	public function addOverseerrRequest($id, $type, $seasons = null)
	{
		$id = ($id) ?? null;
		$type = ($type) ?? null;
		if (!$id) {
			$this->setAPIResponse('error', 'Id was not supplied', 422);
			return false;
		}
		if (!$type) {
			$this->setAPIResponse('error', 'Type was not supplied', 422);
			return false;
		}
		if (!$this->homepageItemPermissions($this->overseerrHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['overseerrURL']);
		try {
			if (!isset($_COOKIE['connect_sid']) && !isset($_COOKIE['connect.sid'])) {
				$this->setAPIResponse('error', 'User does not have Auth Cookie', 500);
				return false;
			}
			$headers = array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-Api-Key' => $this->config['overseerrToken']
			);
			$cookieJar = new Requests_Cookie_Jar(['connect.sid' => $_COOKIE['connect_sid']]);
			$optionsUser = $this->requestOptions($url, null, $this->config['overseerrDisableCertCheck'], $this->config['overseerrUseCustomCertificate'], ['cookies' => $cookieJar]);
			$optionsAPI = $this->requestOptions($url, null, $this->config['overseerrDisableCertCheck'], $this->config['overseerrUseCustomCertificate']);
			// Check if requested already
			$searchResponse = Requests::get($url . '/api/v1/request/', $headers, $optionsAPI);
			if ($searchResponse->success) {
				$details = json_decode($searchResponse->body, true);
				if (count($details['results']) > 0) {
					foreach ($details['results'] as $k => $v) {
						if ($v['media']['tmdbId'] == $id) {
							if ($v['media']['status'] == 5) {
								$this->setAPIResponse('error', 'Request is already available', 409);
								return false;
							} else {
								$this->setAPIResponse('error', 'Request is already requested', 409);
								return false;
							}
						}
					}
				}
			} else {
				$this->setAPIResponse('error', 'Overseerr Connection Error Occurred', 500);
				return false;
			}
			// Get User info
			$response = Requests::get($url . '/api/v1/auth/me', [], $optionsUser);
			if ($response->success) {
				$userInfo = json_decode($response->body, true);
			} else {
				$this->setResponse(500, 'Error getting user information');
				return false;
			}
			switch ($type) {
				case 'season':
				case 'tv':
					if ($this->config['overseerrTvDefault'] == 'user') {
						if ($seasons) {
							if (strpos($seasons, ',') !== false) {
								$seasonsExplode = explode(',', $seasons);
								foreach ($seasonsExplode as $season) {
									$seasonsArray[] = (int)$season;
								}
								$seasons = $seasonsArray;
							} else {
								$seasonsArray[] = (int)$seasons;
								$seasons = $seasonsArray;
							}
						} else {
							$this->setResponse(500, 'Seasons requested was not supplied');
							return false;
						}
					}
					$response = Requests::get($url . '/api/v1/tv/' . $id, $headers, $optionsAPI);
					if ($response->success) {
						$seriesInfo = json_decode($response->body, true);
					} else {
						$this->setResponse(500, 'Error getting series information');
						return false;
					}
					if ($this->config['overseerrTvDefault'] == 'first') {
						$seasons = [1];
					} elseif ($this->config['overseerrTvDefault'] == 'last') {
						$lastSeason = end($seriesInfo['seasons']);
						$lastSeasonNumber = $lastSeason['seasonNumber'];
						$seasons = [$lastSeasonNumber];
					} elseif ($this->config['overseerrTvDefault'] == 'all') {
						$seasons = [];
						foreach ($seriesInfo['seasons'] as $season) {
							if ($season['seasonNumber'] !== 0) {
								$seasons[] = $season['seasonNumber'];
							}
						}
					}
					$response = Requests::get($url . '/api/v1/service/sonarr', $headers, $optionsAPI);
					if ($response->success) {
						$serviceInfo = $this->getDefaultService(json_decode($response->body, true));
						if (!$serviceInfo) {
							$this->setResponse(404, 'No Sonarr service was found in Overseerr');
							return false;
						}
					} else {
						$this->setResponse(500, 'Error getting service information');
						return false;
					}
					$add = [
						'mediaId' => (int)$id,
						'tvdbId' => $seriesInfo['externalIds']['tvdbId'],
						'mediaType' => 'tv',
						'is4k' => (bool)$serviceInfo['is4k'],
						'seasons' => $seasons,
						'serverId' => (int)$serviceInfo['id'],
						'profileId' => (int)$serviceInfo['activeProfileId'],
						'rootFolder' => $serviceInfo['activeDirectory'],
						'languageProfileId' => (int)$serviceInfo['activeLanguageProfileId'],
						//'userId' => (int)$userInfo['id'],
						'tags' => []
					];
					break;
				default:
					$response = Requests::get($url . '/api/v1/service/radarr', $headers, $optionsAPI);
					if ($response->success) {
						$serviceInfo = $this->getDefaultService(json_decode($response->body, true));
						if (!$serviceInfo) {
							$this->setResponse(404, 'No Radarr service was found in Overseerr');
							return false;
						}
					} else {
						$this->setResponse(500, 'Error getting service information');
						return false;
					}
					$add = [
						'mediaId' => (int)$id,
						'mediaType' => 'movie',
						'is4k' => (bool)$serviceInfo['is4k'],
						'serverId' => (int)$serviceInfo['id'],
						'profileId' => (int)$serviceInfo['activeProfileId'],
						//'userId' => (int)$userInfo['id'],
						'tags' => []
					];
					break;
			}
			$response = Requests::post($url . "/api/v1/request", ['Accept' => 'application/json', 'Content-Type' => 'application/json'], json_encode($add), $optionsUser);
			if ($response->success) {
				$this->setAPIResponse('success', 'Overseerr Request submitted', 200);
				return true;
			} else {
				$message = 'Overseerr Error Occurred';
				if ($this->isJSON($response->body)) {
					$messageJSON = json_decode($response->body, true);
					$message = $messageJSON['message'] ?? $message;
				}
				$this->setAPIResponse('error', $message, 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Overseerr')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function actionOverseerrRequest($id, $type, $action)
	{
		$id = ($id) ?? null;
		$type = ($type) ?? null;
		$action = ($action) ?? null;
		if (!$id) {
			$this->setAPIResponse('error', 'Id was not supplied', 422);
			return false;
		}
		if (!$type) {
			$this->setAPIResponse('error', 'Type was not supplied', 422);
			return false;
		}
		if (!$action) {
			$this->setAPIResponse('error', 'Action was not supplied', 422);
			return false;
		}
		if (!$this->homepageItemPermissions($this->overseerrHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['overseerrURL']);
		$headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/json",
			'X-Api-Key' => $this->config['overseerrToken']
		);
		try {
			$options = $this->requestOptions($url, null, $this->config['overseerrDisableCertCheck'], $this->config['overseerrUseCustomCertificate']);
			switch ($action) {
				case 'approve':
					$response = Requests::post($url . "/api/v1/request/" . $id . "/approve", $headers, [], $options);
					$message = 'Overseerr Request has been approved';
					break;
				case 'pending':
					$response = Requests::post($url . '/api/v1/request/' . $id . '/pending', $headers, [], $options);
					$message = 'Overseerr Request has been approved';
					break;
				case 'available':
					$requestInfoResponse = Requests::get($url . '/api/v1/request/' . $id, $headers, $options);
					if ($requestInfoResponse->success) {
						$requestInfo = json_decode($requestInfoResponse->body, true);
						$mediaId = $requestInfo['media']['id'];
					} else {
						$this->setResponse(500, 'Error getting request information');
						return false;
					}
					$response = Requests::post($url . '/api/v1/media/' . $mediaId . '/available', $headers, [], $options);
					$message = 'Overseerr Request has been marked available';
					break;
				case 'unavailable':
					$requestInfoResponse = Requests::get($url . '/api/v1/request/' . $id, $headers, $options);
					if ($requestInfoResponse->success) {
						$requestInfo = json_decode($requestInfoResponse->body, true);
						$mediaId = $requestInfo['media']['id'];
					} else {
						$this->setResponse(500, 'Error getting request information');
						return false;
					}
					$response = Requests::post($url . "/api/v1/media/" . $mediaId . "/pending", $headers, [], $options);
					$message = 'Overseerr Request has been marked unavailable';
					break;
				case 'deny':
					$response = Requests::post($url . "/api/v1/request/" . $id . "/decline", $headers, [], $options);
					$message = 'Overseerr Request has been denied';
					break;
				case 'delete':
					$response = Requests::delete($url . "/api/v1/request/" . $id, $headers, $options);
					$message = 'Overseerr Request has been deleted';
					break;
				default:
					return false;
			}
			if ($response->success) {
				$this->setAPIResponse('success', $message, 200);
				return true;
			} else {
				$message = 'Overseerr Error Occurred';
				if ($this->isJSON($response->body)) {
					$messageJSON = json_decode($response->body, true);
					$message = $messageJSON['message'] ?? $message;
				}
				$this->setAPIResponse('error', $message, 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Overseerr')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function getOverseerrMetadata($id, $type)
	{
		if (!$id) {
			$this->setAPIResponse('error', 'Id was not supplied', 422);
			return false;
		}
		if (!$type) {
			$this->setAPIResponse('error', 'Type was not supplied', 422);
			return false;
		}
		if (!$this->homepageItemPermissions($this->overseerrHomepagePermissions('request'), true)) {
			return false;
		}
		try {
			$url = $this->qualifyURL($this->config['overseerrURL']);
			$headers = array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-Api-Key' => $this->config['overseerrToken']
			);
			$options = $this->requestOptions($url, null, $this->config['overseerrDisableCertCheck'], $this->config['overseerrUseCustomCertificate']);
			$response = Requests::get($url . '/api/v1/' . $type . '/' . $id, $headers, $options);
			if ($response->success) {
				$metadata = json_decode($response->body, true);
				$this->setResponse(200, null, $metadata);
				return $metadata;
			} else {
				$this->setResponse(500, 'Error getting series information');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Overseerr')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function overseerrTVDefault($type)
	{
		return $type == $this->config['overseerrTvDefault'];
	}
}