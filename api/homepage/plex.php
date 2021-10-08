<?php

trait PlexHomepageItem
{
	public function plexSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Plex',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/plex.png',
			'category' => 'Media Server',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$libraryList = [['name' => 'Refresh page to update List', 'value' => '', 'disabled' => true]];
		if ($this->config['plexID'] !== '' && $this->config['plexToken'] !== '') {
			$libraryList = [];
			$loop = $this->plexLibraryList('key')['libraries'];
			foreach ($loop as $key => $value) {
				$libraryList[] = ['name' => $key, 'value' => $value];
			}
		}
		$homepageSettings = [
			'docs' => $this->docs('features/homepage/plex-homepage-item'),
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepagePlexEnabled'),
					$this->settingsOption('auth', 'homepagePlexAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'plexURL'),
					$this->settingsOption('blank'),
					$this->settingsOption('disable-cert-check', 'plexDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'plexUseCustomCertificate'),
					$this->settingsOption('token', 'plexToken'),
					$this->settingsOption('button', '', ['label' => 'Get Plex Token', 'icon' => 'fa fa-ticket', 'text' => 'Retrieve', 'attr' => 'onclick="PlexOAuth(oAuthSuccess,oAuthError, null, \'#homepage-Plex-form [name=plexToken]\')"']),
					$this->settingsOption('password-alt', 'plexID', ['label' => 'Plex Machine']),
					$this->settingsOption('button', '', ['label' => 'Get Plex Machine', 'icon' => 'fa fa-id-badge', 'text' => 'Retrieve', 'attr' => 'onclick="showPlexMachineForm(\'#homepage-Plex-form [name=plexID]\')"']),
				],
				'Active Streams' => [
					$this->settingsOption('enable', 'homepagePlexStreams'),
					$this->settingsOption('auth', 'homepagePlexStreamsAuth'),
					$this->settingsOption('switch', 'homepageShowStreamNames', ['label' => 'User Information']),
					$this->settingsOption('auth', 'homepageShowStreamNamesAuth'),
					$this->settingsOption('refresh', 'homepageStreamRefresh'),
					$this->settingsOption('plex-library-exclude', 'homepagePlexStreamsExclude', ['options' => $libraryList]),
				],
				'Recent Items' => [
					$this->settingsOption('enable', 'homepagePlexRecent'),
					$this->settingsOption('auth', 'homepagePlexRecentAuth'),
					$this->settingsOption('plex-library-exclude', 'homepagePlexRecentExclude', ['options' => $libraryList]),
					$this->settingsOption('limit', 'homepageRecentLimit'),
					$this->settingsOption('refresh', 'homepageRecentRefresh'),
				],
				'Media Search' => [
					$this->settingsOption('enable', 'mediaSearch'),
					$this->settingsOption('auth', 'mediaSearchAuth'),
					$this->settingsOption('plex-library-exclude', 'homepagePlexSearchExclude', ['options' => $libraryList]),
					$this->settingsOption('media-search-server', 'mediaSearchType'),
				],
				'Playlists' => [
					$this->settingsOption('enable', 'homepagePlexPlaylist'),
					$this->settingsOption('auth', 'homepagePlexPlaylistAuth'),
				],
				'Misc Options' => [
					$this->settingsOption('input', 'plexTabName', ['label' => 'Plex Tab Name', 'placeholder' => 'Only use if you have Plex in a reverse proxy']),
					$this->settingsOption('input', 'plexTabURL', ['label' => 'Plex Tab WAN URL', 'placeholder' => 'http(s)://domain.com/plex']),
					$this->settingsOption('image-cache-quality', 'cacheImageSize'),
					$this->settingsOption('blank'),
					$this->settingsOption('switch', 'homepageUseCustomStreamNames', ['label' => 'Use Tautulli custom names for users']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'plex'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionPlex()
	{
		if (!empty($this->config['plexURL']) && !empty($this->config['plexToken'])) {
			$url = $this->qualifyURL($this->config['plexURL']) . "/servers?X-Plex-Token=" . $this->config['plexToken'];
			try {
				$options = $this->requestOptions($url, null, $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
				$response = Requests::get($url, [], $options);
				libxml_use_internal_errors(true);
				if ($response->success) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				} else {
					$this->setAPIResponse('error', 'URL and/or Token not setup correctly', 422);
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'URL and/or Token not setup', 422);
			return 'URL and/or Token not setup';
		}
	}
	
	public function plexHomepagePermissions($key = null)
	{
		$permissions = [
			'streams' => [
				'enabled' => [
					'homepagePlexEnabled',
					'homepagePlexStreams'
				],
				'auth' => [
					'homepagePlexAuth',
					'homepagePlexStreamsAuth'
				],
				'not_empty' => [
					'plexURL',
					'plexToken',
					'plexID'
				]
			],
			'recent' => [
				'enabled' => [
					'homepagePlexEnabled',
					'homepagePlexRecent'
				],
				'auth' => [
					'homepagePlexAuth',
					'homepagePlexRecentAuth'
				],
				'not_empty' => [
					'plexURL',
					'plexToken',
					'plexID'
				]
			],
			'playlists' => [
				'enabled' => [
					'homepagePlexEnabled',
					'homepagePlexPlaylist'
				],
				'auth' => [
					'homepagePlexAuth',
					'homepagePlexPlaylistAuth'
				],
				'not_empty' => [
					'plexURL',
					'plexToken',
					'plexID'
				]
			],
			'metadata' => [
				'enabled' => [
					'homepagePlexEnabled'
				],
				'auth' => [
					'homepagePlexAuth'
				],
				'not_empty' => [
					'plexURL',
					'plexToken',
					'plexID'
				]
			],
			'search' => [
				'enabled' => [
					'homepagePlexEnabled',
					'mediaSearch'
				],
				'auth' => [
					'homepagePlexAuth',
					'mediaSearchAuth'
				],
				'not_empty' => [
					'plexURL',
					'plexToken',
					'plexID'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function homepageOrderplexnowplaying()
	{
		if ($this->homepageItemPermissions($this->plexHomepagePermissions('streams'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Now Playing...</h2></div>
					<script>
						// Plex Stream
						homepageStream("plex", "' . $this->config['homepageStreamRefresh'] . '");
						// End Plex Stream
					</script>
				</div>
				';
		}
	}
	
	public function homepageOrderplexrecent()
	{
		if ($this->homepageItemPermissions($this->plexHomepagePermissions('recent'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Recent...</h2></div>
					<script>
						// Plex Recent
						homepageRecent("plex", "' . $this->config['homepageRecentRefresh'] . '");
						// End Plex Recent
					</script>
				</div>
				';
		}
	}
	
	public function homepageOrderplexplaylist()
	{
		if ($this->homepageItemPermissions($this->plexHomepagePermissions('playlists'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Playlists...</h2></div>
					<script>
						// Plex Playlist
						homepagePlaylist("plex");
						// End Plex Playlist
					</script>
				</div>
				';
		}
	}
	
	public function getPlexHomepageStreams()
	{
		if (!$this->homepageItemPermissions($this->plexHomepagePermissions('streams'), true)) {
			return false;
		}
		if ($this->demo) {
			return $this->demoData('plex/plex-streams.json');
		}
		$this->setTautulliFriendlyNames();
		$ignore = array();
		$exclude = explode(',', $this->config['homepagePlexStreamsExclude']);
		$resolve = true;
		$url = $this->qualifyURL($this->config['plexURL']);
		$url = $url . "/status/sessions?X-Plex-Token=" . $this->config['plexToken'];
		$options = $this->requestOptions($url, $this->config['homepageStreamRefresh'], $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
		try {
			$response = Requests::get($url, [], $options);
			libxml_use_internal_errors(true);
			if ($response->success) {
				$items = array();
				$plex = simplexml_load_string($response->body);
				foreach ($plex as $child) {
					if (!in_array($child['type'], $ignore) && !in_array($child['librarySectionID'], $exclude) && isset($child['librarySectionID'])) {
						$items[] = $this->resolvePlexItem($child);
					}
				}
				$api['content'] = ($resolve) ? $items : $plex;
				$api['plexID'] = $this->config['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			}
		} catch (Exception $e) {
			$this->setAPIResponse('error', null, 422, [$e->getMessage()]);
			return false;
		}
	}
	
	public function getPlexHomepageRecent()
	{
		if (!$this->homepageItemPermissions($this->plexHomepagePermissions('recent'), true)) {
			return false;
		}
		$ignore = array();
		$exclude = explode(',', $this->config['homepagePlexRecentExclude']);
		$resolve = true;
		$url = $this->qualifyURL($this->config['plexURL']);
		$urls['movie'] = $url . "/hubs/home/recentlyAdded?X-Plex-Token=" . $this->config['plexToken'] . "&X-Plex-Container-Start=0&X-Plex-Container-Size=" . $this->config['homepageRecentLimit'] . "&type=1";
		$urls['tv'] = $url . "/hubs/home/recentlyAdded?X-Plex-Token=" . $this->config['plexToken'] . "&X-Plex-Container-Start=0&X-Plex-Container-Size=" . $this->config['homepageRecentLimit'] . "&type=2";
		$urls['music'] = $url . "/hubs/home/recentlyAdded?X-Plex-Token=" . $this->config['plexToken'] . "&X-Plex-Container-Start=0&X-Plex-Container-Size=" . $this->config['homepageRecentLimit'] . "&type=8";
		try {
			foreach ($urls as $k => $v) {
				$options = $this->requestOptions($url, $this->config['homepageRecentRefresh'], $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
				$response = Requests::get($v, [], $options);
				libxml_use_internal_errors(true);
				if ($response->success) {
					$items = array();
					$plex = simplexml_load_string($response->body);
					foreach ($plex as $child) {
						if (!in_array($child['type'], $ignore) && !in_array($child['librarySectionID'], $exclude) && isset($child['librarySectionID'])) {
							$items[] = $this->resolvePlexItem($child);
						}
					}
					if (isset($api)) {
						$api['content'] = array_merge($api['content'], ($resolve) ? $items : $plex);
					} else {
						$api['content'] = ($resolve) ? $items : $plex;
					}
				}
			}
			if (isset($api['content'])) {
				usort($api['content'], function ($a, $b) {
					return $b['addedAt'] <=> $a['addedAt'];
				});
			}
			$api['plexID'] = $this->config['plexID'];
			$api['showNames'] = true;
			$api['group'] = '1';
			$this->setAPIResponse('success', null, 200, $api);
			return $api;
		} catch (Exception $e) {
			$this->setAPIResponse('error', null, 422, [$e->getMessage()]);
			return false;
		}
	}
	
	public function getPlexHomepagePlaylists()
	{
		if (!$this->homepageItemPermissions($this->plexHomepagePermissions('playlists'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['plexURL']);
		$url = $url . "/playlists?X-Plex-Token=" . $this->config['plexToken'];
		$options = $this->requestOptions($url, null, $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
		try {
			$response = Requests::get($url, [], $options);
			libxml_use_internal_errors(true);
			if ($response->success) {
				$items = array();
				$plex = simplexml_load_string($response->body);
				foreach ($plex as $child) {
					if ($child['playlistType'] == "video" && strpos(strtolower($child['title']), 'private') === false) {
						$playlistTitleClean = preg_replace("/(\W)+/", "", (string)$child['title']);
						$playlistURL = $this->qualifyURL($this->config['plexURL']);
						$playlistURL = $playlistURL . $child['key'] . "?X-Plex-Token=" . $this->config['plexToken'];
						$options = ($this->localURL($url)) ? array('verify' => false) : array();
						$playlistResponse = Requests::get($playlistURL, array(), $options);
						if ($playlistResponse->success) {
							$playlistResponse = simplexml_load_string($playlistResponse->body);
							$items[$playlistTitleClean]['title'] = (string)$child['title'];
							foreach ($playlistResponse->Video as $playlistItem) {
								$items[$playlistTitleClean][] = $this->resolvePlexItem($playlistItem);
							}
						}
					}
				}
				$api['content'] = $items;
				$api['plexID'] = $this->config['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Plex API error', 500);
				return false;
			}
		} catch (Exception $e) {
			$this->setAPIResponse('error', null, 422, [$e->getMessage()]);
			return false;
		}
	}
	
	public function getPlexHomepageMetadata($array)
	{
		if (!$this->homepageItemPermissions($this->plexHomepagePermissions('metadata'), true)) {
			return false;
		}
		if ($this->demo) {
			return $this->demoData('plex/plex-metadata.json');
		}
		$key = $array['key'] ?? null;
		if (!$key) {
			$this->setAPIResponse('error', 'Plex Metadata key is not defined', 422);
			return false;
		}
		$ignore = array();
		$resolve = true;
		$url = $this->qualifyURL($this->config['plexURL']);
		$url = $url . "/library/metadata/" . $key . "?X-Plex-Token=" . $this->config['plexToken'];
		$options = $this->requestOptions($url, null, $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
		try {
			$response = Requests::get($url, [], $options);
			libxml_use_internal_errors(true);
			if ($response->success) {
				$items = array();
				$plex = simplexml_load_string($response->body);
				foreach ($plex as $child) {
					if (!in_array($child['type'], $ignore) && isset($child['librarySectionID'])) {
						$items[] = $this->resolvePlexItem($child);
					}
				}
				$api['content'] = ($resolve) ? $items : $plex;
				$api['plexID'] = $this->config['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			}
		} catch (Exception $e) {
			$this->setAPIResponse('error', null, 422, [$e->getMessage()]);
			return false;
		}
	}
	
	public function getPlexHomepageSearch($query)
	{
		if (!$this->homepageItemPermissions($this->plexHomepagePermissions('search'), true)) {
			return false;
		}
		$query = $query ?? null;
		if (!$query) {
			$this->setAPIResponse('error', 'Plex Metadata key is not defined', 422);
			return false;
		}
		$ignore = array('artist', 'episode');
		$exclude = explode(',', $this->config['homepagePlexSearchExclude']);
		$resolve = true;
		$url = $this->qualifyURL($this->config['plexURL']);
		$url = $url . "/search?query=" . rawurlencode($query) . "&X-Plex-Token=" . $this->config['plexToken'];
		$options = $this->requestOptions($url, null, $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
		try {
			$response = Requests::get($url, [], $options);
			libxml_use_internal_errors(true);
			if ($response->success) {
				$items = array();
				$plex = simplexml_load_string($response->body);
				foreach ($plex as $child) {
					if (!in_array($child['type'], $ignore) && !in_array($child['librarySectionID'], $exclude) && isset($child['librarySectionID'])) {
						$items[] = $this->resolvePlexItem($child);
					}
				}
				$api['content'] = ($resolve) ? $items : $plex;
				$api['plexID'] = $this->config['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			}
		} catch (Exception $e) {
			$this->setAPIResponse('error', null, 422, [$e->getMessage()]);
			return false;
		}
	}
	
	public function resolvePlexItem($item)
	{
		// Static Height & Width
		$height = $this->getCacheImageSize('h');
		$width = $this->getCacheImageSize('w');
		$nowPlayingHeight = $this->getCacheImageSize('nph');
		$nowPlayingWidth = $this->getCacheImageSize('npw');
		// Cache Directories
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheDirectoryWeb = 'plugins/images/cache/';
		// Types
		switch ($item['type']) {
			case 'show':
				$plexItem['type'] = 'tv';
				$plexItem['title'] = (string)$item['title'];
				$plexItem['secondaryTitle'] = (string)$item['year'];
				$plexItem['summary'] = (string)$item['summary'];
				$plexItem['ratingKey'] = (string)$item['ratingKey'];
				$plexItem['thumb'] = (string)$item['thumb'];
				$plexItem['key'] = (string)$item['ratingKey'] . "-list";
				$plexItem['nowPlayingThumb'] = (string)$item['art'];
				$plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
				$plexItem['nowPlayingTitle'] = (string)$item['title'];
				$plexItem['nowPlayingBottom'] = (string)$item['year'];
				$plexItem['metadataKey'] = (string)$item['ratingKey'];
				break;
			case 'season':
				$plexItem['type'] = 'tv';
				$plexItem['title'] = (string)$item['parentTitle'];
				$plexItem['secondaryTitle'] = (string)$item['title'];
				$plexItem['summary'] = (string)$item['parentSummary'];
				$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
				$plexItem['thumb'] = (string)$item['thumb'];
				$plexItem['key'] = (string)$item['ratingKey'] . "-list";
				$plexItem['nowPlayingThumb'] = (string)$item['art'];
				$plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
				$plexItem['metadataKey'] = (string)$item['parentRatingKey'];
				break;
			case 'episode':
				$plexItem['type'] = 'tv';
				$plexItem['title'] = (string)$item['grandparentTitle'];
				$plexItem['secondaryTitle'] = (string)$item['parentTitle'] . ' - Episode ' . (string)$item['index'];
				$plexItem['summary'] = (string)$item['title'];
				$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
				$plexItem['thumb'] = ($item['parentThumb'] ? (string)$item['parentThumb'] : (string)$item['grandparentThumb']);
				$plexItem['key'] = (string)$item['ratingKey'] . "-list";
				$plexItem['nowPlayingThumb'] = (string)$item['grandparentArt'];
				$plexItem['nowPlayingKey'] = (string)$item['grandparentRatingKey'] . "-np";
				$plexItem['nowPlayingTitle'] = (string)$item['grandparentTitle'] . ' - ' . (string)$item['title'];
				$plexItem['nowPlayingBottom'] = 'S' . (string)$item['parentIndex'] . ' · E' . (string)$item['index'];
				$plexItem['metadataKey'] = (string)$item['grandparentRatingKey'];
				break;
			case 'clip':
				$useImage = (isset($item['live']) ? "plugins/images/cache/livetv.png" : null);
				$plexItem['type'] = 'clip';
				$plexItem['title'] = (isset($item['live']) ? 'Live TV' : (string)$item['title']);
				$plexItem['secondaryTitle'] = '';
				$plexItem['summary'] = (string)$item['summary'];
				$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
				$plexItem['thumb'] = (string)$item['thumb'];
				$plexItem['key'] = (string)$item['ratingKey'] . "-list";
				$plexItem['nowPlayingThumb'] = (string)$item['art'];
				$plexItem['nowPlayingKey'] = isset($item['ratingKey']) ? (string)$item['ratingKey'] . "-np" : (isset($item['live']) ? "livetv.png" : ":)");
				$plexItem['nowPlayingTitle'] = $plexItem['title'];
				$plexItem['nowPlayingBottom'] = isset($item['extraType']) ? "Trailer" : (isset($item['live']) ? "Live TV" : ":)");
				break;
			case 'album':
			case 'track':
				$plexItem['type'] = 'music';
				$plexItem['title'] = (string)$item['parentTitle'];
				$plexItem['secondaryTitle'] = (string)$item['title'];
				$plexItem['summary'] = (string)$item['title'];
				$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
				$plexItem['thumb'] = (string)$item['thumb'];
				$plexItem['key'] = (string)$item['ratingKey'] . "-list";
				$plexItem['nowPlayingThumb'] = ($item['parentThumb']) ? (string)$item['parentThumb'] : (string)$item['art'];
				$plexItem['nowPlayingKey'] = (string)$item['parentRatingKey'] . "-np";
				$plexItem['nowPlayingTitle'] = (string)$item['grandparentTitle'] . ' - ' . (string)$item['title'];
				$plexItem['nowPlayingBottom'] = (string)$item['parentTitle'];
				$plexItem['metadataKey'] = isset($item['grandparentRatingKey']) ? (string)$item['grandparentRatingKey'] : (string)$item['parentRatingKey'];
				break;
			default:
				$plexItem['type'] = 'movie';
				$plexItem['title'] = (string)$item['title'];
				$plexItem['secondaryTitle'] = (string)$item['year'];
				$plexItem['summary'] = (string)$item['summary'];
				$plexItem['ratingKey'] = (string)$item['ratingKey'];
				$plexItem['thumb'] = (string)$item['thumb'];
				$plexItem['key'] = (string)$item['ratingKey'] . "-list";
				$plexItem['nowPlayingThumb'] = (string)$item['art'];
				$plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
				$plexItem['nowPlayingTitle'] = (string)$item['title'];
				$plexItem['nowPlayingBottom'] = (string)$item['year'];
				$plexItem['metadataKey'] = (string)$item['ratingKey'];
		}
		$plexItem['originalType'] = $item['type'];
		$plexItem['uid'] = (string)$item['ratingKey'];
		$plexItem['elapsed'] = isset($item['viewOffset']) && $item['viewOffset'] !== '0' ? (int)$item['viewOffset'] : null;
		$plexItem['duration'] = isset($item['duration']) ? (int)$item['duration'] : (int)$item->Media['duration'];
		$plexItem['addedAt'] = isset($item['addedAt']) ? (int)$item['addedAt'] : null;
		$plexItem['watched'] = ($plexItem['elapsed'] && $plexItem['duration'] ? floor(($plexItem['elapsed'] / $plexItem['duration']) * 100) : 0);
		$plexItem['transcoded'] = isset($item->TranscodeSession['progress']) ? floor((int)$item->TranscodeSession['progress'] - $plexItem['watched']) : '';
		$plexItem['stream'] = isset($item->Media->Part->Stream['decision']) ? (string)$item->Media->Part->Stream['decision'] : '';
		$plexItem['id'] = str_replace('"', '', (string)$item->Player['machineIdentifier']);
		$plexItem['session'] = (string)$item->Session['id'];
		$plexItem['bandwidth'] = (string)$item->Session['bandwidth'];
		$plexItem['bandwidthType'] = (string)$item->Session['location'];
		$plexItem['sessionType'] = isset($item->TranscodeSession['progress']) ? 'Transcoding' : 'Direct Playing';
		$plexItem['state'] = (((string)$item->Player['state'] == "paused") ? "pause" : "play");
		$plexItem['user'] = $this->formatPlexUserName($item);
		$plexItem['userThumb'] = ($this->config['homepageShowStreamNames'] && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'])) ? (string)$item->User['thumb'] : "";
		$plexItem['userAddress'] = ($this->config['homepageShowStreamNames'] && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'])) ? (string)$item->Player['address'] : "x.x.x.x";
		$plexItem['address'] = $this->config['plexTabURL'] ? $this->config['plexTabURL'] . "/web/index.html#!/server/" . $this->config['plexID'] . "/details?key=/library/metadata/" . $item['ratingKey'] : "https://app.plex.tv/web/app#!/server/" . $this->config['plexID'] . "/details?key=/library/metadata/" . $item['ratingKey'];
		$plexItem['nowPlayingOriginalImage'] = 'api/v2/homepage/image?source=plex&img=' . $plexItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $plexItem['nowPlayingKey'] . '$' . $this->randString();
		$plexItem['originalImage'] = 'api/v2/homepage/image?source=plex&img=' . $plexItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $plexItem['key'] . '$' . $this->randString();
		$plexItem['openTab'] = $this->config['plexTabURL'] && $this->config['plexTabName'] ? true : false;
		$plexItem['tabName'] = $this->config['plexTabName'] ? $this->config['plexTabName'] : '';
		// Stream info
		$plexItem['userStream'] = array(
			'platform' => (string)$item->Player['platform'],
			'product' => (string)$item->Player['product'],
			'device' => (string)$item->Player['device'],
			'stream' => isset($item->Media) ? (string)$item->Media->Part['decision'] . ($item->TranscodeSession['throttled'] == '1' ? ' (Throttled)' : '') : '',
			'videoResolution' => (string)$item->Media['videoResolution'],
			'throttled' => ($item->TranscodeSession['throttled'] == 1) ? true : false,
			'sourceVideoCodec' => (string)$item->TranscodeSession['sourceVideoCodec'],
			'videoCodec' => (string)$item->TranscodeSession['videoCodec'],
			'audioCodec' => (string)$item->TranscodeSession['audioCodec'],
			'sourceAudioCodec' => (string)$item->TranscodeSession['sourceAudioCodec'],
			'videoDecision' => $this->streamType((string)$item->TranscodeSession['videoDecision']),
			'audioDecision' => $this->streamType((string)$item->TranscodeSession['audioDecision']),
			'container' => (string)$item->TranscodeSession['container'],
			'audioChannels' => (string)$item->TranscodeSession['audioChannels']
		);
		// Genre catch all
		if ($item->Genre) {
			$genres = array();
			foreach ($item->Genre as $key => $value) {
				$genres[] = (string)$value['tag'];
			}
		}
		// Actor catch all
		if ($item->Role) {
			$actors = array();
			foreach ($item->Role as $key => $value) {
				if ($value['thumb']) {
					$actors[] = array(
						'name' => (string)$value['tag'],
						'role' => (string)$value['role'],
						'thumb' => (string)$value['thumb']
					);
				}
			}
		}
		// Metadata information
		$plexItem['metadata'] = array(
			'guid' => (string)$item['guid'],
			'summary' => (string)$item['summary'],
			'rating' => (string)$item['rating'],
			'duration' => (string)$item['duration'],
			'originallyAvailableAt' => (string)$item['originallyAvailableAt'],
			'year' => (string)$item['year'],
			'studio' => (string)$item['studio'],
			'tagline' => (string)$item['tagline'],
			'genres' => ($item->Genre) ? $genres : '',
			'actors' => ($item->Role) ? $actors : ''
		);
		if (file_exists($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg')) {
			$plexItem['nowPlayingImageURL'] = $cacheDirectoryWeb . $plexItem['nowPlayingKey'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $plexItem['key'] . '.jpg')) {
			$plexItem['imageURL'] = $cacheDirectoryWeb . $plexItem['key'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg') || !file_exists($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg')) {
			$plexItem['nowPlayingImageURL'] = 'api/v2/homepage/image?source=plex&img=' . $plexItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $plexItem['nowPlayingKey'] . '';
		}
		if (file_exists($cacheDirectory . $plexItem['key'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $plexItem['key'] . '.jpg') || !file_exists($cacheDirectory . $plexItem['key'] . '.jpg')) {
			$plexItem['imageURL'] = 'api/v2/homepage/image?source=plex&img=' . $plexItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $plexItem['key'] . '';
		}
		if (!$plexItem['nowPlayingThumb']) {
			$plexItem['nowPlayingOriginalImage'] = $plexItem['nowPlayingImageURL'] = "plugins/images/cache/no-np.png";
			$plexItem['nowPlayingKey'] = "no-np";
		}
		if (!$plexItem['thumb'] || $plexItem['addedAt'] >= (time() - 300)) {
			$plexItem['originalImage'] = $plexItem['imageURL'] = "plugins/images/cache/no-list.png";
			$plexItem['key'] = "no-list";
		}
		if (isset($useImage)) {
			$plexItem['useImage'] = $useImage;
		}
		return $plexItem;
	}
	
	public function getTautulliFriendlyNames($bypass = null)
	{
		$names = [];
		if (!$this->qualifyRequest(1) && !$bypass) {
			return false;
		}
		$url = $this->qualifyURL($this->config['tautulliURL']);
		$url .= '/api/v2?apikey=' . $this->config['tautulliApikey'];
		$url .= '&cmd=get_users';
		$options = $this->requestOptions($url, null, $this->config['tautulliDisableCertCheck'], $this->config['tautulliUseCustomCertificate']);
		try {
			$response = Requests::get($url, [], $options);
			$response = json_decode($response->body, true);
			foreach ($response['response']['data'] as $user) {
				if ($user['user_id'] != 0) {
					$names[$user['username']] = $user['friendly_name'];
				}
			}
		} catch (Exception $e) {
			$this->setAPIResponse('error', null, 422, [$e->getMessage()]);
		}
		$this->setAPIResponse('success', null, 200, $names);
		return $names;
	}
	
	public function setTautulliFriendlyNames()
	{
		if ($this->config['tautulliURL'] && $this->config['tautulliApikey'] && $this->config['homepageUseCustomStreamNames']) {
			$names = $this->getTautulliFriendlyNames(true);
			$names = json_encode($names);
			if ($names !== $this->config['homepageCustomStreamNames']) {
				$this->updateConfig(array('homepageCustomStreamNames' => $names));
				$this->config['homepageCustomStreamNames'] = $names;
				$this->setLoggerChannel('Tautulli')->debug('Updating Tautulli custom names config item', $names);
			}
		}
	}
	
	private function formatPlexUserName($item)
	{
		$name = ($this->config['homepageShowStreamNames'] && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'])) ? (string)$item->User['title'] : "";
		try {
			if ($this->config['homepageUseCustomStreamNames']) {
				$customNames = json_decode($this->config['homepageCustomStreamNames'], true);
				if (array_key_exists($name, $customNames)) {
					$name = $customNames[$name];
				}
			}
		} catch (Exception $e) {
			// don't do anythig if it goes wrong, like if the JSON is badly formatted
		}
		return $name;
	}
	
	public function plexLibraryList($value = 'id')
	{
		
		if (!empty($this->config['plexToken']) && !empty($this->config['plexID'])) {
			$url = 'https://plex.tv/api/servers/' . $this->config['plexID'];
			try {
				$headers = array(
					"Accept" => "application/json",
					"X-Plex-Token" => $this->config['plexToken']
				);
				$response = Requests::get($url, $headers, array());
				libxml_use_internal_errors(true);
				if ($response->success) {
					$libraryList = array();
					$plex = simplexml_load_string($response->body);
					foreach ($plex->Server->Section as $child) {
						$libraryList['libraries'][(string)$child['title']] = (string)$child[$value];
					}
					$libraryList = array_change_key_case($libraryList, CASE_LOWER);
					return $libraryList;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				return false;
			};
		}
		return false;
	}
}