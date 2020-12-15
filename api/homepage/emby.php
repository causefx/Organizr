<?php

trait EmbyHomepageItem
{
	public function embySettingsArray()
	{
		return array(
			'name' => 'Emby',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/emby.png',
			'category' => 'Media Server',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageEmbyEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageEmbyEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageEmbyAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageEmbyAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'embyURL',
						'label' => 'URL',
						'value' => $this->config['embyURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'embyToken',
						'label' => 'Token',
						'value' => $this->config['embyToken']
					)
				),
				'Active Streams' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageEmbyStreams',
						'label' => 'Enable',
						'value' => $this->config['homepageEmbyStreams']
					),
					array(
						'type' => 'select',
						'name' => 'homepageEmbyStreamsAuth',
						'label' => 'Minimum Authorization',
						'value' => $this->config['homepageEmbyStreamsAuth'],
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
						'name' => 'homepageEmbyRecent',
						'label' => 'Enable',
						'value' => $this->config['homepageEmbyRecent']
					),
					array(
						'type' => 'select',
						'name' => 'homepageEmbyRecentAuth',
						'label' => 'Minimum Authorization',
						'value' => $this->config['homepageEmbyRecentAuth'],
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
						'name' => 'homepageEmbyLink',
						'label' => 'Emby Homepage Link URL',
						'value' => $this->config['homepageEmbyLink'],
						'help' => 'Available variables: {id} {serverId}'
					),
					array(
						'type' => 'input',
						'name' => 'embyTabName',
						'label' => 'Emby Tab Name',
						'value' => $this->config['embyTabName'],
						'placeholder' => 'Only use if you have Emby in a reverse proxy'
					),
					array(
						'type' => 'input',
						'name' => 'embyTabURL',
						'label' => 'Emby Tab WAN URL',
						'value' => $this->config['embyTabURL'],
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
						'attr' => 'onclick="testAPIConnection(\'emby\')"'
					),
				)
			)
		);
	}
	
	public function testConnectionEmby()
	{
		if (empty($this->config['embyURL'])) {
			$this->setAPIResponse('error', 'Emby URL is not defined', 422);
			return false;
		}
		if (empty($this->config['embyToken'])) {
			$this->setAPIResponse('error', 'Emby Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['embyURL']);
		$url = $url . "/Users?api_key=" . $this->config['embyToken'];
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			} else {
				$this->setAPIResponse('error', 'Emby Connection Error', 500);
				return true;
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function embyHomepagePermissions($key = null)
	{
		$permissions = [
			'streams' => [
				'enabled' => [
					'homepageEmbyEnabled',
					'homepageEmbyStreams'
				],
				'auth' => [
					'homepageEmbyAuth',
					'homepageEmbyStreamsAuth'
				],
				'not_empty' => [
					'embyURL',
					'embyToken'
				]
			],
			'recent' => [
				'enabled' => [
					'homepageEmbyEnabled',
					'homepageEmbyRecent'
				],
				'auth' => [
					'homepageEmbyAuth',
					'homepageEmbyRecentAuth'
				],
				'not_empty' => [
					'embyURL',
					'embyToken'
				]
			],
			'metadata' => [
				'enabled' => [
					'homepageEmbyEnabled'
				],
				'auth' => [
					'homepageEmbyAuth'
				],
				'not_empty' => [
					'embyURL',
					'embyToken'
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
	
	public function homepageOrderembynowplaying()
	{
		if ($this->homepageItemPermissions($this->embyHomepagePermissions('streams'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Now Playing...</h2></div>
					<script>
						// Emby Stream
						homepageStream("emby", "' . $this->config['homepageStreamRefresh'] . '");
						// End Emby Stream
					</script>
				</div>
				';
		}
	}
	
	public function homepageOrderembyrecent()
	{
		if ($this->homepageItemPermissions($this->embyHomepagePermissions('recent'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Recent...</h2></div>
					<script>
						// Emby Recent
						homepageRecent("emby", "' . $this->config['homepageRecentRefresh'] . '");
						// End Emby Recent
					</script>
				</div>
				';
		}
	}
	
	public function getEmbyHomepageStreams()
	{
		if (!$this->homepageItemPermissions($this->embyHomepagePermissions('streams'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['embyURL']);
		$url = $url . '/Sessions?api_key=' . $this->config['embyToken'] . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$emby = json_decode($response->body, true);
				foreach ($emby as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = $this->resolveEmbyItem($child);
					}
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Emby Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Emby Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getEmbyHomepageRecent()
	{
		if (!$this->homepageItemPermissions($this->embyHomepagePermissions('recent'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['embyURL']);
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$username = false;
		$showPlayed = false;
		$userId = 0;
		try {
			
			
			if (isset($this->user['username'])) {
				$username = strtolower($this->user['username']);
			}
			// Get A User
			$userIds = $url . "/Users?api_key=" . $this->config['embyToken'];
			$response = Requests::get($userIds, array(), $options);
			if ($response->success) {
				$emby = json_decode($response->body, true);
				foreach ($emby as $value) { // Scan for admin user
					if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
						$userId = $value['Id'];
					}
					if ($username && strtolower($value['Name']) == $username) {
						$userId = $value['Id'];
						$showPlayed = false;
						break;
					}
				}
				$url = $url . '/Users/' . $userId . '/Items/Latest?EnableImages=true&Limit=' . $this->config['homepageRecentLimit'] . '&api_key=' . $this->config['embyToken'] . ($showPlayed ? '' : '&IsPlayed=false') . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines&IncludeItemTypes=Series,Episode,MusicAlbum,Audio,Movie,Video';
			} else {
				$this->setAPIResponse('error', 'Emby Error Occurred', 500);
				return false;
			}
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$emby = json_decode($response->body, true);
				foreach ($emby as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = $this->resolveEmbyItem($child);
					}
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Emby Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Emby Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getEmbyHomepageMetadata($array)
	{
		if (!$this->homepageItemPermissions($this->embyHomepagePermissions('metadata'), true)) {
			return false;
		}
		$key = $array['key'] ?? null;
		if (!$key) {
			$this->setAPIResponse('error', 'Emby Metadata key is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['embyURL']);
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$username = false;
		$showPlayed = false;
		$userId = 0;
		try {
			
			
			if (isset($this->user['username'])) {
				$username = strtolower($this->user['username']);
			}
			// Get A User
			$userIds = $url . "/Users?api_key=" . $this->config['embyToken'];
			$response = Requests::get($userIds, array(), $options);
			if ($response->success) {
				$emby = json_decode($response->body, true);
				foreach ($emby as $value) { // Scan for admin user
					if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
						$userId = $value['Id'];
					}
					if ($username && strtolower($value['Name']) == $username) {
						$userId = $value['Id'];
						$showPlayed = false;
						break;
					}
				}
				$url = $url . '/Users/' . $userId . '/Items/' . $key . '?EnableImages=true&Limit=' . $this->config['homepageRecentLimit'] . '&api_key=' . $this->config['embyToken'] . ($showPlayed ? '' : '&IsPlayed=false') . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
			} else {
				$this->setAPIResponse('error', 'Emby Error Occurred', 500);
				return false;
			}
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$emby = json_decode($response->body, true);
				if (isset($emby['NowPlayingItem']) || isset($emby['Name'])) {
					$items[] = $this->resolveEmbyItem($emby);
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Emby Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Emby Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function resolveEmbyItem($itemDetails)
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
		//$embyItem['array-item'] = $item;
		//$embyItem['array-itemdetails'] = $itemDetails;
		switch (@$item['Type']) {
			case 'Series':
				$embyItem['type'] = 'tv';
				$embyItem['title'] = $item['Name'];
				$embyItem['secondaryTitle'] = '';
				$embyItem['summary'] = '';
				$embyItem['ratingKey'] = $item['Id'];
				$embyItem['thumb'] = $item['Id'];
				$embyItem['key'] = $item['Id'] . "-list";
				$embyItem['nowPlayingThumb'] = $item['Id'];
				$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
				$embyItem['metadataKey'] = $item['Id'];
				$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['BackdropImageTags'][0]) ? 'Backdrop' : '');
				break;
			case 'Episode':
				$embyItem['type'] = 'tv';
				$embyItem['title'] = $item['SeriesName'];
				$embyItem['secondaryTitle'] = '';
				$embyItem['summary'] = '';
				$embyItem['ratingKey'] = $item['Id'];
				$embyItem['thumb'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']);
				$embyItem['key'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']) . "-list";
				$embyItem['nowPlayingThumb'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] : false);
				$embyItem['nowPlayingKey'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] . '-np' : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] . '-np' : false);
				$embyItem['metadataKey'] = $item['Id'];
				$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['ParentBackdropImageTags'][0]) ? 'Backdrop' : '');
				$embyItem['nowPlayingTitle'] = @$item['SeriesName'] . ' - ' . @$item['Name'];
				$embyItem['nowPlayingBottom'] = 'S' . @$item['ParentIndexNumber'] . ' · E' . @$item['IndexNumber'];
				break;
			case 'MusicAlbum':
			case 'Audio':
				$embyItem['type'] = 'music';
				$embyItem['title'] = $item['Name'];
				$embyItem['secondaryTitle'] = '';
				$embyItem['summary'] = '';
				$embyItem['ratingKey'] = $item['Id'];
				$embyItem['thumb'] = $item['Id'];
				$embyItem['key'] = $item['Id'] . "-list";
				$embyItem['nowPlayingThumb'] = (isset($item['AlbumId']) ? $item['AlbumId'] : @$item['ParentBackdropItemId']);
				$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
				$embyItem['metadataKey'] = isset($item['AlbumId']) ? $item['AlbumId'] : $item['Id'];
				$embyItem['nowPlayingImageType'] = (isset($item['ParentBackdropItemId']) ? "Primary" : "Backdrop");
				$embyItem['nowPlayingTitle'] = @$item['AlbumArtist'] . ' - ' . @$item['Name'];
				$embyItem['nowPlayingBottom'] = @$item['Album'];
				break;
			case 'Movie':
				$embyItem['type'] = 'movie';
				$embyItem['title'] = $item['Name'];
				$embyItem['secondaryTitle'] = '';
				$embyItem['summary'] = '';
				$embyItem['ratingKey'] = $item['Id'];
				$embyItem['thumb'] = $item['Id'];
				$embyItem['key'] = $item['Id'] . "-list";
				$embyItem['nowPlayingThumb'] = $item['Id'];
				$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
				$embyItem['metadataKey'] = $item['Id'];
				$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
				$embyItem['nowPlayingTitle'] = @$item['Name'];
				$embyItem['nowPlayingBottom'] = @$item['ProductionYear'];
				break;
			case 'Video':
				$embyItem['type'] = 'video';
				$embyItem['title'] = $item['Name'];
				$embyItem['secondaryTitle'] = '';
				$embyItem['summary'] = '';
				$embyItem['ratingKey'] = $item['Id'];
				$embyItem['thumb'] = $item['Id'];
				$embyItem['key'] = $item['Id'] . "-list";
				$embyItem['nowPlayingThumb'] = $item['Id'];
				$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
				$embyItem['metadataKey'] = $item['Id'];
				$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
				$embyItem['nowPlayingTitle'] = @$item['Name'];
				$embyItem['nowPlayingBottom'] = @$item['ProductionYear'];
				break;
			default:
				return false;
		}
		$embyItem['uid'] = $item['Id'];
		$embyItem['imageType'] = (isset($item['ImageTags']['Primary']) ? "Primary" : false);
		$embyItem['elapsed'] = isset($itemDetails['PlayState']['PositionTicks']) && $itemDetails['PlayState']['PositionTicks'] !== '0' ? (int)$itemDetails['PlayState']['PositionTicks'] : null;
		$embyItem['duration'] = isset($itemDetails['NowPlayingItem']['RunTimeTicks']) ? (int)$itemDetails['NowPlayingItem']['RunTimeTicks'] : (int)(isset($item['RunTimeTicks']) ? $item['RunTimeTicks'] : '');
		$embyItem['watched'] = ($embyItem['elapsed'] && $embyItem['duration'] ? floor(($embyItem['elapsed'] / $embyItem['duration']) * 100) : 0);
		$embyItem['transcoded'] = isset($itemDetails['TranscodingInfo']['CompletionPercentage']) ? floor((int)$itemDetails['TranscodingInfo']['CompletionPercentage']) : 100;
		$embyItem['stream'] = @$itemDetails['PlayState']['PlayMethod'];
		$embyItem['id'] = $item['ServerId'];
		$embyItem['session'] = @$itemDetails['DeviceId'];
		$embyItem['bandwidth'] = isset($itemDetails['TranscodingInfo']['Bitrate']) ? $itemDetails['TranscodingInfo']['Bitrate'] / 1000 : '';
		$embyItem['bandwidthType'] = 'wan';
		$embyItem['sessionType'] = (@$itemDetails['PlayState']['PlayMethod'] == 'Transcode') ? 'Transcoding' : 'Direct Playing';
		$embyItem['state'] = ((@(string)$itemDetails['PlayState']['IsPaused'] == '1') ? "pause" : "play");
		$embyItem['user'] = ($this->config['homepageShowStreamNames'] && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'])) ? @(string)$itemDetails['UserName'] : "";
		$embyItem['userThumb'] = '';
		$embyItem['userAddress'] = (isset($itemDetails['RemoteEndPoint']) ? $itemDetails['RemoteEndPoint'] : "x.x.x.x");
		$embyVariablesForLink = [
			'{id}' => $embyItem['uid'],
			'{serverId}' => $embyItem['id']
		];
		$embyItem['address'] = $this->userDefinedIdReplacementLink($this->config['homepageEmbyLink'], $embyVariablesForLink);
		$embyItem['nowPlayingOriginalImage'] = 'api/v2/homepage/image?source=emby&type=' . $embyItem['nowPlayingImageType'] . '&img=' . $embyItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $embyItem['nowPlayingKey'] . '$' . $this->randString();
		$embyItem['originalImage'] = 'api/v2/homepage/image?source=emby&type=' . $embyItem['imageType'] . '&img=' . $embyItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $embyItem['key'] . '$' . $this->randString();
		$embyItem['openTab'] = $this->config['embyTabURL'] && $this->config['embyTabName'] ? true : false;
		$embyItem['tabName'] = $this->config['embyTabName'] ? $this->config['embyTabName'] : '';
		// Stream info
		$embyItem['userStream'] = array(
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
						$actorImage = 'api/v2/homepage/image?source=emby&type=Primary&img=' . (string)$value['Id'] . '&height=' . $actorHeight . '&width=' . $actorWidth . '&key=' . (string)$value['Id'] . '-cast';
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
		$embyItem['metadata'] = array(
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
		if (file_exists($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg')) {
			$embyItem['nowPlayingImageURL'] = $cacheDirectoryWeb . $embyItem['nowPlayingKey'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $embyItem['key'] . '.jpg')) {
			$embyItem['imageURL'] = $cacheDirectoryWeb . $embyItem['key'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg') || !file_exists($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg')) {
			$embyItem['nowPlayingImageURL'] = 'api/v2/homepage/image?source=emby&type=' . $embyItem['nowPlayingImageType'] . '&img=' . $embyItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $embyItem['nowPlayingKey'] . '';
		}
		if (file_exists($cacheDirectory . $embyItem['key'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $embyItem['key'] . '.jpg') || !file_exists($cacheDirectory . $embyItem['key'] . '.jpg')) {
			$embyItem['imageURL'] = 'api/v2/homepage/image?source=emby&type=' . $embyItem['imageType'] . '&img=' . $embyItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $embyItem['key'] . '';
		}
		if (!$embyItem['nowPlayingThumb']) {
			$embyItem['nowPlayingOriginalImage'] = $embyItem['nowPlayingImageURL'] = "plugins/images/cache/no-np.png";
			$embyItem['nowPlayingKey'] = "no-np";
		}
		if (!$embyItem['thumb']) {
			$embyItem['originalImage'] = $embyItem['imageURL'] = "plugins/images/cache/no-list.png";
			$embyItem['key'] = "no-list";
		}
		if (isset($useImage)) {
			$embyItem['useImage'] = $useImage;
		}
		return $embyItem;
	}
	
}