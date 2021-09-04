<?php

trait TautulliHomepageItem
{
	public function tautulliSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Tautulli',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/tautulli.png',
			'category' => 'Monitor',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageTautulliEnabled'),
					$this->settingsOption('auth', 'homepageTautulliAuth'),
				],
				'Options' => [
					$this->settingsOption('title', 'tautulliHeader'),
					$this->settingsOption('toggle-title', 'tautulliHeaderToggle'),
					$this->settingsOption('refresh', 'homepageTautulliRefresh'),
				],
				'Connection' => [
					$this->settingsOption('multiple-url', 'tautulliURL'),
					$this->settingsOption('api-key', 'tautulliApikey'),
					$this->settingsOption('disable-cert-check', 'tautulliDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'tautulliUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'tautulli'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'tautulliSocksEnabled'),
					$this->settingsOption('auth', 'tautulliSocksAuth'),
				],
				'Library Stats' => [
					$this->settingsOption('switch', 'tautulliLibraries', ['label' => 'Libraries', 'help' => 'Shows/hides the card with library information.']),
					$this->settingsOption('auth', 'homepageTautulliLibraryAuth'),
				],
				'Viewing Stats' => [
					$this->settingsOption('switch', 'tautulliPopularMovies', ['label' => 'Popular Movies', 'help' => 'Shows/hides the card with Popular Movie information.']),
					$this->settingsOption('switch', 'tautulliPopularTV', ['label' => 'Popular TV', 'help' => 'Shows/hides the card with Popular TV information.']),
					$this->settingsOption('switch', 'tautulliTopMovies', ['label' => 'Top Movies', 'help' => 'Shows/hides the card with Top Movies information.']),
					$this->settingsOption('switch', 'tautulliTopTV', ['label' => 'Top TV', 'help' => 'Shows/hides the card with Top TV information.']),
					$this->settingsOption('auth', 'homepageTautulliViewsAuth'),
				],
				'Misc Stats' => [
					$this->settingsOption('switch', 'tautulliTopUsers', ['label' => 'Top Users', 'help' => 'Shows/hides the card with Top Users information.']),
					$this->settingsOption('switch', 'tautulliTopPlatforms', ['label' => 'Top Platforms', 'help' => 'Shows/hides the card with Top Platforms information.']),
					$this->settingsOption('auth', 'homepageTautulliMiscAuth'),
					$this->settingsOption('switch', 'tautulliFriendlyName', ['label' => 'Use Friendly Name', 'help' => 'Use the friendly name set in tautulli for users.']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'tautulli'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionTautulli()
	{
		if (empty($this->config['tautulliURL'])) {
			$this->setAPIResponse('error', 'Tautulli URL is not defined', 422);
			return false;
		}
		if (empty($this->config['tautulliApikey'])) {
			$this->setAPIResponse('error', 'Tautulli Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['tautulliURL']);
		$apiURL = $url . '/api/v2?apikey=' . $this->config['tautulliApikey'];
		try {
			$homestatsUrl = $apiURL . '&cmd=get_home_stats&grouping=1';
			$options = $this->requestOptions($this->config['tautulliURL'], $this->config['homepageTautulliRefresh'], $this->config['tautulliDisableCertCheck'], $this->config['tautulliUseCustomCertificate']);
			$homestats = Requests::get($homestatsUrl, [], $options);
			if ($homestats->success) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			} else {
				$this->setAPIResponse('error', 'Tautulli Error Occurred - Check URL or Credentials', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Tautulli Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function tautulliHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageTautulliEnabled'
				],
				'auth' => [
					'homepageTautulliAuth'
				],
				'not_empty' => [
					'tautulliURL',
					'tautulliApikey'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function homepageOrdertautulli()
	{
		if ($this->homepageItemPermissions($this->tautulliHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Tautulli...</h2></div>
					<script>
						// Tautulli
						homepageTautulli("' . $this->config['homepageTautulliRefresh'] . '");
						// End Tautulli
					</script>
				</div>
				';
		}
	}
	
	public function getTautulliHomepageData()
	{
		if (!$this->homepageItemPermissions($this->tautulliHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['tautulliURL']);
		$apiURL = $url . '/api/v2?apikey=' . $this->config['tautulliApikey'];
		$height = $this->getCacheImageSize('h');
		$width = $this->getCacheImageSize('w');
		$nowPlayingHeight = $this->getCacheImageSize('nph');
		$nowPlayingWidth = $this->getCacheImageSize('npw');
		try {
			$homestatsUrl = $apiURL . '&cmd=get_home_stats&grouping=1';
			$options = $this->requestOptions($this->config['tautulliURL'], $this->config['homepageTautulliRefresh'], $this->config['tautulliDisableCertCheck'], $this->config['tautulliUseCustomCertificate']);
			$homestats = Requests::get($homestatsUrl, [], $options);
			if ($homestats->success) {
				$homestats = json_decode($homestats->body, true);
				$api['homestats'] = $homestats['response'];
				// Cache art & thumb for first result in each tautulli API result
				$categories = ['top_movies', 'top_tv', 'popular_movies', 'popular_tv'];
				foreach ($categories as $cat) {
					$key = array_search($cat, array_column($api['homestats']['data'], 'stat_id'));
					$img = $api['homestats']['data'][$key]['rows'][0];
					$this->cacheImage($url . '/pms_image_proxy?img=' . $img['art'] . '&rating_key=' . $img['rating_key'] . '&width=' . $nowPlayingWidth . '&height=' . $nowPlayingHeight, $img['rating_key'] . '-np');
					$this->cacheImage($url . '/pms_image_proxy?img=' . $img['thumb'] . '&rating_key=' . $img['rating_key'] . '&width=' . $width . '&height=' . $height, $img['rating_key'] . '-list');
					$img['art'] = 'plugins/images/cache/' . $img['rating_key'] . '-np.jpg';
					$img['thumb'] = 'plugins/images/cache/' . $img['rating_key'] . '-list.jpg';
					$api['homestats']['data'][$key]['rows'][0] = $img;
				}
				// Cache the platform icon
				$key = array_search('top_platforms', array_column($api['homestats']['data'], 'stat_id'));
				$platform = $api['homestats']['data'][$key]['rows'][0]['platform_name'];
				$this->cacheImage($url . '/images/platforms/' . $platform . '.svg', 'tautulli-' . $platform, 'svg');
			}
			$libstatsUrl = $apiURL . '&cmd=get_libraries_table';
			$options = $this->requestOptions($this->config['tautulliURL'], $this->config['homepageTautulliRefresh'], $this->config['tautulliDisableCertCheck'], $this->config['tautulliUseCustomCertificate']);
			$libstats = Requests::get($libstatsUrl, [], $options);
			if ($libstats->success) {
				$libstats = json_decode($libstats->body, true);
				$api['libstats'] = $libstats['response']['data'];
				$categories = ['movie.svg', 'show.svg', 'artist.svg'];
				foreach ($categories as $cat) {
					$parts = explode('.', $cat);
					$this->cacheImage($url . '/images/libraries/' . $cat, 'tautulli-' . $parts[0], $parts[1]);
				}
			}
			$api['options'] = [
				'url' => $url,
				'libraries' => $this->config['tautulliLibraries'],
				'topMovies' => $this->config['tautulliTopMovies'],
				'topTV' => $this->config['tautulliTopTV'],
				'topUsers' => $this->config['tautulliTopUsers'],
				'topPlatforms' => $this->config['tautulliTopPlatforms'],
				'popularMovies' => $this->config['tautulliPopularMovies'],
				'popularTV' => $this->config['tautulliPopularTV'],
				'title' => $this->config['tautulliHeaderToggle'],
				'friendlyName' => $this->config['tautulliFriendlyName'],
			];
			$ids = []; // Array of stat_ids to remove from the returned array
			if (!$this->qualifyRequest($this->config['homepageTautulliLibraryAuth'])) {
				$api['options']['libraries'] = false;
				unset($api['libstats']);
			}
			if (!$this->qualifyRequest($this->config['homepageTautulliViewsAuth'])) {
				$api['options']['topMovies'] = false;
				$api['options']['topTV'] = false;
				$api['options']['popularMovies'] = false;
				$api['options']['popularTV'] = false;
				$ids = array_merge(['top_movies', 'popular_movies', 'popular_tv', 'top_tv'], $ids);
				$api['homestats']['data'] = array_values($api['homestats']['data']);
			}
			if (!$this->qualifyRequest($this->config['homepageTautulliMiscAuth'])) {
				$api['options']['topUsers'] = false;
				$api['options']['topPlatforms'] = false;
				$ids = array_merge(['top_platforms', 'top_users'], $ids);
				$api['homestats']['data'] = array_values($api['homestats']['data']);
			}
			$ids = array_merge(['top_music', 'popular_music', 'last_watched', 'most_concurrent'], $ids);
			foreach ($ids as $id) {
				if ($key = array_search($id, array_column($api['homestats']['data'], 'stat_id'))) {
					unset($api['homestats']['data'][$key]);
					$api['homestats']['data'] = array_values($api['homestats']['data']);
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Tautulli Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}