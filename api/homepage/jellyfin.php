<?php

trait JellyfinHomepageItem
{
	
	public function jellyfinSettingsArray()
	{
		return array(
			'name' => 'Jellyfin',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/jellyfin.png',
			'category' => 'Media Server',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageJellyfinEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageJellyfinEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageJellyfinAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageJellyfinAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'jellyfinURL',
						'label' => 'URL',
						'value' => $this->config['jellyfinURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'jellyfinToken',
						'label' => 'Token',
						'value' => $this->config['jellyfinToken']
					)
				),
				'Active Streams' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageJellyfinStreams',
						'label' => 'Enable',
						'value' => $this->config['homepageJellyfinStreams']
					),
					array(
						'type' => 'select',
						'name' => 'homepageJellyStreamsAuth',
						'label' => 'Minimum Authorization',
						'value' => $this->config['homepageJellyStreamsAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'switch',
						'name' => 'homepageShowStreamNames',
						'label' => 'User Information',
						'value' => $this->config['homepageShowStreamNames']
					),
					array(
						'type' => 'select',
						'name' => 'homepageShowStreamNamesAuth',
						'label' => 'Minimum Authorization',
						'value' => $this->config['homepageShowStreamNamesAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'select',
						'name' => 'homepageStreamRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageStreamRefresh'],
						'options' => $this->timeOptions()
					),
				),
				'Recent Items' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageJellyfinRecent',
						'label' => 'Enable',
						'value' => $this->config['homepageJellyfinRecent']
					),
					array(
						'type' => 'select',
						'name' => 'homepageJellyfinRecentAuth',
						'label' => 'Minimum Authorization',
						'value' => $this->config['homepageJellyfinRecentAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'number',
						'name' => 'homepageRecentLimit',
						'label' => 'Item Limit',
						'value' => $this->config['homepageRecentLimit'],
					),
					array(
						'type' => 'select',
						'name' => 'homepageRecentRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageRecentRefresh'],
						'options' => $this->timeOptions()
					),
				),
				'Misc Options' => array(
					array(
						'type' => 'input',
						'name' => 'jellyfinTabName',
						'label' => 'Jellyfin Tab Name',
						'value' => $this->config['jellyfinTabName'],
						'placeholder' => 'Only use if you have Jellyfin in a reverse proxy'
					),
					array(
						'type' => 'input',
						'name' => 'jellyfinTabURL',
						'label' => 'Jellyfin Tab WAN URL',
						'value' => $this->config['jellyfinTabURL'],
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'select',
						'name' => 'cacheImageSize',
						'label' => 'Image Cache Size',
						'value' => $this->config['cacheImageSize'],
						'options' => array(
							array(
								'name' => 'Low',
								'value' => '.5'
							),
							array(
								'name' => '1x',
								'value' => '1'
							),
							array(
								'name' => '2x',
								'value' => '2'
							),
							array(
								'name' => '3x',
								'value' => '3'
							)
						)
					)
				),
				'Test Connection' => array(
					array(
						'type' => 'blank',
						'label' => 'Please Save before Testing'
					),
					array(
						'type' => 'button',
						'label' => '',
						'icon' => 'fa fa-flask',
						'class' => 'pull-right',
						'text' => 'Test Connection',
						'attr' => 'onclick="testAPIConnection(\'jellyfin\')"'
					),
				)
			)
		);
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
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$url = $url . "/Users?api_key=" . $this->config['jellyfinToken'];
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$json = json_decode($response->body);
				if (is_array($json) || is_object($json)) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				} else {
					$this->setAPIResponse('error', 'URL or token incorrect', 409);
					return false;
				}
			} else {
				$this->setAPIResponse('error', 'Jellyfin Connection Error', 500);
				return true;
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$url = $url . '/Sessions?api_key=' . $this->config['jellyfinToken'] . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = $this->resolveJellyfinItem($child);
					}
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getJellyfinHomepageRecent()
	{
		if (!$this->homepageItemPermissions($this->jellyfinHomepagePermissions('recent'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$username = false;
		$showPlayed = false;
		$userId = 0;
		try {
			if (isset($this->user['username'])) {
				$username = strtolower($this->user['username']);
			}
			// Get A User
			$userIds = $url . "/Users?api_key=" . $this->config['jellyfinToken'];
			$response = Requests::get($userIds, array(), $options);
			if ($response->success) {
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $value) { // Scan for admin user
					if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
						$userId = $value['Id'];
					}
					if ($username && strtolower($value['Name']) == $username) {
						$userId = $value['Id'];
						$showPlayed = false;
						break;
					}
				}
				$url = $url . '/Users/' . $userId . '/Items/Latest?EnableImages=true&Limit=' . $this->config['homepageRecentLimit'] . '&api_key=' . $this->config['jellyfinToken'] . ($showPlayed ? '' : '&IsPlayed=false') . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = $this->resolveJellyfinItem($child);
					}
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getJellyfinHomepageMetadata($array)
	{
		if (!$this->homepageItemPermissions($this->jellyfinHomepagePermissions('metadata'), true)) {
			return false;
		}
		$key = $array['key'] ?? null;
		if (!$key) {
			$this->setAPIResponse('error', 'Jellyfin Metadata key is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$username = false;
		$showPlayed = false;
		$userId = 0;
		try {
			if (isset($this->user['username'])) {
				$username = strtolower($this->user['username']);
			}
			// Get A User
			$userIds = $url . "/Users?api_key=" . $this->config['jellyfinToken'];
			$response = Requests::get($userIds, array(), $options);
			if ($response->success) {
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $value) { // Scan for admin user
					if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
						$userId = $value['Id'];
					}
					if ($username && strtolower($value['Name']) == $username) {
						$userId = $value['Id'];
						$showPlayed = false;
						break;
					}
				}
				$url = $url . '/Users/' . $userId . '/Items/' . $key . '?EnableImages=true&Limit=' . $this->config['homepageRecentLimit'] . '&api_key=' . $this->config['jellyfinToken'] . ($showPlayed ? '' : '&IsPlayed=false') . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$jellyfin = json_decode($response->body, true);
				if (isset($jellyfin['NowPlayingItem']) || isset($jellyfin['Name'])) {
					$items[] = $this->resolveJellyfinItem($jellyfin);
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function resolveJellyfinItem($itemDetails)
	{
		$item = isset($itemDetails['NowPlayingItem']['Id']) ? $itemDetails['NowPlayingItem'] : $itemDetails;
		// Static Height & Width
		$height = $this->getCacheImageSize('h');
		$width = $this->getCacheImageSize('w');
		$nowPlayingHeight = $this->getCacheImageSize('nph');
		$nowPlayingWidth = $this->getCacheImageSize('npw');
		$actorHeight = 450;
		$actorWidth = 300;
		// Cache Directories
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheDirectoryWeb = 'plugins/images/cache/';
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
				$jellyfinItem['title'] = $item['SeriesName'];
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
		$jellyfinItem['id'] = $item['ServerId'];
		$jellyfinItem['session'] = @$itemDetails['DeviceId'];
		$jellyfinItem['bandwidth'] = isset($itemDetails['TranscodingInfo']['Bitrate']) ? $itemDetails['TranscodingInfo']['Bitrate'] / 1000 : '';
		$jellyfinItem['bandwidthType'] = 'wan';
		$jellyfinItem['sessionType'] = (@$itemDetails['PlayState']['PlayMethod'] == 'Transcode') ? 'Transcoding' : 'Direct Playing';
		$jellyfinItem['state'] = ((@(string)$itemDetails['PlayState']['IsPaused'] == '1') ? "pause" : "play");
		$jellyfinItem['user'] = ($this->config['homepageShowStreamNames'] && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'])) ? @(string)$itemDetails['UserName'] : "";
		$jellyfinItem['userThumb'] = '';
		$jellyfinItem['userAddress'] = (isset($itemDetails['RemoteEndPoint']) ? $itemDetails['RemoteEndPoint'] : "x.x.x.x");
		$jellyfinURL = $this->config['jellyfinURL'] . '/web/index.html#!/itemdetails.html?id=';
		$jellyfinItem['address'] = $this->config['jellyfinTabURL'] ? rtrim($this->config['jellyfinTabURL'], '/') . "/web/#!/item/item.html?id=" . $jellyfinItem['uid'] : $jellyfinURL . $jellyfinItem['uid'] . "&serverId=" . $jellyfinItem['id'];
		$jellyfinItem['nowPlayingOriginalImage'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['nowPlayingImageType'] . '&img=' . $jellyfinItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $jellyfinItem['nowPlayingKey'] . '$' . $this->randString();
		$jellyfinItem['originalImage'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['imageType'] . '&img=' . $jellyfinItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $jellyfinItem['key'] . '$' . $this->randString();
		$jellyfinItem['openTab'] = $this->config['jellyfinTabURL'] && $this->config['jellyfinTabName'] ? true : false;
		$jellyfinItem['tabName'] = $this->config['jellyfinTabName'] ? $this->config['jellyfinTabName'] : '';
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
				if (@$value['PrimaryImageTag'] && @$value['Role']) {
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
			'year' => (string)isset($item['ProductionYear']) ? $item['ProductionYear'] : '',
			//'studio' => (string)$item['studio'],
			'tagline' => @(string)$item['Taglines'][0],
			'genres' => (isset($item['Genres'])) ? $genres : '',
			'actors' => (isset($item['People'])) ? $actors : ''
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
			$jellyfinItem['nowPlayingOriginalImage'] = $jellyfinItem['nowPlayingImageURL'] = "plugins/images/cache/no-np.png";
			$jellyfinItem['nowPlayingKey'] = "no-np";
		}
		if (!$jellyfinItem['thumb']) {
			$jellyfinItem['originalImage'] = $jellyfinItem['imageURL'] = "plugins/images/cache/no-list.png";
			$jellyfinItem['key'] = "no-list";
		}
		if (isset($useImage)) {
			$jellyfinItem['useImage'] = $useImage;
		}
		return $jellyfinItem;
	}
	
}