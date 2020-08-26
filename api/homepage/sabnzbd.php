<?php

trait SabNZBdHomepageItem
{
	public function testConnectionSabNZBd()
	{
		if (!empty($this->config['sabnzbdURL']) && !empty($this->config['sabnzbdToken'])) {
			$url = $this->qualifyURL($this->config['sabnzbdURL']);
			$url = $url . '/api?mode=queue&output=json&apikey=' . $this->config['sabnzbdToken'];
			try {
				$options = ($this->localURL($url)) ? array('verify' => false) : array();
				$response = Requests::get($url, array(), $options);
				if ($response->success) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				}
			} catch (Requests_Exception $e) {
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			};
		} else {
			$this->setAPIResponse('error', 'URL and/or Token not setup', 422);
			return 'URL and/or Token not setup';
		}
	}
	
	public function getSabNZBdHomepageQueue()
	{
		if (!$this->config['homepageSabnzbdEnabled']) {
			$this->setAPIResponse('error', 'SabNZBd homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSabnzbdAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['sabnzbdURL'])) {
			$this->setAPIResponse('error', 'Plex URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sabnzbdToken'])) {
			$this->setAPIResponse('error', 'Plex Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$url = $url . '/api?mode=queue&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$url = $url . '/api?mode=history&output=json&limit=100&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['historyItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function pauseSabNZBdQueue($target = null)
	{
		if (!$this->config['homepageSabnzbdEnabled']) {
			$this->setAPIResponse('error', 'SabNZBd homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSabnzbdAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['sabnzbdURL'])) {
			$this->setAPIResponse('error', 'Plex URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sabnzbdToken'])) {
			$this->setAPIResponse('error', 'Plex Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=pause&value=' . $target . '&' : 'mode=pause';
		$url = $url . '/api?' . $id . '&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function resumeSabNZBdQueue($target = null)
	{
		if (!$this->config['homepageSabnzbdEnabled']) {
			$this->setAPIResponse('error', 'SabNZBd homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSabnzbdAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['sabnzbdURL'])) {
			$this->setAPIResponse('error', 'Plex URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sabnzbdToken'])) {
			$this->setAPIResponse('error', 'Plex Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=resume&value=' . $target . '&' : 'mode=resume';
		$url = $url . '/api?' . $id . '&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}