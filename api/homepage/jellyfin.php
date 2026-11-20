<?php

trait JellyfinHomepageItem
{

	public function jellyfinSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Jellyfin',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/jellyfin.png',
			'category' => 'Media Server',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageJellyfinEnabled'),
					$this->settingsOption('auth', 'homepageJellyfinAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'jellyfinURL'),
					$this->settingsOption('token', 'jellyfinToken'),
					$this->settingsOption('disable-cert-check', 'jellyfinDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'jellyfinUseCustomCertificate'),
				],
				'Active Streams' => [
					$this->settingsOption('enable', 'homepageJellyfinStreams'),
					$this->settingsOption('auth', 'homepageJellyStreamsAuth'),
					$this->settingsOption('switch', 'homepageShowStreamNames', ['label' => 'User Information']),
					$this->settingsOption('auth', 'homepageShowStreamNamesAuth'),
					$this->settingsOption('refresh', 'homepageStreamRefresh'),
				],
				'Recent Items' => [
					$this->settingsOption('enable', 'homepageJellyfinRecent'),
					$this->settingsOption('auth', 'homepageJellyfinRecentAuth'),
					$this->settingsOption('limit', 'homepageRecentLimit'),
					$this->settingsOption('refresh', 'homepageRecentRefresh'),
				],
				'Misc Options' => [
					$this->settingsOption('input', 'homepageJellyfinLink', ['label' => 'Jellyfin Homepage Link URL', 'help' => 'Available variables: {id} {serverId}']),
					$this->settingsOption('input', 'jellyfinTabName', ['label' => 'Jellyfin Tab Name', 'placeholder' => 'Only use if you have Jellyfin in a reverse proxy']),
					$this->settingsOption('image-cache-quality', 'cacheImageSize'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'jellyfin'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	/** Build the current Jellyfin authentication header (works on 10.x and 12). */
	protected function jellyfinAuthHeaders()
	{
		$token = (string)($this->config['jellyfinToken'] ?? '');
		return ['Authorization' => 'MediaBrowser Token="' . addcslashes($token, "\\\"") . '"'];
	}

	/** Build API URLs without exposing credentials in query strings. */
	protected function jellyfinApiUrl($path, array $params = [])
	{
		$url = rtrim($this->qualifyURL($this->config['jellyfinURL']), '/') . '/' . ltrim($path, '/');
		if ($params) {
			$query = [];
			foreach ($params as $name => $value) {
				$values = is_array($value) ? $value : [$value];
				foreach ($values as $entry) {
					if (is_bool($entry)) {
						$entry = $entry ? 'true' : 'false';
					}
					$query[] = rawurlencode((string)$name) . '=' . rawurlencode((string)$entry);
				}
			}
			$url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $query);
		}
		return $url;
	}

	/** Find the Organizr user in the authenticated Jellyfin user list. */
	protected function jellyfinResolveUserId($baseUrl, $headers, $options)
	{
		$response = Requests::get($this->jellyfinApiUrl('Users'), $headers, $options);
		if (!$response->success) {
			return false;
		}
		$users = json_decode($response->body, true);
		if (!is_array($users)) {
			return false;
		}
		$username = isset($this->user['username']) ? strtolower((string)$this->user['username']) : '';
		$adminId = null;
		$firstId = null;
		foreach ($users as $user) {
			if (!is_array($user) || empty($user['Id'])) {
				continue;
			}
			if ($firstId === null) {
				$firstId = $user['Id'];
			}
			if ($username !== '' && isset($user['Name']) && strtolower((string)$user['Name']) === $username) {
				return $user['Id'];
			}
			if (!empty($user['Policy']['IsAdministrator'])) {
				$adminId = $user['Id'];
			}
		}
		return $adminId !== null ? $adminId : $firstId;
	}

	public function testConnectionJellyfin()
	{
		if (empty($this->config['jellyfinURL'])) {
			$this->setAPIResponse('error', 'Jellyfin URL is not defined', 422);
			return false;
		}
		if (empty($this->config['jellyfinToken'])) {
			$this->setAPIResponse('error', 'Jellyfin Token is not defined', 422);
			return false;
		}
		$url = $this->jellyfinApiUrl('System/Info');
		$options = $this->requestOptions($url, null, $this->config['jellyfinDisableCertCheck'] ?? false, $this->config['jellyfinUseCustomCertificate'] ?? false);
		try {
			$response = Requests::get($url, $this->jellyfinAuthHeaders(), $options);
			$json = $response->success ? json_decode($response->body, true) : null;
			if ($response->success && is_array($json)) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			}
			$this->setAPIResponse('error', 'URL or token incorrect', $response->status_code ?? 401);
			return false;
		} catch (Requests_Exception $e) {
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function jellyfinHomepagePermissions($key = null)
	{
		$permissions = [
			'streams' => [
				'enabled' => [
					'homepageJellyfinEnabled',
					'homepageJellyfinStreams'
				],
				'auth' => [
					'homepageJellyfinAuth',
					'homepageJellyStreamsAuth'
				],
				'not_empty' => [
					'jellyfinURL',
					'jellyfinToken'
				]
			],
			'recent' => [
				'enabled' => [
					'homepageJellyfinEnabled',
					'homepageJellyfinRecent'
				],
				'auth' => [
					'homepageJellyfinAuth',
					'homepageJellyfinRecentAuth'
				],
				'not_empty' => [
					'jellyfinURL',
					'jellyfinToken'
				]
			],
			'metadata' => [
				'enabled' => [
					'homepageJellyfinEnabled'
				],
				'auth' => [
					'homepageJellyfinAuth'
				],
				'not_empty' => [
					'jellyfinURL',
					'jellyfinToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderjellyfinnowplaying()
	{
		if ($this->homepageItemPermissions($this->jellyfinHomepagePermissions('streams'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Now Playing...</h2></div>
					<script>
						// Jellyfin Stream
						homepageStream("jellyfin", "' . $this->config['homepageStreamRefresh'] . '");
						// End Jellyfin Stream
					</script>
				</div>
				';
		}
	}

	public function homepageOrderjellyfinrecent()
	{
		if ($this->homepageItemPermissions($this->jellyfinHomepagePermissions('recent'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Recent...</h2></div>
					<script>
						// Jellyfin Recent
						homepageRecent("jellyfin", "' . $this->config['homepageRecentRefresh'] . '");
						// End Jellyfin Recent
					</script>
				</div>
				';
		}
	}

	public function getJellyfinHomepageStreams()
	{
		if (!$this->homepageItemPermissions($this->jellyfinHomepagePermissions('streams'), true)) {
			return false;
		}
		$url = $this->jellyfinApiUrl('Sessions');
		$options = $this->requestOptions($url, $this->config['homepageStreamRefresh'] ?? null, $this->config['jellyfinDisableCertCheck'] ?? false, $this->config['jellyfinUseCustomCertificate'] ?? false);
		try {
			$response = Requests::get($url, $this->jellyfinAuthHeaders(), $options);
			if (!$response->success) {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$sessions = json_decode($response->body, true);
			$items = [];
			if (is_array($sessions)) {
				foreach ($sessions as $child) {
					if (is_array($child) && (isset($child['NowPlayingItem']) || isset($child['Name']))) {
						$items[] = $this->resolveJellyfinItem($child);
					}
				}
			}
			$api['content'] = array_values(array_filter($items));
			$this->setAPIResponse('success', null, 200, $api);
			return $api;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Jellyfin')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function getJellyfinHomepageRecent()
	{
		if (!$this->homepageItemPermissions($this->jellyfinHomepagePermissions('recent'), true)) {
			return false;
		}
		$baseUrl = rtrim($this->qualifyURL($this->config['jellyfinURL']), '/');
		$url = $this->jellyfinApiUrl('Items/Latest');
		$options = $this->requestOptions($url, $this->config['homepageRecentRefresh'] ?? null, $this->config['jellyfinDisableCertCheck'] ?? false, $this->config['jellyfinUseCustomCertificate'] ?? false);
		$headers = $this->jellyfinAuthHeaders();
		try {
			$userId = $this->jellyfinResolveUserId($baseUrl, $headers, $options);
			if (!$userId) {
				$this->setAPIResponse('error', 'Unable to resolve Jellyfin user', 500);
				return false;
			}
			$params = [
				'userId' => $userId,
				'enableImages' => true,
				'limit' => max(1, (int)($this->config['homepageRecentLimit'] ?? 10)),
				'isPlayed' => false,
				'fields' => ['Overview', 'People', 'Genres', 'Studios', 'Taglines']
			];
			$url = $this->jellyfinApiUrl('Items/Latest', $params);
			$response = Requests::get($url, $headers, $options);
			if (!$response->success) {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$latest = json_decode($response->body, true);
			$items = [];
			if (is_array($latest)) {
				foreach ($latest as $child) {
					if (is_array($child) && (isset($child['Name']) || isset($child['Id']))) {
						$items[] = $this->resolveJellyfinItem($child);
					}
				}
			}
			$api['content'] = array_values(array_filter($items));
			$this->setAPIResponse('success', null, 200, $api);
			return $api;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Jellyfin')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function getJellyfinHomepageMetadata($array)
	{
		if (!$this->homepageItemPermissions($this->jellyfinHomepagePermissions('metadata'), true)) {
			return false;
		}
		$key = is_array($array) ? ($array['key'] ?? null) : null;
		if (!$key) {
			$this->setAPIResponse('error', 'Jellyfin Metadata key is not defined', 422);
			return false;
		}
		$baseUrl = rtrim($this->qualifyURL($this->config['jellyfinURL']), '/');
		$url = $this->jellyfinApiUrl('Items/' . rawurlencode((string)$key));
		$options = $this->requestOptions($url, 60, $this->config['jellyfinDisableCertCheck'] ?? false, $this->config['jellyfinUseCustomCertificate'] ?? false);
		$headers = $this->jellyfinAuthHeaders();
		try {
			$userId = $this->jellyfinResolveUserId($baseUrl, $headers, $options);
			if (!$userId) {
				$this->setAPIResponse('error', 'Unable to resolve Jellyfin user', 500);
				return false;
			}
			$params = ['userId' => $userId, 'fields' => ['Overview', 'People', 'Genres', 'Studios', 'Taglines'], 'enableImages' => true];
			$url = $this->jellyfinApiUrl('Items/' . rawurlencode((string)$key), $params);
			$response = Requests::get($url, $headers, $options);
			if (!$response->success) {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$details = json_decode($response->body, true);
			$items = [];
			if (is_array($details) && (isset($details['Name']) || isset($details['Id']))) {
				$items[] = $this->resolveJellyfinItem($details);
			}
			$api['content'] = array_values(array_filter($items));
			$this->setAPIResponse('success', null, 200, $api);
			return $api;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Jellyfin')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function resolveJellyfinItem($itemDetails)
	{
		if (!is_array($itemDetails)) {
			return false;
		}
		$item = isset($itemDetails['NowPlayingItem']) && is_array($itemDetails['NowPlayingItem']) ? $itemDetails['NowPlayingItem'] : $itemDetails;
		if (empty($item['Id']) || empty($item['Type'])) {
			return false;
		}
		$item = array_merge(['Name' => '', 'SeriesName' => '', 'ProductionYear' => '', 'ServerId' => '', 'RunTimeTicks' => 0], $item);
		foreach (['ImageTags', 'BackdropImageTags', 'ParentBackdropImageTags', 'Genres', 'People'] as $listField) {
			if (!isset($item[$listField]) || !is_array($item[$listField])) { $item[$listField] = []; }
		}
		$itemDetails += ['PlayState' => [], 'TranscodingInfo' => [], 'Client' => '', 'DeviceName' => '', 'DeviceId' => '', 'UserName' => '', 'RemoteEndPoint' => '', 'NowPlayingItem' => $item];
		if (!is_array($itemDetails['PlayState'])) { $itemDetails['PlayState'] = []; }
		if (!is_array($itemDetails['TranscodingInfo'])) { $itemDetails['TranscodingInfo'] = []; }
		if (!is_array($itemDetails['NowPlayingItem'])) { $itemDetails['NowPlayingItem'] = $item; }
		$itemDetails['NowPlayingItem'] += ['MediaStreams' => [], 'Container' => '', 'RunTimeTicks' => 0];
		if (!is_array($itemDetails['NowPlayingItem']['MediaStreams'])) { $itemDetails['NowPlayingItem']['MediaStreams'] = []; }
		$genres = [];
		$actors = [];
		$actorImage = '';
		// Static Height & Width
		$height = $this->getCacheImageSize('h');
		$width = $this->getCacheImageSize('w');
		$nowPlayingHeight = $this->getCacheImageSize('nph');
		$nowPlayingWidth = $this->getCacheImageSize('npw');
		$actorHeight = 450;
		$actorWidth = 300;
		// Cache Directories
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheDirectoryWeb = 'data/cache/';
		// Types
		switch (@$item['Type']) {
			case 'Series':
				$jellyfinItem['type'] = 'tv';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = $item['Id'];
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['BackdropImageTags'][0]) ? 'Backdrop' : '');
				break;
			case 'Episode':
				$jellyfinItem['type'] = 'tv';
				$jellyfinItem['title'] = $item['SeriesName'] ?: $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']);
				$jellyfinItem['key'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']) . "-list";
				$jellyfinItem['nowPlayingThumb'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] : false);
				$jellyfinItem['nowPlayingKey'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] . '-np' : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] . '-np' : false);
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['ParentBackdropImageTags'][0]) ? 'Backdrop' : '');
				$jellyfinItem['nowPlayingTitle'] = @$item['SeriesName'] . ' - ' . @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = 'S' . @$item['ParentIndexNumber'] . ' · E' . @$item['IndexNumber'];
				break;
			case 'MusicAlbum':
			case 'Audio':
				$jellyfinItem['type'] = 'music';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = (isset($item['AlbumId']) ? $item['AlbumId'] : @$item['ParentBackdropItemId']);
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = isset($item['AlbumId']) ? $item['AlbumId'] : $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = (isset($item['ParentBackdropItemId']) ? "Primary" : "Backdrop");
				$jellyfinItem['nowPlayingTitle'] = @$item['AlbumArtist'] . ' - ' . @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = @$item['Album'];
				break;
			case 'Movie':
				$jellyfinItem['type'] = 'movie';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = $item['Id'];
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
				$jellyfinItem['nowPlayingTitle'] = @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = @$item['ProductionYear'];
				break;
			case 'Video':
				$jellyfinItem['type'] = 'video';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = $item['Id'];
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
				$jellyfinItem['nowPlayingTitle'] = @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = @$item['ProductionYear'];
				break;
			default:
				return false;
		}
		$jellyfinItem['uid'] = $item['Id'];
		$jellyfinItem['imageType'] = (isset($item['ImageTags']['Primary']) ? "Primary" : false);
		$jellyfinItem['elapsed'] = isset($itemDetails['PlayState']['PositionTicks']) && $itemDetails['PlayState']['PositionTicks'] !== '0' ? (int)$itemDetails['PlayState']['PositionTicks'] : null;
		$jellyfinItem['duration'] = isset($itemDetails['NowPlayingItem']['RunTimeTicks']) ? (int)$itemDetails['NowPlayingItem']['RunTimeTicks'] : (int)(isset($item['RunTimeTicks']) ? $item['RunTimeTicks'] : '');
		$jellyfinItem['watched'] = ($jellyfinItem['elapsed'] && $jellyfinItem['duration'] ? floor(($jellyfinItem['elapsed'] / $jellyfinItem['duration']) * 100) : 0);
		$jellyfinItem['transcoded'] = isset($itemDetails['TranscodingInfo']['CompletionPercentage']) ? floor((int)$itemDetails['TranscodingInfo']['CompletionPercentage']) : 100;
		$jellyfinItem['stream'] = @$itemDetails['PlayState']['PlayMethod'];
		$jellyfinItem['id'] = $item['ServerId'] ?? '';
		$jellyfinItem['session'] = @$itemDetails['DeviceId'];
		$jellyfinItem['bandwidth'] = isset($itemDetails['TranscodingInfo']['Bitrate']) ? $itemDetails['TranscodingInfo']['Bitrate'] / 1000 : '';
		$jellyfinItem['bandwidthType'] = 'wan';
		$jellyfinItem['sessionType'] = (@$itemDetails['PlayState']['PlayMethod'] == 'Transcode') ? 'Transcoding' : 'Direct Playing';
		$jellyfinItem['state'] = ((@(string)$itemDetails['PlayState']['IsPaused'] == '1') ? "pause" : "play");
		$jellyfinItem['user'] = (!empty($this->config['homepageShowStreamNames']) && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'] ?? null)) ? (string)$itemDetails['UserName'] : "";
		$jellyfinItem['userThumb'] = '';
		$jellyfinItem['userAddress'] = (isset($itemDetails['RemoteEndPoint']) ? $itemDetails['RemoteEndPoint'] : "x.x.x.x");
		$jellfinVariablesForLink = [
			'{id}' => $jellyfinItem['uid'],
			'{serverId}' => $jellyfinItem['id']
		];
		$jellyfinItem['address'] = $this->userDefinedIdReplacementLink($this->config['homepageJellyfinLink'] ?? '', $jellfinVariablesForLink);
		$jellyfinItem['nowPlayingOriginalImage'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['nowPlayingImageType'] . '&img=' . $jellyfinItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $jellyfinItem['nowPlayingKey'] . '$' . $this->randString();
		$jellyfinItem['originalImage'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['imageType'] . '&img=' . $jellyfinItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $jellyfinItem['key'] . '$' . $this->randString();
		$jellyfinItem['openTab'] = !empty($this->config['jellyfinTabName']);
		$jellyfinItem['tabName'] = $this->config['jellyfinTabName'] ?? '';
		// Stream info
		$jellyfinItem['userStream'] = array(
			'platform' => @(string)$itemDetails['Client'],
			'product' => @(string)$itemDetails['Client'],
			'device' => @(string)$itemDetails['DeviceName'],
			'stream' => @$itemDetails['PlayState']['PlayMethod'],
			'videoResolution' => isset($itemDetails['NowPlayingItem']['MediaStreams'][0]['Width']) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Width'] : '',
			'throttled' => false,
			'sourceVideoCodec' => isset($itemDetails['NowPlayingItem']['MediaStreams'][0]) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Codec'] : '',
			'videoCodec' => @$itemDetails['TranscodingInfo']['VideoCodec'],
			'audioCodec' => @$itemDetails['TranscodingInfo']['AudioCodec'],
			'sourceAudioCodec' => isset($itemDetails['NowPlayingItem']['MediaStreams'][1]) ? $itemDetails['NowPlayingItem']['MediaStreams'][1]['Codec'] : (isset($itemDetails['NowPlayingItem']['MediaStreams'][0]) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Codec'] : ''),
			'videoDecision' => $this->streamType(@$itemDetails['PlayState']['PlayMethod']),
			'audioDecision' => $this->streamType(@$itemDetails['PlayState']['PlayMethod']),
			'container' => isset($itemDetails['NowPlayingItem']['Container']) ? $itemDetails['NowPlayingItem']['Container'] : '',
			'audioChannels' => @$itemDetails['TranscodingInfo']['AudioChannels']
		);
		// Genre catch all
		if (isset($item['Genres'])) {
			$genres = array();
			foreach ($item['Genres'] as $genre) {
				$genres[] = $genre;
			}
		}
		// Actor catch all
		if (isset($item['People'])) {
			$actors = array();
			foreach ($item['People'] as $key => $value) {
				if (is_array($value) && !empty($value['PrimaryImageTag']) && !empty($value['Role'])) {
					$actorImage = '';
					if (file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg')) {
						$actorImage = $cacheDirectoryWeb . (string)$value['Id'] . '-cast.jpg';
					}
					if (file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg') && (time() - 604800) > filemtime($cacheDirectory . (string)$value['Id'] . '-cast.jpg') || !file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg')) {
						$actorImage = 'api/v2/homepage/image?source=jellyfin&type=Primary&img=' . (string)$value['Id'] . '&height=' . $actorHeight . '&width=' . $actorWidth . '&key=' . (string)$value['Id'] . '-cast';
					}
					$actors[] = array(
						'name' => (string)$value['Name'],
						'role' => (string)$value['Role'],
						'thumb' => $actorImage
					);
				}
			}
		}
		// Metadata information
		$jellyfinItem['metadata'] = array(
			'guid' => $item['Id'],
			'summary' => @(string)$item['Overview'],
			'rating' => @(string)$item['CommunityRating'],
			'duration' => @(string)$item['RunTimeTicks'],
			'originallyAvailableAt' => @(string)$item['PremiereDate'],
			'year' => isset($item['ProductionYear']) ? (string)$item['ProductionYear'] : '',
			//'studio' => (string)$item['studio'],
			'tagline' => @(string)$item['Taglines'][0],
			'genres' => !empty($item['Genres']) ? $genres : '',
			'actors' => !empty($item['People']) ? $actors : ''
		);
		if (file_exists($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg')) {
			$jellyfinItem['nowPlayingImageURL'] = $cacheDirectoryWeb . $jellyfinItem['nowPlayingKey'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $jellyfinItem['key'] . '.jpg')) {
			$jellyfinItem['imageURL'] = $cacheDirectoryWeb . $jellyfinItem['key'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg') || !file_exists($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg')) {
			$jellyfinItem['nowPlayingImageURL'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['nowPlayingImageType'] . '&img=' . $jellyfinItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $jellyfinItem['nowPlayingKey'] . '';
		}
		if (file_exists($cacheDirectory . $jellyfinItem['key'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $jellyfinItem['key'] . '.jpg') || !file_exists($cacheDirectory . $jellyfinItem['key'] . '.jpg')) {
			$jellyfinItem['imageURL'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['imageType'] . '&img=' . $jellyfinItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $jellyfinItem['key'] . '';
		}
		if (!$jellyfinItem['nowPlayingThumb']) {
			$jellyfinItem['nowPlayingOriginalImage'] = $jellyfinItem['nowPlayingImageURL'] = "plugins/images/homepage/no-np.png";
			$jellyfinItem['nowPlayingKey'] = "no-np";
		}
		if (!$jellyfinItem['thumb']) {
			$jellyfinItem['originalImage'] = $jellyfinItem['imageURL'] = "plugins/images/homepage/no-list.png";
			$jellyfinItem['key'] = "no-list";
		}
		if (isset($useImage)) {
			$jellyfinItem['useImage'] = $useImage;
		}
		return $jellyfinItem;
	}

}
