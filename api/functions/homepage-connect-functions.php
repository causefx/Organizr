<?php
/** @noinspection PhpUndefinedFieldInspection */

trait HomepageConnectFunctions
{
	public function csvHomepageUrlToken($url, $token)
	{
		$list = array();
		$urlList = explode(',', $url);
		$tokenList = explode(',', $token);
		foreach ($urlList as $key => $value) {
			if (isset($tokenList[$key])) {
				$list[$key] = array(
					'url' => $this->qualifyURL($value),
					'token' => $tokenList[$key]
				);
			}
		}
		return $list;
	}

	public function streamType($value)
	{
		if ($value == "transcode" || $value == "Transcode") {
			return "Transcode";
		} elseif ($value == "copy" || $value == "DirectStream") {
			return "Direct Stream";
		} elseif ($value == "directplay" || $value == "DirectPlay") {
			return "Direct Play";
		} else {
			return "Direct Play";
		}
	}

	public function getCacheImageSize($type)
	{
		switch ($type) {
			case 'height':
			case 'h':
				return round(300 * $this->config['cacheImageSize']);
			case 'width':
			case 'w':
				return round(200 * $this->config['cacheImageSize']);
			case 'nowPlayingHeight':
			case 'nph':
				return round(675 * $this->config['cacheImageSize']);
			case 'nowPlayingWidth':
			case 'npw':
				return round(1200 * $this->config['cacheImageSize']);
		}
	}

	public function ombiImport($type = null)
	{
		if (!empty($this->config['ombiURL']) && !empty($this->config['ombiToken']) && !empty($type)) {
			try {
				$url = $this->qualifyURL($this->config['ombiURL']);
				$headers = array(
					"Accept" => "application/json",
					"Content-Type" => "application/json",
					"Apikey" => $this->config['ombiToken']
				);
				$options = ($this->localURL($url)) ? array('verify' => false) : array();
				switch ($type) {
					case 'emby':
					case 'emby_local':
					case 'emby_connect':
					case 'emby_all':
						$response = Requests::post($url . "/api/v1/Job/embyuserimporter", $headers, $options);
						break;
					case 'plex':
						$response = Requests::post($url . "/api/v1/Job/plexuserimporter", $headers, $options);
						break;
					default:
						return false;
						break;
				}
				if ($response->success) {
					$this->setLoggerChannel('Ombi')->info('Ran User Import');
					return true;
				} else {
					$this->setLoggerChannel('Ombi')->warning('Unsuccessful connection');
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->setLoggerChannel('Ombi')->error($e);
				return false;
			}
		}
		return false;
	}
}