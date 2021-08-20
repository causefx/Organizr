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
		$homepageSettings = array(
			'debug' => true,
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageTautulliEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageTautulliEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageTautulliAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageTautulliAuth'],
						'options' => $this->groupOptions
					)
				),
				'Options' => array(
					array(
						'type' => 'input',
						'name' => 'tautulliHeader',
						'label' => 'Title',
						'value' => $this->config['tautulliHeader'],
						'help' => 'Sets the title of this homepage module'
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliHeaderToggle',
						'label' => 'Toggle Title',
						'value' => $this->config['tautulliHeaderToggle'],
						'help' => 'Shows/hides the title of this homepage module'
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'tautulliURL',
						'label' => 'URL',
						'value' => $this->config['tautulliURL'],
						'help' => 'URL for Tautulli API, include the IP, the port and the base URL (e.g. /tautulli/) in the URL',
						'placeholder' => 'http://<ip>:<port>'
					),
					array(
						'type' => 'password-alt',
						'name' => 'tautulliApikey',
						'label' => 'API Key',
						'value' => $this->config['tautulliApikey']
					),
					array(
						'type' => 'select',
						'name' => 'homepageTautulliRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageTautulliRefresh'],
						'options' => $this->timeOptions()
					),
				),
				'API SOCKS' => array(
					array(
						'type' => 'html',
						'override' => 12,
						'label' => '',
						'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">' . $this->socksHeadingHTML('tautulli') . '</div>
								</div>
							</div>'
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliSocksEnabled',
						'label' => 'Enable',
						'value' => $this->config['tautulliSocksEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'tautulliSocksAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['tautulliSocksAuth'],
						'options' => $this->groupOptions
					),
				),
				'Library Stats' => array(
					array(
						'type' => 'switch',
						'name' => 'tautulliLibraries',
						'label' => 'Libraries',
						'value' => $this->config['tautulliLibraries'],
						'help' => 'Shows/hides the card with library information.',
					),
					array(
						'type' => 'select',
						'name' => 'homepageTautulliLibraryAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageTautulliLibraryAuth'],
						'options' => $this->groupOptions
					),
				),
				'Viewing Stats' => array(
					array(
						'type' => 'switch',
						'name' => 'tautulliPopularMovies',
						'label' => 'Popular Movies',
						'value' => $this->config['tautulliPopularMovies'],
						'help' => 'Shows/hides the card with Popular Movies information.',
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliPopularTV',
						'label' => 'Popular TV',
						'value' => $this->config['tautulliPopularTV'],
						'help' => 'Shows/hides the card with Popular TV information.',
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliTopMovies',
						'label' => 'Top Movies',
						'value' => $this->config['tautulliTopMovies'],
						'help' => 'Shows/hides the card with Top Movies information.',
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliTopTV',
						'label' => 'Top TV',
						'value' => $this->config['tautulliTopTV'],
						'help' => 'Shows/hides the card with Top TV information.',
					),
					array(
						'type' => 'select',
						'name' => 'homepageTautulliViewsAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageTautulliViewsAuth'],
						'options' => $this->groupOptions
					),
				),
				'Misc Stats' => array(
					array(
						'type' => 'switch',
						'name' => 'tautulliTopUsers',
						'label' => 'Top Users',
						'value' => $this->config['tautulliTopUsers'],
						'help' => 'Shows/hides the card with Top Users information.',
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliTopPlatforms',
						'label' => 'Top Platforms',
						'value' => $this->config['tautulliTopPlatforms'],
						'help' => 'Shows/hides the card with Top Platforms information.',
					),
					array(
						'type' => 'select',
						'name' => 'homepageTautulliMiscAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageTautulliMiscAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'switch',
						'name' => 'tautulliFriendlyName',
						'label' => 'Use Friendly Name',
						'value' => $this->config['tautulliFriendlyName'],
						'help' => 'Use the friendly name set in tautulli for users.',
					),
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
						'attr' => 'onclick="testAPIConnection(\'tautulli\')"'
					),
				)
			)
		);
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
			$options = $this->requestOptions($this->config['tautulliURL'], false, $this->config['homepageTautulliRefresh']);
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
			$options = $this->requestOptions($this->config['tautulliURL'], false, $this->config['homepageTautulliRefresh']);
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
			$options = $this->requestOptions($this->config['tautulliURL'], false, $this->config['homepageTautulliRefresh']);
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