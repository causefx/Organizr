<?php

trait TautulliHomepageItem
{
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
			$homestats = Requests::get($homestatsUrl, [], []);
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
	
	public function getTautulliHomepageData()
	{
		if (!$this->config['homepageTautulliEnabled']) {
			$this->setAPIResponse('error', 'Tautulli homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageTautulliAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['tautulliURL'])) {
			$this->setAPIResponse('error', 'Tautulli URL is not defined', 422);
			return false;
		}
		if (empty($this->config['tautulliApikey'])) {
			$this->setAPIResponse('error', 'Tautulli Token is not defined', 422);
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
			$homestats = Requests::get($homestatsUrl, [], []);
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
			$libstatsUrl = $apiURL . '&cmd=get_libraries';
			$libstats = Requests::get($libstatsUrl, [], []);
			if ($libstats->success) {
				$libstats = json_decode($libstats->body, true);
				$api['libstats'] = $libstats['response'];
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